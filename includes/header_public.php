<?php
/**
 * Header / Navbar del sitio público
 * Barbería Premium — includes/header_public.php
 */
require_once __DIR__ . '/functions.php';
iniciarSesion();
$config  = getConfig();
$usuario = sesionActiva() ? usuarioActual() : null;
$pagina  = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($config['nombre'] ?? 'Barbería Elite') ?> — <?= $titulo ?? 'Inicio' ?></title>

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <!-- CSS Principal -->
    <link rel="stylesheet" href="/Barberia/assets/css/style.css">
</head>
<body>

<!-- ═══ NAVBAR ═══ -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top" id="mainNav">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="/Barberia/public/index.php">
            <span class="brand-icon"><i class="bi bi-scissors"></i></span>
            <span class="brand-text"><?= e($config['nombre'] ?? 'Barbería Elite') ?></span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav mx-auto gap-1">
                <li class="nav-item">
                    <a class="nav-link <?= $pagina === 'index.php' ? 'active' : '' ?>" href="/Barberia/public/index.php">Inicio</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $pagina === 'servicios.php' ? 'active' : '' ?>" href="/Barberia/public/servicios.php">Servicios</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $pagina === 'galeria.php' ? 'active' : '' ?>" href="/Barberia/public/galeria.php">Galería</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $pagina === 'contacto.php' ? 'active' : '' ?>" href="/Barberia/public/contacto.php">Contacto</a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <?php if ($usuario): ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-gold btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i><?= e($usuario['nombre']) ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
                            <?php if ($usuario['rol'] === 'admin'): ?>
                                <li><a class="dropdown-item" href="/Barberia/admin/index.php"><i class="bi bi-speedometer2 me-2"></i>Panel Admin</a></li>
                            <?php elseif ($usuario['rol'] === 'barbero'): ?>
                                <li><a class="dropdown-item" href="/Barberia/barbero/index.php"><i class="bi bi-calendar-check me-2"></i>Mi Agenda</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item" href="/Barberia/cliente/index.php"><i class="bi bi-house me-2"></i>Mi Panel</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="/Barberia/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Salir</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="/Barberia/auth/login.php" class="btn btn-outline-gold btn-sm">
                        <i class="bi bi-person me-1"></i>Ingresar
                    </a>
                    <a href="/Barberia/public/reservar.php" class="btn btn-gold btn-sm">
                        <i class="bi bi-calendar-plus me-1"></i>Reservar
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
