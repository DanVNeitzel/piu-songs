<?php
declare(strict_types=1);

/** Write beside the destination, then replace it without exposing partial JSON. */
function piu_atomic_json(string $path, $data): void
{
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    $temp = tempnam(dirname($path), '.piu-');
    if ($temp === false) throw new RuntimeException('Não foi possível criar o arquivo temporário.');
    try {
        if (file_put_contents($temp, $json, LOCK_EX) !== strlen($json)) {
            throw new RuntimeException('Falha ao gravar JSON: ' . basename($path));
        }
        json_decode(file_get_contents($temp), true, 512, JSON_THROW_ON_ERROR);
        // Never unlink the old destination: a failed replacement must preserve it.
        if (!rename($temp, $path)) throw new RuntimeException('Falha ao substituir: ' . basename($path));
    } finally {
        if (is_file($temp)) unlink($temp);
    }
}

function piu_generation_lock(string $baseDir)
{
    $dir = $baseDir . '/.runtime';
    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) throw new RuntimeException('Falha ao criar diretório de trabalho.');
    if (!is_file($dir . '/.htaccess')) file_put_contents($dir . '/.htaccess', "Require all denied\n");
    $handle = fopen($dir . '/generation.lock', 'c');
    if (!$handle) throw new RuntimeException('Falha ao abrir bloqueio de geração.');
    if (!flock($handle, LOCK_EX | LOCK_NB)) {
        fclose($handle);
        throw new RuntimeException('Já existe uma geração em andamento.', 409);
    }
    return $handle;
}

function piu_unlock($handle): void
{
    if (is_resource($handle)) { flock($handle, LOCK_UN); fclose($handle); }
}

/** Resolve existing ancestors too, so symlinks cannot escape the project. */
function piu_safe_path(string $base, string $relative): string
{
    $base = realpath($base);
    if ($base === false || str_contains($relative, "\0") || preg_match('~^(?:[/\\\\]|[a-zA-Z]:)|://~', $relative)) {
        throw new RuntimeException('Caminho inválido.', 403);
    }
    $parts = [];
    foreach (explode('/', str_replace('\\', '/', $relative)) as $part) {
        if ($part === '' || $part === '.') continue;
        if ($part === '..') {
            if (!$parts) throw new RuntimeException('Caminho fora da biblioteca.', 403);
            array_pop($parts);
        } else $parts[] = $part;
    }
    $candidate = $base . ($parts ? DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts) : '');
    $ancestor = $candidate;
    while (!file_exists($ancestor) && !is_link($ancestor) && dirname($ancestor) !== $ancestor) $ancestor = dirname($ancestor);
    $resolved = realpath($ancestor);
    $prefix = $base . DIRECTORY_SEPARATOR;
    $inside = $resolved !== false && ($resolved === $base || (DIRECTORY_SEPARATOR === '\\'
        ? strncasecmp($resolved, $prefix, strlen($prefix)) === 0
        : strncmp($resolved, $prefix, strlen($prefix)) === 0));
    if (!$inside) throw new RuntimeException('Caminho fora da biblioteca.', 403);
    return $candidate;
}
