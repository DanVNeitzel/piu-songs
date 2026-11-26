<?php
require_once __DIR__ . '/storage.php';

function piu_http_error(int $code, string $message): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function piu_security_config(): array
{
    $path = dirname(__DIR__) . '/files_config.json';
    return is_file($path) ? json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR) : [];
}

function piu_session(): void
{
    if (PHP_SAPI === 'cli' || session_status() === PHP_SESSION_ACTIVE) return;
    session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax', 'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'use_strict_mode' => true]);
    $minutes = (int) (piu_security_config()['configTime'] ?? 5);
    if ($minutes > 0 && isset($_SESSION['LAST_ACTIVITY']) && time() * 1000 - $_SESSION['LAST_ACTIVITY'] > $minutes * 60000) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['LAST_ACTIVITY'] = time() * 1000;
}

function piu_require_auth(): void
{
    if (PHP_SAPI === 'cli') return;
    piu_session();
    $password = (string) (piu_security_config()['SENHA'] ?? 'abc123');
    if ($password !== '' && empty($_SESSION['_sfm_allowed'])) piu_http_error(401, 'Entre no gerenciador para continuar.');
    header('Cache-Control: no-store');
}

function piu_csrf_token(): string
{
    piu_session();
    if (empty($_SESSION['piu_csrf'])) $_SESSION['piu_csrf'] = bin2hex(random_bytes(32));
    // Existing manager JavaScript reads this cookie.
    setcookie('_sfm_xsrf', $_SESSION['piu_csrf'], ['path' => '/', 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
    $_COOKIE['_sfm_xsrf'] = $_SESSION['piu_csrf'];
    return $_SESSION['piu_csrf'];
}

function piu_require_post(): void
{
    if (PHP_SAPI === 'cli') return;
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') piu_http_error(405, 'Esta operação exige POST.');
    piu_session();
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['xsrf'] ?? '';
    if (!is_string($token) || empty($_SESSION['piu_csrf']) || !hash_equals($_SESSION['piu_csrf'], $token)) piu_http_error(403, 'Token de segurança inválido. Recarregue a página.');
}
