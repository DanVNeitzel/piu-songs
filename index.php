<?php
require_once __DIR__ . '/app/security.php';
require_once __DIR__ . '/app/filesystem.php';
require __DIR__ . '/app/config.php';
require __DIR__ . '/app/manager_login.php';
piu_require_auth();
$piuMutations = ['cloudupload', 'clouddownload', 'cloudsync', 'restoretrash', 'deletetrash', 'restorealltrash', 'emptytrash', 'delete', 'mkdir', 'createfile', 'upload', 'saveconfig', 'savecloudconfig', 'generatesharelink', 'deletesharelink', 'extractzip', 'savefile', 'zip', 'rename', 'movecopy', 'convertimages', 'convertaudio', 'compressfiles'];
$piuAction = $_GET['do'] ?? $_POST['do'] ?? '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' || in_array($piuAction, $piuMutations, true)) piu_require_post();
piu_csrf_token();
if (in_array($piuAction, ['deletetrash', 'emptytrash', 'delete'], true) && !$allow_delete) piu_http_error(403, 'Deletion disabled.');
foreach (['file', 'folder', 'path', 'local_path', 'local_folder', 'dest', 'extract_to'] as $key) {
  foreach ([$_GET[$key] ?? null, $_POST[$key] ?? null] as $value) {
    if ($value !== null) {
        if (!is_string($value)) piu_http_error(400, 'Invalid path.');
        try { piu_safe_path(__DIR__, $value); }
        catch (Throwable $error) { piu_http_error(403, $error->getMessage()); }
    }
  }
}
if (isset($_POST['files']) && !is_array($_POST['files'])) piu_http_error(400, 'Invalid file list.');
foreach (($_POST['files'] ?? []) as $selectedPath) {
    if (!is_string($selectedPath)) piu_http_error(400, 'Invalid file list.');
    try { piu_safe_path(__DIR__, $selectedPath); }
    catch (Throwable $error) { piu_http_error(403, $error->getMessage()); }
}
// Inicialização de variáveis para evitar warnings
$do = $_GET['do'] ?? $_POST['do'] ?? '';
$file = $_GET['file'] ?? $_POST['file'] ?? '';

// Endpoint para informações da pasta
if (isset($do) && ($do ?? '') === 'folderinfo') {
  $folder = $_GET['folder'] ?? '';
  if ($folder === '' || !is_dir($folder)) {
    echo json_encode(['success' => false]);
    exit;
  }
  $name = basename($folder);
  $size = 0;
  $files = 0;
  date_default_timezone_set('America/Sao_Paulo');
  $modified = date('d/m/Y H:i:s', filemtime($folder));
  $dirs = 0;
  $rii = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
  );
  foreach ($rii as $entry) {
    if ($entry->isLink()) continue;
    if ($entry->isDir()) $dirs++;
    elseif ($entry->isFile()) { $files++; $size += $entry->getSize(); }
  }
  // Formata tamanho
  function formatBytes($bytes, $precision = 2)
  {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
  }
  echo json_encode([
    'success' => true,
    'name' => $name,
    'size' => formatBytes($size),
    'modified' => $modified,
    'files' => $files,
    'dirs' => $dirs
  ]);
  exit;
}

// --- INTEGRAÇÃO COM ARMAZENAMENTO EM NUVEM ---

// Classe para integração com Google Drive
require_once __DIR__ . '/app/cloud.php';

// Endpoint para listar arquivos da nuvem
if (isset($do) && ($do ?? '') === 'cloudlist') {
  $provider = $_GET['provider'] ?? '';
  $path = $_GET['path'] ?? '';

  if (!isset($cloud_integrations[$provider]) || !$cloud_integrations[$provider]['enabled']) {
    err(400, 'Provedor de nuvem não configurado ou desabilitado.');
  }

  $files = [];

  try {
    switch ($provider) {
      case 'google_drive':
        $api = new GoogleDriveAPI($cloud_integrations[$provider]);
        $response = $api->listFiles($path ?: 'root');
        if ($response && isset($response['files'])) {
          foreach ($response['files'] as $file) {
            $files[] = [
              'id' => $file['id'],
              'name' => $file['name'],
              'is_dir' => $file['mimeType'] === 'application/vnd.google-apps.folder',
              'size' => isset($file['size']) ? intval($file['size']) : 0,
              'modified' => isset($file['modifiedTime']) ? $file['modifiedTime'] : '',
              'mime_type' => $file['mimeType']
            ];
          }
        }
        break;

      case 'dropbox':
        $api = new DropboxAPI($cloud_integrations[$provider]);
        $response = $api->listFiles($path);
        if ($response && isset($response['entries'])) {
          foreach ($response['entries'] as $file) {
            $files[] = [
              'id' => $file['path_lower'],
              'name' => $file['name'],
              'is_dir' => $file['.tag'] === 'folder',
              'size' => isset($file['size']) ? $file['size'] : 0,
              'modified' => isset($file['client_modified']) ? $file['client_modified'] : '',
              'mime_type' => $file['.tag'] === 'folder' ? 'folder' : 'file'
            ];
          }
        }
        break;

      case 'onedrive':
        $api = new OneDriveAPI($cloud_integrations[$provider]);
        $response = $api->listFiles($path);
        if ($response && isset($response['value'])) {
          foreach ($response['value'] as $file) {
            $files[] = [
              'id' => $file['id'],
              'name' => $file['name'],
              'is_dir' => isset($file['folder']),
              'size' => isset($file['size']) ? $file['size'] : 0,
              'modified' => isset($file['lastModifiedDateTime']) ? $file['lastModifiedDateTime'] : '',
              'mime_type' => isset($file['folder']) ? 'folder' : (isset($file['file']) ? $file['file']['mimeType'] : 'file')
            ];
          }
        }
        break;

      default:
        err(400, 'Provedor de nuvem inválido.');
    }

    echo json_encode(['success' => true, 'files' => $files, 'provider' => $provider]);
  } catch (Exception $e) {
    err(500, 'Erro ao listar arquivos da nuvem: ' . $e->getMessage());
  }
  exit;
}

// Endpoint para upload para nuvem
if (isset($do) && $do === 'cloudupload') {
  $provider = $_POST['provider'] ?? '';
  $files = $_POST['files'] ?? [];
  $cloud_path = $_POST['cloud_path'] ?? '';

  if (!isset($cloud_integrations[$provider]) || !$cloud_integrations[$provider]['enabled']) {
    err(400, 'Provedor de nuvem não configurado ou desabilitado.');
  }

  if (!is_array($files) || empty($files)) {
    err(400, 'Nenhum arquivo especificado para upload.');
  }

  $results = [];

  try {
    foreach ($files as $file) {
      $local_path = __DIR__ . '/' . $file;
      if (!file_exists($local_path)) {
        $results[] = ['file' => $file, 'success' => false, 'error' => 'Arquivo local não encontrado'];
        continue;
      }

      $filename = basename($file);
      $success = false;

      switch ($provider) {
        case 'google_drive':
          $api = new GoogleDriveAPI($cloud_integrations[$provider]);
          $response = $api->uploadFile($local_path, $filename, $cloud_path ?: 'root');
          $success = $response !== false;
          break;

        case 'dropbox':
          $api = new DropboxAPI($cloud_integrations[$provider]);
          $dropbox_path = ($cloud_path ? rtrim($cloud_path, '/') : '') . '/' . $filename;
          $response = $api->uploadFile($local_path, $dropbox_path);
          $success = $response !== false;
          break;

        case 'onedrive':
          $api = new OneDriveAPI($cloud_integrations[$provider]);
          $onedrive_path = ($cloud_path ? rtrim($cloud_path, '/') : '') . '/' . $filename;
          $response = $api->uploadFile($local_path, $onedrive_path);
          $success = $response !== false;
          break;
      }

      $results[] = ['file' => $file, 'success' => $success];
    }

    echo json_encode(['success' => true, 'results' => $results]);
  } catch (Exception $e) {
    err(500, 'Erro ao fazer upload para nuvem: ' . $e->getMessage());
  }
  exit;
}

// Endpoint para download da nuvem
if (isset($do) && $do === 'clouddownload') {
  $provider = $_POST['provider'] ?? '';
  $cloud_files = $_POST['cloud_files'] ?? [];
  $local_path = $_POST['local_path'] ?? '.';

  if (!isset($cloud_integrations[$provider]) || !$cloud_integrations[$provider]['enabled']) {
    err(400, 'Provedor de nuvem não configurado ou desabilitado.');
  }

  if (!is_array($cloud_files) || empty($cloud_files)) {
    err(400, 'Nenhum arquivo especificado para download.');
  }

  $results = [];

  try {
    foreach ($cloud_files as $cloud_file) {
      $file_id = $cloud_file['id'];
      $filename = $cloud_file['name'];
      $local_file_path = piu_safe_path(__DIR__, $local_path . '/' . $filename);

      // Garantir que o diretório local existe
      $local_dir = dirname($local_file_path);
      if (!is_dir($local_dir)) {
        @mkdir($local_dir, 0777, true);
      }

      $success = false;

      switch ($provider) {
        case 'google_drive':
          $api = new GoogleDriveAPI($cloud_integrations[$provider]);
          $success = $api->downloadFile($file_id, $local_file_path);
          break;

        case 'dropbox':
          $api = new DropboxAPI($cloud_integrations[$provider]);
          $success = $api->downloadFile($file_id, $local_file_path);
          break;

        case 'onedrive':
          $api = new OneDriveAPI($cloud_integrations[$provider]);
          $success = $api->downloadFile($file_id, $local_file_path);
          break;
      }

      $results[] = ['file' => $filename, 'success' => $success];
    }

    echo json_encode(['success' => true, 'results' => $results]);
  } catch (Exception $e) {
    err(500, 'Erro ao fazer download da nuvem: ' . $e->getMessage());
  }
  exit;
}

// Endpoint para sincronizar com nuvem
if (isset($do) && $do === 'cloudsync') {
  $provider = $_POST['provider'] ?? '';
  $sync_type = $_POST['sync_type'] ?? 'both'; // 'upload', 'download', 'both'
  $local_folder = $_POST['local_folder'] ?? '.';
  $cloud_folder = $_POST['cloud_folder'] ?? '';

  if (!isset($cloud_integrations[$provider]) || !$cloud_integrations[$provider]['enabled']) {
    err(400, 'Provedor de nuvem não configurado ou desabilitado.');
  }

  $results = ['uploaded' => [], 'downloaded' => []];

  try {
    // Implementação básica de sincronização
    // Aqui você pode implementar lógica mais complexa de sincronização

    echo json_encode(['success' => true, 'results' => $results, 'message' => 'Sincronização iniciada']);
  } catch (Exception $e) {
    err(500, 'Erro na sincronização: ' . $e->getMessage());
  }
  exit;
}
// Caminho absoluto da lixeira
$TRASH_DIR = __DIR__ . '/.trash';
$TRASH_META = $TRASH_DIR . '/meta.json';

// Garante que a lixeira e o meta existem
if (!file_exists($TRASH_DIR))
  @mkdir($TRASH_DIR, 0777, true);
if (!file_exists($TRASH_META))
  @file_put_contents($TRASH_META, '{}');

// Listar arquivos da lixeira
if (isset($do) && ($do ?? '') === 'listtrash') {
  $meta = @json_decode(@file_get_contents($TRASH_META), true) ?: [];
  $results = [];
  foreach ($meta as $trash_name => $info) {
    $trash_path = $TRASH_DIR . '/' . $trash_name;
    if (file_exists($trash_path)) {
      $results[] = [
        'trash_name' => $trash_name,
        'name' => $info['name'],
        'original' => $info['original'],
        'deleted_at' => $info['deleted_at'],
        'is_dir' => isset($info['is_dir']) ? $info['is_dir'] : is_dir($trash_path),
      ];
    }
  }
  echo json_encode(['success' => true, 'results' => $results]);
  exit;
}

// Restaurar arquivo/pasta da lixeira
if (isset($do) && $do === 'restoretrash') {
  $meta = @json_decode(@file_get_contents($TRASH_META), true) ?: [];
  $trash = isset($_POST['trash']) ? $_POST['trash'] : '';
  if (!isset($meta[$trash]))
    err(404, 'Item não encontrado na lixeira.');
  $trash_path = $TRASH_DIR . '/' . $trash;
  $original = $meta[$trash]['original'];
  $target = __DIR__ . '/' . $original;
  // Garante que o diretório de destino existe
  $parent = dirname($target);
  if (!is_dir($parent))
    @mkdir($parent, 0777, true);
  // Não sobrescreve se já existir
  if (file_exists($target))
    err(409, 'Já existe um arquivo/pasta no destino original.');
  $ok = @rename($trash_path, $target);
  if ($ok) {
    unset($meta[$trash]);
    @file_put_contents($TRASH_META, json_encode($meta, JSON_PRETTY_PRINT));
    echo json_encode(['success' => true]);
  } else {
    err(500, 'Falha ao restaurar.');
  }
  exit;
}

