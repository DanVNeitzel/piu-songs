# PIU Songs

Biblioteca de músicas e ferramentas PHP para organizar arquivos do Pump It Up e gerar os catálogos consumidos pelo PIU Web. O projeto reúne um gerenciador de arquivos, a geração de `songs.json` e índices de dificuldade para charts Single e Double.

## Recursos

- Gerenciamento de arquivos pelo navegador, com upload, download, criação de pastas e compartilhamento.
- Catalogação de músicas, áudios, stepfiles e imagens em `Songs/`.
- Geração de índices por modo e nível em `difficulty-zone/`.
- Ferramentas de diagnóstico para JSON, arquivos SSC e nomes com caracteres especiais.
- Conversão de áudio pelo gerenciador, usando FFmpeg.

## Requisitos

- PHP com suporte a JSON. Recomenda-se PHP 8 ou superior para executar o projeto.
- Servidor com suporte a PHP, como Apache, Nginx com PHP ou o servidor embutido do PHP para uso local.
- Permissão de leitura em `Songs/` e de escrita nos arquivos e diretórios gerados.
- Extensão `zip` para operações que utilizam `ZipArchive`.
- Extensão `mbstring` para ferramentas como `validate_ssc_files.php`.
- Extensão `curl` caso sejam utilizadas as integrações de nuvem do gerenciador.
- FFmpeg disponível na pasta do projeto ou no `PATH` para conversão de áudio, com execução de comandos habilitada no PHP.

Os scripts principais são executados diretamente pelo PHP, sem etapa de compilação ou instalação via npm/Composer.

## Execução local

Na raiz do projeto, execute:

```powershell
php -S localhost:8000
```

