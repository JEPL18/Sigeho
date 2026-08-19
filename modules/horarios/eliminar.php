<?php
// modules/horarios/eliminar.php
session_start();
// EL CANDADO
if (!isset($_SESSION['usuario_id']) || strtolower($_SESSION['rol']) != 'administrador') {
    header("Location: index.php");
    exit();
}

require '../../config/db.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM horarios WHERE id = ?");
        $stmt->execute([$id]);
    } catch(PDOException $e) {
        // Ignorar si hay un fallo
    }
}
header("Location: index.php?msg=eliminado");
exit();
?>