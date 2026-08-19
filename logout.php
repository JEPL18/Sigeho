<?php
// logout.php
session_start();

// --- NUEVO: REGISTRO DE BITÁCORA (SALIDA) ---
if (isset($_SESSION['usuario_id'])) {
    require 'config/db.php';
    try {
        $accion = "Cierre de sesión manual";
        $stmt_bitacora = $conn->prepare("INSERT INTO bitacora (usuario_id, accion) VALUES (?, ?)");
        $stmt_bitacora->execute([$_SESSION['usuario_id'], $accion]);
    } catch(PDOException $e) {
        // Silencioso, si falla la bitácora igual debe dejar salir al usuario.
    }
}
// ---------------------------------------------

session_destroy(); // Destruye la sesión segura
header("Location: index.php"); // Te devuelve al Login
exit();
?>