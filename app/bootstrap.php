<?php
declare(strict_types=1);

const BASE_PATH = __DIR__ . '/..';

function load_env(string $path): void
{
    if (!is_readable($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $value = trim($value);
        if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) $value = substr($value, 1, -1);
        $_ENV[trim($key)] = $value;
    }
}
load_env(BASE_PATH . '/.env');
date_default_timezone_set((string)($_ENV['APP_TIMEZONE'] ?? 'America/Santiago'));
function env_value(string $key, string $default = ''): string { return isset($_ENV[$key]) && $_ENV[$key] !== '' ? (string)$_ENV[$key] : $default; }
function app_name(): string { return env_value('APP_NAME', 'Portal Metalrubber'); }
function app_url(string $path = ''): string { return rtrim(env_value('APP_URL', 'http://127.0.0.1:8088'), '/') . '/' . ltrim($path, '/'); }
function is_https(): bool { return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'); }
function client_ip(): string { return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45); }
function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function redirect(string $path): never { header('Location: ' . (str_starts_with($path, 'http') ? $path : app_url($path))); exit; }
function flash(string $key, ?string $value = null): ?string { if ($value !== null) { $_SESSION['_flash'][$key] = $value; return null; } $message = $_SESSION['_flash'][$key] ?? null; unset($_SESSION['_flash'][$key]); return $message; }
function csrf_token(): string { if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(32)); return $_SESSION['_csrf']; }
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'; }
function verify_csrf(): void { $sessionToken = (string)($_SESSION['_csrf'] ?? ''); $postedToken = (string)($_POST['_csrf'] ?? ''); if ($sessionToken === '' || $postedToken === '' || !hash_equals($sessionToken, $postedToken)) { http_response_code(419); exit('Solicitud no válida.'); } }
function db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) return $pdo;
    $dsn = 'mysql:host=' . env_value('DB_HOST', 'localhost') . ';port=' . env_value('DB_PORT', '3306') . ';dbname=' . env_value('DB_NAME', 'qlccl_portal') . ';charset=utf8mb4';
    $pdo = new PDO($dsn, env_value('DB_USER'), env_value('DB_PASS'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
    return $pdo;
}
function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_name(env_value('SESSION_NAME', 'metalrubber_portal'));
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => is_https(), 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
start_secure_session();
function current_user(): ?array
{
    static $user;
    if ($user !== null) return $user ?: null;
    $id = (int)($_SESSION['user_id'] ?? 0);
    if ($id < 1) { $user = []; return null; }
    $stmt = db()->prepare('SELECT u.*, r.slug role_slug, r.name role_name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=? AND u.active=1');
    $stmt->execute([$id]); $user = $stmt->fetch() ?: [];
    return $user ?: null;
}
function require_login(): array { $user = current_user(); if (!$user) redirect('login.php'); return $user; }
function require_admin(): array { $user = require_login(); if (($user['role_slug'] ?? '') !== 'admin') { http_response_code(403); exit('No tienes permisos para acceder a esta sección.'); } return $user; }
function role_label(string $slug): string { return ['admin'=>'Administrador general','supervisor'=>'Supervisor','operario'=>'Operario','payroll'=>'Usuario de remuneraciones','quotations'=>'Usuario de cotizaciones'][$slug] ?? $slug; }
function audit(string $event, ?int $userId = null, ?string $details = null): void { $stmt = db()->prepare('INSERT INTO audit_logs (user_id,event,details,ip_address) VALUES (?,?,?,?)'); $stmt->execute([$userId, $event, $details, client_ip()]); }
function layout_start(string $title, ?array $user = null): void { $fullTitle = e($title . ' | ' . app_name()); ?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="color-scheme" content="light"><title><?= $fullTitle ?></title><link rel="stylesheet" href="<?= e(app_url('assets/app.css')) ?>"></head><body><?php if ($user): ?><header class="topbar"><a class="brand" href="<?= e(app_url('dashboard.php')) ?>"><img src="<?= e(app_url('assets/logo-metalrubber.png')) ?>" alt="Metalrubber"><span>PORTAL</span></a><nav class="topnav"><a href="<?= e(app_url('dashboard.php')) ?>">Inicio</a><?php if (($user['role_slug'] ?? '') === 'admin'): ?><a href="<?= e(app_url('admin/users.php')) ?>">Usuarios</a><?php endif; ?><a href="https://metalrubber.cl">Sitio corporativo</a><a class="nav-logout" href="<?= e(app_url('logout.php')) ?>">Cerrar sesión</a></nav></header><?php endif; ?><main class="page-shell">
<?php }
function layout_end(): void { ?></main><footer class="footer"><span>METALRUBBER</span><span>Portal central de acceso</span></footer></body></html><?php }
function alert_html(?string $message, string $type = 'error'): string { return $message ? '<div class="alert ' . e($type) . '" role="alert">' . e($message) . '</div>' : ''; }
