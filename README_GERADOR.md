# Gerador de catálogo PIU Songs

Entre no gerenciador e abra `regenerate_songs.html`.

1. Clique em **Analisar biblioteca** para escanear os arquivos sem publicar.
2. Revise as inclusões, remoções e ocorrências. Use os filtros para localizar músicas por canal, modo e nível.
3. Clique em **Publicar catálogo** para atualizar `songs.json`.
4. Abra **Gerar índices de dificuldade** e envie o formulário para atualizar `difficulty-zone/`.

Uma alteração na biblioteca depois da prévia exige nova análise. Os problemas encontrados ficam no relatório e não removem músicas automaticamente. A verificação de SSC cobre estrutura e referências; não substitui o parser de notas do jogo.

Pelo terminal:

```powershell
php generate_songs_json.php --preview
php generate_songs_json.php
php generate_difficulty_zone.php
```

Use `--force` no gerador do catálogo para ignorar o cache de metadados. `gerar_catalogo.bat` continua executando catálogo e dificuldades em sequência.

Os dois pontos de entrada usam o mesmo escaneamento. Os `index.php` das categorias não são executados nem regenerados. Caminhos preservam os nomes originais; clientes devem codificar cada segmento ao construir URLs.

Formatos catalogados: MP3, WAV, OGG, SSC, SM, WEBP, PNG, JPG, JPEG, GIF, AVIF e BMP. Arquivos devem ficar diretamente na pasta da música. Os metadados dos filtros e dificuldades são extraídos de SSC.

Execute o servidor local com proteção dos diretórios internos:

```powershell
php -S localhost:8000 router.php
```

Consulte [README.md](README.md) para requisitos, canais Phoenix 2, segurança do servidor, API, cache, testes e integração com o PIU Web.
