<?php
/**
 * CRUD de Citas (Admin)
 * Barbería Premium — admin/citas.php
 */
$titulo     = 'Citas';
$breadcrumb = 'Citas';
require_once __DIR__ . '/../includes/header_admin.php';

$db     = getDB();
$accion = sanitizeStr($_GET['accion'] ?? '');
$id     = sanitizeInt($_GET['id']     ?? 0);

// ── CAMBIAR ESTADO ────────────────────────────────────────────────────────────
if ($accion === 'estado' && $id && isset($_GET['estado'])) {
    verifyCsrf();
    $estado = sanitizeStr($_GET['estado']);
    $validos = ['pendiente','confirmada','en_proceso','completada','cancelada'];
    if (in_array($estado, $validos)) {
        $db->prepare('UPDATE citas SET estado = :e WHERE id = :id')->execute([':e'=>$estado,':id'=>$id]);
        // Si completada, registrar pago si no existe
        if ($estado === 'completada') {
            $exist = $db->prepare('SELECT id FROM pagos WHERE cita_id = :cid');
            $exist->execute([':cid'=>$id]);
            if (!$exist->fetch()) {
                $monto = $db->prepare('SELECT s.precio FROM citas c JOIN servicios s ON c.servicio_id=s.id WHERE c.id=:id');
                $monto->execute([':id'=>$id]);
                $precio = $monto->fetchColumn();
                if ($precio) {
                    $db->prepare('INSERT INTO pagos (cita_id,monto,estado) VALUES (:cid,:m,"pendiente")')
                       ->execute([':cid'=>$id,':m'=>$precio]);
                }
            }
        }
        setFlash('success', 'Estado actualizado.');
    }
    header('Location: /Barberia/admin/citas.php'); exit;
}

// ── ELIMINAR ──────────────────────────────────────────────────────────────────
if ($accion === 'eliminar' && $id) {
    verifyCsrf();
    $db->prepare('UPDATE citas SET estado = "cancelada" WHERE id = :id')->execute([':id'=>$id]);
    setFlash('success', 'Cita cancelada.');
    header('Location: /Barberia/admin/citas.php'); exit;
}

// ── CREAR CITA (admin) ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $clienteId  = sanitizeInt($_POST['cliente_id']  ?? 0);
    $barberoId  = sanitizeInt($_POST['barbero_id']  ?? 0);
    $servicioId = sanitizeInt($_POST['servicio_id'] ?? 0);
    $fecha      = sanitizeStr($_POST['fecha']        ?? '');
    $hora       = sanitizeStr($_POST['hora_inicio']  ?? '');
    $notas      = sanitizeStr($_POST['notas']        ?? '');

    if (!$clienteId || !$barberoId || !$servicioId || !$fecha || !$hora) {
        setFlash('error', 'Todos los campos son obligatorios.');
    } else {
        $srv = $db->prepare('SELECT duracion_min FROM servicios WHERE id=:id');
        $srv->execute([':id'=>$servicioId]);
        $dur = $srv->fetchColumn();
        $horaFin = date('H:i:s', strtotime($hora) + $dur * 60);

        if (!barberoDisponible($barberoId, $fecha, $hora, $horaFin)) {
            setFlash('error', 'El barbero no está disponible en ese horario.');
        } else {
            $db->prepare(
                'INSERT INTO citas (cliente_id,barbero_id,servicio_id,fecha,hora_inicio,hora_fin,notas_cliente,creada_por)
                 VALUES (:cl,:ba,:sv,:fe,:hi,:hf,:no,:cp)'
            )->execute([':cl'=>$clienteId,':ba'=>$barberoId,':sv'=>$servicioId,
                        ':fe'=>$fecha,':hi'=>$hora,':hf'=>$horaFin,':no'=>$notas,':cp'=>$_SESSION['usuario_id']]);
            setFlash('success', 'Cita creada correctamente.');
            header('Location: /Barberia/admin/citas.php'); exit;
        }
    }
}

// ── FILTROS ───────────────────────────────────────────────────────────────────
$filtroFecha   = sanitizeStr($_GET['fecha']   ?? date('Y-m-d'));
$filtroEstado  = sanitizeStr($_GET['estado']  ?? '');
$filtroBarbero = sanitizeInt($_GET['barbero'] ?? 0);

$where  = ['1=1'];
$params = [];
if ($filtroFecha)   { $where[] = 'c.fecha = :f';           $params[':f'] = $filtroFecha; }
if ($filtroEstado)  { $where[] = 'c.estado = :e';           $params[':e'] = $filtroEstado; }
if ($filtroBarbero) { $where[] = 'c.barbero_id = :b';       $params[':b'] = $filtroBarbero; }

$sql = 'SELECT c.*, s.nombre AS servicio, s.precio,
               u.nombre AS cl_nombre, u.apellido AS cl_apellido,
               ub.nombre AS ba_nombre, ub.apellido AS ba_apellido
        FROM citas c
        JOIN servicios s ON c.servicio_id = s.id
        JOIN clientes cl ON c.cliente_id  = cl.id
        JOIN usuarios u  ON cl.usuario_id = u.id
        JOIN barberos b  ON c.barbero_id  = b.id
        JOIN usuarios ub ON b.usuario_id  = ub.id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY c.fecha DESC, c.hora_inicio ASC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$citas = $stmt->fetchAll();

