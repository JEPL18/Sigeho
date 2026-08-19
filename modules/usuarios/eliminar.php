<?php
// modules/usuarios/eliminar.php
session_start();
if (!isset($_SESSION['usuario_id']) || strtolower($_SESSION['rol']) != 'administrador') {
    header("Location: ../../dashboard.php");
    exit();
}

require '../../config/db.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Evitar que el administrador se elimine a sí mismo
    if ($id != $_SESSION['usuario_id']) {
        try {
            $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: index.php?msg=eliminado");
            exit();
        } catch(PDOException $e) {
            // Manejar error en caso de que la BD bloquee el borrado
            header("Location: index.php?msg=error_uso");
            exit();
        }
    } else {
        // Intento de auto-eliminación
        header("Location: index.php?msg=error_propio");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>