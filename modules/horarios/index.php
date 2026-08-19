<?php
// modules/horarios/index.php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../index.php");
    exit();
}

require '../../config/db.php';
$ruta = '../../'; 
include '../../includes/header.php';

$sql = "SELECT h.*, 
               s.codigo AS seccion_cod, s.trayecto, s.trimestre,
               m.nombre AS materia_nombre, 
               p.nombre AS profesor_nombre, 
               a.codigo AS aula_cod 
        FROM horarios h
        INNER JOIN secciones s ON h.seccion_id = s.id
        INNER JOIN materias m ON h.materia_id = m.id
        INNER JOIN profesores p ON h.profesor_id = p.id
        INNER JOIN aulas a ON h.aula_id = a.id
        ORDER BY h.dia_semana, h.hora_inicio ASC";

$stmt = $conn->query($sql);
$horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

function ordenarDias($a, $b) {
    $dias = ['Lunes'=>1, 'Martes'=>2, 'Miercoles'=>3, 'Jueves'=>4, 'Viernes'=>5, 'Sabado'=>6];
    if ($dias[$a['dia_semana']] == $dias[$b['dia_semana']]) {
        return strtotime($a['hora_inicio']) - strtotime($b['hora_inicio']);
    }
    return $dias[$a['dia_semana']] - $dias[$b['dia_semana']];
}
usort($horarios, 'ordenarDias');

// VARIABLE DE CONTROL DE ROL
$es_admin = (strtolower($_SESSION['rol']) == 'administrador');
?>

<div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
    <h2 class="fw-bold mb-0"><i class="bi bi-calendar-week-fill text-danger me-2"></i> Gestión de Horarios</h2>
    
    <?php if($es_admin): ?>
        <a href="nuevo.php" class="btn btn-danger" style="background-color: #8B1A1A;">
            <i class="bi bi-plus-lg"></i> Asignar Nuevo Bloque
        </a>
    <?php endif; ?>
</div>

<?php if(isset($_GET['msg']) && $_GET['msg'] == 'eliminado'): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4">
        <i class="bi bi-check-circle-fill me-2"></i> Bloque de horario eliminado.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row mb-3 area-no-imprimir">
    <div class="col-md-5 ms-auto">
        <div class="input-group shadow-sm">
            <span class="input-group-text bg-white border-end-0 text-danger">
                <i class="bi bi-search"></i>
            </span>
            <input type="text" id="buscadorGeneral" class="form-control border-start-0" placeholder="Buscar por día, sección, materia, profesor o aula...">
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <table class="table table-hover table-striped mb-0 align-middle">
            <thead class="table-dark" style="background-color: #343a40;">
                <tr>
                    <th>Día y Hora</th>
                    <th>Sección</th>
                    <th>Materia</th>
                    <th>Profesor</th>
                    <th>Aula</th>
                    <?php if($es_admin): ?>
                        <th class="text-center">Acciones</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if(count($horarios) > 0): ?>
                    <?php foreach($horarios as $h): ?>
                    <tr>
                        <td>
                            <strong class="text-success"><?php echo $h['dia_semana']; ?></strong><br>
                            <small class="text-muted fw-bold">
                                <?php echo date("h:i A", strtotime($h['hora_inicio'])) . " - " . date("h:i A", strtotime($h['hora_fin'])); ?>
                            </small>
                        </td>
                        <td class="fw-bold">
                            <?php 
                            $texto_tray = ($h['trayecto'] == 0) ? 'Inicial' : 'T'.$h['trayecto'];
                            echo htmlspecialchars($h['seccion_cod']) . " <br><small class='text-muted'>($texto_tray)</small>"; 
                            ?>
                        </td>
                        <td class="text-muted"><?php echo htmlspecialchars($h['materia_nombre']); ?></td>
                        <td><?php echo htmlspecialchars($h['profesor_nombre']); ?></td>
                        <td class="fw-bold text-danger"><?php echo htmlspecialchars($h['aula_cod']); ?></td>
                        
                        <?php if($es_admin): ?>
                        <td class="text-center">
                            <div class="btn-group">
                                <a href="editar.php?id=<?php echo $h['id']; ?>" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                                <a href="eliminar.php?id=<?php echo $h['id']; ?>" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="return confirm('¿Borrar este bloque?');"><i class="bi bi-trash"></i></a>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="<?php echo $es_admin ? '6' : '5'; ?>" class="text-center py-5 text-muted">No hay horarios registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>