<?php
/**
 * Página de inicio — Sitio público
 * Barbería Premium — public/index.php
 */
require_once __DIR__ . '/../includes/functions.php';
$titulo = 'Inicio';

$db       = getDB();
$servicios = $db->query('SELECT * FROM servicios WHERE activo = 1 LIMIT 6')->fetchAll();
$galeria   = $db->query('SELECT * FROM galeria WHERE activa = 1 ORDER BY orden ASC LIMIT 6')->fetchAll();
$barberos  = $db->query(
    'SELECT b.*, u.nombre, u.apellido, u.avatar
     FROM barberos b JOIN usuarios u ON b.usuario_id = u.id
     WHERE b.activo = 1 LIMIT 4'
)->fetchAll();

require_once __DIR__ . '/../includes/header_public.php';
?>

<!-- ═══ HERO ═══ -->
<section class="hero-section" id="inicio">
    <div class="container position-relative" style="z-index:2">
        <div class="row align-items-center min-vh-100 py-5">
            <div class="col-lg-6">
                <div class="hero-badge fade-in-up">
                    <i class="bi bi-star-fill"></i>
                    <span>Barbería Premium desde 2010</span>
                </div>
                <h1 class="hero-title fade-in-up delay-1">
                    El Arte del<br><span>Buen Corte</span>
                </h1>
                <p class="hero-desc fade-in-up delay-2">
                    Más que un corte de cabello — una experiencia. Nuestros barberos expertos combinan técnica clásica y estilo moderno para que luzcas siempre impecable.
                </p>
                <div class="d-flex gap-3 flex-wrap fade-in-up delay-3">
                    <a href="/Barberia/public/reservar.php" class="btn btn-gold btn-lg">
                        <i class="bi bi-calendar-plus me-2"></i>Reservar Cita
                    </a>
                    <a href="/Barberia/public/servicios.php" class="btn btn-outline-gold btn-lg">
                        Ver Servicios
                    </a>
                </div>
                <div class="hero-stats fade-in-up delay-3">
                    <div>
                        <div class="hero-stat-num">5K+</div>
                        <div class="hero-stat-label">Clientes felices</div>
                    </div>
                    <div>
                        <div class="hero-stat-num">15+</div>
                        <div class="hero-stat-label">Años de experiencia</div>
                    </div>
                    <div>
                        <div class="hero-stat-num">4.9</div>
                        <div class="hero-stat-label">Calificación promedio</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══ SERVICIOS ═══ -->
<section class="section-pad" id="servicios" style="background:var(--dark-1);">
    <div class="container">
        <div class="text-center mb-5" data-aos>
            <span class="hero-badge"><i class="bi bi-scissors"></i> Nuestros Servicios</span>
            <h2 class="section-title mt-3">Lo que ofrecemos</h2>
            <div class="gold-divider"></div>
            <p class="section-subtitle mt-3">Servicios de alta calidad con productos premium</p>
        </div>

        <div class="row g-4">
            <?php foreach ($servicios as $s): ?>
            <div class="col-md-6 col-lg-4" data-aos>
                <div class="service-card h-100">
                    <div class="service-icon">
                        <i class="bi bi-scissors"></i>
                    </div>
                    <h5 class="service-name"><?= e($s['nombre']) ?></h5>
                    <p class="service-desc"><?= e($s['descripcion']) ?></p>
                    <div class="service-meta">
                        <span class="service-price"><?= formatMoney($s['precio']) ?></span>
                        <span class="service-duration"><i class="bi bi-clock me-1"></i><?= (int)$s['duracion_min'] ?> min</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-5">
            <a href="/Barberia/public/servicios.php" class="btn btn-outline-gold">
                Ver todos los servicios <i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- ═══ SOBRE NOSOTROS ═══ -->
