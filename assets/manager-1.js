// Variáveis globais do CodeMirror
    var codeMirrorEditor = null;
    var currentEditingFile = null;

    // Função para detectar modo do CodeMirror baseado na extensão do arquivo
    function getModeFromFilename(filename) {
      if (!filename) return 'text';

      var ext = filename.toLowerCase().split('.').pop();
      var modeMap = {
        'js': 'javascript',
        'json': 'application/json',
        'css': 'css',
        'html': 'htmlmixed',
        'htm': 'htmlmixed',
        'xml': 'xml',
        'php': 'php',
        'py': 'python',
        'sql': 'sql',
        'md': 'markdown',
        'markdown': 'markdown',
        'yml': 'yaml',
        'yaml': 'yaml',
        'sh': 'shell',
        'bash': 'shell',
        'dockerfile': 'dockerfile',
        'c': 'text/x-csrc',
        'h': 'text/x-csrc',
        'cpp': 'text/x-c++src',
        'cc': 'text/x-c++src',
        'cxx': 'text/x-c++src',
        'java': 'text/x-java',
        'txt': 'text',
        'log': 'text',
        'ini': 'text',
        'conf': 'text',
        'cfg': 'text'
      };

      return modeMap[ext] || 'text';
    }

    // Inicializar CodeMirror
    function initCodeMirror() {
      if (codeMirrorEditor) {
        codeMirrorEditor.toTextArea();
      }

      codeMirrorEditor = CodeMirror.fromTextArea(document.getElementById('codeEditor'), {
        lineNumbers: true,
        mode: 'text',
        theme: 'monokai',
        indentUnit: 2,
        smartIndent: true,
        tabSize: 2,
        indentWithTabs: false,
        electricChars: true,
        autoCloseBrackets: true,
        matchBrackets: true,
        matchTags: { bothTags: true },
        styleActiveLine: true,
        lineWrapping: true,
        foldGutter: true,
        gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"],
        autoCloseTags: true,
        extraKeys: {
          "Ctrl-F": "findPersistent",
          "Ctrl-H": "replace",
          "Alt-G": "jumpToLine",
          "Ctrl-S": function (cm) { $('#saveTextBtn').click(); },
          "Esc": function (cm) { $('#textEditClose').click(); },
          "Ctrl-Q": function (cm) { cm.foldCode(cm.getCursor()); },
          "Ctrl-Shift-Q": function (cm) { cm.execCommand("unfoldAll"); },
          "Alt-F": "fold",
          "Alt-Shift-F": "unfold",
          "F5": function (cm) { togglePreview(); },
          "Ctrl-Space": "autocomplete",
          "Ctrl-J": "toMatchingTag",
          "Tab": function (cm) { indentSelection(cm); },
          "Shift-Tab": function (cm) { unindentSelection(cm); },
          "Shift-Alt-F": function (cm) { formatCode(cm); },
          "Ctrl-]": function (cm) { indentSelection(cm); },
          "Ctrl-[": function (cm) { unindentSelection(cm); }
        }
      });

      // Atualizar informações do editor
      codeMirrorEditor.on('cursorActivity', updateEditorInfo);
      codeMirrorEditor.on('change', updateEditorInfo);

      // Aplicar configurações iniciais
      updateFontSize();
    }

    // Atualizar informações do editor (linha/coluna/caracteres)
    function updateEditorInfo() {
      if (!codeMirrorEditor) return;

      var cursor = codeMirrorEditor.getCursor();
      var doc = codeMirrorEditor.getDoc();
      var lines = doc.lineCount();
      var chars = doc.getValue().length;
      var selected = doc.getSelection().length;

      // Estilo VS Code na barra de status
      var info = `Ln ${cursor.line + 1}, Col ${cursor.ch + 1}`;
      if (selected > 0) {
        info += ` (${selected} selected)`;
      }

      $('#editorInfo').text(info);

      // Atualizar linguagem na barra de status
      var mode = codeMirrorEditor.getOption('mode');
      var languageName = getLanguageDisplayName(mode);
      $('#languageInfo').text(languageName);
    }

    // Converter modo para nome de exibição
    function getLanguageDisplayName(mode) {
      var modeNames = {
        'javascript': 'JavaScript',
        'application/json': 'JSON',
        'css': 'CSS',
        'htmlmixed': 'HTML',
        'xml': 'XML',
        'php': 'PHP',
        'python': 'Python',
        'sql': 'SQL',
        'markdown': 'Markdown',
        'yaml': 'YAML',
        'shell': 'Shell',
        'dockerfile': 'Dockerfile',
        'text/x-csrc': 'C',
        'text/x-c++src': 'C++',
        'text/x-java': 'Java',
        'text': 'Plain Text'
      };

      return modeNames[mode] || 'Plain Text';
    }

    // Atualizar tamanho da fonte
    function updateFontSize() {
      if (!codeMirrorEditor) return;

      var fontSize = $('#fontSizeSelect').val();
      $('.CodeMirror').css('font-size', fontSize);
      codeMirrorEditor.refresh();
    }

    // Funções de indentação
    function indentSelection(cm) {
      if (cm.somethingSelected()) {
        cm.indentSelection("add");
      } else {
        // Se nada estiver selecionado, inserir tab na posição do cursor
        var cursor = cm.getCursor();
        var indentUnit = cm.getOption("indentUnit");
        var spaces = Array(indentUnit + 1).join(" ");
        cm.replaceSelection(spaces);
      }
    }

    function unindentSelection(cm) {
      if (cm.somethingSelected()) {
        cm.indentSelection("subtract");
      } else {
        // Se nada estiver selecionado, remover indentação da linha atual
        var cursor = cm.getCursor();
        var line = cm.getLine(cursor.line);
        var indentUnit = cm.getOption("indentUnit");

        // Verificar se há espaços ou tabs no início da linha para remover
        var match = line.match(/^(\s*)/);
        if (match && match[1].length > 0) {
          var indent = match[1];
          var toRemove = Math.min(indentUnit, indent.length);
          cm.replaceRange("", { line: cursor.line, ch: 0 }, { line: cursor.line, ch: toRemove });
        }
      }
    }

    function formatCode(cm) {
      var mode = cm.getOption('mode');
      var totalLines = cm.lineCount();

      // Mostrar indicador visual
      $('#formatCodeBtn').addClass('active').find('i').removeClass('fa-magic').addClass('fa-spinner fa-spin');
      $('#textEditStatus').text('Formatando código...');

      // Usar setTimeout para permitir que a UI atualize
      setTimeout(function () {
        // Auto-indentação básica para diferentes linguagens
        cm.operation(function () {
          for (var i = 0; i < totalLines; i++) {
            cm.indentLine(i, "smart");
          }
        });

        // Formatação específica por linguagem
        switch (mode) {
          case 'application/json':
            try {
              var content = cm.getValue();
              var parsed = JSON.parse(content);
              var formatted = JSON.stringify(parsed, null, 2);
              cm.setValue(formatted);
            } catch (e) {
              console.log('Erro ao formatar JSON:', e);
            }
            break;

          case 'htmlmixed':
            // Formatação básica de HTML
            formatHtml(cm);
            break;

          case 'css':
            // Formatação básica de CSS
            formatCss(cm);
            break;

          default:
            // Para outras linguagens, apenas auto-indentar
            break;
        }

        showSuccessNotification('Código formatado com sucesso!');

        // Resetar indicador visual
        $('#formatCodeBtn').removeClass('active').find('i').removeClass('fa-spinner fa-spin').addClass('fa-magic');
        $('#textEditStatus').text('');
      }, 100);
    }

    function formatHtml(cm) {
      var content = cm.getValue();
      var formatted = content
        .replace(/></g, '>\n<')
        .replace(/^\s+|\s+$/g, '')
        .split('\n');

      var result = [];
      var indent = 0;
      var indentStr = '  '; // 2 espaços

      formatted.forEach(function (line) {
        line = line.trim();
        if (line) {
          if (line.match(/^<\//) && !line.match(/^<\/.+>.*<\/.+>$/)) {
            indent--;
          }

          result.push(Array(Math.max(0, indent)).join(indentStr) + line);

          if (line.match(/^<[^\/][^>]*[^\/]>/) && !line.match(/^<[^\/][^>]*[^\/]>.*<\/.+>$/)) {
            indent++;
          }
        }
      });

      cm.setValue(result.join('\n'));
    }

    function formatCss(cm) {
      var content = cm.getValue();
      var formatted = content
        .replace(/\s*{\s*/g, ' {\n  ')
        .replace(/;\s*/g, ';\n  ')
        .replace(/\s*}\s*/g, '\n}\n')
        .replace(/,\s*/g, ',\n')
        .replace(/\n\s*\n/g, '\n')
        .replace(/^\s+|\s+$/g, '');

      cm.setValue(formatted);
    }

    // Mostrar notificação de sucesso
    function showSuccessNotification(message) {
      // Remover notificação anterior se existir
      $('.success-notification').remove();

      // Criar nova notificação
      var notification = $('<div class="success-notification">' +
        '<i class="fa fa-check-circle"></i>' +
        '<span>' + message + '</span>' +
        '</div>');

      // Adicionar ao body
      $('body').append(notification);

      // Remover automaticamente após 3 segundos
      setTimeout(function () {
        notification.remove();
      }, 3000);
    }

    // Funções do Preview
    function togglePreview() {
      if ($('#previewContainer').is(':visible')) {
        hidePreview();
      } else {
        showPreview();
      }
    }

    function showPreview() {
      if (!codeMirrorEditor) return;

      $('#previewContainer').show();
      $('#previewBtn').addClass('active').find('i').removeClass('fa-eye').addClass('fa-eye-slash');

      // Atualizar preview
      updatePreview();

      // Auto-atualizar preview quando o código mudar
      codeMirrorEditor.on('change', updatePreview);
    }

    function hidePreview() {
      $('#previewContainer').hide();
      $('#previewBtn').removeClass('active').find('i').removeClass('fa-eye-slash').addClass('fa-eye');

      // Remover listener de mudança
      if (codeMirrorEditor) {
        codeMirrorEditor.off('change', updatePreview);
      }
    }

    function updatePreview() {
      if (!codeMirrorEditor || !$('#previewContainer').is(':visible')) return;

      var content = codeMirrorEditor.getValue();
      var mode = codeMirrorEditor.getOption('mode');
      var filename = currentEditingFile || '';
      var ext = filename.toLowerCase().split('.').pop();

      var previewHtml = '';

      try {
        switch (mode) {
          case 'htmlmixed':
            // HTML Preview em iframe isolado
            var iframe = document.createElement('iframe');
            iframe.style.width = '100%';
            iframe.style.height = '400px';
            iframe.style.border = '1px solid #ddd';
            iframe.style.borderRadius = '4px';
            iframe.srcdoc = content;

            previewHtml = '<div style="margin-bottom:10px;">' +
              '<div class="alert alert-info" style="margin:0;padding:8px;font-size:12px;">' +
              '<i class="fa fa-info-circle"></i> Preview HTML renderizado' +
              '</div></div>';

            // Adicionar iframe após definir o HTML
            setTimeout(function () {
              $('#previewContent').append(iframe);
            }, 100);
            break;

          case 'xml':
            // XML Preview - mostrar como código formatado
            previewHtml = '<pre style="background:#f8f9fa;padding:15px;border-radius:4px;"><code>' +
              escapeHtml(content) + '</code></pre>';
            break;

          case 'markdown':
            // Markdown Preview (básico)
            previewHtml = renderMarkdown(content);
            break;

          case 'application/json':
          case 'javascript':
            if (ext === 'json') {
              // JSON Preview formatado
              try {
                var jsonObj = JSON.parse(content);
                previewHtml = '<pre style="background:#f8f9fa;padding:15px;border-radius:4px;"><code>' +
                  escapeHtml(JSON.stringify(jsonObj, null, 2)) + '</code></pre>';
              } catch (e) {
                previewHtml = '<div class="alert alert-danger">Erro no JSON: ' + e.message + '</div>';
              }
            } else {
              // JavaScript - mostrar código formatado
              previewHtml = '<pre style="background:#f8f9fa;padding:15px;border-radius:4px;"><code>' +
                escapeHtml(content) + '</code></pre>';
            }
            break;

          case 'css':
            // CSS Preview - aplicar estilos
            previewHtml = '' +
              '<div class="alert alert-info">Estilos CSS aplicados ao preview. Adicione HTML para testar.</div>' +
              '<div style="padding:20px;border:1px solid #ddd;margin-top:10px;">' +
              '<h1>Título H1</h1><h2>Título H2</h2><p>Parágrafo de exemplo</p>' +
              '<button class="btn btn-primary">Botão</button></div>';
            break;

          default:
            // Outros tipos - mostrar como texto
            previewHtml = '<pre style="background:#f8f9fa;padding:15px;border-radius:4px;white-space:pre-wrap;"><code>' +
              escapeHtml(content) + '</code></pre>';
            break;
        }
      } catch (error) {
        previewHtml = '<div class="alert alert-danger">Erro no preview: ' + error.message + '</div>';
      }

      $('#previewContent').html(previewHtml);
    }

    // Renderizador básico de Markdown
    function renderMarkdown(markdown) {
      return markdown
        .replace(/^### (.*$)/gm, '<h3>$1</h3>')
        .replace(/^## (.*$)/gm, '<h2>$1</h2>')
        .replace(/^# (.*$)/gm, '<h1>$1</h1>')
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.*?)\*/g, '<em>$1</em>')
        .replace(/`(.*?)`/g, '<code>$1</code>')
        .replace(/```([\s\S]*?)```/g, '<pre><code>$1</code></pre>')
        .replace(/\n\n/g, '</p><p>')
        .replace(/^(.+)$/gm, '<p>$1</p>')
        .replace(/<p><\/p>/g, '')
        .replace(/<p>(<h[1-6]>.*<\/h[1-6]>)<\/p>/g, '$1')
        .replace(/<p>(<pre>.*<\/pre>)<\/p>/g, '$1');
    }

    // Função auxiliar para escapar HTML
    function escapeHtml(text) {
      var map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      };
      return text.replace(/[&<>"']/g, function (m) { return map[m]; });
    }

    $(function () {
      // Event handlers para as opções do CodeMirror
      $('#languageSelect').on('change', function () {
        if (codeMirrorEditor) {
          codeMirrorEditor.setOption('mode', $(this).val());
          updateEditorInfo(); // Atualizar barra de status
        }
      });

      $('#themeSelect').on('change', function () {
        if (codeMirrorEditor) {
          codeMirrorEditor.setOption('theme', $(this).val());
        }
      });

      $('#fontSizeSelect').on('change', updateFontSize);

      $('#wrapLinesToggle').on('change', function () {
        if (codeMirrorEditor) {
          codeMirrorEditor.setOption('lineWrapping', $(this).is(':checked'));
        }
      });

      $('#showLineNumbers').on('change', function () {
        if (codeMirrorEditor) {
          codeMirrorEditor.setOption('lineNumbers', $(this).is(':checked'));
        }
      });

      // Botões da toolbar
      $('#searchBtn').on('click', function () {
        if (codeMirrorEditor) {
          CodeMirror.commands.findPersistent(codeMirrorEditor);
        }
      });

      $('#replaceBtn').on('click', function () {
        if (codeMirrorEditor) {
          CodeMirror.commands.replace(codeMirrorEditor);
        }
      });

      $('#gotoLineBtn').on('click', function () {
        if (codeMirrorEditor) {
          CodeMirror.commands.jumpToLine(codeMirrorEditor);
        }
      });

      // Botões de folding
      $('#foldBtn').on('click', function () {
        if (codeMirrorEditor) {
          codeMirrorEditor.foldCode(codeMirrorEditor.getCursor());
        }
      });

      $('#unfoldAllBtn').on('click', function () {
        if (codeMirrorEditor) {
          codeMirrorEditor.execCommand("unfoldAll");
        }
      });

      // Botão Salvar
      $('#saveTextBtn').off('click').on('click', function () {
        var file = $('#textEditModal').data('file');
        if (!file || !codeMirrorEditor) return alert('Arquivo inválido.');

        var content = codeMirrorEditor.getValue();
        $('#saveTextBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
        $('#textEditStatus').text('Salvando...');

        $.post('?', {
          do: 'savefile',
          file: file,
          content: content,
          xsrf: (document.cookie.match('(^|; )_sfm_xsrf=([^;]*)') || 0)[2]
        }, function (res) {
          $('#saveTextBtn').prop('disabled', false).html('<i class="fa fa-save"></i>');
          if (res && res.success) {
            $('#textEditStatus').html('<i class="fa fa-check"></i> Salvo com sucesso!').removeClass('text-danger').addClass('text-success');
            setTimeout(function () {
              $('#textEditStatus').text('').removeClass('text-success');
            }, 3000);

            // Mostrar notificação visual de sucesso
            var fileName = $('#textEditModal').data('file').split('/').pop();
            showSuccessNotification('Arquivo "' + fileName + '" salvo com sucesso!');

            // Marcar editor como limpo (sem alterações)
            if (codeMirrorEditor) {
              codeMirrorEditor.markClean();
            }
          } else {
            $('#textEditStatus').text('Erro ao salvar.').removeClass('text-success').addClass('text-danger');
            alert('Erro ao salvar arquivo: ' + (res && res.error ? res.error.msg : 'unknown'));
          }
        }, 'json').fail(function () {
          $('#saveTextBtn').prop('disabled', false).html('<i class="fa fa-save"></i>');
          $('#textEditStatus').text('Erro ao salvar.').removeClass('text-success').addClass('text-danger');
          alert('Falha na requisição para salvar o arquivo.');
        });
      });

      // Botão de Preview
      $('#previewBtn').on('click', function () {
        togglePreview();
      });

      $('#closePreviewBtn').on('click', function () {
        hidePreview();
      });

      // Botões de indentação
      $('#indentBtn').on('click', function () {
        if (codeMirrorEditor) {
          indentSelection(codeMirrorEditor);
        }
      });

      $('#unindentBtn').on('click', function () {
        if (codeMirrorEditor) {
          unindentSelection(codeMirrorEditor);
        }
      });

      $('#formatCodeBtn').on('click', function () {
        if (codeMirrorEditor) {
          formatCode(codeMirrorEditor);
        }
      });

      // Botões da barra de título estilo VS Code
      $('#minimizeBtn').on('click', function () {
        $('#textEditModal').fadeOut(200);
      });

      $('#maximizeBtn').on('click', function () {
        var modal = $('#textEditModal');
        if (modal.hasClass('maximized')) {
          modal.removeClass('maximized');
        } else {
          modal.addClass('maximized');
        }
      });

      // Fechar modal editor de texto
      $(document).on('click', '#textEditClose', function () {
        if (codeMirrorEditor && !codeMirrorEditor.isClean()) {
          if (!confirm('Há alterações não salvas. Deseja realmente fechar?')) {
            return;
          }
        }

        $('#textEditModal').fadeOut(120, function () {
          $(this).css('display', 'none');
        });

        if (codeMirrorEditor) {
          codeMirrorEditor.setValue('');
          codeMirrorEditor.markClean();
        }

        $('#textEditStatus').text('').removeClass('text-success text-danger');
        $('#currentFileName').text('');
        currentEditingFile = null;
      });
      // Fechar modal de preview de imagem (delegação)
      // Handler para fechar modal de imagem (legacy - agora controlado pela galeria)
      $(document).on('click', '#imagePreviewModalClose', function () {
        imageGallery.closeGallery();
      });

      // Fechar modal PDF (delegação)
      $(document).on('click', '#pdfViewModalClose', function () {
        $('#pdfViewModal').fadeOut(120, function () { $(this).css('display', 'none'); });
        $('#pdfViewModalIframe').attr('src', '');
      });

      // Fechar modal de mídia: pausa e limpa src (delegação)
      $(document).on('click', '#mediaModalClose', function () {
        var v = $('#mediaPlayerVideo')[0];
        var a = $('#mediaPlayerAudio')[0];
        try { if (v && !v.paused) v.pause(); } catch (e) { }
        try { if (a && !a.paused) a.pause(); } catch (e) { }
        $('#mediaPlayerVideo').attr('src', '').hide();
        $('#mediaPlayerAudio').attr('src', '').hide();
        $('#mediaModal').fadeOut(120, function () { $(this).css('display', 'none'); });
        $('#mediaTitle').text('');
      });
    });

    // Lixeira: abrir modal e listar itens
    $('#openTrashBtn').on('click', function () {
      var trashModal = new bootstrap.Modal(document.getElementById('trashModal'));
      trashModal.show();
      loadTrashList();
    });

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

    function loadTrashList() {
      var $tbody = $('#trashList');
      $tbody.html('<tr><td colspan="5" class="text-center">Carregando...</td></tr>');
      $.get('?do=listtrash', function (res) {
        if (res && res.success && res.results && res.results.length) {
          var rows = res.results.map(function (item) {
            var name = $('<span/>').text(item.name).html();
            var tipo = item.is_dir ? 'Pasta de arquivos' : 'Arquivo';
            var original = $('<span/>').text(item.original).html();
            var date = item.deleted_at ? $('<span/>').text(item.deleted_at).html() : '-';
            var restoreBtn = '<button class="btn btn-success btn-sm me-1 restore-trash" data-trash="' + encodeURIComponent(item.trash_name) + '"><i class="fa fa-undo"></i> Restaurar</button>';
            var deleteBtn = '<button class="btn btn-danger btn-sm delete-trash" data-trash="' + encodeURIComponent(item.trash_name) + '"><i class="fa fa-trash"></i> Excluir</button>';
            return '<tr>' +
              '<td>' + name + '</td>' +
              '<td>' + tipo + '</td>' +
              // '<td>' + original + '</td>' +
              '<td>' + date + '</td>' +
              '<td>' + restoreBtn + deleteBtn + '</td>' +
              '</tr>';
          });
          $tbody.html(rows.join(''));
        } else {
          $tbody.html('<tr><td colspan="5" class="text-center">Lixeira vazia.</td></tr>');
        }
      }, 'json').fail(function () {
        $tbody.html('<tr><td colspan="4" class="text-danger text-center">Erro ao carregar lixeira.</td></tr>');
      });
    }

    // Restaurar item da lixeira
    $(document).on('click', '.restore-trash', function () {
      var trash = $(this).data('trash');
      if (!trash) return;
      if (!confirm('Restaurar este item?')) return;
      $.post('?', { do: 'restoretrash', trash: trash, xsrf: (document.cookie.match('(^|; )_sfm_xsrf=([^;]*)') || 0)[2] }, function (res) {
        if (res && res.success) {
          showTrashAlert('Item restaurado com sucesso.', 'success');
          loadTrashList();
        } else {
          showTrashAlert('Erro ao restaurar: ' + (res && res.error ? res.error.msg : 'unknown'), 'danger');
        }
      }, 'json').fail(function () {
        showTrashAlert('Falha na requisição.', 'danger');
      });
    });

    // Excluir permanentemente item da lixeira
    $(document).on('click', '.delete-trash', function () {
      var trash = $(this).data('trash');
      if (!trash) return;
      if (!confirm('Excluir permanentemente este item? Esta ação não pode ser desfeita.')) return;
      $.post('?', { do: 'deletetrash', trash: trash, xsrf: (document.cookie.match('(^|; )_sfm_xsrf=([^;]*)') || 0)[2] }, function (res) {
        if (res && res.success) {
          showTrashAlert('Item excluído permanentemente.', 'success');
          loadTrashList();
        } else {
          showTrashAlert('Erro ao excluir: ' + (res && res.error ? res.error.msg : 'unknown'), 'danger');
        }
      }, 'json').fail(function () {
        showTrashAlert('Falha na requisição.', 'danger');
      });
    });

    function showTrashAlert(msg, type) {
      var $alert = $('#trashAlert');
      $alert.removeClass().addClass('alert alert-' + type).text(msg).show();
      setTimeout(function () { $alert.fadeOut(); }, 2500);
    }

    // A atualização da lixeira após exclusão já é feita no handler principal de exclusão
    // Atualizar badge e lista ao excluir selecionados
    $('#deleteSelectedBtn').on('click', function () {
      setTimeout(function () {
        console.log('Atualizando lixeira após exclusão...');
        updateTrashCount();
        loadTrashList();
      }, 2000);
    });

    // ADDED: Sistema de compartilhamento de arquivos
    var ShareSystem = {
      currentFile: null,

      openShareModal: function (file) {
        this.currentFile = file;
        $('#shareFileName').text(file);
        $('#sharePassword').val('');
        $('#shareExpires').val('24');
        $('#shareMaxDownloads').val('0');
        $('#shareResult').hide();

        var modal = new bootstrap.Modal(document.getElementById('shareModal'));
        modal.show();
      },

      generateLink: function () {
        if (!this.currentFile) return;

        var password = $('#sharePassword').val().trim();
        var expires = parseInt($('#shareExpires').val());
        var maxDownloads = parseInt($('#shareMaxDownloads').val());

        var btn = $('#generateShareBtn');
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Gerando...');

        $.post('?', {
          do: 'generatesharelink',
          file: this.currentFile,
          password: password,
          expires_hours: expires,
          max_downloads: maxDownloads,
          xsrf: XSRF
        }, function (res) {
          if (res && res.success) {
            $('#shareUrl').val(res.share_url);

            var info = 'Expira em: ' + res.expires_at;
            if (res.has_password) {
              info += ' • Protegido por senha';
            }
            if (maxDownloads > 0) {
              info += ' • Máx. ' + maxDownloads + ' downloads';
            }
            $('#shareInfo').text(info);

            $('#shareResult').show();

            // Mostrar notificação
            showSuccessNotification('Link de compartilhamento gerado com sucesso!');
          } else {
            alert('Erro ao gerar link: ' + (res && res.error ? res.error.msg : 'Erro desconhecido'));
          }
        }, 'json').fail(function () {
          alert('Erro na comunicação com o servidor');
        }).always(function () {
          btn.prop('disabled', false).html('<i class="fa fa-share-alt"></i> Gerar Link');
        });
      },

      copyUrl: function () {
        var urlField = document.getElementById('shareUrl');
        urlField.select();
        urlField.setSelectionRange(0, 99999); // Para mobile

        try {
          document.execCommand('copy');
          showSuccessNotification('URL copiada para a área de transferência!');
        } catch (err) {
          // Fallback para browsers que não suportam execCommand
          if (navigator.clipboard) {
            navigator.clipboard.writeText(urlField.value).then(function () {
              showSuccessNotification('URL copiada para a área de transferência!');
            }).catch(function () {
              alert('Não foi possível copiar a URL automaticamente. Copie manualmente.');
            });
          } else {
            alert('Não foi possível copiar a URL automaticamente. Copie manualmente.');
          }
        }
      },

      loadShareLinks: function () {
        $('#shareLinksTable').html('<tr><td colspan="8" class="text-center">Carregando...</td></tr>');

        $.get('?do=listsharelinks', function (res) {
          if (res && res.success) {
            var tbody = $('#shareLinksTable');
            tbody.empty();

            if (res.shares && res.shares.length > 0) {
              res.shares.forEach(function (share) {
                // Debug: verificar dados do share
                //console.log('Processando share:', share);

                // Validar dados essenciais
                if (!share.id || !share.security_hash) {
                  console.error('Share com dados incompletos:', share);
                  return; // Pular este share
                }

                var statusBadge = share.expired ?
                  '<span class="badge bg-danger">Expirado</span>' :
                  '<span class="badge bg-success">Ativo</span>';

                var passwordBadge = share.has_password ?
                  '<i class="fa fa-lock text-warning" title="Protegido por senha"></i>' :
                  '<i class="fa fa-unlock text-muted" title="Sem senha"></i>';

                var downloadsText = share.max_downloads > 0 ?
                  share.downloads + '/' + share.max_downloads :
                  share.downloads + '/∞';

                // Escapar dados para evitar problemas de HTML
                var shareId = String(share.id).replace(/['"]/g, '');
                var securityHash = String(share.security_hash).replace(/['"]/g, '');

                // Gerar URL completa do link
                var protocol = window.location.protocol;
                var host = window.location.host;
                var pathname = window.location.pathname;
                var baseUrl = protocol + '//' + host + pathname.substring(0, pathname.lastIndexOf('/'));
                var shareUrl = baseUrl + '/share.php?id=' + shareId + '&hash=' + securityHash;

                // Criar campo com link selecionável
                var uniqueId = 'link_' + shareId;
                var linkHtml = '<div class="input-group input-group-md">' +
                  '<input type="text" class="form-control form-control-md" id="' + uniqueId + '" value="' + shareUrl + '" readonly onclick="this.select()" style="font-size: 11px;">' +
                  '<button class="btn btn-outline-success btn-md copy-url-btn" type="button" data-url="' + shareUrl + '" title="Copiar link">' +
                  '<i class="fa fa-copy"></i>' +
                  '</button>' +
                  '</div>';

                var deleteBtn = '<button class="btn btn-md btn-outline-danger delete-share" data-id="' +
                  shareId + '" title="Excluir link"><i class="fa fa-trash"></i></button>';

                var row = $('<tr>' +
                  '<td>' + share.file_path + '</td>' +
                  '<td>' + share.created_at + '</td>' +
                  '<td>' + share.expires_at + '</td>' +
                  '<td>' + statusBadge + '</td>' +
                  '<td>' + downloadsText + '</td>' +
                  '<td class="text-center">' + passwordBadge + '</td>' +
                  '<td style="min-width: 300px;">' + linkHtml + '</td>' +
                  '<td>' + deleteBtn + '</td>' +
                  '</tr>');

                tbody.append(row);
              });
            } else {
              tbody.html('<tr><td colspan="8" class="text-center text-muted">Nenhum link de compartilhamento encontrado</td></tr>');
            }
          } else {
            $('#shareLinksTable').html('<tr><td colspan="8" class="text-center text-danger">Erro ao carregar links</td></tr>');
          }
        }, 'json').fail(function () {
          $('#shareLinksTable').html('<tr><td colspan="8" class="text-center text-danger">Erro na comunicação com o servidor</td></tr>');
        });
      },

      deleteLink: function (shareId) {
        if (!confirm('Tem certeza que deseja excluir este link de compartilhamento?')) {
          return;
        }

        $.post('?', {
          do: 'deletesharelink',
          share_id: shareId,
          xsrf: XSRF
        }, function (res) {
          if (res && res.success) {
            showSuccessNotification('Link de compartilhamento excluído!');
            ShareSystem.loadShareLinks();
          } else {
            alert('Erro ao excluir link: ' + (res && res.error ? res.error.msg : 'Erro desconhecido'));
          }
        }, 'json').fail(function () {
          alert('Erro na comunicação com o servidor');
        });
      },

      copyShareLink: function (shareId, securityHash) {
        console.log('copyShareLink chamado com:', { shareId: shareId, securityHash: securityHash });

        if (!shareId || !securityHash) {
          alert('Erro: Parâmetros inválidos para copiar link');
          return;
        }

        // Gerar URL completa
        var protocol = window.location.protocol;
        var host = window.location.host;
        var pathname = window.location.pathname;
        var baseUrl = protocol + '//' + host + pathname.substring(0, pathname.lastIndexOf('/'));

        var shareUrl = baseUrl + '/share.php?id=' + shareId + '&hash=' + securityHash;

        console.log('URL gerada:', shareUrl);

        // Método 1: Tentar API moderna do clipboard
        if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
          console.log('Tentando API moderna do clipboard...');
          navigator.clipboard.writeText(shareUrl)
            .then(function () {
              console.log('Sucesso com API moderna');
              showSuccessNotification('Link copiado para a área de transferência!');
            })
            .catch(function (err) {
              console.log('Falha na API moderna:', err);
              ShareSystem.copyWithFallback(shareUrl);
            });
        } else {
          console.log('API moderna não disponível, usando fallback');
          ShareSystem.copyWithFallback(shareUrl);
        }
      },

      copyWithFallback: function (url) {
        console.log('Executando copyWithFallback para:', url);

        // Criar input temporário
        var tempInput = document.createElement('input');
        tempInput.type = 'text';
        tempInput.value = url;
        tempInput.style.position = 'fixed';
        tempInput.style.left = '-9999px';
        tempInput.style.top = '-9999px';
        tempInput.style.opacity = '0';

        document.body.appendChild(tempInput);

        try {
          // Selecionar o texto
          tempInput.focus();
          tempInput.select();
          tempInput.setSelectionRange(0, 99999);

          // Tentar copiar
          var success = document.execCommand('copy');

          if (success) {
            console.log('Sucesso com execCommand');
            showSuccessNotification('Link copiado para a área de transferência!');
          } else {
            console.log('execCommand falhou, mostrando prompt');
            this.showCopyPrompt(url);
          }

        } catch (err) {
          console.log('Erro no execCommand:', err);
          this.showCopyPrompt(url);
        } finally {
          // Limpar
          if (tempInput && tempInput.parentNode) {
            document.body.removeChild(tempInput);
          }
        }
      },

      showCopyPrompt: function (url) {
        // Criar modal personalizado para cópia manual
        var modal = $('<div class="modal fade" tabindex="-1">' +
          '<div class="modal-dialog">' +
          '<div class="modal-content">' +
          '<div class="modal-header">' +
          '<h5 class="modal-title"><i class="fa fa-copy"></i> Copiar Link</h5>' +
          '<button type="button" class="btn-close" data-bs-dismiss="modal"></button>' +
          '</div>' +
          '<div class="modal-body">' +
          '<p>Copie o link abaixo:</p>' +
          '<div class="input-group">' +
          '<input type="text" class="form-control" id="copyUrlInput" value="' + url + '" readonly>' +
          '<button class="btn btn-primary" type="button" onclick="document.getElementById(\'copyUrlInput\').select(); document.execCommand(\'copy\'); showSuccessNotification(\'Link copiado!\');">' +
          '<i class="fa fa-copy"></i> Copiar' +
          '</button>' +
          '</div>' +
          '</div>' +
          '</div>' +
          '</div>' +
          '</div>');

        $('body').append(modal);
        var bootstrapModal = new bootstrap.Modal(modal[0]);
        bootstrapModal.show();

        // Remover modal ao fechar
        modal.on('hidden.bs.modal', function () {
          modal.remove();
        });

        // Auto-selecionar o texto
        modal.on('shown.bs.modal', function () {
          $('#copyUrlInput').select();
        });
      }
    };



    // Event handlers para o sistema de compartilhamento
    $(function () {
      // Debug: Verificar se jQuery e ShareSystem estão carregados
      // console.log('jQuery carregado:', typeof $ !== 'undefined');
      // console.log('ShareSystem carregado:', typeof ShareSystem !== 'undefined');

      // Event handler para copiar URL do link de compartilhamento
      $(document).on('click', '.copy-url-btn', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var url = $btn.data('url');
        var $icon = $btn.find('i');

        // Feedback visual
        var originalClass = $icon.attr('class');
        $icon.attr('class', 'fa fa-spinner fa-spin');
        $btn.prop('disabled', true);

        // Tentar copiar
        if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
          navigator.clipboard.writeText(url)
            .then(function () {
              showSuccessNotification('Link copiado para a área de transferência!');
            })
            .catch(function () {
              // Fallback
              copyToClipboardFallback(url);
            })
            .finally(function () {
              // Restaurar botão
              setTimeout(function () {
                $icon.attr('class', originalClass);
                $btn.prop('disabled', false);
              }, 1000);
            });
        } else {
          copyToClipboardFallback(url);
          setTimeout(function () {
            $icon.attr('class', originalClass);
            $btn.prop('disabled', false);
          }, 1000);
        }
      });

      // Função de fallback para copiar
      function copyToClipboardFallback(text) {
        var tempInput = document.createElement('input');
        tempInput.type = 'text';
        tempInput.value = text;
        tempInput.style.position = 'fixed';
        tempInput.style.left = '-9999px';
        document.body.appendChild(tempInput);
        tempInput.select();
        tempInput.setSelectionRange(0, 99999);

        try {
          var success = document.execCommand('copy');
          if (success) {
            showSuccessNotification('Link copiado para a área de transferência!');
          } else {
            prompt('Copie o link abaixo (Ctrl+C):', text);
          }
        } catch (err) {
          prompt('Copie o link abaixo (Ctrl+C):', text);
        } finally {
          document.body.removeChild(tempInput);
        }
      }

      // Botão de compartilhar arquivo
      $(document).on('click', '.share-file', function (e) {
        e.preventDefault();
        var file = $(this).data('file');
        if (file) {
          ShareSystem.openShareModal(file);
        }
      });

      // Gerar link de compartilhamento
      $('#generateShareBtn').on('click', function () {
        ShareSystem.generateLink();
      });

      // Copiar URL
      $('#copyShareUrl').on('click', function () {
        ShareSystem.copyUrl();
      });

      // Abrir modal de gerenciamento de links
      $('#shareLinksBtn').on('click', function () {
        var modal = new bootstrap.Modal(document.getElementById('shareLinksModal'));
        modal.show();
        ShareSystem.loadShareLinks();
      });

      // Atualizar lista de links
      $('#refreshShareLinks').on('click', function () {
        ShareSystem.loadShareLinks();
      });

      // Excluir link de compartilhamento
      $(document).on('click', '.delete-share', function () {
        var shareId = $(this).data('id');
        ShareSystem.deleteLink(shareId);
      });


    });

    // Floating Audio Player - Sistema completo de reprodução de áudio
    var FloatingAudioPlayer = {
      playlist: [],
      currentTrackIndex: -1,
      isPlaying: false,
      isMinimized: false,
      isRepeat: false,
      isShuffle: false,
      currentVolume: 100,
      isMuted: false,
      audio: null,

      init: function () {
        this.audio = document.getElementById('floatingAudioElement');
        this.bindEvents();
        this.updateDisplay();
      },

      bindEvents: function () {
        var self = this;

        // Controles principais
        $('#playPauseBtn').on('click', function () { self.togglePlayPause(); });
        $('#prevBtn').on('click', function () { self.previousTrack(); });
        $('#nextBtn').on('click', function () { self.nextTrack(); });
        $('#repeatBtn').on('click', function () { self.toggleRepeat(); });
        $('#shuffleBtn').on('click', function () { self.toggleShuffle(); });

        // Controles do header
        $('#togglePlayerBtn').on('click', function () { self.toggleMinimize(); });
        $('#closePlayerBtn').on('click', function () { self.hide(); });
        $('.floating-player-header').on('dblclick', function () { self.toggleMinimize(); });

        // Volume
        $('#muteBtn').on('click', function () { self.toggleMute(); });
        $('#volumeSlider').on('input', function () { self.setVolume($(this).val()); });

        // Barra de progresso
        $('#progressBar').on('click', function (e) { self.seekTo(e); });

        // Playlist
        $('#clearPlaylistBtn').on('click', function () { self.clearPlaylist(); });

        // Eventos do audio element
        this.audio.addEventListener('loadedmetadata', function () { self.updateDisplay(); });
        this.audio.addEventListener('timeupdate', function () { self.updateProgress(); });
        this.audio.addEventListener('ended', function () { self.onTrackEnded(); });
        this.audio.addEventListener('error', function () { self.onError(); });

        // Atalhos do teclado (quando o player estiver visível)
        $(document).on('keydown', function (e) {
          if ($('#floatingAudioPlayer').is(':visible')) {
            switch (e.which) {
              case 32: // Spacebar
                e.preventDefault();
                self.togglePlayPause();
                break;
              case 37: // Left Arrow
                self.seekRelative(-10);
                break;
              case 39: // Right Arrow
                self.seekRelative(10);
                break;
              case 38: // Up Arrow
                e.preventDefault();
                self.setVolume(Math.min(100, self.currentVolume + 10));
                break;
              case 40: // Down Arrow
                e.preventDefault();
                self.setVolume(Math.max(0, self.currentVolume - 10));
                break;
            }
          }
        });

        // Auto-minimizar ao navegar por pastas (não ocultar completamente)
        $(window).on('hashchange', function () {
          if ($('#floatingAudioPlayer').is(':visible') && !self.isMinimized) {
            self.toggleMinimize();
          }
        });
      },

      show: function () {
        $('#floatingAudioPlayer').addClass('show').show();
      },

      hide: function () {
        this.stop();
        $('#floatingAudioPlayer').removeClass('show').hide();
      },

      toggleMinimize: function () {
        this.isMinimized = !this.isMinimized;
        $('#floatingAudioPlayer').toggleClass('minimized', this.isMinimized);
        $('#togglePlayerBtn i').toggleClass('fa-chevron-down fa-chevron-up');

        // Salvar estado no localStorage
        localStorage.setItem('audioPlayerMinimized', this.isMinimized);
      },

      addToPlaylist: function (name, src) {
        // Verificar se já existe na playlist
        var exists = this.playlist.find(function (track) { return track.src === src; });
        if (exists) return;

        this.playlist.push({
          name: name,
          src: src,
          displayName: name.split('/').pop()
        });

        this.updatePlaylistDisplay();
      },

      // Função para adicionar todos os arquivos de áudio da pasta atual
      addCurrentFolderAudios: function () {
        var audioExts = ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac'];
        var self = this;

        // Buscar todos os arquivos de áudio na tabela/grid atual
        $('.view-audio').each(function () {
          var file = $(this).attr('data-file');
          if (file) {
            var ext = file.split('.').pop().toLowerCase();
            if (audioExts.includes(ext)) {
              var src = './' + file;
              self.addToPlaylist(file, src);
            }
          }
        });

        if (this.playlist.length > 0) {
          this.updatePlaylistDisplay();
          this.show();
        }
      },

      removeFromPlaylist: function (index) {
        if (index >= 0 && index < this.playlist.length) {
          // Se remover a faixa atual, parar reprodução
          if (index === this.currentTrackIndex) {
            this.stop();
          } else if (index < this.currentTrackIndex) {
            this.currentTrackIndex--;
          }

          this.playlist.splice(index, 1);
          this.updatePlaylistDisplay();
        }
      },

      clearPlaylist: function () {
        this.stop();
        this.playlist = [];
        this.currentTrackIndex = -1;
        this.updatePlaylistDisplay();
        this.updateDisplay();
      },

      playTrack: function (nameOrIndex) {
        var index;

        if (typeof nameOrIndex === 'string') {
          index = this.playlist.findIndex(function (track) { return track.name === nameOrIndex; });
        } else {
          index = nameOrIndex;
        }

        if (index >= 0 && index < this.playlist.length) {
          this.currentTrackIndex = index;
          var track = this.playlist[index];

          this.audio.src = track.src;
          this.audio.load();

          var self = this;
          this.audio.play().then(function () {
            self.isPlaying = true;
            self.updateDisplay();
            self.updatePlaylistDisplay();
          }).catch(function (error) {
            console.error('Erro ao reproduzir áudio:', error);
            self.onError();
          });
        }
      },

      togglePlayPause: function () {
        if (this.playlist.length === 0) return;

        if (this.audio.paused) {
          if (this.currentTrackIndex === -1) {
            this.playTrack(0);
          } else {
            this.audio.play();
            this.isPlaying = true;
          }
        } else {
          this.audio.pause();
          this.isPlaying = false;
        }

        this.updateDisplay();
      },

      stop: function () {
        this.audio.pause();
        this.audio.currentTime = 0;
        this.isPlaying = false;
        this.updateDisplay();
      },

      previousTrack: function () {
        if (this.playlist.length === 0) return;

        var newIndex;
        if (this.isShuffle) {
          newIndex = Math.floor(Math.random() * this.playlist.length);
        } else {
          newIndex = this.currentTrackIndex > 0 ? this.currentTrackIndex - 1 : this.playlist.length - 1;
        }

        this.playTrack(newIndex);
      },

      nextTrack: function () {
        if (this.playlist.length === 0) return;

        var newIndex;
        if (this.isShuffle) {
          newIndex = Math.floor(Math.random() * this.playlist.length);
        } else {
          newIndex = this.currentTrackIndex < this.playlist.length - 1 ? this.currentTrackIndex + 1 : 0;
        }

        this.playTrack(newIndex);
      },

      onTrackEnded: function () {
        if (this.isRepeat) {
          this.audio.currentTime = 0;
          this.audio.play();
        } else {
          this.nextTrack();
        }
      },

      onError: function () {
        console.error('Erro no player de áudio');
        this.isPlaying = false;
        this.updateDisplay();

        // Tentar próxima faixa se houver
        if (this.playlist.length > 1) {
          setTimeout(() => this.nextTrack(), 1000);
        }
      },

      toggleRepeat: function () {
        this.isRepeat = !this.isRepeat;
        $('#repeatBtn').toggleClass('active', this.isRepeat);

        // Se shuffle estiver ativo, desativar
        if (this.isRepeat && this.isShuffle) {
          this.toggleShuffle();
        }
      },

      toggleShuffle: function () {
        this.isShuffle = !this.isShuffle;
        $('#shuffleBtn').toggleClass('active', this.isShuffle);

        // Se repeat estiver ativo, desativar
        if (this.isShuffle && this.isRepeat) {
          this.toggleRepeat();
        }
      },

      setVolume: function (volume) {
        this.currentVolume = Math.max(0, Math.min(100, volume));
        this.audio.volume = this.currentVolume / 100;
        $('#volumeSlider').val(this.currentVolume);

        // Atualizar ícone do volume
        var icon = 'fa-volume-up';
        if (this.currentVolume === 0 || this.isMuted) {
          icon = 'fa-volume-off';
        } else if (this.currentVolume < 50) {
          icon = 'fa-volume-down';
        }

        $('#muteBtn i').removeClass('fa-volume-off fa-volume-down fa-volume-up').addClass(icon);
      },

      toggleMute: function () {
        this.isMuted = !this.isMuted;

        if (this.isMuted) {
          this.audio.volume = 0;
          $('#muteBtn i').removeClass('fa-volume-up fa-volume-down').addClass('fa-volume-off');
        } else {
          this.audio.volume = this.currentVolume / 100;
          this.setVolume(this.currentVolume);
        }
      },

      seekTo: function (e) {
        if (this.audio.duration) {
          var rect = e.currentTarget.getBoundingClientRect();
          var percent = (e.clientX - rect.left) / rect.width;
          this.audio.currentTime = percent * this.audio.duration;
        }
      },

      seekRelative: function (seconds) {
        if (this.audio.duration) {
          this.audio.currentTime = Math.max(0, Math.min(this.audio.duration, this.audio.currentTime + seconds));
        }
      },

      updateProgress: function () {
        if (this.audio.duration) {
          var percent = (this.audio.currentTime / this.audio.duration) * 100;
          $('#progressFill').css('width', percent + '%');

          $('#currentTime').text(this.formatTime(this.audio.currentTime));
          $('#totalTime').text(this.formatTime(this.audio.duration));
        }
      },

      updateDisplay: function () {
        // Atualizar botão play/pause
        if (this.isPlaying && !this.audio.paused) {
          $('#playPauseBtn i').removeClass('fa-play').addClass('fa-pause');
        } else {
          $('#playPauseBtn i').removeClass('fa-pause').addClass('fa-play');
        }

        // Atualizar informações da faixa
        if (this.currentTrackIndex >= 0 && this.currentTrackIndex < this.playlist.length) {
          var track = this.playlist[this.currentTrackIndex];
          $('#currentTrackName').text(track.displayName);
          $('#trackDisplayName').text(track.displayName);
        } else {
          $('#currentTrackName').text('Nenhuma música');
          $('#trackDisplayName').text('Nenhuma música selecionada');
        }

        // Atualizar tempos
        if (this.audio.duration) {
          $('#totalTime').text(this.formatTime(this.audio.duration));
        } else {
          $('#currentTime').text('0:00');
          $('#totalTime').text('0:00');
        }
      },

      updatePlaylistDisplay: function () {
        var $playlist = $('#playlist');
        $playlist.empty();

        if (this.playlist.length === 0) {
          $playlist.append('<div style="text-align:center;padding:20px;opacity:0.6;">Nenhuma música na playlist</div>');
          return;
        }

        var self = this;
        this.playlist.forEach(function (track, index) {
          var $item = $('<div class="playlist-item">')
            .toggleClass('active', index === self.currentTrackIndex);

          var $name = $('<div class="playlist-item-name">').text(track.displayName);
          var $remove = $('<div class="playlist-item-remove" title="Remover">').html('<i class="fa fa-times"></i>');

          $item.append($name).append($remove);

          $name.on('click', function () { self.playTrack(index); });
          $remove.on('click', function (e) {
            e.stopPropagation();
            self.removeFromPlaylist(index);
          });

          $playlist.append($item);
        });

        // Mostrar/ocultar container de playlist
        if (this.playlist.length > 1) {
          $('#playlistContainer').show();
        } else {
          $('#playlistContainer').hide();
        }
      },

      formatTime: function (seconds) {
        if (isNaN(seconds)) return '0:00';

        var mins = Math.floor(seconds / 60);
        var secs = Math.floor(seconds % 60);
        return mins + ':' + (secs < 10 ? '0' : '') + secs;
      }
    };

    // Inicializar o player quando o documento estiver pronto
    $(function () {
      FloatingAudioPlayer.init();

      // Restaurar estado minimizado
      var wasMinimized = localStorage.getItem('audioPlayerMinimized') === 'true';
      if (wasMinimized) {
        FloatingAudioPlayer.isMinimized = true;
        $('#floatingAudioPlayer').addClass('minimized');
        $('#togglePlayerBtn i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
      }

      // Botões na navbar para controlar o player
      $('#toggleAudioPlayerBtn').on('click', function () {
        if ($('#floatingAudioPlayer').is(':visible')) {
          FloatingAudioPlayer.hide();
        } else {
          FloatingAudioPlayer.show();
        }
        updateNavbarPlayerButton();
      });

      $('#addAllAudiosBtn').on('click', function () {
        FloatingAudioPlayer.addCurrentFolderAudios();
      });

      // Função para atualizar os botões do player na navbar
      function updateNavbarPlayerButton() {
        var isVisible = $('#floatingAudioPlayer').is(':visible');
        var hasPlaylist = FloatingAudioPlayer.playlist.length > 0;
        var isPlaying = FloatingAudioPlayer.isPlaying && !FloatingAudioPlayer.audio.paused;
        var hasAudiosInFolder = $('.view-audio').length > 0;

        // Botão para adicionar áudios da pasta
        $('#addAllAudiosBtn').toggle(hasAudiosInFolder);

        // Botão do player (só aparece se houver playlist)
        if (hasPlaylist) {
          $('#toggleAudioPlayerBtn').show();

          if (isPlaying) {
            $('#audioPlayerStatusText').text('Tocando');
          } else if (isVisible) {
            $('#audioPlayerStatusText').text('Player');
          } else {
            $('#audioPlayerStatusText').text('Player');
          }
        }
      }

      // Sobrescrever algumas funções do player para atualizar o botão da navbar
      var originalShow = FloatingAudioPlayer.show;
      FloatingAudioPlayer.show = function () {
        originalShow.call(this);
        updateNavbarPlayerButton();
      };

      var originalHide = FloatingAudioPlayer.hide;
      FloatingAudioPlayer.hide = function () {
        originalHide.call(this);
        updateNavbarPlayerButton();
      };

      var originalUpdateDisplay = FloatingAudioPlayer.updateDisplay;
      FloatingAudioPlayer.updateDisplay = function () {
        originalUpdateDisplay.call(this);
        updateNavbarPlayerButton();
      };

      var originalAddToPlaylist = FloatingAudioPlayer.addToPlaylist;
      FloatingAudioPlayer.addToPlaylist = function (name, src) {
        originalAddToPlaylist.call(this, name, src);
        updateNavbarPlayerButton();
      };

      var originalClearPlaylist = FloatingAudioPlayer.clearPlaylist;
      FloatingAudioPlayer.clearPlaylist = function () {
        originalClearPlaylist.call(this);
        updateNavbarPlayerButton();
      };
    });

    // ADDED: Funções da galeria de imagens
    var imageGallery = {
      images: [],
      currentIndex: 0,
      isZoomed: false,

      init: function () {
        this.bindEvents();
      },

      bindEvents: function () {
        var self = this;

        // Fechar modal
        $('#imagePreviewModalClose').on('click', function () {
          self.closeGallery();
        });

        // Navegação
        $('#prevImageBtn').on('click', function () {
          self.showPrevious();
        });

        $('#nextImageBtn').on('click', function () {
          self.showNext();
        });

        // Zoom
        $('#zoomToggleBtn').on('click', function () {
          self.toggleZoom();
        });

        // Download da imagem atual
        $('#downloadCurrentBtn').on('click', function () {
          if (self.images.length > 0) {
            var currentImage = self.images[self.currentIndex];
            window.open('?do=download&file=' + encodeURIComponent(currentImage.path), '_blank');
          }
        });

        // Navegação por teclado
        $(document).on('keydown', function (e) {
          if ($('#imagePreviewModal').is(':visible')) {
            switch (e.keyCode) {
              case 27: // ESC
                self.closeGallery();
                break;
              case 37: // Seta esquerda
                self.showPrevious();
                break;
              case 39: // Seta direita
                self.showNext();
                break;
              case 32: // Espaço
                e.preventDefault();
                self.toggleZoom();
                break;
            }
          }
        });

        // Clique na imagem para zoom
        $('#imagePreviewModalImg').on('click', function () {
          self.toggleZoom();
        });

        // Fechar modal clicando no fundo
        $('#imagePreviewModal').on('click', function (e) {
          if (e.target === this) {
            self.closeGallery();
          }
        });
      },

      loadImagesFromCurrentFolder: function () {
        var imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
        this.images = [];

        // Coletar imagens da lista atual (tanto tabela quanto grid)
        $('.view-image').each(function () {
          var file = $(this).attr('data-file');
          var fileName = file.split('/').pop();
          var ext = fileName.split('.').pop().toLowerCase();

          if (imageExts.includes(ext)) {
            imageGallery.images.push({
              path: file,
              name: fileName,
              src: './' + file
            });
          }
        });

        // Ordenar por nome
        this.images.sort(function (a, b) {
          return a.name.localeCompare(b.name);
        });
      },

      openGallery: function (selectedFile) {
        this.loadImagesFromCurrentFolder();

        if (this.images.length === 0) {
          alert('Nenhuma imagem encontrada na pasta atual.');
          return;
        }

        // Encontrar o índice da imagem selecionada
        this.currentIndex = this.images.findIndex(function (img) {
          return img.path === selectedFile;
        });

        if (this.currentIndex === -1) {
          this.currentIndex = 0;
        }

        this.showImage();
        this.updateThumbnails();
        $('#imagePreviewModal').fadeIn();
      },

      showImage: function () {
        if (this.images.length === 0) return;

        var currentImage = this.images[this.currentIndex];

        // Atualizar imagem principal
        $('#imagePreviewModalImg').attr('src', currentImage.src);

        // Atualizar informações
        $('#imageTitle').text(currentImage.name);
        $('#imageCounter').text((this.currentIndex + 1) + ' de ' + this.images.length);

        // Resetar zoom
        this.isZoomed = false;
        $('#imagePreviewModalImg').removeClass('zoomed');
        $('#zoomToggleBtn i').removeClass('fa-search-minus').addClass('fa-search-plus');

        // Atualizar thumbnails ativas
        $('.thumbnail-item').removeClass('active');
        $('.thumbnail-item[data-index="' + this.currentIndex + '"]').addClass('active');

        // Scroll thumbnail para visível
        this.scrollThumbnailIntoView();

        // Atualizar visibilidade dos botões de navegação
        $('#prevImageBtn').toggle(this.images.length > 1);
        $('#nextImageBtn').toggle(this.images.length > 1);
      },

      showPrevious: function () {
        if (this.images.length <= 1) return;
        this.currentIndex = this.currentIndex > 0 ? this.currentIndex - 1 : this.images.length - 1;
        this.showImage();
      },

      showNext: function () {
        if (this.images.length <= 1) return;
        this.currentIndex = this.currentIndex < this.images.length - 1 ? this.currentIndex + 1 : 0;
        this.showImage();
      },

      toggleZoom: function () {
        var $img = $('#imagePreviewModalImg');
        this.isZoomed = !this.isZoomed;

        if (this.isZoomed) {
          $img.addClass('zoomed');
          $('#zoomToggleBtn i').removeClass('fa-search-plus').addClass('fa-search-minus');
        } else {
          $img.removeClass('zoomed');
          $('#zoomToggleBtn i').removeClass('fa-search-minus').addClass('fa-search-plus');
        }
      },

      updateThumbnails: function () {
        var $container = $('#imageThumbnailGallery');
        $container.empty();

        var self = this;
        this.images.forEach(function (image, index) {
          var $thumb = $('<img>')
            .addClass('thumbnail-item')
            .attr('src', image.src)
            .attr('data-index', index)
            .attr('title', image.name)
            .on('click', function () {
              self.currentIndex = index;
              self.showImage();
            });

          if (index === self.currentIndex) {
            $thumb.addClass('active');
          }

          $container.append($thumb);
        });

        // Mostrar/ocultar galeria de thumbnails
        $container.toggle(this.images.length > 1);
      },

      scrollThumbnailIntoView: function () {
        var $container = $('#imageThumbnailGallery');
        var $activeThumb = $('.thumbnail-item.active');

        if ($activeThumb.length > 0) {
          var containerWidth = $container.width();
          var thumbLeft = $activeThumb.position().left;
          var thumbWidth = $activeThumb.outerWidth();
          var scrollLeft = $container.scrollLeft();

          if (thumbLeft < 0) {
            $container.scrollLeft(scrollLeft + thumbLeft - 20);
          } else if (thumbLeft + thumbWidth > containerWidth) {
            $container.scrollLeft(scrollLeft + thumbLeft + thumbWidth - containerWidth + 20);
          }
        }
      },

      closeGallery: function () {
        $('#imagePreviewModal').fadeOut();
        $('#imagePreviewModalImg').attr('src', '');
        this.isZoomed = false;
      }
    };

    // Função global para abrir galeria
    function openImageGallery(selectedFile) {
      imageGallery.openGallery(selectedFile);
    }

    // Inicializar galeria quando documento estiver pronto
    $(function () {
      imageGallery.init();
    });

    // ADDED: Função para abrir modal de visualização ZIP
    function openZipViewer(file) {
      $.get('?do=listzip&file=' + encodeURIComponent(file), null, 'json')
        .done(function (data) {
          console.log('Resposta do servidor:', data); // Debug
          if (data && data.success) {
            $('#zipFileName').text(data.zip_file);
            $('#zipFileCount').text(data.total_files);

            // Sugerir nome da pasta baseado no nome do arquivo ZIP
            const zipName = data.zip_file.split('/').pop().replace('.zip', '');
            $('#extractFolderName').val(zipName + '_extracted');

            // Preencher lista de arquivos
            let html = '';
            data.files.forEach(function (file) {
              const icon = file.is_dir ? 'fa-folder' : 'fa-file-o';
              const sizeText = file.is_dir ? '-' : formatBytes(file.size);
              const compressedText = file.is_dir ? '-' : formatBytes(file.compressed_size);

              html += `
            <tr>
              <td>
                <i class="fa ${icon}"></i> 
                ${escapeHtml(file.name)}
              </td>
              <td>${file.modified}</td>
              <td>${sizeText}</td>
              <td>${compressedText}</td>
            </tr>
          `;
            });

            $('#zipFilesList').html(html);
            var zipViewModal = new bootstrap.Modal(document.getElementById('zipViewModal'));
            zipViewModal.show();

            // Configurar botão de extração
            $('#extractZipBtn').off('click').on('click', function () {
              extractZip(data.zip_file);
            });

          } else {
            console.log('Erro na resposta do servidor:', data); // Debug
            alert('Erro ao carregar conteúdo do ZIP: ' + (data && data.error ? data.error.msg : 'Erro desconhecido'));
          }
        })
        .fail(function (xhr, status, error) {
          // Tenta extrair mensagem de erro do JSON retornado
          var msg = error;
          if (xhr && xhr.responseText) {
            try {
              var data = JSON.parse(xhr.responseText);
              if (data && data.error && data.error.msg) {
                msg = data.error.msg;
              }
            } catch (e) {
              msg = xhr.responseText;
            }
          }
          alert('Erro ao comunicar com o servidor: ' + msg);
        });
    }

    // ADDED: Função para extrair ZIP
    function extractZip(zipFile) {
      const folderName = $('#extractFolderName').val().trim();

      if (!folderName) {
        alert('Por favor, digite um nome para a pasta de extração.');
        return;
      }

      const btn = $('#extractZipBtn');
      const originalText = btn.html();

      btn.html('<i class="fa fa-spinner fa-spin"></i> Extraindo...').prop('disabled', true);

      $.post('', {
        do: 'extractzip',
        file: zipFile,
        extract_to: folderName,
        xsrf: token
      }, null, 'json')
        .done(function (data) {
          console.log('Resposta extração:', data); // Debug
          if (data && data.success) {
            alert(`ZIP extraído com sucesso!\n${data.extracted} arquivos extraídos para: ${data.extract_path}`);
            var zipViewModal = bootstrap.Modal.getInstance(document.getElementById('zipViewModal'));
            if (zipViewModal) zipViewModal.hide();
            // Recarregar lista de arquivos
            if (typeof list === 'function') {
              list();
            } else {
              window.location.reload();
            }
          } else {
            console.log('Erro na extração:', data); // Debug
            alert('Erro ao extrair ZIP: ' + (data && data.error ? data.error.msg : 'Erro desconhecido'));
          }
        })
        .fail(function (xhr, status, error) {
          // Tenta extrair mensagem de erro do JSON retornado
          var msg = error;
          if (xhr && xhr.responseText) {
            try {
              var data = JSON.parse(xhr.responseText);
              if (data && data.error && data.error.msg) {
                msg = data.error.msg;
              }
            } catch (e) {
              msg = xhr.responseText;
            }
          }
          alert('Erro ao processar: ' + msg);
        })
        .always(function () {
          btn.html(originalText).prop('disabled', false);
        });
    }

    // ADDED: Função auxiliar para formatar bytes
    function formatBytes(bytes, decimals = 2) {
      if (bytes === 0) return '0 B';
      const k = 1024;
      const dm = decimals < 0 ? 0 : decimals;
      const sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    // ADDED: Função auxiliar para escape HTML
    function escapeHtml(text) {
      const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      };
      return text.replace(/[&<>"']/g, function (m) { return map[m]; });
    }
  