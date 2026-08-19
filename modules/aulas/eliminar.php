<?php
// modules/aulas/eliminar.php
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
        $stmt = $conn->prepare("DELETE FROM aulas WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: index.php?msg=eliminado");
        exit();
    } catch(PDOException $e) {
        // Si el aula ya está asignada en la tabla de horarios, MySQL bloquea el borrado.
        // Redirigimos con la alerta.
        header("Location: index.php?msg=error_uso");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>