<?php
/**
 * Gerador Automático de songs.json
 * 
 * Este script escaneia diretamente a pasta Songs/ e todos os subdiretórios,
 * encontrando todos os arquivos de música (.ssc, .mp3, imagens) e
 * gerando um único songs.json completo.
 * 
 * CORRIGIDO: Lida corretamente com caracteres especiais !@#$%¨&*(){}[]
 */

set_time_limit(300); // 5 minutos de timeout
ini_set('memory_limit', '512M'); // Aumentar memória se necessário

// Configurar encoding para Windows (importante para caracteres especiais)
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    // No Windows, garantir que estamos usando UTF-8
    if (function_exists('mb_internal_encoding')) {
        mb_internal_encoding('UTF-8');
    }
}

// Modo debug: adicione ?debug=1 na URL para ver detalhes
$debugMode = isset($_GET['debug']) && $_GET['debug'] == '1';

// Diretório base
$baseDir = __DIR__;
$songsDir = $baseDir . DIRECTORY_SEPARATOR . 'Songs';
$outputFile = $baseDir . DIRECTORY_SEPARATOR . 'songs.json';

/**
 * Procura por um arquivo de imagem na pasta (para usar como banner)
 */
function findBannerImage($folderPath, $folderName) {
    global $debugMode;
    
    $imageExtensions = ['png', 'webp', 'jpg', 'jpeg', 'gif', 'avif', 'bmp'];
    
    if (!is_dir($folderPath)) {
        return null;
    }
    
    $files = scandir($folderPath);
    if (!$files) {
        return null;
    }
    
    // Procurar por arquivos de imagem
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $filePath = $folderPath . DIRECTORY_SEPARATOR . $file;
        
        // Verificar se é arquivo (não pasta)
        if (!is_file($filePath)) continue;
        
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        
        // Se encontrar uma imagem, retornar o path
        if (in_array($ext, $imageExtensions)) {
            if ($debugMode) echo "<p style='margin-left: 20px; color: #ff0;'>🖼️ Banner encontrado: $file</p>";
            return './Songs/' . $folderName . '/' . $file;
        }
    }
    
    if ($debugMode) echo "<p style='margin-left: 20px; color: orange;'>⚠ Nenhum banner encontrado em $folderName</p>";
    return null;
}

/**
 * Escaneia diretamente uma pasta e retorna JSON com os arquivos
 * NÃO gera index.php, escaneia diretamente do PHP
 */
function scanMusicFolder($folderPath, $folderName) {
    global $debugMode;
    
    // Procurar por arquivo de imagem para usar como banner
    $bannerPath = findBannerImage($folderPath, $folderName);
    
    $result = [
        'children' => [],
        'name' => $folderName,
        'path' => './Songs/' . $folderName,
        'type' => 'folder'
    ];
    
    // Adicionar banner apenas se encontrado
    if ($bannerPath !== null) {
        $result['banner'] = $bannerPath;
    }
    
    // Escanear pastas de músicas dentro desta categoria
    if (!is_dir($folderPath)) {
        if ($debugMode) echo "<p style='color: red;'>❌ Não é pasta: $folderPath</p>";
        return $result;
    }
    
    $items = scandir($folderPath);
    if (!$items) {
        if ($debugMode) echo "<p style='color: red;'>❌ Erro ao escanear: $folderPath</p>";
        return $result;
    }
    
    $totalSongs = 0;
    $totalFiles = 0;
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        
        $itemPath = $folderPath . DIRECTORY_SEPARATOR . $item;
        
        // Verificar se é uma pasta (música)
        if (is_dir($itemPath)) {
            if ($debugMode) echo "<p style='color: cyan;'>📁 Escaneando música: $item</p>";
            
            $songData = [
                'children' => [],
                'name' => $item,
                'path' => './Songs/' . $folderName . '/' . $item,
                'type' => 'folder'
            ];
            
            $fileCount = 0;
            
            // Escanear arquivos dentro da pasta da música
            $songFiles = scandir($itemPath);
            if ($songFiles) {
                foreach ($songFiles as $file) {
                    if ($file === '.' || $file === '..') continue;
                    
                    $fullFilePath = $itemPath . DIRECTORY_SEPARATOR . $file;
                    
                    // DEBUG: Mostrar TUDO que foi encontrado
                    if ($debugMode) {
                        $isFileCheck = is_file($fullFilePath) ? '📄' : '📁';
                        echo "<p style='margin-left: 20px; font-size: 11px; color: #999;'>$isFileCheck $file</p>";
                    }
                    
                    // Verificar se é arquivo (não pasta)
                    if (!is_file($fullFilePath)) continue;
                    
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    
                    // Verificar se é arquivo de áudio, stepfile ou imagem
                    $validExtensions = ['mp3', 'wav', 'ogg', 'ssc', 'sm', 'webp', 'png', 'jpg', 'jpeg', 'gif', 'avif', 'bmp'];
                    
                    if (in_array($ext, $validExtensions)) {
                        // Manter path sem encoding - o encoding será feito no lado do cliente quando necessário
                        $songPath = './Songs/' . $folderName . '/' . $item . '/' . $file;
                        
                        $songData['children'][] = [
                            'name' => $file,
                            'path' => $songPath,
                            'type' => 'file'
                        ];
                        
                        $fileCount++;
                        $totalFiles++;
                        
                        if ($debugMode) echo "<p style='margin-left: 40px; color: #0f0;'>✓ Adicionado: $file (.$ext)</p>";
                    } else {
                        if ($debugMode && !empty($ext)) {
                            echo "<p style='margin-left: 40px; color: orange;'>⚠ Ignorado (extensão .$ext): $file</p>";
                        }
                    }
                }
            }
            
            // SÓ ADICIONAR SE TIVER PELO MENOS 1 ARQUIVO
            if ($fileCount > 0) {
                $result['children'][] = $songData;
                $totalSongs++;
                if ($debugMode) echo "<p style='color: #0f0;'>✅ Música adicionada: $item ($fileCount arquivos)</p>";
            } else {
                if ($debugMode) echo "<p style='color: orange;'>⚠ Música PULADA (sem arquivos válidos): $item</p>";
            }
        }
    }
    
    if ($debugMode) {
        echo "<p style='color: #0ff;'><strong>📊 Total: $totalSongs músicas, $totalFiles arquivos</strong></p>";
    }
    
    return $result;
}

