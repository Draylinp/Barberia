<?php
/**
 * Formulario de contacto
 * Barbería Premium — public/contacto.php
 */
require_once __DIR__ . '/../includes/functions.php';
$titulo = 'Contacto';
$config = getConfig();
$ok = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $nombre  = sanitizeStr($_POST['nombre']  ?? '');
    $email   = sanitizeEmail($_POST['email'] ?? '');
    $telefono= sanitizeStr($_POST['telefono']?? '');
    $mensaje = sanitizeStr($_POST['mensaje'] ?? '');

    if (!$nombre || !$email || !$mensaje) {
        $error = 'Completa los campos obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo no es válido.';
    } elseif (strlen($mensaje) < 10) {
        $error = 'El mensaje es demasiado corto.';
    } else {
        $db = getDB();
        $db->prepare(
            'INSERT INTO contactos (nombre, email, telefono, mensaje) VALUES (:n,:e,:t,:m)'
        )->execute([':n'=>$nombre, ':e'=>$email, ':t'=>$telefono, ':m'=>$mensaje]);
        $ok = true;
    }
}

require_once __DIR__ . '/../includes/header_public.php';
?>

<div style="padding:130px 0 60px;background:linear-gradient(135deg,var(--dark-1),var(--dark-2));border-bottom:1px solid var(--border);">
    <div class="container text-center">
        <span class="hero-badge"><i class="bi bi-envelope"></i> Contacto</span>
        <h1 class="section-title mt-3">¿Tienes alguna pregunta?</h1>
        <div class="gold-divider mb-3"></div>
        <p class="text-muted">Escríbenos y te respondemos a la brevedad</p>
    </div>
</div>

<section class="section-pad">
    <div class="container">
        <div class="row g-5">
            <!-- Info de contacto -->
            <div class="col-lg-4">
                <h4 class="mb-4" style="font-family:var(--font-serif);">Información de contacto</h4>

                <div class="contact-info-item">
                    <div class="contact-icon"><i class="bi bi-telephone-fill"></i></div>
                    <div class="contact-info-text">
                        <strong>Teléfono</strong>
                        <span><?= e($config['telefono'] ?? '+1 (555) 000-0000') ?></span>
                    </div>
                </div>

                <div class="contact-info-item">
                    <div class="contact-icon"><i class="bi bi-envelope-fill"></i></div>
                    <div class="contact-info-text">
                        <strong>Correo electrónico</strong>
                        <span><?= e($config['email'] ?? 'info@barberiaelite.com') ?></span>
                    </div>
                </div>

                <div class="contact-info-item">
                    <div class="contact-icon"><i class="bi bi-geo-alt-fill"></i></div>
                    <div class="contact-info-text">
                        <strong>Dirección</strong>
                        <span><?= e(($config['direccion'] ?? '') . ', ' . ($config['ciudad'] ?? '')) ?></span>
                    </div>
                </div>

                <div class="contact-info-item">
                    <div class="contact-icon"><i class="bi bi-clock-fill"></i></div>
                    <div class="contact-info-text">
                        <strong>Horario de atención</strong>
                        <span>Lun–Vie: 9AM–8PM<br>Sábado: 9AM–6PM</span>
                    </div>
                </div>

                <div class="social-links d-flex gap-3 mt-4">
                    <a href="#"><i class="bi bi-instagram fs-4"></i></a>
                    <a href="#"><i class="bi bi-facebook fs-4"></i></a>
                    <a href="#"><i class="bi bi-tiktok fs-4"></i></a>
                    <a href="#"><i class="bi bi-whatsapp fs-4"></i></a>
                </div>
            </div>

            <!-- Formulario -->
            <div class="col-lg-8">
                <?php if ($ok): ?>
                    <div class="text-center py-5">
                        <div style="width:80px;height:80px;background:rgba(34,197,94,.1);border:2px solid #4ade80;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;">
                            <i class="bi bi-check-lg text-success" style="font-size:2rem;"></i>
                        </div>
                        <h4>¡Mensaje enviado!</h4>
                        <p class="text-muted mb-4">Gracias por contactarnos. Te responderemos pronto.</p>
                        <a href="/Barberia/public/index.php" class="btn btn-outline-gold">Volver al inicio</a>
                    </div>
                <?php else: ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger mb-4"><i class="bi bi-exclamation-triangle me-2"></i><?= e($error) ?></div>
                    <?php endif; ?>

                    <div class="form-card">
                        <form method="POST" action="" class="needs-validation" novalidate>
                            <?= csrfField() ?>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nombre completo <span class="text-gold">*</span></label>
                                    <input type="text" name="nombre" class="form-control"
                                           placeholder="Tu nombre" value="<?= e($_POST['nombre'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Correo electrónico <span class="text-gold">*</span></label>
                                    <input type="email" name="email" class="form-control"
                                           placeholder="tu@email.com" value="<?= e($_POST['email'] ?? '') ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Teléfono (opcional)</label>
                                <input type="tel" name="telefono" class="form-control"
                                       placeholder="+1 555 000 0000" value="<?= e($_POST['telefono'] ?? '') ?>">
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Mensaje <span class="text-gold">*</span></label>
                                <textarea name="mensaje" class="form-control" rows="5"
                                          placeholder="¿En qué podemos ayudarte?" required
                                          minlength="10"><?= e($_POST['mensaje'] ?? '') ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-gold px-5 py-2">
                                <i class="bi bi-send me-2"></i>Enviar mensaje
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer_public.php'; ?>
