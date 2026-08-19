<?php
// modules/profesores/nuevo.php
session_start();
// EL CANDADO
if (!isset($_SESSION['usuario_id']) || strtolower($_SESSION['rol']) != 'administrador') {
    header("Location: index.php");
    exit();
}

require '../../config/db.php';
$error = ""; $success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cedula = trim(strtoupper($_POST['cedula']));
    $nombre = trim(ucwords(strtolower($_POST['nombre'])));
    $estado = $_POST['estado'];

    if (empty($cedula) || empty($nombre) || empty($estado)) {
        $error = "Todos los campos son obligatorios.";
    } else {
        try {
            // Verificamos que la cédula no esté registrada ya
            $check = $conn->prepare("SELECT id FROM profesores WHERE cedula = ?");
            $check->execute([$cedula]);
            if($check->rowCount() > 0){
                $error = "Esta cédula ya está registrada en el sistema.";
            } else {
                $stmt = $conn->prepare("INSERT INTO profesores (cedula, nombre, estado) VALUES (?, ?, ?)");
                $stmt->execute([$cedula, $nombre, $estado]);
                $success = "Docente registrado correctamente.";
            }
        } catch(PDOException $e) { 
            $error = "Error al guardar: " . $e->getMessage(); 
        }
    }
}

$ruta = '../../'; include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
    <h2 class="fw-bold mb-0 text-dark"><i class="bi bi-person-plus text-danger me-2"></i> Registrar Profesor</h2>
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
                        <label class="form-label fw-bold">Cédula de Identidad (Ej: V-12345678)</label>
                        <input type="text" name="cedula" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre Completo (Ej: Dr. Alexis Mujica)</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold">Estado en la Institución</label>
                        <select name="estado" class="form-select" required>
                            <option value="ACTIVO">ACTIVO</option>
                            <option value="INACTIVO">INACTIVO (Reposo/Jubilado)</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-danger w-100 fw-bold" style="background-color: #8B1A1A;">
                        <i class="bi bi-save"></i> Guardar Docente
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>