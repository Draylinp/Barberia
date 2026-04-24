<?php
/**
 * Funciones de utilidad global
 * Barbería Premium — includes/functions.php
 */

require_once __DIR__ . '/../config/database.php';

// ─── Sesión segura ────────────────────────────────────────────────────────────

function iniciarSesion(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => false,   // true en HTTPS
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

function sesionActiva(): bool {
    return isset($_SESSION['usuario_id'], $_SESSION['rol']) && !empty($_SESSION['rol']);
}

function requireLogin(string $redirect = '/Barberia/auth/login.php'): void {
    if (!sesionActiva()) {
        header('Location: ' . $redirect);
        exit;
    }
}

function requireRol(string ...$roles): void {
    if (!sesionActiva()) {
        // Destruir sesión parcial antes de redirigir al login
        session_unset();
        session_destroy();
        header('Location: /Barberia/auth/login.php');
        exit;
    }
    if (!in_array($_SESSION['rol'], $roles, true)) {
        header('Location: /Barberia/auth/login.php?error=acceso');
        exit;
    }
}

function usuarioActual(): array {
    return [
        'id'       => $_SESSION['usuario_id']  ?? null,
        'nombre'   => $_SESSION['nombre']      ?? '',
        'apellido' => $_SESSION['apellido']    ?? '',
        'email'    => $_SESSION['email']       ?? '',
        'rol'      => $_SESSION['rol']         ?? '',
        'avatar'   => $_SESSION['avatar']      ?? null,
    ];
}

// ─── Sanitización / Escapado ──────────────────────────────────────────────────

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function sanitizeStr(string $val): string {
    return trim(strip_tags($val));
}

function sanitizeInt(mixed $val): int {
    return (int) filter_var($val, FILTER_SANITIZE_NUMBER_INT);
}

function sanitizeEmail(string $val): string {
    return filter_var(trim($val), FILTER_SANITIZE_EMAIL);
}

// ─── Respuestas JSON (para la API) ────────────────────────────────────────────

function jsonOk(array $data = [], string $msg = 'OK'): never {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true, 'message' => $msg, 'data' => $data]);
    exit;
}

function jsonError(string $msg, int $code = 400): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

// ─── Flash messages ───────────────────────────────────────────────────────────

function setFlash(string $tipo, string $mensaje): void {
    iniciarSesion();
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function showFlash(): void {
    $f = getFlash();
    if (!$f) return;
    $tipo = match($f['tipo']) {
        'success' => 'success',
        'error'   => 'danger',
        'warning' => 'warning',
        default   => 'info',
    };
    echo '<div class="alert alert-' . $tipo . ' alert-dismissible fade show" role="alert">'
        . e($f['mensaje'])
        . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>'
        . '</div>';
}

// ─── Configuración de la barbería ─────────────────────────────────────────────

function getConfig(): array {
    static $config = null;
    if ($config === null) {
        $db = getDB();
        $config = $db->query('SELECT * FROM configuracion LIMIT 1')->fetch() ?: [];
    }
    return $config;
}

// ─── Helpers de formato ───────────────────────────────────────────────────────

function formatMoney(float $amount, string $currency = 'USD'): string {
    return '$' . number_format($amount, 2);
}

function formatFecha(string $fecha): string {
    $ts = strtotime($fecha);
    return $ts ? date('d/m/Y', $ts) : $fecha;
}

function formatHora(string $hora): string {
    $ts = strtotime($hora);
    return $ts ? date('g:i A', $ts) : $hora;
}

function badgeEstadoCita(string $estado): string {
    $map = [
        'pendiente'   => 'warning',
        'confirmada'  => 'info',
        'en_proceso'  => 'primary',
        'completada'  => 'success',
        'cancelada'   => 'danger',
    ];
    $color = $map[$estado] ?? 'secondary';
    return '<span class="badge bg-' . $color . '">' . ucfirst(str_replace('_', ' ', e($estado))) . '</span>';
}

// ─── Paginación ───────────────────────────────────────────────────────────────

function paginar(int $total, int $porPagina, int $paginaActual, string $url): string {
    $totalPaginas = (int) ceil($total / $porPagina);
    if ($totalPaginas <= 1) return '';

    $html = '<nav><ul class="pagination pagination-sm mb-0">';
    for ($i = 1; $i <= $totalPaginas; $i++) {
        $active = ($i === $paginaActual) ? ' active' : '';
        $link   = $url . (str_contains($url, '?') ? '&' : '?') . 'pagina=' . $i;
        $html  .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $link . '">' . $i . '</a></li>';
    }
    $html .= '</ul></nav>';
    return $html;
}

// ─── Disponibilidad de barbero ────────────────────────────────────────────────

/**
 * Verifica si un barbero está disponible en la fecha/hora indicada.
 * Retorna true si no hay colisión con citas activas ni bloqueos.
 */
function barberoDisponible(int $barberoId, string $fecha, string $horaInicio, string $horaFin, ?int $excluirCitaId = null): bool {
    $db = getDB();

    // Verificar contra citas existentes
    $sql = 'SELECT COUNT(*) FROM citas
            WHERE barbero_id = :b AND fecha = :f AND estado NOT IN (\'cancelada\')
            AND hora_inicio < :hf AND hora_fin > :hi';
    $params = [':b' => $barberoId, ':f' => $fecha, ':hi' => $horaInicio, ':hf' => $horaFin];
    if ($excluirCitaId) {
        $sql .= ' AND id != :exc';
        $params[':exc'] = $excluirCitaId;
    }
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    if ($stmt->fetchColumn() > 0) return false;

    // Verificar contra horarios bloqueados
    $sql2 = 'SELECT COUNT(*) FROM horarios_bloqueados
              WHERE barbero_id = :b AND fecha = :f
              AND hora_inicio < :hf AND hora_fin > :hi';
    $stmt2 = $db->prepare($sql2);
    $stmt2->execute([':b' => $barberoId, ':f' => $fecha, ':hi' => $horaInicio, ':hf' => $horaFin]);
    return $stmt2->fetchColumn() == 0;
}

// ─── Token CSRF ───────────────────────────────────────────────────────────────

function csrfToken(): string {
    iniciarSesion();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        die('Token CSRF inválido.');
    }
}
