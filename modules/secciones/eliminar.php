<?php
// modules/secciones/eliminar.php
session_start();
if (!isset($_SESSION['usuario_id']) || strtolower($_SESSION['rol']) != 'administrador') {
    header("Location: index.php");
    exit();
}

require '../../config/db.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM secciones WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: index.php?msg=eliminado");
        exit();
    } catch(PDOException $e) {
        // Bloqueo de seguridad
        header("Location: index.php?msg=error_uso");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>