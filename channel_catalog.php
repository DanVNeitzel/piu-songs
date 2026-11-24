<?php
/** Shared channel layout for both catalogue generators. Songs keep their real paths. */
function applyChannelLayout(array $catalog): array {
    $config = json_decode(file_get_contents(__DIR__ . '/channels.json'), true, 512, JSON_THROW_ON_ERROR);
    $stages = $catalog['children'][0]['children'];
    $songs = [];
    $byFolder = [];
    foreach ($stages as $stage) {
        $byFolder[$stage['name']] = $stage['children'];
        foreach ($stage['children'] as $song) {
            if (($song['type'] ?? '') === 'folder') {
                $songs[$song['path']] = $song;
            }
        }
    }
    $metadata = [];
    foreach ($songs as $path => $song) {
        $tags = [];
        $types = [];
        $ucs = false;
        foreach ($song['children'] as $file) {
            if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'ssc') continue;
            $content = file_get_contents(__DIR__ . '/' . preg_replace('~^\./~', '', $file['path']));
            if ($content === false) continue;
            $header = preg_split('/#NOTEDATA\s*:/i', $content, 2)[0];
            preg_match_all('/#(SONGTYPE|SONGCATEGORY|GENRE)\s*:\s*([^;]*);/i', $header, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) $tags[strtoupper($match[1])][] = strtoupper(trim($match[2]));
            preg_match_all('/#STEPSTYPE\s*:\s*([^;]*);/i', $content, $matches);
            foreach ($matches[1] as $type) $types[] = strtolower(trim($type));
            $ucs = $ucs || (bool) preg_match('/#(?:DESCRIPTION|CHARTNAME)\s*:[^;]*\bUCS\b/i', $content);
        }
        $categories = $tags['SONGCATEGORY'] ?? [];
        if (!$categories || in_array('USE_GENRE', $categories, true)) {
            $categories = array_merge($categories, $tags['GENRE'] ?? []);
        }
        $metadata[$path] = ['songtype' => $tags['SONGTYPE'] ?? [], 'category' => $categories,
            'stepstype' => $types, 'ucs' => $ucs];
    }
    $channels = [];
    foreach ($config['channels'] as $definition) {
        $folder = $definition['folder'];
        $rule = $definition['rule'];
        $children = $byFolder[$folder] ?? [];
        if ($rule !== 'series' && $rule !== 'manual') {
            $children = [];
            foreach ($songs as $path => $song) {
                $m = $metadata[$path];
                $match = $rule === 'all'
                    || ($rule === 'category' && count(array_intersect($definition['values'], $m['category'])) > 0)
                    || ($rule === 'songtype' && count(array_intersect($definition['values'], $m['songtype'])) > 0)
                    || ($rule === 'stepstype' && count(array_intersect($definition['values'], $m['stepstype'])) > 0)
                    || ($rule === 'ucs' && $m['ucs']);
                if ($match) $children[$path] = $song;
            }
            $children = array_values($children);
        }
        $channels[] = ['children' => $children, 'name' => $folder, 'path' => './Songs/' . $folder,
            'type' => 'folder', 'banner' => './Songs/' . $folder . '/banner.png',
            'channelId' => $definition['id'], 'virtual' => $rule !== 'series' && $rule !== 'manual'];
        unset($byFolder[$folder]);
    }
    // Keep any future, unconfigured song packs visible too.
    foreach ($stages as $stage) {
        if (isset($byFolder[$stage['name']])) $channels[] = $stage;
    }
    $catalog['children'][0]['children'] = $channels;
    return $catalog;
}
