<?php
/**
 * Historial y gestión de citas del cliente
 * Barbería Premium — cliente/citas.php
 */
$titulo = 'Mis Citas';
require_once __DIR__ . '/../includes/header_cliente.php';

$db        = getDB();
$clienteId = $_SESSION['cliente_id'] ?? 0;
$cfg       = getConfig();
$minCancelar = (int)($cfg['min_cancelacion'] ?? 60);

// ── Cancelar cita ─────────────────────────────────────────────────────────────
if (isset($_GET['cancelar'])) {
    verifyCsrf();
    $citaId = sanitizeInt($_GET['cancelar']);

    $c = $db->prepare('SELECT fecha, hora_inicio, estado FROM citas WHERE id=:id AND cliente_id=:cl');
    $c->execute([':id'=>$citaId,':cl'=>$clienteId]);
    $cita = $c->fetch();

    if ($cita && in_array($cita['estado'], ['pendiente','confirmada'])) {
        $minutosRestantes = (strtotime($cita['fecha'].' '.$cita['hora_inicio']) - time()) / 60;
        if ($minutosRestantes >= $minCancelar) {
            $db->prepare('UPDATE citas SET estado="cancelada" WHERE id=:id')
               ->execute([':id'=>$citaId]);
            setFlash('success', 'Cita cancelada exitosamente.');
        } else {
            setFlash('error', "Solo puedes cancelar con al menos {$minCancelar} minutos de anticipación.");
        }
    } else {
        setFlash('error', 'No es posible cancelar esta cita.');
    }
    header('Location: /Barberia/cliente/citas.php'); exit;
}

$filtro = sanitizeStr($_GET['filtro'] ?? 'proximas');

$where  = ['c.cliente_id = :cl'];
$params = [':cl' => $clienteId];

if ($filtro === 'proximas') {
    $where[] = 'c.fecha >= CURDATE()';
    $where[] = 'c.estado NOT IN ("cancelada","completada")';
} elseif ($filtro === 'historial') {
    $where[] = '(c.estado IN ("completada","cancelada") OR c.fecha < CURDATE())';
}

$sql = 'SELECT c.*, s.nombre AS servicio, s.precio, s.duracion_min,
               ub.nombre AS ba_nombre, ub.apellido AS ba_apellido
        FROM citas c
        JOIN servicios s ON c.servicio_id = s.id
        JOIN barberos b  ON c.barbero_id  = b.id
        JOIN usuarios ub ON b.usuario_id  = ub.id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY c.fecha DESC, c.hora_inicio DESC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$citas = $stmt->fetchAll();
?>

<!-- Tabs filtro -->
<ul class="nav nav-pills mb-4 gap-1">
    <li class="nav-item">
        <a href="?filtro=proximas"
           class="nav-link <?= $filtro==='proximas'?'active':'text-muted' ?>"
           style="<?= $filtro==='proximas'?'background:var(--gold);color:#000;':'' ?>">
            Próximas
        </a>
    </li>
    <li class="nav-item">
        <a href="?filtro=todas"
           class="nav-link <?= $filtro==='todas'?'active':'text-muted' ?>"
           style="<?= $filtro==='todas'?'background:var(--gold);color:#000;':'' ?>">
            Todas
        </a>
    </li>
    <li class="nav-item">
        <a href="?filtro=historial"
           class="nav-link <?= $filtro==='historial'?'active':'text-muted' ?>"
           style="<?= $filtro==='historial'?'background:var(--gold);color:#000;':'' ?>">
            Historial
        </a>
    </li>
</ul>

<?php if (empty($citas)): ?>
<div class="text-center py-5 text-muted">
    <i class="bi bi-calendar-x" style="font-size:3rem;opacity:.3;"></i>
    <p class="mt-3 mb-3">No tienes citas en esta categoría.</p>
    <a href="/Barberia/public/reservar.php" class="btn btn-gold btn-sm">
        <i class="bi bi-calendar-plus me-2"></i>Reservar una cita
    </a>
</div>
<?php else: ?>
<?php foreach ($citas as $c):
    $fechaHoraStr   = $c['fecha'] . ' ' . $c['hora_inicio'];
    $minutosRestantes = (strtotime($fechaHoraStr) - time()) / 60;
    $puedeCancelar  = $minutosRestantes > $minCancelar && in_array($c['estado'],['pendiente','confirmada']);
?>
<div class="agenda-card <?= $c['estado'] ?> mb-3">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <?= badgeEstadoCita($c['estado']) ?>
                <span class="text-gold fw-semibold"><?= formatFecha($c['fecha']) ?></span>
                <span class="text-muted small"><?= formatHora($c['hora_inicio']) ?> – <?= formatHora($c['hora_fin']) ?></span>
            </div>
            <div class="fw-semibold fs-6 mb-1"><?= e($c['servicio']) ?></div>
            <div class="text-muted small">
                <i class="bi bi-person me-1"></i><?= e($c['ba_nombre'].' '.$c['ba_apellido']) ?>
                &nbsp;·&nbsp;
                <i class="bi bi-clock me-1"></i><?= (int)$c['duracion_min'] ?> min
                &nbsp;·&nbsp;
                <span class="text-gold fw-semibold"><?= formatMoney($c['precio']) ?></span>
            </div>
            <?php if ($c['notas_cliente']): ?>
            <div class="text-muted small mt-1">
                <i class="bi bi-sticky me-1"></i><?= e($c['notas_cliente']) ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="d-flex flex-column gap-2 align-items-end">
            <?php if ($puedeCancelar): ?>
            <a href="?cancelar=<?= $c['id'] ?>&csrf_token=<?= csrfToken() ?>"
               class="btn btn-sm btn-outline-danger"
               data-confirm="¿Cancelar esta cita?">
                <i class="bi bi-x-circle me-1"></i>Cancelar
            </a>
            <?php endif; ?>
            <?php if ($c['estado'] === 'completada'): ?>
            <a href="/Barberia/public/reservar.php?servicio_id=<?= $c['servicio_id'] ?>"
               class="btn btn-sm btn-outline-gold">
                <i class="bi bi-arrow-repeat me-1"></i>Repetir
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<div class="mt-4">
    <a href="/Barberia/public/reservar.php" class="btn btn-gold">
        <i class="bi bi-calendar-plus me-2"></i>Reservar nueva cita
    </a>
</div>

<?php require_once __DIR__ . '/../includes/footer_admin.php'; ?>
