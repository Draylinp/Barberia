<?php
/**
 * CRUD de Barberos
 * Barbería Premium — admin/barberos.php
 */
$titulo     = 'Barberos';
$breadcrumb = 'Barberos';
require_once __DIR__ . '/../includes/header_admin.php';

$db    = getDB();
$accion = sanitizeStr($_GET['accion'] ?? '');
$id     = sanitizeInt($_GET['id']     ?? 0);

// ── ELIMINAR ──────────────────────────────────────────────────────────────────
if ($accion === 'eliminar' && $id) {
    verifyCsrf();
    // Desactivar en lugar de borrar (mantiene historial de citas)
    $db->prepare('UPDATE barberos SET activo = 0 WHERE id = :id')->execute([':id' => $id]);
    $db->prepare('UPDATE usuarios SET activo = 0 WHERE id = (SELECT usuario_id FROM barberos WHERE id = :id)')->execute([':id' => $id]);
    setFlash('success', 'Barbero desactivado correctamente.');
    header('Location: /Barberia/admin/barberos.php');
    exit;
}

// ── GUARDAR (crear o editar) ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $editId      = sanitizeInt($_POST['edit_id']      ?? 0);
    $nombre      = sanitizeStr($_POST['nombre']        ?? '');
    $apellido    = sanitizeStr($_POST['apellido']      ?? '');
    $email       = sanitizeEmail($_POST['email']       ?? '');
    $telefono    = sanitizeStr($_POST['telefono']      ?? '');
    $especialidad= sanitizeStr($_POST['especialidad']  ?? '');
    $bio         = sanitizeStr($_POST['bio']           ?? '');
    $comision    = (float)($_POST['comision_pct']      ?? 40);
    $password    = $_POST['password']                  ?? '';

    $errMsg = '';
    if (!$nombre || !$apellido || !$email) $errMsg = 'Nombre, apellido y correo son obligatorios.';

    if (!$errMsg) {
        if ($editId) {
            // Actualizar usuario
            $db->prepare('UPDATE usuarios SET nombre=:n,apellido=:a,email=:e,telefono=:t WHERE id=(SELECT usuario_id FROM barberos WHERE id=:id)')
               ->execute([':n'=>$nombre,':a'=>$apellido,':e'=>$email,':t'=>$telefono,':id'=>$editId]);
            if ($password) {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost'=>12]);
                $db->prepare('UPDATE usuarios SET password_hash=:h WHERE id=(SELECT usuario_id FROM barberos WHERE id=:id)')
                   ->execute([':h'=>$hash,':id'=>$editId]);
            }
            $db->prepare('UPDATE barberos SET especialidad=:esp,bio=:bio,comision_pct=:com WHERE id=:id')
               ->execute([':esp'=>$especialidad,':bio'=>$bio,':com'=>$comision,':id'=>$editId]);
            setFlash('success', 'Barbero actualizado.');
        } else {
            // Verificar email único
            $dup = $db->prepare('SELECT id FROM usuarios WHERE email = :e');
            $dup->execute([':e'=>$email]);
            if ($dup->fetch()) {
                $errMsg = 'Ese correo ya está registrado.';
            } else {
                if (!$password) { $errMsg = 'La contraseña es obligatoria al crear un barbero.'; }
                else {
                    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost'=>12]);
                    $db->beginTransaction();
                    try {
                        $db->prepare('INSERT INTO usuarios (nombre,apellido,email,password_hash,rol,telefono) VALUES (:n,:a,:e,:h,"barbero",:t)')
                           ->execute([':n'=>$nombre,':a'=>$apellido,':e'=>$email,':h'=>$hash,':t'=>$telefono]);
                        $uid = $db->lastInsertId();
                        $db->prepare('INSERT INTO barberos (usuario_id,especialidad,bio,comision_pct) VALUES (:u,:esp,:bio,:com)')
                           ->execute([':u'=>$uid,':esp'=>$especialidad,':bio'=>$bio,':com'=>$comision]);
                        $db->commit();
                        setFlash('success', 'Barbero creado exitosamente.');
                    } catch(Exception $ex) {
                        $db->rollBack(); $errMsg = 'Error al crear barbero.';
                    }
                }
            }
        }
        if (!$errMsg) { header('Location: /Barberia/admin/barberos.php'); exit; }
    }
    if ($errMsg) setFlash('error', $errMsg);
}

// ── LISTAR ────────────────────────────────────────────────────────────────────
$barberos = $db->query(
    'SELECT b.*, u.nombre, u.apellido, u.email, u.telefono, u.activo AS u_activo,
            (SELECT COUNT(*) FROM citas c WHERE c.barbero_id = b.id AND c.estado != "cancelada") AS total_citas
     FROM barberos b JOIN usuarios u ON b.usuario_id = u.id
     ORDER BY u.nombre ASC'
)->fetchAll();

// Barbero a editar
$editando = null;
if ($accion === 'editar' && $id) {
    $stmt = $db->prepare(
        'SELECT b.*, u.nombre, u.apellido, u.email, u.telefono FROM barberos b JOIN usuarios u ON b.usuario_id = u.id WHERE b.id = :id'
    );
    $stmt->execute([':id'=>$id]);
    $editando = $stmt->fetch();
}
?>

