<?php
// modules/aulas/index.php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../index.php");
    exit();
}
require '../../config/db.php';
$ruta = '../../'; 
include '../../includes/header.php';

$sql = "SELECT * FROM aulas ORDER BY codigo ASC";
$stmt = $conn->query($sql);
$aulas = $stmt->fetchAll(PDO::FETCH_ASSOC);
$es_admin = (isset($_SESSION['rol']) && strtolower($_SESSION['rol']) == 'administrador');
?>

<div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
    <h2 class="fw-bold mb-0"><i class="bi bi-door-open text-danger me-2"></i> Aulas y Espacios</h2>
    <?php if($es_admin): ?>
        <a href="nuevo.php" class="btn btn-danger" style="background-color: #8B1A1A;">
            <i class="bi bi-plus-lg"></i> Registrar Aula
        </a>
    <?php endif; ?>
</div>

<?php if(isset($_GET['msg']) && $_GET['msg'] == 'error_uso'): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <strong>Acción denegada:</strong> No se puede eliminar esta aula porque ya está asignada a un bloque de horario.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row mb-3 area-no-imprimir">
    <div class="col-md-5 ms-auto">
        <div class="input-group shadow-sm">
            <span class="input-group-text bg-white border-end-0 text-danger">
                <i class="bi bi-search"></i>
            </span>
            <input type="text" id="buscadorGeneral" class="form-control border-start-0" placeholder="Buscar por código, capacidad o estado...">
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <table class="table table-hover table-striped mb-0 align-middle text-center">
            <thead class="table-dark" style="background-color: #343a40;">
                <tr>
                    <th>Código</th>
                    <th>Capacidad</th>
                    <th>Estado</th>
                    <?php if($es_admin): ?><th>Acciones</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach($aulas as $a): ?>
                <tr>
                    <td class="fw-bold text-dark"><?php echo htmlspecialchars($a['codigo']); ?></td>
                    <td><i class="bi bi-people-fill me-1 text-muted"></i> <?php echo $a['capacidad']; ?> alumnos</td>
                    <td>
                        <?php if($a['estado'] == 'OPERATIVA'): ?>
                            <span class="badge bg-success">Operativa</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Inoperativa</span>
                        <?php endif; ?>
                    </td>
                    <?php if($es_admin): ?>
                    <td>
                        <div class="btn-group">
                            <a href="editar.php?id=<?php echo $a['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <a href="eliminar.php?id=<?php echo $a['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Borrar aula?');"><i class="bi bi-trash"></i></a>
                        </div>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>