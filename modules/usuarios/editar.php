<?php
// modules/usuarios/editar.php
session_start();
if (!isset($_SESSION['usuario_id']) || strtolower($_SESSION['rol']) != 'administrador') {
    header("Location: index.php");
    exit();
}

require '../../config/db.php';
$error = ""; $success = "";

if (!isset($_GET['id'])) { header("Location: index.php"); exit(); }
$id_editar = (int)$_GET['id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim(ucwords(strtolower($_POST['nombre'])));
    $correo = trim(strtolower($_POST['correo']));
    $rol = $_POST['rol'];
    $nueva_password = $_POST['password'];
    
    // Preguntas fijas
    $p1 = "¿Fecha de cumpleaños?";
    $p2 = "¿Nombre del colegio donde estudiaste?";
    $p3 = "¿Ciudad donde naciste?";
    
    $r1 = strtolower(trim($_POST['respuesta_seguridad_1']));
    $r2 = strtolower(trim($_POST['respuesta_seguridad_2']));
    $r3 = strtolower(trim($_POST['respuesta_seguridad_3']));

    if (empty($nombre) || empty($correo) || empty($rol)) {
        $error = "Nombre, correo y rol son obligatorios.";
    } else {
        try {
            if ($id_editar == $_SESSION['usuario_id'] && strtolower($rol) != 'administrador') {
                $error = "No puedes quitarte los permisos de administrador a ti mismo.";
            } else {
                $check = $conn->prepare("SELECT id FROM usuarios WHERE correo = ? AND id != ?");
                $check->execute([$correo, $id_editar]);
                
                if ($check->rowCount() > 0) {
                    $error = "El correo ya está en uso por otra persona.";
                } else {
                    $sql = "UPDATE usuarios SET nombre=?, correo=?, rol=?, pregunta_seguridad_1=?, pregunta_seguridad_2=?, pregunta_seguridad_3=?";
                    $params = [$nombre, $correo, $rol, $p1, $p2, $p3];

                    if (!empty($nueva_password)) { $sql .= ", password=?"; $params[] = password_hash($nueva_password, PASSWORD_DEFAULT); }
                    if (!empty($r1)) { $sql .= ", respuesta_seguridad_1=?"; $params[] = $r1; }
                    if (!empty($r2)) { $sql .= ", respuesta_seguridad_2=?"; $params[] = $r2; }
                    if (!empty($r3)) { $sql .= ", respuesta_seguridad_3=?"; $params[] = $r3; }
                    
                    $sql .= " WHERE id=?";
                    $params[] = $id_editar;

                    $stmt = $conn->prepare($sql);
                    $stmt->execute($params);
                    $success = "Datos actualizados correctamente.";
                }
            }
        } catch(PDOException $e) { $error = "Error DB: " . $e->getMessage(); }
    }
}

$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$id_editar]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$usuario) { header("Location: index.php"); exit(); }

$ruta = '../../'; include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
    <h2 class="fw-bold mb-0 text-dark"><i class="bi bi-person-gear text-info me-2"></i> Editar Cuenta</h2>
    <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <?php if($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
                <?php if($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

                <form action="editar.php?id=<?php echo $id_editar; ?>" method="POST">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nombre del Personal</label>
                            <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Correo Institucional</label>
                            <input type="email" name="correo" class="form-control" value="<?php echo htmlspecialchars($usuario['correo']); ?>" required>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Rol</label>
                            <select name="rol" class="form-select" required <?php echo ($id_editar == $_SESSION['usuario_id']) ? 'disabled' : ''; ?>>
                                <option value="Asistente" <?php echo (strtolower($usuario['rol']) == 'asistente') ? 'selected' : ''; ?>>Asistente</option>
                                <option value="Administrador" <?php echo (strtolower($usuario['rol']) == 'administrador') ? 'selected' : ''; ?>>Administrador</option>
                            </select>
                            <?php if($id_editar == $_SESSION['usuario_id']): ?>
                                <input type="hidden" name="rol" value="Administrador">
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nueva Contraseña</label>
                            <input type="password" name="password" class="form-control" placeholder="(Dejar en blanco para no cambiar)">
                        </div>
                    </div>

                    <h5 class="fw-bold text-danger border-bottom pb-2 mb-3"><i class="bi bi-shield-lock-fill"></i> Sistema de Recuperación (3 Pasos)</h5>
                    
                    <div class="row mb-3 bg-light p-3 rounded align-items-center">
                        <div class="col-md-6">
                            <span class="fw-bold text-dark"><i class="bi bi-1-circle-fill text-danger me-1"></i> ¿Fecha de cumpleaños?</span>
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="respuesta_seguridad_1" class="form-control border-danger" placeholder="(Dejar en blanco para no cambiar)">
                        </div>
                    </div>

                    <div class="row mb-3 bg-light p-3 rounded align-items-center">
                        <div class="col-md-6">
                            <span class="fw-bold text-dark"><i class="bi bi-2-circle-fill text-danger me-1"></i> ¿Nombre del colegio donde estudiaste?</span>
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="respuesta_seguridad_2" class="form-control border-danger" placeholder="(Dejar en blanco para no cambiar)">
                        </div>
                    </div>

                    <div class="row mb-4 bg-light p-3 rounded align-items-center">
                        <div class="col-md-6">
                            <span class="fw-bold text-dark"><i class="bi bi-3-circle-fill text-danger me-1"></i> ¿Ciudad donde naciste?</span>
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="respuesta_seguridad_3" class="form-control border-danger" placeholder="(Dejar en blanco para no cambiar)">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-bold fs-5"><i class="bi bi-save"></i> Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>