<section class="section-pad" id="nosotros">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5" data-aos>
                <div class="about-img-wrap">
                    <img src="/Barberia/assets/img/about.jpg"
                         alt="Sobre nosotros"
                         style="border-radius:12px;border:3px solid var(--gold);width:100%;"
                         onerror="this.src='https://placehold.co/500x600/1a1a1a/c9a227?text=Barbería+Elite'">
                    <div class="about-badge">
                        <div class="num">15+</div>
                        <div class="small fw-normal">Años de<br>experiencia</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7" data-aos>
                <span class="hero-badge"><i class="bi bi-info-circle"></i> Sobre Nosotros</span>
                <h2 class="section-title mt-3">Tradición y modernidad<br>en cada corte</h2>
                <div class="gold-divider mb-4" style="margin-left:0;"></div>
                <p class="text-muted mb-3" style="line-height:1.9;">
                    En Barbería Elite combinamos las técnicas clásicas de la barbería tradicional con las tendencias más actuales. Nuestro equipo de barberos certificados está comprometido con brindar una experiencia excepcional en cada visita.
                </p>
                <p class="text-muted mb-4" style="line-height:1.9;">
                    Usamos productos de primera calidad y mantenemos los más altos estándares de higiene y atención al cliente. Cada cliente es tratado como un invitado especial.
                </p>
                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <div style="background:var(--dark-2);border:1px solid var(--border);border-radius:10px;padding:1.1rem;" class="d-flex gap-3 align-items-center">
                            <i class="bi bi-award text-gold fs-3"></i>
                            <div>
                                <div class="fw-semibold small">Certificados</div>
                                <div class="text-muted" style="font-size:.8rem;">Barberos profesionales</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div style="background:var(--dark-2);border:1px solid var(--border);border-radius:10px;padding:1.1rem;" class="d-flex gap-3 align-items-center">
                            <i class="bi bi-shield-check text-gold fs-3"></i>
                            <div>
                                <div class="fw-semibold small">Higiene total</div>
                                <div class="text-muted" style="font-size:.8rem;">Equipo esterilizado</div>
                            </div>
                        </div>
                    </div>
                </div>
                <a href="/Barberia/public/reservar.php" class="btn btn-gold">
                    <i class="bi bi-calendar-plus me-2"></i>Reserva ahora
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ═══ BARBEROS ═══ -->
<?php if (!empty($barberos)): ?>
<section class="section-pad" style="background:var(--dark-1);">
    <div class="container">
        <div class="text-center mb-5" data-aos>
            <span class="hero-badge"><i class="bi bi-person-badge"></i> El Equipo</span>
            <h2 class="section-title mt-3">Nuestros Barberos</h2>
            <div class="gold-divider"></div>
        </div>
        <div class="row g-4 justify-content-center">
            <?php foreach ($barberos as $b): ?>
            <div class="col-sm-6 col-lg-3" data-aos>
                <div class="text-center">
                    <div class="barber-avatar mx-auto mb-3" style="width:110px;height:110px;font-size:2.5rem;">
                        <?php if ($b['foto'] || $b['avatar']): ?>
                            <img src="/Barberia/assets/img/<?= e($b['foto'] ?? $b['avatar']) ?>"
                                 alt="<?= e($b['nombre']) ?>"
                                 style="width:100%;height:100%;object-fit:cover;border-radius:50%;"
                                 onerror="this.style.display='none'">
                        <?php else: ?>
                            <?= strtoupper(substr($b['nombre'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <h5 class="mb-1" style="font-size:1.05rem;"><?= e($b['nombre'] . ' ' . $b['apellido']) ?></h5>
                    <p class="text-gold small mb-2"><?= e($b['especialidad'] ?? 'Barbero Profesional') ?></p>
                    <p class="text-muted small"><?= e($b['bio'] ?? '') ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ═══ GALERÍA ═══ -->
<?php if (!empty($galeria)): ?>
<section class="section-pad">
    <div class="container">
        <div class="text-center mb-5" data-aos>
            <span class="hero-badge"><i class="bi bi-images"></i> Galería</span>
            <h2 class="section-title mt-3">Nuestros Trabajos</h2>
            <div class="gold-divider"></div>
        </div>
        <div class="gallery-grid" data-aos>
            <?php foreach ($galeria as $img): ?>
            <div class="gallery-item">
                <img src="/Barberia/assets/img/galeria/<?= e($img['imagen']) ?>"
                     alt="<?= e($img['titulo'] ?? 'Trabajo') ?>"
                     onerror="this.src='https://placehold.co/400x300/1a1a1a/c9a227?text=Barbería'">
                <div class="gallery-overlay">
                    <div>
                        <div class="fw-semibold"><?= e($img['titulo'] ?? '') ?></div>
                        <div class="text-muted small"><?= e($img['descripcion'] ?? '') ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="/Barberia/public/galeria.php" class="btn btn-outline-gold">Ver galería completa <i class="bi bi-arrow-right ms-2"></i></a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ═══ HORARIOS Y UBICACIÓN ═══ -->
<section class="section-pad" style="background:var(--dark-1);" id="horarios">
    <div class="container">
        <div class="text-center mb-5">
            <span class="hero-badge"><i class="bi bi-clock"></i> Horarios</span>
            <h2 class="section-title mt-3">Horarios de Atención</h2>
            <div class="gold-divider"></div>
        </div>
        <div class="row g-4 align-items-start">
            <div class="col-lg-5">
                <div class="schedule-card">
                    <?php
                    $dias = [
                        'Lunes'     => ['09:00 AM', '08:00 PM'],
                        'Martes'    => ['09:00 AM', '08:00 PM'],
                        'Miércoles' => ['09:00 AM', '08:00 PM'],
                        'Jueves'    => ['09:00 AM', '08:00 PM'],
                        'Viernes'   => ['09:00 AM', '08:00 PM'],
                        'Sábado'    => ['09:00 AM', '06:00 PM'],
                        'Domingo'   => null,
                    ];
                    $hoy = date('l');
                    $hoyes = ['Monday'=>'Lunes','Tuesday'=>'Martes','Wednesday'=>'Miércoles','Thursday'=>'Jueves','Friday'=>'Viernes','Saturday'=>'Sábado','Sunday'=>'Domingo'][$hoy] ?? '';
                    foreach ($dias as $dia => $horario):
                        $esHoy = $dia === $hoyes;
                    ?>
                    <div class="schedule-row <?= $esHoy ? 'today' : '' ?>">
                        <span class="schedule-day">
                            <?= e($dia) ?>
                            <?php if ($esHoy): ?><span class="badge bg-gold text-dark ms-2" style="font-size:.65rem;">Hoy</span><?php endif; ?>
                        </span>
                        <?php if ($horario): ?>
                            <span class="schedule-time"><?= $horario[0] ?> – <?= $horario[1] ?></span>
                        <?php else: ?>
                            <span class="schedule-closed">Cerrado</span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="map-placeholder">
                    <i class="bi bi-geo-alt-fill text-gold" style="font-size:3rem;"></i>
                    <h5 class="mb-1">Encuéntranos</h5>
                    <p class="text-muted small text-center mb-0">
                        <?php $cfg = getConfig(); ?>
                        <?= e($cfg['direccion'] ?? 'Calle Principal 123') ?>,
                        <?= e($cfg['ciudad'] ?? 'Ciudad') ?>
                    </p>
                    <a href="https://maps.google.com" target="_blank" class="btn btn-outline-gold btn-sm mt-3">
                        <i class="bi bi-map me-2"></i>Abrir en Google Maps
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══ CTA FINAL ═══ -->
<section class="section-pad-sm text-center" style="background:linear-gradient(135deg,var(--dark-2),var(--dark-3));border-top:1px solid var(--border);">
    <div class="container">
        <h2 class="section-title mb-3">¿Listo para lucir increíble?</h2>
        <p class="text-muted mb-4">Reserva tu cita ahora y experimenta la diferencia Barbería Elite.</p>
        <a href="/Barberia/public/reservar.php" class="btn btn-gold btn-lg px-5">
            <i class="bi bi-calendar-plus me-2"></i>Reservar mi cita
        </a>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer_public.php'; ?>
