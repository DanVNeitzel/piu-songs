<?php
/**
 * Generates difficulty-zone JSON files from songs.json and .ssc chart data.
 *
 * Output:
 *   difficulty-zone/single/1.json ... 26.json, 27-plus.json
 *   difficulty-zone/double/1.json ... 26.json, 27-plus.json
 *   difficulty-zone/manifest.json
 *
 * Usage:
 *   php generate_difficulty_zone.php
 */

set_time_limit(300);
ini_set('memory_limit', '512M');

const MAX_EXPLICIT_LEVEL = 26;
const PLUS_LEVEL_LABEL = '27+';
const PLUS_LEVEL_FILE = '27-plus.json';

$baseDir = __DIR__;
$sourceFile = $baseDir . DIRECTORY_SEPARATOR . 'songs.json';
$outputDir = $baseDir . DIRECTORY_SEPARATOR . 'difficulty-zone';
$isCli = PHP_SAPI === 'cli';
$debugMode = $isCli
    ? in_array('--debug', $argv ?? [], true)
    : (isset($_GET['debug']) && $_GET['debug'] == '1');

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function outputLine($html = '') {
    global $isCli;

    if ($isCli) {
        echo trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) . PHP_EOL;
        return;
    }

    echo $html . PHP_EOL;
}

function debugLine($html) {
    global $debugMode;

    if ($debugMode) {
        outputLine($html);
    }
}

function projectPath($relativePath) {
    global $baseDir;

    $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
    $path = preg_replace('/^\.' . preg_quote(DIRECTORY_SEPARATOR, '/') . '/', '', $path);
    return $baseDir . DIRECTORY_SEPARATOR . $path;
}

function jsonWrite($path, $data) {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    if ($json === false) {
        throw new RuntimeException('Failed to encode JSON for ' . $path . ': ' . json_last_error_msg());
    }

    if (file_put_contents($path, $json) === false) {
        throw new RuntimeException('Failed to write ' . $path);
    }
}

function emptyBuckets() {
    $buckets = [
        'single' => [],
        'double' => [],
    ];

    foreach (array_keys($buckets) as $mode) {
        for ($level = 1; $level <= MAX_EXPLICIT_LEVEL; $level++) {
            $buckets[$mode][(string) $level] = [];
        }

        $buckets[$mode][PLUS_LEVEL_LABEL] = [];
    }

    return $buckets;
}

function findSongsRoot($data) {
    if (isset($data['children'][0]) && ($data['children'][0]['name'] ?? '') === 'Songs') {
        return $data['children'][0];
    }

    if (($data['name'] ?? '') === 'Songs') {
        return $data;
    }

    throw new RuntimeException('Could not find Songs root inside songs.json');
}

function firstSongBanner($song) {
    $imageExtensions = ['png', 'webp', 'jpg', 'jpeg', 'gif', 'avif', 'bmp'];

    foreach (($song['children'] ?? []) as $child) {
        if (($child['type'] ?? '') !== 'file') {
            continue;
        }

        $extension = strtolower(pathinfo($child['name'] ?? '', PATHINFO_EXTENSION));
        if (in_array($extension, $imageExtensions, true)) {
            return $child['path'] ?? null;
        }
    }

    return null;
}

function songSscFiles($song) {
    $files = [];

    foreach (($song['children'] ?? []) as $child) {
        if (($child['type'] ?? '') !== 'file') {
            continue;
        }

        $extension = strtolower(pathinfo($child['name'] ?? '', PATHINFO_EXTENSION));
        if ($extension === 'ssc') {
            $files[] = $child;
        }
    }

    return $files;
}

function normalizeIdentityPart($value) {
    $value = trim((string) $value);

    if ($value === '') {
        return '';
    }

    if (function_exists('mb_strtolower')) {
        $value = mb_strtolower($value, 'UTF-8');
    } else {
        $value = strtolower($value);
    }

    $normalized = @preg_replace('/[^\p{L}\p{N}]+/u', '', $value);
    if ($normalized !== null && $normalized !== '') {
        return $normalized;
    }

    return preg_replace('/[^a-z0-9]+/i', '', $value) ?? '';
}

function fallbackSongIdentity($name) {
    $name = (string) $name;
    $name = preg_replace('/^\(\d+\)\s*/', '', $name);
    $name = preg_replace('/^[a-z]{0,3}\d+(?:\.\d+)?[a-z]?\s*[- ]\s*/i', '', $name);
    return normalizeIdentityPart($name);
}

function firstSscTagValue($content, $tag) {
    if (preg_match('/#' . preg_quote($tag, '/') . '\s*:\s*([^;]*)\s*;/i', $content, $match)) {
        return trim($match[1]);
    }

    return '';
}

