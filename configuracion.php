<?php
// configuracion.php
session_start();
require 'config/db.php';

// Bloquear si no está logueado O si es un Asistente
if (!isset($_SESSION['usuario_id']) || strtolower($_SESSION['rol']) != 'administrador') {
    header("Location: dashboard.php");
    exit();
}

$error = "";
$success = "";

// PROCESAR GUARDADO DE FECHA
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'guardar_fecha') {
    $nueva_fecha = trim($_POST['fecha_inicio_lapso']);
    
    if (empty($nueva_fecha)) {
        $error = "Debe seleccionar una fecha válida.";
    } else {
        try {
            $sql = "UPDATE configuracion SET valor = ? WHERE parametro = 'fecha_inicio_lapso'";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$nueva_fecha]);
            $success = "La fecha de inicio de lapso ha sido actualizada correctamente.";
        } catch(PDOException $e) {
            $error = "Error al actualizar: " . $e->getMessage();
        }
    }
}

// OBTENER LA FECHA ACTUAL 
$stmt = $conn->query("SELECT valor FROM configuracion WHERE parametro = 'fecha_inicio_lapso'");
$fecha_actual = $stmt->fetchColumn();

// GENERAR UNA CONTRASEÑA ALEATORIA SEGURA PARA EL RESPALDO (10 caracteres)
$caracteres = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
$pass_backup = substr(str_shuffle($caracteres), 0, 10);

$ruta = ''; 
include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
    <h2 class="fw-bold mb-0 text-dark"><i class="bi bi-gear-fill text-danger me-2"></i> Configuración del Sistema</h2>
    <a href="dashboard.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver al Inicio</a>
</div>

<?php if($error): ?><div class="alert alert-danger shadow-sm"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?></div><?php endif; ?>
<?php if($success): ?><div class="alert alert-success shadow-sm"><i class="bi bi-check-circle-fill me-2"></i> <?php echo $success; ?></div><?php endif; ?>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-dark text-white fw-bold">
                <i class="bi bi-calendar-range me-2"></i> Control de Lapso Académico
            </div>
            <div class="card-body p-4">
                <p class="text-muted small">El sistema calcula automáticamente la semana de clases actual basándose en la fecha en la que iniciaron formalmente las actividades del trimestre.</p>
                
                <form action="configuracion.php" method="POST">
                    <input type="hidden" name="accion" value="guardar_fecha">
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark">Fecha de Inicio del Lapso Actual</label>
                        <input type="date" name="fecha_inicio_lapso" class="form-control form-control-lg border-secondary" value="<?php echo $fecha_actual; ?>" required>
                    </div>
                    <button type="submit" class="btn btn-danger w-100 fw-bold" style="background-color: #8B1A1A;">
                        <i class="bi bi-save me-1"></i> Actualizar Fecha
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header text-white fw-bold" style="background-color: #198754;">
                <i class="bi bi-shield-lock-fill me-2"></i> Seguridad y Respaldo (Backup)
            </div>
            <div class="card-body p-4 text-center d-flex flex-column justify-content-center">
                <i class="bi bi-server text-muted mb-3" style="font-size: 3rem;"></i>
                <h5 class="fw-bold">Respaldo Total Cifrado</h5>
                <p class="text-muted small mb-4">Descarga una copia completa de la base de datos y los archivos, protegida con encriptación AES-256 para garantizar la privacidad institucional.</p>
                
                <button type="button" class="btn btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#modalRespaldo">
                    <i class="bi bi-cloud-arrow-down-fill me-2"></i> Generar Respaldo Cifrado
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRespaldo" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <form action="backup.php" method="POST">
          <div class="modal-header bg-warning">
            <h5 class="modal-title fw-bold text-dark"><i class="bi bi-exclamation-triangle-fill me-2"></i> Aviso de Seguridad</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4 text-center">
            <p class="text-dark">Por protocolos de seguridad, el sistema ha generado una contraseña única aleatoria para cifrar este archivo ZIP.</p>
            
            <div class="alert alert-danger p-2 mb-4">
                <strong>¡NO LA COMPARTAS!</strong> Es exclusiva para la administración del sistema. Si la pierdes, será imposible recuperar los datos del ZIP.
            </div>

            <p class="text-muted small mb-1">Tu contraseña para este archivo es:</p>
            <h2 class="font-monospace text-primary fw-bold user-select-all bg-light border p-3 rounded" style="letter-spacing: 3px;">
                <?php echo $pass_backup; ?>
            </h2>
            
            <input type="hidden" name="password_respaldo" value="<?php echo $pass_backup; ?>">
          </div>
          <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-success fw-bold" onclick="setTimeout(function(){ $('#modalRespaldo').modal('hide'); }, 1000);"><i class="bi bi-download me-2"></i> Entendido, Descargar Archivo</button>
          </div>
      </form>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>