<?php
// dashboard.php
session_start();
require 'config/db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

// --- NUEVO: LÓGICA PARA VACIAR EL LOG DE CHOQUES ---
if (isset($_GET['action']) && $_GET['action'] == 'clear_logs' && strtolower($_SESSION['rol']) == 'administrador') {
    $conn->query("DELETE FROM log_choques");
    header("Location: dashboard.php");
    exit();
}
// ---------------------------------------------------

$ruta = ''; 
include 'includes/header.php';

// --- CONSULTAS PARA LOS INDICADORES (KPIs) ---
$tot_prof_activos = $conn->query("SELECT COUNT(*) FROM profesores WHERE estado = 'ACTIVO'")->fetchColumn();
$tot_prof_inactivos = $conn->query("SELECT COUNT(*) FROM profesores WHERE estado != 'ACTIVO'")->fetchColumn();

$tot_sec = $conn->query("SELECT COUNT(*) FROM secciones")->fetchColumn();

$tot_aulas_operativas = $conn->query("SELECT COUNT(*) FROM aulas WHERE estado = 'OPERATIVA'")->fetchColumn();
$tot_aulas_inoperativas = $conn->query("SELECT COUNT(*) FROM aulas WHERE estado != 'OPERATIVA'")->fetchColumn();

$tot_choques = $conn->query("SELECT COUNT(*) FROM log_choques")->fetchColumn();
$logs = $conn->query("SELECT * FROM log_choques ORDER BY fecha DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);


// --- LÓGICA PARA CARGAR EL HORARIO ---
$stmt_secciones = $conn->query("SELECT id, codigo, trayecto, trimestre FROM secciones ORDER BY trayecto ASC, trimestre ASC, codigo ASC");
$secciones = $stmt_secciones->fetchAll(PDO::FETCH_ASSOC);

$seccion_seleccionada = isset($_GET['seccion_id']) ? (int)$_GET['seccion_id'] : null;
$turno_seleccionado = isset($_GET['turno']) ? strtoupper($_GET['turno']) : '';
$horarios_seccion = [];
$datos_seccion = null;

if ($seccion_seleccionada) {
    $stmt_sec = $conn->prepare("SELECT * FROM secciones WHERE id = ?");
    $stmt_sec->execute([$seccion_seleccionada]);
    $datos_seccion = $stmt_sec->fetch(PDO::FETCH_ASSOC);

    // 🕒 Consulta base
    $sql_horario = "SELECT h.*, m.nombre AS materia, p.nombre AS profesor, a.codigo AS aula 
                    FROM horarios h
                    INNER JOIN materias m ON h.materia_id = m.id
                    INNER JOIN profesores p ON h.profesor_id = p.id
                    INNER JOIN aulas a ON h.aula_id = a.id
                    WHERE h.seccion_id = :sec_id ";

    // 🧠 FILTRO INTELIGENTE DE TIEMPO SEGÚN EL TURNO
    if ($turno_seleccionado == 'MATUTINO') {
        $sql_horario .= " AND h.hora_inicio < '12:00:00' ";
    } elseif ($turno_seleccionado == 'VESPERTINO') {
        $sql_horario .= " AND h.hora_inicio >= '12:00:00' AND h.hora_inicio < '18:00:00' ";
    } elseif ($turno_seleccionado == 'NOCTURNO') {
        $sql_horario .= " AND h.hora_inicio >= '18:00:00' ";
    }

    $sql_horario .= " ORDER BY h.hora_inicio ASC";

    $stmt_horario = $conn->prepare($sql_horario);
    $stmt_horario->execute(['sec_id' => $seccion_seleccionada]);
    
    while ($row = $stmt_horario->fetch(PDO::FETCH_ASSOC)) {
        $rango_hora = date("h:i A", strtotime($row['hora_inicio'])) . " a " . date("h:i A", strtotime($row['hora_fin']));
        $dia = $row['dia_semana'];
        $horarios_seccion[$rango_hora][$dia] = $row;
    }
}
?>

<div class="row g-3 mb-4 area-no-imprimir">
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100" style="border-left: 4px solid #0d6efd;">
            <div class="card-body">
                <h6 class="text-muted mb-2">Estado Docente</h6>
                <h3 class="fw-bold mb-0"><?php echo $tot_prof_activos; ?> <small class="text-success fs-6"><i class="bi bi-check-circle"></i> Activos</small></h3>
                <?php if($tot_prof_inactivos > 0): ?>
                    <small class="text-danger fw-bold"><i class="bi bi-exclamation-circle"></i> <?php echo $tot_prof_inactivos; ?> de Reposo/Inactivos</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100" style="border-left: 4px solid #198754;">
            <div class="card-body">
                <h6 class="text-muted mb-2">Secciones (Matrícula)</h6>
                <h3 class="fw-bold mb-0"><?php echo $tot_sec; ?> <small class="text-muted fs-6">Registradas</small></h3>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100" style="border-left: 4px solid #ffc107;">
            <div class="card-body">
                <h6 class="text-muted mb-2">Disponibilidad de Aulas</h6>
                <h3 class="fw-bold mb-0"><?php echo $tot_aulas_operativas; ?> <small class="text-success fs-6"><i class="bi bi-door-open-fill"></i> Operativas</small></h3>
                <?php if($tot_aulas_inoperativas > 0): ?>
                    <small class="text-warning text-dark fw-bold"><i class="bi bi-tools"></i> <?php echo $tot_aulas_inoperativas; ?> Mantenimiento/Clausuradas</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100 bg-danger text-white border-0">
            <div class="card-body d-flex flex-column justify-content-center">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-white-50 mb-0">Choques Detectados</h6>
                    <i class="bi bi-shield-fill-x fs-3 opacity-50"></i>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                    <h3 class="fw-bold mb-0"><?php echo $tot_choques; ?></h3>
                    <button class="btn btn-sm btn-light text-danger fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalLog">
                        <i class="bi bi-eye-fill"></i> Ver Log
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4 bg-light area-no-imprimir">
    <div class="card-body">
        <form action="dashboard.php" method="GET" class="row align-items-end">
            <div class="col-md-6">
                <label class="form-label fw-bold text-dark"><i class="bi bi-search me-1"></i> Consultar Horario de la Sección:</label>
                <select name="seccion_id" class="form-select border-secondary" required>
                    <option value="" disabled selected>Seleccione la sección...</option>
                    <?php foreach($secciones as $s): ?>
                        <option value="<?php echo $s['id']; ?>" <?php echo ($seccion_seleccionada == $s['id']) ? 'selected' : ''; ?>>
                            TRAYECTO <?php echo $s['trayecto']; ?> - TRIM. <?php echo $s['trimestre']; ?> | <?php echo $s['codigo']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold text-dark"><i class="bi bi-clock-fill me-1"></i> Turno:</label>
                <select name="turno" class="form-select border-secondary" required>
                    <option value="MATUTINO" <?php echo ($turno_seleccionado == 'MATUTINO') ? 'selected' : ''; ?>>Matutino</option>
                    <option value="VESPERTINO" <?php echo ($turno_seleccionado == 'VESPERTINO') ? 'selected' : ''; ?>>Vespertino</option>
                    <option value="NOCTURNO" <?php echo ($turno_seleccionado == 'NOCTURNO') ? 'selected' : ''; ?>>Nocturno</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-danger w-100" style="background-color: #8B1A1A;">
                    <i class="bi bi-calendar3 me-1"></i> Cargar Horario
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($seccion_seleccionada && $datos_seccion): ?>
    <div class="card shadow-sm border-0 mb-5">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center area-no-imprimir">
            <h6 class="mb-0 fw-bold text-dark">Vista Previa de Impresión</h6>
            <div class="btn-group">
                <button class="btn btn-outline-danger btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir / Guardar PDF</button>
                <button class="btn btn-success btn-sm" onclick="exportarExcel('tablaHorario', 'Horario_<?php echo $datos_seccion['codigo']; ?>')"><i class="bi bi-file-earmark-excel"></i> Exportar Excel</button>
            </div>
        </div>
        
        <div class="card-body overflow-auto p-4" id="area-impresion">
            <table class="tabla-word" id="tablaHorario">
                <tr>
                    <td colspan="6" class="header-rojo">
                        PROGRAMA NACIONAL DE FORMACIÓN EN CONTADURÍA PÚBLICA: TRAYECTO <?php echo $datos_seccion['trayecto']; ?> - TRIMESTRE <?php echo $datos_seccion['trimestre']; ?> - <?php echo $datos_seccion['codigo']; ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="6" class="header-oscuro">
                        TURNO: <?php echo htmlspecialchars($turno_seleccionado); ?>
                    </td>
                </tr>
                <tr>
                    <th>HORA</th>
                    <th>LUNES</th>
                    <th>MARTES</th>
                    <th>MIÉRCOLES</th>
                    <th>JUEVES</th>
                    <th>VIERNES</th>
                </tr>
                
                <?php if (empty($horarios_seccion)): ?>
                    <tr><td colspan="6" class="py-5 text-muted h6 text-center">No hay bloques asignados para esta sección en el turno seleccionado.</td></tr>
                <?php else: ?>
                    <?php 
                    $dias = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'];
                    foreach ($horarios_seccion as $rango_hora => $clases_por_dia): 
                    ?>
                        <tr>
                            <td class="columna-hora"><?php echo $rango_hora; ?></td>
                            <?php foreach ($dias as $d): ?>
                                <td>
                                    <?php if (isset($clases_por_dia[$d])): ?>
                                        <span class="celda-materia"><?php echo htmlspecialchars($clases_por_dia[$d]['materia']); ?></span>
                                        <span class="celda-profe">PROFE. <?php echo htmlspecialchars($clases_por_dia[$d]['profesor']); ?></span>
                                        <span class="celda-aula">AULA: <?php echo htmlspecialchars($clases_por_dia[$d]['aula']); ?></span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <script>
    function exportarExcel(tableID, filename = ''){
        var downloadLink;
        var dataType = 'application/vnd.ms-excel;charset=UTF-8';
        var tableSelect = document.getElementById(tableID);
        var html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><meta charset="UTF-8"></head><body>' + tableSelect.outerHTML + '</body></html>';
        var blob = new Blob(['\ufeff', html], { type: dataType });
        filename = filename ? filename + '.xls' : 'Horario.xls';
        downloadLink = document.createElement("a");
        document.body.appendChild(downloadLink);
        if(navigator.msSaveOrOpenBlob){
            navigator.msSaveOrOpenBlob(blob, filename);
        }else{
            downloadLink.href = URL.createObjectURL(blob);
            downloadLink.download = filename;
            downloadLink.click();
        }
    }
    </script>
<?php else: ?>
    <div class="alert alert-secondary text-center py-5 border-0 shadow-sm area-no-imprimir">
        <i class="bi bi-calendar3 fs-1 text-muted d-block mb-3"></i>
        <h5 class="text-muted">Seleccione una sección y un turno en el menú superior para visualizar su horario.</h5>
    </div>
<?php endif; ?>

<div class="modal fade" id="modalLog" tabindex="-1" aria-labelledby="modalLogLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title fw-bold" id="modalLogLabel"><i class="bi bi-shield-lock-fill"></i> Auditoría: Log de Choques Bloqueados</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <table class="table table-striped table-hover mb-0" style="font-size: 0.85rem;">
            <thead class="table-dark sticky-top">
                <tr>
                    <th>Fecha y Hora</th>
                    <th>Intento de Asignación</th>
                    <th>Detalle del Conflicto (Motivo del Bloqueo)</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($logs) > 0): ?>
                    <?php foreach($logs as $log): ?>
                        <tr>
                            <td class="text-nowrap fw-bold text-danger"><?php echo date("d/m/Y h:i A", strtotime($log['fecha'])); ?></td>
                            <td><?php echo htmlspecialchars($log['intento_asignacion']); ?></td>
                            <td><?php echo htmlspecialchars($log['detalle_conflicto']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="text-center py-4 text-muted">No se han registrado choques de horarios en el sistema.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
      </div>
      <div class="modal-footer bg-light d-flex justify-content-between">
        <?php if(strtolower($_SESSION['rol']) == 'administrador'): ?>
            <a href="dashboard.php?action=clear_logs" class="btn btn-outline-danger btn-sm fw-bold" onclick="return confirm('¿Estás seguro de borrar todo el historial de auditoría?');">
                <i class="bi bi-trash-fill"></i> Vaciar Historial
            </a>
        <?php else: ?>
            <div></div> <!-- Espacio vacío para asistentes -->
        <?php endif; ?>
        <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cerrar Registro</button>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>