<?php
// modules/materias/nuevo.php
session_start();
// EL CANDADO
if (!isset($_SESSION['usuario_id']) || strtolower($_SESSION['rol']) != 'administrador') {
    header("Location: index.php");
    exit();
}

require '../../config/db.php';
$error = ""; $success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo = trim(strtoupper($_POST['codigo']));
    $nombre = trim(ucwords(strtolower($_POST['nombre']))); 
    $trayecto = $_POST['trayecto']; 
    $horas_semanales = (int)$_POST['horas_semanales'];

    // Se cambia la validación para que acepte el "0" (Trayecto Inicial)
    if (empty($codigo) || empty($nombre) || $trayecto === '' || empty($horas_semanales)) {
        $error = "Todos los campos son obligatorios.";
    } else {
        $trayecto = (int)$trayecto;
        try {
            $check = $conn->prepare("SELECT id FROM materias WHERE codigo = ?");
            $check->execute([$codigo]);
            if($check->rowCount() > 0){
                $error = "Ya existe una materia registrada con ese código.";
            } else {
                $stmt = $conn->prepare("INSERT INTO materias (codigo, nombre, trayecto, horas_semanales) VALUES (?, ?, ?, ?)");
                $stmt->execute([$codigo, $nombre, $trayecto, $horas_semanales]);
                $success = "Unidad Curricular registrada correctamente.";
            }
        } catch(PDOException $e) { 
            $error = "Error al guardar: " . $e->getMessage(); 
        }
    }
}

$ruta = '../../'; include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
    <h2 class="fw-bold mb-0 text-dark"><i class="bi bi-journal-plus text-danger me-2"></i> Registrar Materia</h2>
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
                        <label class="form-label fw-bold">Código Oficial (Ej: CON-221)</label>
                        <input type="text" name="codigo" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre de la Unidad Curricular</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-6">
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
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Horas Semanales</label>
                            <input type="number" name="horas_semanales" class="form-control" min="1" max="10" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-danger w-100 fw-bold" style="background-color: #8B1A1A;">
                        <i class="bi bi-save"></i> Guardar Materia
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>