// Excluir permanentemente arquivo/pasta da lixeira
if (isset($do) && $do === 'deletetrash') {
  $meta = @json_decode(@file_get_contents($TRASH_META), true) ?: [];
  $trash = isset($_POST['trash']) ? $_POST['trash'] : '';
  if (!isset($meta[$trash]))
    err(404, 'Item não encontrado na lixeira.');
  $trash_path = $TRASH_DIR . '/' . $trash;
  if (is_dir($trash_path)) {
    rmrf($trash_path);
  } else {
    @unlink($trash_path);
  }
  unset($meta[$trash]);
  @file_put_contents($TRASH_META, json_encode($meta, JSON_PRETTY_PRINT));
  echo json_encode(['success' => true]);
  exit;
}

// Restaurar todos os itens da lixeira
if (isset($do) && $do === 'restorealltrash') {
  $meta = @json_decode(@file_get_contents($TRASH_META), true) ?: [];
  $restored = 0;
  foreach ($meta as $trash => $info) {
    $trash_path = $TRASH_DIR . '/' . $trash;
    $original = $info['original'];
    $target = __DIR__ . '/' . $original;
    $parent = dirname($target);
    if (!is_dir($parent))
      @mkdir($parent, 0777, true);
    if (!file_exists($target) && file_exists($trash_path)) {
      if (@rename($trash_path, $target)) {
        unset($meta[$trash]);
        $restored++;
      }
    }
  }
  @file_put_contents($TRASH_META, json_encode($meta, JSON_PRETTY_PRINT));
  echo json_encode(['success' => true, 'restored' => $restored]);
  exit;
}

// Esvaziar a lixeira (excluir todos permanentemente)
if (isset($do) && $do === 'emptytrash') {
  $meta = @json_decode(@file_get_contents($TRASH_META), true) ?: [];
  $deleted = 0;
  foreach ($meta as $trash => $info) {
    $trash_path = $TRASH_DIR . '/' . $trash;
    if (file_exists($trash_path)) {
      if (is_dir($trash_path)) {
        rmrf($trash_path);
      } else {
        @unlink($trash_path);
      }
      $deleted++;
    }
    unset($meta[$trash]);
  }
  @file_put_contents($TRASH_META, json_encode($meta, JSON_PRETTY_PRINT));
  echo json_encode(['success' => true, 'deleted' => $deleted]);
  exit;
}

// Ao excluir, mover para lixeira (ajuste no endpoint de delete)

// Deve estar em UTF-8 ou `basename` não funcionará
setlocale(LC_ALL, 'pt_BR.UTF-8');

$tmp_dir = dirname($_SERVER['SCRIPT_FILENAME']);
if (DIRECTORY_SEPARATOR === '\\')
  $tmp_dir = str_replace('/', DIRECTORY_SEPARATOR, $tmp_dir);
$tmp = get_absolute_path($tmp_dir . '/' . $file);

if ($tmp === false)
  err(404, 'File or Directory Not Found');
if (substr($tmp, 0, strlen($tmp_dir)) !== $tmp_dir)
  err(403, "Forbidden");
if (strpos($file, DIRECTORY_SEPARATOR) === 0)
  err(403, "Forbidden");
if (preg_match('@^.+://@', $file)) {
  err(403, "Forbidden");
}


$file = $file ?: '.';

// ADDED: endpoint de logout — encerra sessão e redireciona para tela de senha
if (isset($do) && ($do ?? '') === 'logout') {
  // Always start session before manipulating it
  if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
  }
  session_unset();
  session_destroy();
  // Expire session cookie
  if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
      session_name(),
      '',
      time() - 42000,
      $params["path"],
      $params["domain"],
      $params["secure"],
      $params["httponly"]
    );
  }
  header('Location: ?');
  exit;
}

if (($do ?? '') == 'list') {
  if (is_dir($file)) {
    $directory = $file;
    $result = [];
    $files = array_diff(scandir($directory), ['.', '..']);
    foreach ($files as $entry) {
      if (!is_entry_ignored($entry, $allow_show_folders, $hidden_patterns)) {
        $i = $directory . '/' . $entry;
        if (is_link($i)) continue;
        $stat = stat($i);
        $result[] = [
          'mtime' => $stat['mtime'],
          'size' => $stat['size'],
          'name' => basename($i),
          'path' => preg_replace('@^\./@', '', $i),
          'is_dir' => is_dir($i),
          'is_deleteable' => $allow_delete && ((!is_dir($i) && is_writable($directory)) ||
            (is_dir($i) && is_writable($directory) && is_recursively_deleteable($i))),
          'is_readable' => is_readable($i),
          'is_writable' => is_writable($i),
          'is_executable' => is_executable($i),
        ];
      }
    }
    usort($result, function ($f1, $f2) {
      $f1_key = ($f1['is_dir'] ?: 2) . $f1['name'];
      $f2_key = ($f2['is_dir'] ?: 2) . $f2['name'];
      return $f1_key > $f2_key;
    });
  } else {
    err(412, "Não é um diretório");
  }
  echo json_encode(['success' => true, 'is_writable' => is_writable($file), 'results' => $result]);
  exit;
} elseif (($do ?? '') == 'listfolders') {
  // Endpoint para listar todas as pastas recursivamente
  if (is_dir($file)) {
    $result = [];

    function scanFoldersRecursively($dir, &$result, $allow_show_folders, $hidden_patterns)
    {
      if (!is_readable($dir))
        return;

      $files = @scandir($dir);
      if ($files === false)
        return;

      $files = array_diff($files, ['.', '..']);
      foreach ($files as $entry) {
        if (!is_entry_ignored($entry, $allow_show_folders, $hidden_patterns)) {
          $fullPath = $dir . '/' . $entry;
          if (is_link($fullPath)) continue;
          if (is_dir($fullPath)) {
            $relativePath = preg_replace('@^\./@', '', $fullPath);
            $result[] = [
              'name' => basename($fullPath),
              'path' => $relativePath,
              'is_dir' => true,
              'is_writable' => is_writable($fullPath)
            ];
            // Recursivamente escanear subpastas
            scanFoldersRecursively($fullPath, $result, $allow_show_folders, $hidden_patterns);
          }
        }
      }
    }

    scanFoldersRecursively($file, $result, $allow_show_folders, $hidden_patterns);

    // Ordenar por caminho
    usort($result, function ($a, $b) {
      return strcmp($a['path'], $b['path']);
    });

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'folders' => $result, 'count' => count($result), 'base_path' => $file]);
  } else {
    err(412, "Não é um diretório");
  }
  exit;
} elseif (($do ?? '') == 'search') {
  // Endpoint para pesquisar arquivos e pastas
  $query = isset($_GET['query']) ? trim($_GET['query']) : '';
  $extensions = isset($_GET['extensions']) ? $_GET['extensions'] : '';
  $include_subfolders = isset($_GET['include_subfolders']) ? (bool) $_GET['include_subfolders'] : true;
  $current_path = isset($_GET['path']) ? $_GET['path'] : '.';

  // Validar o diretório base
  if (!is_dir($current_path)) {
    err(412, "Diretório não encontrado");
  }

  $result = [];
  $searched_files = 0;
  $searched_folders = 0;

  function searchFiles($dir, $query, $extensions, $include_subfolders, &$result, &$searched_files, &$searched_folders, $allow_show_folders, $hidden_patterns)
  {
    if (!is_readable($dir))
      return;

    $files = @scandir($dir);
    if ($files === false)
      return;

    $files = array_diff($files, ['.', '..']);
    $extension_array = $extensions ? array_map('trim', explode(',', strtolower($extensions))) : [];

    foreach ($files as $entry) {
      if (is_entry_ignored($entry, $allow_show_folders, $hidden_patterns))
        continue;

      $fullPath = $dir . '/' . $entry;
      if (is_link($fullPath)) continue;
      $isDir = is_dir($fullPath);

      if ($isDir) {
        $searched_folders++;
      } else {
        $searched_files++;
      }

      // Verificar se corresponde à pesquisa
      $matches_name = empty($query) || stripos($entry, $query) !== false;
      $matches_extension = true;

      if (!$isDir && !empty($extension_array)) {
        $file_ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
        $matches_extension = in_array($file_ext, $extension_array);
      }

      if ($matches_name && $matches_extension) {
        $stat = stat($fullPath);
        $relativePath = preg_replace('@^\./@', '', $fullPath);

        $result[] = [
          'mtime' => $stat['mtime'],
          'size' => $stat['size'],
          'name' => basename($fullPath),
          'path' => $relativePath,
          'dir_path' => dirname($relativePath),
          'is_dir' => $isDir,
          'is_deleteable' => ((!$isDir && is_writable($dir)) || ($isDir && is_writable($dir) && is_recursively_deleteable($fullPath))),
          'is_readable' => is_readable($fullPath),
          'is_writable' => is_writable($fullPath),
          'is_executable' => is_executable($fullPath),
          'highlight_query' => $query
        ];
      }

      // Pesquisar recursivamente em subpastas se solicitado
      if ($isDir && $include_subfolders) {
        searchFiles($fullPath, $query, $extensions, $include_subfolders, $result, $searched_files, $searched_folders, $allow_show_folders, $hidden_patterns);
      }
    }
  }

  searchFiles($current_path, $query, $extensions, $include_subfolders, $result, $searched_files, $searched_folders, $allow_show_folders, $hidden_patterns);

  // Ordenar resultados: pastas primeiro, depois arquivos, ambos por nome
  usort($result, function ($f1, $f2) {
    if ($f1['is_dir'] !== $f2['is_dir']) {
      return $f1['is_dir'] ? -1 : 1;
    }
    return strcasecmp($f1['name'], $f2['name']);
  });

  header('Content-Type: application/json');
  echo json_encode([
    'success' => true,
    'results' => $result,
    'stats' => [
      'total_found' => count($result),
      'files_searched' => $searched_files,
      'folders_searched' => $searched_folders,
      'query' => $query,
      'extensions' => $extensions,
      'include_subfolders' => $include_subfolders,
      'search_path' => $current_path
    ]
  ]);
  exit;
} elseif ($do == 'delete') {
  if ($allow_delete) {
    // Mover para lixeira
    $trash_id = uniqid('trash_', true);
    $basename = basename($file);
    // Função para normalizar nome (remover acentos e caracteres especiais)
    function normalize_filename($str)
    {
      // Remove acentos
      $str = preg_replace('/[áàãâä]/ui', 'a', $str);
      $str = preg_replace('/[éèêë]/ui', 'e', $str);
      $str = preg_replace('/[íìîï]/ui', 'i', $str);
      $str = preg_replace('/[óòõôö]/ui', 'o', $str);
      $str = preg_replace('/[úùûü]/ui', 'u', $str);
      $str = preg_replace('/[ç]/ui', 'c', $str);
      // Remove outros caracteres especiais
      $str = preg_replace('/[^a-z0-9_\.-]/i', '_', $str);
      return $str;
    }
    $normalized = normalize_filename($basename);
    $trash_name = $trash_id . '_' . $normalized;
    $meta = @json_decode(@file_get_contents($TRASH_META), true) ?: [];
    $trash_path = $TRASH_DIR . '/' . $trash_name;
    $ok = @rename($file, $trash_path);
    if ($ok) {
      // Horário UTC-3 (Brasília)
      $dt = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
      $meta[$trash_name] = [
        'name' => $basename,
        'original' => $file,
        'deleted_at' => $dt->format('Y-m-d H:i:s'),
      ];
      @file_put_contents($TRASH_META, json_encode($meta, JSON_PRETTY_PRINT));
    } else {
      // Se não conseguiu mover, tenta excluir normalmente
      rmrf($file);
    }
  }
  exit;
} elseif ($do == 'mkdir' && $allow_create_folder) {
  // don't allow actions outside root. we also filter out slashes to catch args like './../outside'
  $dir = $_POST['name'] ?? '';
  if (!is_string($dir) || $dir === '' || $dir === '.' || $dir === '..' || strpbrk($dir, '/\\') !== false) err(400, 'Nome de pasta inválido.');
  try { $destination = piu_safe_path(__DIR__, $file . '/' . $dir); }
  catch (Throwable $error) { err(403, $error->getMessage()); }
  if (!mkdir($destination)) err(500, 'Falha ao criar pasta.');
  echo json_encode(['success' => true]);
  exit;
}

