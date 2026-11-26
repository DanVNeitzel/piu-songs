<?php
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/charts.php';
require_once dirname(__DIR__) . '/channel_catalog.php';

class PiuCatalog
{
    private array $cache = [];
    private array $nextCache = [];
    private array $issues = [];
    private int $hits = 0;
    public function __construct(private string $base, private bool $force = false) {}

    private function issue(string $code, string $path, string $message): void
    {
        $this->issues[] = compact('code', 'path', 'message');
    }

    private function metadata(string $relative): array
    {
        $absolute = piu_safe_path($this->base, $relative);
        $signature = filemtime($absolute) . ':' . filesize($absolute) . ':' . filectime($absolute);
        $cached = $this->cache[$relative] ?? null;
        if (!$this->force && $cached && $cached['signature'] === $signature) {
            $data = $cached['data'];
            $this->hits++;
        } else $data = piu_chart_metadata($absolute);
        $this->nextCache[$relative] = ['signature' => $signature, 'data' => $data];
        return $data;
    }

    public function scan(): array
    {
        clearstatcache();
        $cacheFile = $this->base . '/.runtime/metadata.json';
        if (is_file($cacheFile)) {
            try {
                $cached = json_decode(file_get_contents($cacheFile), true, 512, JSON_THROW_ON_ERROR);
                if (($cached['version'] ?? null) === 1) $this->cache = $cached['files'] ?? [];
            } catch (Throwable $error) { /* A cache failure only triggers a rescan. */ }
        }
        $songsRoot = $this->base . '/Songs';
        if (!is_dir($songsRoot)) throw new RuntimeException('Pasta Songs não encontrada.');
        $baseFile = $this->base . '/base_songs.json';
        $catalog = json_decode(file_get_contents($baseFile), true, 512, JSON_THROW_ON_ERROR);
        if (($catalog['children'][0]['name'] ?? '') !== 'Songs') throw new RuntimeException('Estrutura inválida em base_songs.json.');
        $stages = [];
        $library = [];
        $metadata = [];
        $identities = [];
        $extensions = ['mp3', 'wav', 'ogg', 'ssc', 'sm', 'webp', 'png', 'jpg', 'jpeg', 'gif', 'avif', 'bmp'];
        $images = ['webp', 'png', 'jpg', 'jpeg', 'gif', 'avif', 'bmp'];
        foreach ($this->entries($songsRoot) as $category) {
            $directory = $songsRoot . '/' . $category;
            if (!is_dir($directory) || is_link($directory)) continue;
            $stage = ['children' => [], 'name' => $category, 'path' => './Songs/' . $category, 'type' => 'folder'];
            foreach ($this->entries($directory) as $name) {
                $folder = $directory . '/' . $name;
                if (is_link($folder)) continue;
                if (is_file($folder)) {
                    if (in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), $images, true) && !isset($stage['banner'])) $stage['banner'] = $stage['path'] . '/' . $name;
                    continue;
                }
                if (!is_dir($folder)) continue;
                $path = $stage['path'] . '/' . $name;
                $song = ['children' => [], 'name' => $name, 'path' => $path, 'type' => 'folder'];
                $audio = []; $stepfiles = []; $charts = []; $title = ''; $artist = ''; $banner = null;
                $issueStart = count($this->issues);
                foreach ($this->entries($folder) as $file) {
                    $absolute = $folder . '/' . $file;
                    if (is_link($absolute)) continue;
                    if (is_dir($absolute)) { $this->issue('nested_folder', $path . '/' . $file, 'Subpasta não incluída no catálogo.'); continue; }
                    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if (!in_array($extension, $extensions, true)) continue;
                    $relative = $path . '/' . $file;
                    $song['children'][] = ['name' => $file, 'path' => $relative, 'type' => 'file'];
                    if (in_array($extension, ['mp3', 'wav', 'ogg'], true)) $audio[] = $relative;
                    if (in_array($extension, $images, true) && $banner === null) $banner = $relative;
                    if ($extension === 'sm') { $stepfiles[] = $relative; $this->issue('sm_only_metadata', $relative, 'SM catalogado; metadados e dificuldades são extraídos de SSC.'); }
                    if ($extension !== 'ssc') continue;
                    $stepfiles[] = $relative;
                    $data = $this->metadata($relative);
                    $metadata[$relative] = $data;
                    $title = $title ?: $data['title']; $artist = $artist ?: $data['artist'];
                    $charts = array_merge($charts, $data['charts']);
                    foreach ($data['issues'] as $message) $this->issue('invalid_ssc', $relative, $message);
                    foreach (['MUSIC', 'BANNER', 'BACKGROUND', 'CDTITLE', 'JACKET'] as $tag) {
                        $reference = $data['tags'][$tag] ?? '';
                        if ($reference === '') continue;
                        try { $target = piu_safe_path($this->base, substr($folder, strlen($this->base) + 1) . '/' . $reference); $exists = is_file($target); }
                        catch (Throwable $error) { $exists = false; }
                        if (!$exists) $this->issue('missing_reference', $relative, $tag . ': referência ausente ou fora da biblioteca: ' . $reference);
                    }
                }
                if (!$audio) $this->issue('missing_audio', $path, 'Música sem áudio MP3, WAV ou OGG.');
                if (!$stepfiles) $this->issue('missing_chart', $path, 'Música sem SSC ou SM.');
                $identity = strtolower(trim($title) . '|' . trim($artist));
                if ($title !== '' && isset($identities[$identity])) $this->issue('duplicate', $path, 'Mesmo título e artista de ' . $identities[$identity]);
                elseif ($title !== '') $identities[$identity] = $path;
                $library[] = ['name' => $name, 'title' => $title ?: $name, 'artist' => $artist, 'category' => $category,
                    'path' => $path, 'banner' => $banner, 'audio' => $audio, 'charts' => $charts,
                    'issues' => array_slice($this->issues, $issueStart)];
                if ($song['children']) $stage['children'][] = $song;
            }
            $stages[] = $stage;
        }
        $catalog['children'][0]['children'] = $stages;
        $catalog = applyChannelLayout($catalog, $metadata, $this->base);
        $memberships = [];
        foreach ($catalog['children'][0]['children'] as $channel) foreach ($channel['children'] as $song) $memberships[$song['path']][] = $channel['name'];
        foreach ($library as &$song) $song['channels'] = $memberships[$song['path']] ?? [];
        unset($song);
        $previous = [];
        $oldFile = $this->base . '/songs.json';
        if (is_file($oldFile)) {
            $old = json_decode(file_get_contents($oldFile), true, 512, JSON_THROW_ON_ERROR);
            foreach ($old['children'][0]['children'] ?? [] as $channel) foreach ($channel['children'] ?? [] as $song) $previous[$song['path']] = $song;
        }
        $current = [];
        foreach ($stages as $stage) foreach ($stage['children'] as $song) $current[$song['path']] = $song;
        $changes = ['added' => array_keys(array_diff_key($current, $previous)), 'removed' => array_keys(array_diff_key($previous, $current)), 'changed' => []];
        foreach (array_intersect_key($current, $previous) as $path => $song) if ($song != $previous[$path]) $changes['changed'][] = $path;
        return ['catalog' => $catalog, 'library' => $library, 'metadata' => $metadata, 'issues' => $this->issues, 'changes' => $changes,
            'cache' => ['version' => 1, 'files' => $this->nextCache],
            'stats' => ['processed' => count($stages), 'skipped' => 0, 'total_categories' => count($catalog['children'][0]['children']), 'total_songs' => count($current), 'cache_hits' => $this->hits, 'issues' => count($this->issues)]];
    }

    private function entries(string $directory): array
    {
        $entries = scandir($directory);
        if ($entries === false) throw new RuntimeException('Falha ao escanear ' . $directory);
        return array_values(array_filter($entries, static fn($name) => $name[0] !== '.'));
    }
}

function piu_catalog_run(string $base, bool $publish = false, bool $force = false, ?string $expected = null): array
{
    $lock = piu_generation_lock($base);
    try {
        $result = (new PiuCatalog($base, $force))->scan();
        $result['fingerprint'] = hash('sha256', json_encode([$result['catalog'], $result['library'], $result['changes']], JSON_THROW_ON_ERROR));
        if ($publish && $expected !== null && !hash_equals($expected, $result['fingerprint'])) {
            throw new RuntimeException('A biblioteca mudou desde a prévia. Atualize a prévia antes de publicar.', 409);
        }
        piu_atomic_json($base . '/.runtime/metadata.json', $result['cache']);
        if ($publish) {
            if (!$result['stats']['total_songs']) throw new RuntimeException('Catálogo vazio: publicação cancelada para preservar o catálogo atual.');
            piu_atomic_json($base . '/songs.json', $result['catalog']);
        }
        $size = strlen(json_encode($result['catalog'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $result['stats']['file_size'] = number_format($size / 1024, 1) . ' KB';
        unset($result['cache'], $result['metadata']);
        $result['published'] = $publish;
        return $result;
    } finally { piu_unlock($lock); }
}
