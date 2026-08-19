<?php
// modules/aulas/editar.php
session_start();
if (!isset($_SESSION['usuario_id']) || strtolower($_SESSION['rol']) != 'administrador') {
    header("Location: index.php");
    exit();
}

require '../../config/db.php';
$error = ""; $success = "";

if (!isset($_GET['id'])) { header("Location: index.php"); exit(); }
$id = (int)$_GET['id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo = trim(strtoupper($_POST['codigo']));
    $capacidad = (int)$_POST['capacidad'];
    $estado = $_POST['estado'];

    if (empty($codigo) || empty($capacidad) || empty($estado)) {
        $error = "Todos los campos son obligatorios.";
    } else {
        try {
            $check = $conn->prepare("SELECT id FROM aulas WHERE codigo = ? AND id != ?");
            $check->execute([$codigo, $id]);
            
            if($check->rowCount() > 0){
                $error = "El código ya pertenece a otra aula.";
            } else {
                $stmt = $conn->prepare("UPDATE aulas SET codigo=?, capacidad=?, estado=? WHERE id=?");
                $stmt->execute([$codigo, $capacidad, $estado, $id]);
                $success = "Aula actualizada correctamente.";
            }
        } catch(PDOException $e) { 
            $error = "Error al actualizar: " . $e->getMessage(); 
        }
    }
}

$stmt = $conn->prepare("SELECT * FROM aulas WHERE id = ?");
$stmt->execute([$id]);
$aula = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$aula) { header("Location: index.php"); exit(); }

$ruta = '../../'; include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
    <h2 class="fw-bold mb-0 text-dark"><i class="bi bi-pencil-square text-danger me-2"></i> Editar Aula</h2>
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
                        <label class="form-label fw-bold">Código del Espacio</label>
                        <input type="text" name="codigo" class="form-control" value="<?php echo htmlspecialchars($aula['codigo']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Capacidad (Alumnos)</label>
                        <input type="number" name="capacidad" class="form-control" value="<?php echo $aula['capacidad']; ?>" min="1" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold">Estado</label>
                        <select name="estado" class="form-select" required>
                            <option value="OPERATIVA" <?php echo ($aula['estado'] == 'OPERATIVA') ? 'selected' : ''; ?>>OPERATIVA</option>
                            <option value="INOPERATIVA" <?php echo ($aula['estado'] == 'INOPERATIVA') ? 'selected' : ''; ?>>INOPERATIVA</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold"><i class="bi bi-save"></i> Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>