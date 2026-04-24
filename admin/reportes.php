<?php
/**
 * Reportes y estadísticas
 * Barbería Premium — admin/reportes.php
 */
$titulo     = 'Reportes';
$breadcrumb = 'Reportes';
require_once __DIR__ . '/../includes/header_admin.php';

$db = getDB();

$desde = sanitizeStr($_GET['desde'] ?? date('Y-m-01'));
$hasta = sanitizeStr($_GET['hasta'] ?? date('Y-m-d'));
$filtroBarbero = sanitizeInt($_GET['barbero'] ?? 0);

// ── Ingresos en el rango ──────────────────────────────────────────────────────
$sqlIng = 'SELECT COALESCE(SUM(p.monto),0) FROM pagos p JOIN citas c ON p.cita_id=c.id WHERE c.fecha BETWEEN :d AND :h AND p.estado="pagado"';
$paramsBase = [':d'=>$desde, ':h'=>$hasta];
if ($filtroBarbero) { $sqlIng .= ' AND c.barbero_id=:b'; $paramsBase[':b'] = $filtroBarbero; }
$stmtIng = $db->prepare($sqlIng); $stmtIng->execute($paramsBase);
$totalIngresos = $stmtIng->fetchColumn();

// ── Citas en el rango ─────────────────────────────────────────────────────────
$sqlCitas = 'SELECT COUNT(*) FROM citas c WHERE c.fecha BETWEEN :d AND :h AND c.estado != "cancelada"';
$p2 = [':d'=>$desde,':h'=>$hasta];
if ($filtroBarbero) { $sqlCitas .= ' AND c.barbero_id=:b'; $p2[':b']=$filtroBarbero; }
$stmtC = $db->prepare($sqlCitas); $stmtC->execute($p2);
$totalCitas = $stmtC->fetchColumn();

// ── Por barbero ───────────────────────────────────────────────────────────────
$sqlBar = 'SELECT u.nombre, u.apellido, COUNT(c.id) AS citas,
           COALESCE(SUM(p.monto),0) AS ingresos,
           COALESCE(SUM(p.monto * b.comision_pct / 100),0) AS comision
           FROM citas c
           JOIN barberos b ON c.barbero_id = b.id
           JOIN usuarios u ON b.usuario_id = u.id
           LEFT JOIN pagos p ON p.cita_id = c.id AND p.estado = "pagado"
           WHERE c.fecha BETWEEN :d AND :h AND c.estado != "cancelada"
           GROUP BY c.barbero_id ORDER BY citas DESC';
$stmtBar = $db->prepare($sqlBar); $stmtBar->execute([':d'=>$desde,':h'=>$hasta]);
$porBarbero = $stmtBar->fetchAll();

// ── Por servicio ──────────────────────────────────────────────────────────────
$sqlSrv = 'SELECT s.nombre, COUNT(c.id) AS citas, COALESCE(SUM(p.monto),0) AS ingresos
           FROM citas c JOIN servicios s ON c.servicio_id=s.id
           LEFT JOIN pagos p ON p.cita_id=c.id AND p.estado="pagado"
           WHERE c.fecha BETWEEN :d AND :h AND c.estado!="cancelada"
           GROUP BY c.servicio_id ORDER BY citas DESC';
$stmtSrv = $db->prepare($sqlSrv); $stmtSrv->execute([':d'=>$desde,':h'=>$hasta]);
$porServicio = $stmtSrv->fetchAll();

// ── Por estado ────────────────────────────────────────────────────────────────
$stmtEst = $db->prepare('SELECT estado, COUNT(*) AS total FROM citas WHERE fecha BETWEEN :d AND :h GROUP BY estado');
$stmtEst->execute([':d'=>$desde,':h'=>$hasta]);
$porEstado = $stmtEst->fetchAll(PDO::FETCH_KEY_PAIR);

$barberos = $db->query('SELECT b.id, u.nombre, u.apellido FROM barberos b JOIN usuarios u ON b.usuario_id=u.id WHERE b.activo=1')->fetchAll();
?>