$clientes = $db->query('SELECT cl.id, u.nombre, u.apellido FROM clientes cl JOIN usuarios u ON cl.usuario_id=u.id ORDER BY u.nombre')->fetchAll();
$barberos  = $db->query('SELECT b.id, u.nombre, u.apellido FROM barberos b JOIN usuarios u ON b.usuario_id=u.id WHERE b.activo=1 ORDER BY u.nombre')->fetchAll();
$servicios = $db->query('SELECT * FROM servicios WHERE activo=1 ORDER BY nombre')->fetchAll();
$estados   = ['pendiente','confirmada','en_proceso','completada','cancelada'];
?>

<!-- Filtros -->
<div class="admin-card mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-sm-3">
            <label class="form-label">Fecha</label>
            <input type="date" name="fecha" class="form-control form-control-sm"
                   value="<?= e($filtroFecha) ?>">
        </div>
        <div class="col-sm-3">
            <label class="form-label">Estado</label>
            <select name="estado" class="form-select form-select-sm">
                <option value="">Todos</option>
                <?php foreach ($estados as $est): ?>
                <option value="<?= $est ?>" <?= $filtroEstado === $est ? 'selected' : '' ?>>
                    <?= ucfirst(str_replace('_',' ',$est)) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-3">
            <label class="form-label">Barbero</label>
            <select name="barbero" class="form-select form-select-sm">
                <option value="">Todos</option>
                <?php foreach ($barberos as $b): ?>
                <option value="<?= $b['id'] ?>" <?= $filtroBarbero == $b['id'] ? 'selected' : '' ?>>
                    <?= e($b['nombre'].' '.$b['apellido']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-3 d-flex gap-2">
            <button type="submit" class="btn btn-gold btn-sm flex-fill">
                <i class="bi bi-search me-1"></i>Filtrar
            </button>
            <a href="/Barberia/admin/citas.php" class="btn btn-outline-gold btn-sm">
                <i class="bi bi-x-lg"></i>
            </a>
        </div>
    </form>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="text-muted small"><?= count($citas) ?> cita(s) encontrada(s)</span>
    <button class="btn btn-gold btn-sm" data-bs-toggle="modal" data-bs-target="#modalNuevaCita">
        <i class="bi bi-plus-lg me-2"></i>Nueva Cita
    </button>
</div>

<!-- Tabla citas -->
<div class="admin-table-wrap">
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="tablaCitas">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cliente</th>
                    <th>Servicio</th>
                    <th>Barbero</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($citas)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No hay citas para este filtro.</td></tr>
                <?php endif; ?>
                <?php foreach ($citas as $c): ?>
                <tr>
                    <td class="text-muted">#<?= $c['id'] ?></td>
                    <td><?= e($c['cl_nombre'].' '.$c['cl_apellido']) ?></td>
                    <td>
                        <div><?= e($c['servicio']) ?></div>
                        <div class="text-gold" style="font-size:.75rem;"><?= formatMoney($c['precio']) ?></div>
                    </td>
                    <td><?= e($c['ba_nombre'].' '.$c['ba_apellido']) ?></td>
                    <td><?= formatFecha($c['fecha']) ?></td>
                    <td><?= formatHora($c['hora_inicio']) ?> – <?= formatHora($c['hora_fin']) ?></td>
                    <td><?= badgeEstadoCita($c['estado']) ?></td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-gold dropdown-toggle" data-bs-toggle="dropdown">
                                Acción
                            </button>
                            <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
                                <?php foreach ($estados as $est): if ($est === $c['estado']) continue; ?>
                                <li>
                                    <a class="dropdown-item small"
                                       href="?accion=estado&id=<?= $c['id'] ?>&estado=<?= $est ?>&csrf_token=<?= csrfToken() ?>">
                                        Marcar como <?= str_replace('_',' ', ucfirst($est)) ?>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item small text-danger"
                                       href="?accion=eliminar&id=<?= $c['id'] ?>&csrf_token=<?= csrfToken() ?>"
                                       data-confirm="¿Cancelar esta cita?">
                                        Cancelar cita
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal nueva cita -->
<div class="modal fade" id="modalNuevaCita" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nueva Cita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <?= csrfField() ?>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Cliente <span class="text-gold">*</span></label>
                            <select name="cliente_id" class="form-select" required>
                                <option value="">Selecciona cliente…</option>
                                <?php foreach ($clientes as $cl): ?>
                                <option value="<?= $cl['id'] ?>"><?= e($cl['nombre'].' '.$cl['apellido']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Servicio <span class="text-gold">*</span></label>
                            <select name="servicio_id" id="modal_servicio_id" class="form-select" required>
                                <option value="">Selecciona servicio…</option>
                                <?php foreach ($servicios as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= e($s['nombre']) ?> — <?= formatMoney($s['precio']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Barbero <span class="text-gold">*</span></label>
                            <select name="barbero_id" id="modal_barbero_id" class="form-select" required>
                                <option value="">Selecciona barbero…</option>
                                <?php foreach ($barberos as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= e($b['nombre'].' '.$b['apellido']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha <span class="text-gold">*</span></label>
                            <input type="date" name="fecha" id="modal_fecha" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Hora <span class="text-gold">*</span></label>
                            <select name="hora_inicio" id="modal_hora_inicio" class="form-select" required>
                                <option value="">Selecciona hora…</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notas</label>
                            <textarea name="notas" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-gold" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-gold"><i class="bi bi-calendar-check me-2"></i>Crear Cita</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer_admin.php'; ?>
<?php if (isset($_GET['nueva'])): ?>
<script>window.addEventListener('load', () => new bootstrap.Modal(document.getElementById('modalNuevaCita')).show());</script>
<?php endif; ?>
