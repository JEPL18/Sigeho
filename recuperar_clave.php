<?php
// recuperar_clave.php
session_start();
require 'config/db.php'; // Conexión a la base de datos

$error = "";
$step = 1; // Paso por defecto: Pedir el correo

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        
        // PASO 1: Validar si el correo existe
        if ($_POST['action'] == 'verificar_correo') {
            $correo = trim(strtolower($_POST['correo']));
            $stmt = $conn->prepare("SELECT id FROM usuarios WHERE correo = ?");
            $stmt->execute([$correo]);
            
            if ($stmt->rowCount() > 0) {
                $_SESSION['reset_email'] = $correo; // Guardamos el correo en sesión temporalmente
                $step = 2; // Avanzar a las preguntas
            } else {
                $error = "No existe ninguna cuenta vinculada a este correo institucional.";
            }
        }
        
        // PASO 2: Validar las 3 respuestas de seguridad
        elseif ($_POST['action'] == 'verificar_respuestas') {
            $r1 = strtolower(trim($_POST['r1']));
            $r2 = strtolower(trim($_POST['r2']));
            $r3 = strtolower(trim($_POST['r3']));
            $correo = $_SESSION['reset_email'];

            $stmt = $conn->prepare("SELECT id FROM usuarios WHERE correo = ? AND respuesta_seguridad_1 = ? AND respuesta_seguridad_2 = ? AND respuesta_seguridad_3 = ?");
            $stmt->execute([$correo, $r1, $r2, $r3]);

            if ($stmt->rowCount() > 0) {
                $_SESSION['reset_autorizado'] = true; // Permiso concedido para cambiar clave
                $step = 3; // Avanzar al formulario de nueva clave
            } else {
                $error = "Respuestas incorrectas. Verifique la ortografía e intente nuevamente.";
                $step = 2; // Mantener en las preguntas
            }
        }
        
        // PASO 3: Guardar la nueva contraseña
        elseif ($_POST['action'] == 'actualizar_clave') {
            if (isset($_SESSION['reset_autorizado']) && $_SESSION['reset_autorizado'] === true) {
                $nueva_clave = $_POST['nueva_clave'];
                
                if (strlen($nueva_clave) < 6) {
                    $error = "La contraseña debe tener al menos 6 caracteres.";
                    $step = 3;
                } else {
                    $pass_hash = password_hash($nueva_clave, PASSWORD_DEFAULT);
                    $correo = $_SESSION['reset_email'];
                    
                    $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE correo = ?");
                    $stmt->execute([$pass_hash, $correo]);
                    
                    // Limpiar la seguridad temporal
                    unset($_SESSION['reset_email']);
                    unset($_SESSION['reset_autorizado']);
                    
                    // Redirigir al login con éxito
                    header("Location: index.php?msg=clave_recuperada");
                    exit();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Acceso | SIGEHO-CONT</title>
    <!-- Favicon -->
    <link rel="icon" href="assets/img/logo_conta.jpeg" type="image/jpeg">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- NUESTRO CSS CENTRALIZADO -->
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body class="login-container">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow border-0 rounded-3">
                <div class="card-header bg-uptag text-white text-center py-4 rounded-top-3 border-0">
                    <h3 class="fw-bold mb-0"><i class="bi bi-shield-lock"></i> Recuperar Acceso</h3>
                    <p class="mb-0 small text-white-50">SIGEHO-CONT | Contaduría UPTAG</p>
                </div>
                
                <div class="card-body p-4">
                    <?php if($error): ?>
                        <div class="alert alert-danger shadow-sm"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php if($step == 1): ?>
                        <p class="text-muted text-center mb-4">Ingrese su correo institucional para buscar su cuenta y validar su identidad.</p>
                        <form action="recuperar_clave.php" method="POST">
                            <input type="hidden" name="action" value="verificar_correo">
                            <div class="mb-4">
                                <label class="form-label fw-bold">Correo Institucional</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-envelope" style="color: #8B1A1A;"></i></span>
                                    <input type="email" name="correo" class="form-control" placeholder="ejemplo@uptag.edu.ve" required autofocus>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-danger w-100 fw-bold py-2 mb-3" style="background-color: #8B1A1A;">Continuar <i class="bi bi-arrow-right"></i></button>
                            <div class="text-center">
                                <a href="index.php" class="text-decoration-none text-secondary"><i class="bi bi-arrow-left"></i> Volver al Inicio</a>
                            </div>
                        </form>
                    <?php endif; ?>

                    <?php if($step == 2): ?>
                        <div class="alert alert-info bg-light border-info text-dark shadow-sm">
                            <i class="bi bi-person-check-fill text-info"></i> Cuenta encontrada: <strong><?php echo $_SESSION['reset_email']; ?></strong>
                        </div>
                        <p class="text-muted text-center mb-4">Responda correctamente sus 3 preguntas de seguridad para autorizar el cambio de clave.</p>
                        
                        <form action="recuperar_clave.php" method="POST">
                            <input type="hidden" name="action" value="verificar_respuestas">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold"><i class="bi bi-1-circle-fill" style="color: #8B1A1A;"></i> ¿Fecha de cumpleaños?</label>
                                <input type="text" name="r1" class="form-control" placeholder="Su respuesta" required autofocus>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold"><i class="bi bi-2-circle-fill" style="color: #8B1A1A;"></i> ¿Nombre del colegio donde estudiaste?</label>
                                <input type="text" name="r2" class="form-control" placeholder="Su respuesta" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold"><i class="bi bi-3-circle-fill" style="color: #8B1A1A;"></i> ¿Ciudad donde naciste?</label>
                                <input type="text" name="r3" class="form-control" placeholder="Su respuesta" required>
                            </div>

                            <button type="submit" class="btn btn-danger w-100 fw-bold py-2 mb-3" style="background-color: #8B1A1A;"><i class="bi bi-shield-check"></i> Verificar Identidad</button>
                            <div class="text-center">
                                <a href="recuperar_clave.php" class="text-decoration-none text-secondary">Cancelar</a>
                            </div>
                        </form>
                    <?php endif; ?>

                    <?php if($step == 3): ?>
                        <div class="text-center mb-4">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                            <h5 class="fw-bold mt-2">¡Identidad Verificada!</h5>
                            <p class="text-muted small">Por favor, ingrese su nueva contraseña.</p>
                        </div>
                        
                        <form action="recuperar_clave.php" method="POST">
                            <input type="hidden" name="action" value="actualizar_clave">
                            <div class="mb-4">
                                <label class="form-label fw-bold">Nueva Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-key" style="color: #8B1A1A;"></i></span>
                                    <input type="password" name="nueva_clave" class="form-control" placeholder="Mínimo 6 caracteres" required autofocus>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success w-100 fw-bold py-2"><i class="bi bi-floppy"></i> Guardar y Acceder</button>
                        </form>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>