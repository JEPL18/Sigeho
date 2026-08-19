<?php
// modules/secciones/nuevo.php
session_start();
if (!isset($_SESSION['usuario_id']) || strtolower($_SESSION['rol']) != 'administrador') {
    header("Location: index.php");
    exit();
}

require '../../config/db.php';
$error = ""; $success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo = trim(strtoupper($_POST['codigo']));
    $trayecto = $_POST['trayecto']; // Se toma tal cual del form
    $trimestre = (int)$_POST['trimestre'];
    $cantidad_alumnos = (int)$_POST['cantidad_alumnos'];

    // Se valida permitiendo explícitamente el valor 0
    if (empty($codigo) || $trayecto === '' || empty($trimestre) || empty($cantidad_alumnos)) {
        $error = "Todos los campos son obligatorios.";
    } else {
        $trayecto = (int)$trayecto;
        try {
            $check = $conn->prepare("SELECT id FROM secciones WHERE codigo = ?");
            $check->execute([$codigo]);
            if($check->rowCount() > 0){
                $error = "Ya existe una sección registrada con ese código.";
            } else {
                $stmt = $conn->prepare("INSERT INTO secciones (codigo, trayecto, trimestre, cantidad_alumnos) VALUES (?, ?, ?, ?)");
                $stmt->execute([$codigo, $trayecto, $trimestre, $cantidad_alumnos]);
                $success = "Sección registrada correctamente.";
            }
        } catch(PDOException $e) { 
            $error = "Error al guardar: " . $e->getMessage(); 
        }
    }
}

$ruta = '../../'; include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
    <h2 class="fw-bold mb-0 text-dark"><i class="bi bi-people text-danger me-2"></i> Registrar Sección</h2>
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
                        <label class="form-label fw-bold">Código de Sección (Ej: SECC-34)</label>
                        <input type="text" name="codigo" class="form-control" required>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Trayecto</label>
                            <select name="trayecto" class="form-select" required>
                                <option value="" disabled selected>Seleccione...</option>
                                <option value="0">Trayecto Inicial</option>
                                <option value="1">Trayecto 1</option>
                                <option value="2">Trayecto 2</option>
                                <option value="3">Trayecto 3</option>
                                <option value="4">Trayecto 4</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Trimestre</label>
                            <input type="number" name="trimestre" class="form-control" min="1" max="3" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Matrícula</label>
                            <input type="number" name="cantidad_alumnos" class="form-control" min="1" required placeholder="Alumnos">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-danger w-100 fw-bold" style="background-color: #8B1A1A;">
                        <i class="bi bi-save"></i> Guardar Sección
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>