<!-- Filtros -->
<div class="admin-card mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-sm-3">
            <label class="form-label">Desde</label>
            <input type="date" name="desde" class="form-control form-control-sm" value="<?= e($desde) ?>">
        </div>
        <div class="col-sm-3">
            <label class="form-label">Hasta</label>
            <input type="date" name="hasta" class="form-control form-control-sm" value="<?= e($hasta) ?>">
        </div>
        <div class="col-sm-3">
            <label class="form-label">Barbero</label>
            <select name="barbero" class="form-select form-select-sm">
                <option value="">Todos</option>
                <?php foreach ($barberos as $b): ?>
                <option value="<?= $b['id'] ?>" <?= $filtroBarbero==$b['id']?'selected':'' ?>>
                    <?= e($b['nombre'].' '.$b['apellido']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-3 d-flex gap-2">
            <button type="submit" class="btn btn-gold btn-sm flex-fill"><i class="bi bi-search me-1"></i>Generar</button>
            <a href="/Barberia/admin/reportes.php" class="btn btn-outline-gold btn-sm"><i class="bi bi-x-lg"></i></a>
        </div>
    </form>
</div>

<!-- KPIs -->
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-currency-dollar"></i></div>
            <div>
                <div class="stat-num"><?= formatMoney($totalIngresos) ?></div>
                <div class="stat-label">Ingresos en el período</div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="stat-card">
            <div class="stat-icon gold"><i class="bi bi-calendar-check"></i></div>
            <div>
                <div class="stat-num"><?= $totalCitas ?></div>
                <div class="stat-label">Citas realizadas</div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-graph-up"></i></div>
            <div>
                <div class="stat-num"><?= $totalCitas > 0 ? formatMoney($totalIngresos / $totalCitas) : '$0.00' ?></div>
                <div class="stat-label">Promedio por cita</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Por barbero -->
    <div class="col-lg-6">
        <div class="admin-table-wrap">
            <div class="admin-table-header">
                <h6><i class="bi bi-person-badge me-2 text-gold"></i>Rendimiento por Barbero</h6>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Barbero</th><th>Citas</th><th>Ingresos</th><th>Comisión</th></tr></thead>
                    <tbody>
                        <?php if (empty($porBarbero)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">Sin datos.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($porBarbero as $r): ?>
                        <tr>
                            <td><?= e($r['nombre'].' '.$r['apellido']) ?></td>
                            <td><span class="badge bg-secondary"><?= $r['citas'] ?></span></td>
                            <td class="text-gold"><?= formatMoney($r['ingresos']) ?></td>
                            <td class="text-muted"><?= formatMoney($r['comision']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Por servicio -->
    <div class="col-lg-6">
        <div class="admin-table-wrap">
            <div class="admin-table-header">
                <h6><i class="bi bi-scissors me-2 text-gold"></i>Servicios más solicitados</h6>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Servicio</th><th>Citas</th><th>Ingresos</th></tr></thead>
                    <tbody>
                        <?php if (empty($porServicio)): ?>
                        <tr><td colspan="3" class="text-center text-muted py-3">Sin datos.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($porServicio as $r): ?>
                        <tr>
                            <td><?= e($r['nombre']) ?></td>
                            <td><span class="badge bg-secondary"><?= $r['citas'] ?></span></td>
                            <td class="text-gold"><?= formatMoney($r['ingresos']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Por estado -->
    <div class="col-12">
        <div class="admin-card">
            <div class="admin-card-title"><i class="bi bi-pie-chart"></i>Distribución por estado de citas</div>
            <div class="row g-3">
                <?php
                $estadoColors = ['pendiente'=>'warning','confirmada'=>'info','en_proceso'=>'primary','completada'=>'success','cancelada'=>'danger'];
                foreach ($estadoColors as $est => $color):
                    $cnt = $porEstado[$est] ?? 0;
                ?>
                <div class="col-sm-4 col-md-2-4" style="flex:0 0 20%;max-width:20%;">
                    <div class="text-center p-3" style="background:var(--dark-4);border-radius:8px;">
                        <div class="fw-bold fs-4 text-<?= $color ?>"><?= $cnt ?></div>
                        <div class="text-muted small mt-1"><?= ucfirst(str_replace('_',' ',$est)) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer_admin.php'; ?>
