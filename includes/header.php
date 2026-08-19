<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($ruta)) { $ruta = ''; }
$rol_usuario = isset($_SESSION['rol']) ? strtolower($_SESSION['rol']) : '';

// Lógica para calcular la semana actual (Máximo 12 Semanas)
$semana_texto = "Sin configurar";
if (isset($conn)) {
    try {
        $stmt_conf = $conn->query("SELECT valor FROM configuracion WHERE parametro = 'fecha_inicio_lapso'");
        if ($stmt_conf) {
            $fecha_inicio_db = $stmt_conf->fetchColumn();
            if ($fecha_inicio_db) {
                // Comparamos solo las fechas (sin horas) para mayor exactitud
                $inicio_lapso = new DateTime($fecha_inicio_db);
                $inicio_lapso->setTime(0, 0, 0);
                $hoy = new DateTime(); 
                $hoy->setTime(0, 0, 0);
                
                if ($hoy >= $inicio_lapso) {
                    $diff = $inicio_lapso->diff($hoy);
                    $semana_num = floor($diff->days / 7) + 1;
                    
                    // LÍMITE: Si pasa de 12 semanas, se declara finalizado
                    if ($semana_num > 12) {
                        $semana_texto = "Trimestre Finalizado";
                    } else {
                        $semana_texto = "Semana " . $semana_num;
                    }
                } else {
                    // Si la fecha configurada es del futuro
                    $semana_texto = "Por iniciar";
                }
            }
        }
    } catch(PDOException $e) {
        // Ignorar si hay fallo de BD para que no se rompa la cabecera
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGEHO-CONT | UPTAG</title>
    <!-- Favicon -->
    <link rel="icon" href="<?php echo $ruta; ?>assets/img/logo_conta.jpeg" type="image/jpeg">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <!-- NUESTRO CSS CENTRALIZADO -->
    <link rel="stylesheet" href="<?php echo $ruta; ?>assets/css/estilos.css">
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <div id="sidebar" class="d-flex flex-column area-no-imprimir">
        <div class="p-3 d-flex align-items-center border-bottom border-danger">
            <img src="<?php echo $ruta; ?>assets/img/logo_conta.jpeg" alt="Logo" width="40" class="rounded me-2 bg-white p-1">
            <h5 class="mb-0 fw-bold">Contaduría UPTAG</h5>
        </div>
        
        <div class="p-3 flex-grow-1">
            <ul class="nav flex-column gap-1">
                <li class="nav-item">
                    <a href="<?php echo $ruta; ?>dashboard.php" class="nav-link"><i class="bi bi-grid-1x2-fill me-2"></i> Panel Principal</a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo $ruta; ?>modules/horarios/index.php" class="nav-link fw-bold" style="color: #ffd700;"><i class="bi bi-calendar-week-fill me-2"></i> Horarios</a>
                </li>
                <li class="nav-item mt-3 mb-1"><small class="text-white-50 fw-bold px-3">GESTIÓN ACADÉMICA</small></li>
                <li class="nav-item"><a href="<?php echo $ruta; ?>modules/profesores/index.php" class="nav-link"><i class="bi bi-person-badge-fill me-2"></i> Profesores</a></li>
                <li class="nav-item"><a href="<?php echo $ruta; ?>modules/materias/index.php" class="nav-link"><i class="bi bi-journal-bookmark-fill me-2"></i> Malla Curricular</a></li>
                <li class="nav-item"><a href="<?php echo $ruta; ?>modules/aulas/index.php" class="nav-link"><i class="bi bi-door-open-fill me-2"></i> Aulas y Espacios</a></li>
                <li class="nav-item"><a href="<?php echo $ruta; ?>modules/secciones/index.php" class="nav-link"><i class="bi bi-people-fill me-2"></i> Secciones</a></li>
                
                <?php if($rol_usuario == 'administrador'): ?>
                <li class="nav-item mt-3 mb-1"><small class="text-white-50 fw-bold px-3">SISTEMA</small></li>
                <li class="nav-item"><a href="<?php echo $ruta; ?>modules/usuarios/index.php" class="nav-link"><i class="bi bi-shield-lock-fill text-info me-2"></i> Gestión de Usuarios</a></li>
                <li class="nav-item"><a href="<?php echo $ruta; ?>configuracion.php" class="nav-link"><i class="bi bi-gear-fill me-2"></i> Configuración</a></li>
                <li class="nav-item"><a href="<?php echo $ruta; ?>backup.php" class="nav-link"><i class="bi bi-database-fill-down text-success me-2"></i> Respaldo (Backup)</a></li>
                <?php endif; ?>
            </ul>
        </div>
        
        <div class="p-3 border-top border-danger">
            <a href="<?php echo $ruta; ?>logout.php" class="nav-link text-warning fw-bold"><i class="bi bi-box-arrow-left me-2"></i> Cerrar Sesión</a>
        </div>
    </div>

    <!-- Main Content -->
    <div id="main-content" class="d-flex flex-column bg-light vh-100 overflow-hidden">
        
        <!-- Navbar Superior -->
        <nav class="top-navbar d-flex justify-content-between align-items-center area-no-imprimir">
            <div class="d-flex align-items-center">
                <button class="btn btn-light d-md-none me-3" id="menu-toggle"><i class="bi bi-list fs-4"></i></button>
                <h4 class="mb-0 fw-bold text-dark">SIGEHO-CONT</h4>
            </div>
            
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-light text-dark border p-2 shadow-sm d-none d-md-inline">
                    ( Lapso Académico Actual: <span class="text-danger"><?php echo $semana_texto; ?></span> )
                </span>
                <div class="dropdown">
                    <button class="btn btn-white dropdown-toggle fw-bold text-secondary" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle text-danger fs-5 me-1"></i> <?php echo isset($_SESSION['nombre']) ? htmlspecialchars($_SESSION['nombre']) : 'Usuario'; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                        <li><a class="dropdown-item" href="<?php echo $ruta; ?>configuracion.php"><i class="bi bi-person-gear me-2"></i>Configuración</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger fw-bold" href="<?php echo $ruta; ?>logout.php"><i class="bi bi-box-arrow-left me-2"></i>Cerrar Sesión</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Contenido Dinámico de la Página -->
        <div class="p-4 flex-grow-1 overflow-auto">