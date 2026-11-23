<?php
/**
 * Analisador detalhado do arquivo SSC específico
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$sscFile = __DIR__ . '/Songs/XX/16D4-[Remix] Prime Time/16D4 - [Remix] Prime Time.ssc';

echo "<style>body { font-family: monospace; background: #000; color: #0f0; padding: 20px; font-size: 12px; } .error { color: #f00; background: #300; padding: 2px 5px; } .warning { color: #ff0; } .ok { color: #0f0; } pre { background: #111; border: 1px solid #0f0; padding: 10px; overflow-x: auto; }</style>";

echo "<h1>🔍 Análise do arquivo SSC corrompido</h1>";
echo "<p><strong>Arquivo:</strong> $sscFile</p>";
echo "<hr>";

if (!file_exists($sscFile)) {
    echo "<p class='error'>❌ ARQUIVO NÃO ENCONTRADO!</p>";
    echo "<p>O arquivo pode ter sido movido ou deletado.</p>";
    exit;
}

// Ler conteúdo
$content = file_get_contents($sscFile);
$lines = explode("\n", $content);

echo "<h2>📊 Informações do arquivo:</h2>";
echo "<ul>";
echo "<li>Tamanho: " . number_format(strlen($content)) . " bytes</li>";
echo "<li>Total de linhas: " . count($lines) . "</li>";
echo "</ul>";

// Caracteres válidos
$validNoteChars = '01234567XxYyZzVHFMLK*BSEIa|{}';

echo "<hr>";
echo "<h2>🐛 Procurando por erros...</h2>";

$inNotesSection = false;
$lineNumber = 0;
$errorsFound = [];
$notesSections = 0;

foreach ($lines as $line) {
    $lineNumber++;
    $trimmed = trim($line);
    
    // Detectar início de seção #NOTES
    if (preg_match('/#NOTES:/i', $trimmed)) {
        $inNotesSection = true;
        $notesSections++;
        continue;
    }
    
    // Detectar fim de seção
    if ($inNotesSection && preg_match('/^;/', $trimmed)) {
        $inNotesSection = false;
        continue;
    }
    
    // Se estiver na seção de notas, verificar caracteres
    if ($inNotesSection && !empty($trimmed)) {
        // Ignorar linhas com : (metadata)
        if (strpos($trimmed, ':') !== false) continue;
        
        // Ignorar separadores puros
        if ($trimmed === ',' || $trimmed === ';') continue;
        
        // Verificar cada caractere
        for ($i = 0; $i < strlen($trimmed); $i++) {
            $char = $trimmed[$i];
            
            // Verificar se é válido
            if (strpos($validNoteChars, $char) === false) {
                // Espaços e tabs às vezes são OK
                if ($char === ' ' || $char === "\t" || $char === "\r") continue;
                
                $errorsFound[] = [
                    'line' => $lineNumber,
                    'char' => $char,
                    'ascii' => ord($char),
                    'context' => $trimmed,
                    'position' => $i
                ];
            }
        }
    }
}

echo "<p>Seções #NOTES encontradas: <strong>$notesSections</strong></p>";
echo "<p>Erros encontrados: <strong class='error'>" . count($errorsFound) . "</strong></p>";

if (count($errorsFound) > 0) {
    echo "<hr>";
    echo "<h2 class='error'>❌ CARACTERES INVÁLIDOS ENCONTRADOS:</h2>";
    
    // Mostrar apenas os primeiros 10 erros (evitar output muito grande)
    $errorsToShow = array_slice($errorsFound, 0, 10);
    
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-color: #0f0;'>";
    echo "<tr style='background: #030;'>";
    echo "<th>Linha</th>";
    echo "<th>Caractere</th>";
    echo "<th>ASCII</th>";
    echo "<th>Posição</th>";
    echo "<th>Contexto (primeiros 80 chars)</th>";
    echo "</tr>";
    
    foreach ($errorsToShow as $error) {
        $contextPreview = substr($error['context'], 0, 80);
        $charDisplay = ($error['char'] === "\n" || $error['char'] === "\r") ? 
            '<em>[newline]</em>' : 
            htmlspecialchars($error['char']);
        
        echo "<tr>";
        echo "<td class='error'>{$error['line']}</td>";
        echo "<td class='error' style='font-size: 20px; font-weight: bold;'>{$charDisplay}</td>";
        echo "<td>{$error['ascii']}</td>";
        echo "<td>{$error['position']}</td>";
        echo "<td style='font-size: 10px;'>" . htmlspecialchars($contextPreview) . "...</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    if (count($errorsFound) > 10) {
        echo "<p class='warning'>⚠ Mostrando apenas os primeiros 10 erros de " . count($errorsFound) . " encontrados.</p>";
    }
    
    echo "<hr>";
    echo "<h2>🔧 COMO CORRIGIR:</h2>";
    echo "<ol>";
    echo "<li><strong>Opção 1 (RECOMENDADO):</strong> Re-baixe esta música de uma fonte confiável</li>";
    echo "<li><strong>Opção 2:</strong> Execute <code>move_corrupted_song.bat</code> para mover esta música temporariamente</li>";
    echo "<li><strong>Opção 3 (AVANÇADO):</strong> Edite manualmente o arquivo SSC:</li>";
    echo "<ul>";
    echo "<li>Abra o arquivo em um editor de texto (Notepad++, VSCode)</li>";
    echo "<li>Vá até a linha indicada acima</li>";
    echo "<li>Remova ou substitua os caracteres inválidos</li>";
    echo "<li>Salve e teste novamente</li>";
    echo "</ul>";
    echo "</ol>";
    
    // Mostrar linha problemática completa
    if (count($errorsFound) > 0) {
        $firstError = $errorsFound[0];
        echo "<hr>";
        echo "<h3>📝 Exemplo da primeira linha com erro:</h3>";
        echo "<p><strong>Linha {$firstError['line']}:</strong></p>";
        echo "<pre>" . htmlspecialchars($firstError['context']) . "</pre>";
        echo "<p>Caractere inválido '<strong class='error'>" . htmlspecialchars($firstError['char']) . "</strong>' na posição {$firstError['position']}</p>";
    }
    
} else {
    echo "<p class='ok'>✅ Nenhum erro encontrado nas seções #NOTES!</p>";
    echo "<p class='warning'>⚠ O erro pode estar em outra parte do arquivo ou ser um problema de encoding.</p>";
}

echo "<hr>";
echo "<h2>🔗 Próximos passos:</h2>";
echo "<ul>";
echo "<li><a href='move_corrupted_song.bat' download style='color: #0ff;'>📥 Baixar script para mover música</a></li>";
echo "<li><a href='generate_songs_json.php' style='color: #0ff;'>🔄 Regenerar songs.json</a></li>";
echo "<li><a href='find_corrupted_ssc.php' style='color: #0ff;'>🔍 Escanear todos os SSC novamente</a></li>";
echo "</ul>";
?>
