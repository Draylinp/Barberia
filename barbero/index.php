<?php
/**
 * Agenda diaria del Barbero
 * Barbería Premium — barbero/index.php
 */
$titulo = 'Mi Agenda';
require_once __DIR__ . '/../includes/header_barbero.php';

$db        = getDB();
$barberoId = $_SESSION['barbero_id'] ?? 0;
$hoy       = sanitizeStr($_GET['fecha'] ?? date('Y-m-d'));

// Citas del día
$citas = $db->prepare(
    'SELECT c.*, s.nombre AS servicio, s.duracion_min, s.precio,
            u.nombre AS cl_nombre, u.apellido AS cl_apellido, u.telefono AS cl_tel
     FROM citas c
     JOIN servicios s  ON c.servicio_id = s.id
     JOIN clientes cl  ON c.cliente_id  = cl.id
     JOIN usuarios u   ON cl.usuario_id = u.id
     WHERE c.barbero_id = :b AND c.fecha = :f
     ORDER BY c.hora_inicio ASC'
);
$citas->execute([':b'=>$barberoId, ':f'=>$hoy]);
$citasHoy = $citas->fetchAll();

// Estadísticas rápidas hoy
$totalHoy      = count($citasHoy);
$completadasHoy= count(array_filter($citasHoy, fn($c) => $c['estado']==='completada'));
$ingresosHoy   = array_sum(array_map(fn($c) => $c['estado']==='completada' ? $c['precio'] : 0, $citasHoy));

$prevDate = date('Y-m-d', strtotime($hoy . ' -1 day'));
$nextDate = date('Y-m-d', strtotime($hoy . ' +1 day'));
?>

<!-- Navegación de fecha -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <a href="?fecha=<?= $prevDate ?>" class="btn btn-outline-gold btn-sm">
        <i class="bi bi-chevron-left"></i>
    </a>
    <div class="text-center">
        <h5 class="mb-0" style="font-family:var(--font-serif);">
            <?= date('l', strtotime($hoy)) === date('l') && $hoy === date('Y-m-d') ? 'Hoy — ' : '' ?>
            <?= date('d \d\e F, Y', strtotime($hoy)) ?>
        </h5>
        <?php if ($hoy !== date('Y-m-d')): ?>
        <a href="?fecha=<?= date('Y-m-d') ?>" class="text-gold small">Volver a hoy</a>
        <?php endif; ?>
    </div>
    <a href="?fecha=<?= $nextDate ?>" class="btn btn-outline-gold btn-sm">
        <i class="bi bi-chevron-right"></i>
    </a>
</div>

<!-- Stats del día -->
<div class="row g-3 mb-4">
    <div class="col-4">
        <div class="stat-card">
            <div class="stat-icon gold"><i class="bi bi-calendar-check"></i></div>
            <div><div class="stat-num"><?= $totalHoy ?></div><div class="stat-label">Citas hoy</div></div>
        </div>
    </div>
    <div class="col-4">
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-check-circle"></i></div>
            <div><div class="stat-num"><?= $completadasHoy ?></div><div class="stat-label">Completadas</div></div>
        </div>
    </div>
    <div class="col-4">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-currency-dollar"></i></div>
            <div><div class="stat-num"><?= formatMoney($ingresosHoy) ?></div><div class="stat-label">Cobrado hoy</div></div>
        </div>
    </div>
</div>

<!-- Lista de citas del día -->
<?php if (empty($citasHoy)): ?>
<div class="text-center py-5 text-muted">
    <i class="bi bi-calendar-x" style="font-size:3rem;opacity:.3;"></i>
    <p class="mt-3">No tienes citas programadas para este día.</p>
</div>
<?php else: ?>
<div id="agendaLista">
    <?php foreach ($citasHoy as $c): ?>
    <div class="agenda-card <?= $c['estado'] ?>">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <!-- Hora -->
                <div class="text-center" style="min-width:60px;">
                    <div class="text-gold fw-bold"><?= formatHora($c['hora_inicio']) ?></div>
                    <div class="text-muted" style="font-size:.72rem;"><?= (int)$c['duracion_min'] ?> min</div>
                </div>
                <!-- Info -->
                <div>
                    <div class="fw-semibold"><?= e($c['cl_nombre'].' '.$c['cl_apellido']) ?></div>
                    <div class="text-muted small">
                        <i class="bi bi-scissors me-1"></i><?= e($c['servicio']) ?>
                        &nbsp;·&nbsp;
                        <i class="bi bi-telephone me-1"></i><?= e($c['cl_tel'] ?? '—') ?>
                    </div>
                    <?php if ($c['notas_cliente']): ?>
                    <div class="text-muted" style="font-size:.77rem;margin-top:.25rem;">
                        <i class="bi bi-sticky me-1"></i><?= e($c['notas_cliente']) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-gold fw-semibold"><?= formatMoney($c['precio']) ?></span>
                <?= badgeEstadoCita($c['estado']) ?>
                <!-- Cambiar estado -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-gold dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
                        <?php
                        $transiciones = [
                            'pendiente'  => ['confirmada','cancelada'],
                            'confirmada' => ['en_proceso','cancelada'],
                            'en_proceso' => ['completada','cancelada'],
                        ];
                        $opciones = $transiciones[$c['estado']] ?? [];
                        foreach ($opciones as $nEst):
                        ?>
                        <li>
                            <a class="dropdown-item small"
                               href="/Barberia/barbero/citas.php?accion=estado&id=<?= $c['id'] ?>&estado=<?= $nEst ?>&csrf_token=<?= csrfToken() ?>&back=agenda&fecha=<?= $hoy ?>">
                                <?= ucfirst(str_replace('_',' ',$nEst)) ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer_admin.php'; ?>