function parseSscFile($path) {
    $content = file_get_contents($path);
    if ($content === false) {
        return [
            'title' => '',
            'artist' => '',
            'charts' => [],
        ];
    }

    $title = firstSscTagValue($content, 'TITLE');
    $artist = firstSscTagValue($content, 'ARTIST');
    $charts = [];
    $sections = preg_split('/#NOTEDATA\s*:\s*;/i', $content);

    if ($sections === false || count($sections) < 2) {
        return [
            'title' => $title,
            'artist' => $artist,
            'charts' => $charts,
        ];
    }

    array_shift($sections);

    foreach ($sections as $section) {
        if (!preg_match('/#STEPSTYPE\s*:\s*([^;]+)\s*;/i', $section, $typeMatch)) {
            continue;
        }

        if (!preg_match('/#METER\s*:\s*(\d+)\s*;/i', $section, $meterMatch)) {
            continue;
        }

        $stepType = strtolower(trim($typeMatch[1]));
        $meter = (int) $meterMatch[1];

        if ($meter < 1) {
            continue;
        }

        if ($stepType === 'pump-single') {
            $mode = 'single';
        } elseif ($stepType === 'pump-double') {
            $mode = 'double';
        } else {
            continue;
        }

        $charts[] = [
            'mode' => $mode,
            'level' => $meter > MAX_EXPLICIT_LEVEL ? PLUS_LEVEL_LABEL : (string) $meter,
        ];
    }

    return [
        'title' => $title,
        'artist' => $artist,
        'charts' => $charts,
    ];
}

function addChartToBuckets(&$buckets, &$seen, $chart, $entry) {
    $mode = $chart['mode'];
    $level = $chart['level'];
    $songIdentity = $entry['songIdentity'] ?? '';
    $key = $mode . '|' . $level . '|' . $songIdentity;

    if (isset($seen[$key])) {
        return false;
    }

    $seen[$key] = true;
    unset($entry['songIdentity']);
    $buckets[$mode][$level][] = $entry;
    return true;
}

function sortBucket(&$items) {
    usort($items, function ($a, $b) {
        $nameCompare = strcasecmp($a['name'] ?? '', $b['name'] ?? '');
        if ($nameCompare !== 0) {
            return $nameCompare;
        }

        $stageCompare = ($a['stageIndex'] ?? 0) <=> ($b['stageIndex'] ?? 0);
        if ($stageCompare !== 0) {
            return $stageCompare;
        }

        $songCompare = ($a['songIndex'] ?? 0) <=> ($b['songIndex'] ?? 0);
        if ($songCompare !== 0) {
            return $songCompare;
        }

        return strcasecmp($a['sscPath'] ?? '', $b['sscPath'] ?? '');
    });
}

