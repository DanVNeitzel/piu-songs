<?php
function piu_matches_pattern($pattern, $name)
{
  return fnmatch($pattern, $name, FNM_CASEFOLD);
}

function copy_dir_recursive($src, $dst)
{
  if (is_link($src)) return false;
  $dir = opendir($src);
  @mkdir($dst);
  while (false !== ($file = readdir($dir))) {
    if (($file != '.') && ($file != '..')) {
      if (is_dir($src . DIRECTORY_SEPARATOR . $file)) {
        copy_dir_recursive($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file);
      } else {
        copy($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file);
      }
    }
  }
  closedir($dir);
  return true;
}

// ADDED: função auxiliar recursiva para adicionar diretórios/arquivos ao zip
function addPathToZip($zip, $absPath, $localPath, $disallowed_patterns, &$added, $compression_level = 6, $preserve_paths = true)
{
  if (is_link($absPath)) return;
  // $absPath = caminho absoluto no servidor
  // $localPath = caminho relativo a usar dentro do zip (string)
  if (!is_readable($absPath))
    return;

  if (is_dir($absPath)) {
    // adiciona diretório vazio no zip (se necessário)
    if ($preserve_paths) {
      $zip->addEmptyDir($localPath);
    }

    $files = array_diff(scandir($absPath), ['.', '..']);
    foreach ($files as $entry) {
      // filtrar padrões proibidos pelo nome base
      foreach ($disallowed_patterns as $pattern) {
        if (piu_matches_pattern($pattern, $entry)) {
          continue 2;
        }
      }

      $childAbs = $absPath . '/' . $entry;
      $childLocal = $preserve_paths ? ($localPath === '' ? $entry : ($localPath . '/' . $entry)) : $entry;

      if (is_dir($childAbs)) {
        addPathToZip($zip, $childAbs, $childLocal, $disallowed_patterns, $added, $compression_level, $preserve_paths);
      } elseif (is_file($childAbs) && is_readable($childAbs)) {
        // adiciona arquivo preservando ou não o caminho relativo dentro do zip
        $zip->addFile($childAbs, $childLocal);
        // Definir nível de compressão para o arquivo adicionado
        $zip->setCompressionName($childLocal, ZipArchive::CM_DEFLATE, $compression_level);
        $added++;
      }
    }
  } elseif (is_file($absPath) && is_readable($absPath)) {
    $zip->addFile($absPath, $localPath);
    // Definir nível de compressão para o arquivo adicionado
    $zip->setCompressionName($localPath, ZipArchive::CM_DEFLATE, $compression_level);
    $added++;
  }
}

function is_entry_ignored($entry, $allow_show_folders, $hidden_patterns)
{
  if (in_array($entry, ['index.php', 'router.php', 'app', 'assets', 'tests'], true)) {
    return true;
  }

  // Sempre ocultar arquivos de configuração
  if ($entry === 'files_config.json' || $entry === 'zip_progress.json' || $entry === 'share.php' || $entry === 'ffmpeg.exe') {
    return true;
  }

  if (is_dir($entry) && !$allow_show_folders) {
    return true;
  }
  foreach ($hidden_patterns as $pattern) {
    if (piu_matches_pattern($pattern, $entry)) {
      return true;
    }
  }
  return false;
}

function rmrf($dir)
{
  if (is_link($dir)) { unlink($dir); return; }
  if (is_dir($dir)) {
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file)
      rmrf("$dir/$file");
    rmdir($dir);
  } else {
    unlink($dir);
  }
}
function is_recursively_deleteable($d)
{
  $stack = [$d];
  while ($dir = array_pop($stack)) {
    if (!is_readable($dir) || !is_writable($dir))
      return false;
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file)
      if (!is_link("$dir/$file") && is_dir("$dir/$file")) {
        $stack[] = "$dir/$file";
      }
  }
  return true;
}

