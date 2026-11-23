<?php
/**
 * Gerador Automático de songs.json - Versão Aprimorada
 * 
 * Este script escaneia a pasta Songs/, encontra os arquivos PHP de cada subpasta,
 * executa cada um e compila tudo em um único songs.json
 */

// Configurações
set_time_limit(600); // 10 minutos de timeout
ini_set('memory_limit', '1024M'); // 1GB de memória
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// Permite execução via AJAX
header('Content-Type: application/json; charset=utf-8');

// Diretório base
$baseDir = __DIR__;
$songsDir = $baseDir . DIRECTORY_SEPARATOR . 'Songs';
$outputFile = $baseDir . DIRECTORY_SEPARATOR . 'songs.json';

// Verificar se deve forçar regeneração de todos os index.php
$forceRegenerate = isset($_GET['force']) && $_GET['force'] === 'true';

// Log de progresso
$log = [];

function addLog($message, $type = 'info') {
    global $log;
    $log[] = [
        'time' => date('H:i:s'),
        'type' => $type,
        'message' => $message
    ];
}

// Verificar se a pasta Songs existe
if (!is_dir($songsDir)) {
    addLog("Pasta 'Songs' não encontrada!", 'error');
    echo json_encode(['success' => false, 'error' => "Pasta 'Songs' não encontrada!", 'log' => $log]);
    exit;
}

addLog("Iniciando geração do songs.json...", 'info');
addLog("Diretório: $songsDir", 'info');

// Criar a estrutura base do JSON diretamente
$allSongsData = [
    'children' => [
        [
            'children' => [],
            'name' => 'Songs',
            'path' => './Songs',
            'type' => 'folder'
        ]
    ],
    'name' => '.',
    'path' => '.',
    'type' => 'folder'
];

addLog("Estrutura base criada", 'success');

// Escanear todas as pastas dentro de Songs/
$folders = scandir($songsDir);
$processedCount = 0;
$skippedCount = 0;
$totalSongs = 0;

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
    
    addLog("Processando pasta: $folder", 'info');
    
    // Procurar especificamente por index.php na pasta
    $indexFile = $folderPath . DIRECTORY_SEPARATOR . 'index.php';
    
    // Verificar se deve gerar/regenerar o index.php
    if (!file_exists($indexFile) || $forceRegenerate) {
        if (!file_exists($indexFile)) {
            addLog("Arquivo index.php não encontrado em: $folder - Gerando automaticamente...", 'warning');
        } else {
            addLog("Regenerando index.php em: $folder (modo forçado)...", 'info');
        }
        
        // Gerar index.php automaticamente
        if (generateIndexPhp($folderPath)) {
            addLog("✓ index.php gerado com sucesso em: $folder", 'success');
        } else {
            addLog("Erro ao gerar index.php em: $folder", 'error');
            $skippedCount++;
            continue;
        }
    }
    
    // Usar o index.php encontrado
    $phpFile = $indexFile;
    $phpFileName = 'index.php';
    
    addLog("Arquivo encontrado: $phpFileName", 'info');
    
    try {
        // Salvar diretório atual
        $originalDir = getcwd();
        
        // Executar o arquivo PHP e capturar a saída
        ob_start();
        chdir($folderPath); // Mudar para o diretório da pasta
        include $phpFile;
        $output = ob_get_clean();
        
        // Restaurar diretório original
        chdir($originalDir);
        
        // Processar a saída HTML para extrair o JSON
        $jsonData = extractJSONFromHTML($output);
        
        if ($jsonData === null) {
            addLog("Falha ao extrair JSON de: $phpFileName", 'error');
            $skippedCount++;
            continue;
        }
        
        // Adicionar ao array principal (dentro de Songs -> children)
        $allSongsData['children'][0]['children'][] = $jsonData;
        
        $songsInFolder = isset($jsonData['children']) ? count($jsonData['children']) : 0;
        $totalSongs += $songsInFolder;
        
        addLog("✓ $folder processado com sucesso ($songsInFolder músicas)", 'success');
        $processedCount++;
        
    } catch (Exception $e) {
        addLog("Erro ao processar $folder: " . $e->getMessage(), 'error');
        $skippedCount++;
    }
}

