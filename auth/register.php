<?php
/**
 * Registro de nuevos clientes
 * Barbería Premium — auth/register.php
 */
require_once __DIR__ . '/../includes/functions.php';
iniciarSesion();

if (sesionActiva()) { header('Location: /Barberia/cliente/index.php'); exit; }

$error  = '';
$campos = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $nombre   = sanitizeStr($_POST['nombre']   ?? '');
    $apellido = sanitizeStr($_POST['apellido'] ?? '');
    $email    = sanitizeEmail($_POST['email']  ?? '');
    $telefono = sanitizeStr($_POST['telefono'] ?? '');
    $password = $_POST['password']  ?? '';
    $confirm  = $_POST['confirm']   ?? '';

    $campos = compact('nombre', 'apellido', 'email', 'telefono');

    // Validaciones
    if (!$nombre || !$apellido || !$email || !$password) {
        $error = 'Todos los campos obligatorios deben completarse.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico no es válido.';
    } elseif (strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($password !== $confirm) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $db = getDB();

        // Verificar email único
        $check = $db->prepare('SELECT id FROM usuarios WHERE email = :e LIMIT 1');
        $check->execute([':e' => $email]);
        if ($check->fetch()) {
            $error = 'Este correo ya está registrado.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            $db->beginTransaction();
            try {
                // Insertar usuario
                $ins = $db->prepare(
                    'INSERT INTO usuarios (nombre, apellido, email, password_hash, rol, telefono)
                     VALUES (:n, :a, :e, :h, "cliente", :t)'
                );
                $ins->execute([':n' => $nombre, ':a' => $apellido, ':e' => $email, ':h' => $hash, ':t' => $telefono]);
                $uid = $db->lastInsertId();

                // Crear perfil de cliente
                $db->prepare('INSERT INTO clientes (usuario_id) VALUES (:uid)')
                   ->execute([':uid' => $uid]);

                $db->commit();
                header('Location: /Barberia/auth/login.php?registered=1');
                exit;
            } catch (Exception $ex) {
                $db->rollBack();
                $error = 'Error al crear la cuenta. Intenta nuevamente.';
                error_log($ex->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta — Barbería Elite</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Barberia/assets/css/style.css">
</head>
<body>
<div class="auth-page">
    <div class="container">
        <div class="auth-card" style="max-width:480px;">
            <div class="auth-logo">
                <div class="brand-icon mx-auto mb-2" style="width:52px;height:52px;font-size:1.4rem;">
                    <i class="bi bi-scissors"></i>
                </div>
                <h4 class="auth-title">Crear cuenta</h4>
                <p class="auth-subtitle">Regístrate para reservar tus citas fácilmente</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger mb-3">
                    <i class="bi bi-exclamation-triangle me-2"></i><?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="needs-validation" novalidate>
                <?= csrfField() ?>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label">Nombre <span class="text-gold">*</span></label>
                        <input type="text" name="nombre" class="form-control"
                               placeholder="Juan" value="<?= e($campos['nombre'] ?? '') ?>" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Apellido <span class="text-gold">*</span></label>
                        <input type="text" name="apellido" class="form-control"
                               placeholder="Pérez" value="<?= e($campos['apellido'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Correo electrónico <span class="text-gold">*</span></label>
                    <input type="email" name="email" class="form-control"
                           placeholder="tu@email.com" value="<?= e($campos['email'] ?? '') ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Teléfono (opcional)</label>
                    <input type="tel" name="telefono" class="form-control"
                           placeholder="+1 555 000 0000" value="<?= e($campos['telefono'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Contraseña <span class="text-gold">*</span></label>
                    <input type="password" name="password" id="pass1" class="form-control"
                           placeholder="Mínimo 8 caracteres" required minlength="8">
                    <div id="pass-strength" class="mt-1" style="height:4px;border-radius:2px;background:var(--border);"></div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Confirmar contraseña <span class="text-gold">*</span></label>
                    <input type="password" name="confirm" id="pass2" class="form-control"
                           placeholder="Repite tu contraseña" required>
                </div>

                <button type="submit" class="btn btn-gold w-100 py-2 mb-3">
                    <i class="bi bi-person-plus me-2"></i>Crear mi cuenta
                </button>

                <p class="text-center text-muted small mb-0">
                    ¿Ya tienes cuenta?
                    <a href="/Barberia/auth/login.php" class="text-gold">Inicia sesión</a>
                </p>
                <p class="text-center mt-2">
                    <a href="/Barberia/public/index.php" class="text-muted small">
                        <i class="bi bi-arrow-left me-1"></i>Volver al sitio
                    </a>
                </p>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Indicador de fortaleza de contraseña
document.getElementById('pass1').addEventListener('input', function() {
    const bar = document.getElementById('pass-strength');
    const v = this.value;
    let score = 0;
    if (v.length >= 8)  score++;
    if (/[A-Z]/.test(v)) score++;
    if (/[0-9]/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;
    const colors = ['#dc3545','#ffc107','#0dcaf0','#198754'];
    const widths = ['25%','50%','75%','100%'];
    bar.style.width   = widths[score - 1] || '0';
    bar.style.background = colors[score - 1] || 'var(--border)';
    bar.style.transition = 'all .3s';
});
</script>
</body>
</html>
