<?php
class GoogleDriveAPI
{
  private $access_token;
  private $refresh_token;
  private $client_id;
  private $client_secret;

  public function __construct($config)
  {
    $this->access_token = $config['access_token'];
    $this->refresh_token = $config['refresh_token'];
    $this->client_id = $config['client_id'];
    $this->client_secret = $config['client_secret'];
  }

  public function listFiles($folder_id = 'root')
  {
    $url = 'https://www.googleapis.com/drive/v3/files';
    $params = [
      'q' => "'{$folder_id}' in parents and trashed=false",
      'fields' => 'files(id,name,mimeType,size,modifiedTime,parents)'
    ];

    return $this->makeRequest($url, 'GET', $params);
  }

  public function uploadFile($file_path, $name, $parent_id = 'root')
  {
    $metadata = [
      'name' => $name,
      'parents' => [$parent_id]
    ];

    $url = 'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart';
    $boundary = '-------314159265358979323846';

    $data = "--{$boundary}\r\n";
    $data .= "Content-Type: application/json\r\n\r\n";
    $data .= json_encode($metadata) . "\r\n";
    $data .= "--{$boundary}\r\n";
    $data .= "Content-Type: application/octet-stream\r\n\r\n";
    $data .= file_get_contents($file_path);
    $data .= "\r\n--{$boundary}--";

    $headers = [
      'Content-Type: multipart/related; boundary=' . $boundary,
      'Content-Length: ' . strlen($data)
    ];

    return $this->makeRequest($url, 'POST', null, $data, $headers);
  }

  public function downloadFile($file_id, $local_path)
  {
    $url = "https://www.googleapis.com/drive/v3/files/{$file_id}?alt=media";
    $response = $this->makeRequest($url, 'GET', null, null, [], true);

    if ($response) {
      return file_put_contents($local_path, $response) !== false;
    }
    return false;
  }

  private function makeRequest($url, $method = 'GET', $params = null, $data = null, $headers = [], $raw = false)
  {
    $ch = curl_init();

    if ($params && $method === 'GET') {
      $url .= '?' . http_build_query($params);
    }

    $default_headers = [
      'Authorization: Bearer ' . $this->access_token
    ];

    $headers = array_merge($default_headers, $headers);

    curl_setopt_array($ch, [
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPHEADER => $headers,
      CURLOPT_CUSTOMREQUEST => $method
    ]);

    if ($data) {
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw) {
      return $http_code === 200 ? $response : false;
    }

    return $http_code === 200 ? json_decode($response, true) : false;
  }
}

// Classe para integração com Dropbox
class DropboxAPI
{
  private $access_token;

  public function __construct($config)
  {
    $this->access_token = $config['access_token'];
  }

  public function listFiles($path = '')
  {
    $url = 'https://api.dropboxapi.com/2/files/list_folder';
    $data = json_encode(['path' => $path]);

    return $this->makeRequest($url, 'POST', $data);
  }

  public function uploadFile($file_path, $dropbox_path)
  {
    $url = 'https://content.dropboxapi.com/2/files/upload';
    $headers = [
      'Dropbox-API-Arg: ' . json_encode(['path' => $dropbox_path, 'mode' => 'add']),
      'Content-Type: application/octet-stream'
    ];

    return $this->makeRequest($url, 'POST', file_get_contents($file_path), $headers);
  }

  public function downloadFile($dropbox_path, $local_path)
  {
    $url = 'https://content.dropboxapi.com/2/files/download';
    $headers = [
      'Dropbox-API-Arg: ' . json_encode(['path' => $dropbox_path])
    ];

    $response = $this->makeRequest($url, 'POST', '', $headers, true);

    if ($response) {
      return file_put_contents($local_path, $response) !== false;
    }
    return false;
  }

  private function makeRequest($url, $method = 'POST', $data = '', $extra_headers = [], $raw = false)
  {
    $ch = curl_init();

    $headers = [
      'Authorization: Bearer ' . $this->access_token
    ];

    if (!in_array('Content-Type: application/octet-stream', $extra_headers)) {
      $headers[] = 'Content-Type: application/json';
    }

    $headers = array_merge($headers, $extra_headers);

    curl_setopt_array($ch, [
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPHEADER => $headers,
      CURLOPT_CUSTOMREQUEST => $method,
      CURLOPT_POSTFIELDS => $data
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw) {
      return $http_code === 200 ? $response : false;
    }

    return $http_code === 200 ? json_decode($response, true) : false;
  }
}

// Classe para integração com OneDrive
class OneDriveAPI
{
  private $access_token;
  private $refresh_token;
  private $client_id;
  private $client_secret;

  public function __construct($config)
  {
    $this->access_token = $config['access_token'];
    $this->refresh_token = $config['refresh_token'];
    $this->client_id = $config['client_id'];
    $this->client_secret = $config['client_secret'];
  }

  public function listFiles($folder_path = '/')
  {
    $url = 'https://graph.microsoft.com/v1.0/me/drive/root';
    if ($folder_path !== '/') {
      $url .= ':' . $folder_path . ':/children';
    } else {
      $url .= '/children';
    }

    return $this->makeRequest($url, 'GET');
  }

  public function uploadFile($file_path, $onedrive_path)
  {
    $url = "https://graph.microsoft.com/v1.0/me/drive/root:{$onedrive_path}:/content";
    $data = file_get_contents($file_path);

    $headers = [
      'Content-Type: application/octet-stream'
    ];

    return $this->makeRequest($url, 'PUT', $data, $headers);
  }

  public function downloadFile($file_id, $local_path)
  {
    $url = "https://graph.microsoft.com/v1.0/me/drive/items/{$file_id}/content";
    $response = $this->makeRequest($url, 'GET', null, [], true);

    if ($response) {
      return file_put_contents($local_path, $response) !== false;
    }
    return false;
  }

  private function makeRequest($url, $method = 'GET', $data = null, $extra_headers = [], $raw = false)
  {
    $ch = curl_init();

    $headers = [
      'Authorization: Bearer ' . $this->access_token
    ];

    if ($method !== 'GET' && !in_array('Content-Type: application/octet-stream', $extra_headers)) {
      $headers[] = 'Content-Type: application/json';
    }

    $headers = array_merge($headers, $extra_headers);

    curl_setopt_array($ch, [
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPHEADER => $headers,
      CURLOPT_CUSTOMREQUEST => $method
    ]);

    if ($data) {
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw) {
      return $http_code === 200 ? $response : false;
    }

    return $http_code === 200 ? json_decode($response, true) : false;
  }
}

