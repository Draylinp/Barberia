<?php
/**
 * Header del panel de cliente
 * Barbería Premium — includes/header_cliente.php
 */
require_once __DIR__ . '/functions.php';
iniciarSesion();
requireRol('cliente');
$config  = getConfig();
$usuario = usuarioActual();
$paginaCliente = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Panel — <?= e($titulo ?? '') ?> | <?= e($config['nombre'] ?? 'Barbería') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Barberia/assets/css/admin.css">
</head>
<body class="admin-body">

<div class="admin-topbar d-lg-none">
    <button class="btn btn-sm btn-dark" id="sidebarToggle"><i class="bi bi-list fs-5"></i></button>
    <span class="fw-semibold"><?= e($titulo ?? 'Mi Panel') ?></span>
    <a href="/Barberia/auth/logout.php" class="btn btn-sm btn-outline-danger"><i class="bi bi-box-arrow-right"></i></a>
</div>

<div class="admin-layout">

<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-brand">
        <span class="brand-icon"><i class="bi bi-scissors"></i></span>
        <span class="brand-text">Barbería<br><small>Mi Cuenta</small></span>
    </div>
    <div class="sidebar-profile">
        <div class="avatar-circle"><?= strtoupper(substr($usuario['nombre'],0,1)) ?></div>
        <div>
            <div class="fw-semibold small"><?= e($usuario['nombre'].' '.$usuario['apellido']) ?></div>
            <div class="badge" style="background:rgba(201,162,39,.2);color:var(--gold);font-size:.65rem;">Cliente</div>
        </div>
    </div>
    <hr class="sidebar-hr">
    <ul class="sidebar-nav">
        <li class="nav-label">Mi cuenta</li>
        <li class="<?= $paginaCliente==='index.php'?'active':'' ?>">
            <a href="/Barberia/cliente/index.php"><i class="bi bi-house"></i><span>Inicio</span></a>
        </li>
        <li class="<?= $paginaCliente==='citas.php'?'active':'' ?>">
            <a href="/Barberia/cliente/citas.php"><i class="bi bi-calendar-check"></i><span>Mis citas</span></a>
        </li>
        <li class="<?= $paginaCliente==='reservar.php'?'active':'' ?>">
            <a href="/Barberia/public/reservar.php"><i class="bi bi-calendar-plus"></i><span>Nueva cita</span></a>
        </li>
        <li class="<?= $paginaCliente==='perfil.php'?'active':'' ?>">
            <a href="/Barberia/cliente/perfil.php"><i class="bi bi-person-circle"></i><span>Mi perfil</span></a>
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
        <h5 class="mb-0"><?= e($titulo ?? 'Panel') ?></h5>
        <div class="d-flex gap-2 align-items-center">
            <a href="/Barberia/public/reservar.php" class="btn btn-gold btn-sm">
                <i class="bi bi-calendar-plus me-1"></i>Reservar cita
            </a>
            <a href="/Barberia/auth/logout.php" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-box-arrow-right me-1"></i>Salir
            </a>
        </div>
    </div>
    <div class="admin-content">
        <?php showFlash(); ?>
