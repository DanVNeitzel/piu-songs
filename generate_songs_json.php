<?php
require_once __DIR__ . '/app/security.php';
require_once __DIR__ . '/app/catalog.php';
if (PHP_SAPI !== 'cli') {
    piu_require_auth();
    header('Location: regenerate_songs.html');
    exit;
}
set_time_limit(600);
ini_set('memory_limit', '512M');
try {
    $preview = in_array('--preview', $argv, true);
    $result = piu_catalog_run(__DIR__, !$preview, in_array('--force', $argv, true));
    echo ($preview ? 'Prévia concluída' : 'songs.json publicado') . PHP_EOL;
    echo json_encode(['stats' => $result['stats'], 'changes' => $result['changes'], 'issues' => $result['issues']], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, 'ERRO: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