function get_absolute_path($path)
{
    $root = dirname(__DIR__);
    $normalized = str_replace(['/', chr(92)], DIRECTORY_SEPARATOR, $path);
    $prefix = $root . DIRECTORY_SEPARATOR;
    if (strncasecmp($normalized, $prefix, strlen($prefix)) !== 0) return false;
    try { return piu_safe_path($root, substr($normalized, strlen($prefix))); }
    catch (Throwable $error) { return false; }
}

// Função para tentar reparar ou extrair dados de PNG corrompido
function tryRepairPNG($file_path)
{
  if (!file_exists($file_path) || !is_readable($file_path)) {
    return false;
  }

  $file_data = file_get_contents($file_path);
  if ($file_data === false || strlen($file_data) < 33) { // PNG mínimo tem ~33 bytes
    return false;
  }

  // Verificar se tem assinatura PNG correta
  $png_signature = substr($file_data, 0, 8);
  $expected_signature = "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A";

  if ($png_signature !== $expected_signature) {
    return false;
  }

  try {
    // Tentar encontrar e validar chunk IHDR (informações do cabeçalho)
    $ihdr_pos = strpos($file_data, 'IHDR');
    if ($ihdr_pos !== false && $ihdr_pos >= 8) {
      // Extrair dimensões do IHDR
      $length_data = substr($file_data, $ihdr_pos - 4, 4);
      $length = unpack('N', $length_data)[1];

      if ($length === 13) { // IHDR sempre tem 13 bytes
        $ihdr_data = substr($file_data, $ihdr_pos + 4, 13);
        if (strlen($ihdr_data) === 13) {
          $info = unpack('Nwidth/Nheight/Cbit_depth/Ccolor_type/Ccompression/Cfilter/Cinterlace', $ihdr_data);

          // Verificar se as dimensões são válidas
          if (
            $info['width'] > 0 && $info['height'] > 0 &&
            $info['width'] <= 32767 && $info['height'] <= 32767
          ) {

            // Tentar criar uma imagem simples com essas dimensões
            $repair_image = imagecreatetruecolor($info['width'], $info['height']);
            if ($repair_image) {
              // Tentar preservar transparência se o PNG suporta
              if ($info['color_type'] === 6 || $info['color_type'] === 4) { // PNG com alpha
                imagealphablending($repair_image, false);
                imagesavealpha($repair_image, true);
                $transparent = imagecolorallocatealpha($repair_image, 0, 0, 0, 127);
                imagefill($repair_image, 0, 0, $transparent);
              } else {
                // Preencher com branco para PNG sem transparência
                $white = imagecolorallocate($repair_image, 255, 255, 255);
                imagefill($repair_image, 0, 0, $white);
              }

              return $repair_image;
            }
          }
        }
      }
    }
  } catch (Exception $e) {
    // Falha no reparo
    return false;
  }

  return false;
}

// Função para tentar carregar PNG usando múltiplos métodos
function loadPNGRobust($file_path)
{
  // Método 1: Padrão
  $image = @imagecreatefrompng($file_path);
  if ($image) {
    return ['image' => $image, 'method' => 'imagecreatefrompng'];
  }

  // Método 2: imagecreatefromstring
  $data = file_get_contents($file_path);
  if ($data !== false) {
    $image = @imagecreatefromstring($data);
    if ($image) {
      return ['image' => $image, 'method' => 'imagecreatefromstring'];
    }
  }

  // Método 3: Tentar reparo
  $image = tryRepairPNG($file_path);
  if ($image) {
    return ['image' => $image, 'method' => 'repaired_png'];
  }

  return false;
}
function escapeFilePath($path)
{
  // No Windows, usar método mais robusto
  if (DIRECTORY_SEPARATOR === '\\') {
    // Primeiro, converter barras para o padrão do Windows
    $path = str_replace('/', '\\', $path);

    // Para Windows, usar formato de caminho curto se necessário
    // ou escapar adequadamente
    if (function_exists('realpath') && file_exists($path)) {
      // Se o arquivo existe, tentar usar o caminho real
      $real_path = realpath($path);
      if ($real_path !== false) {
        $path = $real_path;
      }
    }

    // Escapar aspas duplas duplicando-as
    $path = str_replace('"', '""', $path);

    // Retornar o caminho entre aspas duplas
    return '"' . $path . '"';
  } else {
    // Linux/Mac: usar escapeshellarg padrão
    return escapeshellarg($path);
  }
}

