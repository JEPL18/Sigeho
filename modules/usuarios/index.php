<?php
// modules/usuarios/index.php
session_start();
// Barrera de seguridad: Solo Administradores
if (!isset($_SESSION['usuario_id']) || strtolower($_SESSION['rol']) != 'administrador') {
    header("Location: ../../dashboard.php");
    exit();
}

require '../../config/db.php';
$ruta = '../../'; 
include '../../includes/header.php';

$sql = "SELECT id, nombre, correo, rol FROM usuarios ORDER BY rol ASC, nombre ASC";
$stmt = $conn->query($sql);
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
    <h2 class="fw-bold mb-0"><i class="bi bi-person-badge-fill text-info me-2"></i> Cuentas de Acceso</h2>
    <a href="nuevo.php" class="btn btn-info text-white fw-bold">
        <i class="bi bi-person-plus-fill"></i> Registrar Usuario
    </a>
</div>

<?php if(isset($_GET['msg'])): ?>
    <?php if($_GET['msg'] == 'eliminado'): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4">
            <i class="bi bi-check-circle-fill me-2"></i> Usuario revocado y eliminado del sistema.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif($_GET['msg'] == 'error_uso'): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <strong>Error:</strong> No se pudo eliminar el usuario por dependencias en el sistema.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif($_GET['msg'] == 'error_propio'): ?>
        <div class="alert alert-warning alert-dismissible fade show shadow-sm mb-4 text-dark">
            <i class="bi bi-shield-exclamation me-2"></i> <strong>Acción denegada:</strong> No puedes eliminar tu propia cuenta activa.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="row mb-3 area-no-imprimir">
    <div class="col-md-5 ms-auto">
        <div class="input-group shadow-sm">
            <span class="input-group-text bg-white border-end-0 text-info">
                <i class="bi bi-search"></i>
            </span>
            <input type="text" id="buscadorGeneral" class="form-control border-start-0" placeholder="Buscar por nombre, correo o rol...">
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <table class="table table-hover table-striped mb-0 align-middle text-center">
            <thead class="table-dark" style="background-color: #343a40;">
                <tr>
                    <th>Nombre del Personal</th>
                    <th>Correo (Acceso)</th>
                    <th>Nivel de Privilegios</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($usuarios as $u): ?>
                <tr>
                    <td class="fw-bold text-start ps-4"><?php echo htmlspecialchars($u['nombre']); ?></td>
                    <td class="text-muted"><?php echo htmlspecialchars($u['correo']); ?></td>
                    <td>
                        <?php if(strtolower($u['rol']) == 'administrador'): ?>
                            <span class="badge bg-danger"><i class="bi bi-shield-lock-fill"></i> Administrador</span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><i class="bi bi-eye-fill"></i> Asistente</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="editar.php?id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-primary" title="Editar / Cambiar Clave"><i class="bi bi-pencil"></i></a>
                            <?php if($u['id'] != $_SESSION['usuario_id']): // Evita que el admin se borre a sí mismo ?>
                                <a href="eliminar.php?id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Revocar el acceso a este usuario?');" title="Eliminar"><i class="bi bi-trash"></i></a>
                            <?php else: ?>
                                <button class="btn btn-sm btn-outline-secondary" disabled title="No puedes eliminar tu propia cuenta"><i class="bi bi-trash"></i></button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>