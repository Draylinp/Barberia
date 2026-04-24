<?php
/**
 * CRUD de Servicios
 * Barbería Premium — admin/servicios.php
 */
$titulo     = 'Servicios';
$breadcrumb = 'Servicios';
require_once __DIR__ . '/../includes/header_admin.php';

$db     = getDB();
$accion = sanitizeStr($_GET['accion'] ?? '');
$id     = sanitizeInt($_GET['id']     ?? 0);

// ── ELIMINAR ──────────────────────────────────────────────────────────────────
if ($accion === 'eliminar' && $id) {
    verifyCsrf();
    $db->prepare('UPDATE servicios SET activo = 0 WHERE id = :id')->execute([':id'=>$id]);
    setFlash('success', 'Servicio desactivado.');
    header('Location: /Barberia/admin/servicios.php'); exit;
}

// ── ACTIVAR ───────────────────────────────────────────────────────────────────
if ($accion === 'activar' && $id) {
    verifyCsrf();
    $db->prepare('UPDATE servicios SET activo = 1 WHERE id = :id')->execute([':id'=>$id]);
    setFlash('success', 'Servicio activado.');
    header('Location: /Barberia/admin/servicios.php'); exit;
}

// ── GUARDAR ───────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $editId      = sanitizeInt($_POST['edit_id']     ?? 0);
    $nombre      = sanitizeStr($_POST['nombre']       ?? '');
    $descripcion = sanitizeStr($_POST['descripcion']  ?? '');
    $precio      = (float)($_POST['precio']           ?? 0);
    $duracion    = sanitizeInt($_POST['duracion_min'] ?? 30);

    if (!$nombre || $precio <= 0 || $duracion <= 0) {
        setFlash('error', 'Nombre, precio y duración son obligatorios.');
    } else {
        if ($editId) {
            $db->prepare('UPDATE servicios SET nombre=:n,descripcion=:d,precio=:p,duracion_min=:du WHERE id=:id')
               ->execute([':n'=>$nombre,':d'=>$descripcion,':p'=>$precio,':du'=>$duracion,':id'=>$editId]);
            setFlash('success', 'Servicio actualizado.');
        } else {
            $db->prepare('INSERT INTO servicios (nombre,descripcion,precio,duracion_min) VALUES (:n,:d,:p,:du)')
               ->execute([':n'=>$nombre,':d'=>$descripcion,':p'=>$precio,':du'=>$duracion]);
            setFlash('success', 'Servicio creado.');
        }
        header('Location: /Barberia/admin/servicios.php'); exit;
    }
}

$servicios = $db->query('SELECT * FROM servicios ORDER BY activo DESC, nombre ASC')->fetchAll();
?>

<div class="d-flex justify-content-end mb-4">
    <button class="btn btn-gold btn-sm" data-bs-toggle="modal" data-bs-target="#modalServicio"
            onclick="resetServicio()">
        <i class="bi bi-plus-lg me-2"></i>Nuevo Servicio
    </button>
</div>

<div class="admin-table-wrap">
    <div class="admin-table-header">
        <h6><i class="bi bi-scissors me-2 text-gold"></i>Servicios (<?= count($servicios) ?>)</h6>
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="text" class="form-control form-control-sm" placeholder="Buscar…"
                   data-search-table="tablaServicios">
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="tablaServicios">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Precio</th>
                    <th>Duración</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($servicios as $s): ?>
                <tr>
                    <td class="fw-semibold"><?= e($s['nombre']) ?></td>
                    <td class="text-muted" style="max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        <?= e($s['descripcion']) ?>
                    </td>
                    <td class="text-gold fw-semibold"><?= formatMoney($s['precio']) ?></td>
                    <td><i class="bi bi-clock me-1 text-muted"></i><?= (int)$s['duracion_min'] ?> min</td>
                    <td>
                        <?= $s['activo']
                            ? '<span class="badge bg-success">Activo</span>'
                            : '<span class="badge bg-secondary">Inactivo</span>' ?>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-gold me-1"
                                onclick="editarServicio(<?= $s['id'] ?>, <?= json_encode($s) ?>)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <?php if ($s['activo']): ?>
                        <a href="?accion=eliminar&id=<?= $s['id'] ?>&csrf_token=<?= csrfToken() ?>"
                           class="btn btn-sm btn-outline-danger"
                           data-confirm="¿Desactivar este servicio?">
                            <i class="bi bi-slash-circle"></i>
                        </a>
                        <?php else: ?>
                        <a href="?accion=activar&id=<?= $s['id'] ?>&csrf_token=<?= csrfToken() ?>"
                           class="btn btn-sm btn-outline-success"
                           data-confirm="¿Activar este servicio?">
                            <i class="bi bi-check-circle"></i>
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalServicio" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalServicioTitulo">Nuevo Servicio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <?= csrfField() ?>
                <input type="hidden" name="edit_id" id="s_edit_id" value="0">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nombre <span class="text-gold">*</span></label>
                            <input type="text" name="nombre" id="s_nombre" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" id="s_descripcion" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Precio (USD) <span class="text-gold">*</span></label>
                            <input type="number" name="precio" id="s_precio" class="form-control"
                                   min="0.01" step="0.50" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Duración (min) <span class="text-gold">*</span></label>
                            <input type="number" name="duracion_min" id="s_duracion" class="form-control"
                                   min="5" step="5" value="30" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-gold" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-gold"><i class="bi bi-save me-2"></i>Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer_admin.php'; ?>
<script>
function resetServicio() {
    document.getElementById('modalServicioTitulo').textContent = 'Nuevo Servicio';
    document.getElementById('s_edit_id').value = 0;
    ['nombre','descripcion','precio'].forEach(f => document.getElementById('s_' + f).value = '');
    document.getElementById('s_duracion').value = 30;
}
function editarServicio(id, d) {
    document.getElementById('modalServicioTitulo').textContent = 'Editar Servicio';
    document.getElementById('s_edit_id').value     = id;
    document.getElementById('s_nombre').value      = d.nombre      || '';
    document.getElementById('s_descripcion').value = d.descripcion || '';
    document.getElementById('s_precio').value      = d.precio      || '';
    document.getElementById('s_duracion').value    = d.duracion_min|| 30;
    new bootstrap.Modal(document.getElementById('modalServicio')).show();
}
<?php if (isset($_GET['nuevo'])): ?>
window.addEventListener('load', () => { resetServicio(); new bootstrap.Modal(document.getElementById('modalServicio')).show(); });
<?php endif; ?>
</script>