try {
    if (!$isCli) {
        echo "<!doctype html><meta charset='utf-8'>";
        echo "<style>body { font-family: monospace; background: #000; color: #0f0; padding: 20px; } a { color: #0ff; } .muted { color: #999; } .ok { color: #0f0; } .warn { color: orange; } .error { color: red; font-weight: bold; } .info { color: #0ff; } .debug { color: #ccc; font-size: 12px; margin-left: 20px; } .debug-deep { color: #888; font-size: 11px; margin-left: 40px; }</style>";
        echo "<body>";
    }

    outputLine("<h1>Gerador de difficulty-zone</h1>");
    outputLine("<p>Escaneando arquivo: <strong>" . e($sourceFile) . "</strong></p>");
    outputLine("<p><em>Lendo songs.json e extraindo niveis dos arquivos .ssc...</em></p>");
    outputLine("<p class='ok'><strong>OK:</strong> Gera single/double de 1 a 26 e agrupa 27+ automaticamente</p>");

    if ($debugMode) {
        outputLine("<p style='background: yellow; color: black; padding: 10px; font-weight: bold;'>MODO DEBUG ATIVADO - Mostrando todos os detalhes</p>");
    } else {
        outputLine("<p class='muted'>Para ver detalhes completos: <a href='?debug=1'>Ativar modo debug</a></p>");
    }

    outputLine("<hr>");

    if (!file_exists($sourceFile)) {
        throw new RuntimeException('songs.json not found at ' . $sourceFile);
    }

    $data = json_decode(file_get_contents($sourceFile), true);
    if (!is_array($data)) {
        throw new RuntimeException('Invalid songs.json: ' . json_last_error_msg());
    }

    outputLine("<div class='ok'>OK songs.json carregado com sucesso</div>");

    $songsRoot = findSongsRoot($data);
    outputLine("<div class='ok'>OK Estrutura Songs encontrada dentro de songs.json</div>");
    outputLine("<hr>");

    $buckets = emptyBuckets();
    $seen = [];
    $stats = [
        'songsScanned' => 0,
        'sscScanned' => 0,
        'chartsFound' => 0,
        'missingSsc' => 0,
        'duplicatesSkipped' => 0,
    ];

    foreach (($songsRoot['children'] ?? []) as $stageIndex => $stage) {
        $stageName = $stage['name'] ?? ('Categoria ' . $stageIndex);
        $stageStats = [
            'songs' => 0,
            'withoutSsc' => 0,
            'ssc' => 0,
            'charts' => 0,
            'missingSsc' => 0,
            'duplicatesSkipped' => 0,
        ];

        outputLine("<h3>Processando: " . e($stageName) . "</h3>");
        outputLine("<p>Escaneando musicas e arquivos .ssc...</p>");

        foreach (($stage['children'] ?? []) as $songIndex => $song) {
            $sscFiles = songSscFiles($song);
            if (empty($sscFiles)) {
                $stageStats['withoutSsc']++;
                debugLine("<p class='debug warn'>Sem .ssc: " . e($song['name'] ?? '') . "</p>");
                continue;
            }

            $stageStats['songs']++;
            $stats['songsScanned']++;
            $banner = firstSongBanner($song);
            debugLine("<p class='debug'>Musica: " . e($song['name'] ?? '') . " (" . count($sscFiles) . " .ssc)</p>");
            if ($banner !== null) {
                debugLine("<p class='debug-deep'>Banner: " . e($banner) . "</p>");
            } else {
                debugLine("<p class='debug-deep warn'>Nenhum banner encontrado</p>");
            }

            foreach ($sscFiles as $sscFile) {
                $sscRelativePath = $sscFile['path'] ?? '';
                $sscAbsolutePath = projectPath($sscRelativePath);

                if (!is_file($sscAbsolutePath)) {
                    $stats['missingSsc']++;
                    $stageStats['missingSsc']++;
                    debugLine("<p class='debug-deep error'>SSC nao encontrado: " . e($sscRelativePath) . "</p>");
                    continue;
                }

                $stats['sscScanned']++;
                $stageStats['ssc']++;
                $sscData = parseSscFile($sscAbsolutePath);
                $charts = $sscData['charts'];
                $stats['chartsFound'] += count($charts);
                $stageStats['charts'] += count($charts);
                debugLine("<p class='debug-deep'>Lido: " . e($sscRelativePath) . " (" . count($charts) . " charts single/double)</p>");

                $titleIdentity = normalizeIdentityPart($sscData['title']);
                $artistIdentity = normalizeIdentityPart($sscData['artist']);
                $songIdentity = $titleIdentity !== ''
                    ? $titleIdentity . '|' . $artistIdentity
                    : fallbackSongIdentity($song['name'] ?? '');

                debugLine("<p class='debug-deep'>Identidade anti-duplicacao: " . e($songIdentity) . "</p>");

                $entry = [
                    'stageIndex' => $stageIndex,
                    'songIndex' => $songIndex,
                    'name' => $song['name'] ?? '',
                    'songPath' => $song['path'] ?? '',
                    'banner' => $banner,
                    'sscPath' => $sscRelativePath,
                    'songIdentity' => $songIdentity,
                ];

                foreach ($charts as $chart) {
                    if (addChartToBuckets($buckets, $seen, $chart, $entry)) {
                        debugLine("<p class='debug-deep ok'>Chart adicionado: " . e($chart['mode']) . " " . e($chart['level']) . "</p>");
                    } else {
                        $stats['duplicatesSkipped']++;
                        $stageStats['duplicatesSkipped']++;
                        debugLine("<p class='debug-deep warn'>Duplicata ignorada: " . e($chart['mode']) . " " . e($chart['level']) . " - " . e($song['name'] ?? '') . "</p>");
                    }
                }
            }
        }

        if ($stageStats['songs'] > 0) {
            outputLine("<p class='ok'>Sucesso! Categoria processada</p>");
        } else {
            outputLine("<p class='warn'>Nenhuma musica com .ssc encontrada nesta categoria</p>");
        }

        outputLine("<p>Total de musicas com .ssc nesta categoria: " . $stageStats['songs'] . "</p>");
        outputLine("<p class='muted'>SSC lidos: " . $stageStats['ssc'] . " | Charts encontrados: " . $stageStats['charts'] . " | Duplicatas ignoradas: " . $stageStats['duplicatesSkipped'] . " | Sem .ssc: " . $stageStats['withoutSsc'] . " | SSC faltando: " . $stageStats['missingSsc'] . "</p>");
        outputLine("<hr>");
    }

    outputLine("<h3>Ordenando listas por dificuldade...</h3>");

    foreach ($buckets as $mode => &$levels) {
        foreach ($levels as &$items) {
            sortBucket($items);
        }
    }
    unset($levels, $items);

    outputLine("<p class='ok'>Listas ordenadas com sucesso</p>");
    outputLine("<h3>Salvando arquivos em difficulty-zone</h3>");

    foreach (['single', 'double'] as $mode) {
        $modeDir = $outputDir . DIRECTORY_SEPARATOR . $mode;
        if (!is_dir($modeDir) && !mkdir($modeDir, 0777, true)) {
            throw new RuntimeException('Failed to create ' . $modeDir);
        }

        outputLine("<p class='info'>Salvando modo: <strong>" . e($mode) . "</strong></p>");

        for ($level = 1; $level <= MAX_EXPLICIT_LEVEL; $level++) {
            jsonWrite($modeDir . DIRECTORY_SEPARATOR . $level . '.json', $buckets[$mode][(string) $level]);
            debugLine("<p class='debug'>Arquivo salvo: difficulty-zone/" . e($mode) . "/" . $level . ".json (" . count($buckets[$mode][(string) $level]) . " musicas)</p>");
        }

        jsonWrite($modeDir . DIRECTORY_SEPARATOR . PLUS_LEVEL_FILE, $buckets[$mode][PLUS_LEVEL_LABEL]);
        debugLine("<p class='debug'>Arquivo salvo: difficulty-zone/" . e($mode) . "/" . PLUS_LEVEL_FILE . " (" . count($buckets[$mode][PLUS_LEVEL_LABEL]) . " musicas)</p>");
    }

    $counts = [];
    foreach ($buckets as $mode => $levels) {
        $counts[$mode] = [];
        foreach ($levels as $level => $items) {
            $counts[$mode][$level] = count($items);
        }
    }

    $manifest = [
        'generatedAt' => gmdate('Y-m-d\TH:i:s\Z'),
        'source' => '../songs.json',
        'levels' => [
            'min' => 1,
            'max' => MAX_EXPLICIT_LEVEL,
            'plus' => PLUS_LEVEL_LABEL,
        ],
        'stats' => $stats,
        'counts' => $counts,
    ];

    if (!is_dir($outputDir) && !mkdir($outputDir, 0777, true)) {
        throw new RuntimeException('Failed to create ' . $outputDir);
    }

    jsonWrite($outputDir . DIRECTORY_SEPARATOR . 'manifest.json', $manifest);

    outputLine("<h2 class='ok'>difficulty-zone gerado com sucesso!</h2>");
    outputLine("<p><strong>Localizacao:</strong> " . e($outputDir) . "</p>");
    outputLine("<p><strong>Manifest:</strong> " . e($outputDir . DIRECTORY_SEPARATOR . 'manifest.json') . "</p>");

    outputLine("<hr>");
    outputLine("<h3>Estatisticas:</h3>");
    outputLine("<ul>");
    outputLine("<li>Musicas escaneadas: <strong>" . $stats['songsScanned'] . "</strong></li>");
    outputLine("<li>Arquivos SSC lidos: <strong>" . $stats['sscScanned'] . "</strong></li>");
    outputLine("<li>Charts encontrados: <strong>" . $stats['chartsFound'] . "</strong></li>");
    outputLine("<li>Duplicatas ignoradas: <strong>" . $stats['duplicatesSkipped'] . "</strong></li>");
    outputLine("<li>Arquivos SSC faltando: <strong>" . $stats['missingSsc'] . "</strong></li>");
    outputLine("<li>Total single: <strong>" . array_sum($counts['single']) . "</strong></li>");
    outputLine("<li>Total double: <strong>" . array_sum($counts['double']) . "</strong></li>");
    outputLine("</ul>");

    if ($debugMode) {
        outputLine("<h3>Contagem por nivel:</h3>");
        outputLine("<p class='debug'><strong>Single:</strong> " . e(json_encode($counts['single'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) . "</p>");
        outputLine("<p class='debug'><strong>Double:</strong> " . e(json_encode($counts['double'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) . "</p>");
    }

    outputLine("<hr>");
    outputLine("<p><a href='index.php'>Voltar para o gerenciador de arquivos</a></p>");

    if (!$isCli) {
        echo '</body>';
    }
} catch (Throwable $error) {
    if (!$isCli) {
        http_response_code(500);
    }

    outputLine("<p class='error'>ERRO: " . e($error->getMessage()) . "</p>");
    exit(1);
}
