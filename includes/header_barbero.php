<?php
/**
 * Header del panel de barbero
 * Barbería Premium — includes/header_barbero.php
 */
require_once __DIR__ . '/functions.php';
requireRol('barbero', 'admin');
$config  = getConfig();
$usuario = usuarioActual();
$paginaBarbero = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barbero — <?= e($titulo ?? 'Panel') ?> | <?= e($config['nombre'] ?? 'Barbería') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Barberia/assets/css/admin.css">
</head>
<body class="admin-body">

<div class="admin-topbar d-lg-none">
    <button class="btn btn-sm btn-dark" id="sidebarToggle"><i class="bi bi-list fs-5"></i></button>
    <span class="fw-semibold"><?= e($titulo ?? 'Barbero') ?></span>
    <a href="/Barberia/auth/logout.php" class="btn btn-sm btn-outline-danger"><i class="bi bi-box-arrow-right"></i></a>
</div>

<div class="admin-layout">

<!-- Sidebar barbero -->
<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-brand">
        <span class="brand-icon"><i class="bi bi-scissors"></i></span>
        <span class="brand-text">Barbería<br><small>Mi Panel</small></span>
    </div>
    <div class="sidebar-profile">
        <div class="avatar-circle"><?= strtoupper(substr($usuario['nombre'],0,1)) ?></div>
        <div>
            <div class="fw-semibold small"><?= e($usuario['nombre'].' '.$usuario['apellido']) ?></div>
            <div class="badge bg-gold text-dark" style="font-size:.65rem;">Barbero</div>
        </div>
    </div>
    <hr class="sidebar-hr">
    <ul class="sidebar-nav">
        <li class="nav-label">Mi trabajo</li>
        <li class="<?= $paginaBarbero==='index.php'?'active':'' ?>">
            <a href="/Barberia/barbero/index.php"><i class="bi bi-grid"></i><span>Mi agenda</span></a>
        </li>
        <li class="<?= $paginaBarbero==='citas.php'?'active':'' ?>">
            <a href="/Barberia/barbero/citas.php"><i class="bi bi-calendar-check"></i><span>Citas</span></a>
        </li>
        <li class="<?= $paginaBarbero==='horarios.php'?'active':'' ?>">
            <a href="/Barberia/barbero/horarios.php"><i class="bi bi-clock"></i><span>Bloquear horarios</span></a>
        </li>
        <li class="<?= $paginaBarbero==='ingresos.php'?'active':'' ?>">
            <a href="/Barberia/barbero/ingresos.php"><i class="bi bi-wallet2"></i><span>Mis ingresos</span></a>
        </li>
    </ul>
    <div class="sidebar-footer">
        <a href="/Barberia/public/index.php" class="sidebar-footer-link"><i class="bi bi-house"></i><span>Sitio</span></a>
        <a href="/Barberia/auth/logout.php" class="sidebar-footer-link text-danger"><i class="bi bi-box-arrow-right"></i><span>Salir</span></a>
    </div>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<main class="admin-main">
    <div class="admin-header d-none d-lg-flex">
        <h5 class="mb-0 fw-semibold"><?= e($titulo ?? 'Panel') ?></h5>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small"><?= e($usuario['nombre']) ?></span>
            <a href="/Barberia/auth/logout.php" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-box-arrow-right me-1"></i>Salir
            </a>
        </div>
    </div>
    <div class="admin-content">
        <?php showFlash(); ?>
