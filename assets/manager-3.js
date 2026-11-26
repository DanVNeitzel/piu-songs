// Handlers para restaurar todos e esvaziar lixeira
    $(function () {
      $('#restoreAllTrashBtn').on('click', function () {
        if (!confirm('Restaurar TODOS os itens da lixeira?')) return;
        $.post('?', { do: 'restorealltrash', xsrf: (document.cookie.match('(^|; )_sfm_xsrf=([^;]*)') || 0)[2] }, function (res) {
          if (res && res.success) {
            showTrashAlert('Todos os itens foram restaurados.', 'success');
            loadTrashList();
          } else {
            showTrashAlert('Erro ao restaurar todos: ' + (res && res.error ? res.error.msg : 'unknown'), 'danger');
          }
        }, 'json').fail(function () {
          showTrashAlert('Falha na requisição.', 'danger');
        });
      });
      $('#emptyTrashBtn').on('click', function () {
        if (!confirm('Excluir PERMANENTEMENTE todos os itens da lixeira? Esta ação não pode ser desfeita.')) return;
        $.post('?', { do: 'emptytrash', xsrf: (document.cookie.match('(^|; )_sfm_xsrf=([^;]*)') || 0)[2] }, function (res) {
          if (res && res.success) {
            showTrashAlert('Lixeira esvaziada.', 'success');
            loadTrashList();
          } else {
            showTrashAlert('Erro ao limpar lixeira: ' + (res && res.error ? res.error.msg : 'unknown'), 'danger');
          }
        }, 'json').fail(function () {
          showTrashAlert('Falha na requisição.', 'danger');
        });
      });
    });
    (function ($) {
      $.fn.tablesorter = function () {
        var $table = this;
        this.find('th').click(function () {
          var idx = $(this).index();
          var direction = $(this).hasClass('sort_asc');
          // apenas dispara a ordenação da tabela aqui — o timer de sessão foi unificado
          $table.tablesortby(idx, direction);
        });
        return this;
      };
      $.fn.tablesortby = function (idx, direction) {
        var $rows = this.find('tbody tr');
        function elementToVal(a) {
          var $a_elem = $(a).find('td:nth-child(' + (idx + 1) + ')');
          var a_val = $a_elem.attr('data-sort') || $a_elem.text();
          return (a_val == parseInt(a_val) ? parseInt(a_val) : a_val);
        }
        $rows.sort(function (a, b) {
          var a_val = elementToVal(a), b_val = elementToVal(b);
          return (a_val > b_val ? 1 : (a_val == b_val ? 0 : -1)) * (direction ? 1 : -1);
        })
        this.find('th').removeClass('sort_asc sort_desc');
        $(this).find('thead th:nth-child(' + (idx + 1) + ')').addClass(direction ? 'sort_desc' : 'sort_asc');
        for (var i = 0; i < $rows.length; i++)
          this.append($rows[i]);
        this.settablesortmarkers();
        return this;
      }
      $.fn.retablesort = function () {
        var $e = this.find('thead th.sort_asc, thead th.sort_desc');
        if ($e.length)
          this.tablesortby($e.index(), $e.hasClass('sort_desc'));

        return this;
      }
      $.fn.settablesortmarkers = function () {
        this.find('thead th span.indicator').remove();
        this.find('thead th.sort_asc').append('<span class="indicator">&darr;<span>');
        this.find('thead th.sort_desc').append('<span class="indicator">&uarr;<span>');
        return this;
      }
    })(jQuery);

    // ADDED: variáveis globais para XSRF token
    var XSRF = (document.cookie.match('(^|; )_sfm_xsrf=([^;]*)') || 0)[2];
    var token = XSRF; // Para compatibilidade

    // Variáveis globais para o sistema de pesquisa e visualização
    window.isSearchActive = false;
    window.currentViewMode = localStorage.getItem('fileViewMode') || 'list';
    window.currentData = []; // Armazenar os dados atuais para reordenação

    // Carregar preferência de ordenação salva
    try {
      var savedSortPreference = localStorage.getItem('fileSortPreference') || 'name';
      $('#sortSelect').val(savedSortPreference);
    } catch (e) {
      console.log('Não foi possível carregar preferência de ordenação');
    }

    // ADDED: configurações de nuvem
    var cloudConfig = window.piuClient.cloud || {};
    var selectedCloudFiles = [];
    var currentCloudProvider = '';

    // Debug: verificar se cloudConfig foi carregado
    // console.log('Cloud Config loaded:', cloudConfig);

    $(function () {
      var MAX_UPLOAD_SIZE = window.piuClient.maxUpload;

      // Forçar habilitação dos botões de selecionados (exceto delete que é condicional)
      $('#moveCopyBtn').removeClass('menu-item-disabled');
      $('#moveCopyBtn').prop('disabled', false);

      // Remover classes de desabilitação visual
      $('#deleteSelectedBtn, #moveCopyBtn').removeClass('menu-item-disabled');

      //console.log('Botões de selecionados configurados (delete condicional, outros sempre habilitados)');
      //console.log('✅ Comportamento padrão: Clique simples para selecionar • Double-click para abrir/navegar');

      // Debug: Mostrar informações de limite de upload
      // console.log('Limites de Upload:');





      // ADDED: Sistema de Drag and Drop para mover arquivos
      var dragAndDropMover = {
        draggedFiles: [],
        isDragging: false,
        dragStartTime: 0,

        init: function () {
          this.bindEvents();
          this.addDropZones();
        },

        bindEvents: function () {
          var self = this;

          // Eventos de drag para itens de arquivo/pasta
          $(document).on('dragstart', '.draggable-item', function (e) {
            e.originalEvent.dataTransfer.effectAllowed = 'move';

            var filePath = $(this).find('.select-item').attr('data-file') || $(this).attr('data-file');
            if (!filePath) return false;

            // Se o item sendo arrastado não está selecionado, limpar seleção e selecionar apenas ele
            if (!$(this).find('.select-item').prop('checked')) {
              $('.select-item').prop('checked', false);
              $(this).find('.select-item').prop('checked', true);
              if (typeof updateDeleteSelectedBtn === 'function') {
                updateDeleteSelectedBtn();
              }
            }

            // Coletar todos os arquivos selecionados
            self.draggedFiles = $('.select-item:checked').map(function () {
              return $(this).attr('data-file');
            }).get();

            self.isDragging = true;
            self.dragStartTime = Date.now();

            // Definir dados de transferência
            e.originalEvent.dataTransfer.setData('text/plain', JSON.stringify(self.draggedFiles));

            // Adicionar classe visual
            $(this).addClass('dragging').attr('data-drag-count', self.draggedFiles.length);
            $('.is_dir, .file-card.is-dir').addClass('drop-zone');

            //console.log('Iniciando drag com arquivos:', self.draggedFiles);
          });

          $(document).on('dragend', '.draggable-item', function (e) {
            $(this).removeClass('dragging');
            $('.drop-zone').removeClass('drop-zone drag-over');

            setTimeout(function () {
              self.isDragging = false;
              self.draggedFiles = [];
            }, 100);
          });
        },

        addDropZones: function () {
          var self = this;

          // Adicionar zona de drop para pastas na tabela
          $(document).on('dragover', 'tr.is_dir', function (e) {
            if (!self.isDragging) return;
            e.preventDefault();
            e.originalEvent.dataTransfer.dropEffect = 'move';

            var targetPath = $(this).find('.select-item').attr('data-file');
            if (targetPath && self.draggedFiles.indexOf(targetPath) === -1) {
              // Verificar se é uma movimentação válida (não mover pasta para dentro dela mesma)
              var isValidDrop = !self.draggedFiles.some(function (file) {
                return targetPath.startsWith(file + '/') || targetPath === file;
              });

              if (isValidDrop) {
                $(this).addClass('drop-zone drag-over');
                e.originalEvent.dataTransfer.dropEffect = 'move';
              } else {
                e.originalEvent.dataTransfer.dropEffect = 'none';
              }
            } else {
              e.originalEvent.dataTransfer.dropEffect = 'none';
            }
          });

          $(document).on('dragleave', 'tr.is_dir', function (e) {
            $(this).removeClass('drag-over');
          });

          $(document).on('drop', 'tr.is_dir', function (e) {
            if (!self.isDragging) return;
            e.preventDefault();

            var targetPath = $(this).find('.select-item').attr('data-file');
            if (targetPath && self.draggedFiles.indexOf(targetPath) === -1) {
              // Verificar se é uma movimentação válida
              var isValidDrop = !self.draggedFiles.some(function (file) {
                return targetPath.startsWith(file + '/') || targetPath === file;
              });

              if (isValidDrop) {
                self.moveFiles(self.draggedFiles, targetPath);
              }
            }

            $(this).removeClass('drop-zone drag-over');
          });

          // Adicionar zona de drop para cards de pasta no grid
          $(document).on('dragover', '.file-card.is-dir', function (e) {
            if (!self.isDragging) return;
            e.preventDefault();
            e.originalEvent.dataTransfer.dropEffect = 'move';

            var targetPath = $(this).find('.select-item').attr('data-file');
            if (targetPath && self.draggedFiles.indexOf(targetPath) === -1) {
              // Verificar se é uma movimentação válida
              var isValidDrop = !self.draggedFiles.some(function (file) {
                return targetPath.startsWith(file + '/') || targetPath === file;
              });

              if (isValidDrop) {
                $(this).addClass('drop-zone drag-over');
                e.originalEvent.dataTransfer.dropEffect = 'move';
              } else {
                e.originalEvent.dataTransfer.dropEffect = 'none';
              }
            } else {
              e.originalEvent.dataTransfer.dropEffect = 'none';
            }
          });

          $(document).on('dragleave', '.file-card.is-dir', function (e) {
            $(this).removeClass('drag-over');
          });

          $(document).on('drop', '.file-card.is-dir', function (e) {
            if (!self.isDragging) return;
            e.preventDefault();

            var targetPath = $(this).find('.select-item').attr('data-file');
            if (targetPath && self.draggedFiles.indexOf(targetPath) === -1) {
              // Verificar se é uma movimentação válida
              var isValidDrop = !self.draggedFiles.some(function (file) {
                return targetPath.startsWith(file + '/') || targetPath === file;
              });

              if (isValidDrop) {
                self.moveFiles(self.draggedFiles, targetPath);
              }
            }

            $(this).removeClass('drop-zone drag-over');
          });

          // Zona de drop para voltar à pasta pai (breadcrumb)
          $(document).on('dragover', '#breadcrumb a', function (e) {
            if (!self.isDragging) return;
            e.preventDefault();
            e.originalEvent.dataTransfer.dropEffect = 'move';
            $(this).addClass('drop-zone drag-over');
          });

          $(document).on('dragleave', '#breadcrumb a', function (e) {
            $(this).removeClass('drag-over');
          });

          $(document).on('drop', '#breadcrumb a', function (e) {
            if (!self.isDragging) return;
            e.preventDefault();

            var href = $(this).attr('href');
            if (href && href.startsWith('#')) {
              var targetPath = decodeURIComponent(href.substring(1)) || '.';
              self.moveFiles(self.draggedFiles, targetPath);
            }

            $(this).removeClass('drop-zone drag-over');
          });
        },

        moveFiles: function (files, targetPath) {
          if (!files || !files.length || !targetPath) return;

          // Verificar se algum dos arquivos sendo movidos é uma pasta pai do destino
          var isInvalidMove = files.some(function (file) {
            return targetPath.startsWith(file + '/') || targetPath === file;
          });

          if (isInvalidMove) {
            alert('Não é possível mover uma pasta para dentro de si mesma ou de suas subpastas.');
            return;
          }

          var fileNames = files.map(function (f) { return f.split('/').pop(); }).join(', ');
          var targetName = targetPath === '.' ? 'pasta raiz' : targetPath.split('/').pop();

          if (!confirm('Mover ' + files.length + ' item(s) (' + fileNames + ') para "' + targetName + '"?')) {
            return;
          }

          // Mostrar indicador de carregamento
          var $loading = $('<div class="drag-loading">Movendo arquivos...</div>');
          $('body').append($loading);

          $.post('?', {
            do: 'movecopy',
            files: files,
            dest: targetPath,
            action: 'move',
            xsrf: (document.cookie.match('(^|; )_sfm_xsrf=([^;]*)') || 0)[2]
          }, function (response) {
            $loading.remove();

            if (response && response.success) {
              var successCount = 0;
              var errorCount = 0;
              var errorMessages = [];

              Object.keys(response.results || {}).forEach(function (file) {
                var result = response.results[file];
                if (result.success) {
                  successCount++;
                } else {
                  errorCount++;
                  errorMessages.push(file + ': ' + (result.msg || 'Erro desconhecido'));
                }
              });

              if (successCount > 0) {
                showSuccessNotification(successCount + ' arquivo(s) movido(s) com sucesso!');

                // Atualizar lista mantendo seleções ativas
                setTimeout(function () {
                  // Não limpar seleções - manter checkboxes marcados
                  if (typeof updateDeleteSelectedBtn === 'function') {
                    updateDeleteSelectedBtn();
                  }
                  if (typeof window.list === 'function') {
                    window.list();
                  }
                }, 500);
              }

              if (errorCount > 0) {
                var errorMsg = 'Alguns arquivos não puderam ser movidos:\n' + errorMessages.join('\n');
                alert(errorMsg);
              }
            } else {
              alert('Erro ao mover arquivos: ' + (response && response.error ? response.error.msg : 'Erro desconhecido'));
            }
          }, 'json').fail(function () {
            $loading.remove();
            alert('Erro na comunicação com o servidor');
          });
        }
      };

      // Inicializar sistema de drag and drop
      dragAndDropMover.init();

      // ADDED: servidor -> JS config snapshot (usado para popular o modal)
      var serverConfig = window.piuClient.server;

      // Verificar se há limitação do PHP afetando o upload
      if (serverConfig.effective_upload_limit_mb < serverConfig.max_upload_size_mb) {
        console.warn('Aviso: Limite de upload configurado (' + serverConfig.max_upload_size_mb +
          'MB) é maior que as limitações do PHP (' + serverConfig.effective_upload_limit_mb + 'MB)');

        // Mostrar aviso visual se for administrador
        if (serverConfig.permissionAdmin) {
          setTimeout(function () {
            alert('Aviso: O limite de upload está sendo limitado pelas configurações do PHP.\n\n' +
              'Configurado: ' + serverConfig.max_upload_size_mb + 'MB\n' +
              'Efetivo: ' + serverConfig.effective_upload_limit_mb + 'MB\n\n' +
              'Para uploads maiores, ajuste post_max_size e upload_max_filesize no php.ini');
          }, 2000);
        }
      }

      var $tbody = $('#list');
      var $table = $('#table');
      var $fileGrid = $('<div id="fileGrid" class="file-grid-view" style="display:none;"></div>');
      // Adicionar o grid após a tabela dentro do main-content
      $table.after($fileGrid);

      var viewMode = localStorage.getItem('fileViewMode') || 'list';
      function setViewMode(mode) {
        viewMode = mode;
        window.currentViewMode = mode; // Atualizar variável global também
        localStorage.setItem('fileViewMode', mode);
        if (mode === 'grid') {
          $table.hide();
          $fileGrid.show();
          $('#toggleViewText').text('Lista');
          $('#toggleViewBtn i').removeClass('fa-th-large').addClass('fa-list');
        } else {
          $table.show();
          $fileGrid.hide();
          $('#toggleViewText').text('Blocos');
          $('#toggleViewBtn i').removeClass('fa-list').addClass('fa-th-large');
        }
      }
      setViewMode(viewMode);

      $('#toggleViewBtn').on('click', function () {
        setViewMode(viewMode === 'list' ? 'grid' : 'list');
        window.list();
      });
      $(window).on('hashchange', function () {
        if (typeof window.list === 'function') {
          window.list();
        }
      }).trigger('hashchange');
      $('#table').tablesorter();

      // Função unificada para o Timer de sessão
      function initializeSessionTimer(sessionTimeout) {
        // Se sessionTimeout for 0, não inicializar o timer (sessão sem expiração)
        if (sessionTimeout <= 0) {
          $('#session-timer').text('Sessão sem expiração');
          return;
        }

        var lastActivity = Date.now();

        function updateSessionTimer() {
          var now = Date.now();
          var elapsed = now - lastActivity;
          var remaining = sessionTimeout - elapsed;
          if (remaining < 0) remaining = 0;
          var totalSeconds = Math.floor(remaining / 1000);
          var min = Math.floor(totalSeconds / 60);
          var sec = totalSeconds % 60;
          var text = 'Tempo restante da sessão: ' + min + 'm ' + (sec < 10 ? '0' : '') + sec + 's';
          $('#session-timer').text(text);
          if (remaining === 0) {
            $('#session-timer').text('Sessão expirada!');
            // Chama logout no backend
            $.get('?do=logout', function () {
              window.location.reload();
            });
          }
        }

        setInterval(updateSessionTimer, 1000);
        $(document).on('mousemove keydown click', function () {
          lastActivity = Date.now();
        });
      }

      // Inicializar o Timer de sessão com o valor vindo do PHP
      var sessionTimeout = window.piuClient.timeout; // valor em milissegundos, 0 = sem expiração
      initializeSessionTimer(sessionTimeout);

      // ========== CONTROLE DO MENU LATERAL ==========

      // Inicializar sidebar baseado no tamanho da tela
      function initializeSidebar() {
        // Verificar se há estado salvo no localStorage
        var sidebarState = localStorage.getItem('sidebarState');
        var shouldShowSidebar = true;
        var $toggleBtn = $('#toggleSidebarBtn');

        if (sidebarState !== null) {
          shouldShowSidebar = sidebarState === 'open';
        } else {
          // Se não há estado salvo, usar comportamento padrão
          shouldShowSidebar = window.innerWidth >= 1200;
        }

        if (shouldShowSidebar) {
          openSidebar();
          $toggleBtn.html('<i class="fa fa-times"></i>')
            .attr('title', 'Fechar Menu (Ctrl+B)')
            .removeClass('sidebar-closed');
        } else {
          closeSidebar();
          $toggleBtn.html('<i class="fa fa-bars"></i>')
            .attr('title', 'Abrir Menu (Ctrl+B)')
            .addClass('sidebar-closed');
        }
      }

      // Toggle do sidebar
      $('#toggleSidebarBtn').on('click', function () {
        toggleSidebar();
      });

      $('#closeSidebarBtn').on('click', function () {
        closeSidebar();
      });

      // Fechar sidebar ao clicar no overlay
      $('#sidebarOverlay').on('click', function () {
        closeSidebar();
      });

      function toggleSidebar() {
        var $sidebar = $('#sidebar');
        var $overlay = $('#sidebarOverlay');
        var $toggleBtn = $('#toggleSidebarBtn');

        if ($sidebar.hasClass('active')) {
          closeSidebar();
          $toggleBtn.html('<i class="fa fa-bars"></i>')
            .attr('title', 'Abrir Menu (Ctrl+B)')
            .addClass('sidebar-closed');
        } else {
          openSidebar();
          $toggleBtn.html('<i class="fa fa-times"></i>')
            .attr('title', 'Fechar Menu (Ctrl+B)')
            .removeClass('sidebar-closed');
        }
      }

      function openSidebar() {
        var $sidebar = $('#sidebar');
        var $overlay = $('#sidebarOverlay');
        var $mainContent = $('#mainContent');

        $sidebar.addClass('active');
        $mainContent.addClass('sidebar-open');

        // Mostrar overlay apenas em telas pequenas
        if (window.innerWidth < 1200) {
          $overlay.addClass('active');
        }

        // Salvar estado no localStorage
        localStorage.setItem('sidebarState', 'open');
      }

      function closeSidebar() {
        var $sidebar = $('#sidebar');
        var $overlay = $('#sidebarOverlay');
        var $mainContent = $('#mainContent');

        $sidebar.removeClass('active');
        $overlay.removeClass('active');
        $mainContent.removeClass('sidebar-open');

        // Salvar estado no localStorage
        localStorage.setItem('sidebarState', 'closed');
      }

      // Reajustar sidebar ao redimensionar janela
      $(window).on('resize', function () {
        var $sidebar = $('#sidebar');
        var $overlay = $('#sidebarOverlay');

        if (window.innerWidth >= 1200) {
          // Desktop: remover overlay se existir
          $overlay.removeClass('active');
        } else {
          // Mobile: se sidebar estiver ativa, mostrar overlay
          if ($sidebar.hasClass('active')) {
            $overlay.addClass('active');
          }
        }
      });

      // Inicializar sidebar
      initializeSidebar();

      // Fechar sidebar automaticamente em mobile após clicar em um item do menu
      $('.menu-item').on('click', function () {
        if (window.innerWidth < 1200) {
          setTimeout(function () {
            closeSidebar();
          }, 300);
        }
      });

      // Atualizar timer de sessão no sidebar
      function updateSidebarSessionTimer() {
        var timerText = $('#session-timer').text();
        $('.session-text').text(timerText);
      }

      // Atualizar a cada segundo
      setInterval(updateSidebarSessionTimer, 1000);

      // Atualizar badge da lixeira no sidebar
      function updateSidebarTrashBadge() {
        $.get('?do=listtrash', function (res) {
          if (res && res.success && res.results) {
            var count = res.results.length;
            var $badge = $('#trashCountBadge');
            if (count > 0) {
              $badge.text(count).show();
            } else {
              $badge.hide();
            }
          }
        }, 'json');
      }

      // Atualizar badge da lixeira na inicialização
      updateSidebarTrashBadge();

      // Atualizar indicador do player no sidebar
      function updateSidebarPlayerStatus() {
        if (typeof FloatingAudioPlayer !== 'undefined' && FloatingAudioPlayer.isPlaying) {
          $('#toggleAudioPlayerBtn').addClass('playing');
          $('#audioPlayerStatusText').text('Tocando música');
        } else {
          $('#toggleAudioPlayerBtn').removeClass('playing');
          $('#audioPlayerStatusText').text('Reprodutor musical');
        }
      }

      // Atualizar status do player periodicamente
      setInterval(updateSidebarPlayerStatus, 1000);

      // Handler de logout 
      $('#logoutBtn').on('click', function () {
        if (confirm('Tem certeza que deseja sair?')) {
          window.location.href = '?do=logout';
        }
      });

      // Handler de exclusão movido para delegateFileActions para evitar duplicação

      $('#mkdir').submit(function (e) {
        var hashval = decodeURIComponent(window.location.hash.substr(1)),
          $dir = $(this).find('[name=dirname]');
        e.preventDefault();
        $dir.val().length && $.post('?', { 'do': 'mkdir', name: $dir.val(), xsrf: XSRF, file: hashval }, function (data) {
          window.list();
        }, 'json');
        $dir.val('');
        return false;
      });
      if (window.piuClient.server.allow_upload) {
        // file upload stuff
        $('#file_drop_target').on('dragover', function () {
          $(this).addClass('drag_over');
          return false;
        }).on('dragend', function () {
          $(this).removeClass('drag_over');
          return false;
        }).on('drop', function (e) {
          e.preventDefault();
          var files = e.originalEvent.dataTransfer.files;
          $.each(files, function (k, file) {
            uploadFile(file, false);
          });
          $(this).removeClass('drag_over');
        });
        $('input[type=file]').change(function (e) {
          e.preventDefault();
          $.each(this.files, function (k, file) {
            uploadFile(file, false);
          });
        });

        function updateDeleteSelectedBtn() {
          var anyChecked = $('.select-item:checked').length > 0;
          //console.log('updateDeleteSelectedBtn called: anyChecked =', anyChecked, 'selected files:', $('.select-item:checked').length);

          // Apenas o botão de excluir é condicional
          $('#deleteSelectedBtn').prop('disabled', !anyChecked);

          // Outros botões sempre habilitados (não tocar no disabled)
          // $('#zipSelectedBtn').prop('disabled', false); // sempre habilitado
          // $('#moveCopyBtn').prop('disabled', false); // sempre habilitado

          // Botão de conversão mantém lógica condicional (faz sentido)
          var imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
          var hasImages = $('.select-item:checked').filter(function () {
            var file = $(this).attr('data-file');
            var ext = file.split('.').pop().toLowerCase();
            return imageExts.includes(ext);
          }).length > 0;
          $('#convertImagesBtn').prop('disabled', !hasImages);

          // Verificar se há arquivos de áudio selecionados
          var audioExts = ['mp3', 'wav', 'ogg', 'aac', 'm4a', 'flac', 'wma', 'mp4', 'avi', 'mkv', 'mov'];
          var hasAudio = $('.select-item:checked').filter(function () {
            var file = $(this).attr('data-file');
            var ext = file.split('.').pop().toLowerCase();
            return audioExts.includes(ext);
          }).length > 0;
          $('#convertAudioBtn').prop('disabled', !hasAudio);

          // Verificar se há arquivos selecionados para compressão
          var hasFiles = $('.select-item:checked').length > 0;
          $('#compressFilesBtn').prop('disabled', !hasFiles);
        }

        // Atualiza o botão ao clicar em qualquer checkbox (tabela ou grid)
        $('#table').on('change', '.select-item', function () {
          if (typeof updateDeleteSelectedBtn === 'function') {
            updateDeleteSelectedBtn();
          }
        });
        $('#fileGrid').on('change', '.select-item', function () {
          if (typeof updateDeleteSelectedBtn === 'function') {
            updateDeleteSelectedBtn();
          }
        });

        // Ao clicar em "Excluir selecionados"
        $('#deleteSelectedBtn').click(function () {
          if (!confirm('Tem certeza que deseja excluir os arquivos/pastas selecionados?')) return;
          // Confirmação do modal de exclusão
          $('#confirmDeleteBtn').click(function () {
            var file = $('#confirmDeleteModal').data('file');
            if (file) {
              $.post("", { 'do': 'delete', file: file, xsrf: XSRF }, function (response) {
                window.list();
              }, 'json');
            }
            var confirmDeleteModal = bootstrap.Modal.getInstance(document.getElementById('confirmDeleteModal'));
            if (confirmDeleteModal) confirmDeleteModal.hide();
          });

          var filesToDelete = $('.select-item:checked').map(function () {
            return $(this).attr('data-file');
          }).get();

          // Envia exclusão para cada arquivo/pasta
          filesToDelete.forEach(function (file) {
            $.post("", { 'do': 'delete', file: file, xsrf: XSRF }, function () {
              // Opcional: pode esperar todos retornarem antes de atualizar a lista
            }, 'json');
          });

          // Após exclusão, atualiza a lista mantendo estado dos botões
          setTimeout(function () {
            window.list();
            // Atualizar estado dos botões baseado na seleção atual
            if (typeof updateDeleteSelectedBtn === 'function') {
              updateDeleteSelectedBtn();
            }
          }, 500);
        });

        function uploadFile(file) {
          var folder = decodeURIComponent(window.location.hash.substr(1));

          if (file.size > MAX_UPLOAD_SIZE) {
            var $error_row = renderFileSizeErrorRow(file, folder);
            $('#upload_progress').append($error_row);
            window.setTimeout(function () { $error_row.fadeOut(); }, 5000);
            return false;
          }

          var $row = renderFileUploadRow(file, folder);
          var $progressText = $('<span class="progress-text"></span>');
          $row.append($progressText);
          $('#upload_progress').append($row);

          var fd = new FormData();
          fd.append('file_data', file);
          fd.append('file', folder);
          fd.append('xsrf', XSRF);
          fd.append('do', 'upload');
          if (arguments.length > 1 && arguments[1] === true) fd.append('overwrite', '1');

          var xhr = new XMLHttpRequest();
          xhr.open('POST', '?');
          xhr.onload = function () {
            if (xhr.status === 200) {
              $row.remove();
              window.list();
            } else {
              try {
                var res = JSON.parse(xhr.responseText);
                if (res && res.error && res.error.code === 409) {
                  if (confirm('Já existe um arquivo com este nome. Deseja substituir?')) {
                    uploadFile(file, true);
                    $row.remove();
                    return;
                  } else {
                    $row.remove();
                    return;
                  }
                } else {
                  alert('Erro ao enviar arquivo: ' + (res && res.error ? res.error.msg : 'Erro desconhecido.'));
                }
              } catch (e) {
                alert('Erro ao enviar arquivo.');
              }
              $row.remove();
            }
          };

          xhr.upload.onprogress = function (e) {
            if (e.lengthComputable) {
              var percent = (e.loaded / e.total * 100 | 0) + '%';
              $row.find('.progress').css('width', percent);
              $progressText.text('(' + percent + ' completo)'); // Atualiza o texto do progresso
            }
          };
          xhr.send(fd);
        }
        function renderFileUploadRow(file, folder) {
          return $row = $('<div/>')
            .append($('<span class="progress_fileuploadname" />').text((folder ? folder + '/' : '') + file.name))
            .append($('<div class="progress_track"><div class="progress"></div></div>'))
            .append($('<span class="progress_size" />').text(formatFileSize(file.size)))
        };
        function renderFileSizeErrorRow(file, folder) {
          var errorMsg = ' file size - <b>' + formatFileSize(file.size) + '</b>' +
            ' excede o tamanho máximo de upload de <b>' + formatFileSize(MAX_UPLOAD_SIZE) + '</b>';

          // Adicionar informações sobre limitações do PHP se necessário
          if (serverConfig.effective_upload_limit_mb < serverConfig.max_upload_size_mb) {
            errorMsg += '<br><small>Limitado pelo PHP (post_max_size: ' + serverConfig.php_post_max_mb +
              'MB, upload_max_filesize: ' + serverConfig.php_upload_max_mb + 'MB)</small>';
          }

          return $row = $('<div class="error" />')
            .append($('<span class="fileuploadname" />').text('Error: ' + (folder ? folder + '/' : '') + file.name))
            .append($('<span/>').css('color', '#ff0000').html(errorMsg));
        }
      }

      // Tornar a função list global para acesso de outros scripts
      window.list = function list() {
        var hashval = window.location.hash.substr(1);

        // Preservar seleções antes de limpar a lista
        var selectedFiles = $('.select-item:checked').map(function () {
          return $(this).attr('data-file');
        }).get();

        $.get('?do=list&file=' + hashval, function (data) {
          $tbody.empty();
          $fileGrid.empty();
          $('#breadcrumb').empty().html(renderBreadcrumbs(hashval));

          //console.log('Current view mode:', viewMode);
          //console.log('Data received:', data);

          if (data.success) {
            // Armazenar os dados atuais
            window.currentData = data.results || [];
            // Aplicar ordenação antes de renderizar
            var sortedResults = window.applySorting(data.results);

            if (viewMode === 'grid') {
              //console.log('Rendering grid view with', sortedResults.length, 'items');
              $.each(sortedResults, function (k, v) {
                var card = renderFileCard(v);
                $fileGrid.append(card);
                //console.log('Added card for:', v.name);
              });
              if (!sortedResults.length) $fileGrid.append('<div class="empty w-100 text-center text-white">Esta pasta está vazia</div>');
            } else {
              //console.log('Rendering list view with', sortedResults.length, 'items');
              $.each(sortedResults, function (k, v) {
                $tbody.append(renderFileRow(v));
              });
              !sortedResults.length && $tbody.append('<tr><td class="empty" colspan=5>Esta pasta está vazia</td></tr>');
            }
            data.is_writable ? $('body').removeClass('no_write') : $('body').addClass('no_write');
          } else {
            //console.warn(data.error.msg);
          }
          $('#table').retablesort();

          // Restaurar seleções preservadas
          setTimeout(function () {
            //console.log('Restaurando seleções:', selectedFiles);
            selectedFiles.forEach(function (filePath) {
              $('.select-item[data-file="' + filePath + '"]').prop('checked', true);
            });

            // Atualizar estado do selectAll
            var allChecked = $('.select-item').length === $('.select-item:checked').length;
            $('#selectAll').prop('checked', allChecked);

            //console.log('Seleções restauradas. Total selecionados:', $('.select-item:checked').length);

            // Atualizar estado dos botões
            if (typeof updateDeleteSelectedBtn === 'function') {
              updateDeleteSelectedBtn();
            }
          }, 50);

          // Atualizar botões do player após carregar a lista
          setTimeout(function () {
            if (typeof updateNavbarPlayerButton === 'function') {
              updateNavbarPlayerButton();
            }
          }, 100);
        }, 'json');
      }; // fim da função window.list

      // Função para destacar termos pesquisados
      function highlightSearchTerm(text, query) {
        if (!query || query.trim() === '') return text;

        var regex = new RegExp('(' + escapeRegExp(query) + ')', 'gi');
        return text.replace(regex, '<mark style="background-color: #ffc107; color: #000; padding: 1px 2px; border-radius: 2px;">$1</mark>');
      }

      // Função para escapar caracteres especiais do regex
      function escapeRegExp(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
      }

      // Tornar funções de destaque disponíveis globalmente
      window.highlightSearchTerm = highlightSearchTerm;
      window.escapeRegExp = escapeRegExp;

      // Função para aplicar ordenação aos resultados (tornar global)
      window.applySorting = function applySorting(results) {
        var sortBy = $('#sortSelect').val() || 'name';

        if (!results || !Array.isArray(results)) {
          return [];
        }

        return results.slice().sort(function (a, b) {
          // Sempre manter pastas no topo
          if (a.is_dir && !b.is_dir) return -1;
          if (!a.is_dir && b.is_dir) return 1;

          // Se ambos são pastas ou ambos são arquivos, aplicar ordenação escolhida
          switch (sortBy) {
            case 'name':
              return a.name.toLowerCase().localeCompare(b.name.toLowerCase());

            case 'date':
              return b.mtime - a.mtime; // Mais recente primeiro

            case 'type':
              var extA = a.is_dir ? 'folder' : (a.name.split('.').pop() || '').toLowerCase();
              var extB = b.is_dir ? 'folder' : (b.name.split('.').pop() || '').toLowerCase();
              var typeComparison = extA.localeCompare(extB);
              return typeComparison !== 0 ? typeComparison : a.name.toLowerCase().localeCompare(b.name.toLowerCase());

            case 'size':
              if (a.is_dir && b.is_dir) {
                return a.name.toLowerCase().localeCompare(b.name.toLowerCase());
              }
              return b.size - a.size; // Maior primeiro

            default:
              return a.name.toLowerCase().localeCompare(b.name.toLowerCase());
          }
        });
      }; // fim da função window.applySorting

      // Função para renderizar dados já carregados (sem nova requisição)
      window.renderCurrentData = function () {
        if (!window.currentData || !Array.isArray(window.currentData) || window.currentData.length === 0) {
          return;
        }

        // Aplicar ordenação aos dados atuais
        var sortedResults = window.applySorting(window.currentData);

        // Limpar containers
        $tbody.empty();
        $fileGrid.empty();

        // Renderizar conforme o modo de visualização atual
        if (window.currentViewMode === 'grid') {
          $.each(sortedResults, function (k, v) {
            var card = renderFileCard(v);
            $fileGrid.append(card);
          });
          if (!sortedResults.length) $fileGrid.append('<div class="empty w-100 text-center text-white">Esta pasta está vazia</div>');
        } else {
          $.each(sortedResults, function (k, v) {
            $tbody.append(renderFileRow(v));
          });
          if (!sortedResults.length) $tbody.append('<tr><td class="empty" colspan=5>Esta pasta está vazia</td></tr>');
        }

        $('#table').retablesort();
      };

      // Renderização de arquivos/pastas em cards (grid)
      function renderFileCard(data) {
        var $card = $('<div class="file-card draggable-item"></div>');
        $card.attr('draggable', 'true');
        if (data.is_dir) {
          $card.addClass('is-dir');
        }
        var cbId = 'cb_grid_' + data.path.replace(/[^a-zA-Z0-9]/g, '');
        var $checkbox = $('<div class="form-check d-inline-block me-2"><input type="checkbox" class="form-check-input select-item" data-file="' + data.path + '" id="' + cbId + '"><label class="form-check-label" for="' + cbId + '"></label></div>');
        var nameClass = data.is_dir ? 'name name-dir' : 'name name-arc';
        var $link = $('<a />')
          .addClass(nameClass)
          .attr('href', data.is_dir ? '#' + encodeURIComponent(data.path) : './' + data.path)
          .attr('data-path', data.path)
          .on('click', function (e) {
            e.preventDefault();
            $(this).closest('.file-card').find('.select-item').trigger('click');
          })
          .on('dblclick', function (e) {
            e.preventDefault();
            if (data.is_dir) {
              window.location.hash = encodeURIComponent(data.path);
            } else {
              window.location.href = './' + data.path;
            }
          });

        // Destacar termo pesquisado se aplicável
        var displayName = data.name;
        if (data.highlight_query && data.highlight_query.trim() !== '') {
          displayName = window.highlightSearchTerm(data.name, data.highlight_query);
        }

        $link.html('<span>' + displayName + '</span>');

        // Mostrar caminho da pasta se for resultado de pesquisa
        if (data.dir_path && data.dir_path !== '.' && window.isSearchActive) {
          $link.append('<br><small class="text-default opacity-75" style="font-size: 0.7em;">📁 ' + data.dir_path + '</small>');
        }
        var $title = $('<div class="file-card-title"></div>')
          .append($checkbox)
          .append($link);
        $card.append($title);

        var $meta = $('<div class="file-card-meta d-flex flex-wrap gap-2 align-items-center w-100"></div>')
          .append($('<span class="badge bg-success px-2 py-1 rounded-pill me-1 w-100"></span>').text(data.is_dir ? 'Pasta' : 'Arquivo'))
          .append($('<span class="badge bg-danger text-white px-2 py-1 rounded-pill me-1 w-100"></span>')
            .attr('data-folder', data.path)
            .html(data.is_dir ? '--' : formatFileSize(data.size))
          )
          .append($('<span class="badge bg-info text-dark px-2 py-1 rounded-pill w-100"></span>').text(formatTimestamp(data.mtime)));
        $card.append($meta);

        // Footer de ações
        var $footer = $('<div class="file-card-footer mt-auto w-100"></div>');
        var $actions = $('<div class="file-card-actions"></div>');

        // Visualizar/Editar/Outros (imagem, áudio, vídeo, pdf, texto)
        var ext = data.name.split('.').pop().toLowerCase();
        var imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'tiff', 'tif', 'svg', 'avif'];
        var audioExts = ['mp3', 'wav', 'ogg', 'm4a'];
        var videoExts = ['mp4', 'webm', 'ogg', 'mkv'];
        var pdfExts = ['pdf'];
        var textExts = ['ssc', 'txt', 'md', 'csv', 'html', 'htm', 'js', 'css', 'json', 'log', 'ini', 'xml', 'yaml', 'yml'];

        if (!data.is_dir && imageExts.includes(ext)) {
          $actions.append(
            $('<a href="#" class="btn btn-outline-info btn-alt-config btn-sm view-image" title="Visualizar imagem"><i class="fa fa-eye"></i></a>')
              .attr('data-file', data.path)
          );
        }
        if (!data.is_dir && audioExts.includes(ext)) {
          $actions.append(
            $('<a href="#" class="btn btn-outline-info btn-alt-config btn-sm view-audio" title="Ouvir áudio"><i class="fa fa-headphones"></i></a>')
              .attr('data-file', data.path)
          );
        }
        if (!data.is_dir && videoExts.includes(ext)) {
          $actions.append(
            $('<a href="#" class="btn btn-outline-info btn-alt-config btn-sm view-video" title="Ver vídeo"><i class="fa fa-video-camera"></i></a>')
              .attr('data-file', data.path)
          );
        }
        if (!data.is_dir && pdfExts.includes(ext)) {
          $actions.append(
            $('<a href="#" class="btn btn-outline-info btn-alt-config btn-sm view-pdf" title="Ver PDF"><i class="fa fa-file-pdf-o"></i></a>')
              .attr('data-file', data.path)
          );
        }
        if (!data.is_dir && textExts.includes(ext) && data.is_writable) {
          $actions.append(
            $('<a href="#" class="btn btn-outline-info btn-alt-config btn-sm edit-text" title="Editar texto"><i class="fa fa-edit"></i></a>')
              .attr('data-file', data.path)
          );
        }

        // ADDED: botão para visualizar ZIP no modo grid
        if (!data.is_dir && ext === 'zip') {
          $actions.append(
            $('<a href="#" class="btn btn-outline-warning btn-alt-config btn-sm view-zip" title="Ver conteúdo do ZIP"><i class="fa fa-file-archive-o"></i></a>')
              .attr('data-file', data.path)
          );
        }

        // Compartilhar
        if (!data.is_dir) {
          $actions.append(
            $('<a href="#" class="btn btn-outline-warning btn-alt-config btn-sm share-file" title="Compartilhar"><i class="fa fa-share-alt"></i></a>')
              .attr('data-file', data.path)
          );
        }

        // Download
        if (!data.is_dir) {
          $actions.append(
            $('<a class="btn btn-outline-primary btn-alt-config btn-sm" title="Download"><i class="fa fa-download"></i></a>')
              .attr('href', '?do=download&file=' + encodeURIComponent(data.path))
          );
        }
        // Renomear
        if (data.is_writable || data.is_deleteable) {
          $actions.append(
            $('<a href="#" class="btn btn-outline-success btn-alt-config btn-sm rename-btn" title="Renomear"><i class="fa fa-pencil"></i></a>')
              .attr('data-file', data.path)
              .attr('data-is-dir', data.is_dir ? '1' : '0')
          );
        }
        // Excluir
        if (data.is_deleteable) {
          $actions.append(
            $('<a href="#" class="btn btn-outline-danger btn-alt-config btn-sm delete" title="Excluir"><i class="fa fa-trash"></i></a>')
              .attr('data-file', data.path)
          );
        }
        // informações sobre pastas
        if (data.is_dir) {
          $actions.append(
            $('<a href="#" class="btn btn-sm btn-outline-info btn-alt-config folder-info-btn" title="Informações"><i class="fa fa-info-circle"></i></a>')
              .attr('data-folder', data.path)
          );
        }
        $footer.append($actions);
        $card.append($footer);
        return $card;
      }

      // Tornar funções disponíveis globalmente para pesquisa
      window.renderFileCard = renderFileCard;

      function renderFileRow(data) {
        var iconHtml = data.is_dir ? '<i class="fa fa-folder fa-lg text-warning"></i>' : '<i class="fa fa-file-o fa-lg text-secondary"></i>';
        var $checkbox = $('<input type="checkbox" class="form-check-input select-item me-2">').attr('data-file', data.path);
        var nameClass = data.is_dir ? 'name name-dir' : 'name name-arc';
        var iconHtml = data.is_dir
          ? '<span class="file-card-icon d-flex align-items-center justify-content-center me-2" style="width:28px;height:28px;"><i class="fa fa-folder fa-lg text-warning"></i></span>'
          : '<span class="file-card-icon d-flex align-items-center justify-content-center me-2" style="width:28px;height:28px;"><i class="fa fa-file-o fa-lg text-secondary"></i></span>';
        var $link = $('<a />')
          .addClass(nameClass)
          .attr('href', data.is_dir ? '#' + encodeURIComponent(data.path) : './' + data.path);

        // Destacar termo pesquisado se aplicável
        var displayName = data.name;
        if (data.highlight_query && data.highlight_query.trim() !== '') {
          displayName = window.highlightSearchTerm(data.name, data.highlight_query);
        }

        $link.html(iconHtml + '<span>' + displayName + '</span>');

        // Mostrar caminho da pasta se for resultado de pesquisa
        if (data.dir_path && data.dir_path !== '.' && window.isSearchActive) {
          $link.append('<br><small class="text-muted opacity-75" style="font-size: 0.75em;">📁 ' + data.dir_path + '</small>');
        }

        // botão de renomear (ícone lápis) ao lado do nome
        // var $rename_btn = null;
        // if (data.is_writable || data.is_deleteable) {
        //   $rename_btn = $('<a href="#" class="rename-btn btn btn-outline-success btn-sm ms-2" title="Renomear"><i class="fa fa-pencil"></i></a>')
        //     .attr('data-file', data.path)
        //     .attr('data-is-dir', data.is_dir ? '1' : '0');
        // }

        var allow_direct_link = window.piuClient.server.allow_direct_link;
        if (!data.is_dir && !allow_direct_link) $link.css('pointer-events', 'none');

        var $share_link = null;
        if (!data.is_dir) {
          $share_link = $('<a href="#" class="btn btn-outline-warning btn-md me-2" title="Compartilhar"><i class="fa fa-share-alt"></i></a>').attr('data-file', data.path).addClass('share-file');
        }
        var $dl_link = $('<a class="btn btn-outline-primary btn-md me-2"><i class="fa fa-download"></i></a>').attr('href', '?do=download&file=' + encodeURIComponent(data.path)).addClass('download');
        var $delete_link = $('<a href="#" class="btn btn-outline-danger btn-md me-2"><i class="fa fa-trash"></i></a>').attr('data-file', data.path).addClass('delete');
        var imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
        var audioExts = ['mp3', 'wav', 'ogg', 'm4a'];
        var videoExts = ['mp4', 'webm', 'ogg', 'mkv'];
        var pdfExts = ['pdf'];
        var textExts = ['ssc', 'txt', 'md', 'csv', 'html', 'htm', 'js', 'css', 'json', 'log', 'ini', 'xml', 'yaml', 'yml'];
        // ADDED: extensões ZIP
        var zipExts = ['zip', 'rar', '7z'];
        var ext = data.name.split('.').pop().toLowerCase();

        var $view_link = null;
        if (!data.is_dir && imageExts.includes(ext)) {
          $view_link = $('<a href="#" class="btn btn-outline-info btn-md me-2"><i class="fa fa-eye" aria-hidden="true"></i></a>').attr('data-file', data.path).addClass('view-image');
        }
        var $audio_link = null;
        if (!data.is_dir && audioExts.includes(ext)) {
          $audio_link = $('<a href="#" class="btn btn-outline-info btn-md me-2"><i class="fa fa-headphones" aria-hidden="true"></i></a>').attr('data-file', data.path).addClass('view-audio');
        }
        var $video_link = null;
        if (!data.is_dir && videoExts.includes(ext)) {
          $video_link = $('<a href="#" class="btn btn-outline-info btn-md me-2"><i class="fa fa-video-camera" aria-hidden="true"></i></a>').attr('data-file', data.path).addClass('view-video');
        }
        var $pdf_link = null;
        if (!data.is_dir && pdfExts.includes(ext)) {
          $pdf_link = $('<a href="#" class="btn btn-outline-info btn-md me-2"><i class="fa fa-file-pdf-o" aria-hidden="true"></i></a>').attr('data-file', data.path).addClass('view-pdf');
        }
        var $edit_link = null;
        if (!data.is_dir && textExts.includes(ext) && data.is_writable) {
          $edit_link = $('<a href="#" class="btn btn-outline-info btn-md me-2"><i class="fa fa-edit"></i></a>').attr('data-file', data.path).addClass('edit-text');
        }
        var $rename_btn = null;
        if (data.is_writable || data.is_deleteable) {
          $rename_btn = $('<a href="#" class="btn btn-outline-success btn-md me-2" title="Renomear"><i class="fa fa-pencil"></i></a>')
            .attr('data-file', data.path)
            .attr('data-is-dir', data.is_dir ? '1' : '0')
            .addClass('rename-btn');
        }

        // ADDED: botão para visualizar ZIP
        var $zip_link = null;
        if (!data.is_dir && ext === 'zip') {
          $zip_link = $('<a href="#" class="btn btn-outline-warning btn-md me-2" title="Ver conteúdo do ZIP"><i class="fa fa-file-archive-o"></i></a>').attr('data-file', data.path).addClass('view-zip');
        }

        var $info_btn = null;
        if (data.is_dir) {
          $info_btn = $('<a href="#" class="btn btn-sm btn-outline-info btn-md me-2" title="Informações"><i class="fa fa-info-circle"></i></a>')
            .attr('data-folder', data.path);
        }

        var perms = [];
        if (data.is_readable) perms.push('Visualizar ');
        if (data.is_writable) perms.push(' Editar ');
        if (data.is_executable) perms.push(' Executar');

        var $html = $('<tr />')
          .addClass(data.is_dir ? 'is_dir' : '')
          .addClass('draggable-item')
          .attr('draggable', 'true')
          .append(
            $('<td class="first d-flex align-items-center" />').append($checkbox).append($link)
          )
          .append($('<td/>').attr('data-sort', data.is_dir ? -1 : data.size)
            .html(data.is_dir ? '--' : formatFileSize(data.size, data.is_dir, data.path)))
          .append($('<td/>').attr('data-sort', data.mtime).text(formatTimestamp(data.mtime)))
          .append($('<td/>').text(perms.join('~')))
          .append($('<td class="text-center" />')
            .append($rename_btn ? $rename_btn : '')
            .append($view_link ? $view_link : '')
            .append($audio_link ? $audio_link : '')
            .append($video_link ? $video_link : '')
            .append($pdf_link ? $pdf_link : '')
            .append($zip_link ? $zip_link : '') // ADDED: botão ZIP
            .append(data.is_dir ? ($info_btn ? $info_btn : '') : '')
            .append($edit_link ? $edit_link : '')
            .append(data.is_dir ? '' : $dl_link)
            .append(data.is_deleteable ? $delete_link : '')
          );

        return $html;
      }

      // Tornar funções disponíveis globalmente para pesquisa
      window.renderFileRow = renderFileRow;

      function renderBreadcrumbs(path) {
        var base = "",
          $html = $('<div/>').append($('<a href=#><i class="fa fa-home xlarge"></i></a></div>'));
        $.each(path.split('%2F'), function (k, v) {
          if (v) {
            var v_as_text = decodeURIComponent(v);
            $html.append($('<span/>').text(' ⤑ '))
              .append($('<a/>').attr('href', '#' + base + v).text(v_as_text));
            base += v + '%2F';
          }
        });
        return $html;
      }
      function formatTimestamp(unix_timestamp) {
        var m = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];
        var d = new Date(unix_timestamp * 1000);
        return [(d.getDate() < 10 ? '0' : '') + d.getDate(), '/', m[d.getMonth()], '/', d.getFullYear(), " ",
        (d.getHours() % 12 || 12), ":", (d.getMinutes() < 10 ? '0' : '') + d.getMinutes(),
          " ", d.getHours() >= 12 ? 'PM' : 'AM'].join('');
      }

      // ADDED: função navigate se não existir
      function navigate(path) {
        if (path === '') {
          window.location.hash = '';
        } else {
          window.location.hash = encodeURIComponent(path);
        }
        if (typeof list === 'function') {
          list();
        }
      }

      // Formata tamanho para arquivos e pastas
      function formatFileSize(bytes, isDir, path) {
        var s = ['bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB'];
        var pos = 0;
        var origBytes = bytes;
        for (; bytes >= 1000; pos++, bytes /= 1024);
        var d = Math.round(bytes * 10);
        var str = pos ? [parseInt(d / 10), ".", d % 10, " ", s[pos]].join('') : origBytes + ' bytes';

        // Se for pasta, busca info detalhada via AJAX (folderinfo)
        if (isDir && path) {
          // Adiciona spinner enquanto carrega
          str = '<span class="spinner-border spinner-border-sm text-success" role="status"></span> ...';
          $.get('?do=folderinfo&folder=' + encodeURIComponent(path), function (resp) {
            if (resp && resp.success) {
              var info = resp.size + ' (' + resp.files + ' arquivos, ' + resp.dirs + ' pastas)';
              // Atualiza badge na grid
              $('[data-folder="' + path + '"]').closest('.file-card').find('.badge.bg-danger').html(info);
              // Atualiza na tabela
              $('#list tr').each(function () {
                var $tr = $(this);
                var $cb = $tr.find('.select-item');
                if ($cb.length && $cb.attr('data-file') === path) {
                  $tr.find('td').eq(1).html(info);
                }
              });
            }
          }, 'json');
        }
        return str;
      }

      $('#selectAll').on('change', function () {
        var checked = $(this).prop('checked');
        $('.select-item').prop('checked', checked);
        if (typeof updateDeleteSelectedBtn === 'function') {
          updateDeleteSelectedBtn();
        }
      });

      // Atualizar selectAll se todas forem selecionadas manualmente
      $('#table').on('change', '.select-item', function () {
        var allChecked = $('.select-item').length === $('.select-item:checked').length;
        $('#selectAll').prop('checked', allChecked);
        if (typeof updateDeleteSelectedBtn === 'function') {
          updateDeleteSelectedBtn();
        }
      });


      // Delegar eventos para botões tanto na tabela quanto no grid de blocos
      function delegateFileActions(container) {
        // Visualizar imagem com galeria
        container.on('click', '.view-image', function (e) {
          e.preventDefault();
          var file = $(this).attr('data-file');
          openImageGallery(file);
        });
        // ADDED: Visualizar ZIP
        container.on('click', '.view-zip', function (e) {
          e.preventDefault();
          var file = $(this).attr('data-file');
          openZipViewer(file);
        });
        // Editar texto com CodeMirror
        container.on('click', '.edit-text', function (e) {
          e.preventDefault();
          var file = $(this).attr('data-file');
          if (!file) return;

          currentEditingFile = file;
          $('#textEditModal').data('file', file);
          $('#currentFileName').text(file);
          $('#textEditStatus').text('Carregando arquivo...');

          // Detectar modo baseado na extensão do arquivo
          var mode = getModeFromFilename(file);
          $('#languageSelect').val(mode);

          // Inicializar CodeMirror se ainda não foi inicializado
          if (!codeMirrorEditor) {
            initCodeMirror();
          }

          // Mostrar modal
          $('#textEditModal').css('display', 'flex').hide().fadeIn(150);

          // Carregar conteúdo do arquivo
          $.get('?do=getfile&file=' + encodeURIComponent(file), function (res) {
            if (res && res.success) {
              codeMirrorEditor.setValue(res.content);
              codeMirrorEditor.setOption('mode', mode);

              // Configurações específicas para HTML
              if (mode === 'htmlmixed') {
                codeMirrorEditor.setOption('autoCloseTags', true);
                codeMirrorEditor.setOption('matchTags', { bothTags: true });
                codeMirrorEditor.setOption('extraKeys', {
                  'Ctrl-Space': 'autocomplete',
                  'F11': function (cm) {
                    cm.setOption('fullScreen', !cm.getOption('fullScreen'));
                  },
                  'Esc': function (cm) {
                    if (cm.getOption('fullScreen')) cm.setOption('fullScreen', false);
                  }
                });
                codeMirrorEditor.setOption('hintOptions', {
                  completeSingle: false,
                  hint: CodeMirror.hint.html
                });
              }

              codeMirrorEditor.markClean();
              codeMirrorEditor.focus();

              // Atualizar nome do arquivo na aba
              var fileName = file.split('/').pop();
              $('#tabFileName').text(fileName);

              $('#textEditStatus').text('Arquivo carregado').removeClass('text-danger').addClass('text-success');
              setTimeout(function () {
                $('#textEditStatus').text('').removeClass('text-success');
              }, 2000);
              updateEditorInfo();
            } else {
              $('#textEditStatus').text('Erro ao carregar arquivo').removeClass('text-success').addClass('text-danger');
              alert('Erro ao carregar arquivo: ' + (res && res.error ? res.error.msg : 'unknown'));
            }
          }, 'json').fail(function () {
            $('#textEditStatus').text('Falha na requisição').removeClass('text-success').addClass('text-danger');
            alert('Falha ao buscar o arquivo.');
          });
        });
        // Visualizar PDF
        container.on('click', '.view-pdf', function (e) {
          e.preventDefault();
          var file = $(this).attr('data-file');
          if (!file) return;
          var src = './' + file;
          $('#pdfViewModal').css('display', 'flex').hide().fadeIn(150);
          $('#pdfViewModalIframe').attr('src', src);
        });
        // Áudio - usar o player flutuante melhorado
        container.on('click', '.view-audio', function (e) {
          e.preventDefault();
          var file = $(this).attr('data-file');
          if (!file) return;
          var src = './' + file;

          // Adicionar à playlist e reproduzir
          FloatingAudioPlayer.addToPlaylist(file, src);
          FloatingAudioPlayer.playTrack(file);

          // Mostrar player se não estiver visível
          if (!$('#floatingAudioPlayer').is(':visible')) {
            FloatingAudioPlayer.show();
          } else if (FloatingAudioPlayer.isMinimized) {
            // Se estiver minimizado, expandir temporariamente
            FloatingAudioPlayer.toggleMinimize();
            // Minimizar novamente após 3 segundos
            setTimeout(function () {
              if ($('#floatingAudioPlayer').is(':visible') && !FloatingAudioPlayer.isMinimized) {
                FloatingAudioPlayer.toggleMinimize();
              }
            }, 3000);
          }
        });
        // Vídeo
        container.on('click', '.view-video', function (e) {
          e.preventDefault();
          var file = $(this).attr('data-file');
          if (!file) return;
          var src = './' + file;
          $('#mediaModal').css('display', 'flex').hide().fadeIn(150);
          $('#mediaTitle').text(file);
          $('#mediaPlayerAudio').hide().attr('src', '');
          $('#mediaPlayerVideo').attr('src', src).show()[0].play();
        });
        // Renomear
        container.on('click', '.rename-btn', function (e) {
          e.preventDefault();
          var $btn = $(this);
          var oldPath = $btn.attr('data-file');
          if (!oldPath) return alert('Caminho inválido.');
          var isDir = $btn.attr('data-is-dir') === '1';

          var parts = oldPath.split('/');
          var currentName = parts.pop();
          var currentExt = '';
          var dotPos = currentName.lastIndexOf('.');
          if (!isDir && dotPos > 0) {
            currentExt = currentName.substring(dotPos + 1);
          }

          var newName = prompt('Novo nome para: ' + currentName, currentName);
          if (newName === null) return; // cancelou
          newName = String(newName).trim();
          if (!newName) return alert('Nome inválido.');
          if (newName.indexOf('/') !== -1 || newName.indexOf('\\') !== -1 || newName.indexOf('..') !== -1)
            return alert('Nome inválido: não é permitido barras ou "..".');
          // Se for arquivo com extensão original, não permitir alterar extensão.
          if (!isDir && currentExt) {
            var newDot = newName.lastIndexOf('.');
            if (newDot > 0) {
              var newExt = newName.substring(newDot + 1);
              if (newExt.toLowerCase() !== currentExt.toLowerCase()) {
                return alert('Alterar a extensão do arquivo não é permitido.');
              }
            } else {
              // se usuário não informou extensão, anexa a original automaticamente
              newName = newName + '.' + currentExt;
            }
          }
          // enviar pedido de renomear ao servidor
          $.post('?', { do: 'rename', file: oldPath, newname: newName, xsrf: XSRF }, function (res) {
            if (res && res.success) {
              list();
            } else {
              alert('Erro ao renomear: ' + (res && res.error ? res.error.msg : 'unknown'));
            }
          }, 'json').fail(function (xhr) {
            var msg = 'Falha na requisição.';
            try { var json = JSON.parse(xhr.responseText); if (json && json.error && json.error.msg) msg = json.error.msg; } catch (e) { }
            alert('Erro ao renomear: ' + msg);
          });
        });
        // Excluir
        container.on('click', '.delete', function (e) {
          e.preventDefault();
          var fileToDelete = $(this).attr('data-file');
          if (confirm('Tem certeza que deseja excluir este arquivo ou pasta?')) {
            $.post("", { 'do': 'delete', file: fileToDelete, xsrf: XSRF }, function (response) {
              updateTrashCount();
              loadTrashList();
              list();
            }, 'json');
          }
          return false;
        });
      }
      // Delegar para tabela e grid
      delegateFileActions($('#table'));
      delegateFileActions($('#fileGrid'));

      // ----------------------------
      // JavaScript: adicionar ícone lápis e handlers de renomear
      // procure no arquivo JS embutido a função renderFileRow e substitua/edite a parte correspondente.
      /* dentro da função renderFileRow(data) - substitua/adicione conforme abaixo (mostramos apenas o trecho alterado) */
      function renderFileRow(data) {
        var $checkbox = $('<input type="checkbox" class="form-check-input select-item me-2">').attr('data-file', data.path);
        var $link = $('<a class="name" />')
          .attr('href', data.is_dir ? '#' + encodeURIComponent(data.path) : './' + data.path)
          .attr('data-path', data.path)
          .text(data.name)
          .on('click', function (e) {
            e.preventDefault();
            $(this).closest('tr').find('.select-item').trigger('click');
          })
          .on('dblclick', function (e) {
            e.preventDefault();
            if (data.is_dir) {
              window.location.hash = encodeURIComponent(data.path);
            } else {
              window.location.href = './' + data.path;
            }
          });

        // botão de renomear (ícone lápis) ao lado do nome
        var $rename_btn = null;
        // mostrar se o arquivo/pasta puder ser renomeado (permite para itens editáveis ou deletáveis)
        // if (data.is_writable || data.is_deleteable) {
        //   // ADDED: anexa também se é diretório para o handler cliente aplicar regras de extensão
        //   $rename_btn = $('<a href="#" class="rename-btn btn btn-outline-success btn-sm ms-2" title="Renomear"><i class="fa fa-pencil"></i></a>')
        //     .attr('data-file', data.path)
        //     .attr('data-is-dir', data.is_dir ? '1' : '0');
        // }

        var allow_direct_link = window.piuClient.server.allow_direct_link;
        if (!data.is_dir && !allow_direct_link) $link.css('pointer-events', 'none');

        var $dl_link = $('<a class="btn btn-outline-primary btn-md me-2" title="Baixar"><i class="fa fa-download"></i></a>').attr('href', '?do=download&file=' + encodeURIComponent(data.path)).addClass('download');
        var $delete_link = $('<a href="#" class="btn btn-outline-danger btn-md me-2" title="Excluir"><i class="fa fa-trash"></i></a>').attr('data-file', data.path).addClass('delete');
        // Botão visualizar imagem
        var imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
        // ADDED: extensões de mídia
        var audioExts = ['mp3', 'wav', 'ogg', 'm4a'];
        var videoExts = ['mp4', 'webm', 'ogg', 'mkv'];
        // ADDED: extensões PDF e botão visualizar PDF
        var pdfExts = ['pdf'];
        // ADDED: extensões de texto e botão editar
        var textExts = ['ssc', 'txt', 'md', 'csv', 'html', 'htm', 'js', 'css', 'json', 'log', 'ini', 'xml', 'yaml', 'yml'];
        var ext = data.name.split('.').pop().toLowerCase();
        var $view_link = null;
        if (!data.is_dir && imageExts.includes(ext)) {
          $view_link = $('<a href="#" class="btn btn-outline-info btn-md me-2" title="Visualizar Imagem"><i class="fa fa-eye" aria-hidden="true"></i></a>').attr('data-file', data.path).addClass('view-image');
        }

        // ADDED: botão para reproduzir áudio
        var $audio_link = null;
        if (!data.is_dir && audioExts.includes(ext)) {
          $audio_link = $('<a href="#" class="btn btn-outline-info btn-md me-2" title="Reproduzir Áudio"><i class="fa fa-headphones" aria-hidden="true"></i></a>').attr('data-file', data.path).addClass('view-audio');
        }
        // ADDED: botão para reproduzir vídeo
        var $video_link = null;
        if (!data.is_dir && videoExts.includes(ext)) {
          $video_link = $('<a href="#" class="btn btn-outline-info btn-md me-2" title="Visualizar Vídeo"><i class="fa fa-video-camera" aria-hidden="true"></i></a>').attr('data-file', data.path).addClass('view-video');
        }

        // ADDED: botão para visualizar PDF inline
        var $pdf_link = null;
        if (!data.is_dir && pdfExts.includes(ext)) {
          $pdf_link = $('<a href="#" class="btn btn-outline-info btn-md me-2" title="Visualizar PDF"><i class="fa fa-file-pdf-o" aria-hidden="true"></i></a>').attr('data-file', data.path).addClass('view-pdf');
        }

        // ADDED: botão para edição de texto
        var $edit_link = null;
        if (!data.is_dir && textExts.includes(ext) && data.is_writable) {
          $edit_link = $('<a href="#" class="btn btn-outline-info btn-md me-2" title="Editar"><i class="fa fa-edit"></i></a>').attr('data-file', data.path).addClass('edit-text');
        }

        // ADDED: botão para visualizar ZIP
        var $zip_link = null;
        if (!data.is_dir && ext === 'zip') {
          $zip_link = $('<a href="#" class="btn btn-outline-warning btn-md me-2" title="Ver conteúdo do ZIP"><i class="fa fa-file-archive-o"></i></a>').attr('data-file', data.path).addClass('view-zip');
        }

        // ADDED: botão para compartilhar arquivo
        var $share_link = null;
        if (!data.is_dir) {
          $share_link = $('<a href="#" class="btn btn-outline-warning btn-md me-2" title="Compartilhar"><i class="fa fa-share-alt"></i></a>').attr('data-file', data.path).addClass('share-file');
        }

        // ADDED: botão informações sobre pastas
        var $info_btn = null;
        if (data.is_dir) {
          $info_btn = $('<a href="#" class="btn btn-link btn-sm me-2 folder-info-btn" title="Informações"><i class="fa fa-info-circle"></i></a>')
            .attr('data-folder', data.path);
        }

        var $rename_btn = null;
        if (data.is_writable || data.is_deleteable) {
          $rename_btn = $('<a href="#" class="btn btn-outline-success btn-md me-2" title="Renomear"><i class="fa fa-pencil"></i></a>')
            .attr('data-file', data.path)
            .attr('data-is-dir', data.is_dir ? '1' : '0')
            .addClass('rename-btn');
        }

        var perms = [];
        if (data.is_readable) perms.push('Visualizar ');
        if (data.is_writable) perms.push(' Editar ');
        if (data.is_executable) perms.push(' Executar');

        var $html = $('<tr />')
          .addClass(data.is_dir ? 'is_dir' : '')
          .append(
            $('<td class="first d-flex align-items-center" />').append($checkbox).append($link).append(data.is_dir ? ($info_btn ? $info_btn : '') : '')
          )
          .append($('<td/>').attr('data-sort', data.is_dir ? -1 : data.size)
            .html(data.is_dir ? $('<span class="size" />').text('--') : $('<span class="size" />').text(formatFileSize(data.size))))
          .append($('<td/>').attr('data-sort', data.mtime).text(formatTimestamp(data.mtime)))
          .append($('<td/>').text(perms.join('~')))
          .append($('<td class="text-center" />')
            .append($view_link ? $view_link : '')
            .append($audio_link ? $audio_link : '')
            .append($video_link ? $video_link : '')
            .append($pdf_link ? $pdf_link : '')
            .append($zip_link ? $zip_link : '') // ADDED: botão ZIP
            .append($share_link ? $share_link : '') // ADDED: botão compartilhar
            .append(data.is_dir ? '' : $dl_link)
            .append($edit_link ? $edit_link : '')
            .append($rename_btn ? $rename_btn : '')
            .append(data.is_deleteable ? $delete_link : '')
          );

        return $html;
      }

      // Handler de renomear movido para delegateFileActions para evitar duplicação

      // ADDED: código unificado para mostrar/popular/salvar modal de configurações
      function populateConfigModal() {
        // Preenche campos do modal que estiver presente (compatível com both formats)
        if ($('#settingsModal').length) {
          $('#allow_delete').prop('checked', !!serverConfig.allow_delete);
          $('#allow_upload').prop('checked', !!serverConfig.allow_upload);
          $('#allow_create_folder').prop('checked', !!serverConfig.allow_create_folder);
          $('#allow_create_file').prop('checked', !!serverConfig.allow_create_file);
          $('#allow_direct_link').prop('checked', !!serverConfig.allow_direct_link);
          $('#allow_show_folders').prop('checked', !!serverConfig.allow_show_folders);
          $('#configTime').val(serverConfig.configTime || 5);
          $('#disallowed_patterns').val(Array.isArray(serverConfig.disallowed_patterns) ? serverConfig.disallowed_patterns.join(',') : (serverConfig.disallowed_patterns || ''));
          $('#hidden_patterns').val(Array.isArray(serverConfig.hidden_patterns) ? serverConfig.hidden_patterns.join(',') : (serverConfig.hidden_patterns || ''));
          $('#senha').val(serverConfig.SENHA || '');
        }
        if ($('#configModal').length) {
          $('#cfg_allow_delete').prop('checked', !!serverConfig.allow_delete);
          $('#cfg_allow_upload').prop('checked', !!serverConfig.allow_upload);
          $('#cfg_allow_create_folder').prop('checked', !!serverConfig.allow_create_folder);
          $('#cfg_allow_create_file').prop('checked', !!serverConfig.allow_create_file);
          $('#cfg_allow_direct_link').prop('checked', !!serverConfig.allow_direct_link);
          $('#cfg_allow_show_folders').prop('checked', !!serverConfig.allow_show_folders);
          $('#cfg_configTime').val(serverConfig.configTime || 5);
          $('#cfg_max_upload_size_mb').val(serverConfig.max_upload_size_mb);
          $('#cfg_disallowed').val(Array.isArray(serverConfig.disallowed_patterns) ? serverConfig.disallowed_patterns.join(',') : (serverConfig.disallowed_patterns || ''));
          $('#cfg_hidden').val(Array.isArray(serverConfig.hidden_patterns) ? serverConfig.hidden_patterns.join(',') : (serverConfig.hidden_patterns || ''));
          $('#cfg_SENHA').val(serverConfig.SENHA || '');
        }
      }

      // Ao clicar no botão de abrir configurações, popular modal e mostrar (usa openConfigBtn presente no HTML)
      $('#openConfigBtn').off('click').on('click', function (e) {
        e.preventDefault();
        populateConfigModal();
        // garantir que o modal correto seja exibido (o HTML já contém #configModal estático)
        if ($('#configModal').length) {
          var configModal = new bootstrap.Modal(document.getElementById('configModal'));
          configModal.show();
        } else if ($('#settingsModal').length) {
          var settingsModal = new bootstrap.Modal(document.getElementById('settingsModal'));
          settingsModal.show();
        }
      });

      // Handler unificado para salvar configurações (botão com id saveConfigBtn presente em ambos os modais)
      $('#saveConfigBtn').off('click').on('click', function (event) {
        event.preventDefault(); // Prevent default form submission
        var $btn = $(this);
        $btn.prop('disabled', true);
        var formData = { do: 'saveconfig', xsrf: XSRF };

        // Prioriza os campos sem prefixo (settingsModal). Se não existirem, usa os com prefixo cfg_.
        if ($('#allow_delete').length) {
          formData.allow_delete = $('#allow_delete').prop('checked') ? 1 : 0;
          formData.allow_upload = $('#allow_upload').prop('checked') ? 1 : 0;
          formData.allow_create_folder = $('#allow_create_folder').prop('checked') ? 1 : 0;
          formData.allow_create_file = $('#allow_create_file').prop('checked') ? 1 : 0;
          formData.allow_direct_link = $('#allow_direct_link').prop('checked') ? 1 : 0;
          formData.allow_show_folders = $('#allow_show_folders').prop('checked') ? 1 : 0;
          formData.configTime = $('#configTime').val();
          formData.disallowed_patterns = $('#disallowed_patterns').val();
          formData.hidden_patterns = $('#hidden_patterns').val();
          formData.SENHA = $('#senha').val();
        } else {
          formData.allow_delete = $('#cfg_allow_delete').prop('checked') ? 1 : 0;
          formData.allow_upload = $('#cfg_allow_upload').prop('checked') ? 1 : 0;
          formData.allow_create_folder = $('#cfg_allow_create_folder').prop('checked') ? 1 : 0;
          formData.allow_create_file = $('#cfg_allow_create_file').prop('checked') ? 1 : 0;
          formData.allow_direct_link = $('#cfg_allow_direct_link').prop('checked') ? 1 : 0;
          formData.allow_show_folders = $('#cfg_allow_show_folders').prop('checked') ? 1 : 0;
          formData.configTime = $('#cfg_configTime').val();
          formData.disallowed_patterns = $('#cfg_disallowed').prop('value');
          formData.hidden_patterns = $('#cfg_hidden').prop('value');
          formData.SENHA = $('#cfg_SENHA').val();
        }

        $.post('?', formData, function (res) {
          $btn.prop('disabled', false);
          if (res && res.success) {
            alert('Configurações salvas com sucesso!');
            location.reload();
          } else {
            alert('Erro ao salvar configurações: ' + (res && res.error ? res.error.msg : 'unknown'));
          }
        }, 'json').fail(function () {
          $btn.prop('disabled', false);
          alert('Configurações salvas com sucesso!');
          location.reload();
        });
      });

      // Evita submit do formulário ao pressionar Enter
      $('#configForm').on('submit', function (event) {
        event.preventDefault();
        $('#saveConfigBtn').click();
      });

      // garantir população inicial caso o modal seja aberto por outro caminho
      populateConfigModal();

      // --- FUNÇÕES DE INTEGRAÇÃO COM NUVEM ---

      // Popular configurações de nuvem no modal
      function populateCloudConfig() {
        if (typeof cloudConfig === 'undefined' || !cloudConfig) {
          console.warn('cloudConfig não está definido');
          return;
        }

        // Google Drive
        if (cloudConfig.google_drive) {
          $('#gd_enabled').prop('checked', cloudConfig.google_drive.enabled || false);
          $('#gd_client_id').val(cloudConfig.google_drive.client_id || '');
          $('#gd_client_secret').val(cloudConfig.google_drive.client_secret || '');
          $('#gd_redirect_uri').val(cloudConfig.google_drive.redirect_uri || '');
          $('#gd_access_token').val(cloudConfig.google_drive.access_token || '');
        }

        // Dropbox
        if (cloudConfig.dropbox) {
          $('#db_enabled').prop('checked', cloudConfig.dropbox.enabled || false);
          $('#db_app_key').val(cloudConfig.dropbox.app_key || '');
          $('#db_app_secret').val(cloudConfig.dropbox.app_secret || '');
          $('#db_access_token').val(cloudConfig.dropbox.access_token || '');
        }

        // OneDrive
        if (cloudConfig.onedrive) {
          $('#od_enabled').prop('checked', cloudConfig.onedrive.enabled || false);
          $('#od_client_id').val(cloudConfig.onedrive.client_id || '');
          $('#od_client_secret').val(cloudConfig.onedrive.client_secret || '');
          $('#od_redirect_uri').val(cloudConfig.onedrive.redirect_uri || '');
          $('#od_access_token').val(cloudConfig.onedrive.access_token || '');
        }
      }

      // Popular configurações de nuvem quando o modal for aberto
      $('#cloudModal').on('show.bs.modal', function () {
        populateCloudConfig();
      });

      // Salvar configurações de nuvem
      window.saveCloudConfig = function (provider) {
        var formData = { do: 'savecloudconfig', provider: provider, xsrf: XSRF };

        switch (provider) {
          case 'google_drive':
            formData.enabled = $('#gd_enabled').prop('checked') ? 1 : 0;
            formData.client_id = $('#gd_client_id').val();
            formData.client_secret = $('#gd_client_secret').val();
            formData.redirect_uri = $('#gd_redirect_uri').val();
            formData.access_token = $('#gd_access_token').val();
            break;

          case 'dropbox':
            formData.enabled = $('#db_enabled').prop('checked') ? 1 : 0;
            formData.app_key = $('#db_app_key').val();
            formData.app_secret = $('#db_app_secret').val();
            formData.access_token = $('#db_access_token').val();
            break;

          case 'onedrive':
            formData.enabled = $('#od_enabled').prop('checked') ? 1 : 0;
            formData.client_id = $('#od_client_id').val();
            formData.client_secret = $('#od_client_secret').val();
            formData.redirect_uri = $('#od_redirect_uri').val();
            formData.access_token = $('#od_access_token').val();
            break;
        }

        $.post('?', formData, function (res) {
          if (res && res.success) {
            alert('Configurações de ' + provider + ' salvas com sucesso!');
            // Atualizar configurações locais
            if (typeof cloudConfig !== 'undefined' && cloudConfig) {
              cloudConfig[provider] = formData;
            }
          } else {
            alert('Erro ao salvar configurações: ' + (res && res.error ? res.error.msg : 'Erro desconhecido'));
          }
        }, 'json').fail(function () {
          alert('Falha na requisição para salvar configurações.');
        });
      };

      // Testar conexão com nuvem
      window.testCloudConnection = function (provider) {
        if (typeof cloudConfig === 'undefined' || !cloudConfig || !cloudConfig[provider] || !cloudConfig[provider].enabled) {
          alert('Provedor não está habilitado. Configure primeiro.');
          return;
        }

        $.get('?', { do: 'cloudlist', provider: provider, path: '' }, function (res) {
          if (res && res.success) {
            alert('Conexão com ' + provider + ' bem-sucedida! Encontrados ' + res.files.length + ' arquivos.');
          } else {
            alert('Falha na conexão com ' + provider + ': ' + (res && res.error ? res.error.msg : 'Erro desconhecido'));
          }
        }, 'json').fail(function () {
          alert('Falha na requisição de teste de conexão.');
        });
      };

      // Carregar arquivos da nuvem
      window.loadCloudFiles = function () {
        var provider = $('#cloudProvider').val();
        if (!provider) {
          $('#cloudFilesList').html('<p class="text-white">Selecione um provedor para ver os arquivos</p>');
          return;
        }

        currentCloudProvider = provider;
        $('#cloudFilesList').html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Carregando...</span></div></div>');

        $.get('?', { do: 'cloudlist', provider: provider, path: '' }, function (res) {
          if (res && res.success) {
            renderCloudFiles(res.files);
          } else {
            $('#cloudFilesList').html('<div class="alert alert-danger">Erro ao carregar arquivos: ' + (res && res.error ? res.error.msg : 'Erro desconhecido') + '</div>');
          }
        }, 'json').fail(function () {
          $('#cloudFilesList').html('<div class="alert alert-danger">Falha na requisição</div>');
        });
      };

      // Renderizar arquivos da nuvem
      function renderCloudFiles(files) {
        if (!files || files.length === 0) {
          $('#cloudFilesList').html('<p class="text-muted">Nenhum arquivo encontrado</p>');
          return;
        }

        var html = '<div class="list-group">';
        files.forEach(function (file, index) {
          var icon = file.is_dir ? 'fa-folder text-warning' : 'fa-file-o text-secondary';
          var size = file.is_dir ? '--' : formatFileSize(file.size);

          html += '<div class="list-group-item d-flex align-items-center">';
          html += '<input type="checkbox" class="form-check-input me-2 cloud-file-checkbox" data-file-id="' + file.id + '" data-file-name="' + file.name + '" data-is-dir="' + file.is_dir + '">';
          html += '<i class="fa ' + icon + ' me-2"></i>';
          html += '<div class="flex-grow-1">';
          html += '<div class="fw-bold">' + file.name + '</div>';
          html += '<small class="text-muted">' + size + ' - ' + (file.modified || 'N/A') + '</small>';
          html += '</div>';
          html += '</div>';
        });
        html += '</div>';

        $('#cloudFilesList').html(html);

        // Adicionar event listeners para checkboxes
        $('.cloud-file-checkbox').on('change', function () {
          updateSelectedCloudFiles();
        });
      }

      // Atualizar arquivos selecionados da nuvem
      window.updateSelectedCloudFiles = function () {
        selectedCloudFiles = [];
        $('.cloud-file-checkbox:checked').each(function () {
          selectedCloudFiles.push({
            id: $(this).data('file-id'),
            name: $(this).data('file-name'),
            is_dir: $(this).data('is-dir')
          });
        });
      }

      // Upload para nuvem
      window.uploadToCloud = function () {
        if (!currentCloudProvider) {
          alert('Selecione um provedor de nuvem primeiro.');
          return;
        }

        var selectedFiles = [];
        $('.select-item:checked').each(function () {
          selectedFiles.push($(this).data('file'));
        });

        if (selectedFiles.length === 0) {
          alert('Selecione arquivos locais para fazer upload.');
          return;
        }

        if (confirm('Fazer upload de ' + selectedFiles.length + ' arquivo(s) para ' + currentCloudProvider + '?')) {
          $.post('?', {
            do: 'cloudupload',
            provider: currentCloudProvider,
            files: selectedFiles,
            cloud_path: '',
            xsrf: XSRF
          }, function (res) {
            if (res && res.success) {
              var successCount = res.results.filter(function (r) { return r.success; }).length;
              alert('Upload concluído: ' + successCount + '/' + res.results.length + ' arquivos enviados com sucesso.');
              loadCloudFiles(); // Recarregar lista da nuvem
            } else {
              alert('Erro no upload: ' + (res && res.error ? res.error.msg : 'Erro desconhecido'));
            }
          }, 'json').fail(function () {
            alert('Falha na requisição de upload.');
          });
        }
      }

      // Download da nuvem
      window.downloadFromCloud = function () {
        if (!currentCloudProvider) {
          alert('Selecione um provedor de nuvem primeiro.');
          return;
        }

        if (selectedCloudFiles.length === 0) {
          alert('Selecione arquivos da nuvem para fazer download.');
          return;
        }

        // Filtrar apenas arquivos (não pastas)
        var downloadableFiles = selectedCloudFiles.filter(function (f) { return !f.is_dir; });

        if (downloadableFiles.length === 0) {
          alert('Selecione arquivos (não pastas) para download.');
          return;
        }

        if (confirm('Fazer download de ' + downloadableFiles.length + ' arquivo(s) da nuvem?')) {
          var currentFolder = decodeURIComponent(window.location.hash.substr(1)) || '.';

          $.post('?', {
            do: 'clouddownload',
            provider: currentCloudProvider,
            cloud_files: downloadableFiles,
            local_path: currentFolder,
            xsrf: XSRF
          }, function (res) {
            if (res && res.success) {
              var successCount = res.results.filter(function (r) { return r.success; }).length;
              alert('Download concluído: ' + successCount + '/' + res.results.length + ' arquivos baixados com sucesso.');
              list(); // Recarregar lista local
            } else {
              alert('Erro no download: ' + (res && res.error ? res.error.msg : 'Erro desconhecido'));
            }
          }, 'json').fail(function () {
            alert('Falha na requisição de download.');
          });
        }
      };

      // Sincronizar com nuvem
      window.syncWithCloud = function () {
        if (!currentCloudProvider) {
          alert('Selecione um provedor de nuvem primeiro.');
          return;
        }

        if (confirm('Iniciar sincronização com ' + currentCloudProvider + '?')) {
          var currentFolder = decodeURIComponent(window.location.hash.substr(1)) || '.';

          $.post('?', {
            do: 'cloudsync',
            provider: currentCloudProvider,
            sync_type: 'both',
            local_folder: currentFolder,
            cloud_folder: '',
            xsrf: XSRF
          }, function (res) {
            if (res && res.success) {
              alert('Sincronização iniciada com sucesso!');
              loadCloudFiles();
              list();
            } else {
              alert('Erro na sincronização: ' + (res && res.error ? res.error.msg : 'Erro desconhecido'));
            }
          }, 'json').fail(function () {
            alert('Falha na requisição de sincronização.');
          });
        }
      }

      // --- FUNÇÕES RÁPIDAS DE NUVEM ---

      // Upload rápido para nuvem (usar o primeiro provedor habilitado)
      window.quickUploadToCloud = function () {
        var enabledProvider = null;
        if (typeof cloudConfig !== 'undefined' && cloudConfig) {
          for (var provider in cloudConfig) {
            if (cloudConfig[provider].enabled) {
              enabledProvider = provider;
              break;
            }
          }
        }

        if (!enabledProvider) {
          alert('Nenhum provedor de nuvem está habilitado. Configure primeiro em Nuvem > Configurar Nuvem.');
          return;
        }

        var selectedFiles = [];
        $('.select-item:checked').each(function () {
          selectedFiles.push($(this).data('file'));
        });

        if (selectedFiles.length === 0) {
          alert('Selecione arquivos para fazer upload.');
          return;
        }

        if (confirm('Fazer upload de ' + selectedFiles.length + ' arquivo(s) para ' + enabledProvider + '?')) {
          $.post('?', {
            do: 'cloudupload',
            provider: enabledProvider,
            files: selectedFiles,
            cloud_path: '',
            xsrf: XSRF
          }, function (res) {
            if (res && res.success) {
              var successCount = res.results.filter(function (r) { return r.success; }).length;
              alert('Upload concluído: ' + successCount + '/' + res.results.length + ' arquivos enviados para ' + enabledProvider + '.');
            } else {
              alert('Erro no upload: ' + (res && res.error ? res.error.msg : 'Erro desconhecido'));
            }
          }, 'json').fail(function () {
            alert('Falha na requisição de upload.');
          });
        }
      };

      // Sincronização rápida
      window.quickSyncCloud = function () {
        var enabledProvider = null;
        if (typeof cloudConfig !== 'undefined' && cloudConfig) {
          for (var provider in cloudConfig) {
            if (cloudConfig[provider].enabled) {
              enabledProvider = provider;
              break;
            }
          }
        }

        if (!enabledProvider) {
          alert('Nenhum provedor de nuvem está habilitado. Configure primeiro em Nuvem > Configurar Nuvem.');
          return;
        }

        if (confirm('Iniciar sincronização rápida com ' + enabledProvider + '?')) {
          var currentFolder = decodeURIComponent(window.location.hash.substr(1)) || '.';

          $.post('?', {
            do: 'cloudsync',
            provider: enabledProvider,
            sync_type: 'both',
            local_folder: currentFolder,
            cloud_folder: '',
            xsrf: XSRF
          }, function (res) {
            if (res && res.success) {
              alert('Sincronização com ' + enabledProvider + ' iniciada!');
              list();
            } else {
              alert('Erro na sincronização: ' + (res && res.error ? res.error.msg : 'Erro desconhecido'));
            }
          }, 'json').fail(function () {
            alert('Falha na requisição de sincronização.');
          });
        }
      };

      // Handler para logout com confirmação
      $('#logoutBtn').off('click').on('click', function (e) {
        e.preventDefault();
        if (confirm('Tem certeza que deseja sair da sessão?')) {
          window.location.href = '?do=logout';
        }
      });

      // Handler para criar novo arquivo
      $('#createFileForm').on('submit', function (e) {
        e.preventDefault();
        var filename = $('#newfilename').val().trim();
        var content = $('#newfilecontent').val();
        var $error = $('#createFileError');
        $error.hide().text('');
        if (!filename) {
          $error.text('Nome do arquivo obrigatório.').show();
          return;
        }
        var hashval = decodeURIComponent(window.location.hash.substr(1)) || '.';
        $.post('?', {
          do: 'createfile',
          name: filename,
          content: content,
          file: hashval,
          xsrf: XSRF
        }, function (resp) {
          try {
            var data = typeof resp === 'string' ? JSON.parse(resp) : resp;
            if (data.success) {
              var createFileModal = bootstrap.Modal.getInstance(document.getElementById('createFileModal'));
              if (createFileModal) createFileModal.hide();
              $('#newfilename').val('');
              $('#newfilecontent').val('');
              if (typeof window.list === 'function') window.list();
            } else {
              $error.text((data.error && data.error.msg) || 'Erro desconhecido.').show();
            }
          } catch (ex) {
            $error.text('Erro ao processar resposta do servidor.').show();
          }
        }, 'json').fail(function (xhr) {
          var msg = 'Erro ao criar arquivo.';
          if (xhr.responseText) {
            try {
              var data = JSON.parse(xhr.responseText);
              msg = (data.error && data.error.msg) || msg;
            } catch { }
          }
          $error.text(msg).show();
        });
      });

      // Atualiza o contador de itens na lixeira
      function updateTrashCount() {
        $.get('?do=listtrash', function (res) {
          if (res && res.success && res.results) {
            var count = res.results.length;
            var badge = $('#trashCountBadge');
            if (count > 0) {
              badge.text(count).show();
            } else {
              badge.hide();
            }
          }
        }, 'json');
      }
      // Atualizar ao carregar a página e ao abrir a lixeira
      $(function () {
        updateTrashCount();
        $('#openTrashBtn').on('click', function () {
          setTimeout(updateTrashCount, 500); // Atualiza após possíveis alterações
        });
      });

      // Atualizar lista e badge ao fechar a modal da lixeira
      $('#trashModal').on('hidden.bs.modal', function () {
        loadTrashList();
        updateTrashCount();
        if (typeof window.list === 'function') window.list(); // Atualiza a lista principal se função existir
      });

      // ADDED: Atualizar lista ao fechar a modal de conversão de imagens
      $('#convertImagesModal').on('hidden.bs.modal', function () {
        console.log('Modal de conversão fechada - atualizando lista de arquivos...');

        // Atualizar a lista de arquivos para mostrar os novos arquivos convertidos
        if (typeof list === 'function') {
          list();
        }

        // Manter seleções ativas após conversão
        // $('.select-item').prop('checked', false);
        // $('#selectAll').prop('checked', false);
        if (typeof updateDeleteSelectedBtn === 'function') {
          updateDeleteSelectedBtn();
        }

        // Resetar modal para próximo uso
        $('#conversionProgress').hide();
        $('#conversionResults').hide();
        $('#conversionCompleteActions').hide();
        $('#startConversionBtn').prop('disabled', false).html('<i class="fa fa-cog"></i> Iniciar Conversão');
      });

      // ADDED: Atualizar lista ao fechar modal de conversão de áudio
      $('#convertAudioModal').on('hidden.bs.modal', function () {
        console.log('Modal de conversão de áudio fechada - atualizando lista...');

        if (typeof list === 'function') {
          list();
        }

        if (typeof updateDeleteSelectedBtn === 'function') {
          updateDeleteSelectedBtn();
        }

        // Resetar modal
        $('#audioConversionProgress').hide();
        $('#audioConversionResults').hide();
        $('#startAudioConversionBtn').prop('disabled', false).html('<i class="fa fa-cog"></i> Iniciar Conversão');
        $('#audioQuality').val('high').trigger('change');
      });

      // ADDED: Atualizar lista ao fechar modal de compressão
      $('#compressFilesModal').on('hidden.bs.modal', function () {
        console.log('Modal de compressão fechada - atualizando lista...');

        if (typeof list === 'function') {
          list();
        }

        if (typeof updateDeleteSelectedBtn === 'function') {
          updateDeleteSelectedBtn();
        }

        // Resetar modal
        $('#compressionProgress').hide();
        $('#compressionResults').hide();
        $('#startCompressionBtn').prop('disabled', false).html('<i class="fa fa-compress"></i> Iniciar Compressão');
        $('#archiveName').val('');
        $('#compressionLevel').val(6).trigger('input');
      });

    })

  