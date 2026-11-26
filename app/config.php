<?php
//Desativar relatório de erro
error_reporting(error_reporting() & ~E_NOTICE);

//Opções de Segurança
$allow_delete = true; // Defina como false para desativar o botão de exclusão e excluir a solicitação POST.
$allow_upload = true; // Defina como true para permitir o upload de arquivos
$allow_create_folder = true; // Defina como false para desativar a criação de pasta
$allow_create_file = true; // ADDED: permitir criação de novos arquivos de texto via UI
$allow_direct_link = true; // Defina como false para permitir apenas downloads e não link direto
$allow_show_folders = true; // Defina como false para ocultar todos os subdiretórios
$configTime = 5; // Defina o tempo de expiração da sessão em minutos (padrão: 5 minutos)
$max_upload_size_mb = 200; // Limite máximo de tamanho para upload em MB (padrão: 50MB)

$disallowed_patterns = ['*.php'];  // Padrões de arquivos não permitidos para upload, download, criação e zip (usado fnmatch)
$hidden_patterns = ['*.css', '*.js', '.*']; // Extensões ocultas no índice do diretório

$SENHA = 'abc123';  // Defina a senha, para acessar o gerenciador de arquivos ... (opcional)

// --- CONFIGURAÇÕES DE INTEGRAÇÃO COM NUVEM ---
$cloud_integrations = [
  'google_drive' => [
    'enabled' => false,
    'client_id' => '',
    'client_secret' => '',
    'redirect_uri' => '',
    'access_token' => '',
    'refresh_token' => ''
  ],
  'dropbox' => [
    'enabled' => false,
    'app_key' => '',
    'app_secret' => '',
    'access_token' => ''
  ],
  'onedrive' => [
    'enabled' => false,
    'client_id' => '',
    'client_secret' => '',
    'redirect_uri' => '',
    'access_token' => '',
    'refresh_token' => ''
  ]
];

// ADDED: controle de acesso ao modal de configurações (editar este valor no arquivo para ativar)
$permissionAdmin = true; // se true -> mostra o ícone de configuração e habilita salvar (altere manualmente neste arquivo)

// ADDED: carregar configurações salvas em JSON (se existir)
$config_file = dirname(__DIR__) . '/files_config.json';
if (file_exists($config_file)) {
  $cfg = @json_decode(@file_get_contents($config_file), true);
  if (is_array($cfg)) {
    if (isset($cfg['allow_delete']))
      $allow_delete = (bool) $cfg['allow_delete'];
    if (isset($cfg['allow_upload']))
      $allow_upload = (bool) $cfg['allow_upload'];
    if (isset($cfg['allow_create_folder']))
      $allow_create_folder = (bool) $cfg['allow_create_folder'];
    if (isset($cfg['allow_create_file']))
      $allow_create_file = (bool) $cfg['allow_create_file'];
    if (isset($cfg['allow_direct_link']))
      $allow_direct_link = (bool) $cfg['allow_direct_link'];
    if (isset($cfg['allow_show_folders']))
      $allow_show_folders = (bool) $cfg['allow_show_folders'];
    if (isset($cfg['configTime']))
      $configTime = intval($cfg['configTime']);
    if (isset($cfg['max_upload_size_mb']))
      $max_upload_size_mb = intval($cfg['max_upload_size_mb']);
    if (isset($cfg['disallowed_patterns']) && is_array($cfg['disallowed_patterns']))
      $disallowed_patterns = $cfg['disallowed_patterns'];
    if (isset($cfg['hidden_patterns']) && is_array($cfg['hidden_patterns']))
      $hidden_patterns = $cfg['hidden_patterns'];
    if (isset($cfg['SENHA']))
      $SENHA = $cfg['SENHA'];
    // ADDED: carregar permissionAdmin (controle de acesso ao modal de configurações)
    if (isset($cfg['permissionAdmin']))
      $permissionAdmin = (bool) $cfg['permissionAdmin'];
    // ADDED: carregar configurações de integração com nuvem
    if (isset($cfg['cloud_integrations']) && is_array($cfg['cloud_integrations']))
      $cloud_integrations = array_merge($cloud_integrations, $cfg['cloud_integrations']);
  }
}

