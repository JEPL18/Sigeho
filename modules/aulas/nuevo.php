<?php
// modules/aulas/nuevo.php
session_start();
if (!isset($_SESSION['usuario_id']) || strtolower($_SESSION['rol']) != 'administrador') {
    header("Location: index.php");
    exit();
}

require '../../config/db.php';
$error = ""; $success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo = trim(strtoupper($_POST['codigo']));
    $capacidad = (int)$_POST['capacidad'];
    $estado = $_POST['estado'];

    if (empty($codigo) || empty($capacidad) || empty($estado)) {
        $error = "Todos los campos son obligatorios.";
    } else {
        try {
            $check = $conn->prepare("SELECT id FROM aulas WHERE codigo = ?");
            $check->execute([$codigo]);
            if($check->rowCount() > 0){
                $error = "Ya existe un aula registrada con ese código.";
            } else {
                $stmt = $conn->prepare("INSERT INTO aulas (codigo, capacidad, estado) VALUES (?, ?, ?)");
                $stmt->execute([$codigo, $capacidad, $estado]);
                $success = "Aula registrada correctamente.";
            }
        } catch(PDOException $e) { 
            $error = "Error al guardar: " . $e->getMessage(); 
        }
    }
}

$ruta = '../../'; include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
    <h2 class="fw-bold mb-0 text-dark"><i class="bi bi-door-closed text-danger me-2"></i> Registrar Aula</h2>
    <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <?php if($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
                <?php if($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

                <form action="nuevo.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Código del Espacio (Ej: M-110)</label>
                        <input type="text" name="codigo" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Capacidad (Número de Alumnos)</label>
                        <input type="number" name="capacidad" class="form-control" min="1" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold">Estado Inicial</label>
                        <select name="estado" class="form-select" required>
                            <option value="OPERATIVA">OPERATIVA</option>
                            <option value="INOPERATIVA">INOPERATIVA / MANTENIMIENTO</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-danger w-100 fw-bold" style="background-color: #8B1A1A;">
                        <i class="bi bi-save"></i> Guardar Aula
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>