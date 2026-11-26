<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/app/catalog.php';
require_once __DIR__ . '/app/static_export.php';
set_time_limit(600);
ini_set('memory_limit', '512M');
try {
    $options = getopt('', ['media-base:']);
    $mediaBase = $options['media-base'] ?? getenv('PIU_MEDIA_BASE_URL') ?: '';
    if (!is_string($mediaBase) || $mediaBase === '') throw new InvalidArgumentException('Use --media-base=https://servidor/publico/ (raiz que contém Songs/).');
    $result = piu_catalog_run(__DIR__);
    $export = piu_export_static(__DIR__, $result, $mediaBase);
    echo 'Site HTML exportado em ' . $export['directory'] . ' (' . $export['songs'] . ' músicas).' . PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, 'ERRO: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