// Verificar se a pasta Songs existe
if (!is_dir($songsDir)) {
    die("Erro: Pasta 'Songs' não encontrada!");
}

echo "<h1>Gerador de songs.json</h1>";
echo "<style>body { font-family: monospace; background: #000; color: #0f0; padding: 20px; } a { color: #0ff; }</style>";
echo "<p>Escaneando pasta: <strong>$songsDir</strong></p>";
echo "<p><em>Escaneando diretamente todas as músicas e arquivos...</em></p>";
echo "<p style='color: #0f0;'><strong>✓ CORRIGIDO:</strong> Suporte completo para caracteres especiais !@#$%¨&*(){}[]</p>";

if ($debugMode) {
    echo "<p style='background: yellow; color: black; padding: 10px; font-weight: bold;'>🐛 MODO DEBUG ATIVADO - Mostrando todos os detalhes</p>";
} else {
    echo "<p style='color: #999;'>💡 Para ver detalhes completos: <a href='?debug=1' style='color: #0ff;'>Ativar modo debug</a></p>";
}

echo "<hr>";

// Carregar a estrutura base do base_songs.json
$baseFile = __DIR__ . DIRECTORY_SEPARATOR . 'base_songs.json';
if (!file_exists($baseFile)) {
    echo "<div class='alert alert-danger'>❌ Arquivo base_songs.json não encontrado!</div>";
    exit;
}

$allSongsData = json_decode(file_get_contents($baseFile), true);
if ($allSongsData === null) {
    echo "<div class='alert alert-danger'>❌ Erro ao decodificar base_songs.json!</div>";
    exit;
}

echo "<div class='alert alert-success'>✓ Estrutura base carregada de base_songs.json</div>";

// Escanear todas as pastas dentro de Songs/
$folders = scandir($songsDir);
$processedCount = 0;
$skippedCount = 0;

foreach ($folders as $folder) {
    // Ignorar . e ..
    if ($folder === '.' || $folder === '..') {
        continue;
    }
    
    $folderPath = $songsDir . DIRECTORY_SEPARATOR . $folder;
    
    // Verificar se é uma pasta
    if (!is_dir($folderPath)) {
        continue;
    }
    
    echo "<h3>Processando: $folder</h3>";
    
    // Escanear diretamente a pasta ao invés de usar index.php
    echo "<p>📂 Escaneando arquivos...</p>";
    
    $jsonData = scanMusicFolder($folderPath, $folder);
    
    if (empty($jsonData['children'])) {
        echo "<p style='color: orange;'>⚠ Nenhuma música encontrada em $folder</p>";
        $skippedCount++;
        continue;
    }
    
    // Adicionar ao array principal (dentro de Songs -> children)
    $allSongsData['children'][0]['children'][] = $jsonData;
    
    echo "<p style='color: green;'>✅ Sucesso! Adicionado ao songs.json</p>";
    echo "<p>Total de músicas nesta pasta: " . count($jsonData['children']) . "</p>";
    
    // Mostrar algumas músicas como exemplo
    if (count($jsonData['children']) > 0) {
        echo "<p style='color: #ccc; font-size: 12px;'>Exemplos: ";
        $examples = array_slice($jsonData['children'], 0, 3);
        foreach ($examples as $song) {
            $fileCount = count($song['children']);
            $icon = $fileCount > 0 ? '✅' : '❌';
            echo "$icon {$song['name']} ($fileCount arquivos), ";
        }
        echo "...</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ ATENÇÃO: Nenhuma música foi adicionada nesta categoria!</p>";
        echo "<p style='color: yellow;'>💡 Execute com <a href='?debug=1' style='color: cyan;'>modo debug</a> para ver o que está acontecendo</p>";
    }
    
    $processedCount++;
    
    echo "<hr>";
}

require_once __DIR__ . '/channel_catalog.php';
$allSongsData = applyChannelLayout($allSongsData);

// Salvar o arquivo songs.json
$jsonOutput = json_encode($allSongsData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if (file_put_contents($outputFile, $jsonOutput)) {
    echo "<h2 style='color: green;'>✅ Arquivo songs.json gerado com sucesso!</h2>";
    echo "<p><strong>Localização:</strong> $outputFile</p>";
    echo "<p><strong>Tamanho:</strong> " . number_format(filesize($outputFile) / 1024, 2) . " KB</p>";
} else {
    echo "<h2 style='color: red;'>❌ Erro ao salvar arquivo songs.json!</h2>";
}

// Estatísticas
echo "<hr>";
echo "<h3>Estatísticas:</h3>";
echo "<ul>";
echo "<li>Pastas processadas: <strong>$processedCount</strong></li>";
echo "<li>Pastas puladas: <strong>$skippedCount</strong></li>";
echo "<li>Total de categorias no JSON: <strong>" . count($allSongsData['children'][0]['children']) . "</strong></li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='index.php'>← Voltar para o gerenciador de arquivos</a></p>";
?>
