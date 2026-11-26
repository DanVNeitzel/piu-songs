<?php
require_once __DIR__ . '/app/security.php';
piu_require_auth();
/**
 * Validação rigorosa de JSON
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$files = ['debug_09_REMIX.json', 'debug_MOESTEP.json', 'debug_OVERCROSS.json'];

foreach ($files as $filename) {
    echo "<h2>$filename</h2>";
    
    if (!file_exists($filename)) {
        echo "<p style='color:red;'>Arquivo não encontrado</p><hr>";
        continue;
    }
    
    $content = file_get_contents($filename);
    
    // Verificar BOM
    if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
        echo "<p style='color:orange;'>⚠ BOM UTF-8 detectado no início</p>";
        $content = substr($content, 3);
    }
    
    // Verificar caracteres invisíveis
    $hasInvisible = false;
    for ($i = 0; $i < min(strlen($content), 100000); $i++) {
        $ord = ord($content[$i]);
        // Caracteres de controle que não são espaço, tab, newline, carriage return
        if ($ord < 32 && $ord !== 9 && $ord !== 10 && $ord !== 13) {
            $hasInvisible = true;
            echo "<p style='color:red;'>Caractere invisível encontrado na posição $i: ASCII $ord</p>";
            echo "<p>Contexto: " . htmlspecialchars(substr($content, $i-20, 40)) . "</p>";
            break;
        }
    }
    
    if (!$hasInvisible) {
        echo "<p style='color:green;'>✓ Sem caracteres invisíveis detectados</p>";
    }
    
    // Tentar decodificar
    $decoded = @json_decode($content, true);
    
    if ($decoded === null) {
        $error = json_last_error_msg();
        echo "<p style='color:red;'>Erro JSON: $error</p>";
        
        // Usar JSONLint para encontrar posição exata
        if (function_exists('json_last_error_msg')) {
            echo "<p>Tentando localizar erro...</p>";
            
            // Dividir em linhas menores para teste
            $lines = str_split($content, 1000);
            $position = 0;
            $accumulated = '';
            
            foreach ($lines as $index => $line) {
                $accumulated .= $line;
                $testJson = $accumulated . ']}';
                
                $test = @json_decode($testJson, true);
                if ($test === null && strlen($accumulated) > 100) {
                    $errorPos = $position + strlen($line);
                    echo "<p style='color:orange;'>Erro detectado próximo ao byte: $errorPos</p>";
                    
                    $start = max(0, $errorPos - 100);
                    $end = min(strlen($content), $errorPos + 100);
                    $context = substr($content, $start, $end - $start);
                    
                    echo "<h3>Contexto (100 chars antes e depois):</h3>";
                    echo "<pre style='white-space: pre-wrap;'>" . htmlspecialchars($context) . "</pre>";
                    
                    // Mostrar bytes
                    echo "<h3>Bytes ao redor da posição do erro:</h3>";
                    echo "<pre>";
                    for ($i = max(0, $errorPos - 10); $i < min(strlen($content), $errorPos + 10); $i++) {
                        $char = $content[$i];
                        $ord = ord($char);
                        $display = ($ord >= 32 && $ord < 127) ? $char : '?';
                        echo "[$i] $ord ($display) ";
                    }
                    echo "</pre>";
                    
                    break;
                }
                $position += strlen($line);
            }
        }
    } else {
        echo "<p style='color:green;'>✓ JSON válido!</p>";
    }
    
    echo "<hr>";
}
?>