// Salvar o arquivo songs.json
$jsonOutput = json_encode($allSongsData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$saveSuccess = false;
if (file_put_contents($outputFile, $jsonOutput)) {
    $fileSize = filesize($outputFile);
    addLog("songs.json salvo com sucesso (" . number_format($fileSize / 1024, 2) . " KB)", 'success');
    $saveSuccess = true;
} else {
    addLog("Erro ao salvar arquivo songs.json!", 'error');
}

// Retornar resultado
echo json_encode([
    'success' => $saveSuccess,
    'stats' => [
        'processed' => $processedCount,
        'skipped' => $skippedCount,
        'total_categories' => count($allSongsData['children'][0]['children']),
        'total_songs' => $totalSongs,
        'file_size' => isset($fileSize) ? number_format($fileSize / 1024, 2) . ' KB' : '0 KB'
    ],
    'log' => $log
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

/**
 * Gera um arquivo index.php padrão para uma pasta de músicas
 */
function generateIndexPhp($folderPath) {
    $indexContent = <<<'PHP'
<style>
  body {
    color: #FFEB3B;
    font-size: 15px;
    font-family: monospace;
    background-color: #090909;
  }

  h1 {
    color: #0300ff;
  }

  h3 {
    color: #ff0000;
  }
</style>

<?php

// Obtém o nome da pasta atual
$currentFolder = basename(__DIR__);

echo '
{<br>
"children": [<br>
';

// Lista todas as pastas dentro da pasta atual (pastas de músicas)
$folders = glob("*", GLOB_ONLYDIR);

// Filtrar para remover pastas inválidas
$folders = array_filter($folders, function($folder) {
    return $folder !== 'index.php' && substr($folder, -4) !== '.php';
});

// Ordenar pastas
sort($folders);

$folderCount = count($folders);
$currentIndex = 0;

if ($folders) {
  foreach ($folders as $folder) {
    $currentIndex++;
    
    echo '
    {<br>
    "children": [<br>
    ';

    // Array para armazenar todos os arquivos encontrados
    $foundFiles = [];

    // Buscar arquivo .mp3 ou .MP3
    $mp3Files = array_merge(glob("$folder/*.mp3"), glob("$folder/*.MP3"));
    if (!empty($mp3Files)) {
        $file = $mp3Files[0]; // Pegar o primeiro arquivo
        $foundFiles[] = [
            'name' => $folder . '.mp3',
            'path' => './Songs/' . $currentFolder . '/' . $file,
            'type' => 'file'
        ];
    }

    // Buscar arquivo .ssc ou .SSC
    $sscFiles = array_merge(glob("$folder/*.ssc"), glob("$folder/*.SSC"));
    if (!empty($sscFiles)) {
        $file = $sscFiles[0]; // Pegar o primeiro arquivo
        $foundFiles[] = [
            'name' => $folder . '.ssc',
            'path' => './Songs/' . $currentFolder . '/' . $file,
            'type' => 'file'
        ];
    }

    // Buscar arquivo .webp ou .WEBP
    $webpFiles = array_merge(glob("$folder/*.webp"), glob("$folder/*.WEBP"));
    if (!empty($webpFiles)) {
        $file = $webpFiles[0]; // Pegar o primeiro arquivo
        $foundFiles[] = [
            'name' => $folder . '.webp',
            'path' => './Songs/' . $currentFolder . '/' . $file,
            'type' => 'file'
        ];
    }

    // Imprimir os arquivos encontrados
    $fileCount = count($foundFiles);
    for ($i = 0; $i < $fileCount; $i++) {
        $fileData = $foundFiles[$i];
        echo '
                {<br>
                "name": "' . $fileData['name'] . '",<br>
                "path": "' . $fileData['path'] . '",<br>
                "type": "' . $fileData['type'] . '"<br>
                }';
        
        // Adicionar vírgula se não for o último arquivo
        if ($i < $fileCount - 1) {
            echo ',';
        }
        echo '<br>
                ';
    }

    echo '
            ],<br>
            "name": "' . $folder . '",<br>
            "path": "./Songs/' . $currentFolder . '/' . $folder . '",<br>
            "type": "folder"<br>
            }';
    
    // Adicionar vírgula se não for a última pasta
    if ($currentIndex < $folderCount) {
        echo ',';
    }
    echo '<br>
            ';
  }
} else {
  echo "Nenhuma pasta encontrada dentro de $currentFolder.";
}

// Buscar o banner (pode ser .webp, .png, .jpg, etc.)
$bannerFiles = array_merge(
    glob("*.webp"),
    glob("*.WEBP"),
    glob("*.png"),
    glob("*.PNG"),
    glob("*.jpg"),
    glob("*.JPG")
);

$bannerPath = "./Songs/" . $currentFolder . "/banner.webp"; // Padrão

// Se encontrou algum banner, usar o primeiro
if (!empty($bannerFiles)) {
    $bannerPath = "./Songs/" . $currentFolder . "/" . $bannerFiles[0];
}

echo '
        ],<br>
        "name": "' . $currentFolder . '",<br>
        "path": "./Songs/' . $currentFolder . '",<br>
        "banner": "' . $bannerPath . '",<br>
        "type": "folder"<br>
        }<br>
        ';

?>
PHP;

    $indexFile = $folderPath . DIRECTORY_SEPARATOR . 'index.php';
    return file_put_contents($indexFile, $indexContent) !== false;
}

/**
 * Extrai JSON de uma saída HTML
 * Os arquivos PHP geram HTML com o JSON embutido, precisamos extraí-lo
 */
function extractJSONFromHTML($html) {
    // Remover tags <style> e seu conteúdo
    $text = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);
    
    // Remover tags <br> e outras tags HTML
    $text = str_replace(['<br>', '<br/>', '<br />'], '', $text);
    $text = strip_tags($text);
    
    // Decode HTML entities
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    
    // Remover espaços extras e quebras de linha
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);
    
    // Encontrar o início do JSON (primeira chave '{')
    $jsonStart = strpos($text, '{');
    if ($jsonStart !== false) {
        $text = substr($text, $jsonStart);
    }
    
    // Corrigir vírgulas extras antes de ] e }
    // Padrão: ,] ou ,} viram ] e }
    $text = preg_replace('/,(\s*[\]}])/', '$1', $text);
    
    // Adicionar vírgulas ausentes entre objetos (} seguido de {)
    $text = preg_replace('/(})\s*({)/', '$1, $2', $text);
    
    // Tentar parsear o JSON
    $jsonData = @json_decode($text, true);
    
    if ($jsonData !== null && json_last_error() === JSON_ERROR_NONE) {
        return $jsonData;
    }
    
    return null;
}
?>
