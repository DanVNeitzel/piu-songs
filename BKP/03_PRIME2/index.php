<style>
  body {
    color: #FFEB3B;
    font-size: 15px;
    font-family: monospace;
    background-color: #090909;
  }

  h1 {
    color: #0300ff;
  }

  h3 {
    color: #ff0000;
  }
</style>

<?php

// Obtém o nome da pasta atual
$currentFolder = basename(__DIR__);

echo '
{<br>
"children": [<br>
';

// Lista todas as pastas dentro da pasta atual (pastas de músicas)
$folders = glob("*", GLOB_ONLYDIR);

// Filtrar para remover pastas inválidas
$folders = array_filter($folders, function($folder) {
    return $folder !== 'index.php' && substr($folder, -4) !== '.php';
});

// Ordenar pastas
sort($folders);

$folderCount = count($folders);
$currentIndex = 0;

if ($folders) {
  foreach ($folders as $folder) {
    $currentIndex++;
    
    echo '
    {<br>
    "children": [<br>
    ';

    // Array para armazenar todos os arquivos encontrados
    $foundFiles = [];

    // Buscar arquivo .mp3 ou .MP3
    $mp3Files = array_merge(glob("$folder/*.mp3"), glob("$folder/*.MP3"));
    if (!empty($mp3Files)) {
        $file = $mp3Files[0]; // Pegar o primeiro arquivo
        $foundFiles[] = [
            'name' => $folder . '.mp3',
            'path' => './Songs/' . $currentFolder . '/' . $file,
            'type' => 'file'
        ];
    }

    // Buscar arquivo .ssc ou .SSC
    $sscFiles = array_merge(glob("$folder/*.ssc"), glob("$folder/*.SSC"));
    if (!empty($sscFiles)) {
        $file = $sscFiles[0]; // Pegar o primeiro arquivo
        $foundFiles[] = [
            'name' => $folder . '.ssc',
            'path' => './Songs/' . $currentFolder . '/' . $file,
            'type' => 'file'
        ];
    }

    // Buscar arquivo .webp ou .WEBP
    $webpFiles = array_merge(glob("$folder/*.webp"), glob("$folder/*.WEBP"));
    if (!empty($webpFiles)) {
        $file = $webpFiles[0]; // Pegar o primeiro arquivo
        $foundFiles[] = [
            'name' => $folder . '.webp',
            'path' => './Songs/' . $currentFolder . '/' . $file,
            'type' => 'file'
        ];
    }

    // Imprimir os arquivos encontrados
    $fileCount = count($foundFiles);
    for ($i = 0; $i < $fileCount; $i++) {
        $fileData = $foundFiles[$i];
        echo '
                {<br>
                "name": "' . $fileData['name'] . '",<br>
                "path": "' . $fileData['path'] . '",<br>
                "type": "' . $fileData['type'] . '"<br>
                }';
        
        // Adicionar vírgula se não for o último arquivo
        if ($i < $fileCount - 1) {
            echo ',';
        }
        echo '<br>
                ';
    }

    echo '
            ],<br>
            "name": "' . $folder . '",<br>
            "path": "./Songs/' . $currentFolder . '/' . $folder . '",<br>
            "type": "folder"<br>
            }';
    
    // Adicionar vírgula se não for a última pasta
    if ($currentIndex < $folderCount) {
        echo ',';
    }
    echo '<br>
            ';
  }
} else {
  echo "Nenhuma pasta encontrada dentro de $currentFolder.";
}

// Buscar o banner (pode ser .webp, .png, .jpg, etc.)
$bannerFiles = array_merge(
    glob("*.webp"),
    glob("*.WEBP"),
    glob("*.png"),
    glob("*.PNG"),
    glob("*.jpg"),
    glob("*.JPG")
);

$bannerPath = "./Songs/" . $currentFolder . "/banner.webp"; // Padrão

// Se encontrou algum banner, usar o primeiro
if (!empty($bannerFiles)) {
    $bannerPath = "./Songs/" . $currentFolder . "/" . $bannerFiles[0];
}

echo '
        ],<br>
        "name": "' . $currentFolder . '",<br>
        "path": "./Songs/' . $currentFolder . '",<br>
        "banner": "' . $bannerPath . '",<br>
        "type": "folder"<br>
        }<br>
        ';

?>