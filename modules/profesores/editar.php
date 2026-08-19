<?php
// modules/profesores/editar.php
session_start();
// EL CANDADO
if (!isset($_SESSION['usuario_id']) || strtolower($_SESSION['rol']) != 'administrador') {
    header("Location: index.php");
    exit();
}

require '../../config/db.php';
$error = ""; $success = "";

if (!isset($_GET['id'])) { header("Location: index.php"); exit(); }
$id = (int)$_GET['id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cedula = trim(strtoupper($_POST['cedula']));
    $nombre = trim(ucwords(strtolower($_POST['nombre'])));
    $estado = $_POST['estado'];

    if (empty($cedula) || empty($nombre) || empty($estado)) {
        $error = "Todos los campos son obligatorios.";
    } else {
        try {
            $check = $conn->prepare("SELECT id FROM profesores WHERE cedula = ? AND id != ?");
            $check->execute([$cedula, $id]);
            
            if($check->rowCount() > 0){
                $error = "Esta cédula ya está asociada a otro docente.";
            } else {
                $stmt = $conn->prepare("UPDATE profesores SET cedula=?, nombre=?, estado=? WHERE id=?");
                $stmt->execute([$cedula, $nombre, $estado, $id]);
                $success = "Datos del docente actualizados correctamente.";
            }
        } catch(PDOException $e) { 
            $error = "Error al actualizar: " . $e->getMessage(); 
        }
    }
}

$stmt = $conn->prepare("SELECT * FROM profesores WHERE id = ?");
$stmt->execute([$id]);
$profesor = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$profesor) { header("Location: index.php"); exit(); }

$ruta = '../../'; include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
    <h2 class="fw-bold mb-0 text-dark"><i class="bi bi-pencil-square text-danger me-2"></i> Editar Profesor</h2>
    <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <?php if($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
                <?php if($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

                <form action="editar.php?id=<?php echo $id; ?>" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Cédula de Identidad</label>
                        <input type="text" name="cedula" class="form-control" value="<?php echo htmlspecialchars($profesor['cedula']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre Completo</label>
                        <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($profesor['nombre']); ?>" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold">Estado</label>
                        <select name="estado" class="form-select" required>
                            <option value="ACTIVO" <?php echo ($profesor['estado'] == 'ACTIVO') ? 'selected' : ''; ?>>ACTIVO</option>
                            <option value="INACTIVO" <?php echo ($profesor['estado'] == 'INACTIVO') ? 'selected' : ''; ?>>INACTIVO</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 fw-bold"><i class="bi bi-save"></i> Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>