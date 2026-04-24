<?php
/**
 * Dashboard del Administrador
 * Barbería Premium — admin/index.php
 */
$titulo    = 'Dashboard';
$breadcrumb = 'Dashboard';
require_once __DIR__ . '/../includes/header_admin.php';

$db  = getDB();
$hoy = date('Y-m-d');

// ── Estadísticas del día ──────────────────────────────────────────────────────
$citasHoy     = $db->prepare('SELECT COUNT(*) FROM citas WHERE fecha = :h AND estado != "cancelada"');
$citasHoy->execute([':h' => $hoy]);
$totalCitasHoy = $citasHoy->fetchColumn();

$ingresosHoy  = $db->prepare('SELECT COALESCE(SUM(p.monto),0) FROM pagos p JOIN citas c ON p.cita_id = c.id WHERE c.fecha = :h AND p.estado = "pagado"');
$ingresosHoy->execute([':h' => $hoy]);
$totalIngresosHoy = $ingresosHoy->fetchColumn();

$totalClientes = $db->query('SELECT COUNT(*) FROM clientes')->fetchColumn();
$totalBarberos = $db->query('SELECT COUNT(*) FROM barberos WHERE activo = 1')->fetchColumn();

// ── Ingresos del mes ──────────────────────────────────────────────────────────
$mes = date('Y-m');
$ingMes = $db->prepare('SELECT COALESCE(SUM(p.monto),0) FROM pagos p JOIN citas c ON p.cita_id = c.id WHERE DATE_FORMAT(c.fecha,"%Y-%m") = :m AND p.estado = "pagado"');
$ingMes->execute([':m' => $mes]);
$totalIngresosMes = $ingMes->fetchColumn();

// ── Servicios más solicitados ────────────────────────────────────────────────
$topServicios = $db->query(
    'SELECT s.nombre, COUNT(c.id) AS total
     FROM citas c JOIN servicios s ON c.servicio_id = s.id
     WHERE c.estado != "cancelada"
     GROUP BY c.servicio_id ORDER BY total DESC LIMIT 5'
)->fetchAll();

// ── Barbero con más trabajo ───────────────────────────────────────────────────
$topBarbero = $db->query(
    'SELECT u.nombre, u.apellido, COUNT(c.id) AS total
     FROM citas c JOIN barberos b ON c.barbero_id = b.id JOIN usuarios u ON b.usuario_id = u.id
     WHERE c.estado != "cancelada"
     GROUP BY c.barbero_id ORDER BY total DESC LIMIT 1'
)->fetch();

// ── Últimas citas ─────────────────────────────────────────────────────────────
$ultimasCitas = $db->query(
    'SELECT c.*, s.nombre AS servicio, u.nombre AS cliente_nombre, u.apellido AS cliente_apellido,
            ub.nombre AS barbero_nombre, ub.apellido AS barbero_apellido
     FROM citas c
     JOIN servicios s   ON c.servicio_id = s.id
     JOIN clientes cl   ON c.cliente_id  = cl.id
     JOIN usuarios u    ON cl.usuario_id = u.id
     JOIN barberos b    ON c.barbero_id  = b.id
     JOIN usuarios ub   ON b.usuario_id  = ub.id
     ORDER BY c.created_at DESC LIMIT 8'
)->fetchAll();

