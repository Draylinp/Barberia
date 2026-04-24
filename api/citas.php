<?php
/**
 * API: Operaciones sobre citas (autenticado)
 * POST /api/citas.php  { accion, cita_id, ... }
 * Barbería Premium — api/citas.php
 */
require_once __DIR__ . '/../includes/functions.php';
iniciarSesion();
header('Content-Type: application/json; charset=utf-8');

if (!sesionActiva()) jsonError('No autenticado.', 401);

$db     = getDB();
$accion = sanitizeStr($_POST['accion'] ?? $_GET['accion'] ?? '');

// ── Citas del día de un barbero (para el calendario) ─────────────────────────
if ($accion === 'agenda' && in_array($_SESSION['rol'], ['barbero','admin'])) {
    $barberoId = sanitizeInt($_GET['barbero_id'] ?? $_SESSION['barbero_id'] ?? 0);
    $fecha     = sanitizeStr($_GET['fecha']      ?? date('Y-m-d'));

    $stmt = $db->prepare(
        'SELECT c.id, c.hora_inicio, c.hora_fin, c.estado,
                s.nombre AS servicio,
                u.nombre AS cliente
         FROM citas c
         JOIN servicios s ON c.servicio_id = s.id
         JOIN clientes cl ON c.cliente_id  = cl.id
         JOIN usuarios u  ON cl.usuario_id = u.id
         WHERE c.barbero_id = :b AND c.fecha = :f AND c.estado != "cancelada"
         ORDER BY c.hora_inicio'
    );
    $stmt->execute([':b'=>$barberoId, ':f'=>$fecha]);
    jsonOk(['citas' => $stmt->fetchAll()]);
}

// ── Cancelar cita (cliente) ───────────────────────────────────────────────────
if ($accion === 'cancelar' && $_SESSION['rol'] === 'cliente') {
    $citaId    = sanitizeInt($_POST['cita_id']   ?? 0);
    $clienteId = $_SESSION['cliente_id']          ?? 0;
    $cfg       = getConfig();
    $minCan    = (int)($cfg['min_cancelacion']   ?? 60);

    $c = $db->prepare('SELECT fecha, hora_inicio, estado FROM citas WHERE id=:id AND cliente_id=:cl');
    $c->execute([':id'=>$citaId, ':cl'=>$clienteId]);
    $cita = $c->fetch();

    if (!$cita) jsonError('Cita no encontrada.', 404);
    if (!in_array($cita['estado'], ['pendiente','confirmada'])) jsonError('No se puede cancelar en este estado.');

    $minutos = (strtotime($cita['fecha'].' '.$cita['hora_inicio']) - time()) / 60;
    if ($minutos < $minCan) jsonError("Debes cancelar con al menos {$minCan} minutos de anticipación.");

    $db->prepare('UPDATE citas SET estado="cancelada" WHERE id=:id')->execute([':id'=>$citaId]);
    jsonOk([], 'Cita cancelada.');
}

jsonError('Acción no reconocida.', 400);
