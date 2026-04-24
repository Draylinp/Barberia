<?php
/**
 * Ingresos y comisiones del barbero
 * Barbería Premium — barbero/ingresos.php
 */
$titulo = 'Mis Ingresos';
require_once __DIR__ . '/../includes/header_barbero.php';

$db        = getDB();
$barberoId = $_SESSION['barbero_id'] ?? 0;

$desde = sanitizeStr($_GET['desde'] ?? date('Y-m-01'));
$hasta = sanitizeStr($_GET['hasta'] ?? date('Y-m-d'));

// Obtener comisión del barbero
$comPct = $db->prepare('SELECT comision_pct FROM barberos WHERE id=:b');
$comPct->execute([':b'=>$barberoId]);
$comisionPct = $comPct->fetchColumn() ?: 40;

// Ingresos en el rango
$sql = 'SELECT c.fecha, c.hora_inicio, s.nombre AS servicio, s.precio, p.estado AS pago_estado, p.metodo
        FROM citas c
        JOIN servicios s ON c.servicio_id = s.id
        LEFT JOIN pagos p ON p.cita_id = c.id
        WHERE c.barbero_id = :b AND c.fecha BETWEEN :d AND :h AND c.estado = "completada"
        ORDER BY c.fecha DESC, c.hora_inicio DESC';
$stmt = $db->prepare($sql);
$stmt->execute([':b'=>$barberoId,':d'=>$desde,':h'=>$hasta]);
$registros = $stmt->fetchAll();

$totalBruto  = array_sum(array_column($registros, 'precio'));
$totalComision = $totalBruto * $comisionPct / 100;
$totalCitas  = count($registros);
?>

<!-- Filtros -->
<div class="admin-card mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-sm-4">
            <label class="form-label">Desde</label>
            <input type="date" name="desde" class="form-control form-control-sm" value="<?= e($desde) ?>">
        </div>
        <div class="col-sm-4">
            <label class="form-label">Hasta</label>
            <input type="date" name="hasta" class="form-control form-control-sm" value="<?= e($hasta) ?>">
        </div>
        <div class="col-sm-4">
            <button type="submit" class="btn btn-gold btn-sm w-100">Consultar</button>
        </div>
    </form>
</div>

<!-- Resumen -->
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="stat-card">
            <div class="stat-icon gold"><i class="bi bi-calendar-check"></i></div>
            <div><div class="stat-num"><?= $totalCitas ?></div><div class="stat-label">Servicios completados</div></div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-cash-stack"></i></div>
            <div><div class="stat-num"><?= formatMoney($totalBruto) ?></div><div class="stat-label">Facturado</div></div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-wallet2"></i></div>
            <div>
                <div class="stat-num"><?= formatMoney($totalComision) ?></div>
                <div class="stat-label">Mi comisión (<?= number_format($comisionPct,0) ?>%)</div>
            </div>
        </div>
    </div>
</div>

<!-- Detalle -->
<div class="admin-table-wrap">
    <div class="admin-table-header">
        <h6><i class="bi bi-receipt me-2 text-gold"></i>Detalle de servicios</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Fecha</th><th>Hora</th><th>Servicio</th><th>Precio</th><th>Mi comisión</th><th>Pago</th></tr></thead>
            <tbody>
                <?php if (empty($registros)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No hay registros en este período.</td></tr>
                <?php endif; ?>
                <?php foreach ($registros as $r): ?>
                <tr>
                    <td><?= formatFecha($r['fecha']) ?></td>
                    <td><?= formatHora($r['hora_inicio']) ?></td>
                    <td><?= e($r['servicio']) ?></td>
                    <td class="text-gold"><?= formatMoney($r['precio']) ?></td>
                    <td class="text-success"><?= formatMoney($r['precio'] * $comisionPct / 100) ?></td>
                    <td>
                        <?php if ($r['pago_estado'] === 'pagado'): ?>
                            <span class="badge bg-success">Pagado</span>
                        <?php elseif ($r['pago_estado'] === 'pendiente'): ?>
                            <span class="badge bg-warning text-dark">Pendiente</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Sin registro</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <?php if (!empty($registros)): ?>
            <tfoot>
                <tr style="border-top:2px solid var(--border);">
                    <td colspan="3" class="text-end fw-semibold">Total:</td>
                    <td class="text-gold fw-bold"><?= formatMoney($totalBruto) ?></td>
                    <td class="text-success fw-bold"><?= formatMoney($totalComision) ?></td>
                    <td></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer_admin.php'; ?>
