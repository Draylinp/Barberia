<?php
/**
 * Gestión de citas del barbero
 * Barbería Premium — barbero/citas.php
 */
$titulo = 'Mis Citas';
require_once __DIR__ . '/../includes/header_barbero.php';

$db        = getDB();
$barberoId = $_SESSION['barbero_id'] ?? 0;
$accion    = sanitizeStr($_GET['accion'] ?? '');
$id        = sanitizeInt($_GET['id']     ?? 0);

// ── Cambiar estado ────────────────────────────────────────────────────────────
if ($accion === 'estado' && $id) {
    verifyCsrf();
    $nuevoEstado = sanitizeStr($_GET['estado'] ?? '');
    $permitidos  = ['confirmada','en_proceso','completada','cancelada'];

    // Verificar que la cita pertenece al barbero
    $check = $db->prepare('SELECT id FROM citas WHERE id=:id AND barbero_id=:b');
    $check->execute([':id'=>$id, ':b'=>$barberoId]);

    if ($check->fetch() && in_array($nuevoEstado, $permitidos)) {
        $db->prepare('UPDATE citas SET estado=:e WHERE id=:id')->execute([':e'=>$nuevoEstado,':id'=>$id]);
        if ($nuevoEstado === 'completada') {
            // Registrar pago pendiente si no existe
            $existePago = $db->prepare('SELECT id FROM pagos WHERE cita_id=:cid');
            $existePago->execute([':cid'=>$id]);
            if (!$existePago->fetch()) {
                $precio = $db->prepare('SELECT s.precio FROM citas c JOIN servicios s ON c.servicio_id=s.id WHERE c.id=:id');
                $precio->execute([':id'=>$id]);
                $monto = $precio->fetchColumn();
                if ($monto) {
                    $db->prepare('INSERT INTO pagos (cita_id,monto,estado) VALUES (:c,:m,"pendiente")')
                       ->execute([':c'=>$id,':m'=>$monto]);
                }
            }
        }
        setFlash('success', 'Estado actualizado correctamente.');
    }

    $back = sanitizeStr($_GET['back'] ?? '');
    $fecha = sanitizeStr($_GET['fecha'] ?? '');
    if ($back === 'agenda' && $fecha) {
        header("Location: /Barberia/barbero/index.php?fecha=$fecha");
    } else {
        header('Location: /Barberia/barbero/citas.php');
    }
    exit;
}

// ── Agregar notas ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nota_cita_id'])) {
    verifyCsrf();
    $citaId = sanitizeInt($_POST['nota_cita_id']);
    $nota   = sanitizeStr($_POST['notas_barbero'] ?? '');
    $db->prepare('UPDATE citas SET notas_barbero=:n WHERE id=:id AND barbero_id=:b')
       ->execute([':n'=>$nota,':id'=>$citaId,':b'=>$barberoId]);
    setFlash('success', 'Nota guardada.');
    header('Location: /Barberia/barbero/citas.php'); exit;
}

// ── Filtros ───────────────────────────────────────────────────────────────────
$filtroFecha  = sanitizeStr($_GET['fecha']  ?? '');
$filtroEstado = sanitizeStr($_GET['estado'] ?? '');

$where  = ['c.barbero_id = :b'];
$params = [':b' => $barberoId];
if ($filtroFecha)  { $where[] = 'c.fecha = :f'; $params[':f'] = $filtroFecha; }
if ($filtroEstado) { $where[] = 'c.estado = :e'; $params[':e'] = $filtroEstado; }

$sql = 'SELECT c.*, s.nombre AS servicio, s.precio,
               u.nombre AS cl_nombre, u.apellido AS cl_apellido, u.telefono AS cl_tel
        FROM citas c
        JOIN servicios s ON c.servicio_id = s.id
        JOIN clientes cl ON c.cliente_id  = cl.id
        JOIN usuarios u  ON cl.usuario_id = u.id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY c.fecha DESC, c.hora_inicio ASC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$citas = $stmt->fetchAll();
