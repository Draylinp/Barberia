<?php
/**
 * Panel principal del cliente
 * Barbería Premium — cliente/index.php
 */
$titulo = 'Mi Panel';
require_once __DIR__ . '/../includes/header_cliente.php';

$db        = getDB();
$clienteId = $_SESSION['cliente_id'] ?? 0;
$hoy       = date('Y-m-d');

// Próximas citas
$proximas = $db->prepare(
    'SELECT c.*, s.nombre AS servicio, s.precio,
            ub.nombre AS ba_nombre, ub.apellido AS ba_apellido
     FROM citas c
     JOIN servicios s ON c.servicio_id = s.id
     JOIN barberos b  ON c.barbero_id  = b.id
     JOIN usuarios ub ON b.usuario_id  = ub.id
     WHERE c.cliente_id = :cl AND c.fecha >= :h AND c.estado NOT IN ("cancelada","completada")
     ORDER BY c.fecha ASC, c.hora_inicio ASC LIMIT 3'
);
$proximas->execute([':cl'=>$clienteId,':h'=>$hoy]);
$proximasCitas = $proximas->fetchAll();

// Estadísticas
$totalCitas     = $db->prepare('SELECT COUNT(*) FROM citas WHERE cliente_id=:cl');
$totalCitas->execute([':cl'=>$clienteId]);
$totalCitas = $totalCitas->fetchColumn();

$completadas = $db->prepare('SELECT COUNT(*) FROM citas WHERE cliente_id=:cl AND estado="completada"');
$completadas->execute([':cl'=>$clienteId]);
$completadas = $completadas->fetchColumn();

$cfg         = getConfig();
$minCancelar = (int)($cfg['min_cancelacion'] ?? 60);
?>

<!-- Bienvenida -->
<div class="admin-card mb-4" style="background:linear-gradient(135deg,var(--dark-3),var(--dark-4));border-color:rgba(201,162,39,.2);">
    <div class="d-flex align-items-center gap-3">
        <div class="avatar-circle" style="width:56px;height:56px;font-size:1.5rem;">
            <?= strtoupper(substr($usuario['nombre'],0,1)) ?>
        </div>
        <div>
            <h5 class="mb-0" style="font-family:var(--font-serif);">
                ¡Hola, <?= e($usuario['nombre']) ?>!
            </h5>
            <p class="text-muted small mb-0">Bienvenido a tu panel de Barbería Elite</p>
        </div>
        <div class="ms-auto">
            <a href="/Barberia/public/reservar.php" class="btn btn-gold">
                <i class="bi bi-calendar-plus me-2"></i>Reservar cita
            </a>
        </div>
    </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="stat-card">
            <div class="stat-icon gold"><i class="bi bi-calendar-check"></i></div>
            <div><div class="stat-num"><?= $totalCitas ?></div><div class="stat-label">Citas totales</div></div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-check-circle"></i></div>
            <div><div class="stat-num"><?= $completadas ?></div><div class="stat-label">Completadas</div></div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-calendar-event"></i></div>
            <div><div class="stat-num"><?= count($proximasCitas) ?></div><div class="stat-label">Próximas</div></div>
        </div>
    </div>
</div>

<!-- Próximas citas -->
<div class="admin-card">
    <div class="admin-card-title"><i class="bi bi-calendar-event"></i>Próximas citas</div>

    <?php if (empty($proximasCitas)): ?>
    <div class="text-center py-4 text-muted">
        <i class="bi bi-calendar-x" style="font-size:2.5rem;opacity:.3;"></i>
        <p class="mt-3 mb-3">No tienes citas próximas.</p>
        <a href="/Barberia/public/reservar.php" class="btn btn-gold btn-sm">
            <i class="bi bi-calendar-plus me-2"></i>Reservar ahora
        </a>
    </div>
    <?php else: ?>
    <?php foreach ($proximasCitas as $c):
        $fechaHoraStr = $c['fecha'] . ' ' . $c['hora_inicio'];
        $minutosRestantes = (strtotime($fechaHoraStr) - time()) / 60;
        $puedeCancelar = $minutosRestantes > $minCancelar;
    ?>
    <div class="agenda-card <?= $c['estado'] ?> mb-2">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <div class="text-gold fw-bold mb-1">
                    <?= formatFecha($c['fecha']) ?> — <?= formatHora($c['hora_inicio']) ?>
                </div>
                <div class="fw-semibold"><?= e($c['servicio']) ?></div>
                <div class="text-muted small">
                    <i class="bi bi-person me-1"></i><?= e($c['ba_nombre'].' '.$c['ba_apellido']) ?>
                    &nbsp;·&nbsp;
                    <span class="text-gold"><?= formatMoney($c['precio']) ?></span>
                </div>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <?= badgeEstadoCita($c['estado']) ?>
                <?php if ($puedeCancelar && in_array($c['estado'], ['pendiente','confirmada'])): ?>
                <a href="/Barberia/cliente/citas.php?cancelar=<?= $c['id'] ?>&csrf_token=<?= csrfToken() ?>"
                   class="btn btn-sm btn-outline-danger"
                   data-confirm="¿Cancelar esta cita?">
                    Cancelar
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <div class="mt-3">
        <a href="/Barberia/cliente/citas.php" class="btn btn-outline-gold btn-sm">
            Ver todas mis citas <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer_admin.php'; ?>