// ADDED: endpoint para criar novo arquivo de texto
elseif ($do == 'createfile' && $allow_create_file) {
  $folder = isset($file) && $file !== '' ? $file : '.';
  if (strpos($folder, '..') !== false)
    err(403, "Pasta proibida.");

  $name = isset($_POST['name']) ? trim($_POST['name']) : '';
  if ($name === '')
    err(400, "Nome do arquivo ausente.");

  // proibir barras, backslashes e parent traversal
  if (strpos($name, '/') !== false || strpos($name, '\\') !== false || strpos($name, '..') !== false)
    err(400, "Nome de arquivo inválido.");

  // checar padrões proibidos
  foreach ($disallowed_patterns as $pattern)
    if (piu_matches_pattern($pattern, $name))
      err(403, "Arquivos deste tipo não são permitidos.");

  $tmp_dir = dirname($_SERVER['SCRIPT_FILENAME']);
  if (DIRECTORY_SEPARATOR === '\\')
    $tmp_dir = str_replace('/', DIRECTORY_SEPARATOR, $tmp_dir);

  $abs = get_absolute_path($tmp_dir . '/' . $folder . '/' . $name);
  if ($abs === false)
    err(500, "Falha ao construir o caminho.");

  if (substr($abs, 0, strlen($tmp_dir)) !== $tmp_dir)
    err(403, "Proibido");

  if (file_exists($abs))
    err(409, "Arquivo já existe.");

  $parent_dir = dirname($abs);
  if (!is_writable($parent_dir))
    err(403, "Diretório pai não é gravável.");

  $content = isset($_POST['content']) ? $_POST['content'] : '';
  $res = @file_put_contents($abs, $content, LOCK_EX);
  if ($res === false)
    err(500, "Falha ao criar o arquivo.");

  echo json_encode(['success' => true, 'file' => ($folder === '.' ? $name : ($folder . '/' . $name))]);
  exit;
} elseif ($do == 'upload' && $allow_upload) {
  if (!isset($_FILES['file_data']) || ($_FILES['file_data']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) err(400, 'Upload incompleto ou acima dos limites do servidor.');
  $uploadName = $_FILES['file_data']['name'];
  if (!is_string($uploadName) || $uploadName === '' || strpbrk($uploadName, '/\\') !== false) err(400, 'Nome de arquivo inválido.');
  foreach ($disallowed_patterns as $pattern)
    if (piu_matches_pattern($pattern, $_FILES['file_data']['name']))
      err(403, "Arquivos deste tipo não são permitidos.");

  // Validar tamanho do arquivo
  $file_size = $_FILES['file_data']['size'];
  $max_size_bytes = $max_upload_size_mb * 1024 * 1024;
  if ($file_size > $max_size_bytes) {
    err(413, "Arquivo excede o tamanho máximo permitido de " . $max_upload_size_mb . "MB. Tamanho atual: " . round($file_size / 1024 / 1024, 2) . "MB");
  }

  try { $target = piu_safe_path(__DIR__, $file . '/' . $uploadName); }
  catch (Throwable $error) { err(403, $error->getMessage()); }
  $overwrite = isset($_POST['overwrite']) && $_POST['overwrite'] == '1';
  if (file_exists($target) && !$overwrite) {
    err(409, "Arquivo já existe.");
  }
  $res = move_uploaded_file($_FILES['file_data']['tmp_name'], $target);
  if ($res === false) {
    err(500, "Falha ao mover o arquivo.");
  }
  echo json_encode(['success' => true]);
  exit;
} elseif (($do ?? '') == 'download') {
  // Corrigir: garantir que $file está definido e seguro
  $download_file = isset($file) ? ($file ?? '') : '';
  $download_file = urldecode($download_file);
  if ($download_file === '' || strpos($download_file, '..') !== false || preg_match('@^.+://@', $download_file)) {
    err(400, 'Arquivo inválido para download.');
  }
  $tmp_dir = dirname($_SERVER['SCRIPT_FILENAME']);
  if (DIRECTORY_SEPARATOR === '\\')
    $tmp_dir = str_replace('/', DIRECTORY_SEPARATOR, $tmp_dir);
  $abs = get_absolute_path($tmp_dir . '/' . $download_file);
  if ($abs === false || !file_exists($abs) || !is_file($abs)) {
    err(404, 'Arquivo não encontrado para download.');
  }
  foreach ($disallowed_patterns as $pattern)
    if (piu_matches_pattern($pattern, basename($abs)))
      err(403, "Arquivos deste tipo não são permitidos.");

  $filename = basename($abs);
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  header('Content-Description: File Transfer');
  header('Content-Type: ' . finfo_file($finfo, $abs));
  $disp = 'attachment; filename="' . str_replace('"', '', $filename) . '"';
  if (preg_match('/[^\x20-\x7e]/', $filename)) {
    $disp .= "; filename*=UTF-8''" . rawurlencode($filename);
  }
  header('Content-Disposition: ' . $disp);
  header('Content-Transfer-Encoding: binary');
  header('Expires: 0');
  header('Cache-Control: must-revalidate');
  header('Pragma: public');
  header('Content-Length: ' . filesize($abs));
  flush();
  $fp = fopen($abs, 'rb');
  if ($fp) {
    while (!feof($fp)) {
      print fread($fp, 8192);
      flush();
    }
    fclose($fp);
  } else {
    err(500, 'Falha ao abrir arquivo para download.');
  }
  exit;
}
// ADDED: salvar configurações (somente se permissionAdmin == true)
// receberá campos via POST e gravará files_config.json
elseif ($do == 'saveconfig') {
  if (!$permissionAdmin)
    err(403, "Proibido");
  // construir payload seguro
  $new = is_array($cfg ?? null) ? $cfg : [];
  $new['allow_delete'] = !empty($_POST['allow_delete']) ? true : false;
  $new['allow_upload'] = !empty($_POST['allow_upload']) ? true : false;
  $new['allow_create_folder'] = !empty($_POST['allow_create_folder']) ? true : false;
  $new['allow_create_file'] = !empty($_POST['allow_create_file']) ? true : false;
  $new['allow_direct_link'] = !empty($_POST['allow_direct_link']) ? true : false;
  $new['allow_show_folders'] = !empty($_POST['allow_show_folders']) ? true : false;
  $new['configTime'] = isset($_POST['configTime']) ? intval($_POST['configTime']) : $configTime;
  $new['max_upload_size_mb'] = isset($_POST['max_upload_size_mb']) ? max(1, intval($_POST['max_upload_size_mb'])) : $max_upload_size_mb;
  // padrões como CSV -> array
  $dp = isset($_POST['disallowed_patterns']) ? trim($_POST['disallowed_patterns']) : '';
  $hp = isset($_POST['hidden_patterns']) ? trim($_POST['hidden_patterns']) : '';
  $new['disallowed_patterns'] = $dp === '' ? [] : array_values(array_filter(array_map('trim', explode(',', $dp))));
  $new['hidden_patterns'] = $hp === '' ? [] : array_values(array_filter(array_map('trim', explode(',', $hp))));
  // senha (opcional) - grava exatamente o valor recebido
  $new['SENHA'] = isset($_POST['SENHA']) ? $_POST['SENHA'] : $SENHA;
  // preservar ou atualizar permissionAdmin (se fornecido via POST, atualiza; caso contrário preserva o atual)
  if (isset($_POST['permissionAdmin'])) {
    $new['permissionAdmin'] = !empty($_POST['permissionAdmin']) ? true : false;
  } else {
    $new['permissionAdmin'] = $permissionAdmin;
  }

  $config_file = __DIR__ . '/files_config.json';
  try { piu_atomic_json($config_file, $new); $ok = true; }
  catch (Throwable $error) { $ok = false; }
  if ($ok === false) {
    err(500, "Falha ao gravar o arquivo de configuração.");
  }
  echo json_encode(['success' => true]);
}

// ADDED: endpoint para gerar link de compartilhamento externo
elseif ($do == 'generatesharelink') {
  $file = isset($file) ? $file : '';
  $password = isset($_POST['password']) ? trim($_POST['password']) : '';
  $expires_hours = isset($_POST['expires_hours']) ? intval($_POST['expires_hours']) : 24;

  if (!$file) {
    err(400, "Arquivo não especificado");
  }

  // Verificar se arquivo existe e é acessível
  $tmp_dir = dirname($_SERVER['SCRIPT_FILENAME']);
  if (DIRECTORY_SEPARATOR === '\\')
    $tmp_dir = str_replace('/', DIRECTORY_SEPARATOR, $tmp_dir);

  $abs_path = get_absolute_path($tmp_dir . '/' . $file);
  if ($abs_path === false || !file_exists($abs_path)) {
    err(404, "Arquivo não encontrado");
  }

  if (substr($abs_path, 0, strlen($tmp_dir)) !== $tmp_dir) {
    err(403, "Acesso negado");
  }

  // Criar diretório para links compartilhados se não existir
  $share_dir = __DIR__ . '/.shares';
  if (!file_exists($share_dir)) {
    @mkdir($share_dir, 0777, true);
  }

  // Gerar hash único para o link
  $share_id = bin2hex(openssl_random_pseudo_bytes(16));
  $security_hash = hash('sha256', $share_id . $file . time());

  // Calcular timestamp de expiração
  $expires_at = time() + ($expires_hours * 3600);

  // Dados do compartilhamento
  $share_data = [
    'file_path' => $file,
    'created_at' => time(),
    'expires_at' => $expires_at,
    'password' => $password ? password_hash($password, PASSWORD_DEFAULT) : null,
    'security_hash' => $security_hash,
    'downloads' => 0,
    'max_downloads' => isset($_POST['max_downloads']) ? intval($_POST['max_downloads']) : 0 // 0 = ilimitado
  ];

  // Salvar dados do compartilhamento
  $share_file = $share_dir . '/' . $share_id . '.json';
  $ok = @file_put_contents($share_file, json_encode($share_data, JSON_PRETTY_PRINT));

  if (!$ok) {
    err(500, "Falha ao criar link de compartilhamento");
  }

  // Gerar URL completa
  $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'];
  $script_name = $_SERVER['SCRIPT_NAME'];
  $base_url = $protocol . '://' . $host . dirname($script_name);

  $share_url = $base_url . '/share.php?id=' . $share_id . '&hash=' . $security_hash;

  echo json_encode([
    'success' => true,
    'share_id' => $share_id,
    'share_url' => $share_url,
    'security_hash' => $security_hash,
    'expires_at' => (new DateTime('@' . $expires_at))->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('d/m/Y H:i:s'),
    'has_password' => !empty($password)
  ]);
  exit;
}

// ADDED: endpoint para listar links de compartilhamento
elseif (($do ?? '') == 'listsharelinks') {
  $share_dir = __DIR__ . '/.shares';
  $shares = [];

  if (file_exists($share_dir)) {
    $files = glob($share_dir . '/*.json');
    foreach ($files as $share_file) {
      $data = @json_decode(@file_get_contents($share_file), true);
      if ($data) {
        $share_id = basename($share_file, '.json');
        $shares[] = [
          'id' => $share_id,
          'file_path' => $data['file_path'],
          'created_at' => (new DateTime('@' . $data['created_at']))->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('d/m/Y H:i:s'),
          'expires_at' => (new DateTime('@' . $data['expires_at']))->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('d/m/Y H:i:s'),
          'expired' => time() > $data['expires_at'],
          'has_password' => !empty($data['password']),
          'downloads' => $data['downloads'] ?? 0,
          'max_downloads' => $data['max_downloads'] ?? 0,
          'security_hash' => $data['security_hash'] ?? ''
        ];
      }
    }
  }

  echo json_encode(['success' => true, 'shares' => $shares]);
  exit;
}

// ADDED: endpoint para deletar link de compartilhamento
elseif ($do == 'deletesharelink') {
  $share_id = isset($_POST['share_id']) ? $_POST['share_id'] : '';

  if (!$share_id || !preg_match('/^[a-f0-9]{32}$/', $share_id)) {
    err(400, "ID de compartilhamento inválido");
  }

  $share_dir = __DIR__ . '/.shares';
  $share_file = $share_dir . '/' . $share_id . '.json';

  if (file_exists($share_file)) {
    @unlink($share_file);
  }

  echo json_encode(['success' => true]);
  exit;
}

// --- ADDED: endpoint para listar conteúdo de arquivo ZIP ---
elseif (($do ?? '') == 'listzip') {
  $zip_file = isset($file) ? ($file ?? '') : '';
  if ($zip_file === '' || strpos($zip_file, '..') !== false) {
    err(400, 'Arquivo ZIP inválido.');
  }

  $tmp_dir = dirname($_SERVER['SCRIPT_FILENAME']);
  if (DIRECTORY_SEPARATOR === '\\')
    $tmp_dir = str_replace('/', DIRECTORY_SEPARATOR, $tmp_dir);

  $zip_abs = get_absolute_path($tmp_dir . '/' . $zip_file);
  if ($zip_abs === false || !file_exists($zip_abs) || !is_file($zip_abs)) {
    err(404, 'Arquivo ZIP não encontrado. Caminho: ' . $zip_abs . ', Existe: ' . (file_exists($zip_abs) ? 'sim' : 'não') . ', É arquivo: ' . (is_file($zip_abs) ? 'sim' : 'não'));
  }

  if (substr($zip_abs, 0, strlen($tmp_dir)) !== $tmp_dir) {
    err(403, "Proibido");
  }

  if (!class_exists('ZipArchive')) {
    err(500, "ZipArchive não está disponível no servidor.");
  }

  $zip = new ZipArchive();
  $result = $zip->open($zip_abs);
  if ($result !== true) {
    $errorMessages = [
      ZipArchive::ER_OK => 'Sem erro',
      ZipArchive::ER_MULTIDISK => 'Arquivo multi-disco não suportado',
      ZipArchive::ER_RENAME => 'Erro de renomeação',
      ZipArchive::ER_CLOSE => 'Erro ao fechar arquivo',
      ZipArchive::ER_SEEK => 'Erro de busca',
      ZipArchive::ER_READ => 'Erro de leitura',
      ZipArchive::ER_WRITE => 'Erro de escrita',
      ZipArchive::ER_CRC => 'Erro de CRC',
      ZipArchive::ER_ZIPCLOSED => 'ZIP fechado',
      ZipArchive::ER_NOENT => 'Arquivo não existe',
      ZipArchive::ER_EXISTS => 'Arquivo já existe',
      ZipArchive::ER_OPEN => 'Não é possível abrir arquivo',
      ZipArchive::ER_TMPOPEN => 'Falha ao criar arquivo temporário',
      ZipArchive::ER_ZLIB => 'Erro Zlib',
      ZipArchive::ER_MEMORY => 'Erro de memória',
      ZipArchive::ER_CHANGED => 'Entrada alterada',
      ZipArchive::ER_COMPNOTSUPP => 'Método de compressão não suportado',
      ZipArchive::ER_EOF => 'Final do arquivo prematuro',
      ZipArchive::ER_INVAL => 'Argumento inválido',
      ZipArchive::ER_NOZIP => 'Não é um arquivo ZIP',
      ZipArchive::ER_INTERNAL => 'Erro interno',
      ZipArchive::ER_INCONS => 'ZIP inconsistente',
      ZipArchive::ER_REMOVE => 'Não é possível remover arquivo',
      ZipArchive::ER_DELETED => 'Entrada deletada'
    ];
    $errorMsg = isset($errorMessages[$result]) ? $errorMessages[$result] : 'Erro desconhecido código: ' . $result;
    err(500, "Falha ao abrir arquivo ZIP: " . $errorMsg);
  }

  $files = [];
  for ($i = 0; $i < $zip->numFiles; $i++) {
    $entry = $zip->getNameIndex($i);
    $stat = $zip->statIndex($i);

    $files[] = [
      'name' => $entry,
      'size' => $stat['size'],
      'compressed_size' => $stat['comp_size'],
      'modified' => (new DateTime('@' . $stat['mtime']))->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('d/m/Y h:i A'),
      'is_dir' => substr($entry, -1) === '/',
      'method' => $stat['comp_method']
    ];
  }

  $zip->close();

  header('Content-Type: application/json');
  echo json_encode([
    'success' => true,
    'zip_file' => $zip_file,
    'files' => $files,
    'total_files' => count($files)
  ]);
  exit;
}

// --- ADDED: endpoint para extrair arquivo ZIP ---
elseif ($do == 'extractzip') {
  $zip_file = isset($file) ? $file : '';
  $extract_to = isset($_POST['extract_to']) ? trim($_POST['extract_to']) : '';

  if ($zip_file === '' || strpos($zip_file, '..') !== false) {
    err(400, 'Arquivo ZIP inválido.');
  }

  if ($extract_to === '') {
    err(400, 'Nome da pasta de destino não especificado.');
  }

  // Sanitizar nome da pasta
  $extract_to = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $extract_to);
  if (strpos($extract_to, '/') !== false || strpos($extract_to, '\\') !== false || strpos($extract_to, '..') !== false) {
    err(400, 'Nome de pasta inválido.');
  }

  $tmp_dir = dirname($_SERVER['SCRIPT_FILENAME']);
  if (DIRECTORY_SEPARATOR === '\\')
    $tmp_dir = str_replace('/', DIRECTORY_SEPARATOR, $tmp_dir);

  $zip_abs = get_absolute_path($tmp_dir . '/' . $zip_file);
  if ($zip_abs === false || !file_exists($zip_abs) || !is_file($zip_abs)) {
    err(404, 'Arquivo ZIP não encontrado.');
  }

  if (substr($zip_abs, 0, strlen($tmp_dir)) !== $tmp_dir) {
    err(403, "Proibido");
  }

  // Diretório onde o ZIP está localizado
  $zip_dir = dirname($zip_abs);
  $extract_path = $zip_dir . DIRECTORY_SEPARATOR . $extract_to;

  if (file_exists($extract_path)) {
    err(409, 'Pasta de destino já existe.');
  }

  if (!is_writable($zip_dir)) {
    err(403, 'Diretório não é gravável.');
  }

  if (!class_exists('ZipArchive')) {
    err(500, "ZipArchive não está disponível no servidor.");
  }

  $zip = new ZipArchive();
  $result = $zip->open($zip_abs);
  if ($result !== true) {
    $errorMessages = [
      ZipArchive::ER_OK => 'Sem erro',
      ZipArchive::ER_MULTIDISK => 'Arquivo multi-disco não suportado',
      ZipArchive::ER_RENAME => 'Erro de renomeação',
      ZipArchive::ER_CLOSE => 'Erro ao fechar arquivo',
      ZipArchive::ER_SEEK => 'Erro de busca',
      ZipArchive::ER_READ => 'Erro de leitura',
      ZipArchive::ER_WRITE => 'Erro de escrita',
      ZipArchive::ER_CRC => 'Erro de CRC',
      ZipArchive::ER_ZIPCLOSED => 'ZIP fechado',
      ZipArchive::ER_NOENT => 'Arquivo não existe',
      ZipArchive::ER_EXISTS => 'Arquivo já existe',
      ZipArchive::ER_OPEN => 'Não é possível abrir arquivo',
      ZipArchive::ER_TMPOPEN => 'Falha ao criar arquivo temporário',
      ZipArchive::ER_ZLIB => 'Erro Zlib',
      ZipArchive::ER_MEMORY => 'Erro de memória',
      ZipArchive::ER_CHANGED => 'Entrada alterada',
      ZipArchive::ER_COMPNOTSUPP => 'Método de compressão não suportado',
      ZipArchive::ER_EOF => 'Final do arquivo prematuro',
      ZipArchive::ER_INVAL => 'Argumento inválido',
      ZipArchive::ER_NOZIP => 'Não é um arquivo ZIP',
      ZipArchive::ER_INTERNAL => 'Erro interno',
      ZipArchive::ER_INCONS => 'ZIP inconsistente',
      ZipArchive::ER_REMOVE => 'Não é possível remover arquivo',
      ZipArchive::ER_DELETED => 'Entrada deletada'
    ];
    $errorMsg = isset($errorMessages[$result]) ? $errorMessages[$result] : 'Erro desconhecido código: ' . $result;
    err(500, "Falha ao abrir arquivo ZIP para extração: " . $errorMsg);
  }

  // Criar diretório de destino
  if (!@mkdir($extract_path, 0777, true)) {
    $zip->close();
    err(500, 'Falha ao criar diretório de destino.');
  }

  // Extrair arquivos
  $extracted = 0;
  for ($i = 0; $i < $zip->numFiles; $i++) {
    $entry_name = $zip->getNameIndex($i);

    // Validar se o caminho é seguro
    if (strpos($entry_name, '..') !== false) {
      continue; // Pular entradas inseguras
    }

    try { $target_path = piu_safe_path($extract_path, $entry_name); }
    catch (Throwable $error) { continue; }
    foreach ($disallowed_patterns as $pattern) {
      if (fnmatch(strtolower($pattern), strtolower(basename($entry_name)))) continue 2;
    }

    // Garantir que o diretório pai existe
    $parent_dir = dirname($target_path);
    if (!is_dir($parent_dir)) {
      @mkdir($parent_dir, 0777, true);
    }

    // Extrair arquivo individual
    if ($zip->extractTo($extract_path, $entry_name)) {
      $extracted++;
    }
  }

  $zip->close();

  header('Content-Type: application/json');
  echo json_encode([
    'success' => true,
    'extracted' => $extracted,
    'extract_path' => dirname($zip_file) . '/' . $extract_to
  ]);
  exit;
}

