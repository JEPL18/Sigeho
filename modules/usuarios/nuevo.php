<?php
// modules/usuarios/nuevo.php
session_start();
if (!isset($_SESSION['usuario_id']) || strtolower($_SESSION['rol']) != 'administrador') {
    header("Location: index.php");
    exit();
}

require '../../config/db.php';
$error = ""; $success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim(ucwords(strtolower($_POST['nombre'])));
    $correo = trim(strtolower($_POST['correo']));
    $rol = $_POST['rol'];
    $password = $_POST['password'];
    
    // Las 3 preguntas fijas (Con tus signos de interrogación)
    $p1 = "¿Fecha de cumpleaños?";
    $r1 = strtolower(trim($_POST['respuesta_seguridad_1']));
    
    $p2 = "¿Nombre del colegio donde estudiaste?";
    $r2 = strtolower(trim($_POST['respuesta_seguridad_2']));
    
    $p3 = "¿Ciudad donde naciste?";
    $r3 = strtolower(trim($_POST['respuesta_seguridad_3']));

    if (empty($nombre) || empty($correo) || empty($rol) || empty($password) || empty($r1) || empty($r2) || empty($r3)) {
        $error = "Todos los campos y respuestas son obligatorios.";
    } elseif (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    } else {
        try {
            $check = $conn->prepare("SELECT id FROM usuarios WHERE correo = ?");
            $check->execute([$correo]);
            
            if ($check->rowCount() > 0) {
                $error = "Este correo ya está registrado en otra cuenta.";
            } else {
                $pass_hash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO usuarios (nombre, correo, rol, password, pregunta_seguridad_1, respuesta_seguridad_1, pregunta_seguridad_2, respuesta_seguridad_2, pregunta_seguridad_3, respuesta_seguridad_3) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$nombre, $correo, $rol, $pass_hash, $p1, $r1, $p2, $r2, $p3, $r3]);
                
                $success = "Usuario registrado exitosamente.";
            }
        } catch(PDOException $e) { $error = "Error DB: " . $e->getMessage(); }
    }
}

$ruta = '../../'; include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
    <h2 class="fw-bold mb-0 text-dark"><i class="bi bi-person-plus-fill text-info me-2"></i> Crear Nueva Cuenta</h2>
    <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <?php if($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
                <?php if($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

                <form action="nuevo.php" method="POST">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nombre del Personal</label>
                            <input type="text" name="nombre" class="form-control" placeholder="Ej: Lcda. María Pérez" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Correo Institucional (Acceso)</label>
                            <input type="email" name="correo" class="form-control" placeholder="maria@uptag.edu.ve" required>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nivel de Permisos</label>
                            <select name="rol" class="form-select" required>
                                <option value="Asistente">Asistente (Solo Lectura)</option>
                                <option value="Administrador">Administrador (Control Total)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Contraseña de Acceso</label>
                            <input type="password" name="password" class="form-control" placeholder="Mínimo 6 caracteres" required>
                        </div>
                    </div>

                    <h5 class="fw-bold text-danger border-bottom pb-2 mb-3"><i class="bi bi-shield-lock-fill"></i> Sistema de Recuperación (3 Pasos)</h5>
                    
                    <div class="row mb-3 bg-light p-3 rounded align-items-center">
                        <div class="col-md-6">
                            <span class="fw-bold text-dark"><i class="bi bi-1-circle-fill text-danger me-1"></i> ¿Fecha de cumpleaños?</span>
                            <small class="d-block text-muted">(Ej: 15/04/1990)</small>
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="respuesta_seguridad_1" class="form-control border-danger" required>
                        </div>
                    </div>

                    <div class="row mb-3 bg-light p-3 rounded align-items-center">
                        <div class="col-md-6">
                            <span class="fw-bold text-dark"><i class="bi bi-2-circle-fill text-danger me-1"></i> ¿Nombre del colegio donde estudiaste?</span>
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="respuesta_seguridad_2" class="form-control border-danger" required>
                        </div>
                    </div>

                    <div class="row mb-4 bg-light p-3 rounded align-items-center">
                        <div class="col-md-6">
                            <span class="fw-bold text-dark"><i class="bi bi-3-circle-fill text-danger me-1"></i> ¿Ciudad donde naciste?</span>
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="respuesta_seguridad_3" class="form-control border-danger" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-info text-white w-100 fw-bold fs-5"><i class="bi bi-check-circle"></i> Guardar Usuario</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>