// ── Citas pendientes hoy ──────────────────────────────────────────────────────
$pendientesHoy = $db->prepare(
    'SELECT COUNT(*) FROM citas WHERE fecha = :h AND estado = "pendiente"'
);
$pendientesHoy->execute([':h' => $hoy]);
$totalPendientes = $pendientesHoy->fetchColumn();
?>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon gold"><i class="bi bi-calendar-check"></i></div>
            <div>
                <div class="stat-num"><?= $totalCitasHoy ?></div>
                <div class="stat-label">Citas hoy</div>
                <?php if ($totalPendientes > 0): ?>
                <div class="stat-change"><i class="bi bi-clock text-warning me-1"></i><?= $totalPendientes ?> pendientes</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-currency-dollar"></i></div>
            <div>
                <div class="stat-num"><?= formatMoney($totalIngresosHoy) ?></div>
                <div class="stat-label">Ingresos hoy</div>
                <div class="stat-change up"><i class="bi bi-arrow-up me-1"></i>Este mes: <?= formatMoney($totalIngresosMes) ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-people"></i></div>
            <div>
                <div class="stat-num"><?= $totalClientes ?></div>
                <div class="stat-label">Clientes registrados</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon purple"><i class="bi bi-person-badge"></i></div>
            <div>
                <div class="stat-num"><?= $totalBarberos ?></div>
                <div class="stat-label">Barberos activos</div>
                <?php if ($topBarbero): ?>
                <div class="stat-change"><i class="bi bi-star-fill text-warning me-1"></i><?= e($topBarbero['nombre']) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Últimas citas -->
    <div class="col-lg-8">
        <div class="admin-table-wrap">
            <div class="admin-table-header">
                <h6><i class="bi bi-calendar-event me-2 text-gold"></i>Últimas Citas</h6>
                <div class="d-flex gap-2">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" class="form-control form-control-sm" placeholder="Buscar…"
                               data-search-table="tablaCitas">
                    </div>
                    <a href="/Barberia/admin/citas.php" class="btn btn-gold btn-sm">
                        <i class="bi bi-plus-lg me-1"></i>Nueva cita
                    </a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="tablaCitas">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Servicio</th>
                            <th>Barbero</th>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ultimasCitas)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No hay citas registradas.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($ultimasCitas as $c): ?>
                        <tr>
                            <td><?= e($c['cliente_nombre'] . ' ' . $c['cliente_apellido']) ?></td>
                            <td><?= e($c['servicio']) ?></td>
                            <td><?= e($c['barbero_nombre'] . ' ' . $c['barbero_apellido']) ?></td>
                            <td><?= formatFecha($c['fecha']) ?></td>
                            <td><?= formatHora($c['hora_inicio']) ?></td>
                            <td><?= badgeEstadoCita($c['estado']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Panel lateral -->
    <div class="col-lg-4">
        <!-- Servicios más solicitados -->
        <div class="admin-card mb-4">
            <div class="admin-card-title"><i class="bi bi-bar-chart"></i>Servicios más solicitados</div>
            <?php if (empty($topServicios)): ?>
                <p class="text-muted small">Sin datos aún.</p>
            <?php endif; ?>
            <?php foreach ($topServicios as $ts): ?>
            <div class="mb-3">
                <div class="d-flex justify-content-between small mb-1">
                    <span><?= e($ts['nombre']) ?></span>
                    <span class="text-gold fw-semibold"><?= $ts['total'] ?></span>
                </div>
                <?php $max = $topServicios[0]['total'] ?: 1; $pct = round($ts['total'] / $max * 100); ?>
                <div style="height:6px;background:var(--dark-5);border-radius:3px;">
                    <div style="width:<?= $pct ?>%;height:100%;background:var(--gold);border-radius:3px;transition:width .6s;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Accesos rápidos -->
        <div class="admin-card">
            <div class="admin-card-title"><i class="bi bi-lightning"></i>Accesos rápidos</div>
            <div class="d-grid gap-2">
                <a href="/Barberia/admin/citas.php?nueva=1" class="btn btn-gold btn-sm">
                    <i class="bi bi-calendar-plus me-2"></i>Nueva cita
                </a>
                <a href="/Barberia/admin/barberos.php?nueva=1" class="btn btn-outline-gold btn-sm">
                    <i class="bi bi-person-plus me-2"></i>Agregar barbero
                </a>
                <a href="/Barberia/admin/servicios.php?nuevo=1" class="btn btn-outline-gold btn-sm">
                    <i class="bi bi-scissors me-2"></i>Nuevo servicio
                </a>
                <a href="/Barberia/admin/reportes.php" class="btn btn-outline-gold btn-sm">
                    <i class="bi bi-file-earmark-bar-graph me-2"></i>Ver reportes
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer_admin.php'; ?>