// --- ADDED: endpoint para ler arquivo (para edição) ---
elseif (($do ?? '') == 'getfile') {
  foreach ($disallowed_patterns as $pattern)
    if (piu_matches_pattern($pattern, $file))
      err(403, "Arquivos deste tipo não são permitidos.");

  if (!is_file($file) || !is_readable($file))
    err(404, "Arquivo não encontrado ou não pode ser lido");

  $max_read = 5 * 1024 * 1024; // 5MB limite de leitura via editor
  if (filesize($file) > $max_read)
    err(413, "Arquivo muito grande para editar pelo editor web.");

  $content = file_get_contents($file);
  header("Content-Type: application/json; charset=utf-8");
  echo json_encode(['success' => true, 'content' => $content, 'path' => $file]);
  exit;
}

// --- ADDED: endpoint para salvar conteúdo editado ---
elseif ($do == 'savefile') {
  foreach ($disallowed_patterns as $pattern)
    if (piu_matches_pattern($pattern, $file))
      err(403, "Arquivos deste tipo não são permitidos.");

  if (!is_file($file))
    err(404, "Arquivo não encontrado.");

  if (!is_writable($file))
    err(403, "Arquivo não é gravável.");

  $content = isset($_POST['content']) ? $_POST['content'] : '';
  $res = @file_put_contents($file, $content, LOCK_EX);
  if ($res === false)
    err(500, "Falha ao salvar o arquivo.");
  echo json_encode(['success' => true]);
  exit;
}
// --- ADDED: endpoint para criar um ZIP com arquivos selecionados (AGORA suporta diretórios recursivamente) ---
elseif ($do == 'zip') {
  if (!isset($_POST['files']) || !is_array($_POST['files']))
    err(400, "Nenhum arquivo especificado.");

  // Nome do zip opcional
  $zip_name = isset($_POST['name']) ? trim($_POST['name']) : '';
  // pasta atual (hash)
  $folder = isset($_POST['folder']) && $_POST['folder'] !== '' ? $_POST['folder'] : '.';
  if (strpos($folder, '..') !== false)
    err(403, "Pasta proibida.");

  // sanitize requested zip name
  if ($zip_name === '') {
    $zip_name = 'selected_' . time() . '.zip';
  } else {
    // garantir extensão .zip e caracter seguro (permitir espaços e acentos)
    $zip_name = basename(preg_replace('/[<>:"|?*]/', '_', $zip_name));
    if (stripos($zip_name, '.zip') === false)
      $zip_name .= '.zip';
  }

  if (!class_exists('ZipArchive'))
    err(500, "ZipArchive não está disponível no servidor.");

  // garantir que a pasta destino exista
  $dest_rel = ($folder === '.' || $folder === '') ? $zip_name : ($folder . '/' . $zip_name);
  $dest_abs = $tmp_dir . '/' . $dest_rel;
  $dest_dir = dirname($dest_abs);
  if (!is_dir($dest_dir)) {
    @mkdir($dest_dir, 0777, true);
  }

  // Progresso: arquivo temporário
  $progress_file = $tmp_dir . '/zip_progress.json';
  @file_put_contents($progress_file, json_encode([
    'status' => 'iniciando',
    'total' => count($_POST['files']),
    'current' => 0,
    'added' => 0,
    'zip' => $dest_rel
  ]));

  $zip = new ZipArchive();
  if ($zip->open($dest_abs, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true)
    err(500, "Falha ao criar o arquivo zip.");

  // ADDED: Configurar nível de compressão
  $compression_level = isset($_POST['compression_level']) ? intval($_POST['compression_level']) : 6;
  $preserve_paths = isset($_POST['preserve_paths']) && $_POST['preserve_paths'] === '1';

  // Validar nível de compressão (0-9)
  if ($compression_level < 0 || $compression_level > 9) {
    $compression_level = 6; // Padrão
  }

  $added = 0;
  $current = 0;
  foreach ($_POST['files'] as $f) {
    $current++;
    // basic checks
    if (!$f)
      continue;
    if (strpos($f, '..') !== false)
      continue;
    if (preg_match('@^.+://@', $f))
      continue;

    // proibir padrões indesejados (por base filename)
    foreach ($disallowed_patterns as $pattern) {
      if (piu_matches_pattern($pattern, basename($f))) {
        // não adiciona arquivos proibidos
        continue 2;
      }
    }

    $abs = get_absolute_path($tmp_dir . '/' . $f);
    if ($abs === false)
      continue;
    if (substr($abs, 0, strlen($tmp_dir)) !== $tmp_dir)
      continue;

    // se for diretório, adicionar recursivamente mantendo estrutura (usa basename($f) como raiz no ZIP)
    if (is_dir($abs)) {
      addPathToZip($zip, $abs, basename($f), $disallowed_patterns, $added, $compression_level, $preserve_paths);
    } elseif (is_file($abs) && is_readable($abs)) {
      // arquivo simples
      $zip->addFile($abs, basename($abs));
      // Definir nível de compressão para o arquivo adicionado
      $zip->setCompressionName(basename($abs), ZipArchive::CM_DEFLATE, $compression_level);
      $added++;
    }
    // Atualiza progresso
    @file_put_contents($progress_file, json_encode([
      'status' => 'processando',
      'total' => count($_POST['files']),
      'current' => $current,
      'added' => $added,
      'zip' => $dest_rel
    ]));
  }

  $zip->close();

  if ($added === 0) {
    // remover zip vazio
    @unlink($dest_abs);
    @file_put_contents($progress_file, json_encode([
      'status' => 'erro',
      'msg' => 'No valid files to add to zip.'
    ]));
    err(400, "No valid files to add to zip.");
  }

  @file_put_contents($progress_file, json_encode([
    'status' => 'finalizado',
    'total' => count($_POST['files']),
    'current' => $current,
    'added' => $added,
    'zip' => $dest_rel
  ]));

  echo json_encode(['success' => true, 'zip' => $dest_rel, 'added' => $added]);
  exit;
}

// Endpoint para consultar progresso do ZIP
elseif (isset($do) && ($do ?? '') === 'zipprogress') {
  $progress_file = $tmp_dir . '/zip_progress.json';
  if (file_exists($progress_file)) {
    $progress = @json_decode(@file_get_contents($progress_file), true);
    echo json_encode(['success' => true, 'progress' => $progress]);
  } else {
    echo json_encode(['success' => false, 'progress' => null]);
  }
  exit;
} elseif ($do == 'rename') {
  $old = isset($file) ? trim($file) : '';
  $newname = isset($_POST['newname']) ? trim($_POST['newname']) : '';
  if ($old === '' || $newname === '')
    err(400, "Parâmetros ausentes.");

  // proibir caminhos relativos na nova string e barras
  if (strpos($newname, '/') !== false || strpos($newname, '\\') !== false)
    err(400, "Nome novo inválido.");

  if (strpos($old, '..') !== false || preg_match('@^.+://@', $old))
    err(403, "Proibido");

  // basic disallowed extensions check for target name
  foreach ($disallowed_patterns as $pattern)
    if (piu_matches_pattern($pattern, $newname))
      err(403, "Nome de destino proibido pelo padrão.");

  $tmp_dir = dirname($_SERVER['SCRIPT_FILENAME']);
  if (DIRECTORY_SEPARATOR === '\\')
    $tmp_dir = str_replace('/', DIRECTORY_SEPARATOR, $tmp_dir);

  $old_abs = get_absolute_path($tmp_dir . '/' . $old);
  if ($old_abs === false)
    err(404, 'Arquivo ou diretório de origem não encontrado.');

  if (substr($old_abs, 0, strlen($tmp_dir)) !== $tmp_dir)
    err(403, "Proibido");

  // Ensure source exists
  if (!file_exists($old_abs))
    err(404, "Arquivo ou diretório de origem não encontrado.");

  // Ensure parent dir writable
  $parent_dir = dirname($old_abs);
  if (!is_writable($parent_dir))
    err(403, "Diretório pai não é gravável.");

  // Build new absolute path (keep same parent)
  $new_rel = ($old === '.' ? $newname : (rtrim(dirname($old), '/\\') . '/' . $newname));
  $new_abs = get_absolute_path($tmp_dir . '/' . $new_rel);
  if ($new_abs === false)
    err(500, "Falha ao construir o novo caminho.");

  if (substr($new_abs, 0, strlen($tmp_dir)) !== $tmp_dir)
    err(403, "Proibido");

  // Prevent overwrite
  if (file_exists($new_abs))
    err(409, "Arquivo ou diretório de destino já existe.");

  // perform rename
  if (!@rename($old_abs, $new_abs))
    err(500, "Falha ao renomear.");

  echo json_encode(['success' => true, 'old' => $old, 'new' => $new_rel]);
  exit;
}
// --- ADDED: handler for move/copy bulk operation ---
elseif ($do == 'movecopy') {
  if (!isset($_POST['files']) || !is_array($_POST['files']))
    err(400, "Nenhum arquivo especificado.");
  $files = $_POST['files'];
  $dest = isset($_POST['dest']) ? trim($_POST['dest']) : '';
  $action = isset($_POST['action']) ? trim($_POST['action']) : 'move';
  if ($dest === '' || strpos($dest, '..') !== false)
    err(400, "Destino inválido.");
  $tmp_dir = dirname($_SERVER['SCRIPT_FILENAME']);
  if (DIRECTORY_SEPARATOR === '\\')
    $tmp_dir = str_replace('/', DIRECTORY_SEPARATOR, $tmp_dir);
  $dest_abs = get_absolute_path($tmp_dir . '/' . $dest);
  if ($dest_abs === false || !is_dir($dest_abs) || !is_writable($dest_abs))
    err(403, "Destino não é gravável ou não é um diretório.");
  $results = [];
  foreach ($files as $f) {
    $res = ['success' => false];
    if (!$f || strpos($f, '..') !== false || preg_match('@^.+://@', $f)) {
      $res['msg'] = 'Arquivo inválido';
      $results[$f] = $res;
      continue;
    }
    $src_abs = get_absolute_path($tmp_dir . '/' . $f);
    if ($src_abs === false || !file_exists($src_abs)) {
      $res['msg'] = 'Origem não encontrada';
      $results[$f] = $res;
      continue;
    }
    $basename = basename($src_abs);
    $target_abs = $dest_abs . DIRECTORY_SEPARATOR . $basename;
    if (file_exists($target_abs)) {
      $res['msg'] = 'Destino já existe';
      $results[$f] = $res;
      continue;
    }
    if ($action === 'move') {
      if (@rename($src_abs, $target_abs)) {
        $res['success'] = true;
      } else {
        $res['msg'] = 'Falha ao mover';
      }
    } elseif ($action === 'copy') {
      if (is_dir($src_abs)) {
        if (copy_dir_recursive($src_abs, $target_abs)) {
          $res['success'] = true;
        } else {
          $res['msg'] = 'Falha ao copiar';
        }
      } else {
        if (@copy($src_abs, $target_abs)) {
          $res['success'] = true;
        } else {
          $res['msg'] = 'Falha ao copiar';
        }
      }
    } else {
      $res['msg'] = 'Ação desconhecida';
    }
    $results[$f] = $res;
  }
  echo json_encode(['success' => true, 'results' => $results]);
  exit;
}

// ADDED: endpoint para conversão e compressão de imagens - VERSÃO CORRIGIDA
elseif ($do == 'convertimages') {
  try {
    if (!isset($_POST['files']) || !is_array($_POST['files']))
      err(400, "Nenhuma imagem especificada.");

    $files = $_POST['files'];
    $format = isset($_POST['format']) ? $_POST['format'] : 'webp';
    $quality = isset($_POST['quality']) ? intval($_POST['quality']) : 85;
    $maxWidth = isset($_POST['max_width']) ? intval($_POST['max_width']) : 1920;
    $maxHeight = isset($_POST['max_height']) ? intval($_POST['max_height']) : 1080;
    $preserveOriginal = isset($_POST['preserve_original']) && $_POST['preserve_original'] === '1';
    $addSuffix = isset($_POST['add_suffix']) && $_POST['add_suffix'] === '1';
    $debug = isset($_POST['debug']) && $_POST['debug'] === '1';

    // Auto-ativar debug se houver poucos arquivos (para facilitar troubleshooting)
    if (count($files) <= 3) {
      $debug = true;
    }

    // Validar parâmetros
    if (!in_array($format, ['webp', 'jpeg', 'png', 'avif'])) {
      err(400, "Formato de saída inválido.");
    }
    if ($quality < 10 || $quality > 100) {
      err(400, "Qualidade deve estar entre 10 e 100.");
    }

    // Verificar se o GD está disponível
    if (!extension_loaded('gd')) {
      err(500, "Extensão GD não está disponível no servidor.");
    }

    $tmp_dir = dirname($_SERVER['SCRIPT_FILENAME']);
    if (DIRECTORY_SEPARATOR === '\\')
      $tmp_dir = str_replace('/', DIRECTORY_SEPARATOR, $tmp_dir);

    $results = [];
    $converted = 0;

    foreach ($files as $file) {
      $result = ['success' => false, 'file' => $file];

      // Validar arquivo - verificar apenas path traversal real, não nomes de arquivo com pontos
      if (!$file || preg_match('@^.+://@', $file) || preg_match('@(^|/|\\\\)\.\.(/|\\\\|$)@', $file)) {
        $result['error'] = 'Arquivo inválido';
        $results[] = $result;
        continue;
      }

      // Aplicar a mesma lógica de construção de caminho do áudio
      $file_normalized = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $file);

      // Tentar diferentes possibilidades de caminho
      $possible_paths = [];

      // 1. Se o arquivo é um caminho absoluto que começa com tmp_dir, usar diretamente
      if (strpos($file_normalized, $tmp_dir) === 0) {
        $possible_paths[] = $file_normalized;
      } else {
        // 2. Caminho com subpasta files/ (estrutura real detectada)
        $possible_paths[] = $tmp_dir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . $file_normalized;

        // 3. Caminho relativo direto (sem files/)
        $possible_paths[] = $tmp_dir . DIRECTORY_SEPARATOR . $file_normalized;

        // 4. Verificar se começa com "files" e remover para evitar duplicação
        $file_clean = $file_normalized;
        if (strpos($file_clean, 'files' . DIRECTORY_SEPARATOR) === 0) {
          $file_clean = substr($file_clean, strlen('files' . DIRECTORY_SEPARATOR));
          $possible_paths[] = $tmp_dir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . $file_clean;
          $possible_paths[] = $tmp_dir . DIRECTORY_SEPARATOR . $file_clean;
        }
      }

      // Testar cada caminho possível
      $src_abs = null;
      foreach ($possible_paths as $test_path) {
        $test_path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $test_path);

        if (file_exists($test_path) && is_file($test_path)) {
          $src_abs = $test_path;
          break;
        }
      }

      if ($src_abs === null || !file_exists($src_abs) || !is_file($src_abs)) {
        $result['error'] = 'Arquivo não encontrado: ' . $file;
        $results[] = $result;
        continue;
      }

      // Debug: adicionar informações detalhadas somente se solicitado
      if ($debug) {
        $result['debug_info'] = [
          'found_path' => $src_abs,
          'file_size' => filesize($src_abs),
          'is_readable' => is_readable($src_abs),
          'mime_type' => function_exists('mime_content_type') ? @mime_content_type($src_abs) : 'N/A'
        ];
      }

      // Verificar se o caminho tem caracteres problemáticos
      $sanitize_info = sanitizeFileName(basename($src_abs));
      $use_temp_file = false;
      $temp_file_info = null;
      $actual_image_path = $src_abs;

      // Sempre tentar criar arquivo temporário se houver caracteres especiais ou espaços
      $full_path_check = $src_abs;
      $has_special_chars = preg_match('/[^\x20-\x7E]/', $full_path_check) ||
        strpos($full_path_check, ' ') !== false ||
        $sanitize_info['has_problematic'];

      if ($has_special_chars) {
        if ($debug) {
          $result['debug_info']['has_special_chars'] = true;
          $result['debug_info']['problematic_chars'] = $sanitize_info['problematic_chars'];
          $result['debug_info']['using_temp_file'] = true;
        }

        // Criar arquivo temporário para caracteres problemáticos
        $temp_file_info = createTempFileLink($src_abs, sys_get_temp_dir());
        if ($temp_file_info) {
          $actual_image_path = $temp_file_info['path'];
          $use_temp_file = true;
          if ($debug) {
            $result['debug_info']['temp_path'] = $actual_image_path;
          }
        } else {
          if ($debug) {
            $result['debug_info']['temp_file_creation_failed'] = true;
          }
        }
      }

      // Verificar se é uma imagem
      $imageInfo = @getimagesize($actual_image_path);
      if ($imageInfo === false) {
        $result['error'] = 'Não é uma imagem válida - getimagesize falhou';
        if ($debug) {
          $result['debug_info']['getimagesize_error'] = 'getimagesize retornou false';
        }

        // Limpar arquivo temporário se foi criado
        if ($use_temp_file && $temp_file_info && $temp_file_info['is_copy']) {
          @unlink($temp_file_info['path']);
        }

        $results[] = $result;
        continue;
      }

      // Verificar se a imagem não é muito grande para evitar problemas de memória
      $image_memory_required = ($imageInfo[0] * $imageInfo[1] * 4) / 1024 / 1024; // MB aproximados
      $memory_limit = ini_get('memory_limit');
      $memory_limit_mb = intval($memory_limit);

      if ($image_memory_required > ($memory_limit_mb * 0.8)) {
        $result['error'] = 'Imagem muito grande. Requer aproximadamente ' . round($image_memory_required, 1) . 'MB de memória';

        if ($use_temp_file && $temp_file_info && $temp_file_info['is_copy']) {
          @unlink($temp_file_info['path']);
        }

        $results[] = $result;
        continue;
      }

      if ($debug) {
        $result['debug_info']['image_info'] = [
          'width' => $imageInfo[0],
          'height' => $imageInfo[1],
          'type' => $imageInfo[2],
          'type_name' => function_exists('image_type_to_extension') ? @image_type_to_extension($imageInfo[2]) : 'N/A',
          'mime' => isset($imageInfo['mime']) ? $imageInfo['mime'] : 'N/A'
        ];
      }

      // Carregar imagem com debugging melhorado e tratamento de erros robusto
      $sourceImage = null;
      $load_method = '';
      $load_error_details = '';

      switch ($imageInfo[2]) {
        case IMAGETYPE_JPEG:
          $load_method = 'imagecreatefromjpeg';
          $sourceImage = @imagecreatefromjpeg($actual_image_path);
          if (!$sourceImage) {
            $load_error_details = 'Possível corrupção no arquivo JPEG ou arquivo não é um JPEG válido';
          }
          break;
        case IMAGETYPE_PNG:
          $load_method = 'loadPNGRobust';
          // Usar função robusta para carregar PNG
          $png_result = loadPNGRobust($actual_image_path);
          if ($png_result && isset($png_result['image'])) {
            $sourceImage = $png_result['image'];
            $load_method = $png_result['method'];
          } else {
            $load_error_details = 'Todos os métodos de carregamento PNG falharam. Arquivo pode estar severamente corrompido';
          }
          break;
        case IMAGETYPE_GIF:
          $load_method = 'imagecreatefromgif';
          $sourceImage = @imagecreatefromgif($actual_image_path);
          if (!$sourceImage) {
            $load_error_details = 'Possível corrupção no arquivo GIF ou formato GIF não suportado';
          }
          break;
        case IMAGETYPE_WEBP:
          $load_method = 'imagecreatefromwebp';
          if (function_exists('imagecreatefromwebp')) {
            $sourceImage = @imagecreatefromwebp($actual_image_path);
            if (!$sourceImage) {
              $load_error_details = 'Arquivo WebP corrompido ou versão não suportada';
            }
          } else {
            $result['error'] = 'Suporte a WebP não está disponível no servidor';
            $results[] = $result;
            continue 2;
          }
          break;
        case IMAGETYPE_BMP:
          $load_method = 'imagecreatefrombmp';
          if (function_exists('imagecreatefrombmp')) {
            $sourceImage = @imagecreatefrombmp($actual_image_path);
            if (!$sourceImage) {
              $load_error_details = 'Arquivo BMP corrompido ou formato BMP não suportado';
            }
          } else {
            $result['error'] = 'Suporte a BMP não está disponível no servidor';
            $results[] = $result;
            continue 2;
          }
          break;
        default:
          $result['error'] = 'Formato de imagem não suportado: ' . $imageInfo[2];
          $results[] = $result;
          continue 2;
      }

      if ($debug) {
        $result['debug_info']['load_method'] = $load_method;
        $result['debug_info']['load_success'] = ($sourceImage !== false && $sourceImage !== null);
        if ($load_error_details) {
          $result['debug_info']['load_error_details'] = $load_error_details;
        }
      }

      if (!$sourceImage) {
        // Tentar método alternativo usando imagecreatefromstring como fallback
        if (file_exists($actual_image_path) && is_readable($actual_image_path)) {
          $image_data = file_get_contents($actual_image_path);
          if ($image_data !== false && strlen($image_data) > 0) {
            $sourceImage = @imagecreatefromstring($image_data);
            if ($sourceImage) {
              $load_method .= ' (fallback: imagecreatefromstring)';
              $load_error_details = '';
            } else {
              if ($load_error_details) {
                $load_error_details .= '. Fallback com imagecreatefromstring também falhou';
              } else {
                $load_error_details = 'Fallback com imagecreatefromstring falhou';
              }
            }
          } else {
            if ($load_error_details) {
              $load_error_details .= '. Não foi possível ler dados do arquivo';
            } else {
              $load_error_details = 'Não foi possível ler dados do arquivo';
            }
          }
        }
      }

      if (!$sourceImage) {
        if ($load_error_details) {
          $result['error'] = 'Falha ao carregar imagem: ' . $load_error_details;
        } else {
          $result['error'] = 'Falha ao carregar imagem usando ' . $load_method . '. Arquivo pode estar corrompido ou formato não suportado';
        }

        if ($debug) {
          $result['debug_info']['load_error'] = 'Função de carregamento retornou false/null';
          $result['debug_info']['file_path'] = $actual_image_path;
          $result['debug_info']['file_exists'] = file_exists($actual_image_path);
          $result['debug_info']['file_readable'] = is_readable($actual_image_path);
          $result['debug_info']['file_size'] = file_exists($actual_image_path) ? filesize($actual_image_path) : 'N/A';
        }

        // Limpar arquivo temporário se foi criado
        if ($use_temp_file && $temp_file_info && $temp_file_info['is_copy']) {
          @unlink($temp_file_info['path']);
        }

        $results[] = $result;
        continue;
      }

      $originalWidth = imagesx($sourceImage);
      $originalHeight = imagesy($sourceImage);

      // Calcular novas dimensões mantendo proporção
      $newWidth = $originalWidth;
      $newHeight = $originalHeight;

      if ($originalWidth > $maxWidth || $originalHeight > $maxHeight) {
        $ratioWidth = $maxWidth / $originalWidth;
        $ratioHeight = $maxHeight / $originalHeight;
        $ratio = min($ratioWidth, $ratioHeight);

        $newWidth = intval($originalWidth * $ratio);
        $newHeight = intval($originalHeight * $ratio);
      }

      // Criar nova imagem
      $newImage = imagecreatetruecolor($newWidth, $newHeight);

      // Preservar transparência para PNG, WebP e GIF
      if ($format === 'png' || $format === 'webp' || $imageInfo[2] === IMAGETYPE_PNG || $imageInfo[2] === IMAGETYPE_GIF) {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);

        // Para PNG e WebP, definir cor transparente
        if ($format === 'png' || $format === 'webp') {
          $transparent = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
          imagefill($newImage, 0, 0, $transparent);
        }
      } else {
        // Para JPEG, usar fundo branco
        $white = imagecolorallocate($newImage, 255, 255, 255);
        imagefill($newImage, 0, 0, $white);
      }

      // Redimensionar
      imagecopyresampled(
        $newImage,
        $sourceImage,
        0,
        0,
        0,
        0,
        $newWidth,
        $newHeight,
        $originalWidth,
        $originalHeight
      );

      // Determinar nome do arquivo de saída
      $pathInfo = pathinfo($src_abs);
      $baseName = $pathInfo['filename'];
      $outputDir = $pathInfo['dirname'];

      // Estratégia melhorada para não preservar originais (mesmo do áudio)
      $will_replace_original = !$preserveOriginal && !$addSuffix;

      if ($addSuffix) {
        $baseName .= '_converted';
        $outputFile = $outputDir . DIRECTORY_SEPARATOR . $baseName . '.' . $format;
      } elseif ($will_replace_original) {
        // Usar arquivo temporário durante conversão, substituir original só no final
        $outputFile = $outputDir . DIRECTORY_SEPARATOR . $baseName . '_temp_conversion_' . time() . '.' . $format;
      } else {
        $outputFile = $outputDir . DIRECTORY_SEPARATOR . $baseName . '.' . $format;
      }

      // REMOVIDO: Não fazer backup/rename antes da conversão
      // O arquivo original permanece intacto durante toda a conversão

      // Salvar imagem convertida
      $success = false;
      switch ($format) {
        case 'jpeg':
          $success = imagejpeg($newImage, $outputFile, $quality);
          break;
        case 'png':
          // PNG qualidade: 0 (sem compressão) a 9 (máxima compressão)
          // Converter qualidade de 1-100 para 0-9
          $pngQuality = min(9, max(0, intval((100 - $quality) / 11)));
          $success = imagepng($newImage, $outputFile, $pngQuality);
          break;
        case 'webp':
          if (function_exists('imagewebp')) {
            $success = imagewebp($newImage, $outputFile, $quality);
          } else {
            $result['error'] = 'Função imagewebp() não está disponível no servidor';
          }
          break;
        case 'avif':
          if (function_exists('imageavif')) {
            $success = imageavif($newImage, $outputFile, $quality);
          } else {
            $result['error'] = 'Função imageavif() não está disponível no servidor';
          }
          break;
      }

      // Limpeza
      imagedestroy($sourceImage);
      imagedestroy($newImage);

      if ($success) {
        // Se deve substituir o arquivo original, fazer isso agora (mesmo do áudio)
        $final_output_path = $outputFile;
        if ($will_replace_original) {
          $original_file_path = $pathInfo['dirname'] . DIRECTORY_SEPARATOR . $pathInfo['filename'] . '.' . $pathInfo['extension'];

          // Fazer backup temporário do original (por segurança)
          $backup_temp = $original_file_path . '.backup_' . time();
          $backup_success = @rename($src_abs, $backup_temp);

          // Mover arquivo convertido para o lugar do original
          $replace_success = @rename($outputFile, $original_file_path);

          if ($replace_success) {
            // Sucesso na substituição - remover backup
            if ($backup_success) {
              @unlink($backup_temp);
            }
            $final_output_path = $original_file_path;
          } else {
            // Falha na substituição - restaurar backup e manter arquivo convertido
            if ($backup_success) {
              @rename($backup_temp, $src_abs);
            }
            // Manter arquivo convertido com nome temporário
          }
        }

        $result['success'] = true;
        $result['original_size'] = filesize($src_abs);
        $result['new_size'] = filesize($final_output_path);
        $result['output_file'] = str_replace($tmp_dir . DIRECTORY_SEPARATOR, '', $final_output_path);
        $result['compression_ratio'] = round((1 - $result['new_size'] / $result['original_size']) * 100, 1);
        $result['replaced_original'] = $will_replace_original;
        $converted++;

        // Limpar arquivo temporário se foi usado
        if ($use_temp_file && $temp_file_info && $temp_file_info['is_copy']) {
          @unlink($temp_file_info['path']);
        }
      } else {
        $result['error'] = 'Falha ao salvar imagem convertida';

        // Limpar arquivo temporário se foi criado mas a conversão falhou
        if ($will_replace_original && file_exists($outputFile)) {
          @unlink($outputFile);
        }

        // Limpar arquivo temporário se foi usado
        if ($use_temp_file && $temp_file_info && $temp_file_info['is_copy']) {
          @unlink($temp_file_info['path']);
        }
      }

      $results[] = $result;
    }

    echo json_encode([
      'success' => true,
      'results' => $results,
      'converted' => $converted,
      'total' => count($files)
    ]);

  } catch (Exception $e) {
    err(500, "Erro interno: " . $e->getMessage());
  }
  exit;
}

