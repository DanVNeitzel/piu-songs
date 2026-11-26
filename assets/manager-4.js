$(function () {
      // Atualiza também o botão mover/copiar quando a seleção mudar
      // hook: sempre que um checkbox mudar, atualiza botões
      $('#table').on('change', '.select-item', function () {
        var allChecked = $('.select-item').length === $('.select-item:checked').length;
        $('#selectAll').prop('checked', allChecked);
        if (typeof updateDeleteSelectedBtn === 'function') {
          updateDeleteSelectedBtn();
        }
      });

      // Função para obter todas as pastas recursivamente
      function getAllFolders(path, callback) {
        console.log('getAllFolders chamado para:', path);
        $.get('?do=listfolders&file=' + encodeURIComponent(path), function (data) {
          console.log('Resposta recebida do listfolders:', data);
          if (data && data.success && data.folders) {
            console.log('Encontradas', data.folders.length, 'pastas');
            callback(data.folders);
          } else {
            console.log('Erro ou nenhuma pasta encontrada');
            callback([]);
          }
        }, 'json').fail(function (xhr, status, error) {
          console.log('Erro na requisição listfolders:', { xhr: xhr, status: status, error: error });
          callback([]);
        });
      }

      // Handler para abrir modal mover/copiar
      $('#moveCopyBtn').on('click', function (e) {
        e.preventDefault();
        var filesToOperate = $('.select-item:checked').map(function () { return $(this).attr('data-file'); }).get();
        if (!filesToOperate.length) return alert('Nenhum arquivo/pasta selecionado.');

        // popular lista
        $('#moveCopyFileList').empty();
        filesToOperate.forEach(function (f) { $('#moveCopyFileList').append($('<li/>').text(f)); });
        $('#moveCopySummary').text('Itens selecionados: ' + filesToOperate.length);
        $('#moveCopyResult').hide().empty();

        // Mostrar loading no select
        var $select = $('#moveCopyDest');
        var $spinner = $('#loadingSpinner');
        $select.empty();
        $select.append($('<option value="">Carregando pastas...</option>'));
        $select.prop('disabled', true);
        $spinner.show();

        // Popular select de pastas recursivamente
        getAllFolders('.', function (folders) {
          console.log('Populando select com', folders.length, 'pastas');
          $select.empty();
          $select.prop('disabled', false);
          $spinner.hide();
          $select.append($('<option value=".">📁 Pasta raiz (./)</option>'));

          folders.forEach(function (folder, index) {
            console.log('Adicionando pasta', index + 1, ':', folder.path);
            var displayText = folder.name;
            // Adicionar indentação visual baseada na profundidade
            var depth = (folder.path.match(/\//g) || []).length;
            var indent = '  '.repeat(depth);
            var folderIcon = '📁 ';
            $select.append($('<option/>').val(folder.path).text(indent + folderIcon + folder.path));
          });

          if (folders.length === 0) {
            console.log('Nenhuma pasta encontrada, adicionando opção de aviso');
            $select.append($('<option value="" disabled>Nenhuma pasta encontrada</option>'));
          }

          console.log('Select populado com', $select.find('option').length, 'opções total');
        });

        var moveCopyModal = new bootstrap.Modal(document.getElementById('moveCopyModal'));
        moveCopyModal.show();
      });

      // Abrir modal de informações da pasta
      $(document).on('click', '.folder-info-btn', function (e) {
        e.preventDefault();
        var folder = $(this).data('folder');
        $('#folderInfoContent').html('<div class="text-center">Carregando...</div>');
        var modal = new bootstrap.Modal(document.getElementById('folderInfoModal'));
        modal.show();
        $.get('?do=folderinfo&folder=' + encodeURIComponent(folder), function (resp) {
          if (resp.success) {
            if (resp.modified) {
              // Ajusta formato para DD/MM/YYYY HH:mm:ss
              var dt = resp.modified;
              // Se vier como string, tenta converter para Date
              var parts = dt.split(' ');
              if (parts.length === 2) {
                var dateParts = parts[0].split('/');
                var timeParts = parts[1].split(':');
                if (dateParts.length === 3 && timeParts.length === 3) {
                  var d = new Date(dateParts[2], dateParts[1] - 1, dateParts[0], timeParts[0], timeParts[1], timeParts[2]);
                  resp.modified = d.toLocaleString('pt-BR', { hour12: false });
                }
              }
            }
            var html = '<ul class="list-group">';
            html += '<li class="list-group-item"><b>Nome:</b> ' + resp.name + '</li>';
            html += '<li class="list-group-item"><b>Tamanho:</b> ' + resp.size + '</li>';
            html += '<li class="list-group-item"><b>Última modificação:</b> ' + resp.modified + '</li>';
            html += '<li class="list-group-item"><b>Nº de arquivos:</b> ' + resp.files + '</li>';
            html += '<li class="list-group-item"><b>Nº de diretórios:</b> ' + resp.dirs + '</li>';
            html += '</ul>';
            $('#folderInfoContent').html(html);
          } else {
            $('#folderInfoContent').html('<div class="text-danger">Erro ao obter informações.</div>');
          }
        }, 'json');
      });

      // ADDED: Handler para conversão de imagens
      $('#convertImagesBtn').on('click', function (e) {
        e.preventDefault();
        var selectedFiles = $('.select-item:checked').map(function () {
          return $(this).attr('data-file');
        }).get();

        // Filtrar apenas imagens
        var imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'tiff', 'tif', 'svg', 'avif'];
        var imageFiles = selectedFiles.filter(function (file) {
          var ext = file.split('.').pop().toLowerCase();
          return imageExts.includes(ext);
        });

        if (!imageFiles.length) {
          alert('Nenhuma imagem selecionada.');
          return;
        }

        // Preencher lista de imagens selecionadas
        var filesList = '<strong>Imagens selecionadas (' + imageFiles.length + '):</strong><br>';
        imageFiles.forEach(function (file) {
          var size = getFileSize(file);
          filesList += '<small class="d-block text-info">• ' + file + (size ? ' (' + size + ')' : '') + '</small>';
        });
        $('#selectedImagesList').html(filesList);

        // Mostrar modal
        var modal = new bootstrap.Modal(document.getElementById('convertImagesModal'));
        modal.show();
      });

      // Handler do slider de qualidade
      $('#imageQuality').on('input', function () {
        $('#qualityValue').text($(this).val());
      });

      // Handler do checkbox "preservar original"
      $('#preserveOriginal').on('change', function () {
        var isChecked = $(this).prop('checked');
        if (!isChecked) {
          $('#addSuffix').prop('checked', false).prop('disabled', true);
        } else {
          $('#addSuffix').prop('disabled', false);
        }
      });

      // Handler para iniciar conversão
      $('#startConversionBtn').on('click', function () {
        // Função local para formatar bytes
        function formatBytes(bytes, decimals = 2) {
          if (bytes === 0) return '0 B';
          const k = 1024;
          const dm = decimals < 0 ? 0 : decimals;
          const sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB'];
          const i = Math.floor(Math.log(bytes) / Math.log(k));
          return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }

        var selectedFiles = $('.select-item:checked').map(function () {
          return $(this).attr('data-file');
        }).get();

        // Filtrar apenas imagens
        var imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'tiff', 'tif', 'svg', 'avif'];
        var imageFiles = selectedFiles.filter(function (file) {
          var ext = file.split('.').pop().toLowerCase();
          return imageExts.includes(ext);
        });

        if (!imageFiles.length) {
          alert('Nenhuma imagem selecionada.');
          return;
        }

        // Coletar configurações
        var settings = {
          do: 'convertimages',
          files: imageFiles,
          format: $('#outputFormat').val(),
          quality: $('#imageQuality').val(),
          max_width: $('#maxWidth').val(),
          max_height: $('#maxHeight').val(),
          preserve_original: $('#preserveOriginal').prop('checked') ? '1' : '0',
          add_suffix: $('#addSuffix').prop('checked') ? '1' : '0',
          xsrf: XSRF
        };

        // Mostrar progresso
        $('#conversionProgress').show();
        $('#conversionResults').hide();
        $('#startConversionBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Convertendo...');

        // Simular progresso durante a conversão
        var progress = 0;
        var progressInterval = setInterval(function () {
          progress += Math.random() * 15;
          if (progress > 90) progress = 90;
          $('#conversionProgressBar').css('width', progress + '%').text(Math.round(progress) + '%');
        }, 200);

        // Iniciar conversão
        $.post('?', settings, function (response) {
          clearInterval(progressInterval);
          $('#conversionProgressBar').css('width', '100%').text('100%');
          $('#conversionStatus').text('Conversão finalizada!');

          if (response && response.success) {
            var resultsHtml = '<div class="alert alert-success">';
            resultsHtml += '<strong>Conversão concluída!</strong><br>';
            resultsHtml += 'Total de imagens processadas: ' + response.total + '<br>';
            resultsHtml += 'Imagens convertidas com sucesso: ' + response.converted + '<br>';
            resultsHtml += '</div>';

            // Mostrar detalhes de cada arquivo
            if (response.results && response.results.length > 0) {
              resultsHtml += '<div class="mt-3">';
              response.results.forEach(function (result) {
                var status = result.success ? 'success' : 'danger';
                var icon = result.success ? 'check-circle' : 'times-circle';
                resultsHtml += '<div class="alert alert-' + status + ' py-2 mb-2">';
                resultsHtml += '<i class="fa fa-' + icon + '"></i> <strong>' + result.file + '</strong><br>';

                if (result.success) {
                  resultsHtml += '<small>';
                  resultsHtml += 'Arquivo de saída: ' + result.output_file + '<br>';
                  resultsHtml += 'Tamanho original: ' + formatBytes(result.original_size) + '<br>';
                  resultsHtml += 'Novo tamanho: ' + formatBytes(result.new_size) + '<br>';
                  resultsHtml += 'Compressão: ' + result.compression_ratio + '%';
                  resultsHtml += '</small>';
                } else {
                  resultsHtml += '<small class="text-danger">Erro: ' + (result.error || 'Erro desconhecido') + '</small>';
                }
                resultsHtml += '</div>';
              });
              resultsHtml += '</div>';
            }

            $('#resultsContainer').html(resultsHtml);
            $('#conversionResults').show();

            // Mostrar botão especial para fechar e ver novos arquivos se houver conversões bem-sucedidas
            if (response.converted > 0) {
              $('#conversionCompleteActions').show();
            }

          } else {
            var errorMsg = response && response.error ? response.error.msg : 'Erro desconhecido';
            $('#resultsContainer').html('<div class="alert alert-danger">Erro na conversão: ' + errorMsg + '</div>');
            $('#conversionResults').show();
          }
        }, 'json').fail(function (xhr) {
          clearInterval(progressInterval);
          var errorMsg = 'Falha na requisição';
          try {
            var data = JSON.parse(xhr.responseText);
            if (data && data.error && data.error.msg) {
              errorMsg = data.error.msg;
            }
          } catch (e) { }

          $('#resultsContainer').html('<div class="alert alert-danger">Erro: ' + errorMsg + '</div>');
          $('#conversionResults').show();
        }).always(function () {
          $('#startConversionBtn').prop('disabled', false).html('<i class="fa fa-cog"></i> Iniciar Conversão');
        });
      });

      // Função auxiliar para obter tamanho do arquivo
      function getFileSize(filePath) {
        var $row = $('.select-item[data-file="' + filePath + '"]').closest('tr');
        if ($row.length) {
          return $row.find('td').eq(1).text().trim();
        }
        return null;
      }

      // ADDED: Handler para conversão de áudio
      $('#convertAudioBtn').on('click', function (e) {
        e.preventDefault();
        var selectedFiles = $('.select-item:checked').map(function () {
          return $(this).attr('data-file');
        }).get();

        // Filtrar apenas arquivos de áudio
        var audioExts = ['mp3', 'wav', 'ogg', 'aac', 'm4a', 'flac', 'wma', 'mp4', 'avi', 'mkv', 'mov'];
        var audioFiles = selectedFiles.filter(function (file) {
          var ext = file.split('.').pop().toLowerCase();
          return audioExts.includes(ext);
        });

        if (!audioFiles.length) {
          alert('Nenhum arquivo de áudio selecionado.');
          return;
        }

        // Preencher lista de arquivos de áudio selecionados com informações úteis
        var filesList = '<strong>Arquivos de áudio selecionados (' + audioFiles.length + '):</strong><br>';

        // Adicionar informação sobre processamento para grandes quantidades
        if (audioFiles.length > 100) {
          filesList += '<div class="alert alert-info mt-2 mb-2"><small>';
          filesList += '<i class="fa fa-info-circle"></i> ';
          filesList += 'Grande quantidade de arquivos (' + audioFiles.length + '). ';
          filesList += 'O processamento pode levar alguns minutos, mas será feito sem limites de timeout.';
          filesList += '</small></div>';
        }

        // Mostrar apenas primeiros 10 arquivos se houver muitos
        var filesToShow = audioFiles.length > 10 ? audioFiles.slice(0, 10) : audioFiles;
        filesToShow.forEach(function (file) {
          var size = getFileSize(file);
          filesList += '<small class="d-block text-info">• ' + file + (size ? ' (' + size + ')' : '') + '</small>';
        });

        if (audioFiles.length > 10) {
          filesList += '<small class="text-default">... e mais ' + (audioFiles.length - 10) + ' arquivos</small>';
        }

        $('#selectedAudioList').html(filesList);

        // Mostrar modal
        var modal = new bootstrap.Modal(document.getElementById('convertAudioModal'));
        modal.show();
      });

      // Handler para mostrar/ocultar bitrate personalizado
      $('#audioQuality').on('change', function () {
        if ($(this).val() === 'custom') {
          $('#customBitrateRow').show();
        } else {
          $('#customBitrateRow').hide();
        }
      });

      // Handler para iniciar conversão de áudio
      $('#startAudioConversionBtn').on('click', function () {
        var selectedFiles = $('.select-item:checked').map(function () {
          return $(this).attr('data-file');
        }).get();

        // Filtrar apenas arquivos de áudio
        var audioExts = ['mp3', 'wav', 'ogg', 'aac', 'm4a', 'flac', 'wma', 'mp4', 'avi', 'mkv', 'mov'];
        var audioFiles = selectedFiles.filter(function (file) {
          var ext = file.split('.').pop().toLowerCase();
          return audioExts.includes(ext);
        });

        if (!audioFiles.length) {
          alert('Nenhum arquivo de áudio selecionado.');
          return;
        }

        // Coletar configurações
        var settings = {
          do: 'convertaudio',
          files: audioFiles,
          format: $('#audioOutputFormat').val(),
          quality: $('#audioQuality').val(),
          bitrate: $('#audioBitrate').val(),
          preserve_original: $('#audioPreserveOriginal').prop('checked') ? '1' : '0',
          add_suffix: $('#audioAddSuffix').prop('checked') ? '1' : '0',
          xsrf: XSRF
        };

        // Mostrar progresso
        $('#audioConversionProgress').show();
        $('#audioConversionResults').hide();
        $('#startAudioConversionBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Convertendo...');

        // Simular progresso durante a conversão
        var progress = 0;
        var progressInterval = setInterval(function () {
          progress += Math.random() * 10;
          if (progress > 85) progress = 85;
          $('#audioConversionProgressBar').css('width', progress + '%').text(Math.round(progress) + '%');
        }, 300);

        // Iniciar conversão
        $.post('?', settings, function (response) {
          clearInterval(progressInterval);
          $('#audioConversionProgressBar').css('width', '100%').text('100%');
          $('#audioConversionStatus').text('Conversão finalizada!');

          if (response && response.success) {
            var resultsHtml = '<div class="alert alert-success">';
            resultsHtml += '<strong>🎉 Conversão de áudio concluída!</strong><br>';
            resultsHtml += '<div class="row mt-2">';
            resultsHtml += '<div class="col-md-6">';
            resultsHtml += '📊 Total de arquivos processados: <strong>' + response.total + '</strong><br>';
            resultsHtml += '✅ Arquivos convertidos com sucesso: <strong>' + response.converted + '</strong><br>';
            resultsHtml += '</div>';
            resultsHtml += '<div class="col-md-6">';
            if (response.timing) {
              resultsHtml += '⏱️ Tempo total: <strong>' + response.timing.total_time_formatted + '</strong><br>';
              resultsHtml += '📈 Tempo médio por arquivo: <strong>' + response.timing.average_per_file_formatted + '</strong><br>';
              if (response.timing.files_per_second > 0) {
                resultsHtml += '🚀 Velocidade: <strong>' + response.timing.files_per_second + ' arquivos/s</strong>';
              }
            }
            resultsHtml += '</div>';
            resultsHtml += '</div>';
            resultsHtml += '</div>';

            // Mostrar detalhes de cada arquivo
            if (response.results && response.results.length > 0) {
              resultsHtml += '<div class="mt-3">';
              response.results.forEach(function (result) {
                var status = result.success ? 'success' : 'danger';
                var icon = result.success ? 'check-circle' : 'times-circle';
                resultsHtml += '<div class="alert alert-' + status + ' py-2 mb-2">';
                resultsHtml += '<i class="fa fa-' + icon + '"></i> <strong>' + result.file + '</strong><br>';

                if (result.success) {
                  resultsHtml += '<small>';
                  resultsHtml += '📁 Arquivo de saída: ' + result.output_file + '<br>';
                  if (result.bitrate) resultsHtml += '🎵 Bitrate: ' + result.bitrate + ' kbps • ';
                  if (result.compression_ratio) resultsHtml += '📉 Redução: ' + result.compression_ratio + '% • ';
                  if (result.conversion_time_formatted) resultsHtml += '⏱️ Tempo: ' + result.conversion_time_formatted;
                  resultsHtml += '</small>';
                } else {
                  resultsHtml += '<small class="text-danger">';
                  resultsHtml += '❌ Erro: ' + (result.error || 'Erro desconhecido');
                  if (result.conversion_time_formatted) resultsHtml += ' (Tempo decorrido: ' + result.conversion_time_formatted + ')';
                  resultsHtml += '</small>';
                }
                resultsHtml += '</div>';
              });
              resultsHtml += '</div>';
            }

            // Adicionar estatísticas detalhadas de timing se disponíveis
            if (response.timing && response.converted > 1) {
              resultsHtml += '<div class="mt-3">';
              resultsHtml += '<h6><i class="fa fa-clock-o"></i> Estatísticas de Performance</h6>';
              resultsHtml += '<div class="row">';
              resultsHtml += '<div class="col-md-4">';
              resultsHtml += '<div class="card bg-light p-2">';
              resultsHtml += '<small><strong>⚡ Arquivo mais rápido</strong><br>' + response.timing.fastest_file_formatted + '</small>';
              resultsHtml += '</div>';
              resultsHtml += '</div>';
              resultsHtml += '<div class="col-md-4">';
              resultsHtml += '<div class="card bg-light p-2">';
              resultsHtml += '<small><strong>🐌 Arquivo mais lento</strong><br>' + response.timing.slowest_file_formatted + '</small>';
              resultsHtml += '</div>';
              resultsHtml += '</div>';
              resultsHtml += '<div class="col-md-4">';
              resultsHtml += '<div class="card bg-light p-2">';
              resultsHtml += '<small><strong>📊 Velocidade média</strong><br>' + response.timing.files_per_second + ' arquivos/segundo</small>';
              resultsHtml += '</div>';
              resultsHtml += '</div>';
              resultsHtml += '</div>';
              resultsHtml += '</div>';
            }

            $('#audioResultsContainer').html(resultsHtml);
            $('#audioConversionResults').show();

          } else {
            var errorMsg = response && response.error ? response.error.msg : 'Erro desconhecido';
            $('#audioResultsContainer').html('<div class="alert alert-danger">Erro na conversão: ' + errorMsg + '</div>');
            $('#audioConversionResults').show();
          }
        }, 'json').fail(function (xhr) {
          clearInterval(progressInterval);
          var errorMsg = 'Falha na requisição';
          try {
            var data = JSON.parse(xhr.responseText);
            if (data && data.error && data.error.msg) {
              errorMsg = data.error.msg;
            }
          } catch (e) { }

          $('#audioResultsContainer').html('<div class="alert alert-danger">Erro: ' + errorMsg + '</div>');
          $('#audioConversionResults').show();
        }).always(function () {
          $('#startAudioConversionBtn').prop('disabled', false).html('<i class="fa fa-cog"></i> Iniciar Conversão');
        });
      });

      // ADDED: Handler para compressão de arquivos
      $('#compressFilesBtn').on('click', function (e) {
        e.preventDefault();
        var selectedFiles = $('.select-item:checked').map(function () {
          return $(this).attr('data-file');
        }).get();

        if (!selectedFiles.length) {
          alert('Nenhum arquivo selecionado.');
          return;
        }

        // Preencher lista de arquivos selecionados
        var filesList = '<strong>Arquivos selecionados (' + selectedFiles.length + '):</strong><br>';
        selectedFiles.forEach(function (file) {
          var size = getFileSize(file);
          filesList += '<small class="d-block text-info">• ' + file + (size ? ' (' + size + ')' : '') + '</small>';
        });
        $('#selectedCompressionList').html(filesList);

        // Mostrar modal
        var modal = new bootstrap.Modal(document.getElementById('compressFilesModal'));
        modal.show();
      });

      // Handler do slider de nível de compressão
      $('#compressionLevel').on('input', function () {
        $('#compressionLevelValue').text($(this).val());
      });

      // Handler para iniciar compressão
      $('#startCompressionBtn').on('click', function () {
        var selectedFiles = $('.select-item:checked').map(function () {
          return $(this).attr('data-file');
        }).get();

        if (!selectedFiles.length) {
          alert('Nenhum arquivo selecionado.');
          return;
        }

        // Coletar configurações
        var settings = {
          do: 'compressfiles',
          files: selectedFiles,
          compression_type: $('#compressionType').val(),
          compression_level: $('#compressionLevel').val(),
          archive_name: $('#archiveName').val(),
          preserve_paths: $('#preservePaths').prop('checked') ? '1' : '0',
          folder: window.piuClient.folder,
          xsrf: XSRF
        };

        // Mostrar progresso
        $('#compressionProgress').show();
        $('#compressionResults').hide();
        $('#startCompressionBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Comprimindo...');

        // Simular progresso durante a compressão
        var progress = 0;
        var progressInterval = setInterval(function () {
          progress += Math.random() * 8;
          if (progress > 90) progress = 90;
          $('#compressionProgressBar').css('width', progress + '%').text(Math.round(progress) + '%');
        }, 250);

        // Iniciar compressão
        $.post('?', settings, function (response) {
          clearInterval(progressInterval);
          $('#compressionProgressBar').css('width', '100%').text('100%');
          $('#compressionStatus').text('Compressão finalizada!');

          if (response && response.success) {
            var resultsHtml = '<div class="alert alert-success">';
            resultsHtml += '<strong>Compressão concluída!</strong><br>';
            resultsHtml += 'Arquivo criado: <strong>' + response.archive + '</strong><br>';
            resultsHtml += 'Arquivos adicionados: ' + response.files_added + '<br>';
            if (response.archive_size) {
              resultsHtml += 'Tamanho do arquivo: ' + formatBytes(response.archive_size);
            }
            resultsHtml += '</div>';

            // Botão para baixar o arquivo comprimido
            resultsHtml += '<div class="mt-3 text-center">';
            resultsHtml += '<a href="?do=download&file=' + encodeURIComponent(response.archive) + '" class="btn btn-success">';
            resultsHtml += '<i class="fa fa-download"></i> Baixar Arquivo Comprimido';
            resultsHtml += '</a>';
            resultsHtml += '</div>';

            $('#compressionResultsContainer').html(resultsHtml);
            $('#compressionResults').show();

            // Atualizar lista de arquivos após um tempo
            setTimeout(function () {
              list();
            }, 1000);

          } else {
            var errorMsg = response && response.error ? response.error.msg : 'Erro desconhecido';
            $('#compressionResultsContainer').html('<div class="alert alert-danger">Erro na compressão: ' + errorMsg + '</div>');
            $('#compressionResults').show();
          }
        }, 'json').fail(function (xhr) {
          clearInterval(progressInterval);
          var errorMsg = 'Falha na requisição';
          try {
            var data = JSON.parse(xhr.responseText);
            if (data && data.error && data.error.msg) {
              errorMsg = data.error.msg;
            }
          } catch (e) { }

          $('#compressionResultsContainer').html('<div class="alert alert-danger">Erro: ' + errorMsg + '</div>');
          $('#compressionResults').show();
        }).always(function () {
          $('#startCompressionBtn').prop('disabled', false).html('<i class="fa fa-compress"></i> Iniciar Compressão');
        });
      });

      // Executar mover/copy
      $('#confirmMoveCopyBtn').on('click', function () {
        var filesToOperate = $('.select-item:checked').map(function () { return $(this).attr('data-file'); }).get();
        if (!filesToOperate.length) return alert('Nenhum arquivo/pasta selecionado.');
        var dest = $('#moveCopyDest').val();
        if (!dest || dest.indexOf('..') !== -1) return alert('Destino inválido.');
        var action = $('input[name=movecopy_action]:checked').val();
        var payload = { do: 'movecopy', files: filesToOperate, dest: dest, action: action, xsrf: (document.cookie.match('(^|; )_sfm_xsrf=([^;]*)') || 0)[2] };
        $('#confirmMoveCopyBtn').prop('disabled', true).text('Executando...');
        $.post('?', payload, function (res) {
          $('#confirmMoveCopyBtn').prop('disabled', false).text('Executar');
          if (res && res.success) {
            var out = [];
            Object.keys(res.results || {}).forEach(function (k) {
              var r = res.results[k];
              out.push('<div>' + k + ': ' + (r.success ? '<span style="color:green">OK</span>' : '<span style="color:red">' + (r.msg || 'Erro') + '</span>') + '</div>');
            });
            $('#moveCopyResult').html(out.join('')).show();
            // Só atualiza a página se pelo menos um item foi bem-sucedido
            var anySuccess = Object.values(res.results || {}).some(function (r) { return r.success; });
            if (anySuccess) {
              setTimeout(function () {
                var moveCopyModal = bootstrap.Modal.getInstance(document.getElementById('moveCopyModal'));
                if (moveCopyModal) moveCopyModal.hide();
                // Manter seleções ativas após operação
                // $('#selectAll').prop('checked', false);
                // $('.select-item').prop('checked', false);
                if (typeof updateDeleteSelectedBtn === 'function') {
                  updateDeleteSelectedBtn();
                }
                location.reload(); // Atualiza a página após a operação
              }, 1000);
            }
          } else {
            alert('Erro ao executar operação: ' + (res && res.error ? res.error.msg : 'unknown'));
            $('#moveCopyResult').hide();
          }
        }, 'json').fail(function (xhr) {
          $('#confirmMoveCopyBtn').prop('disabled', false).text('Executar');
          var msg = 'Falha na requisição.';
          try { var json = JSON.parse(xhr.responseText); if (json && json.error && json.error.msg) msg = json.error.msg; } catch (e) { }
          alert('Erro: ' + msg);
        });
      });

      // === SISTEMA DE MODAIS AVANÇADOS ===

      // Inicializar modais avançados
      window.AdvancedModalSystem = {

        // Criar taskbar se não existir
        createTaskbar: function () {
          if (!document.getElementById('modal-taskbar')) {
            const taskbar = document.createElement('div');
            taskbar.id = 'modal-taskbar';
            taskbar.className = 'modal-taskbar';
            taskbar.style.display = 'none';
            document.body.appendChild(taskbar);
          }
        },

        // Adicionar controles ao modal
        addControls: function (modal) {
          const modalId = modal.getAttribute('id');
          const header = modal.querySelector('.modal-header');

          if (header && !header.querySelector('.modal-controls')) {
            // Adicionar classe de arrastável
            modal.classList.add('modal-draggable');

            // Criar controles
            const controls = document.createElement('div');
            controls.className = 'modal-controls';
            controls.innerHTML = `
              <button type="button" class="modal-control-btn" onclick="AdvancedModalSystem.toggleFullscreen('${modalId}')" title="Tela Cheia">
                <i class="fa fa-expand"></i>
              </button>
              <button type="button" class="modal-control-btn" onclick="AdvancedModalSystem.minimizeModal('${modalId}')" title="Minimizar">
                <i class="fa fa-minus"></i>
              </button>
              <button type="button" class="modal-control-btn" data-bs-dismiss="modal" title="Fechar">
                <i class="fa fa-close"></i>
              </button>
            `;

            header.appendChild(controls);

            // Adicionar handle de redimensionamento
            const content = modal.querySelector('.modal-content');
            if (content && !content.querySelector('.resize-handle')) {
              content.classList.add('modal-resizable');
              const resizeHandle = document.createElement('div');
              resizeHandle.className = 'resize-handle';
              content.appendChild(resizeHandle);
              this.enableResize(modal, resizeHandle);
            }

            // Habilitar arrastar
            this.enableDrag(modal, header);
          }
        },

        // Habilitar arrastar modal
        enableDrag: function (modal, header) {
          let isDragging = false;
          let startX, startY, initialLeft, initialTop;

          header.addEventListener('mousedown', function (e) {
            if (e.target.closest('.modal-controls') || e.target.closest('.btn-close')) return;

            isDragging = true;
            modal.classList.add('dragging');

            const dialog = modal.querySelector('.modal-dialog');
            const rect = dialog.getBoundingClientRect();

            startX = e.clientX;
            startY = e.clientY;
            initialLeft = rect.left;
            initialTop = rect.top;

            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);

            e.preventDefault();
          });

          function onMouseMove(e) {
            if (!isDragging) return;

            const deltaX = e.clientX - startX;
            const deltaY = e.clientY - startY;

            const dialog = modal.querySelector('.modal-dialog');
            const newLeft = Math.max(0, Math.min(window.innerWidth - dialog.offsetWidth, initialLeft + deltaX));
            const newTop = Math.max(0, Math.min(window.innerHeight - dialog.offsetHeight, initialTop + deltaY));

            dialog.style.position = 'fixed';
            dialog.style.left = newLeft + 'px';
            dialog.style.top = newTop + 'px';
            dialog.style.margin = '0';
          }

          function onMouseUp() {
            isDragging = false;
            modal.classList.remove('dragging');
            document.removeEventListener('mousemove', onMouseMove);
            document.removeEventListener('mouseup', onMouseUp);
          }
        },

        // Habilitar redimensionamento
        enableResize: function (modal, handle) {
          let isResizing = false;
          let startX, startY, startWidth, startHeight;

          handle.addEventListener('mousedown', function (e) {
            isResizing = true;

            const dialog = modal.querySelector('.modal-dialog');
            const rect = dialog.getBoundingClientRect();

            startX = e.clientX;
            startY = e.clientY;
            startWidth = rect.width;
            startHeight = rect.height;

            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);

            e.preventDefault();
            e.stopPropagation();
          });

          function onMouseMove(e) {
            if (!isResizing) return;

            const deltaX = e.clientX - startX;
            const deltaY = e.clientY - startY;

            const dialog = modal.querySelector('.modal-dialog');
            const newWidth = Math.max(300, startWidth + deltaX);
            const newHeight = Math.max(200, startHeight + deltaY);

            dialog.style.width = newWidth + 'px';
            dialog.style.height = newHeight + 'px';
            dialog.style.maxWidth = 'none';
          }

          function onMouseUp() {
            isResizing = false;
            document.removeEventListener('mousemove', onMouseMove);
            document.removeEventListener('mouseup', onMouseUp);
          }
        },

        // Alternar modo flutuante
        toggleFloating: function (modalId) {
          const modal = document.getElementById(modalId);
          const backdrop = document.querySelector('.modal-backdrop');

          if (modal.classList.contains('modal-floating')) {
            // Voltar ao modo normal
            modal.classList.remove('modal-floating');
            if (backdrop) backdrop.style.display = 'block';
            this.resetModalPosition(modal);
            this.updateControlButton(modalId, 'floating', false);
          } else {
            // Ativar modo flutuante
            modal.classList.add('modal-floating');
            if (backdrop) backdrop.style.display = 'none';
            this.updateControlButton(modalId, 'floating', true);
          }
        },

        // Alternar tela cheia
        toggleFullscreen: function (modalId) {
          const modal = document.getElementById(modalId);

          if (modal.classList.contains('modal-fullscreen')) {
            // Sair da tela cheia
            modal.classList.remove('modal-fullscreen');
            this.resetModalPosition(modal);
            this.updateControlButton(modalId, 'fullscreen', false);
          } else {
            // Entrar em tela cheia
            modal.classList.add('modal-fullscreen');
            modal.classList.remove('modal-floating');
            this.updateControlButton(modalId, 'fullscreen', true);
            this.updateControlButton(modalId, 'floating', false);
          }
        },

        // Minimizar modal
        minimizeModal: function (modalId) {
          const modal = document.getElementById(modalId);
          const title = modal.querySelector('.modal-title').textContent;

          modal.classList.add('modal-minimized');
          this.createTaskbar();
          this.addToTaskbar(modalId, title);
        },

        // Adicionar à taskbar
        addToTaskbar: function (modalId, title) {
          const taskbar = document.getElementById('modal-taskbar');
          taskbar.style.display = 'flex';

          if (!taskbar.querySelector(`[data-modal="${modalId}"]`)) {
            const item = document.createElement('button');
            item.className = 'modal-taskbar-item';
            item.setAttribute('data-modal', modalId);
            item.textContent = title;
            item.onclick = () => this.restoreModal(modalId);
            taskbar.appendChild(item);
          }
        },

        // Restaurar modal da taskbar
        restoreModal: function (modalId) {
          const modal = document.getElementById(modalId);
          const taskbar = document.getElementById('modal-taskbar');
          const item = taskbar.querySelector(`[data-modal="${modalId}"]`);

          modal.classList.remove('modal-minimized');

          if (item) {
            item.remove();

            // Esconder taskbar se vazia
            if (taskbar.children.length === 0) {
              taskbar.style.display = 'none';
            }
          }
        },

        // Resetar posição do modal
        resetModalPosition: function (modal) {
          const dialog = modal.querySelector('.modal-dialog');
          dialog.style.position = '';
          dialog.style.left = '';
          dialog.style.top = '';
          dialog.style.width = '';
          dialog.style.height = '';
          dialog.style.margin = '';
          dialog.style.maxWidth = '';
        },

        // Atualizar estado do botão de controle
        updateControlButton: function (modalId, type, active) {
          const modal = document.getElementById(modalId);
          const button = modal.querySelector(`.modal-control-btn[onclick*="${type}"]`);

          if (button) {
            if (active) {
              button.classList.add('active');
            } else {
              button.classList.remove('active');
            }
          }
        },

        // Inicializar sistema
        init: function () {
          // Observar novos modais
          const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
              mutation.addedNodes.forEach((node) => {
                if (node.nodeType === 1 && node.classList.contains('modal')) {
                  this.addControls(node);
                }
              });
            });
          });

          observer.observe(document.body, { childList: true, subtree: true });

          // Inicializar modais existentes
          document.querySelectorAll('.modal').forEach(modal => {
            this.addControls(modal);
          });

          // Atalhos de teclado
          document.addEventListener('keydown', (e) => {
            if (e.altKey && e.key === 'Enter') {
              // Alt + Enter = Fullscreen no modal ativo
              const activeModal = document.querySelector('.modal.show');
              if (activeModal) {
                this.toggleFullscreen(activeModal.id);
              }
            }

            if (e.ctrlKey && e.key === 'm') {
              // Ctrl + M = Minimizar modal ativo
              const activeModal = document.querySelector('.modal.show');
              if (activeModal) {
                this.minimizeModal(activeModal.id);
                e.preventDefault();
              }
            }
          });
        }
      };

      // Inicializar sistema quando documento estiver pronto
      AdvancedModalSystem.init();

      // Mostrar/esconder dicas de atalhos
      let shortcutsTimeout;
      document.addEventListener('keydown', function (e) {
        if (e.altKey || e.ctrlKey) {
          clearTimeout(shortcutsTimeout);
          showShortcutsHint();
          shortcutsTimeout = setTimeout(hideShortcutsHint, 3000);
        }
      });

      function showShortcutsHint() {
        let hint = document.getElementById('modal-shortcuts-hint');
        if (!hint) {
          hint = document.createElement('div');
          hint.id = 'modal-shortcuts-hint';
          hint.className = 'modal-shortcuts-hint';
          hint.innerHTML = `
            <div class="shortcut"><span class="key">Alt</span> + <span class="key">Enter</span> = Tela Cheia</div>
            <div class="shortcut"><span class="key">Ctrl</span> + <span class="key">M</span> = Minimizar</div>
            <div class="shortcut">Arrastar = Mover Modal</div>
            <div class="shortcut">Canto inferior direito = Redimensionar</div>
          `;
          document.body.appendChild(hint);
        }
        hint.classList.add('show');
      }

      function hideShortcutsHint() {
        const hint = document.getElementById('modal-shortcuts-hint');
        if (hint) {
          hint.classList.remove('show');
        }
      }

    });
  