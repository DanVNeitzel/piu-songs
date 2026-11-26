<!DOCTYPE html>
<html>

<head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link data-rh="true" rel="icon" href="../files_assets/icons/files-logo.svg">
  <!-- DEFINA O TITULO DO SITE -->
  <title>[Logado] Gerenciador de Arquivos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <link href="../files_assets/css/style.css" rel="stylesheet">
  <link href="../files_assets/css/config.css" rel="stylesheet">
  <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
  <script src='../files_assets/js/script.js' crossorigin='anonymous'></script>

  <!-- CodeMirror CSS e JS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/monokai.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/dracula.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/material.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/solarized.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/dialog/dialog.min.css">
  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/search/matchesonscrollbar.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/hint/show-hint.min.css">

  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js"></script>

  <!-- Modos de linguagem do CodeMirror -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/javascript/javascript.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/css/css.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/xml/xml.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/htmlmixed/htmlmixed.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/php/php.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/python/python.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/sql/sql.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/markdown/markdown.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/yaml/yaml.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/shell/shell.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/dockerfile/dockerfile.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/clike/clike.min.js"></script>

  <!-- Addons do CodeMirror -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/edit/closebrackets.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/edit/matchbrackets.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/selection/active-line.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/search/search.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/search/searchcursor.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/dialog/dialog.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/search/jump-to-line.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/fold/foldcode.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/fold/foldgutter.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/fold/brace-fold.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/fold/xml-fold.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/fold/comment-fold.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/edit/closetag.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/edit/matchtags.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/hint/show-hint.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/hint/xml-hint.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/hint/html-hint.min.js"></script>
  
  <!-- ADDED: Modal para visualização de conteúdo ZIP -->
  <div class="modal fade" id="zipViewModal" tabindex="-1" aria-labelledby="zipViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="zipViewModalLabel">
            <i class="fa fa-file-archive-o"></i> Conteúdo do Arquivo ZIP
          </h5>

        </div>
        <div class="modal-body">
          <div class="row mb-3">
            <div class="col-md-8 text-white">
              <strong>Arquivo:</strong> <span class="text-white" id="zipFileName"></span>
            </div>
            <div class="col-md-4 text-end text-white">
              <strong>Total de arquivos:</strong> <span class="text-white" id="zipFileCount"></span>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label for="extractFolderName" class="form-label">Nome da pasta para extração:</label>
              <input type="text" class="form-control" id="extractFolderName" placeholder="Digite o nome da pasta">
            </div>
            <div class="col-md-6 d-flex align-items-end">
              <button type="button" class="btn btn-success" id="extractZipBtn">
                <i class="fa fa-download"></i> Extrair ZIP
              </button>
            </div>
          </div>

          <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
            <table class="table table-striped table-sm">
              <thead class="table-dark sticky-top">
                <tr>
                  <th><i class="fa fa-file-o"></i> Nome</th>
                  <th><i class="fa fa-calendar"></i> Modificado</th>
                  <th><i class="fa fa-hdd-o"></i> Tamanho</th>
                  <th><i class="fa fa-compress"></i> Comprimido</th>
                </tr>
              </thead>
              <tbody id="zipFilesList">
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fa fa-times"></i> Fechar
          </button>
        </div>
      </div>
    </div>
  </div>
  <!-- Modal da Lixeira -->
  <div class="modal fade" id="trashModal" tabindex="-1" aria-labelledby="trashModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="trashModalLabel">Lixeira</h5>
        </div>
        <div class="modal-body">
          <div id="trashAlert" style="display:none;"></div>
          <div class="mb-2 d-flex gap-2">
            <button id="restoreAllTrashBtn" class="btn btn-success btn-sm"><i class="fa fa-undo"></i>
              Restaurar
              Todos</button>
            <button id="emptyTrashBtn" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i> Esvaziar
              Lixeira</button>
          </div>
          <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
              <thead class="table-dark">
                <tr>
                  <th>Nome</th>
                  <th>Tipo</th>
                  <th>Data Exclusão</th>
                  <th>Ações</th>
                </tr>
              </thead>
              <tbody id="trashList">
                <tr>
                  <td colspan="5" class="text-center">Carregando...</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
        </div>
      </div>
    </div>
  </div>

  <script src="assets/manager-3.js"></script>

