<?php
/**
 * API: Listado de servicios activos
 * GET /api/servicios.php
 * Barbería Premium — api/servicios.php
 */
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json; charset=utf-8');

$db       = getDB();
$servicios= $db->query('SELECT id, nombre, descripcion, precio, duracion_min FROM servicios WHERE activo=1 ORDER BY nombre')->fetchAll();

jsonOk(['servicios' => $servicios]);
