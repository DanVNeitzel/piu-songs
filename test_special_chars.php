<?php
require_once __DIR__ . '/app/security.php';
piu_require_auth();
/**
 * Script de teste para verificar se o escaneamento funciona com caracteres especiais
 */

echo "<h1>Teste de Caracteres Especiais</h1>";
echo "<style>body { font-family: monospace; background: #000; color: #0f0; padding: 20px; }</style>";

$testFolders = [
    'Songs/XX/1620-%X (Percent X)',
    'Songs/XX/16F9-[Full Song] Baroque Virus',
    'Songs/XX/1642-The Little Prince (Prod. Godic)',
    'Songs/XX/1647-HANN (Alone)',
];

echo "<h2>Testando pastas:</h2>";

foreach ($testFolders as $folder) {
    echo "<hr>";
    echo "<h3>📁 $folder</h3>";
    
    $fullPath = __DIR__ . DIRECTORY_SEPARATOR . $folder;
    
    if (!is_dir($fullPath)) {
        echo "<p style='color: red;'>❌ Pasta não existe!</p>";
        continue;
    }
    
    echo "<p style='color: green;'>✓ Pasta existe</p>";
    
    // Listar arquivos
    $files = scandir($fullPath);
    
    if (!$files) {
        echo "<p style='color: red;'>❌ Erro ao escanear pasta</p>";
        continue;
    }
    
    echo "<p><strong>Arquivos encontrados:</strong></p>";
    echo "<ul>";
    
    $audioFound = false;
    $sscFound = false;
    $imageFound = false;
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $icon = '📄';
        
        if (in_array($ext, ['mp3', 'wav', 'ogg'])) {
            $icon = '🎵';
            $audioFound = true;
        } elseif (in_array($ext, ['ssc', 'sm'])) {
            $icon = '📋';
            $sscFound = true;
        } elseif (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif'])) {
            $icon = '🖼️';
            $imageFound = true;
        }
        
        // Testar URL encode
        $encoded = rawurlencode($file);
        $decoded = rawurldecode($encoded);
        $match = ($file === $decoded) ? '✓' : '❌';
        
        echo "<li>$icon <strong>$file</strong> (.$ext) - URL: $match</li>";
    }
    
    echo "</ul>";
    
    // Resumo
    echo "<p><strong>Resumo:</strong></p>";
    echo "<ul>";
    echo "<li>Áudio: " . ($audioFound ? '✓ Encontrado' : '❌ Não encontrado') . "</li>";
    echo "<li>SSC: " . ($sscFound ? '✓ Encontrado' : '❌ Não encontrado') . "</li>";
    echo "<li>Imagem: " . ($imageFound ? '✓ Encontrado' : '❌ Não encontrado') . "</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<h2>✅ Teste concluído!</h2>";
echo "<p><a href='generate_songs_json.php'>← Voltar para o gerador</a></p>";
?>