?>

<!-- Filtros -->
<div class="admin-card mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-sm-4">
            <label class="form-label">Fecha</label>
            <input type="date" name="fecha" class="form-control form-control-sm" value="<?= e($filtroFecha) ?>">
        </div>
        <div class="col-sm-4">
            <label class="form-label">Estado</label>
            <select name="estado" class="form-select form-select-sm">
                <option value="">Todos</option>
                <?php foreach (['pendiente','confirmada','en_proceso','completada','cancelada'] as $est): ?>
                <option value="<?= $est ?>" <?= $filtroEstado===$est?'selected':'' ?>>
                    <?= ucfirst(str_replace('_',' ',$est)) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-4 d-flex gap-2">
            <button type="submit" class="btn btn-gold btn-sm flex-fill">Filtrar</button>
            <a href="/Barberia/barbero/citas.php" class="btn btn-outline-gold btn-sm"><i class="bi bi-x-lg"></i></a>
        </div>
    </form>
</div>

<?php if (empty($citas)): ?>
<div class="text-center py-5 text-muted">
    <i class="bi bi-calendar-x" style="font-size:3rem;opacity:.3;"></i>
    <p class="mt-3">No hay citas para este filtro.</p>
</div>
<?php else: ?>
<?php foreach ($citas as $c): ?>
<div class="agenda-card <?= $c['estado'] ?> mb-2">
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div>
            <div class="d-flex align-items-center gap-3 mb-1">
                <span class="text-gold fw-bold"><?= formatFecha($c['fecha']) ?> <?= formatHora($c['hora_inicio']) ?></span>
                <?= badgeEstadoCita($c['estado']) ?>
            </div>
            <div class="fw-semibold"><?= e($c['cl_nombre'].' '.$c['cl_apellido']) ?></div>
            <div class="text-muted small">
                <i class="bi bi-scissors me-1"></i><?= e($c['servicio']) ?>
                &nbsp;·&nbsp;
                <i class="bi bi-telephone me-1"></i><?= e($c['cl_tel'] ?? '—') ?>
                &nbsp;·&nbsp;
                <span class="text-gold"><?= formatMoney($c['precio']) ?></span>
            </div>
            <?php if ($c['notas_barbero']): ?>
            <div class="text-muted small mt-1"><i class="bi bi-sticky me-1"></i><?= e($c['notas_barbero']) ?></div>
            <?php endif; ?>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <?php
            $transiciones = ['pendiente'=>['confirmada','cancelada'],'confirmada'=>['en_proceso','cancelada'],'en_proceso'=>['completada']];
            $opciones = $transiciones[$c['estado']] ?? [];
            foreach ($opciones as $nEst):
                $btnClass = match($nEst) { 'completada'=>'btn-gold', 'cancelada'=>'btn-outline-danger', default=>'btn-outline-gold' };
            ?>
            <a href="?accion=estado&id=<?= $c['id'] ?>&estado=<?= $nEst ?>&csrf_token=<?= csrfToken() ?>"
               class="btn btn-sm <?= $btnClass ?>">
                <?= ucfirst(str_replace('_',' ',$nEst)) ?>
            </a>
            <?php endforeach; ?>
            <button class="btn btn-sm btn-outline-gold" data-bs-toggle="collapse"
                    data-bs-target="#nota-<?= $c['id'] ?>">
                <i class="bi bi-sticky"></i>
            </button>
        </div>
    </div>
    <!-- Nota colapsable -->
    <div class="collapse mt-2" id="nota-<?= $c['id'] ?>">
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="nota_cita_id" value="<?= $c['id'] ?>">
            <div class="d-flex gap-2">
                <textarea name="notas_barbero" class="form-control form-control-sm" rows="2"
                          placeholder="Agregar nota interna…"><?= e($c['notas_barbero'] ?? '') ?></textarea>
                <button type="submit" class="btn btn-sm btn-gold align-self-end">Guardar</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer_admin.php'; ?>
