<?php
// backup.php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

// SI ALGUIEN INTENTA ENTRAR DIRECTO SIN PASAR POR EL MODAL, LO REBOTAMOS
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['password_respaldo'])) {
    header("Location: configuracion.php");
    exit();
}

require 'config/db.php';

// 1. OBTENEMOS LA CLAVE GENERADA EN EL MODAL
$password_cifrado = $_POST['password_respaldo'];

$fecha_backup = date("Y-m-d_H-i-s");
$nombre_zip = "Respaldo_SIGEHO_" . $fecha_backup . ".zip";
$ruta_zip = sys_get_temp_dir() . '/' . $nombre_zip;

$zip = new ZipArchive();
if ($zip->open($ruta_zip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    die("Error crítico: No se pudo crear el archivo ZIP en el servidor.");
}

// 2. VOLCADO DE LA BASE DE DATOS
$tablas = ['aulas', 'configuracion', 'horarios', 'log_choques', 'materias', 'profesores', 'secciones', 'usuarios'];
$sql_dump = "-- ==========================================\n";
$sql_dump .= "-- RESPALDO DE BASE DE DATOS SIGEHO-CONT\n";
$sql_dump .= "-- Fecha de Generación: " . date('Y-m-d H:i:s') . "\n";
$sql_dump .= "-- ==========================================\n\n";

foreach ($tablas as $tabla) {
    $stmt = $conn->query("SELECT * FROM $tabla");
    $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($filas) > 0) {
        $sql_dump .= "-- Volcado de datos para la tabla `$tabla`\n";
        foreach ($filas as $fila) {
            $columnas = array_keys($fila);
            $valores = array_map(function($val) {
                if ($val === null) return 'NULL';
                return "'" . addslashes($val) . "'";
            }, array_values($fila));
            
            $sql_dump .= "INSERT INTO `$tabla` (`" . implode("`, `", $columnas) . "`) VALUES (" . implode(", ", $valores) . ");\n";
        }
        $sql_dump .= "\n";
    }
}

$zip->addFromString('base_de_datos_sigeho.sql', $sql_dump);
$zip->setEncryptionName('base_de_datos_sigeho.sql', ZipArchive::EM_AES_256, $password_cifrado);

// 3. RESPALDO DE IMÁGENES
$dir_img = 'assets/img';
if (is_dir($dir_img)) {
    $archivos = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir_img), RecursiveIteratorIterator::LEAVES_ONLY);
    
    foreach ($archivos as $name => $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen(realpath($dir_img)) + 1);
            
            $zip->addFile($filePath, 'imagenes_sistema/' . $relativePath);
            $zip->setEncryptionName('imagenes_sistema/' . $relativePath, ZipArchive::EM_AES_256, $password_cifrado);
        }
    }
}

$zip->close();

// 4. FORZAR LA DESCARGA 
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $nombre_zip . '"');
header('Content-Length: ' . filesize($ruta_zip));
header('Pragma: no-cache');
header('Expires: 0');

readfile($ruta_zip);

// 5. LIMPIEZA
unlink($ruta_zip);
exit();
?>