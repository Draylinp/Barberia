<?php
/**
 * Gestión de Pagos
 * Barbería Premium — admin/pagos.php
 */
$titulo     = 'Pagos';
$breadcrumb = 'Pagos';
require_once __DIR__ . '/../includes/header_admin.php';

$db     = getDB();
$accion = sanitizeStr($_GET['accion'] ?? '');
$id     = sanitizeInt($_GET['id']     ?? 0);

// ── MARCAR COMO PAGADO ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pagar_id'])) {
    verifyCsrf();
    $pagoId  = sanitizeInt($_POST['pagar_id']);
    $metodo  = sanitizeStr($_POST['metodo']    ?? 'efectivo');
    $ref     = sanitizeStr($_POST['referencia']?? '');
    $db->prepare(
        'UPDATE pagos SET estado="pagado", metodo=:m, referencia=:r, pagado_en=NOW() WHERE id=:id'
    )->execute([':m'=>$metodo,':r'=>$ref,':id'=>$pagoId]);
    setFlash('success', 'Pago registrado exitosamente.');
    header('Location: /Barberia/admin/pagos.php'); exit;
}

$filtroEstado = sanitizeStr($_GET['estado'] ?? '');
$filtroFecha  = sanitizeStr($_GET['fecha']  ?? '');

$where = ['1=1'];
$params = [];
if ($filtroEstado) { $where[] = 'p.estado = :e'; $params[':e'] = $filtroEstado; }
if ($filtroFecha)  { $where[] = 'c.fecha = :f';  $params[':f'] = $filtroFecha; }

$sql = 'SELECT p.*, c.fecha, c.hora_inicio, s.nombre AS servicio,
               u.nombre AS cl_nombre, u.apellido AS cl_apellido,
               ub.nombre AS ba_nombre, ub.apellido AS ba_apellido
        FROM pagos p
        JOIN citas c    ON p.cita_id    = c.id
        JOIN servicios s ON c.servicio_id = s.id
        JOIN clientes cl ON c.cliente_id  = cl.id
        JOIN usuarios u  ON cl.usuario_id = u.id
        JOIN barberos b  ON c.barbero_id  = b.id
        JOIN usuarios ub ON b.usuario_id  = ub.id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY p.created_at DESC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$pagos = $stmt->fetchAll();

$totalPagado   = array_sum(array_column(array_filter($pagos, fn($p) => $p['estado'] === 'pagado'), 'monto'));
$totalPendiente= array_sum(array_column(array_filter($pagos, fn($p) => $p['estado'] === 'pendiente'), 'monto'));
?>

<!-- Stats rápidos -->
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-check-circle"></i></div>
            <div><div class="stat-num"><?= formatMoney($totalPagado) ?></div><div class="stat-label">Cobrado</div></div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="stat-card">
            <div class="stat-icon gold"><i class="bi bi-clock-history"></i></div>
            <div><div class="stat-num"><?= formatMoney($totalPendiente) ?></div><div class="stat-label">Por cobrar</div></div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-receipt"></i></div>
            <div><div class="stat-num"><?= count($pagos) ?></div><div class="stat-label">Transacciones</div></div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="admin-card mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-sm-4">
            <label class="form-label">Estado</label>
            <select name="estado" class="form-select form-select-sm">
                <option value="">Todos</option>
                <option value="pendiente"   <?= $filtroEstado==='pendiente'  ?'selected':'' ?>>Pendiente</option>
                <option value="pagado"      <?= $filtroEstado==='pagado'     ?'selected':'' ?>>Pagado</option>
                <option value="reembolsado" <?= $filtroEstado==='reembolsado'?'selected':'' ?>>Reembolsado</option>
            </select>
        </div>
        <div class="col-sm-4">
            <label class="form-label">Fecha de cita</label>
            <input type="date" name="fecha" class="form-control form-control-sm" value="<?= e($filtroFecha) ?>">
        </div>
        <div class="col-sm-4 d-flex gap-2">
            <button type="submit" class="btn btn-gold btn-sm flex-fill">Filtrar</button>
            <a href="/Barberia/admin/pagos.php" class="btn btn-outline-gold btn-sm"><i class="bi bi-x-lg"></i></a>
        </div>
    </form>
</div>

<!-- Tabla -->
<div class="admin-table-wrap">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>#Cita</th><th>Cliente</th><th>Servicio</th><th>Barbero</th><th>Fecha</th><th>Monto</th><th>Método</th><th>Estado</th><th></th></tr>
            </thead>
            <tbody>
                <?php if (empty($pagos)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No hay pagos registrados.</td></tr>
                <?php endif; ?>
                <?php foreach ($pagos as $p): ?>
                <tr>
                    <td class="text-muted">#<?= $p['cita_id'] ?></td>
                    <td><?= e($p['cl_nombre'].' '.$p['cl_apellido']) ?></td>
                    <td><?= e($p['servicio']) ?></td>
                    <td><?= e($p['ba_nombre'].' '.$p['ba_apellido']) ?></td>
                    <td><?= formatFecha($p['fecha']) ?></td>
                    <td class="text-gold fw-semibold"><?= formatMoney($p['monto']) ?></td>
                    <td><?= e(ucfirst($p['metodo'])) ?></td>
                    <td>
                        <?php
                        $colors = ['pagado'=>'success','pendiente'=>'warning','reembolsado'=>'secondary'];
                        $color  = $colors[$p['estado']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?= $color ?>"><?= ucfirst($p['estado']) ?></span>
                    </td>
                    <td>
                        <?php if ($p['estado'] === 'pendiente'): ?>
                        <button class="btn btn-sm btn-gold" data-bs-toggle="modal"
                                data-bs-target="#modalPago"
                                onclick="setPago(<?= $p['id'] ?>, '<?= formatMoney($p['monto']) ?>')">
                            Cobrar
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal cobrar -->
<div class="modal fade" id="modalPago" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar Cobro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="pagar_id" id="pagar_id">
                <div class="modal-body">
                    <p class="text-muted small mb-3">Monto: <strong id="pago_monto" class="text-gold"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">Método de pago</label>
                        <select name="metodo" class="form-select">
                            <option value="efectivo">Efectivo</option>
                            <option value="tarjeta">Tarjeta</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Referencia (opcional)</label>
                        <input type="text" name="referencia" class="form-control" placeholder="N° transacción…">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-gold btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-gold btn-sm"><i class="bi bi-check-lg me-1"></i>Confirmar cobro</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer_admin.php'; ?>
<script>
function setPago(id, monto) {
    document.getElementById('pagar_id').value = id;
    document.getElementById('pago_monto').textContent = monto;
}
</script>
