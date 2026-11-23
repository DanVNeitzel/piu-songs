<?php
/**
 * Script para encontrar arquivos SSC corrompidos
 * Procura por caracteres inválidos que causam erro de parsing
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);

echo "<style>body { font-family: monospace; background: #000; color: #0f0; padding: 20px; } .error { color: #f00; } .warning { color: #ff0; } .ok { color: #0f0; }</style>";
echo "<h1>🔍 Detector de Arquivos SSC Corrompidos</h1>";

$songsDir = __DIR__ . DIRECTORY_SEPARATOR . 'Songs';
$totalSSC = 0;
$corruptedSSC = 0;
$validSSC = 0;

// Caracteres válidos de notas SSC
$validNoteChars = '01234567XxYyZzVHFMLK*BSEIa';

// Função recursiva para escanear pastas
function scanForSSC($dir, &$totalSSC, &$corruptedSSC, &$validSSC, $validNoteChars) {
    $items = scandir($dir);
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        
        if (is_dir($path)) {
            scanForSSC($path, $totalSSC, $corruptedSSC, $validSSC, $validNoteChars);
        } elseif (is_file($path) && preg_match('/\.(ssc|SSC)$/', $item)) {
            $totalSSC++;
            
            // Ler conteúdo do arquivo
            $content = file_get_contents($path);
            
            if ($content === false) {
                echo "<p class='error'>❌ Erro ao ler: $path</p>";
                $corruptedSSC++;
                continue;
            }
            
            // Extrair nome da música (pasta pai)
            $songFolder = basename(dirname($path));
            $category = basename(dirname(dirname($path)));
            
            // Procurar por seções #NOTES
            $hasNoteSection = preg_match('/#NOTES:/i', $content);
            
            if (!$hasNoteSection) {
                echo "<p class='warning'>⚠ SEM #NOTES: $category/$songFolder/$item</p>";
                $validSSC++; // Ainda é válido, só não tem notas
                continue;
            }
            
            // Verificar caracteres inválidos em linhas de notas
            // Linhas de notas geralmente começam com dígitos ou letras válidas
            $lines = explode("\n", $content);
            $inNotesSection = false;
            $lineNumber = 0;
            $hasInvalidChars = false;
            $invalidLines = [];
            
            foreach ($lines as $line) {
                $lineNumber++;
                $trimmed = trim($line);
                
                // Detectar início de seção #NOTES
                if (preg_match('/#NOTES:/i', $trimmed)) {
                    $inNotesSection = true;
                    continue;
                }
                
                // Detectar fim de seção (normalmente ; ou próximo #)
                if ($inNotesSection && (preg_match('/^;/', $trimmed) || preg_match('/^#/', $trimmed))) {
                    $inNotesSection = false;
                }
                
                // Se estiver na seção de notas, verificar caracteres
                if ($inNotesSection && !empty($trimmed)) {
                    // Ignorar linhas com : (metadata dentro de #NOTES)
                    if (strpos($trimmed, ':') !== false) continue;
                    
                    // Ignorar separadores
                    if ($trimmed === ',' || $trimmed === ';') continue;
                    
                    // Verificar se linha contém apenas caracteres válidos
                    for ($i = 0; $i < strlen($trimmed); $i++) {
                        $char = $trimmed[$i];
                        
                        // Permitir pipes (separadores de medida)
                        if ($char === '|') continue;
                        
                        // Verificar se é caractere válido
                        if (strpos($validNoteChars, $char) === false) {
                            $hasInvalidChars = true;
                            $invalidLines[] = [
                                'line' => $lineNumber,
                                'char' => $char,
                                'content' => substr($trimmed, 0, 50)
                            ];
                            break 2; // Sair dos dois loops
                        }
                    }
                }
            }
            
            if ($hasInvalidChars) {
                echo "<p class='error'>❌ CORROMPIDO: $category/<strong>$songFolder</strong>/$item</p>";
                echo "<ul style='margin-left: 40px; color: #f99;'>";
                foreach ($invalidLines as $inv) {
                    echo "<li>Linha {$inv['line']}: Caractere inválido '<strong>{$inv['char']}</strong>' em: {$inv['content']}</li>";
                }
                echo "</ul>";
                $corruptedSSC++;
            } else {
                $validSSC++;
                // echo "<p class='ok'>✓ OK: $category/$songFolder/$item</p>"; // Comentar para reduzir output
            }
        }
    }
}

echo "<p>Escaneando: <strong>$songsDir</strong></p>";
echo "<hr>";

scanForSSC($songsDir, $totalSSC, $corruptedSSC, $validSSC, $validNoteChars);

echo "<hr>";
echo "<h2>📊 RESUMO:</h2>";
echo "<ul>";
echo "<li><strong>Total de arquivos .ssc:</strong> $totalSSC</li>";
echo "<li class='ok'><strong>Válidos:</strong> $validSSC</li>";
echo "<li class='error'><strong>Corrompidos:</strong> $corruptedSSC</li>";
echo "</ul>";

if ($corruptedSSC > 0) {
    echo "<hr>";
    echo "<h3 class='error'>⚠️ AÇÃO NECESSÁRIA:</h3>";
    echo "<p>Arquivos SSC corrompidos precisam ser:</p>";
    echo "<ul>";
    echo "<li>1. <strong>Re-baixados</strong> de uma fonte confiável</li>";
    echo "<li>2. <strong>Corrigidos manualmente</strong> (remover caracteres inválidos)</li>";
    echo "<li>3. <strong>Removidos</strong> da pasta Songs/ (temporariamente)</li>";
    echo "</ul>";
} else {
    echo "<p class='ok'><strong>✅ Nenhum arquivo SSC corrompido encontrado!</strong></p>";
}

echo "<hr>";
echo "<p><a href='generate_songs_json.php'>← Voltar para o gerador</a></p>";
?>