// ADDED: endpoint para conversão de áudio
elseif ($do == 'convertaudio') {
  // Verificar e ajustar limites PHP para processamento de muitos arquivos
  $max_input_vars = ini_get('max_input_vars');
  if ($max_input_vars > 0 && $max_input_vars < 10000) {
    @ini_set('max_input_vars', 10000);
  }

  if (!isset($_POST['files']) || !is_array($_POST['files']))
    err(400, "Nenhum arquivo de áudio especificado.");

  $files = $_POST['files'];
  $format = isset($_POST['format']) ? $_POST['format'] : 'mp3';
  $quality = isset($_POST['quality']) ? $_POST['quality'] : 'high';
  $bitrate = isset($_POST['bitrate']) ? intval($_POST['bitrate']) : 192;
  $preserveOriginal = isset($_POST['preserve_original']) && $_POST['preserve_original'] === '1';
  $addSuffix = isset($_POST['add_suffix']) && $_POST['add_suffix'] === '1';
  $debug = isset($_POST['debug']) && $_POST['debug'] === '1';

  // NOVO: Processamento em lotes - sem limite de timeout
  $batch_size = isset($_POST['batch_size']) ? intval($_POST['batch_size']) : 500;
  $start_index = isset($_POST['start_index']) ? intval($_POST['start_index']) : 0;
  $is_batch_request = isset($_POST['is_batch']) && $_POST['is_batch'] === '1';

  // Remover limite de lote - permite processar todos os arquivos
  if ($batch_size < 1)
    $batch_size = 1;
  // Sem limite máximo de batch_size

  // Configurar limites de execução
  $original_time_limit = ini_get('max_execution_time');
  $original_memory_limit = ini_get('memory_limit');

  // Remover limite de tempo para conversão de áudio (0 = sem limite)
  @ini_set('max_execution_time', 0);
  @ini_set('memory_limit', '512M');

  // Validar parâmetros
  if (!in_array($format, ['mp3', 'wav', 'ogg', 'aac', 'm4a'])) {
    err(400, "Formato de áudio inválido.");
  }
  if (!in_array($quality, ['low', 'medium', 'high', 'custom'])) {
    err(400, "Qualidade inválida.");
  }
  if ($bitrate < 64 || $bitrate > 320) {
    err(400, "Bitrate deve estar entre 64 e 320 kbps.");
  }

  // Verificar se FFmpeg está disponível
  // Locais possíveis do FFmpeg (em ordem de prioridade)
  $ffmpeg_paths = [
    dirname($_SERVER['SCRIPT_FILENAME']) . '/ffmpeg.exe',  // Na mesma pasta do script
    dirname($_SERVER['SCRIPT_FILENAME']) . '/bin/ffmpeg.exe',  // Na pasta bin
    'C:/ffmpeg/bin/ffmpeg.exe',  // Instalação padrão Windows
    'ffmpeg.exe',  // No PATH do sistema
    'ffmpeg'  // No PATH (Linux/Mac)
  ];

  $ffmpeg_path = null;
  foreach ($ffmpeg_paths as $path) {
    // Para caminhos absolutos, verificar se o arquivo existe primeiro
    if (strpos($path, '/') !== false || strpos($path, '\\') !== false) {
      if (file_exists($path)) {
        $check = shell_exec('"' . $path . '" -version 2>&1');
        if ($check && strpos($check, 'ffmpeg version') !== false) {
          $ffmpeg_path = '"' . $path . '"';  // Aspas para lidar com espaços no caminho
          break;
        }
      }
    } else {
      // Para comandos no PATH, verificar diretamente
      $check = shell_exec($path . ' -version 2>&1');
      if ($check && strpos($check, 'ffmpeg version') !== false) {
        $ffmpeg_path = $path;
        break;
      }
    }
  }

  if ($ffmpeg_path === null) {
    err(500, "FFmpeg não foi encontrado. Coloque ffmpeg.exe na pasta do script ou instale no sistema.");
  }

  $tmp_dir = dirname($_SERVER['SCRIPT_FILENAME']);
  if (DIRECTORY_SEPARATOR === '\\')
    $tmp_dir = str_replace('/', DIRECTORY_SEPARATOR, $tmp_dir);

  $results = [];
  $converted = 0;
  $total_files = count($files);

  // Processamento sem limite de quantidade - aceita qualquer número de arquivos

  // Determinar arquivos para processar neste lote
  if ($is_batch_request) {
    $files_to_process = array_slice($files, $start_index, $batch_size);
    $batch_info = [
      'batch_number' => floor($start_index / $batch_size) + 1,
      'total_batches' => ceil($total_files / $batch_size),
      'start_index' => $start_index,
      'end_index' => min($start_index + $batch_size - 1, $total_files - 1),
      'files_in_batch' => count($files_to_process)
    ];
  } else {
    $files_to_process = $files;
    $batch_info = null;
  }

  // Determinar bitrate baseado na qualidade
  $actual_bitrate = $bitrate;
  switch ($quality) {
    case 'low':
      $actual_bitrate = 96;
      break;
    case 'medium':
      $actual_bitrate = 128;
      break;
    case 'high':
      $actual_bitrate = 192;
      break;
    case 'custom':
      $actual_bitrate = $bitrate;
      break;
  }

  // Processamento sem limite de tempo - rastreamento preciso
  $conversion_start_time = microtime(true);
  $file_timings = [];

  foreach ($files_to_process as $file) {
    $file_start_time = microtime(true);
    // Remover verificação de timeout - permite processamento completo

    $result = ['success' => false, 'file' => $file];

    // Validar arquivo - verificar apenas path traversal real, não nomes de arquivo com pontos
    if (!$file || preg_match('@^.+://@', $file) || preg_match('@(^|/|\\\\)\.\.(/|\\\\|$)@', $file)) {
      $result['error'] = 'Arquivo inválido';
      $results[] = $result;
      continue;
    }

    // Construir caminho absoluto corretamente - versão robusta
    $file_normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file);

    // Debug sempre habilitado para investigar o problema
    $debug_info = [
      'original_file' => $file,
      'file_normalized' => $file_normalized,
      'tmp_dir' => $tmp_dir
    ];

    // Tentar diferentes possibilidades de caminho
    $possible_paths = [];

    // 1. Se o arquivo é um caminho absoluto que começa com tmp_dir, usar diretamente
    if (strpos($file_normalized, $tmp_dir) === 0) {
      $possible_paths[] = $file_normalized;
      $debug_info['path_type'] = 'absolute';
    } else {
      // 2. Caminho com subpasta files/ (estrutura real detectada)
      $possible_paths[] = $tmp_dir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . $file_normalized;

      // 3. Caminho relativo direto (sem files/)
      $possible_paths[] = $tmp_dir . DIRECTORY_SEPARATOR . $file_normalized;

      // 4. Verificar se começa com "files" e remover para evitar duplicação
      $file_clean = $file_normalized;
      if (strpos($file_clean, 'files' . DIRECTORY_SEPARATOR) === 0) {
        $file_clean = substr($file_clean, strlen('files' . DIRECTORY_SEPARATOR));
        $possible_paths[] = $tmp_dir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . $file_clean;
        $possible_paths[] = $tmp_dir . DIRECTORY_SEPARATOR . $file_clean;
        $debug_info['removed_prefix'] = 'files' . DIRECTORY_SEPARATOR;
      }

      $debug_info['path_type'] = 'relative';
    }

    // Testar cada caminho possível
    $src_abs = null;
    foreach ($possible_paths as $test_path) {
      $test_path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $test_path);
      if (!isset($debug_info['tested_paths'])) {
        $debug_info['tested_paths'] = [];
      }
      $debug_info['tested_paths'][] = [
        'path' => $test_path,
        'exists' => file_exists($test_path),
        'is_file' => is_file($test_path)
      ];

      if (file_exists($test_path) && is_file($test_path)) {
        $src_abs = $test_path;
        $debug_info['found_path'] = $test_path;
        break;
      }
    }

    $debug_info['final_path'] = $src_abs;
    $debug_info['file_found'] = ($src_abs !== null);

    // Sempre incluir debug info
    $result['debug_info'] = $debug_info;

    if ($src_abs === null || !file_exists($src_abs) || !is_file($src_abs)) {
      $result['error'] = 'Arquivo não encontrado. Caminhos testados: ' . implode(', ', array_column($debug_info['tested_paths'] ?? [], 'path'));
      $results[] = $result;
      continue;
    }

    // Verificar se é um arquivo de áudio
    $audio_extensions = ['mp3', 'wav', 'ogg', 'aac', 'm4a', 'flac', 'wma', 'mp4', 'avi', 'mkv', 'mov'];
    $file_ext = strtolower(pathinfo($src_abs, PATHINFO_EXTENSION));

    if (!in_array($file_ext, $audio_extensions)) {
      $result['error'] = 'Formato de arquivo não suportado para conversão de áudio';
      $results[] = $result;
      continue;
    }

    // Determinar nome do arquivo de saída
    $pathInfo = pathinfo($src_abs);
    $baseName = $pathInfo['filename'];
    $outputDir = $pathInfo['dirname'];

    // Estratégia melhorada para não preservar originais:
    // 1. Sempre converter para arquivo temporário primeiro
    // 2. Só substituir o original após conversão bem-sucedida
    $will_replace_original = !$preserveOriginal && !$addSuffix;

    if ($addSuffix) {
      $baseName .= '_converted';
      $outputFile = $outputDir . DIRECTORY_SEPARATOR . $baseName . '.' . $format;
    } elseif ($will_replace_original) {
      // Usar arquivo temporário durante conversão, substituir original só no final
      $outputFile = $outputDir . DIRECTORY_SEPARATOR . $baseName . '_temp_conversion_' . time() . '.' . $format;
    } else {
      $outputFile = $outputDir . DIRECTORY_SEPARATOR . $baseName . '.' . $format;
    }

    // REMOVIDO: Não fazer backup/rename antes da conversão
    // O arquivo original permanece intacto durante toda a conversão

    // Verificar se arquivo tem caracteres problemáticos
    $sanitize_info = sanitizeFileName(basename($src_abs));
    $use_temp_file = false;
    $temp_file_info = null;
    $actual_input_path = $src_abs;

    if ($sanitize_info['has_problematic']) {
      if ($debug) {
        $result['debug_info']['problematic_chars'] = $sanitize_info['problematic_chars'];
        $result['debug_info']['using_temp_file'] = true;
      }

      // Criar arquivo temporário para caracteres problemáticos
      $temp_file_info = createTempFileLink($src_abs, sys_get_temp_dir());
      if ($temp_file_info) {
        $actual_input_path = $temp_file_info['path'];
        $use_temp_file = true;
        if ($debug) {
          $result['debug_info']['temp_path'] = $actual_input_path;
        }
      }
    }

    // Construir comando FFmpeg
    $input_file = escapeFilePath($actual_input_path);
    $output_file = escapeFilePath($outputFile);

    $ffmpeg_options = '';
    switch ($format) {
      case 'mp3':
        $ffmpeg_options = "-codec:a libmp3lame -b:a {$actual_bitrate}k";
        break;
      case 'wav':
        $ffmpeg_options = "-codec:a pcm_s16le";
        break;
      case 'ogg':
        $ffmpeg_options = "-codec:a libvorbis -b:a {$actual_bitrate}k";
        break;
      case 'aac':
        $ffmpeg_options = "-codec:a aac -b:a {$actual_bitrate}k";
        break;
      case 'm4a':
        $ffmpeg_options = "-codec:a aac -b:a {$actual_bitrate}k";
        break;
    }

    $command = "{$ffmpeg_path} -i {$input_file} {$ffmpeg_options} -y {$output_file} 2>&1";

    // Adicionar informações de debug se solicitado
    if ($debug) {
      $result['debug_info'] = [
        'command' => $command,
        'ffmpeg_path' => $ffmpeg_path,
        'input_file' => $src_abs,
        'output_file' => $outputFile,
        'ffmpeg_options' => $ffmpeg_options
      ];
    }

    // Executar conversão
    $output = shell_exec($command);
    $exit_code = 0;

    // Executar novamente para capturar o código de saída
    exec($command, $exec_output, $exit_code);

    if ($debug) {
      $result['debug_info']['exit_code'] = $exit_code;
      $result['debug_info']['raw_output'] = $output;
      $result['debug_info']['output_file_exists'] = file_exists($outputFile);
      $result['debug_info']['output_file_size'] = file_exists($outputFile) ? filesize($outputFile) : 0;
    }

    if (file_exists($outputFile) && filesize($outputFile) > 0) {
      $file_end_time = microtime(true);
      $file_conversion_time = $file_end_time - $file_start_time;

      // Se deve substituir o arquivo original, fazer isso agora
      $final_output_path = $outputFile;
      if ($will_replace_original) {
        $original_file_path = $pathInfo['dirname'] . DIRECTORY_SEPARATOR . $pathInfo['filename'] . '.' . $pathInfo['extension'];

        // Fazer backup temporário do original (por segurança)
        $backup_temp = $original_file_path . '.backup_' . time();
        $backup_success = @rename($src_abs, $backup_temp);

        // Mover arquivo convertido para o lugar do original
        $replace_success = @rename($outputFile, $original_file_path);

        if ($replace_success) {
          // Sucesso na substituição - remover backup
          if ($backup_success) {
            @unlink($backup_temp);
          }
          $final_output_path = $original_file_path;
        } else {
          // Falha na substituição - restaurar backup e manter arquivo convertido
          if ($backup_success) {
            @rename($backup_temp, $src_abs);
          }
          // Manter arquivo convertido com nome temporário
        }
      }

      $result['success'] = true;
      $result['original_size'] = filesize($src_abs);
      $result['new_size'] = filesize($final_output_path);
      $result['output_file'] = str_replace($tmp_dir . DIRECTORY_SEPARATOR, '', $final_output_path);
      $result['compression_ratio'] = round((1 - $result['new_size'] / $result['original_size']) * 100, 1);
      $result['bitrate'] = $actual_bitrate;
      $result['conversion_time'] = round($file_conversion_time, 2);
      $result['conversion_time_formatted'] = formatTime($file_conversion_time);
      $result['replaced_original'] = $will_replace_original;

      $file_timings[] = [
        'file' => basename($file),
        'time' => $file_conversion_time
      ];

      $converted++;

      // Limpar arquivo temporário se foi usado
      if ($use_temp_file && $temp_file_info && $temp_file_info['is_copy']) {
        @unlink($temp_file_info['path']);
      }
    } else {
      // Verificar se é realmente um erro ou apenas informações do FFmpeg
      $error_indicators = ['Error', 'Invalid', 'No such file', 'Permission denied', 'Cannot', 'failed', 'Unable'];
      $is_real_error = false;
      $error_found = '';

      foreach ($error_indicators as $indicator) {
        if (stripos($output, $indicator) !== false) {
          $is_real_error = true;
          $error_found = $indicator;
          break;
        }
      }

      // Calcular tempo mesmo em caso de erro
      $file_end_time = microtime(true);
      $file_conversion_time = $file_end_time - $file_start_time;
      $result['conversion_time'] = round($file_conversion_time, 2);
      $result['conversion_time_formatted'] = formatTime($file_conversion_time);

      if ($exit_code !== 0 || $is_real_error) {
        // Extrair apenas linhas que parecem ser erros reais
        $lines = explode("\n", $output);
        $error_lines = [];
        foreach ($lines as $line) {
          $line = trim($line);
          if (!empty($line)) {
            foreach ($error_indicators as $indicator) {
              if (stripos($line, $indicator) !== false) {
                $error_lines[] = $line;
                break;
              }
            }
          }
        }

        if (!empty($error_lines)) {
          $result['error'] = 'Falha na conversão: ' . implode('; ', array_slice($error_lines, 0, 3));
        } else {
          $result['error'] = 'Falha na conversão: Código de saída ' . $exit_code . ' (indicador: ' . $error_found . ')';
        }
      } else {
        // FFmpeg executou sem erros aparentes mas arquivo não foi criado
        $result['error'] = 'Arquivo de saída não foi criado. Possível problema de permissões ou codificação não suportada.';
      }

      if ($debug) {
        $result['debug_info']['error_analysis'] = [
          'exit_code' => $exit_code,
          'is_real_error' => $is_real_error,
          'error_found' => $error_found,
          'output_lines' => explode("\n", $output)
        ];
      }

      // REMOVIDO: Não há mais backup para restaurar
      // O arquivo original permanece intacto em caso de erro

      // Limpar arquivo temporário em caso de erro
      if ($use_temp_file && $temp_file_info && $temp_file_info['is_copy']) {
        @unlink($temp_file_info['path']);
      }

      // Limpar arquivo de saída temporário se foi criado mas a conversão falhou
      if ($will_replace_original && file_exists($outputFile)) {
        @unlink($outputFile);
      }
    }

    $results[] = $result;
  }

  // Calcular tempo total da conversão
  $conversion_end_time = microtime(true);
  $total_conversion_time = $conversion_end_time - $conversion_start_time;

  // Calcular estatísticas de tempo
  $avg_time_per_file = count($file_timings) > 0 ? array_sum(array_column($file_timings, 'time')) / count($file_timings) : 0;
  $fastest_file = count($file_timings) > 0 ? min(array_column($file_timings, 'time')) : 0;
  $slowest_file = count($file_timings) > 0 ? max(array_column($file_timings, 'time')) : 0;

  echo json_encode([
    'success' => true,
    'results' => $results,
    'converted' => $converted,
    'total' => count($files),
    'timing' => [
      'total_time' => round($total_conversion_time, 2),
      'total_time_formatted' => formatTime($total_conversion_time),
      'average_per_file' => round($avg_time_per_file, 2),
      'average_per_file_formatted' => formatTime($avg_time_per_file),
      'fastest_file' => round($fastest_file, 2),
      'fastest_file_formatted' => formatTime($fastest_file),
      'slowest_file' => round($slowest_file, 2),
      'slowest_file_formatted' => formatTime($slowest_file),
      'files_per_second' => $total_conversion_time > 0 ? round($converted / $total_conversion_time, 2) : 0
    ]
  ]);
  exit;
}

