<?php
/**
 * Página de servicios
 * Barbería Premium — public/servicios.php
 */
require_once __DIR__ . '/../includes/functions.php';
$titulo   = 'Servicios';
$db       = getDB();
$servicios = $db->query('SELECT * FROM servicios WHERE activo = 1 ORDER BY precio ASC')->fetchAll();
require_once __DIR__ . '/../includes/header_public.php';
?>

<!-- Page Header -->
<div style="padding:130px 0 60px;background:linear-gradient(135deg,var(--dark-1),var(--dark-2));border-bottom:1px solid var(--border);">
    <div class="container text-center">
        <span class="hero-badge"><i class="bi bi-scissors"></i> Catálogo</span>
        <h1 class="section-title mt-3">Nuestros Servicios</h1>
        <div class="gold-divider mb-3"></div>
        <p class="text-muted">Calidad premium en cada servicio</p>
    </div>
</div>

<section class="section-pad">
    <div class="container">
        <div class="row g-4">
            <?php foreach ($servicios as $s): ?>
            <div class="col-md-6 col-lg-4" data-aos>
                <div class="service-card h-100">
                    <div class="service-icon">
                        <?php
                        $iconos = ['Corte'=>'bi-scissors','Barba'=>'bi-person','Afeitado'=>'bi-brush','Tratamiento'=>'bi-droplet','Masaje'=>'bi-hand-index'];
                        $icon = 'bi-scissors';
                        foreach ($iconos as $key => $ic) {
                            if (stripos($s['nombre'], $key) !== false) { $icon = $ic; break; }
                        }
                        ?>
                        <i class="bi <?= $icon ?>"></i>
                    </div>
                    <h5 class="service-name"><?= e($s['nombre']) ?></h5>
                    <p class="service-desc"><?= e($s['descripcion']) ?></p>
                    <div class="service-meta">
                        <span class="service-price"><?= formatMoney($s['precio']) ?></span>
                        <span class="service-duration"><i class="bi bi-clock me-1"></i><?= (int)$s['duracion_min'] ?> min</span>
                    </div>
                    <a href="/Barberia/public/reservar.php?servicio_id=<?= $s['id'] ?>"
                       class="btn btn-outline-gold w-100 mt-3 btn-sm">
                        <i class="bi bi-calendar-plus me-1"></i>Reservar este servicio
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- CTA -->
        <div class="text-center mt-5 p-5" style="background:var(--dark-2);border:1px solid var(--border);border-radius:16px;">
            <h4 class="mb-2">¿No encuentras lo que buscas?</h4>
            <p class="text-muted mb-4">Contáctanos y con gusto te atendemos.</p>
            <a href="/Barberia/public/contacto.php" class="btn btn-outline-gold me-3">
                <i class="bi bi-envelope me-2"></i>Contactarnos
            </a>
            <a href="/Barberia/public/reservar.php" class="btn btn-gold">
                <i class="bi bi-calendar-plus me-2"></i>Reservar cita
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer_public.php'; ?>
