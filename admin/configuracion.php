<?php
/**
 * Configuración general de la barbería
 * Barbería Premium — admin/configuracion.php
 */
$titulo     = 'Configuración';
$breadcrumb = 'Configuración';
require_once __DIR__ . '/../includes/header_admin.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['seccion'])) {
    verifyCsrf();
    $sec = sanitizeStr($_POST['seccion']);

    if ($sec === 'general') {
        $fields = ['nombre','slogan','telefono','email','direccion','ciudad','moneda'];
        $sets   = implode(',', array_map(fn($f) => "$f=:$f", $fields));
        $params = array_combine(array_map(fn($f) => ":$f", $fields),
                                array_map(fn($f) => sanitizeStr($_POST[$f] ?? ''), $fields));
        $db->prepare("UPDATE configuracion SET $sets WHERE id=1")->execute($params);
        setFlash('success', 'Configuración general guardada.');
    }

    if ($sec === 'horario') {
        $apertura = sanitizeStr($_POST['hora_apertura'] ?? '09:00');
        $cierre   = sanitizeStr($_POST['hora_cierre']   ?? '20:00');
        $dias     = sanitizeStr($_POST['dias_trabajo']   ?? '');
        $minCan   = sanitizeInt($_POST['min_cancelacion']?? 60);
        $db->prepare('UPDATE configuracion SET hora_apertura=:a,hora_cierre=:c,dias_trabajo=:d,min_cancelacion=:m WHERE id=1')
           ->execute([':a'=>$apertura,':c'=>$cierre,':d'=>$dias,':m'=>$minCan]);
        setFlash('success', 'Horarios actualizados.');
    }

    header('Location: /Barberia/admin/configuracion.php'); exit;
}

$cfg     = $db->query('SELECT * FROM configuracion WHERE id=1')->fetch();
$mensajes= $db->query('SELECT * FROM contactos ORDER BY created_at DESC LIMIT 20')->fetchAll();
$noLeidos= $db->query('SELECT COUNT(*) FROM contactos WHERE leido=0')->fetchColumn();

// Marcar como leído si se abre
if (isset($_GET['leer']) && sanitizeInt($_GET['leer'])) {
    $db->prepare('UPDATE contactos SET leido=1 WHERE id=:id')->execute([':id'=>sanitizeInt($_GET['leer'])]);
    header('Location: /Barberia/admin/configuracion.php#mensajes'); exit;
}
?>

<div class="row g-4">
    <!-- Configuración general -->
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="admin-card-title"><i class="bi bi-gear"></i>Datos generales</div>
            <form method="POST" class="needs-validation" novalidate>
                <?= csrfField() ?>
                <input type="hidden" name="seccion" value="general">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Nombre de la barbería</label>
                        <input type="text" name="nombre" class="form-control" value="<?= e($cfg['nombre'] ?? '') ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Slogan</label>
                        <input type="text" name="slogan" class="form-control" value="<?= e($cfg['slogan'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" class="form-control" value="<?= e($cfg['telefono'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Correo</label>
                        <input type="email" name="email" class="form-control" value="<?= e($cfg['email'] ?? '') ?>">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Dirección</label>
                        <input type="text" name="direccion" class="form-control" value="<?= e($cfg['direccion'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Ciudad</label>
                        <input type="text" name="ciudad" class="form-control" value="<?= e($cfg['ciudad'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Moneda</label>
                        <select name="moneda" class="form-select">
                            <option value="USD" <?= ($cfg['moneda']??'')=='USD'?'selected':'' ?>>USD ($)</option>
                            <option value="EUR" <?= ($cfg['moneda']??'')=='EUR'?'selected':'' ?>>EUR (€)</option>
                            <option value="COP" <?= ($cfg['moneda']??'')=='COP'?'selected':'' ?>>COP</option>
                            <option value="MXN" <?= ($cfg['moneda']??'')=='MXN'?'selected':'' ?>>MXN</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-gold btn-sm"><i class="bi bi-save me-2"></i>Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Horarios y reglas -->
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="admin-card-title"><i class="bi bi-clock"></i>Horarios y reglas</div>
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="seccion" value="horario">
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label">Hora apertura</label>
                        <input type="time" name="hora_apertura" class="form-control" value="<?= e($cfg['hora_apertura'] ?? '09:00') ?>">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Hora cierre</label>
                        <input type="time" name="hora_cierre" class="form-control" value="<?= e($cfg['hora_cierre'] ?? '20:00') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Días de trabajo</label>
                        <input type="text" name="dias_trabajo" class="form-control"
                               value="<?= e($cfg['dias_trabajo'] ?? 'Lun,Mar,Mie,Jue,Vie,Sab') ?>"
                               placeholder="Lun,Mar,Mie,Jue,Vie,Sab">
                        <div class="form-text text-muted">Separados por coma</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Minutos mínimos para cancelar</label>
                        <input type="number" name="min_cancelacion" class="form-control"
                               value="<?= (int)($cfg['min_cancelacion'] ?? 60) ?>" min="0">
                        <div class="form-text text-muted">Ej: 60 = el cliente puede cancelar hasta 1h antes</div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-gold btn-sm"><i class="bi bi-save me-2"></i>Guardar horarios</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Mensajes de contacto -->
    <div class="col-12" id="mensajes">
        <div class="admin-table-wrap">
            <div class="admin-table-header">
                <h6>
                    <i class="bi bi-envelope me-2 text-gold"></i>Mensajes de contacto
                    <?php if ($noLeidos > 0): ?>
                        <span class="badge bg-danger ms-1"><?= $noLeidos ?> nuevos</span>
                    <?php endif; ?>
                </h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Nombre</th><th>Email</th><th>Mensaje</th><th>Fecha</th><th></th></tr></thead>
                    <tbody>
                        <?php if (empty($mensajes)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No hay mensajes.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($mensajes as $m): ?>
                        <tr style="<?= !$m['leido'] ? 'background:rgba(201,162,39,.05)' : '' ?>">
                            <td>
                                <?php if (!$m['leido']): ?>
                                    <span class="badge bg-gold text-dark me-1" style="font-size:.6rem;">Nuevo</span>
                                <?php endif; ?>
                                <?= e($m['nombre']) ?>
                            </td>
                            <td class="text-muted small"><?= e($m['email']) ?></td>
                            <td style="max-width:280px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                <?= e($m['mensaje']) ?>
                            </td>
                            <td class="text-muted small"><?= formatFecha($m['created_at']) ?></td>
                            <td>
                                <?php if (!$m['leido']): ?>
                                <a href="?leer=<?= $m['id'] ?>" class="btn btn-sm btn-outline-gold">
                                    <i class="bi bi-check2"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer_admin.php'; ?>
