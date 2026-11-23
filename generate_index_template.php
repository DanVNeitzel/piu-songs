<?php
/**
 * Gera um arquivo index.php padrão para uma pasta de músicas
 * 
 * @param string $folderPath Caminho completo da pasta
 * @return bool Sucesso ou falha
 */
function generateIndexPhp($folderPath) {
    $indexContent = <<<'PHP'
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

$numDir = 0;

// Obtém o nome da pasta atual
$currentFolder = basename(__DIR__);

echo '
{<br>
"children": [<br>
';

// Obtém o nome da pasta atual
$currentFolder = basename(__DIR__);

// Lista todas as pastas dentro da pasta atual
$folders = glob("*", GLOB_ONLYDIR);

if ($folders) {
  // echo "Pastas dentro de $currentFolder:<br>";
  foreach ($folders as $folder) {



    echo '
    {<br>
    "children": [<br>
    ';

    if ("$folder/*.mp3") {
      $files = glob("$folder/*.mp3");
      if ($files) {
        foreach ($files as $file) {
          echo '
          {<br>
          "name": "' . $folder . '.mp3",<br>
          "path": "./Songs/' . $currentFolder . '/' . $file . '",<br>
          "type": "file"<br>
          },<br>
          ';
        }
        // echo "<br>";
      }
    }

    if ("$folder/*.MP3") {
      $files = glob("$folder/*.MP3");
      if ($files) {
        foreach ($files as $file) {
          echo '
          {<br>
          "name": "' . $folder . '.MP3",<br>
          "path": "./Songs/' . $currentFolder . '/' . $file . '",<br>
          "type": "file"<br>
          },<br>
          ';
        }
        // echo "<br>";
      }
    }

    if ("$folder/*.ssc") {
      $files = glob("$folder/*.ssc");
      if ($files) {
        foreach ($files as $file) {
          echo '
          {<br>
          "name": "' . $folder . '.ssc",<br>
          "path": "./Songs/' . $currentFolder . '/' . $file . '",<br>
          "type": "file"<br>
          },<br>
          ';
        }
        // echo "<br>";
      }
    }

    if ("$folder/*.SSC") {
      $files = glob("$folder/*.SSC");
      if ($files) {
        foreach ($files as $file) {
          echo '
          {<br>
          "name": "' . $folder . '.SSC",<br>
          "path": "./Songs/' . $currentFolder . '/' . $file . '",<br>
          "type": "file"<br>
          },<br>
          ';
        }
        // echo "<br>";
      }
    }

    if ("$folder/*.webp") {
      $files = glob("$folder/*.webp");
      if ($files) {
        foreach ($files as $file) {
          echo '
          {<br>
          "name": "' . $folder . '.webp",<br>
          "path": "./Songs/' . $currentFolder . '/' . $file . '",<br>
          "type": "file"<br>
          }<br>
          ';
        }
        echo "<br>";
      }
    }

    if ("$folder/*.WEBP") {
      $files = glob("$folder/*.WEBP");
      if ($files) {
        foreach ($files as $file) {
          echo '
          {<br>
          "name": "' . $folder . '.WEBP",<br>
          "path": "./Songs/' . $currentFolder . '/' . $file . '",<br>
          "type": "file"<br>
          }<br>
          ';
        }
        echo "<br>";
      }
    }

    echo '
  ],<br>
  "name": "' . $folder . '",<br>
  "path": "./Songs/' . $currentFolder . '/' . $folder . '",<br>
  "type": "folder"<br>
  },<br>
  ';

  }
} else {
  echo "Nenhuma pasta encontrada dentro de $currentFolder.";
}

echo '
],<br>
"name": "' . $currentFolder . '",<br>
"path": "./Songs/' . $currentFolder . '",<br>
"banner": "./Songs/' . $currentFolder . '/banner.png",<br>
"type": "folder"<br>
}<br>
';

?>
PHP;

    $indexFile = $folderPath . DIRECTORY_SEPARATOR . 'index.php';
    return file_put_contents($indexFile, $indexContent) !== false;
}
?>
