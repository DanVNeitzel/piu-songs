// =====================================================================
    //                    SISTEMA DE PESQUISA DE ARQUIVOS
    // =====================================================================

    // Variáveis globais para pesquisa
    var searchTimeout = null;
    // var isSearchActive = false; // Agora é global: window.isSearchActive
    var originalListFunction = null;
    var isSearchVisible = false; // Controla se o bloco de pesquisa está visível

    // Função para mostrar/ocultar o bloco de pesquisa
    function toggleSearchContainer() {
      var $container = $('#searchContainer');
      var $toggleBtn = $('#toggleSearchBtn');

      if (isSearchVisible) {
        // Ocultar pesquisa
        $container.removeClass('search-visible').addClass('search-hidden');
        $toggleBtn.removeClass('active').attr('title', 'Mostrar Pesquisa');
        isSearchVisible = false;

        // Se houver pesquisa ativa, limpar
        if (window.isSearchActive) {
          clearSearch();
        }
      } else {
        // Mostrar pesquisa
        $container.removeClass('search-hidden').addClass('search-visible');
        $toggleBtn.addClass('active').attr('title', 'Ocultar Pesquisa');
        isSearchVisible = true;

        // Focar no campo de pesquisa após aparecer
        setTimeout(function () {
          $('#searchInput').focus();
        }, 300);
      }
    }

    // Função para realizar pesquisa
    // Tornar função de pesquisa global
    window.performSearch = function performSearch() {
      var query = $('#searchInput').val().trim();
      var extensions = $('#searchExtension').val();
      var includeSubfolders = $('#includeSubfolders').is(':checked');
      var currentPath = decodeURIComponent(window.location.hash.substr(1)) || '.';

      // Se a pesquisa estiver vazia, voltar à listagem normal
      if (query === '' && extensions === '') {
        clearSearch();
        return;
      }

      // Atualizar interface
      $('#searchResults').html('<i class="fa fa-spinner fa-spin"></i> Pesquisando...');
      window.isSearchActive = true;

      // Fazer requisição de pesquisa
      $.get('?', {
        do: 'search',
        query: query,
        extensions: extensions,
        include_subfolders: includeSubfolders ? '1' : '0',
        path: currentPath
      }, function (data) {
        if (data.success) {
          displaySearchResults(data.results, data.stats);
        } else {
          $('#searchResults').text('Erro na pesquisa');
        }
      }, 'json').fail(function () {
        $('#searchResults').text('Erro na pesquisa');
      });
    }; // fim da função window.performSearch

    // Função para exibir resultados da pesquisa
    function displaySearchResults(results, stats) {
      var $tbody = $('#list');
      var $fileGrid = $('#fileGrid');

      // Verificar modo de visualização atual (fallback seguro)
      var currentViewMode = window.currentViewMode || 'list';
      try {
        currentViewMode = localStorage.getItem('fileViewMode') || 'list';
        window.currentViewMode = currentViewMode;
      } catch (e) {
        console.log('Erro ao acessar localStorage, usando modo lista');
      }

      // Limpar resultados anteriores
      $tbody.empty();
      $fileGrid.empty();

      if (results.length === 0) {
        var message = 'Nenhum resultado encontrado';
        if (currentViewMode === 'grid') {
          $fileGrid.append('<div class="empty w-100 text-center text-white">' + message + '</div>');
        } else {
          $tbody.append('<tr><td class="empty" colspan="5">' + message + '</td></tr>');
        }
        $('#searchResults').text(message);
        return;
      }

      // Armazenar os resultados da pesquisa
      window.currentData = results || [];

      // Aplicar ordenação aos resultados de pesquisa
      var sortedResults = window.applySorting(results);

      // Renderizar resultados
      if (currentViewMode === 'grid') {
        $.each(sortedResults, function (k, v) {
          var card = window.renderFileCard(v);
          if (v.highlight_query) {
            card.addClass('search-highlight');
          }
          $fileGrid.append(card);
        });
      } else {
        $.each(sortedResults, function (k, v) {
          var row = window.renderFileRow(v);
          if (v.highlight_query) {
            row.addClass('search-highlight');
          }
          $tbody.append(row);
        });
      }

      // Atualizar estatísticas
      var resultText = stats.total_found + ' resultado' + (stats.total_found !== 1 ? 's' : '') + ' encontrado' + (stats.total_found !== 1 ? 's' : '');
      if (stats.include_subfolders) {
        resultText += ' (incluindo subpastas)';
      }
      $('#searchResults').text(resultText);

      // Reconfigurar ordenação da tabela
      $('#table').retablesort();
    }

    // Função para limpar pesquisa
    function clearSearch() {
      window.isSearchActive = false;
      $('#searchInput').val('');
      $('#searchExtension').val('');
      $('#searchResults').text('Digite para pesquisar arquivos e pastas');

      // Voltar à listagem normal
      if (typeof window.list === 'function') {
        window.list();
      }
    }

    // Função para mostrar o bloco de pesquisa (se estiver oculto)
    function showSearchContainer() {
      if (!isSearchVisible) {
        toggleSearchContainer();
      }
    }

    // Inicialização da pesquisa
    $(function () {
      // Pesquisa em tempo real
      $('#searchInput').on('input', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function () {
          window.performSearch();
        }, 500); // Delay de 500ms para evitar muitas requisições
      });

      // Pesquisa ao alterar filtro de extensão
      $('#searchExtension').on('change', function () {
        if ($('#searchInput').val().trim() !== '' || $(this).val() !== '') {
          window.performSearch();
        }
      });

      // Pesquisa ao alterar inclusão de subpastas
      $('#includeSubfolders').on('change', function () {
        if ($('#searchInput').val().trim() !== '' || $('#searchExtension').val() !== '') {
          window.performSearch();
        }
      });

      // Botão limpar pesquisa
      $('#clearSearchBtn').on('click', function () {
        clearSearch();
      });

      // Event handler para o select de ordenação
      $('#sortSelect').on('change', function () {
        // Salvar preferência de ordenação
        try {
          localStorage.setItem('fileSortPreference', $(this).val());
        } catch (e) {
          console.log('Não foi possível salvar preferência de ordenação');
        }

        // Re-aplicar ordenação aos dados atuais sem nova requisição
        if (window.isSearchActive) {
          // Se estiver em modo de pesquisa, refazer a pesquisa
          window.performSearch();
        } else {
          // Se estiver navegando normalmente, apenas reordenar dados atuais
          if (typeof window.renderCurrentData === 'function') {
            window.renderCurrentData();
          } else {
            // Fallback: recarregar a lista
            if (typeof window.list === 'function') {
              window.list();
            }
          }
        }
      });

      // Pesquisa com Enter
      $('#searchInput').on('keypress', function (e) {
        if (e.which === 13) { // Enter
          window.performSearch();
        }
      });

      // Esc para limpar pesquisa
      $(document).on('keydown', function (e) {
        if (e.which === 27 && window.isSearchActive) { // Esc
          clearSearch();
        }

        // Ctrl+F para mostrar pesquisa e focar
        if (e.ctrlKey && e.which === 70) {
          e.preventDefault();
          showSearchContainer();
          $('#searchInput').focus().select();
        }

        // Ctrl+B para toggle do sidebar
        if (e.ctrlKey && e.which === 66) {
          e.preventDefault();
          toggleSidebar();
        }
      });

      // Botão de toggle da pesquisa no header
      $('#toggleSearchBtn').on('click', function () {
        toggleSearchContainer();
      });

      // Botão de pesquisa no sidebar
      $('#focusSearchBtn').on('click', function () {
        showSearchContainer();
        $('#searchInput').focus().select();
      });

      // Interceptar mudanças de hash para limpar pesquisa se necessário
      $(window).on('hashchange', function () {
        if (window.isSearchActive) {
          // Se o usuário navegar para outra pasta, limpar a pesquisa
          setTimeout(function () {
            clearSearch();
          }, 100);
        }
      });
    });

  