<!-- Botón nueva -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div></div>
    <button class="btn btn-gold btn-sm" data-bs-toggle="modal" data-bs-target="#modalBarbero"
            onclick="resetModal()">
        <i class="bi bi-person-plus me-2"></i>Nuevo Barbero
    </button>
</div>

<!-- Tabla -->
<div class="admin-table-wrap">
    <div class="admin-table-header">
        <h6><i class="bi bi-person-badge me-2 text-gold"></i>Barberos registrados (<?= count($barberos) ?>)</h6>
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="text" class="form-control form-control-sm" placeholder="Buscar…"
                   data-search-table="tablaBarberos">
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="tablaBarberos">
            <thead>
                <tr>
                    <th>Barbero</th>
                    <th>Especialidad</th>
                    <th>Contacto</th>
                    <th>Comisión</th>
                    <th>Citas</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($barberos)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No hay barberos registrados.</td></tr>
                <?php endif; ?>
                <?php foreach ($barberos as $b): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-circle-sm"><?= strtoupper(substr($b['nombre'],0,1)) ?></div>
                            <div>
                                <div class="fw-semibold"><?= e($b['nombre'].' '.$b['apellido']) ?></div>
                                <div class="text-muted" style="font-size:.75rem;"><?= e($b['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= e($b['especialidad'] ?? '—') ?></td>
                    <td><?= e($b['telefono'] ?? '—') ?></td>
                    <td><?= number_format($b['comision_pct'],1) ?>%</td>
                    <td><span class="badge bg-secondary"><?= $b['total_citas'] ?></span></td>
                    <td>
                        <?php if ($b['activo']): ?>
                            <span class="badge bg-success">Activo</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-gold me-1"
                                onclick="editarBarbero(<?= $b['id']?>,<?= json_encode($b) ?>)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <a href="/Barberia/admin/barberos.php?accion=eliminar&id=<?= $b['id'] ?>&csrf_token=<?= csrfToken() ?>"
                           class="btn btn-sm btn-outline-danger"
                           data-confirm="¿Desactivar a este barbero?">
                            <i class="bi bi-slash-circle"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal crear/editar barbero -->
<div class="modal fade" id="modalBarbero" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalBarberoTitulo">Nuevo Barbero</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" class="needs-validation" novalidate>
                <?= csrfField() ?>
                <input type="hidden" name="edit_id" id="edit_id" value="0">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre <span class="text-gold">*</span></label>
                            <input type="text" name="nombre" id="f_nombre" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Apellido <span class="text-gold">*</span></label>
                            <input type="text" name="apellido" id="f_apellido" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Correo <span class="text-gold">*</span></label>
                            <input type="email" name="email" id="f_email" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="tel" name="telefono" id="f_telefono" class="form-control">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Especialidad</label>
                            <input type="text" name="especialidad" id="f_especialidad" class="form-control"
                                   placeholder="Ej: Fades, Barba clásica…">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Comisión (%)</label>
                            <input type="number" name="comision_pct" id="f_comision" class="form-control"
                                   min="0" max="100" step="0.5" value="40">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Biografía</label>
                            <textarea name="bio" id="f_bio" class="form-control" rows="2"
                                      placeholder="Breve descripción del barbero…"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Contraseña <span class="text-gold" id="pass_required">*</span></label>
                            <input type="password" name="password" id="f_password" class="form-control"
                                   placeholder="Mínimo 8 caracteres">
                            <div class="form-text text-muted" id="pass_hint"></div>
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
function resetModal() {
    document.getElementById('modalBarberoTitulo').textContent = 'Nuevo Barbero';
    document.getElementById('edit_id').value = 0;
    ['nombre','apellido','email','telefono','especialidad','bio','password'].forEach(f => {
        document.getElementById('f_' + f).value = f === 'f_comision' ? 40 : '';
    });
    document.getElementById('f_comision').value = 40;
    document.getElementById('pass_required').style.display = '';
    document.getElementById('pass_hint').textContent = '';
    document.getElementById('f_password').required = true;
}

function editarBarbero(id, data) {
    document.getElementById('modalBarberoTitulo').textContent = 'Editar Barbero';
    document.getElementById('edit_id').value       = id;
    document.getElementById('f_nombre').value      = data.nombre      || '';
    document.getElementById('f_apellido').value    = data.apellido    || '';
    document.getElementById('f_email').value       = data.email       || '';
    document.getElementById('f_telefono').value    = data.telefono    || '';
    document.getElementById('f_especialidad').value= data.especialidad|| '';
    document.getElementById('f_bio').value         = data.bio         || '';
    document.getElementById('f_comision').value    = data.comision_pct|| 40;
    document.getElementById('f_password').value    = '';
    document.getElementById('f_password').required = false;
    document.getElementById('pass_required').style.display = 'none';
    document.getElementById('pass_hint').textContent = 'Deja vacío para no cambiar la contraseña.';
    new bootstrap.Modal(document.getElementById('modalBarbero')).show();
}

<?php if (isset($_GET['nueva'])): ?>
window.addEventListener('load', () => {
    resetModal();
    new bootstrap.Modal(document.getElementById('modalBarbero')).show();
});
<?php endif; ?>
</script>
