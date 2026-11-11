<?php
/**
 * PhishHunter · Arranque común: configuración, sesión, BD y utilidades.
 */

define('APP_ROOT', dirname(__DIR__));

require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/includes/routes.php';

if (APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

if (session_status() === PHP_SESSION_NONE) {
    session_name('phishhunter');
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

/* ---------------------------------------------------------------------
 *  Base de datos
 * ------------------------------------------------------------------- */

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            if (PHP_SAPI !== 'cli' && strpos($_SERVER['SCRIPT_NAME'] ?? '', 'install.php') === false) {
                header('Location: ' . url('install.php'));
                exit;
            }
            throw $e;
        }
    }
    return $pdo;
}

function db_all(string $sql, array $params = []): array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function db_one(string $sql, array $params = []): ?array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $row = $st->fetch();
    return $row === false ? null : $row;
}

function db_val(string $sql, array $params = [])
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $v = $st->fetchColumn();
    return $v === false ? null : $v;
}

function db_exec(string $sql, array $params = []): int
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->rowCount();
}

/* ---------------------------------------------------------------------
 *  Rutas y salida
 * ------------------------------------------------------------------- */

/** URL base de la app (funciona en /game, en la raíz o en subcarpetas). */
function base_url(): string
{
    static $base = null;
    if ($base === null) {
        if (PHP_SAPI === 'cli') {
            return $base = '';
        }
        $root      = str_replace('\\', '/', realpath(APP_ROOT));
        $scriptDir = str_replace('\\', '/', realpath(dirname($_SERVER['SCRIPT_FILENAME'])));
        $extra     = substr($scriptDir, strlen($root)); // p. ej. "/admin"
        $webDir    = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        if ($extra !== '' && substr($webDir, -strlen($extra)) === $extra) {
            $webDir = substr($webDir, 0, -strlen($extra));
        }
        $base = rtrim($webDir, '/');
    }
    return $base;
}

function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    if (pretty_urls() && ($pretty = pretty_path($path)) !== null) {
        $path = $pretty;
    }
    return base_url() . '/' . $path;
}

function asset(string $path): string
{
    $file = APP_ROOT . '/assets/' . $path;
    $v = is_file($file) ? filemtime($file) : 1;
    return url('assets/' . $path) . '?v=' . $v;
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . (preg_match('#^https?://#', $path) ? $path : url($path)));
    exit;
}

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/* ---------------------------------------------------------------------
 *  CSRF y mensajes flash
 * ------------------------------------------------------------------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        if (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/api/') !== false) {
            json_response(['ok' => false, 'error' => 'Sesión expirada. Recarga la página.'], 419);
        }
        http_response_code(419);
        exit('La sesión expiró. Vuelve atrás y recarga la página.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/** Celebraciones pendientes (insignias, logros, subida de nivel). */
function queue_celebration(array $c): void
{
    $_SESSION['celebrations'][] = $c;
}

function take_celebrations(): array
{
    $c = $_SESSION['celebrations'] ?? [];
    unset($_SESSION['celebrations']);
    return $c;
}

/* ---------------------------------------------------------------------
 *  Autenticación
 * ------------------------------------------------------------------- */

function current_user(bool $refresh = false): ?array
{
    static $user = null;
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    if ($user === null || $refresh) {
        try {
            $user = db_one('SELECT * FROM users WHERE id = ?', [$_SESSION['user_id']]);
        } catch (PDOException $e) {
            return null; // BD aún no instalada
        }
        if (!$user) {
            unset($_SESSION['user_id']);
        } elseif (isset($user['active']) && !(int) $user['active']) {
            // Cuenta deshabilitada por el súper admin: se cierra la sesión.
            unset($_SESSION['user_id']);
            flash('error', 'Tu cuenta está deshabilitada. Comunícate con el administrador de PhishHunter.');
            $user = null;
        }
    }
    return $user;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        if (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/api/') !== false) {
            json_response(['ok' => false, 'error' => 'Debes iniciar sesión.'], 401);
        }
        flash('info', 'Inicia sesión para continuar tu entrenamiento.');
        redirect('login.php');
    }
    // Contraseña temporal: hay que cambiarla antes de seguir.
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if (!empty($u['must_change_password']) && !in_array($script, ['profile.php', 'logout.php'], true) && strpos($_SERVER['SCRIPT_NAME'] ?? '', '/api/') === false) {
        flash('info', 'Estás usando una contraseña temporal. Crea tu propia contraseña para continuar.');
        redirect('profile.php#cuenta');
    }
    return $u;
}

function require_admin(): array
{
    $u = require_login();
    if (!is_staff($u)) {
        http_response_code(403);
        exit('Acceso solo para docentes.');
    }
    return $u;
}

function require_super(): array
{
    $u = require_login();
    if (!is_super($u)) {
        http_response_code(403);
        exit('Acceso solo para el súper administrador.');
    }
    return $u;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
}

require_once __DIR__ . '/access.php';
require_once __DIR__ . '/game.php';
require_once __DIR__ . '/assessment.php';
require_once __DIR__ . '/arcade.php';
require_once __DIR__ . '/layout.php';

// Direcciones antiguas con «.php» -> URL amigable (solo si el servidor las admite)
redirect_legacy_php();
