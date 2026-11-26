<?php
require_once __DIR__ . '/app/security.php';
piu_require_auth();
/**
 * Script de DEBUG - Testa escaneamento de UMA música específica
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<style>body { font-family: monospace; background: #000; color: #0f0; padding: 20px; } pre { background: #111; padding: 10px; border: 1px solid #0f0; }</style>";
echo "<h1>🐛 Debug de Escaneamento</h1>";

// Testar uma música específica com caracteres especiais
$testSongs = [
    'Songs/XX/16F9-[Full Song] Baroque Virus',
    'Songs/XX/1620-%X (Percent X)',
    'Songs/XX/1642-The Little Prince (Prod. Godic)',
    'Songs/XX/1647-HANN (Alone)',
];

foreach ($testSongs as $songPath) {
    echo "<hr>";
    echo "<h2>📁 $songPath</h2>";
    
    $fullPath = __DIR__ . DIRECTORY_SEPARATOR . $songPath;
    
    echo "<p><strong>Caminho completo:</strong> $fullPath</p>";
    
    // Verificar se existe
    if (!is_dir($fullPath)) {
        echo "<p style='color: red;'>❌ PASTA NÃO EXISTE!</p>";
        continue;
    }
    
    echo "<p style='color: green;'>✓ Pasta existe</p>";
    
    // Escanear arquivos
    $files = scandir($fullPath);
    
    if (!$files) {
        echo "<p style='color: red;'>❌ ERRO AO ESCANEAR!</p>";
        continue;
    }
    
    echo "<p><strong>scandir() retornou " . count($files) . " itens:</strong></p>";
    echo "<ul>";
    
    $validFiles = [];
    $validExtensions = ['mp3', 'wav', 'ogg', 'ssc', 'sm', 'webp', 'png', 'jpg', 'jpeg', 'gif', 'avif', 'bmp'];
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $filePath = $fullPath . DIRECTORY_SEPARATOR . $file;
        $isFile = is_file($filePath);
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $isValid = in_array($ext, $validExtensions);
        
        $icon = $isFile ? '📄' : '📁';
        $color = $isValid ? 'lime' : '#999';
        $status = $isValid ? '✓ VÁLIDO' : ($isFile ? "❌ Extensão .$ext não aceita" : '⚠ É pasta');
        
        echo "<li style='color: $color;'>$icon <strong>$file</strong> - $status</li>";
        
        if ($isFile && $isValid) {
            $validFiles[] = [
                'name' => $file,
                'ext' => $ext,
                'path' => $filePath
            ];
        }
    }
    
    echo "</ul>";
    
    // Resumo
    echo "<p><strong>RESUMO:</strong></p>";
    echo "<p>Total de arquivos válidos encontrados: <strong style='color: yellow;'>" . count($validFiles) . "</strong></p>";
    
    if (count($validFiles) > 0) {
        echo "<p style='color: lime;'>✅ Esta música DEVERIA aparecer no songs.json!</p>";
        echo "<details><summary>Ver lista de arquivos válidos (JSON)</summary>";
        echo "<pre>" . json_encode($validFiles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</pre>";
        echo "</details>";
    } else {
        echo "<p style='color: red;'>❌ Esta música NÃO tem arquivos válidos!</p>";
    }
}

echo "<hr>";
echo "<h2>✅ Teste concluído!</h2>";
echo "<p><a href='generate_songs_json.php?debug=1'>← Gerar songs.json com DEBUG</a></p>";
?>
