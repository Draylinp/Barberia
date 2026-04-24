<?php
/**
 * Gestión de Clientes
 * Barbería Premium — admin/clientes.php
 */
$titulo     = 'Clientes';
$breadcrumb = 'Clientes';
require_once __DIR__ . '/../includes/header_admin.php';

$db     = getDB();
$accion = sanitizeStr($_GET['accion'] ?? '');
$id     = sanitizeInt($_GET['id']     ?? 0);

if ($accion === 'toggle' && $id) {
    verifyCsrf();
    $db->prepare('UPDATE usuarios SET activo = NOT activo WHERE id = (SELECT usuario_id FROM clientes WHERE id = :id)')
       ->execute([':id'=>$id]);
    setFlash('success', 'Estado del cliente actualizado.');
    header('Location: /Barberia/admin/clientes.php'); exit;
}

$buscar = sanitizeStr($_GET['q'] ?? '');
$sql = 'SELECT cl.id, cl.created_at, u.nombre, u.apellido, u.email, u.telefono, u.activo,
               (SELECT COUNT(*) FROM citas c WHERE c.cliente_id = cl.id) AS total_citas,
               (SELECT COUNT(*) FROM citas c WHERE c.cliente_id = cl.id AND c.estado = "completada") AS completadas
        FROM clientes cl JOIN usuarios u ON cl.usuario_id = u.id';
$params = [];
if ($buscar) {
    $sql .= ' WHERE u.nombre LIKE :q OR u.apellido LIKE :q OR u.email LIKE :q';
    $params[':q'] = '%' . $buscar . '%';
}
$sql .= ' ORDER BY cl.created_at DESC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$clientes = $stmt->fetchAll();
?>

<div class="admin-card mb-4">
    <form method="GET" class="d-flex gap-2">
        <div class="search-box flex-fill" style="max-width:none;">
            <i class="bi bi-search"></i>
            <input type="text" name="q" class="form-control form-control-sm"
                   placeholder="Buscar por nombre, apellido o correo…"
                   value="<?= e($buscar) ?>">
        </div>
        <button type="submit" class="btn btn-gold btn-sm">Buscar</button>
        <?php if ($buscar): ?>
        <a href="/Barberia/admin/clientes.php" class="btn btn-outline-gold btn-sm">Limpiar</a>
        <?php endif; ?>
    </form>
</div>

<div class="admin-table-wrap">
    <div class="admin-table-header">
        <h6><i class="bi bi-people me-2 text-gold"></i>Clientes (<?= count($clientes) ?>)</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Contacto</th>
                    <th>Citas totales</th>
                    <th>Completadas</th>
                    <th>Registrado</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($clientes)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No se encontraron clientes.</td></tr>
                <?php endif; ?>
                <?php foreach ($clientes as $cl): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-circle-sm"><?= strtoupper(substr($cl['nombre'],0,1)) ?></div>
                            <div class="fw-semibold"><?= e($cl['nombre'].' '.$cl['apellido']) ?></div>
                        </div>
                    </td>
                    <td>
                        <div class="small"><?= e($cl['email']) ?></div>
                        <div class="text-muted" style="font-size:.75rem;"><?= e($cl['telefono'] ?? '—') ?></div>
                    </td>
                    <td><span class="badge bg-secondary"><?= $cl['total_citas'] ?></span></td>
                    <td><span class="badge bg-success"><?= $cl['completadas'] ?></span></td>
                    <td class="text-muted small"><?= formatFecha($cl['created_at']) ?></td>
                    <td>
                        <?= $cl['activo']
                            ? '<span class="badge bg-success">Activo</span>'
                            : '<span class="badge bg-danger">Suspendido</span>' ?>
                    </td>
                    <td>
                        <a href="?accion=toggle&id=<?= $cl['id'] ?>&csrf_token=<?= csrfToken() ?>"
                           class="btn btn-sm <?= $cl['activo'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                           data-confirm="<?= $cl['activo'] ? '¿Suspender este cliente?' : '¿Activar este cliente?' ?>">
                            <i class="bi bi-<?= $cl['activo'] ? 'slash-circle' : 'check-circle' ?>"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer_admin.php'; ?>
