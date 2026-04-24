<?php
/**
 * API: Slots disponibles de un barbero para una fecha y servicio
 * GET /api/disponibilidad.php?barbero_id=&servicio_id=&fecha=
 * Barbería Premium — api/disponibilidad.php
 */
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json; charset=utf-8');

$barberoId  = sanitizeInt($_GET['barbero_id']  ?? 0);
$servicioId = sanitizeInt($_GET['servicio_id'] ?? 0);
$fecha      = sanitizeStr($_GET['fecha']        ?? '');

if (!$barberoId || !$servicioId || !$fecha) {
    jsonError('Parámetros incompletos.');
}

if (strtotime($fecha) < strtotime('today')) {
    jsonError('La fecha no puede ser en el pasado.');
}

$db  = getDB();
$cfg = getConfig();

// Duración del servicio
$srv = $db->prepare('SELECT duracion_min FROM servicios WHERE id=:id AND activo=1');
$srv->execute([':id'=>$servicioId]);
$duracion = $srv->fetchColumn();
if (!$duracion) jsonError('Servicio no encontrado.', 404);

// Horario de la barbería
$apertura = $cfg['hora_apertura'] ?? '09:00:00';
$cierre   = $cfg['hora_cierre']   ?? '20:00:00';

// Generar slots de 30 minutos a partir de la apertura
$slots    = [];
$inicio   = strtotime("$fecha $apertura");
$fin      = strtotime("$fecha $cierre");
$durSeg   = $duracion * 60;
$intervalo= 30 * 60;

$ahora = time();

for ($ts = $inicio; $ts + $durSeg <= $fin; $ts += $intervalo) {
    $horaInicio = date('H:i:s', $ts);
    $horaFin    = date('H:i:s', $ts + $durSeg);

    // No mostrar slots en el pasado (para hoy)
    if ($fecha === date('Y-m-d') && $ts <= $ahora + 1800) continue;

    $disponible = barberoDisponible($barberoId, $fecha, $horaInicio, $horaFin);

    $slots[] = [
        'hora'        => $horaInicio,
        'hora_display'=> date('g:i A', $ts),
        'hora_fin'    => $horaFin,
        'disponible'  => $disponible,
    ];
}

jsonOk(['slots' => $slots, 'duracion_min' => $duracion]);
