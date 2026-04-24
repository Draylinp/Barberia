<?php
/**
 * Header del panel administrativo
 * Barbería Premium — includes/header_admin.php
 */
require_once __DIR__ . '/functions.php';
iniciarSesion();          // MUST be called before requireRol to populate $_SESSION
requireRol('admin');
$config  = getConfig();
$usuario = usuarioActual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — <?= e($titulo ?? 'Panel') ?> | <?= e($config['nombre'] ?? 'Barbería') ?></title>

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <!-- CSS Admin -->
    <link rel="stylesheet" href="/Barberia/assets/css/admin.css">
</head>
<body class="admin-body">

<!-- Topbar mobile -->
<div class="admin-topbar d-lg-none">
    <button class="btn btn-sm btn-dark" id="sidebarToggle">
        <i class="bi bi-list fs-5"></i>
    </button>
    <span class="fw-semibold"><?= e($titulo ?? 'Panel Admin') ?></span>
    <div class="d-flex align-items-center gap-2">
        <span class="small text-muted"><?= e($usuario['nombre']) ?></span>
        <a href="/Barberia/auth/logout.php" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
</div>

<!-- Layout principal -->
<div class="admin-layout">

<?php require_once __DIR__ . '/sidebar_admin.php'; ?>

    <!-- Overlay mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Contenido principal -->
    <main class="admin-main">
        <!-- Topbar escritorio -->
        <div class="admin-header d-none d-lg-flex">
            <div>
                <h5 class="mb-0 fw-semibold"><?= e($titulo ?? 'Dashboard') ?></h5>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 small">
                        <li class="breadcrumb-item"><a href="/Barberia/admin/index.php">Admin</a></li>
                        <?php if (isset($breadcrumb)): ?>
                            <li class="breadcrumb-item active"><?= e($breadcrumb) ?></li>
                        <?php endif; ?>
                    </ol>
                </nav>
            </div>
            <div class="d-flex align-items-center gap-3">
                <!-- Notificación de mensajes no leídos -->
                <?php
                $db = getDB();
                $mensajesNuevos = $db->query('SELECT COUNT(*) FROM contactos WHERE leido = 0')->fetchColumn();
                ?>
                <?php if ($mensajesNuevos > 0): ?>
                <a href="/Barberia/admin/configuracion.php" class="btn btn-sm btn-outline-warning position-relative">
                    <i class="bi bi-envelope"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?= $mensajesNuevos ?>
                    </span>
                </a>
                <?php endif; ?>

                <div class="dropdown">
                    <button class="btn btn-dark btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                        <div class="avatar-circle-sm d-inline-flex me-1">
                            <?= strtoupper(substr($usuario['nombre'], 0, 1)) ?>
                        </div>
                        <?= e($usuario['nombre']) ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
                        <li><span class="dropdown-item-text small text-muted"><?= e($usuario['email']) ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/Barberia/public/index.php"><i class="bi bi-house me-2"></i>Ver sitio</a></li>
                        <li><a class="dropdown-item text-danger" href="/Barberia/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Salir</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Contenido de la página -->
        <div class="admin-content">
            <?php showFlash(); ?>
