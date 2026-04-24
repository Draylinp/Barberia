<?php
/**
 * Login de usuarios (admin, barbero, cliente)
 * Barbería Premium — auth/login.php
 */
require_once __DIR__ . '/../includes/functions.php';
iniciarSesion();

// Destruir sesión incompleta/corrupta para evitar bucle de redirecciones
if (isset($_SESSION['usuario_id']) && empty($_SESSION['rol'])) {
    session_unset();
    session_destroy();
    iniciarSesion();
}

// Si ya está logueado con sesión válida, redirigir según rol
if (sesionActiva()) {
    $destinos = [
        'admin'   => '/Barberia/admin/index.php',
        'barbero' => '/Barberia/barbero/index.php',
        'cliente' => '/Barberia/cliente/index.php',
    ];
    $destino = $destinos[$_SESSION['rol']] ?? '/Barberia/public/index.php';
    header('Location: ' . $destino);
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $email    = sanitizeEmail($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Completa todos los campos.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare('SELECT * FROM usuarios WHERE email = :e AND activo = 1 LIMIT 1');
        $stmt->execute([':e' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Regenerar ID de sesión por seguridad
            session_regenerate_id(true);

            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['nombre']     = $user['nombre'];
            $_SESSION['apellido']   = $user['apellido'];
            $_SESSION['email']      = $user['email'];
            $_SESSION['rol']        = $user['rol'];
            $_SESSION['avatar']     = $user['avatar'];

            // Actualizar último login
            $db->prepare('UPDATE usuarios SET ultimo_login = NOW() WHERE id = :id')
               ->execute([':id' => $user['id']]);

            // Si es barbero, guardar el barbero_id en sesión
            if ($user['rol'] === 'barbero') {
                $b = $db->prepare('SELECT id FROM barberos WHERE usuario_id = :uid');
                $b->execute([':uid' => $user['id']]);
                $_SESSION['barbero_id'] = $b->fetchColumn();
            }
            // Si es cliente
            if ($user['rol'] === 'cliente') {
                $c = $db->prepare('SELECT id FROM clientes WHERE usuario_id = :uid');
                $c->execute([':uid' => $user['id']]);
                $_SESSION['cliente_id'] = $c->fetchColumn();
            }

            $destino = match($user['rol']) {
                'admin'   => '/Barberia/admin/index.php',
                'barbero' => '/Barberia/barbero/index.php',
                default   => '/Barberia/cliente/index.php',
            };
            header('Location: ' . $destino);
            exit;
        } else {
            $error = 'Correo o contraseña incorrectos.';
        }
    }
}

$titulo = 'Iniciar Sesión';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — Barbería Elite</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Barberia/assets/css/style.css">
</head>
<body>
<div class="auth-page">
    <div class="container">
        <div class="auth-card">
            <!-- Logo -->
            <div class="auth-logo">
                <div class="brand-icon mx-auto mb-2" style="width:52px;height:52px;font-size:1.4rem;">
                    <i class="bi bi-scissors"></i>
                </div>
                <h4 class="auth-title">Barbería Elite</h4>
            </div>

            <h5 class="auth-title" style="font-size:1.3rem">Bienvenido de vuelta</h5>
            <p class="auth-subtitle">Ingresa tus credenciales para continuar</p>

            <?php if ($error): ?>
                <div class="alert alert-danger mb-3">
                    <i class="bi bi-exclamation-triangle me-2"></i><?= e($error) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error']) && $_GET['error'] === 'acceso'): ?>
                <div class="alert alert-warning mb-3">
                    <i class="bi bi-lock me-2"></i>No tienes permiso para acceder a esa sección.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['registered'])): ?>
                <div class="alert alert-success mb-3">
                    <i class="bi bi-check-circle me-2"></i>Cuenta creada. Ya puedes iniciar sesión.
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="needs-validation" novalidate>
                <?= csrfField() ?>

                <div class="mb-3">
                    <label class="form-label">Correo electrónico</label>
                    <div class="input-group">
                        <span class="input-group-text" style="background:var(--dark-4);border-color:var(--border);color:var(--text-muted);">
                            <i class="bi bi-envelope"></i>
                        </span>
                        <input type="email" name="email" class="form-control"
                               placeholder="tu@email.com"
                               value="<?= e($_POST['email'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text" style="background:var(--dark-4);border-color:var(--border);color:var(--text-muted);">
                            <i class="bi bi-lock"></i>
                        </span>
                        <input type="password" name="password" id="passInput" class="form-control"
                               placeholder="••••••••" required>
                        <button type="button" class="input-group-text" style="background:var(--dark-4);border-color:var(--border);color:var(--text-muted);cursor:pointer;"
                                onclick="togglePass()">
                            <i class="bi bi-eye" id="passIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-gold w-100 py-2 mb-3">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesión
                </button>

                <p class="text-center text-muted small mb-0">
                    ¿No tienes cuenta?
                    <a href="/Barberia/auth/register.php" class="text-gold">Regístrate gratis</a>
                </p>
                <p class="text-center mt-3">
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
function togglePass() {
    const input = document.getElementById('passInput');
    const icon  = document.getElementById('passIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>
</body>
</html>