<link rel="stylesheet" href="assets/manager.css">
<script>window.piuClient = <?php echo json_encode([
 'cloud' => $cloud_integrations, 'maxUpload' => $MAX_UPLOAD_SIZE,
 'timeout' => $timeout ?? 0, 'folder' => $file, 'server' => [
 'allow_delete' => $allow_delete, 'allow_upload' => $allow_upload,
 'allow_create_folder' => $allow_create_folder, 'allow_create_file' => $allow_create_file,
 'allow_direct_link' => $allow_direct_link, 'allow_show_folders' => $allow_show_folders,
 'configTime' => $configTime, 'max_upload_size_mb' => $max_upload_size_mb,
 'disallowed_patterns' => $disallowed_patterns, 'hidden_patterns' => $hidden_patterns,
 'SENHA' => $SENHA, 'permissionAdmin' => $permissionAdmin,
 'effective_upload_limit_mb' => round($MAX_UPLOAD_SIZE / 1024 / 1024, 2),
 'php_post_max_mb' => round($php_post_max / 1024 / 1024, 2),
 'php_upload_max_mb' => round($php_upload_max / 1024 / 1024, 2)
 ]], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;</script>
</head>

<body>
  <!-- Overlay para mobile quando sidebar está aberto -->
  <div id="sidebarOverlay" class="sidebar-overlay"></div>

  <!-- Menu lateral moderno -->
  <div id="sidebar" class="sidebar">
    <!-- Header do sidebar -->
    <div class="sidebar-header">
      <div class="sidebar-brand">
        <i class="fa fa-folder-o"></i>
        <span class="sidebar-brand-text">File Manager</span>
      </div>
      <button id="closeSidebarBtn" class="sidebar-close-btn">
        <i class="fa fa-times"></i>
      </button>
    </div>

    <!-- Timer de sessão no sidebar -->
    <div id="session-timer" class="sidebar-session-timer">
      <i class="fa fa-clock-o"></i>
      <span class="session-text">Carregando...</span>
    </div>

    <!-- Menu do sidebar -->
    <div class="sidebar-menu">

      <!-- Seção: Visualização -->
      <div class="menu-section">
        <div class="menu-section-title">
          <i class="fa fa-eye"></i>
          Visualização
        </div>
        <div class="menu-items">
          <button id="toggleViewBtn" class="menu-item" title="Alternar visualização">
            <i class="fa fa-th-large"></i>
            <span class="menu-text">
              <span class="menu-label">Modo Exibição</span>
              <span id="toggleViewText" class="menu-value">Blocos</span>
            </span>
          </button>
        </div>
      </div>

      <!-- Seção: Criar -->
      <?php if ($allow_create_folder || $allow_create_file): ?>
        <div class="menu-section">
          <div class="menu-section-title">
            <i class="fa fa-plus"></i>
            Criar
          </div>
          <div class="menu-items">
            <?php if ($allow_create_folder): ?>
              <button class="menu-item" data-bs-toggle="modal" data-bs-target="#createFolder" title="Criar Pasta">
                <i class="fa fa-folder-o"></i>
                <span class="menu-text">
                  <span class="menu-label">Nova Pasta</span>
                  <span class="menu-desc">Criar diretório</span>
                </span>
              </button>
            <?php endif; ?>
            <?php if ($allow_create_file): ?>
              <button class="menu-item" data-bs-toggle="modal" data-bs-target="#createFileModal" title="Criar Arquivo">
                <i class="fa fa-file-o"></i>
                <span class="menu-text">
                  <span class="menu-label">Novo Arquivo</span>
                  <span class="menu-desc">Criar arquivo de texto</span>
                </span>
              </button>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- Seção: Nuvem -->
      <?php if ($permissionAdmin): ?>
        <div class="menu-section">
          <div class="menu-section-title">
            <i class="fa fa-cloud"></i>
            Nuvem
          </div>
          <div class="menu-items">
            <button class="menu-item" onclick="quickUploadToCloud()" title="Upload Rápido">
              <i class="fa fa-cloud-upload"></i>
              <span class="menu-text">
                <span class="menu-label">Upload Rápido</span>
                <span class="menu-desc">Enviar selecionados</span>
              </span>
            </button>
            <button class="menu-item" data-bs-toggle="modal" data-bs-target="#cloudModal"
              onclick="$('#cloudmanager-tab').click()" title="Gerenciar Nuvem">
              <i class="fa fa-cloud-download"></i>
              <span class="menu-text">
                <span class="menu-label">Baixar da Nuvem</span>
                <span class="menu-desc">Download arquivos</span>
              </span>
            </button>
            <button class="menu-item" onclick="quickSyncCloud()" title="Sincronização">
              <i class="fa fa-refresh"></i>
              <span class="menu-text">
                <span class="menu-label">Sincronizar</span>
                <span class="menu-desc">Sync automática</span>
              </span>
            </button>
          </div>
        </div>
      <?php endif; ?>

      <!-- Seção: Upload -->
      <?php if ($allow_upload): ?>
        <div class="menu-section">
          <div class="menu-section-title">
            <i class="fa fa-upload"></i>
            Upload
          </div>
          <div class="menu-items">
            <button class="menu-item" data-bs-toggle="modal" data-bs-target="#makeUpload" title="Carregar arquivos">
              <i class="fa fa-upload"></i>
              <span class="menu-text">
                <span class="menu-label">Carregar Arquivos</span>
                <span class="menu-desc">Upload de documentos</span>
              </span>
            </button>
          </div>
        </div>
      <?php endif; ?>

      <!-- Seção: Selecionados -->
      <div class="menu-section">
        <div class="menu-section-title">
          <i class="fa fa-check-square-o"></i>
          Selecionados
        </div>
        <div class="menu-items">
          <?php if ($allow_delete): ?>
            <button id="deleteSelectedBtn" class="menu-item menu-item-danger" title="Excluir Selecionados" disabled>
              <i class="fa fa-trash"></i>
              <span class="menu-text">
                <span class="menu-label">Excluir</span>
                <span class="menu-desc">Remover selecionados</span>
              </span>
            </button>
          <?php endif; ?>
          <?php if ($allow_upload): ?>
            <button id="moveCopyBtn" class="menu-item menu-item-warning" title="Mover/Copiar Selecionados" disabled>
              <i class="fa fa-exchange"></i>
              <span class="menu-text">
                <span class="menu-label">Mover/Copiar</span>
                <span class="menu-desc">Organizar arquivos</span>
              </span>
            </button>
            <button id="convertImagesBtn" class="menu-item menu-item-success"
              title="Converter/Comprimir Imagens Selecionadas" disabled>
              <i class="fa fa-image"></i>
              <span class="menu-text">
                <span class="menu-label">Converter Imagens</span>
                <span class="menu-desc">Otimizar fotos</span>
              </span>
            </button>
            <button id="convertAudioBtn" class="menu-item menu-item-info" title="Converter Arquivos de Áudio" disabled>
              <i class="fa fa-music"></i>
              <span class="menu-text">
                <span class="menu-label">Converter Áudio</span>
                <span class="menu-desc">Mudar formato de som</span>
              </span>
            </button>
            <button id="compressFilesBtn" class="menu-item menu-item-warning"
              title="Comprimir/Criar ZIP dos Arquivos Selecionados" disabled>
              <i class="fa fa-compress"></i>
              <span class="menu-text">
                <span class="menu-label">Comprimir/ZIP</span>
                <span class="menu-desc">Criar arquivo comprimido</span>
              </span>
            </button>
          <?php endif; ?>
        </div>
      </div>

      <!-- Seção: Ferramentas -->
      <div class="menu-section">
        <div class="menu-section-title">
          <i class="fa fa-wrench"></i>
          Ferramentas
        </div>
        <div class="menu-items">
          <button id="focusSearchBtn" class="menu-item" title="Focar na pesquisa (Ctrl+F)">
            <i class="fa fa-search"></i>
            <span class="menu-text">
              <span class="menu-label">Pesquisar</span>
              <span class="menu-desc">Localizar arquivos</span>
            </span>
          </button>
          <button class="menu-item" onClick="window.location.reload()" title="Atualizar lista">
            <i class="fa fa-refresh"></i>
            <span class="menu-text">
              <span class="menu-label">Atualizar</span>
              <span class="menu-desc">Recarregar lista</span>
            </span>
          </button>
          <button id="toggleAudioPlayerBtn" class="menu-item menu-item-player" title="Mostrar/Ocultar Player de Áudio">
            <i class="fa fa-music"></i>
            <span class="menu-text">
              <span class="menu-label">Player de Áudio</span>
              <span id="audioPlayerStatusText" class="menu-desc">Reprodutor musical</span>
            </span>
          </button>
          <button id="shareLinksBtn" class="menu-item" title="Gerenciar Links de Compartilhamento">
            <i class="fa fa-share-alt"></i>
            <span class="menu-text">
              <span class="menu-label">Compartilhamento</span>
              <span class="menu-desc">Links públicos</span>
            </span>
          </button>
          <button id="openTrashBtn" class="menu-item" title="Lixeira">
            <i class="fa fa-trash"></i>
            <span class="menu-text">
              <span class="menu-label">Lixeira</span>
              <span class="menu-desc">Arquivos excluídos</span>
            </span>
            <span id="trashCountBadge" class="menu-badge">0</span>
          </button>
        </div>
      </div>

      <!-- Seção: Sistema -->
      <div class="menu-section">
        <div class="menu-section-title">
          <i class="fa fa-cog"></i>
          Sistema
        </div>
        <div class="menu-items">
          <?php if ($permissionAdmin): ?>
            <button id="openConfigBtn" class="menu-item" data-bs-toggle="modal" data-bs-target="#configModal"
              title="Configurações">
              <i class="fa fa-cog"></i>
              <span class="menu-text">
                <span class="menu-label">Configurações</span>
                <span class="menu-desc">Painel admin</span>
              </span>
            </button>
            <button id="openCloudBtn" class="menu-item" data-bs-toggle="modal" data-bs-target="#cloudModal"
              title="Integração com Nuvem">
              <i class="fa fa-cloud"></i>
              <span class="menu-text">
                <span class="menu-label">Integração Nuvem</span>
                <span class="menu-desc">Google Drive, Dropbox, OneDrive</span>
              </span>
            </button>
            <button id="regenerateSongsBtn" class="menu-item menu-item-success"
              onclick="window.open('regenerate_songs.html', '_blank')" title="Regenerar songs.json">
              <i class="fa fa-music"></i>
              <span class="menu-text">
                <span class="menu-label">Regenerar songs.json</span>
                <span class="menu-desc">Atualizar lista de músicas</span>
              </span>
            </button>
          <?php endif; ?>
          <button id="logoutBtn" class="menu-item menu-item-danger" title="Sair">
            <i class="fa fa-sign-out"></i>
            <span class="menu-text">
              <span class="menu-label">Deslogar</span>
              <span class="menu-desc">Encerrar sessão</span>
            </span>
          </button>
        </div>
      </div>

    </div>
  </div>

  <!-- Conteúdo principal -->
  <div id="mainContent" class="main-content">
    <!-- Header superior com breadcrumb e botão do menu -->
    <div class="top-header">
      <button id="toggleSidebarBtn" class="sidebar-toggle-btn">
        <i class="fa fa-bars"></i>
      </button>
      <div id="breadcrumb" class="breadcrumb-container"></div>
      <div class="header-controls">
        <select id="sortSelect" class="form-select header-select" title="Ordenar arquivos">
          <option value="name">📝 Nome</option>
          <option value="date">📅 Data</option>
          <option value="type">📄 Tipo</option>
          <option value="size">📏 Tamanho</option>
        </select>
        <button id="toggleSearchBtn" class="search-toggle-btn" title="Mostrar/Ocultar Pesquisa">
          <i class="fa fa-search"></i>
        </button>
      </div>
    </div>
    <!-- Sistema de pesquisa de arquivos -->
    <div class="search-container bg-dark border-theme-default p-3 search-hidden" id="searchContainer">
      <div class="row align-items-center">
        <div class="col-md-3">
          <div class="input-group">
            <span class="input-group-text bg-secondary border-secondary text-light">
              <i class="fa fa-search"></i>
            </span>
            <input type="text" id="searchInput" class="form-control bg-dark border-secondary text-light"
              placeholder="Pesquisar arquivos..." autocomplete="off">
          </div>
        </div>
        <div class="col-md-2">
          <select id="searchExtension" class="form-select bg-dark border-secondary text-light">
            <option value="">Todas as extensões</option>
            <option value="txt">Arquivos de texto (.txt)</option>
            <option value="pdf">PDF (.pdf)</option>
            <option value="doc,docx">Word (.doc, .docx)</option>
            <option value="jpg,jpeg,png,gif,webp">Imagens (.jpg, .png, .gif, .webp)</option>
            <option value="mp3,wav,flac">Áudio (.mp3, .wav, .flac)</option>
            <option value="mp4,avi,mkv">Vídeo (.mp4, .avi, .mkv)</option>
            <option value="zip,rar,7z">Compactados (.zip, .rar, .7z)</option>
            <option value="js,css,html,php">Código (.js, .css, .html, .php)</option>
          </select>
        </div>
        <div class="col-md-2">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="includeSubfolders" checked>
            <label class="form-check-label text-light" for="includeSubfolders">
              Incluir subpastas
            </label>
          </div>
        </div>
        <div class="col-md-2">
          <button id="clearSearchBtn" class="btn btn-outline-warning btn-sm">
            <i class="fa fa-times"></i> Limpar
          </button>
        </div>
        <div class="col-md-3">
          <small class="text-light opacity-75" id="searchResults">
            Digite para pesquisar arquivos e pastas
          </small>
        </div>
      </div>
    </div>

    <div class="d-flex align-items-center p-2 text-white bg-success border-theme-default">
      <div class="form-check">
        <input type="checkbox" id="selectAll" class="form-check-input me-2">
        <label class="form-check-label" for="selectAll">Selecionar Todos</label>
      </div>
      <div class="ms-auto text-end">
        <small class="opacity-75">
          💡 <strong>Dica:</strong> Arraste arquivos/pastas para dentro de pastas para movê-los rapidamente
        </small>
      </div>
    </div>
    <table id="table">
      <thead>
        <tr>
          <th>Nome</th>
          <th>Tamanho</th>
          <th>Modificado</th>
          <th>Permissões</th>
          <th class="text-center">Ações</th>
        </tr>
      </thead>
      <tbody id="list">
      </tbody>
    </table>
  </div> <!-- Fim do main-content -->

  <!-- Modal para galeria de imagens com carrossel -->
  <div id="imagePreviewModal"
    style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.9);z-index:9999;align-items:center;justify-content:center;">

    <!-- Controles de navegação -->
    <button id="prevImageBtn" style="position:absolute;left:20px;top:50%;transform:translateY(-50%);z-index:10001;"
      class="btn btn-light btn-lg" title="Imagem anterior">
      <i class="fa fa-chevron-left"></i>
    </button>

    <button id="nextImageBtn" style="position:absolute;right:20px;top:50%;transform:translateY(-50%);z-index:10001;"
      class="btn btn-light btn-lg" title="Próxima imagem">
      <i class="fa fa-chevron-right"></i>
    </button>

    <!-- Container principal da imagem -->
    <div
      style="position:relative;max-width:90vw;max-height:90vh;margin:auto;display:flex;flex-direction:column;align-items:center;justify-content:center;">

      <!-- Informações da imagem -->
      <div id="imageInfo"
        style="position:absolute;top:-60px;left:50%;transform:translateX(-50%);color:white;text-align:center;z-index:10001;">
        <div id="imageTitle" style="font-size:18px;font-weight:bold;margin-bottom:5px;"></div>
        <div id="imageCounter" style="font-size:14px;opacity:0.8;"></div>
      </div>

      <!-- Imagem principal -->
      <img id="imagePreviewModalImg" src="" alt="Preview"
        style="max-width:75vw;max-height:75vh;border-radius:8px;box-shadow:0 0 30px rgba(0,0,0,0.8);cursor:pointer;"
        title="Clique para zoom" />

      <!-- Controles inferiores -->
      <div
        style="position:absolute;bottom:-80px;left:50%;transform:translateX(-50%);display:flex;gap:10px;z-index:10001;">
        <button id="imagePreviewModalClose" class="btn btn-light">
          <i class="fa fa-times"></i> Fechar
        </button>
        <button id="zoomToggleBtn" class="btn btn-secondary">
          <i class="fa fa-search-plus"></i> Zoom
        </button>
        <button id="downloadCurrentBtn" class="btn btn-primary">
          <i class="fa fa-download"></i> Download
        </button>
      </div>
    </div>

    <!-- Miniatura da galeria (parte inferior) -->
    <div id="imageThumbnailGallery"
      style="position:absolute;bottom:20px;left:50%;transform:translateX(-50%);display:flex;gap:8px;max-width:90vw;overflow-x:auto;padding:10px;background:rgba(0,0,0,0.7);border-radius:8px;z-index:10001;">
      <!-- Thumbnails serão adicionadas dinamicamente -->
    </div>
  </div>

  <!-- ADDED: Modal para visualização de PDF -->
  <div id="pdfViewModal"
    style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.75);z-index:10001;align-items:center;justify-content:center;">
    <div
      style="position:relative;width:90%;height:90%;background:#fff;border-radius:8px;overflow:hidden;display:flex;flex-direction:column;">
      <button id="pdfViewModalClose" class="btn btn-light" style="z-index:10002;">Fechar</button>
      <iframe id="pdfViewModalIframe" src="" style="flex:1;border:0;width:100%;height:100%;"></iframe>
    </div>
  </div>

  <!-- ADDED: Modal para reprodução de áudio / vídeo -->
  <div id="mediaModal"
    style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.75);z-index:10002;align-items:center;justify-content:center;">
    <div
      style="position:relative;width:90%;max-width:1100px;height:80%;background:#fff;border-radius:8px;overflow:hidden;display:flex;flex-direction:column;">
      <div
        style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;border-bottom:1px solid #ddd;">
        <div id="mediaTitle" style="font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
        </div>
        <button id="mediaModalClose" class="btn btn-light">Fechar</button>
      </div>
      <div style="flex:1;display:flex;align-items:center;justify-content:center;padding:12px;background:#000;">
        <video id="mediaPlayerVideo" style="width:100dvh;display:none;background:#000;" controls></video>
        <audio id="mediaPlayerAudio" style="width:100dvh;display:none;" controls></audio>
      </div>
    </div>
  </div>

  <!-- Player de áudio flutuante melhorado -->
  <div id="floatingAudioPlayer" style="display:none;">
    <div class="floating-player-header">
      <div class="player-title">
        <i class="fa fa-music"></i>
        <span id="currentTrackName">Nenhuma música</span>
      </div>
      <div class="player-controls-header">
        <button id="togglePlayerBtn" class="btn-player-control" title="Minimizar/Expandir">
          <i class="fa fa-chevron-down"></i>
        </button>
        <button id="closePlayerBtn" class="btn-player-control" title="Fechar Player">
          <i class="fa fa-times"></i>
        </button>
      </div>
    </div>

    <div class="floating-player-body" id="playerBody">
      <div class="player-info">
        <div class="track-info">
          <div class="track-name" id="trackDisplayName">Nenhuma música selecionada</div>
          <div class="track-time">
            <span id="currentTime">0:00</span>
            <span>/</span>
            <span id="totalTime">0:00</span>
          </div>
        </div>
      </div>

      <div class="player-progress">
        <div class="progress-bar-container">
          <div class="progress-bar" id="progressBar">
            <div class="progress-fill" id="progressFill"></div>
            <div class="progress-handle" id="progressHandle"></div>
          </div>
        </div>
      </div>

      <div class="player-controls">
        <button id="prevBtn" class="btn-player-control" title="Anterior">
          <i class="fa fa-step-backward"></i>
        </button>
        <button id="playPauseBtn" class="btn-player-control play-pause-btn" title="Play/Pause">
          <i class="fa fa-play"></i>
        </button>
        <button id="nextBtn" class="btn-player-control" title="Próximo">
          <i class="fa fa-step-forward"></i>
        </button>
        <button id="repeatBtn" class="btn-player-control" title="Repetir">
          <i class="fa fa-repeat"></i>
        </button>
        <button id="shuffleBtn" class="btn-player-control" title="Aleatório">
          <i class="fa fa-random"></i>
        </button>
        <div class="volume-control">
          <button id="muteBtn" class="btn-player-control" title="Mudo">
            <i class="fa fa-volume-up"></i>
          </button>
          <div class="volume-slider">
            <input type="range" id="volumeSlider" min="0" max="100" value="100">
          </div>
        </div>
      </div>

      <div class="playlist-container" id="playlistContainer" style="display:none;">
        <div class="playlist-header">
          <h6>Lista de Reprodução</h6>
          <button id="clearPlaylistBtn" class="btn btn-sm btn-outline-danger">
            <i class="fa fa-trash"></i> Limpar
          </button>
        </div>
        <div class="playlist" id="playlist">
          <!-- Playlist será preenchida dinamicamente -->
        </div>
      </div>
    </div>

    <audio id="floatingAudioElement" preload="metadata"></audio>
  </div>

  <div class="modal fade" id="createFolder" tabindex="-1" aria-labelledby="createFolderLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <!-- Modal Header -->
        <div class="modal-header">
          <h4 class="modal-title" id="createFolderLabel">Criar Pasta</h4>

        </div>
        <!-- Modal body -->
        <div class="modal-body">
          <?php if ($allow_create_folder): ?>
            <form action="?" method="post" id="mkdir" />
            <label for=dirname>Qual será o nome da pasta ?</label>
            <input type="text" class="form-control" id="dirname" placeholder="Nova pasta" name="dirname">
            <div class="modal-footer-alt">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-success" data-bs-dismiss="modal">Criar</button>
            </div>
            </form>
          <?php endif; ?>
          <?php if (!$allow_create_folder): ?>
            <p>Sem permissão para criar pasta</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal de confirmação de exclusão -->
  <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="confirmDeleteLabel">Confirmar Exclusão</h5>

        </div>
        <div class="modal-body">
          Tem certeza que deseja excluir este arquivo ou pasta?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Excluir</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="makeUpload" tabindex="-1" aria-labelledby="makeUploadLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <!-- Modal Header -->
        <div class="modal-header">
          <h4 class="modal-title" id="makeUploadLabel">Carregar arquivos</h4>

        </div>
        <!-- Modal body -->
        <div class="modal-body d-grid">
          <div id="upload_progress"></div>
          <?php if ($allow_upload): ?>
            <div id="file_drop_target">
              Arraste os arquivos aqui para fazer o carregamento<br>
              <b>ou</b>
              <div class="custom-file mb-3">
                <input type="file" multiple class="custom-file-input" id="customFile" name="filename">
                <label class="custom-file-label" for="customFile">Nenhum arquivo selecionado...</label>
              </div>
            </div>
          <?php endif; ?>
          <button type="button" class="btn btn-secondary btn-block mt-3" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ADDED: Modal para criar novo arquivo -->
  <div class="modal fade" id="createFileModal" tabindex="-1" aria-labelledby="createFileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form id="createFileForm">
          <div class="modal-header">
            <h5 class="modal-title" id="createFileModalLabel">Criar Novo Arquivo de Texto</h5>

          </div>
          <div class="modal-body">
            <div class="mb-2">
              <label for="newfilename" class="form-label">Nome do arquivo (ex: novo.txt)</label>
              <input type="text" id="newfilename" class="form-control" placeholder="nome-do-arquivo.txt" required>
            </div>
            <div class="mb-2">
              <label for="newfilecontent" class="form-label">Conteúdo inicial (opcional)</label>
              <textarea id="newfilecontent" class="form-control" rows="6" placeholder="Conteúdo inicial..."></textarea>
            </div>
            <div id="createFileError" class="text-danger" style="display:none;"></div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">Criar Arquivo</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal para informações da pasta -->
  <div class="modal fade" id="folderInfoModal" tabindex="-1" aria-labelledby="folderInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="folderInfoModalLabel">Informações da Pasta</h5>
        </div>
        <div class="modal-body" id="folderInfoContent">
          <!-- Conteúdo preenchido via JS -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ADDED: Modal com CodeMirror para editar arquivos de texto -->
  <div id="textEditModal"
    style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:#252526;z-index:10000;">
    <div style="display:flex;flex-direction:column;width: 100vw;height:100vh;background:#1e1e1e;">

      <!-- Barra superior estilo VS Code -->
      <div class="vscode-titlebar"
        style="background:#3c3c3c;height:35px;display:flex;align-items:center;padding:0 15px;justify-content:space-between;border-bottom:1px solid #464647;">
        <div class="d-flex align-items-center">
          <i class="fa fa-code" style="color:#007acc;margin-right:8px;"></i>
          <span style="color:#cccccc;font-size:13px;font-weight:500;">Editor de Código</span>
          <span style="color:#969696;margin-left:10px;font-size:12px;" id="currentFileName"></span>
        </div>
        <div class="d-flex align-items-center gap-2">
          <button id="minimizeBtn" class="vscode-btn"
            style="color:#cccccc;background:none;border:none;padding:0;width:28px;height:28px;" title="Minimizar">
            <i class="fa fa-window-minimize" style="font-size:10px;"></i>
          </button>
          <button id="maximizeBtn" class="vscode-btn"
            style="color:#cccccc;background:none;border:none;padding:0;width:28px;height:28px;" title="Maximizar">
            <i class="fa fa-window-maximize" style="font-size:10px;"></i>
          </button>
          <button id="textEditClose" class="vscode-btn vscode-close"
            style="color:#cccccc;background:none;border:none;padding:0;width:28px;height:28px;" title="Fechar">
            <i class="fa fa-times" style="font-size:12px;"></i>
          </button>
        </div>
      </div>

      <!-- Barra de abas -->
      <div class="vscode-tabs"
        style="background:#2d2d30;height:35px;display:flex;align-items:center;border-bottom:1px solid #464647;">
        <div class="tab-active"
          style="background:#1e1e1e;height:100%;display:flex;align-items:center;padding:10px 15px;border-right:1px solid #464647;min-width:150px;">
          <i class="fa fa-file-code-o" style="color:#75beff;margin-right:8px;font-size:12px;"></i>
          <span style="color:#cccccc;font-size:12px;" id="tabFileName">arquivo.txt</span>
        </div>
      </div>

      <!-- Barra de ferramentas estilo VS Code -->
      <div class="vscode-toolbar"
        style="background:#2d2d30;border-bottom:1px solid #464647;padding:8px 15px;display:flex;flex-wrap:wrap;align-items:center;gap:15px;">

        <!-- Grupo 1: Linguagem e configurações -->
        <div class="d-flex align-items-center gap-10px">
          <select id="languageSelect" class="vscode-select"
            style="background:#3c3c3c;color:#cccccc;border:1px solid #464647;border-radius:3px;padding:4px 8px;font-size:11px;">
            <option value="text">Plain Text</option>
            <option value="javascript">JavaScript</option>
            <option value="application/json">JSON</option>
            <option value="css">CSS</option>
            <option value="htmlmixed">HTML</option>
            <option value="xml">XML</option>
            <option value="php">PHP</option>
            <option value="python">Python</option>
            <option value="sql">SQL</option>
            <option value="markdown">Markdown</option>
            <option value="yaml">YAML</option>
            <option value="shell">Shell</option>
            <option value="dockerfile">Dockerfile</option>
            <option value="text/x-csrc">C</option>
            <option value="text/x-c++src">C++</option>
            <option value="text/x-java">Java</option>
          </select>

          <select id="themeSelect" class="vscode-select"
            style="background:#3c3c3c;color:#cccccc;border:1px solid #464647;border-radius:3px;padding:4px 8px;font-size:11px;">
            <option value="default">Light</option>
            <option value="monokai" selected>Dark+ (default dark)</option>
            <option value="dracula">Dracula</option>
            <option value="material">Material Dark</option>
            <option value="solarized light">Solarized Light</option>
            <option value="solarized dark">Solarized Dark</option>
          </select>

          <select id="fontSizeSelect" class="vscode-select"
            style="background:#3c3c3c;color:#cccccc;border:1px solid #464647;border-radius:3px;padding:4px 8px;font-size:11px;width:60px;">
            <option value="11px">11</option>
            <option value="12px">12</option>
            <option value="13px">13</option>
            <option value="14px" selected>14</option>
            <option value="16px">16</option>
            <option value="18px">18</option>
            <option value="20px">20</option>
          </select>
        </div>

        <!-- Separador -->
        <div style="width:1px;height:20px;background:#464647;margin:0 5px;"></div>

        <!-- Grupo 2: Ações do editor -->
        <div class="d-flex align-items-center gap-5px">
          <button type="button" class="vscode-toolbar-btn" id="saveTextBtn" title="Salvar (Ctrl+S)"
            style="background:#007acc;color:white;">
            <i class="fa fa-save"></i>
          </button>
          <button type="button" class="vscode-toolbar-btn" id="searchBtn" title="Buscar (Ctrl+F)">
            <i class="fa fa-search"></i>
          </button>
          <button type="button" class="vscode-toolbar-btn" id="replaceBtn" title="Substituir (Ctrl+H)">
            <i class="fa fa-exchange"></i>
          </button>
          <button type="button" class="vscode-toolbar-btn" id="gotoLineBtn" title="Ir para linha (Alt+G)">
            <i class="fa fa-hashtag"></i>
          </button>
          <button type="button" class="vscode-toolbar-btn" id="foldBtn" title="Dobrar código (Ctrl+Q)">
            <i class="fa fa-minus-square-o"></i>
          </button>
          <button type="button" class="vscode-toolbar-btn" id="unfoldAllBtn" title="Expandir tudo (Ctrl+Shift+Q)">
            <i class="fa fa-plus-square-o"></i>
          </button>
          <button type="button" class="vscode-toolbar-btn" id="previewBtn" title="Preview (F5)">
            <i class="fa fa-eye"></i>
          </button>
          <button type="button" class="vscode-toolbar-btn" id="indentBtn" title="Indentar linhas selecionadas (Tab)">
            <i class="fa fa-indent"></i>
          </button>
          <button type="button" class="vscode-toolbar-btn" id="unindentBtn" title="Remover indentação (Shift+Tab)">
            <i class="fa fa-outdent"></i>
          </button>
          <button type="button" class="vscode-toolbar-btn" id="formatCodeBtn" title="Formatar código (Shift+Alt+F)">
            <i class="fa fa-magic"></i>
          </button>
        </div>

        <!-- Separador -->
        <div style="width:1px;height:20px;background:#464647;margin:0 5px;"></div>

        <!-- Grupo 3: Opções -->
        <div class="d-flex align-items-center gap-15px">
          <label class="vscode-checkbox">
            <input type="checkbox" id="wrapLinesToggle" checked>
            <span>Word Wrap</span>
          </label>
          <label class="vscode-checkbox">
            <input type="checkbox" id="showLineNumbers" checked>
            <span>Line Numbers</span>
          </label>
        </div>
      </div>

      <!-- Área do CodeMirror e Preview -->
      <div id="editorContainer" class="d-flex" style="height: calc(90% - 30px);gap: 10px;overflow: auto;">
        <!-- Editor -->
        <div id="codeMirrorContainer" style="flex:1;border:1px solid #ddd;border-radius:4px;">
          <textarea id="codeEditor"></textarea>
        </div>

        <!-- Preview (inicialmente oculto) -->
        <div id="previewContainer"
          style="flex:1;border:1px solid #ddd;border-radius:4px;background:#fff;overflow:auto;display:none;">
          <div id="previewHeader" style="background:#f8f9fa;padding:8px;border-bottom:1px solid #ddd;font-weight:500;">
            <i class="fa fa-eye"></i> Preview
            <button type="button" class="btn btn-sm btn-outline-secondary float-end" id="closePreviewBtn"
              title="Fechar Preview">
              <i class="fa fa-times"></i>
            </button>
          </div>
          <div id="previewContent" style="padding:15px;"></div>
        </div>
      </div>

      <!-- Barra de status estilo VS Code -->
      <div class="vscode-status-bar">
        <div class="d-flex align-items-center gap-15px">
          <span id="editorInfo" style="color:#ffffff;">Ln 1, Col 1</span>
          <span id="encodingInfo" style="color:#ffffff;">UTF-8</span>
          <span id="languageInfo" style="color:#ffffff;">Plain Text</span>
        </div>
        <div class="d-flex align-items-center gap-10px">
          <span id="textEditStatus" style="color:#ffffff;"></span>
        </div>
      </div>
    </div>
  </div>
  <script src="assets/manager-1.js"></script>

  <!-- ADDED: Modal para gerar link de compartilhamento -->
  <div class="modal fade" id="shareModal" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="shareModalLabel">
            <i class="fa fa-share-alt"></i> Gerar Link de Compartilhamento
          </h5>

        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label"><strong>Arquivo:</strong></label>
            <div id="shareFileName" class="form-control-plaintext text-success fw-bold"></div>
          </div>

          <div class="mb-3">
            <label for="sharePassword" class="form-label">Senha (opcional)</label>
            <input type="password" class="form-control" id="sharePassword"
              placeholder="Deixe em branco para link sem senha">
            <div class="form-text text-warning">Se definida, será necessário inserir esta senha para acessar o arquivo.
            </div>
          </div>

          <div class="mb-3">
            <label for="shareExpires" class="form-label">Expiração</label>
            <select class="form-select" id="shareExpires">
              <option value="1">1 hora</option>
              <option value="6">6 horas</option>
              <option value="24" selected>24 horas</option>
              <option value="72">3 dias</option>
              <option value="168">7 dias</option>
              <option value="720">30 dias</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="shareMaxDownloads" class="form-label">Limite de downloads</label>
            <select class="form-select" id="shareMaxDownloads">
              <option value="0" selected>Ilimitado</option>
              <option value="1">1 download</option>
              <option value="5">5 downloads</option>
              <option value="10">10 downloads</option>
              <option value="50">50 downloads</option>
            </select>
          </div>

          <div id="shareResult" style="display:none;" class="mt-3">
            <div class="alert alert-success">
              <h6><i class="fa fa-check-circle"></i> Link criado com sucesso!</h6>
              <div class="mb-2">
                <label class="form-label">URL de Compartilhamento:</label>
                <div class="input-group">
                  <input type="text" class="form-control" id="shareUrl" readonly>
                  <button class="btn btn-outline-secondary" type="button" id="copyShareUrl">
                    <i class="fa fa-copy"></i> Copiar
                  </button>
                </div>
              </div>
              <div class="small text-muted" id="shareInfo"></div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-primary" id="generateShareBtn">
            <i class="fa fa-share-alt"></i> Gerar Link
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- ADDED: Modal para gerenciar links de compartilhamento -->
  <div class="modal fade" id="shareLinksModal" tabindex="-1" aria-labelledby="shareLinksModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="shareLinksModalLabel">
            <i class="fa fa-share-alt"></i> Gerenciar Links de Compartilhamento
          </h5>

        </div>
        <div class="modal-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6>Links Ativos</h6>
            <button class="btn btn-outline-primary" id="refreshShareLinks">
              <i class="fa fa-refresh"></i> Atualizar
            </button>
          </div>

          <div class="table-responsive">
            <table class="table table-striped">
              <thead class="table-dark">
                <tr>
                  <th>Arquivo</th>
                  <th>Criado</th>
                  <th>Expira</th>
                  <th>Status</th>
                  <th>Downloads</th>
                  <th>Senha</th>
                  <th>Link de Compartilhamento</th>
                  <th>Ações</th>
                </tr>
              </thead>
              <tbody id="shareLinksTable">
                <tr>
                  <td colspan="7" class="text-center">Carregando...</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ADDED: Modal para conversão/compressão de imagens -->
  <div class="modal fade" id="convertImagesModal" tabindex="-1" aria-labelledby="convertImagesModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="convertImagesModalLabel">
            <i class="fa fa-image"></i> Converter e Comprimir Imagens
          </h5>

        </div>
        <div class="modal-body">
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="outputFormat" class="form-label">Formato de saída:</label>
              <select class="form-select" id="outputFormat">
                <option value="webp">WebP (Recomendado - Melhor compressão)</option>
                <option value="jpeg">JPEG (Universal)</option>
                <option value="png">PNG (Sem perda de qualidade)</option>
                <option value="avif">AVIF (Compressão avançada)</option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="imageQuality" class="form-label">Qualidade (<span class="text-white"
                  id="qualityValue">85</span>%):</label>
              <input type="range" class="form-range" id="imageQuality" min="10" max="100" value="85">
              <div class="form-text text-white">Menor = arquivo menor, mas pior qualidade</div>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label for="maxWidth" class="form-label">Largura máxima (px):</label>
              <input type="number" class="form-control" id="maxWidth" value="1920" min="100" max="4000">
              <div class="form-text text-white">Redimensionar se maior que este valor</div>
            </div>
            <div class="col-md-6">
              <label for="maxHeight" class="form-label">Altura máxima (px):</label>
              <input type="number" class="form-control" id="maxHeight" value="1080" min="100" max="4000">
              <div class="form-text text-white">Redimensionar se maior que este valor</div>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="preserveOriginal" checked>
                <label class="form-check-label" for="preserveOriginal">
                  Manter arquivos originais
                </label>
                <div class="form-text text-white">Se desmarcado, substitui os originais</div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="addSuffix" checked>
                <label class="form-check-label" for="addSuffix">
                  Adicionar sufixo "_converted"
                </label>
                <div class="form-text text-white">Para diferenciar dos originais</div>
              </div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Imagens selecionadas para conversão:</label>
            <div id="selectedImagesList" class="border rounded p-2 text-white"
              style="max-height: 200px; overflow-y: auto;">
              <small class="text-muted text-white">Carregando...</small>
            </div>
          </div>

          <div id="conversionProgress" style="display:none;" class="mb-3">
            <label class="form-label">Progresso da conversão:</label>
            <div class="progress mb-2">
              <div id="conversionProgressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                role="progressbar" style="width: 0%">0%</div>
            </div>
            <div id="conversionStatus">Preparando conversão...</div>
          </div>

          <div id="conversionResults" style="display:none;" class="mt-3">
            <h6>Resultados da conversão:</h6>
            <div id="resultsContainer"></div>

            <!-- Botão adicional para fechar e atualizar após conversão bem-sucedida -->
            <div id="conversionCompleteActions" style="display:none;" class="mt-3 text-center">
              <button type="button" class="btn btn-success" data-bs-dismiss="modal">
                <i class="fa fa-check-circle"></i> Fechar e Ver Novos Arquivos
              </button>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-primary" id="startConversionBtn">
            <i class="fa fa-cog"></i> Iniciar Conversão
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- ADDED: Modal para conversão de áudio -->
  <div class="modal fade" id="convertAudioModal" tabindex="-1" aria-labelledby="convertAudioModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="convertAudioModalLabel">
            <i class="fa fa-music"></i> Converter Áudio
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="audioOutputFormat" class="form-label">Formato de saída:</label>
              <select class="form-select" id="audioOutputFormat">
                <option value="mp3">MP3 (Universal)</option>
                <option value="wav">WAV (Sem compressão)</option>
                <option value="ogg">OGG (Open Source)</option>
                <option value="aac">AAC (Qualidade/Tamanho)</option>
                <option value="m4a">M4A (Apple)</option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="audioQuality" class="form-label">Qualidade:</label>
              <select class="form-select" id="audioQuality">
                <option value="low">Baixa (96 kbps)</option>
                <option value="medium">Média (128 kbps)</option>
                <option value="high" selected>Alta (192 kbps)</option>
                <option value="custom">Personalizada</option>
              </select>
            </div>
          </div>

          <div class="row mb-3" id="customBitrateRow" style="display: none;">
            <div class="col-md-6">
              <label for="audioBitrate" class="form-label">Bitrate personalizado (kbps):</label>
              <input type="number" class="form-control" id="audioBitrate" value="192" min="64" max="320">
              <div class="form-text text-white">64 a 320 kbps</div>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="audioPreserveOriginal" checked>
                <label class="form-check-label" for="audioPreserveOriginal">
                  Manter arquivos originais
                </label>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="audioAddSuffix" checked>
                <label class="form-check-label" for="audioAddSuffix">
                  Adicionar sufixo "_converted"
                </label>
              </div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Arquivos de áudio selecionados:</label>
            <div id="selectedAudioList" class="border rounded p-2 text-white"
              style="max-height: 200px; overflow-y: auto;">
              <small class="text-muted text-white">Carregando...</small>
            </div>
          </div>

          <div id="audioConversionProgress" style="display:none;" class="mb-3">
            <label class="form-label">Progresso da conversão:</label>
            <div class="progress mb-2">
              <div id="audioConversionProgressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                role="progressbar" style="width: 0%">0%</div>
            </div>
            <div id="audioConversionStatus">Preparando conversão...</div>
          </div>

          <div id="audioConversionResults" style="display:none;overflow-y: scroll;height: 300px;" class="mt-3">
            <h6>Resultados da conversão:</h6>
            <div id="audioResultsContainer"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-primary" id="startAudioConversionBtn">
            <i class="fa fa-cog"></i> Iniciar Conversão
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- ADDED: Modal para compressão de arquivos -->
  <div class="modal fade" id="compressFilesModal" tabindex="-1" aria-labelledby="compressFilesModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="compressFilesModalLabel">
            <i class="fa fa-compress"></i> Comprimir Arquivos / Criar ZIP
          </h5>
        </div>
        <div class="modal-body">
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="compressionType" class="form-label">Tipo de compressão:</label>
              <select class="form-select" id="compressionType">
                <option value="zip">ZIP (Universal)</option>
                <option value="tar">TAR (Linux/Unix)</option>
                <option value="tar.gz">TAR.GZ (Compressão gzip)</option>
                <option value="tar.bz2">TAR.BZ2 (Compressão bzip2)</option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="compressionLevel" class="form-label">Nível de compressão (<span
                  id="compressionLevelValue">6</span>):</label>
              <input type="range" class="form-range" id="compressionLevel" min="0" max="9" value="6">
              <div class="form-text text-white">0 = Sem compressão (rápido), 9 = Máxima compressão (lento)</div>
            </div>
          </div>

          <div class="mb-3">
            <label for="archiveName" class="form-label">Nome do arquivo comprimido:</label>
            <input type="text" class="form-control" id="archiveName" placeholder="arquivo_comprimido">
            <div class="form-text text-white">Deixe vazio para gerar automaticamente</div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="preservePaths" checked>
                <label class="form-check-label" for="preservePaths">
                  Preservar estrutura de pastas
                </label>
                <div class="form-text text-white">Manter hierarquia de diretórios no arquivo</div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="autoDownload" checked>
                <label class="form-check-label" for="autoDownload">
                  Download automático
                </label>
                <div class="form-text text-white">Baixar automaticamente após criação</div>
              </div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Arquivos selecionados para compressão:</label>
            <div id="selectedCompressionList" class="border rounded p-2 text-white"
              style="max-height: 200px; overflow-y: auto;">
              <small class="text-muted text-white">Carregando...</small>
            </div>
          </div>

          <div id="compressionProgress" style="display:none;" class="mb-3">
            <label class="form-label">Progresso da compressão:</label>
            <div class="progress mb-2">
              <div id="compressionProgressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                role="progressbar" style="width: 0%">0%</div>
            </div>
            <div id="compressionStatus">Preparando compressão...</div>
          </div>

          <div id="compressionResults" style="display:none;" class="mt-3">
            <h6>Resultado da compressão:</h6>
            <div id="compressionResultsContainer"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-primary" id="startCompressionBtn">
            <i class="fa fa-compress"></i> Iniciar Compressão
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- ADDED: Modal de Configurações (visível somente via $permissionAdmin) -->
  <div class="modal fade" id="configModal" tabindex="-1" aria-labelledby="configModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form id="configForm">
          <div class="modal-header">
            <h5 class="modal-title" id="configModalLabel">Configurações Principais</h5>

          </div>
          <div class="modal-body">
            <div class="form-check mb-2">
              <input class="form-check-input" type="checkbox" id="cfg_allow_delete">
              <label class="form-check-label" for="cfg_allow_delete">Permitir exclusão
                (allow_delete)</label>
            </div>
            <div class="form-check mb-2">
              <input class="form-check-input" type="checkbox" id="cfg_allow_upload">
              <label class="form-check-label" for="cfg_allow_upload">Permitir upload
                (allow_upload)</label>
            </div>
            <div class="form-check mb-2">
              <input class="form-check-input" type="checkbox" id="cfg_allow_create_folder">
              <label class="form-check-label" for="cfg_allow_create_folder">Permitir criar pasta
                (allow_create_folder)</label>
            </div>
            <div class="form-check mb-2">
              <input class="form-check-input" type="checkbox" id="cfg_allow_create_file">
              <label class="form-check-label" for="cfg_allow_create_file">Permitir criar arquivo
                (allow_create_file)</label>
            </div>
            <div class="form-check mb-2">
              <input class="form-check-input" type="checkbox" id="cfg_allow_direct_link">
              <label class="form-check-label" for="cfg_allow_direct_link">Permitir link direto
                (allow_direct_link)</label>
            </div>
            <div class="form-check mb-2">
              <input class="form-check-input" type="checkbox" id="cfg_allow_show_folders">
              <label class="form-check-label" for="cfg_allow_show_folders">Mostrar subpastas
                (allow_show_folders)</label>
            </div>
            <div class="mb-2">
              <label for="cfg_configTime" class="form-label">Tempo de sessão (minutos) -
                configTime</label>
              <input type="number" id="cfg_configTime" class="form-control" min="1">
            </div>
            <div class="mb-2">
              <label for="cfg_disallowed" class="form-label">Padrões proibidos (vírgula separada)</label>
              <input type="text" id="cfg_disallowed" class="form-control" placeholder="*.php, *.exe">
            </div>
            <div class="mb-2">
              <label for="cfg_hidden" class="form-label">Padrões ocultos (vírgula separada)</label>
              <input type="text" id="cfg_hidden" class="form-control" placeholder="*.php, *.css, .*">
            </div>
            <div class="mb-2">
              <label for="cfg_SENHA" class="form-label">Senha de acesso (SENHA)</label>
              <input type="text" id="cfg_SENHA" class="form-control" placeholder="abc123">
            </div>
            <div id="configSaveStatus" class="text-success" style="display:none;"></div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" id="saveConfigBtn" class="btn btn-primary">Salvar Configurações</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <!-- end config modal -->

  <!-- Modal de Integração com Nuvem -->
  <div class="modal fade" id="cloudModal" tabindex="-1" aria-labelledby="cloudModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="cloudModalLabel">Integração com Armazenamento em Nuvem</h5>

        </div>
        <div class="modal-body">
          <!-- Tabs para diferentes provedores -->
          <ul class="nav nav-tabs" id="cloudTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="googledrive-tab" data-bs-toggle="tab" data-bs-target="#googledrive"
                type="button" role="tab">
                <i class="fa fa-google"></i> Google Drive
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="dropbox-tab" data-bs-toggle="tab" data-bs-target="#dropbox" type="button"
                role="tab">
                <i class="fa fa-dropbox"></i> Dropbox
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="onedrive-tab" data-bs-toggle="tab" data-bs-target="#onedrive" type="button"
                role="tab">
                <i class="fa fa-windows"></i> OneDrive
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="cloudmanager-tab" data-bs-toggle="tab" data-bs-target="#cloudmanager"
                type="button" role="tab">
                <i class="fa fa-folder-open"></i> Gerenciar Arquivos
              </button>
            </li>
          </ul>

          <div class="tab-content" id="cloudTabsContent">
            <!-- Google Drive -->
            <div class="tab-pane fade show active" id="googledrive" role="tabpanel">
              <div class="mt-3">
                <h6>Configurações do Google Drive</h6>
                <form id="googleDriveForm">
                  <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="gd_enabled">
                    <label class="form-check-label" for="gd_enabled">Habilitar Google Drive</label>
                  </div>
                  <div class="mb-2">
                    <label for="gd_client_id" class="form-label">Client ID</label>
                    <input type="text" id="gd_client_id" class="form-control" placeholder="Seu Client ID do Google">
                  </div>
                  <div class="mb-2">
                    <label for="gd_client_secret" class="form-label">Client Secret</label>
                    <input type="password" id="gd_client_secret" class="form-control"
                      placeholder="Seu Client Secret do Google">
                  </div>
                  <div class="mb-2">
                    <label for="gd_redirect_uri" class="form-label">Redirect URI</label>
                    <input type="url" id="gd_redirect_uri" class="form-control"
                      placeholder="https://seusite.com/callback">
                  </div>
                  <div class="mb-2">
                    <label for="gd_access_token" class="form-label">Access Token</label>
                    <input type="password" id="gd_access_token" class="form-control"
                      placeholder="Token de acesso (opcional)">
                  </div>
                  <button type="button" class="btn btn-primary" onclick="saveCloudConfig('google_drive')">Salvar Google
                    Drive</button>
                  <button type="button" class="btn btn-info" onclick="testCloudConnection('google_drive')">Testar
                    Conexão</button>
                </form>
              </div>
            </div>

            <!-- Dropbox -->
            <div class="tab-pane fade" id="dropbox" role="tabpanel">
              <div class="mt-3">
                <h6>Configurações do Dropbox</h6>
                <form id="dropboxForm">
                  <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="db_enabled">
                    <label class="form-check-label" for="db_enabled">Habilitar Dropbox</label>
                  </div>
                  <div class="mb-2">
                    <label for="db_app_key" class="form-label">App Key</label>
                    <input type="text" id="db_app_key" class="form-control" placeholder="Sua App Key do Dropbox">
                  </div>
                  <div class="mb-2">
                    <label for="db_app_secret" class="form-label">App Secret</label>
                    <input type="password" id="db_app_secret" class="form-control"
                      placeholder="Sua App Secret do Dropbox">
                  </div>
                  <div class="mb-2">
                    <label for="db_access_token" class="form-label">Access Token</label>
                    <input type="password" id="db_access_token" class="form-control"
                      placeholder="Token de acesso (opcional)">
                  </div>
                  <button type="button" class="btn btn-primary" onclick="saveCloudConfig('dropbox')">Salvar
                    Dropbox</button>
                  <button type="button" class="btn btn-info" onclick="testCloudConnection('dropbox')">Testar
                    Conexão</button>
                </form>
              </div>
            </div>

            <!-- OneDrive -->
            <div class="tab-pane fade" id="onedrive" role="tabpanel">
              <div class="mt-3">
                <h6>Configurações do OneDrive</h6>
                <form id="oneDriveForm">
                  <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="od_enabled">
                    <label class="form-check-label" for="od_enabled">Habilitar OneDrive</label>
                  </div>
                  <div class="mb-2">
                    <label for="od_client_id" class="form-label">Client ID</label>
                    <input type="text" id="od_client_id" class="form-control" placeholder="Seu Client ID do Microsoft">
                  </div>
                  <div class="mb-2">
                    <label for="od_client_secret" class="form-label">Client Secret</label>
                    <input type="password" id="od_client_secret" class="form-control"
                      placeholder="Seu Client Secret do Microsoft">
                  </div>
                  <div class="mb-2">
                    <label for="od_redirect_uri" class="form-label">Redirect URI</label>
                    <input type="url" id="od_redirect_uri" class="form-control"
                      placeholder="https://seusite.com/callback">
                  </div>
                  <div class="mb-2">
                    <label for="od_access_token" class="form-label">Access Token</label>
                    <input type="password" id="od_access_token" class="form-control"
                      placeholder="Token de acesso (opcional)">
                  </div>
                  <button type="button" class="btn btn-primary" onclick="saveCloudConfig('onedrive')">Salvar
                    OneDrive</button>
                  <button type="button" class="btn btn-info" onclick="testCloudConnection('onedrive')">Testar
                    Conexão</button>
                </form>
              </div>
            </div>

            <!-- Gerenciador de Arquivos da Nuvem -->
            <div class="tab-pane fade" id="cloudmanager" role="tabpanel">
              <div class="mt-3">
                <h6>Gerenciar Arquivos na Nuvem</h6>

                <!-- Seletor de provedor -->
                <div class="mb-3">
                  <label for="cloudProvider" class="form-label">Provedor:</label>
                  <select id="cloudProvider" class="form-select" onchange="loadCloudFiles()">
                    <option value="">Selecione um provedor</option>
                    <option value="google_drive">Google Drive</option>
                    <option value="dropbox">Dropbox</option>
                    <option value="onedrive">OneDrive</option>
                  </select>
                </div>

                <!-- Botões de ação -->
                <div class="mb-3">
                  <button type="button" class="btn btn-success" onclick="uploadToCloud()">Upload para Nuvem</button>
                  <button type="button" class="btn btn-primary" onclick="downloadFromCloud()">Download da Nuvem</button>
                  <button type="button" class="btn btn-warning" onclick="syncWithCloud()">Sincronizar</button>
                  <button type="button" class="btn btn-info" onclick="loadCloudFiles()">Atualizar Lista</button>
                </div>

                <!-- Lista de arquivos da nuvem -->
                <div id="cloudFilesList" class="border rounded p-3" style="max-height: 400px; overflow-y: auto;">
                  <p class="text-white">Selecione um provedor para ver os arquivos</p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
        </div>
      </div>
    </div>
  </div>
  <!-- end cloud modal -->

  <!-- Modal para Mover/Copiar Arquivos/Pastas -->
  <div class="modal fade" id="moveCopyModal" tabindex="-1" aria-labelledby="moveCopyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="moveCopyModalLabel">Mover / Copiar Selecionados</h5>

        </div>
        <div class="modal-body">
          <p id="moveCopySummary">Nenhum item selecionado.</p>

          <div class="mb-2">
            <label class="form-label">Ação</label>
            <div class="d-flex gap-3">
              <input type="radio" name="movecopy_action" id="moveAction" value="move" checked>
              <label for="moveAction">Mover</label>
              <input type="radio" name="movecopy_action" id="copyAction" value="copy">
              <label for="copyAction">Copiar</label>
            </div>
          </div>

          <div class="mb-2">
            <label for="moveCopyDest" class="form-label">Destino</label>
            <div class="position-relative">
              <select id="moveCopyDest" class="form-select"></select>
              <div id="loadingSpinner" class="position-absolute top-50 end-0 translate-middle-y me-2"
                style="display: none;">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                  <span class="visually-hidden">Carregando...</span>
                </div>
              </div>
            </div>
            <small class="form-text text-warning">Escolha a pasta de destino. Todas as pastas serão listadas
              automaticamente.</small>
          </div>

          <div class="mb-2">
            <label class="form-label">Arquivos / Pastas selecionados</label>
            <ul id="moveCopyFileList" style="max-height:200px;overflow:auto;padding-left:16px;margin:0;">
            </ul>
          </div>

          <div id="moveCopyResult" style="display:none;" class="mt-2"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" id="confirmMoveCopyBtn" class="btn btn-primary">Executar</button>
        </div>
      </div>
    </div>
  </div>

  <script src="assets/manager-2.js"></script>

  <script src="assets/manager-4.js"></script>

  <!-- Indicador de atalhos de teclado para modais -->
  <div id="modal-shortcuts-hint" class="modal-shortcuts-hint">
    <div class="shortcut"><span class="key">Alt</span> + <span class="key">Enter</span> = Tela Cheia</div>
    <div class="shortcut"><span class="key">Ctrl</span> + <span class="key">M</span> = Minimizar</div>
    <div class="shortcut">Arrastar cabeçalho = Mover Modal</div>
    <div class="shortcut">Canto inferior direito = Redimensionar</div>
  </div>

</body>

</html>