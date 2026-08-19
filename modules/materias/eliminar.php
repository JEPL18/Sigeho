<?php
// modules/materias/eliminar.php
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
        $stmt = $conn->prepare("DELETE FROM materias WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: index.php?msg=eliminado");
        exit();
    } catch(PDOException $e) {
        // Bloqueo de seguridad: Si la materia está asignada en "horarios", 
        // mandamos la alerta de vuelta al index
        header("Location: index.php?msg=error_uso");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>