// Função para normalizar caminhos de arquivo removendo caracteres problemáticos
function sanitizeFileName($filename)
{
  // Lista expandida de caracteres problemáticos para FFmpeg no Windows
  $problematic_chars = [
    '(',
    ')',
    '[',
    ']',
    '{',
    '}',  // Parênteses e chaves
    '&',
    ';',
    '|',
    '`',
    '$',
    '<',
    '>',
    '!', // Operadores shell
    '^',
    '%',
    '~',
    '?',
    '*',       // Wildcards e escape chars
    '#',
    '@',
    '+',
    '=',           // Outros caracteres especiais
    "'",
    '"'                      // Aspas (problemáticas em alguns contextos)
  ];

  // Verificar se o arquivo tem caracteres problemáticos
  $has_problematic = false;
  $found_chars = [];

  foreach ($problematic_chars as $char) {
    if (strpos($filename, $char) !== false) {
      $has_problematic = true;
      $found_chars[] = $char;
    }
  }

  return [
    'filename' => $filename,
    'has_problematic' => $has_problematic,
    'problematic_chars' => $found_chars
  ];
}

// Função para criar link simbólico temporário se necessário
function createTempFileLink($originalPath, $tempDir)
{
  $pathInfo = pathinfo($originalPath);
  $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';

  // Criar nome temporário seguro
  $tempName = 'temp_' . uniqid() . $extension;
  $tempPath = $tempDir . DIRECTORY_SEPARATOR . $tempName;

  // Tentar criar link simbólico (Windows) ou hard link
  if (DIRECTORY_SEPARATOR === '\\') {
    // Windows: usar mklink
    $cmd = 'mklink "' . $tempPath . '" "' . $originalPath . '" 2>NUL';
    exec($cmd, $output, $return_var);

    if ($return_var !== 0) {
      // Se mklink falhar, copiar arquivo
      if (copy($originalPath, $tempPath)) {
        return ['path' => $tempPath, 'is_copy' => true];
      }
      return false;
    }
    return ['path' => $tempPath, 'is_copy' => false];
  } else {
    // Linux/Mac: usar ln
    if (link($originalPath, $tempPath)) {
      return ['path' => $tempPath, 'is_copy' => false];
    } elseif (copy($originalPath, $tempPath)) {
      return ['path' => $tempPath, 'is_copy' => true];
    }
    return false;
  }
}

function err($code, $msg)
{
  http_response_code($code);
  header("Content-Type: application/json");
  echo json_encode(['error' => ['code' => intval($code), 'msg' => $msg]]);
  exit;
}

function asBytes($ini_v)
{
  $ini_v = trim($ini_v);
  $s = ['g' => 1 << 30, 'm' => 1 << 20, 'k' => 1 << 10];
  return intval($ini_v) * ($s[strtolower(substr($ini_v, -1))] ?? 1);
}

function formatTime($seconds)
{
  if ($seconds < 1) {
    return round($seconds * 1000) . 'ms';
  } elseif ($seconds < 60) {
    return round($seconds, 1) . 's';
  } elseif ($seconds < 3600) {
    $minutes = floor($seconds / 60);
    $secs = $seconds % 60;
    return $minutes . 'm ' . round($secs) . 's';
  } else {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    return $hours . 'h ' . $minutes . 'm ' . round($secs) . 's';
  }
}