// ADDED: endpoint para compressão de arquivos (diferentes formatos)
elseif ($do == 'compressfiles') {
  if (!isset($_POST['files']) || !is_array($_POST['files']))
    err(400, "Nenhum arquivo especificado para compressão.");

  $files = $_POST['files'];
  $compression_type = isset($_POST['compression_type']) ? $_POST['compression_type'] : 'zip';
  $compression_level = isset($_POST['compression_level']) ? intval($_POST['compression_level']) : 6;
  $archive_name = isset($_POST['archive_name']) ? trim($_POST['archive_name']) : '';
  $folder = isset($_POST['folder']) && $_POST['folder'] !== '' ? $_POST['folder'] : '.';
  $preserve_paths = isset($_POST['preserve_paths']) && $_POST['preserve_paths'] === '1';

  // Validar parâmetros
  if (!in_array($compression_type, ['zip', 'tar', 'tar.gz', 'tar.bz2'])) {
    err(400, "Tipo de compressão inválido.");
  }
  if ($compression_level < 0 || $compression_level > 9) {
    err(400, "Nível de compressão deve estar entre 0 e 9.");
  }

  // Sanitizar nome do arquivo
  if ($archive_name === '') {
    $archive_name = 'compressed_files_' . date('Y-m-d_H-i-s');
  } else {
    $archive_name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $archive_name);
  }

  $tmp_dir = dirname($_SERVER['SCRIPT_FILENAME']);
  if (DIRECTORY_SEPARATOR === '\\')
    $tmp_dir = str_replace('/', DIRECTORY_SEPARATOR, $tmp_dir);

  // Determinar diretório de destino baseado na localização dos arquivos selecionados
  $target_dir = $tmp_dir;
  if ($folder !== '.') {
    $target_dir = $tmp_dir . '/' . $folder;
  }

  // Encontrar a pasta comum dos arquivos selecionados para criar o ZIP na mesma pasta
  $common_dir = $target_dir;
  if (!empty($files)) {
    // Analisar todos os arquivos para encontrar a pasta comum
    $file_dirs = array();
    foreach ($files as $file) {
      if (strpos($file, '/') !== false) {
        $file_dirs[] = dirname($file);
      } else {
        $file_dirs[] = '.';
      }
    }

    // Se todos os arquivos estão na mesma subpasta, usar essa pasta
    $unique_dirs = array_unique($file_dirs);
    if (count($unique_dirs) === 1 && $unique_dirs[0] !== '.') {
      $common_dir = $target_dir . '/' . $unique_dirs[0];
    }
  }

  $results = [];
  $archive_path = '';

  try {
    switch ($compression_type) {
      case 'zip':
        if (!class_exists('ZipArchive')) {
          err(500, "ZipArchive não está disponível no servidor.");
        }

        $archive_path = $common_dir . '/' . $archive_name . '.zip';
        $zip = new ZipArchive();

        if ($zip->open($archive_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
          err(500, "Falha ao criar arquivo ZIP.");
        }

        $added = 0;
        foreach ($files as $file) {
          if (!$file || preg_match('@(^|/|\\\\)\.\.(/|\\\\|$)@', $file))
            continue;

          $src_abs = get_absolute_path($target_dir . '/' . $file);
          if ($src_abs === false || !file_exists($src_abs))
            continue;

          if (is_file($src_abs)) {
            $archive_name_in_zip = $preserve_paths ? $file : basename($src_abs);
            $zip->addFile($src_abs, $archive_name_in_zip);
            $zip->setCompressionName($archive_name_in_zip, ZipArchive::CM_DEFLATE, $compression_level);
            $added++;
          } elseif (is_dir($src_abs)) {
            $dir_name_in_zip = $preserve_paths ? $file : basename($src_abs);
            addPathToZip($zip, $src_abs, $dir_name_in_zip, [], $added, $compression_level, $preserve_paths);
          }
        }

        $zip->close();

        if ($added > 0) {
          $results['success'] = true;
          $relative_path = str_replace($tmp_dir . '/', '', $archive_path);
          $relative_path = str_replace($tmp_dir . '\\', '', $relative_path);
          $results['archive'] = $relative_path;
          $results['files_added'] = $added;
          $results['archive_size'] = filesize($archive_path);
        } else {
          @unlink($archive_path);
          err(400, "Nenhum arquivo válido foi adicionado ao arquivo.");
        }
        break;

      case 'tar':
      case 'tar.gz':
      case 'tar.bz2':
        // Verificar se tar está disponível
        $tar_check = shell_exec('tar --version 2>&1');
        if (strpos($tar_check, 'tar') === false) {
          err(500, "Comando tar não está disponível no servidor.");
        }

        $extension = $compression_type;
        $archive_path = $common_dir . '/' . $archive_name . '.' . $extension;

        // Construir comando tar
        $tar_options = '';
        switch ($compression_type) {
          case 'tar':
            $tar_options = 'cf';
            break;
          case 'tar.gz':
            $tar_options = 'czf';
            break;
          case 'tar.bz2':
            $tar_options = 'cjf';
            break;
        }

        $file_list = [];

        foreach ($files as $file) {
          if (!$file || preg_match('@(^|/|\\\\)\.\.(/|\\\\|$)@', $file))
            continue;

          $src_abs = get_absolute_path($target_dir . '/' . $file);
          if ($src_abs === false || !file_exists($src_abs))
            continue;

          if ($preserve_paths) {
            // Manter estrutura de diretórios
            $file_list[] = escapeshellarg($file);
          } else {
            // Apenas o nome do arquivo/diretório
            $file_list[] = escapeshellarg(basename($src_abs));
          }
        }

        if (empty($file_list)) {
          err(400, "Nenhum arquivo válido encontrado para compressão.");
        }

        // Executar comando tar
        $old_cwd = getcwd();
        if ($preserve_paths) {
          chdir($target_dir);
        } else {
          chdir(dirname($src_abs));
        }

        $command = "tar {$tar_options} " . escapeshellarg($archive_path) . " " . implode(' ', $file_list) . " 2>&1";
        $output = shell_exec($command);

        chdir($old_cwd);

        if (file_exists($archive_path) && filesize($archive_path) > 0) {
          $results['success'] = true;
          $relative_path = str_replace($tmp_dir . '/', '', $archive_path);
          $relative_path = str_replace($tmp_dir . '\\', '', $relative_path);
          $results['archive'] = $relative_path;
          $results['files_added'] = count($file_list);
          $results['archive_size'] = filesize($archive_path);
        } else {
          err(500, "Falha ao criar arquivo comprimido: " . $output);
        }
        break;
    }

    echo json_encode($results);
  } catch (Exception $e) {
    err(500, "Erro durante compressão: " . $e->getMessage());
  }
  exit;
}
// --- helper for recursive copy ---
$php_post_max = asBytes(ini_get('post_max_size'));
$php_upload_max = asBytes(ini_get('upload_max_filesize'));
$config_max = $max_upload_size_mb * 1024 * 1024;
$limits = array_filter([$php_post_max, $php_upload_max, $config_max], static fn($value) => $value > 0);
$MAX_UPLOAD_SIZE = $limits ? min($limits) : PHP_INT_MAX;

require __DIR__ . '/app/manager_view.php';