Acesse [http://localhost:8000/](http://localhost:8000/) para abrir o gerenciador. A senha de acesso é configurada no campo `SENHA` de `files_config.json`.

Se o projeto estiver publicado como uma subpasta do servidor, utilize a URL correspondente, por exemplo `http://localhost/piu-songs/`. Os caminhos abaixo são relativos à raiz do projeto.

## Organização das músicas

```text
piu-songs/
├── index.php
├── files_config.json
├── base_songs.json
├── songs.json
├── generate_songs_json.php
├── generate_difficulty_zone.php
├── regenerate_songs.html
├── api_generate_songs.php
├── share.php
├── difficulty-zone/
│   ├── manifest.json
│   ├── single/
│   └── double/
└── Songs/
    └── Categoria/
        ├── banner.webp
        └── Nome da música/
            ├── music.mp3
            ├── chart.ssc
            └── banner.webp
```

Cada categoria contém as pastas das músicas. Coloque os arquivos diretamente na pasta de cada música: o gerador direto não percorre subpastas dentro dela.

Formatos incluídos pelo gerador direto:

| Tipo | Extensões |
| --- | --- |
| Áudio | `.mp3`, `.wav`, `.ogg` |
| Stepfiles | `.ssc`, `.sm` |
| Imagens | `.webp`, `.png`, `.jpg`, `.jpeg`, `.gif`, `.avif`, `.bmp` |

O gerador procura uma imagem na raiz da categoria para preencher seu campo `banner`. Os caminhos registrados no catálogo são relativos, com prefixo `./Songs/`, e preservam os nomes dos arquivos.

## Gerar o catálogo

### Canais Phoenix 2

`channels.json` define a ordem, os banners e as regras dos 26 canais da
[referência em vídeo](https://www.youtube.com/watch?v=LBN5_s6151o).
As pastas `08-1ST~ZERO` e `16-FIESTA~FIESTA2` reúnem, respectivamente,
1st até Zero e Fiesta até Fiesta 2. Prime, Prime 2, XX, Phoenix e Phoenix 2
ficam separados; NX até NXA possui sua própria pasta.

Cada canal tem `banner.png`. Os canais de gênero, formato, dificuldade,
Arcade Mix, Series Zone, Co-op e UCS referenciam os caminhos originais das
músicas no catálogo, sem copiar os arquivos. `SONGCATEGORY` (ou `GENRE`
quando necessário), `SONGTYPE` e `STEPSTYPE` determinam esses filtros.
World Music também inclui a categoria J-Music. UCS inclui músicas com
charts explicitamente identificados como UCS em `DESCRIPTION` ou `CHARTNAME`.
Os canais de dificuldade filtram músicas pelo tipo do chart; a seleção
por nível continua disponível em `difficulty-zone/`.

New Tunes, Rise Volume 1, Rise Mix, Xross e Online Matching ficam vazios
até receberem conteúdo: os SSC atuais não identificam essas associações.
Os diretórios com apenas banner também aparecem no catálogo. A flag
`virtual` indica canais de filtro, que o gerador de dificuldades ignora
para não contar as mesmas músicas novamente. Essa organização do catálogo
não implementa partidas online nem as regras de jogo de Rise Mix ou Xross.

Para reaplicar a organização em uma cópia antiga da biblioteca, visualize:

```powershell
python organize_channels.py --artwork CAMINHO_DAS_IMAGENS_DOS_CANAIS
```

Acrescente `--apply` para mover as pastas e copiar os banners. O script
verifica conflitos antes das movimentações e preserva um banner anterior
como `banner_legacy.png`. Depois gere o catálogo e os índices de dificuldade.
Ambos os geradores de catálogo aplicam as regras de `channel_catalog.php`.

1. Adicione ou atualize os arquivos em `Songs/Categoria/Nome da música/`.
2. Mantenha `base_songs.json` na raiz: ele fornece a estrutura inicial do catálogo.
3. Abra [generate_songs_json.php](http://localhost:8000/generate_songs_json.php) no navegador.
4. Confira as estatísticas exibidas e o arquivo `songs.json` atualizado.

Para exibir os detalhes do escaneamento, utilize:

```text
http://localhost:8000/generate_songs_json.php?debug=1
```

Esse fluxo escaneia os arquivos diretamente e dispensa os `index.php` das categorias. A execução sobrescreve o `songs.json` existente.

### Interface alternativa

`regenerate_songs.html` oferece uma interface que chama `api_generate_songs.php`. Esse fluxo executa os `index.php` de cada categoria e cria os que estiverem ausentes. A opção de regeneração forçada (`?force=true` na API) sobrescreve esses índices.

Os dois fluxos usam implementações diferentes. Para catalogar todos os formatos da tabela acima e lidar com nomes especiais pelo escaneamento direto, utilize `generate_songs_json.php`.

## Gerar índices de dificuldade

Depois de atualizar `songs.json`, execute na raiz:

```powershell
php generate_difficulty_zone.php
```

Para acompanhar os detalhes:

```powershell
php generate_difficulty_zone.php --debug
```

Também é possível acessar `generate_difficulty_zone.php` pelo navegador, usando `?debug=1` para diagnóstico.

O script lê o catálogo e os arquivos `.ssc`, agrupa charts Single e Double por nível e evita entradas duplicadas da mesma música no mesmo modo e nível. Ele grava:

- `difficulty-zone/single/1.json` até `26.json` e `27-plus.json`.
- `difficulty-zone/double/1.json` até `26.json` e `27-plus.json`.
- `difficulty-zone/manifest.json`, com informações da geração.

O arquivo `27-plus.json` reúne os níveis a partir de 27. Esse índice é extraído dos arquivos `.ssc`; arquivos `.sm` incluídos no catálogo não são processados por esse gerador.

## Integração com o PIU Web

Disponibilize `songs.json` ao aplicativo consumidor e mantenha a estrutura `Songs/` acessível conforme os caminhos do catálogo. Na instalação descrita no [guia do gerador](README_GERADOR.md), o catálogo é copiado para `piuweb_phoenix/piured-engine/songs.json`.

Se o consumidor utilizar a seleção por dificuldade, disponibilize também `difficulty-zone/` e seu manifesto. Após atualizar os arquivos, recarregue o aplicativo e, se necessário, limpe o cache do navegador.

## Configuração do gerenciador

O arquivo `files_config.json` controla as opções do `index.php`:

| Campo | Função |
| --- | --- |
| `SENHA` | Senha de acesso ao gerenciador |
| `allow_upload`, `allow_delete` | Permissões de envio e exclusão |
| `allow_create_folder`, `allow_create_file` | Criação de pastas e arquivos |
| `allow_direct_link`, `allow_show_folders` | Links diretos e exibição de pastas |
| `max_upload_size_mb` | Limite de upload definido pelo gerenciador |
| `disallowed_patterns`, `hidden_patterns` | Padrões de arquivos bloqueados e ocultos |
| `permissionAdmin` | Configuração de administração |

O limite de upload também depende de `upload_max_filesize` e `post_max_size` no PHP e dos limites do servidor HTTP.

## Diagnóstico

| Ferramenta | Finalidade |
| --- | --- |
| `debug_scan.php` | Investigar o escaneamento de arquivos |
| `test_special_chars.php` | Verificar nomes com caracteres especiais |
| `validate_json.php` | Validar os JSONs de diagnóstico definidos no próprio script (`debug_*.json`) |
| `validate_ssc_files.php` | Analisar arquivos SSC |
| `find_corrupted_ssc.php` | Procurar indícios de corrupção em SSC |
| `analyze_corrupted_ssc.php` | Investigar arquivos SSC problemáticos |
| `check_php_limits.php` | Consultar limites do ambiente PHP |

Se uma música não aparecer, confira a estrutura das pastas, as extensões aceitas e as permissões de leitura; depois gere o catálogo com `?debug=1`. Para falhas de leitura de charts, consulte também [ERRO_SSC_CORROMPIDO.md](ERRO_SSC_CORROMPIDO.md).

O gerador direto do catálogo e o gerador de dificuldades configuram até 300 segundos de execução e 512 MB de memória. Bibliotecas maiores podem exigir ajustes nesses valores e nos limites do servidor.
