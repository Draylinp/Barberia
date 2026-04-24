<?php
/**
 * Footer del sitio público
 * Barbería Premium — includes/footer_public.php
 */
$config = getConfig();
?>

<!-- ═══ FOOTER ═══ -->
<footer class="site-footer">
    <div class="container">
        <div class="row gy-4">
            <!-- Marca -->
            <div class="col-lg-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="brand-icon"><i class="bi bi-scissors"></i></span>
                    <span class="brand-text fs-5"><?= e($config['nombre'] ?? 'Barbería Elite') ?></span>
                </div>
                <p class="text-muted small"><?= e($config['slogan'] ?? 'El arte del buen corte') ?></p>
                <div class="social-links d-flex gap-3 mt-3">
                    <a href="#"><i class="bi bi-instagram"></i></a>
                    <a href="#"><i class="bi bi-facebook"></i></a>
                    <a href="#"><i class="bi bi-tiktok"></i></a>
                    <a href="#"><i class="bi bi-whatsapp"></i></a>
                </div>
            </div>

            <!-- Links rápidos -->
            <div class="col-6 col-lg-2">
                <h6 class="footer-title">Navegación</h6>
                <ul class="footer-links">
                    <li><a href="/Barberia/public/index.php">Inicio</a></li>
                    <li><a href="/Barberia/public/servicios.php">Servicios</a></li>
                    <li><a href="/Barberia/public/galeria.php">Galería</a></li>
                    <li><a href="/Barberia/public/contacto.php">Contacto</a></li>
                    <li><a href="/Barberia/public/reservar.php">Reservar cita</a></li>
                </ul>
            </div>

            <!-- Horarios -->
            <div class="col-6 col-lg-3">
                <h6 class="footer-title">Horarios</h6>
                <ul class="footer-links">
                    <li><span class="text-muted">Lun – Vie</span> &nbsp; 9:00 AM – 8:00 PM</li>
                    <li><span class="text-muted">Sábado</span> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 9:00 AM – 6:00 PM</li>
                    <li><span class="text-muted">Domingo</span> &nbsp;&nbsp;&nbsp;&nbsp; Cerrado</li>
                </ul>
            </div>

            <!-- Contacto -->
            <div class="col-lg-3">
                <h6 class="footer-title">Contacto</h6>
                <ul class="footer-links">
                    <?php if ($config['telefono'] ?? false): ?>
                        <li><i class="bi bi-telephone me-2 text-gold"></i><?= e($config['telefono']) ?></li>
                    <?php endif; ?>
                    <?php if ($config['email'] ?? false): ?>
                        <li><i class="bi bi-envelope me-2 text-gold"></i><?= e($config['email']) ?></li>
                    <?php endif; ?>
                    <?php if ($config['direccion'] ?? false): ?>
                        <li><i class="bi bi-geo-alt me-2 text-gold"></i><?= e($config['direccion']) ?></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <hr class="footer-hr">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
            <p class="text-muted small mb-0">
                &copy; <?= date('Y') ?> <?= e($config['nombre'] ?? 'Barbería Elite') ?>. Todos los derechos reservados.
            </p>
            <p class="text-muted small mb-0">
                <a href="/Barberia/auth/login.php" class="text-muted">Acceso interno</a>
            </p>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- JS Principal -->
<script src="/Barberia/assets/js/main.js"></script>
</body>
</html>
