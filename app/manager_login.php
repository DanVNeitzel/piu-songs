<?php
if ((string) $SENHA !== '') {

  piu_session();
  // Expiração automática da sessão baseada em inatividade (valor em milissegundos)
  // Se configTime for 0 ou vazio, não aplica controle de expiração
  if ($configTime > 0) {
    $timeout = $configTime * 60 * 1000;
    if (isset($_SESSION['LAST_ACTIVITY']) && ((time() * 1000) - $_SESSION['LAST_ACTIVITY'] > $timeout)) {
      session_unset();
      session_destroy();
      header('Location: ?');
      exit;
    }
    $_SESSION['LAST_ACTIVITY'] = time() * 1000;
  } else {
    // Quando configTime é 0, definir timeout como 0 para desabilitar no JavaScript também
    $timeout = 0;
  }
  if (empty($_SESSION['_sfm_allowed'])) {
    if (isset($_POST['p']) && $_POST['p'] && is_string($_POST['p']) && hash_equals((string) $SENHA, $_POST['p'])) {
      session_regenerate_id(true);
      $_SESSION['_sfm_allowed'] = true;
      header('Location: ?');
      exit;
    }
    echo '
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
      <meta charset="UTF-8">
      <title>[Login] - Gerenciador de Arquivos</title>
      <link data-rh="true" rel="icon" href="../files_assets/icons/files-logo.svg">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"/>
      <style>
        /* Destaque visual para links de pastas e arquivos */
        .name-dir {
          color: #ffc107 !important; /* amarelo pasta */
          font-weight: 600;
        }
        .name-arc {
          color: #bfc9d1 !important; /* cinza claro arquivo */
          font-weight: 500;
        }
        html, body {
          height: 100%;
          margin: 0;
          padding: 0;
          background: linear-gradient(120deg, #1f8657 0%, #191f24 100%);
          font-family: "Segoe UI", Arial, sans-serif;
          width: 100vw;
          height: 100vh;
          transition: background-image 3s ease;
        }
        body {
          display: flex;
          align-items: center;
          justify-content: center;
          min-height: 100vh;
          position: relative;
          z-index: 1;
        }
        .bg-slideshow, .bg-slideshow-next {
          position: fixed;
          top: 0; left: 0;
          width: 100vw; height: 100vh;
          z-index: 0;
          background-size: cover;
          background-position: center;
          background-repeat: no-repeat;
          transition: opacity 1s ease;
          pointer-events: none;
          opacity: 1;
        }
        .bg-slideshow-next {
          opacity: 0;
        }
        .login-container {
          background: rgb(0 0 0 / 25%);
          border-radius: 18px;
          box-shadow: 0 8px 32px 0 rgba(31, 135, 85, 0.37);
          backdrop-filter: blur(25px);
          -webkit-backdrop-filter: blur(25px);
          border: 1px solid rgba(255, 255, 255, 0.18);
          padding: 36px 36px 36px 36px;
          display: flex;
          flex-direction: column;
          align-items: center;
          min-width: 340px;
          z-index: 2;
          position: relative;
        }
        .login-avatar {
          width: 96px;
          height: 96px;
          border-radius: 50%;
          background: #e0f2e9;
          margin-bottom: 18px;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 56px;
          color: #1f8657;
          box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .login-title {
          font-size: 1.5rem;
          color: #ffffff;
          margin-bottom: 8px;
          font-weight: 500;
          letter-spacing: 0.5px;
        }
        .login-subtitle {
          color: #ffffff;
          font-size: 1rem;
          margin-bottom: 24px;
        }
        .login-form {
          width: 100%;
          display: flex;
          flex-direction: column;
          align-items: stretch;
        }
        .login-input {
          padding: 12px 16px;
          border-radius: 8px;
          border: 1.5px solid #8fd6b4;
          background: rgba(255,255,255,0.15);
          color: #fff;
          font-size: 1.1rem;
          margin-bottom: 18px;
          outline: none;
          transition: border 0.2s;
        }
        .login-input:focus {
          border: 1.5px solid #1f8657;
          background: rgba(255,255,255,0.22);
        }
        .login-btn {
          background: #1f8657;
          color: #fff;
          border: none;
          border-radius: 8px;
          padding: 12px 0;
          font-size: 1.1rem;
          font-weight: 500;
          cursor: pointer;
          transition: background 0.2s;
          margin-bottom: 8px;
        }
        .login-btn:hover {
          background: #176b44;
        }
        .login-footer {
          margin-top: 18px;
          color: #1f8657;
          font-size: 0.95rem;
          text-align: center;
        }
        @media (max-width: 480px) {
          .login-container {
            min-width: 90vw;
            padding: 32px 8vw 24px 8vw;
          }
        }
        ::-webkit-input-placeholder {
          color: #ffffff;
        }

        :-moz-placeholder { /* Firefox 18- */
          color: #ffffff;
        }

        ::-moz-placeholder {  /* Firefox 19+ */
          color: #ffffff;
        }

        :-ms-input-placeholder {  
          color: #ffffff;
        }
        .hide {
          display: none;
        }
        /* Garante que o estilo de pastas funcione igual nos dois modos */
        .is_dir .name,
        .is-dir .name {
          color: #ffc107 !important;
          font-weight: 600;
        }
        .empty {
          color: #777;
          font-style: italic;
          text-align: center;
          padding: 3em 0;
        }
        /* Esconder a barra de rolagem customizada do sidebar */
        .sidebar::-webkit-scrollbar {
          width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
          background: rgba(255, 255, 255, 0.1);
        }

        .sidebar::-webkit-scrollbar-thumb {
          background: rgba(78, 205, 196, 0.5);
          border-radius: 3px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
          background: rgba(78, 205, 196, 0.8);
        }
      </style>
    </head>
    <body>
      <div class="bg-slideshow"></div>
      <div class="bg-slideshow-next"></div>
      <div class="login-container">
        <div class="login-avatar hide">
          <i class="fa fa-user"></i>
        </div>
        <div class="login-title">Bem-vindo</div>
        <div class="login-subtitle">Digite a senha para acessar</div>
        <form class="login-form" action="?" method="post" autocomplete="off">
          <input class="login-input" type="password" name="p" placeholder="Senha" autofocus required>
          <button class="login-btn" type="submit">Entrar</button>
        </form>
        <div class="login-footer">
          <span><i class="fa fa-folder"></i> Gerenciador de Arquivos</span>
        </div>
      </div>
      <script>
      // Lista de imagens de fundo (adicione URLs absolutas ou relativas conforme necessário)
      var bgImages = [
        "https://images.pexels.com/photos/417074/pexels-photo-417074.jpeg",
        "https://images.pexels.com/photos/15905161/pexels-photo-15905161.jpeg",
        "https://images.pexels.com/photos/5272416/pexels-photo-5272416.jpeg",
        "https://images.pexels.com/photos/10059884/pexels-photo-10059884.jpeg",
        "https://images.pexels.com/photos/2049422/pexels-photo-2049422.jpeg",
      ];
      function shuffle(arr) {
        for (let i = arr.length - 1; i > 0; i--) {
          const j = Math.floor(Math.random() * (i + 1));
          [arr[i], arr[j]] = [arr[j], arr[i]];
        }
        return arr;
      }
      bgImages = shuffle(bgImages);
      var idx = 0;
      var fadeDuration = 3000; // ms

      var bg1 = document.querySelector(".bg-slideshow");
      var bg2 = document.querySelector(".bg-slideshow-next");
      var showingFirst = true;

      function setBg(first) {
        var el = first ? bg1 : bg2;
        el.style.backgroundImage = "url(\"" + bgImages[idx] + "\")";
      }

      // Inicializa
      setBg(true);
      bg1.style.opacity = 1;
      bg2.style.opacity = 0;

      function fadeTransition() {
        var nextIdx = (idx + 1) % bgImages.length;
        var showEl = showingFirst ? bg2 : bg1;
        var hideEl = showingFirst ? bg1 : bg2;

        // Prepara próxima imagem
        showEl.style.backgroundImage = "url(\"" + bgImages[nextIdx] + "\")";
        showEl.style.transition = "opacity " + (fadeDuration/1000) + "s";
        hideEl.style.transition = "opacity " + (fadeDuration/1000) + "s";

        // Garante que showEl está por cima
        showEl.style.zIndex = 1;
        hideEl.style.zIndex = 0;

        // Inicia fade
        showEl.style.opacity = 0;
        // Força reflow para garantir transição
        void showEl.offsetWidth;
        showEl.style.opacity = 1;
        hideEl.style.opacity = 0;

        // Após fade, troca referência
        setTimeout(function() {
          idx = nextIdx;
          showingFirst = !showingFirst;
        }, fadeDuration);
      }

      setInterval(fadeTransition, 6000);

      </script>
    </body>
    </html>
    ';
    exit;
  }
}

