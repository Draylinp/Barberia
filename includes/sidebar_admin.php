<?php
/**
 * Sidebar del panel administrador
 * Barbería Premium — includes/sidebar_admin.php
 */
$paginaAdmin = basename($_SERVER['PHP_SELF']);
$usuario     = usuarioActual();
?>
<aside class="admin-sidebar" id="adminSidebar">
    <!-- Logo / Marca -->
    <div class="sidebar-brand">
        <span class="brand-icon"><i class="bi bi-scissors"></i></span>
        <span class="brand-text">Barbería<br><small>Elite</small></span>
    </div>

    <!-- Perfil rápido -->
    <div class="sidebar-profile">
        <div class="avatar-circle">
            <?= strtoupper(substr($usuario['nombre'], 0, 1)) ?>
        </div>
        <div>
            <div class="fw-semibold small"><?= e($usuario['nombre'] . ' ' . $usuario['apellido']) ?></div>
            <div class="badge bg-gold text-dark" style="font-size:.65rem">Administrador</div>
        </div>
    </div>

    <hr class="sidebar-hr">

    <!-- Menú principal -->
    <ul class="sidebar-nav">
        <li class="nav-label">Principal</li>

        <li class="<?= $paginaAdmin === 'index.php' ? 'active' : '' ?>">
            <a href="/Barberia/admin/index.php">
                <i class="bi bi-speedometer2"></i><span>Dashboard</span>
            </a>
        </li>

        <li class="nav-label">Gestión</li>

        <li class="<?= $paginaAdmin === 'barberos.php' ? 'active' : '' ?>">
            <a href="/Barberia/admin/barberos.php">
                <i class="bi bi-person-badge"></i><span>Barberos</span>
            </a>
        </li>
        <li class="<?= $paginaAdmin === 'clientes.php' ? 'active' : '' ?>">
            <a href="/Barberia/admin/clientes.php">
                <i class="bi bi-people"></i><span>Clientes</span>
            </a>
        </li>
        <li class="<?= $paginaAdmin === 'servicios.php' ? 'active' : '' ?>">
            <a href="/Barberia/admin/servicios.php">
                <i class="bi bi-scissors"></i><span>Servicios</span>
            </a>
        </li>
        <li class="<?= $paginaAdmin === 'citas.php' ? 'active' : '' ?>">
            <a href="/Barberia/admin/citas.php">
                <i class="bi bi-calendar-check"></i><span>Citas</span>
            </a>
        </li>
        <li class="<?= $paginaAdmin === 'pagos.php' ? 'active' : '' ?>">
            <a href="/Barberia/admin/pagos.php">
                <i class="bi bi-credit-card"></i><span>Pagos</span>
            </a>
        </li>

        <li class="nav-label">Análisis</li>

        <li class="<?= $paginaAdmin === 'reportes.php' ? 'active' : '' ?>">
            <a href="/Barberia/admin/reportes.php">
                <i class="bi bi-bar-chart-line"></i><span>Reportes</span>
            </a>
        </li>

        <li class="nav-label">Sistema</li>

        <li class="<?= $paginaAdmin === 'configuracion.php' ? 'active' : '' ?>">
            <a href="/Barberia/admin/configuracion.php">
                <i class="bi bi-gear"></i><span>Configuración</span>
            </a>
        </li>
    </ul>

    <!-- Pie del sidebar -->
    <div class="sidebar-footer">
        <a href="/Barberia/public/index.php" class="sidebar-footer-link">
            <i class="bi bi-house"></i><span>Ver sitio</span>
        </a>
        <a href="/Barberia/auth/logout.php" class="sidebar-footer-link text-danger">
            <i class="bi bi-box-arrow-right"></i><span>Salir</span>
        </a>
    </div>
</aside>
