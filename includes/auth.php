<?php
declare(strict_types=1);

/**
 * Autenticación basada en sesiones PHP, control RBAC y protección CSRF.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function has_role(string|array $roles): bool
{
    $user = current_user();
    if (!$user) {
        return false;
    }

    $allowed = is_array($roles) ? $roles : [$roles];
    return in_array($user['rol_nombre'], $allowed, true);
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        header('Location: login.php');
        exit;
    }
    return $user;
}

function require_role(string|array $roles): array
{
    $user = require_login();
    if (!has_role($roles)) {
        http_response_code(403);
        require __DIR__ . '/layout.php';
        render_header('Acceso denegado');
        echo '<div class="card">';
        echo '<h2>403 — Acceso denegado</h2>';
        echo '<p>Tu rol actual (<code>' . e($user['rol_nombre']) . '</code>) no tiene permisos para acceder a esta sección.</p>';
        echo '<p><a class="btn btn-primary" href="index.php">Volver al inicio</a></p>';
        echo '</div>';
        render_footer();
        exit;
    }
    return $user;
}

function attempt_login(PDO $pdo, string $email, string $password): bool
{
    $stmt = $pdo->prepare(
        'SELECT u.id, u.nombre, u.email, u.password_hash, u.activo, r.id AS rol_id, r.nombre AS rol_nombre
         FROM usuarios u
         INNER JOIN roles r ON r.id = u.rol_id
         WHERE u.email = :email
         LIMIT 1'
    );
    $stmt->execute(['email' => trim(mb_strtolower($email))]);
    $user = $stmt->fetch();

    if (!$user || (int) $user['activo'] !== 1) {
        return false;
    }

    if (!password_verify($password, $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'nombre' => $user['nombre'],
        'email' => $user['email'],
        'rol_id' => (int) $user['rol_id'],
        'rol_nombre' => $user['rol_nombre'],
    ];

    return true;
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(400);
        exit('Solicitud inválida (token CSRF incorrecto o expirado).');
    }
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}
