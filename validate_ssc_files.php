<?php
/**
 * Validador de Arquivos SSC
 * 
 * Este script escaneia todos os arquivos .ssc na pasta Songs/
 * e identifica arquivos com problemas de encoding ou caracteres inválidos
 */

set_time_limit(600); // 10 minutos
ini_set('memory_limit', '512M');

$baseDir = __DIR__;
$songsDir = $baseDir . DIRECTORY_SEPARATOR . 'Songs';

// Estatísticas
$totalFiles = 0;
$validFiles = 0;
$invalidFiles = 0;
$problems = [];

/**
 * Valida um arquivo SSC verificando caracteres inválidos
 */
function validateSSCFile($filePath) {
    $content = file_get_contents($filePath);
    if ($content === false) {
        return ['valid' => false, 'error' => 'Não foi possível ler o arquivo'];
    }
    
    $issues = [];
    
    // Verificar encoding UTF-8
    if (!mb_check_encoding($content, 'UTF-8')) {
        $issues[] = 'Encoding não é UTF-8 válido';
    }
    
    // Verificar caracteres de controle inválidos
    if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $content)) {
        $issues[] = 'Contém caracteres de controle inválidos';
    }
    
    // Verificar padrões problemáticos comuns
    if (preg_match('/#NOTES:[^;]*[^01234567XxYyZzVHFMLK*BSEIa\s\n\r\t{}|,;:#\/\-\.\d]/m', $content)) {
        $issues[] = 'Contém caracteres inválidos nas notas';
    }
    
    // Verificar se tem ponto e vírgula fora do lugar
    $lines = explode("\n", $content);
    $lineNum = 0;
    foreach ($lines as $line) {
        $lineNum++;
        $trimmed = trim($line);
        
        // Verificar se linha começa com ponto e vírgula (inválido)
        if (strlen($trimmed) > 0 && $trimmed[0] === ';') {
            $issues[] = "Linha $lineNum: Começa com ponto e vírgula (;)";
        }
        
        // Verificar se tem múltiplos pontos e vírgula seguidos
        if (strpos($trimmed, ';;') !== false) {
            $issues[] = "Linha $lineNum: Múltiplos pontos e vírgula seguidos (;;)";
        }
    }
    
    return [
        'valid' => empty($issues),
        'issues' => $issues
    ];
}

/**
 * Escaneia recursivamente procurando arquivos .ssc
 */
function scanForSSCFiles($dir, &$totalFiles, &$validFiles, &$invalidFiles, &$problems) {
    $items = scandir($dir);
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        
        if (is_dir($path)) {
            scanForSSCFiles($path, $totalFiles, $validFiles, $invalidFiles, $problems);
        } elseif (is_file($path) && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'ssc') {
            $totalFiles++;
            
            $result = validateSSCFile($path);
            
            if ($result['valid']) {
                $validFiles++;
            } else {
                $invalidFiles++;
                $relativePath = str_replace($GLOBALS['baseDir'] . DIRECTORY_SEPARATOR, '', $path);
                $problems[] = [
                    'file' => $relativePath,
                    'issues' => $result['issues'] ?? ['Erro desconhecido']
                ];
            }
        }
    }
}

// HTML Header
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Validador de Arquivos SSC</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #000;
            color: #0f0;
            padding: 20px;
            line-height: 1.6;
        }
        h1 {
            color: #0ff;
            border-bottom: 2px solid #0ff;
            padding-bottom: 10px;
        }
        h2 {
            color: #ff0;
            margin-top: 30px;
        }
        .stats {
            background: #111;
            border: 1px solid #0f0;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .valid {
            color: #0f0;
        }
        .invalid {
            color: #f00;
        }
        .warning {
            color: #ff0;
        }
        .problem {
            background: #220000;
            border-left: 4px solid #f00;
            padding: 10px;
            margin: 10px 0;
        }
        .problem-file {
            color: #f00;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .problem-issue {
            color: #ff0;
            margin-left: 20px;
            font-size: 0.9em;
        }
        a {
            color: #0ff;
        }
    </style>
</head>
<body>

<h1>🔍 Validador de Arquivos SSC</h1>

<p><strong>Diretório:</strong> <?php echo htmlspecialchars($songsDir); ?></p>

<p><em>Escaneando todos os arquivos .ssc...</em></p>

<hr>

<?php

// Verificar se a pasta existe
if (!is_dir($songsDir)) {
    echo "<p class='invalid'>❌ Erro: Pasta 'Songs' não encontrada!</p>";
    exit;
}

// Escanear arquivos
echo "<p>⏳ Processando arquivos...</p>";
scanForSSCFiles($songsDir, $totalFiles, $validFiles, $invalidFiles, $problems);

// Mostrar estatísticas
?>

<div class="stats">
    <h2>📊 Estatísticas</h2>
    <p><strong>Total de arquivos .ssc:</strong> <?php echo $totalFiles; ?></p>
    <p class="valid"><strong>✅ Arquivos válidos:</strong> <?php echo $validFiles; ?> (<?php echo $totalFiles > 0 ? round(($validFiles / $totalFiles) * 100, 1) : 0; ?>%)</p>
    <p class="invalid"><strong>❌ Arquivos com problemas:</strong> <?php echo $invalidFiles; ?> (<?php echo $totalFiles > 0 ? round(($invalidFiles / $totalFiles) * 100, 1) : 0; ?>%)</p>
</div>

<?php if ($invalidFiles > 0): ?>
    <h2 class="invalid">❌ Arquivos com Problemas</h2>
    <p class="warning">Os seguintes arquivos foram identificados com problemas:</p>
    
    <?php foreach ($problems as $problem): ?>
        <div class="problem">
            <div class="problem-file">📄 <?php echo htmlspecialchars($problem['file']); ?></div>
            <?php foreach ($problem['issues'] as $issue): ?>
                <div class="problem-issue">⚠ <?php echo htmlspecialchars($issue); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
    
    <hr>
    
    <h2>💡 Recomendações</h2>
    <ul>
        <li>Verifique os arquivos listados acima</li>
        <li>Re-baixe as músicas de fontes confiáveis</li>
        <li>Remova ou corrija os arquivos corrompidos</li>
        <li>Após corrigir, regenere o <code>songs.json</code> usando <a href="generate_songs_json.php">generate_songs_json.php</a></li>
    </ul>
    
<?php else: ?>
    <h2 class="valid">✅ Tudo OK!</h2>
    <p>Todos os arquivos SSC estão válidos e sem problemas aparentes.</p>
<?php endif; ?>

<hr>

<p><a href="generate_songs_json.php">← Voltar para o gerador de songs.json</a></p>

</body>
</html>
