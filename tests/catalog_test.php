<?php
require_once dirname(__DIR__) . '/app/catalog.php';
require_once dirname(__DIR__) . '/app/filesystem.php';
require_once dirname(__DIR__) . '/app/static_export.php';

function check(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
function removeFixture(string $path): void {
    foreach (new FilesystemIterator($path) as $item) {
        if ($item->isDir() && !$item->isLink()) removeFixture($item->getPathname());
        else unlink($item->getPathname());
    }
    rmdir($path);
}
$fixture = sys_get_temp_dir() . '/piu-test-' . bin2hex(random_bytes(8));
mkdir($fixture);
try {
    check(piu_matches_pattern('*.php', 'blocked.PHP'), 'Case-sensitive disallowed extension bypass');
    mkdir($fixture . '/Songs/Test/音楽 !@#$%& [1]', 0700, true);
    $song = $fixture . '/Songs/Test/音楽 !@#$%& [1]';
    $base = ['children' => [['children' => [], 'name' => 'Songs', 'path' => './Songs', 'type' => 'folder']], 'name' => 'root', 'path' => './', 'type' => 'folder'];
    piu_atomic_json($fixture . '/base_songs.json', $base);
    piu_atomic_json($fixture . '/channels.json', ['channels' => [['id' => 'all', 'folder' => 'ALL', 'rule' => 'all'], ['id' => 'test', 'folder' => 'Test', 'rule' => 'series']]]);
    $ssc = "#TITLE:Special;\n#ARTIST:Artist;\n#SONGCATEGORY:K-POP;\n#MUSIC:audio !.mp3;\n#BANNER:missing.png;\n#NOTEDATA:;\n#STEPSTYPE:pump-single;\n#METER:12;\n#NOTES:\n10000\n00000\n;\n#NOTEDATA:;\n#STEPSTYPE:pump-double;\n#METER:28;\n#NOTES:\n1000000000\n;";
    file_put_contents($song . '/chart.ssc', $ssc);
    file_put_contents($song . '/audio !.mp3', 'fixture');
    // An uploaded PHP file must never execute during cataloguing.
    file_put_contents($fixture . '/Songs/Test/index.php', '<?php throw new Exception("Executed category PHP");');
    mkdir($fixture . '/Songs/Test/Incomplete');
    file_put_contents($fixture . '/Songs/Test/Incomplete/broken.ssc', '#TITLE:Broken;#NOTEDATA:;#STEPSTYPE:pump-single;#METER:bad;');
    $preview = piu_catalog_run($fixture);
    file_put_contents($fixture . '/files_config.json', '{"SENHA":"private-fixture"}');
    $export = piu_export_static($fixture, $preview, 'https://raw.githubusercontent.com/owner/repo/sha/');
    check(is_file($export['directory'] . '/index.html'), 'Missing static entry');
    check(!is_file($export['directory'] . '/files_config.json') && !is_dir($export['directory'] . '/app') && !is_file($export['directory'] . '/api_generate_songs.php'), 'Private/server files leaked into public output');
    $public = json_decode(file_get_contents($export['directory'] . '/library-public.json'), true, 512, JSON_THROW_ON_ERROR);
    check(count($public['library']) === 2 && $public['published'] === true, 'Missing static metadata');
    $publicCatalog = json_decode(file_get_contents($export['directory'] . '/songs.json'), true, 512, JSON_THROW_ON_ERROR);
    check(str_starts_with($publicCatalog['children'][0]['children'][0]['children'][0]['path'], 'https://raw.githubusercontent.com/owner/repo/sha/Songs/'), 'Exported resource URL is not external');
    check(!str_contains(file_get_contents($export['directory'] . '/index.html'), '.php'), 'Public page contains PHP controls');
    foreach (['javascript:alert(1)', 'https://user:secret@example.com/', 'https://example.com/?token=secret'] as $invalidBase) {
        try { piu_export_static($fixture, $preview, $invalidBase); throw new RuntimeException('Unsafe media URL accepted'); }
        catch (InvalidArgumentException $expected) {}
    }
    piu_export_static($fixture, $preview, 'https://example.com/media/');
    file_put_contents($export['directory'] . '/unexpected-secret.json', 'private');
    try { piu_export_static($fixture, $preview, 'https://example.com/media/'); throw new RuntimeException('Foreign output files were accepted'); }
    catch (RuntimeException $error) { check(str_contains($error->getMessage(), 'não reconhecido'), 'Wrong foreign-output error'); }
    check(!is_file($fixture . '/songs.json'), 'Preview wrote songs.json');
    check($preview['stats']['total_songs'] === 2, 'Songs counted more than once through virtual channels');
    check(count($preview['library']) === 2, 'Missing library rows');
    $special = array_values(array_filter($preview['library'], fn($song) => $song['title'] === 'Special'))[0];
    check(count($special['charts']) === 2 && $special['charts'][1]['level'] === 28, 'Incorrect chart metadata');
    check(count($special['channels']) === 2, 'Missing virtual membership');
    $codes = array_column($preview['issues'], 'code');
    check(in_array('missing_audio', $codes) && in_array('missing_reference', $codes) && in_array('invalid_ssc', $codes), 'Missing validation findings');
    $again = piu_catalog_run($fixture);
    check($again['stats']['cache_hits'] === 2, 'Unchanged SSC not reused');
    check($preview['fingerprint'] === $again['fingerprint'], 'Unstable preview fingerprint');
    $published = piu_catalog_run($fixture, true, false, $again['fingerprint']);
    $old = file_get_contents($fixture . '/songs.json');
    check(str_contains($old, '音楽 !@#$%& [1]'), 'Special names changed');
    // Exercise the real difficulty entry point in an isolated workspace.
    mkdir($fixture . '/app');
    foreach (['storage.php', 'security.php', 'charts.php'] as $file) copy(dirname(__DIR__) . '/app/' . $file, $fixture . '/app/' . $file);
    copy(dirname(__DIR__) . '/generate_difficulty_zone.php', $fixture . '/generate_difficulty_zone.php');
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($fixture . '/generate_difficulty_zone.php') . ' 2>&1', $output, $exit);
    check($exit === 0, 'Difficulty generator failed: ' . implode("\n", $output));
    $single = json_decode(file_get_contents($fixture . '/difficulty-zone/single/12.json'), true, 512, JSON_THROW_ON_ERROR);
    $double = json_decode(file_get_contents($fixture . '/difficulty-zone/double/27-plus.json'), true, 512, JSON_THROW_ON_ERROR);
    check(count($single) === 1 && count($double) === 1, 'Difficulty buckets or virtual deduplication broken');
    json_decode(file_get_contents($fixture . '/difficulty-zone/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
    $forced = piu_catalog_run($fixture, false, true);
    check($forced['stats']['cache_hits'] === 0, 'Force did not bypass cache');
    try { piu_atomic_json($fixture . '/songs.json', ["bad" => "\xFF"]); throw new RuntimeException('Invalid UTF-8 accepted'); }
    catch (JsonException $expected) {}
    check(file_get_contents($fixture . '/songs.json') === $old, 'Encoding failure destroyed old catalog');
    $lock = piu_generation_lock($fixture);
    try {
        try { piu_catalog_run($fixture); throw new RuntimeException('Concurrent generator accepted'); }
        catch (RuntimeException $error) { check($error->getCode() === 409, 'Wrong lock failure'); }
    } finally { piu_unlock($lock); }
    foreach (['../outside', '..\\outside', '/absolute', 'C:\\absolute', 'http://host/file'] as $path) {
        try { piu_safe_path($fixture, $path); throw new RuntimeException('Unsafe path accepted: ' . $path); }
        catch (RuntimeException $error) { check($error->getCode() === 403, $error->getMessage()); }
    }
    check(piu_safe_path($fixture, './Songs/Test/new.txt') === realpath($fixture) . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, ['Songs', 'Test', 'new.txt']), 'Valid destination rejected');
    file_put_contents($song . '/new.png', 'fixture');
    try { piu_catalog_run($fixture, true, false, $forced['fingerprint']); throw new RuntimeException('Stale preview published'); }
    catch (RuntimeException $error) { check($error->getCode() === 409, 'Wrong stale-preview error'); }
    check(file_get_contents($fixture . '/songs.json') === $old, 'Stale preview changed catalog');
    file_put_contents($song . '/chart.ssc', str_replace('#METER:12', '#METER:16', $ssc) . "\n");
    $changed = piu_catalog_run($fixture);
    check($changed['stats']['cache_hits'] === 1, 'Changed SSC was not invalidated');
    check(count($changed['changes']['changed']) === 1, 'Changed file listing not detected');
    echo "PASS: catalog, validation, cache, special names, virtual channels, atomic writes, lock, paths, stale preview\n";
} finally { removeFixture($fixture); }
