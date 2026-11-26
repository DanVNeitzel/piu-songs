<?php
require_once __DIR__ . '/storage.php';

/** Export only public files; never copy the application/configuration directory. */
function piu_export_static(string $baseDir, array $result, string $mediaBase): array
{
    if (!filter_var($mediaBase, FILTER_VALIDATE_URL) || !in_array(parse_url($mediaBase, PHP_URL_SCHEME), ['https', 'http'], true)
        || parse_url($mediaBase, PHP_URL_USER) !== null || parse_url($mediaBase, PHP_URL_PASS) !== null
        || parse_url($mediaBase, PHP_URL_QUERY) !== null || parse_url($mediaBase, PHP_URL_FRAGMENT) !== null) {
        throw new InvalidArgumentException('Informe uma URL pública HTTP(S) de mídia, sem credenciais, query ou fragmento.');
    }
    $mediaBase = rtrim($mediaBase, '/') . '/';
    $output = piu_safe_path($baseDir, '_site');
    // Refuse links and foreign files in the public output, even on a repeated build.
    $allowed = ['index.html', 'regenerate_songs.html', '.nojekyll', 'library-public.json', 'songs.json',
        'assets', 'assets/catalog.css', 'assets/catalog.js', 'assets/static-catalog.js'];
    if (is_link($output)) throw new RuntimeException('O destino de exportação não pode ser um link.');
    if (is_dir($output)) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($output, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($files as $file) {
            $relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($file->getPathname(), strlen($output) + 1));
            if ($file->isLink() || !in_array($relative, $allowed, true)) throw new RuntimeException('Arquivo não reconhecido no destino público: ' . $relative);
        }
    }
    if (!is_dir($output . '/assets') && !mkdir($output . '/assets', 0755, true)) throw new RuntimeException('Falha ao criar pasta pública.');
    $source = dirname(__DIR__);
    foreach (['index.html', 'assets/catalog.css', 'assets/catalog.js', 'assets/static-catalog.js'] as $file) {
        if (!copy($source . '/' . $file, $output . '/' . $file)) throw new RuntimeException('Falha ao exportar ' . $file);
    }
    // Old bookmarks also open the public library, without admin controls.
    if (!copy($source . '/index.html', $output . '/regenerate_songs.html')) throw new RuntimeException('Falha ao exportar página alternativa.');
    if (file_put_contents($output . '/.nojekyll', '') === false) throw new RuntimeException('Falha ao criar .nojekyll.');
    $snapshot = ['version' => 1, 'published' => true, 'generated_at' => gmdate('Y-m-d\TH:i:s\Z'),
        'media_base_url' => $mediaBase, 'partial' => false, 'library' => $result['library'], 'stats' => $result['stats'],
        'issues' => $result['issues'], 'changes' => ['added' => [], 'removed' => [], 'changed' => []]];
    piu_atomic_json($output . '/library-public.json', $snapshot);
    $catalog = $result['catalog'];
    $rewrite = function (&$node) use (&$rewrite, $mediaBase): void {
        foreach (['path', 'banner'] as $key) {
            if (isset($node[$key]) && str_starts_with($node[$key], './Songs/')) {
                $node[$key] = $mediaBase . implode('/', array_map('rawurlencode', explode('/', substr($node[$key], 2))));
            }
        }
        foreach ($node['children'] ?? [] as $i => $_) $rewrite($node['children'][$i]);
    };
    $rewrite($catalog);
    piu_atomic_json($output . '/songs.json', $catalog);
    return ['directory' => $output, 'songs' => count($snapshot['library'])];
}
