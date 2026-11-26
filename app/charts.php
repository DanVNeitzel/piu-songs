<?php
/** Shared SSC metadata parser. Does not attempt to validate engine-specific note syntax. */
function piu_chart_metadata(string $path): array
{
    $content = file_get_contents($path);
    if ($content === false) throw new RuntimeException('Não foi possível ler ' . $path);
    $content = preg_replace('~//[^\r\n]*~', '', $content);
    $sections = preg_split('/#NOTEDATA\s*:\s*;/i', $content);
    $header = array_shift($sections);
    $tags = [];
    preg_match_all('/#([A-Z0-9]+)\s*:\s*([^;]*);/i', $header, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) $tags[strtoupper($match[1])] = trim($match[2]);
    $charts = [];
    $issues = [];
    $types = [];
    foreach ($sections as $section) {
        preg_match('/#STEPSTYPE\s*:\s*([^;]*);/i', $section, $type);
        preg_match('/#METER\s*:\s*([^;]*);/i', $section, $meter);
        $stepstype = strtolower(trim($type[1] ?? ''));
        $types[] = $stepstype;
        if ($stepstype === '' || !ctype_digit(trim($meter[1] ?? '')) || (int) ($meter[1] ?? 0) < 1 || !preg_match('/#NOTES\s*:\s*[^;\s][^;]*;/i', $section)) {
            $issues[] = 'Chart sem STEPSTYPE, METER positivo ou NOTES completos.';
            continue;
        }
        $charts[] = ['stepstype' => $stepstype, 'mode' => match ($stepstype) { 'pump-single' => 'single', 'pump-double' => 'double', default => $stepstype }, 'level' => (int) $meter[1]];
    }
    if (!$sections) $issues[] = 'SSC sem seções NOTEDATA.';
    return ['title' => $tags['TITLE'] ?? '', 'artist' => $tags['ARTIST'] ?? '', 'tags' => $tags,
        'types' => array_values(array_unique($types)), 'charts' => $charts,
        'ucs' => (bool) preg_match('/#(?:DESCRIPTION|CHARTNAME)\s*:[^;]*\bUCS\b/i', $content), 'issues' => array_values(array_unique($issues))];
}
