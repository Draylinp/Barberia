<?php
/**
 * Perfil del cliente
 * Barbería Premium — cliente/perfil.php
 */
$titulo = 'Mi Perfil';
require_once __DIR__ . '/../includes/header_cliente.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $seccion = sanitizeStr($_POST['seccion'] ?? '');

    if ($seccion === 'datos') {
        $nombre   = sanitizeStr($_POST['nombre']   ?? '');
        $apellido = sanitizeStr($_POST['apellido'] ?? '');
        $telefono = sanitizeStr($_POST['telefono'] ?? '');
        if ($nombre && $apellido) {
            $db->prepare('UPDATE usuarios SET nombre=:n,apellido=:a,telefono=:t WHERE id=:id')
               ->execute([':n'=>$nombre,':a'=>$apellido,':t'=>$telefono,':id'=>$_SESSION['usuario_id']]);
            $_SESSION['nombre']   = $nombre;
            $_SESSION['apellido'] = $apellido;
            setFlash('success', 'Datos actualizados correctamente.');
        } else {
            setFlash('error', 'Nombre y apellido son obligatorios.');
        }
    }

    if ($seccion === 'password') {
        $actual  = $_POST['password_actual'] ?? '';
        $nueva   = $_POST['password_nueva']  ?? '';
        $confirm = $_POST['password_confirm']?? '';

        $u = $db->prepare('SELECT password_hash FROM usuarios WHERE id=:id');
        $u->execute([':id'=>$_SESSION['usuario_id']]);
        $hash = $u->fetchColumn();

        if (!password_verify($actual, $hash)) {
            setFlash('error', 'La contraseña actual no es correcta.');
        } elseif (strlen($nueva) < 8) {
            setFlash('error', 'La nueva contraseña debe tener al menos 8 caracteres.');
        } elseif ($nueva !== $confirm) {
            setFlash('error', 'Las contraseñas nuevas no coinciden.');
        } else {
            $newHash = password_hash($nueva, PASSWORD_BCRYPT, ['cost'=>12]);
            $db->prepare('UPDATE usuarios SET password_hash=:h WHERE id=:id')
               ->execute([':h'=>$newHash,':id'=>$_SESSION['usuario_id']]);
            setFlash('success', 'Contraseña cambiada exitosamente.');
        }
    }

    header('Location: /Barberia/cliente/perfil.php'); exit;
}

$u = $db->prepare('SELECT * FROM usuarios WHERE id=:id');
$u->execute([':id'=>$_SESSION['usuario_id']]);
$perfil = $u->fetch();
?>

<div class="row g-4">
    <!-- Datos personales -->
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="admin-card-title"><i class="bi bi-person-circle"></i>Datos personales</div>
            <form method="POST" class="needs-validation" novalidate>
                <?= csrfField() ?>
                <input type="hidden" name="seccion" value="datos">
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre" class="form-control"
                               value="<?= e($perfil['nombre']) ?>" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Apellido</label>
                        <input type="text" name="apellido" class="form-control"
                               value="<?= e($perfil['apellido']) ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Correo (no editable)</label>
                        <input type="email" class="form-control" value="<?= e($perfil['email']) ?>" disabled>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Teléfono</label>
                        <input type="tel" name="telefono" class="form-control"
                               value="<?= e($perfil['telefono'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-gold btn-sm">
                            <i class="bi bi-save me-2"></i>Guardar cambios
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Cambiar contraseña -->
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="admin-card-title"><i class="bi bi-lock"></i>Cambiar contraseña</div>
            <form method="POST" class="needs-validation" novalidate>
                <?= csrfField() ?>
                <input type="hidden" name="seccion" value="password">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Contraseña actual</label>
                        <input type="password" name="password_actual" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Nueva contraseña</label>
                        <input type="password" name="password_nueva" class="form-control" required minlength="8">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Confirmar nueva contraseña</label>
                        <input type="password" name="password_confirm" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-outline-gold btn-sm">
                            <i class="bi bi-shield-lock me-2"></i>Cambiar contraseña
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer_admin.php'; ?>
