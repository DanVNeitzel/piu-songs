<?php
require_once __DIR__ . '/app/security.php';
require_once __DIR__ . '/app/catalog.php';
piu_require_auth();
header('Content-Type: application/json; charset=utf-8');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    echo json_encode(['success' => true, 'xsrf' => piu_csrf_token()]);
    exit;
}
piu_require_post();
session_write_close();
set_time_limit(600);
ini_set('memory_limit', '512M');
try {
    $action = $_POST['action'] ?? 'preview';
    if (!in_array($action, ['preview', 'publish'], true)) piu_http_error(400, 'Ação inválida.');
    if ($action === 'publish' && (!is_string($_POST['fingerprint'] ?? null) || !preg_match('/^[a-f0-9]{64}$/', $_POST['fingerprint']))) piu_http_error(400, 'Gere uma prévia antes de publicar.');
    $result = piu_catalog_run(__DIR__, $action === 'publish', ($_POST['force'] ?? '') === '1', $action === 'publish' ? $_POST['fingerprint'] : null);
    unset($result['catalog']);
    $result['success'] = true;
    $result['log'] = [['time' => date('H:i:s'), 'type' => 'success', 'message' => $result['published'] ? 'Catálogo publicado.' : 'Prévia concluída. O catálogo publicado foi preservado.']];
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
} catch (Throwable $error) {
    error_log((string) $error);
    piu_http_error($error->getCode() === 409 ? 409 : 500, $error->getMessage());
}
