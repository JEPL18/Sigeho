<?php
// modules/horarios/nuevo.php
session_start();
// EL CANDADO
if (!isset($_SESSION['usuario_id']) || strtolower($_SESSION['rol']) != 'administrador') {
    header("Location: index.php");
    exit();
}

require '../../config/db.php';
$error = ""; $success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $seccion_id = (int)$_POST['seccion_id'];
    $materia_id = (int)$_POST['materia_id'];
    $profesor_id = (int)$_POST['profesor_id'];
    $aula_id = (int)$_POST['aula_id'];
    $dia_semana = $_POST['dia_semana'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = $_POST['hora_fin'];

    if (empty($seccion_id) || empty($materia_id) || empty($profesor_id) || empty($aula_id) || empty($dia_semana) || empty($hora_inicio) || empty($hora_fin)) {
        $error = "Todos los campos son obligatorios.";
    } elseif ($hora_inicio >= $hora_fin) {
        $error = "La hora de inicio debe ser anterior a la hora de fin.";
    } else {
        try {
            // 1. RECOPILAR DATOS PARA VALIDACIONES
            $stmt_check = $conn->prepare("SELECT 
                (SELECT estado FROM profesores WHERE id = ?) as prof_estado,
                (SELECT estado FROM aulas WHERE id = ?) as aula_estado,
                (SELECT capacidad FROM aulas WHERE id = ?) as aula_cap,
                (SELECT cantidad_alumnos FROM secciones WHERE id = ?) as sec_alumn
            ");
            $stmt_check->execute([$profesor_id, $aula_id, $aula_id, $seccion_id]);
            $datos = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if ($datos['prof_estado'] != 'ACTIVO') {
                $error = "El docente seleccionado no está ACTIVO.";
            } elseif ($datos['aula_estado'] != 'OPERATIVA') {
                $error = "El aula seleccionada no está OPERATIVA.";
            } elseif ($datos['sec_alumn'] > $datos['aula_cap']) {
                $error = "Capacidad excedida: El aula (".$datos['aula_cap']." ptos) no soporta la matrícula (".$datos['sec_alumn']." alumnos).";
            } else {
                // 2. ALGORITMO ANTI-CHOQUES
                $sql_choque = "SELECT id FROM horarios 
                               WHERE dia_semana = ? 
                               AND (hora_inicio < ? AND hora_fin > ?)
                               AND (profesor_id = ? OR aula_id = ? OR seccion_id = ?)";
                $stmt_choque = $conn->prepare($sql_choque);
                $stmt_choque->execute([$dia_semana, $hora_fin, $hora_inicio, $profesor_id, $aula_id, $seccion_id]);
                
                if ($stmt_choque->rowCount() > 0) {
                    $error = "<strong>¡Choque Detectado!</strong> Hay un conflicto de horario con esta configuración.";
                    
                    // --- NUEVO: REGISTRAR EL LOG EN LA BASE DE DATOS ---
                    $intento = "Día: $dia_semana | Hora: $hora_inicio a $hora_fin";
                    $detalle = "Choque de Profesor, Aula o Sección ya ocupada.";
                    $sql_log = "INSERT INTO log_choques (usuario_id, intento_asignacion, detalle_conflicto) VALUES (?, ?, ?)";
                    $stmt_log = $conn->prepare($sql_log);
                    $stmt_log->execute([$_SESSION['usuario_id'], $intento, $detalle]);
                    // ---------------------------------------------------

                } else {
                    // 3. GUARDAR
                    $sql = "INSERT INTO horarios (seccion_id, materia_id, profesor_id, aula_id, dia_semana, hora_inicio, hora_fin) VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$seccion_id, $materia_id, $profesor_id, $aula_id, $dia_semana, $hora_inicio, $hora_fin]);
                    $success = "Bloque de clase asignado correctamente.";
                }
            }
        } catch(PDOException $e) { $error = "Error DB: " . $e->getMessage(); }
    }
}

// Cargar catálogos
$secciones = $conn->query("SELECT id, codigo, trayecto, trimestre, cantidad_alumnos FROM secciones")->fetchAll(PDO::FETCH_ASSOC);
$materias = $conn->query("SELECT id, nombre FROM materias")->fetchAll(PDO::FETCH_ASSOC);
$profesores = $conn->query("SELECT id, nombre FROM profesores WHERE estado = 'ACTIVO'")->fetchAll(PDO::FETCH_ASSOC);
$aulas = $conn->query("SELECT id, codigo, capacidad FROM aulas WHERE estado = 'OPERATIVA'")->fetchAll(PDO::FETCH_ASSOC);

$ruta = '../../'; include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
    <h2 class="fw-bold mb-0 text-dark"><i class="bi bi-calendar-plus text-danger me-2"></i> Asignar Bloque de Clase</h2>
    <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-4">
        <?php if($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
        <?php if($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

        <form action="nuevo.php" method="POST">
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Sección</label>
                    <select name="seccion_id" class="form-select" required>
                        <option value="" disabled selected>Seleccione la sección...</option>
                        <?php foreach($secciones as $s): 
                            $texto_tray = ($s['trayecto'] == 0) ? 'Inicial' : 'T'.$s['trayecto'];
                        ?>
                            <option value="<?php echo $s['id']; ?>">
                                <?php echo $texto_tray . " - " . $s['codigo'] . " (" . $s['cantidad_alumnos'] . " Alumnos)"; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Materia</label>
                    <select name="materia_id" class="form-select" required>
                        <option value="" disabled selected>Seleccione la materia...</option>
                        <?php foreach($materias as $m): ?>
                            <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Profesor (Solo Activos)</label>
                    <select name="profesor_id" class="form-select" required>
                        <option value="" disabled selected>Seleccione el docente...</option>
                        <?php foreach($profesores as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Aula (Solo Operativas)</label>
                    <select name="aula_id" class="form-select" required>
                        <option value="" disabled selected>Seleccione el aula...</option>
                        <?php foreach($aulas as $a): ?>
                            <option value="<?php echo $a['id']; ?>">
                                <?php echo htmlspecialchars($a['codigo']) . " (Cap: " . $a['capacidad'] . " ptos)"; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row mb-4 bg-light p-3 rounded">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Día</label>
                    <select name="dia_semana" class="form-select" required>
                        <option value="Lunes">Lunes</option>
                        <option value="Martes">Martes</option>
                        <option value="Miercoles">Miércoles</option>
                        <option value="Jueves">Jueves</option>
                        <option value="Viernes">Viernes</option>
                        <option value="Sabado">Sábado</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Hora Inicio</label>
                    <input type="time" name="hora_inicio" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Hora Fin</label>
                    <input type="time" name="hora_fin" class="form-control" required>
                </div>
            </div>
            <button type="submit" class="btn btn-danger w-100 fw-bold" style="background-color: #8B1A1A;"><i class="bi bi-calendar-check"></i> Asignar Bloque</button>
        </form>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>