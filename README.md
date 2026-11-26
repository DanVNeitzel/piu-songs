# PIU Songs

Biblioteca de músicas e ferramentas PHP para organizar arquivos do Pump It Up e gerar os catálogos consumidos pelo PIU Web. O projeto reúne um gerenciador de arquivos, a geração de `songs.json` e índices de dificuldade para charts Single e Double.

## Acessar em HTML no GitHub Pages

`index.html` é a entrada pública da biblioteca: pesquisa, filtros por canal/modo/nível, áudio e download do relatório funcionam sem PHP no navegador. O gerenciador com upload, exclusão, conversão e publicação permanece em `index.php` e requer servidor PHP. O Pages não executa PHP ([documentação do GitHub](https://docs.github.com/en/pages/getting-started-with-github-pages/creating-a-github-pages-site)).

Para publicar:

1. Envie as alterações para a branch `main` do repositório.
2. No GitHub, abra **Settings → Pages → Build and deployment → Source** e selecione **GitHub Actions**.
3. Em **Actions**, acompanhe **Publicar biblioteca HTML**. Se o push ocorreu antes de ativar o Pages, execute **Run workflow**.
4. Abra a URL exibida pelo deploy, normalmente `https://USUARIO.github.io/REPOSITORIO/`.

O workflow `.github/workflows/pages.yml` executa os testes, gera metadados pelo mesmo serviço do gerador PHP e publica somente `_site/`. A página antiga `regenerate_songs.html` também abre a biblioteca pública no artefato. Não são publicados PHP, configurações, credenciais, backups ou diretórios de trabalho. O acesso público é de consulta; o site não solicita senha e não grava alterações no repositório.

A biblioteca versionada contém cerca de 2,2 GB de mídia, acima do [limite de 1 GB do site publicado no Pages](https://docs.github.com/en/pages/getting-started-with-github-pages/github-pages-limits). Por isso o workflow mantém os arquivos no repositório e aponta a reprodução para `raw.githubusercontent.com/USUARIO/REPOSITORIO/COMMIT/Songs/...`. O commit é fixado para que áudio e metadados correspondam à mesma versão. Os arquivos precisam estar acessíveis publicamente e realmente versionados no GitHub; mídias locais ignoradas, ponteiros Git LFS ou arquivos de um repositório privado não podem ser reproduzidos por visitantes anônimos dessa forma. Para usar outro servidor público de mídia, altere `PIU_MEDIA_BASE_URL` no workflow.

Para exportar localmente sem alterar `songs.json` nem os índices atuais:

```powershell
php export_static.php --media-base=https://raw.githubusercontent.com/USUARIO/REPOSITORIO/main/
python tests/static_http_test.py
python -m http.server 8000 --directory _site
```

Abra `http://localhost:8000/`. A base de mídia deve ser a pasta pública que contém `Songs/`, sem senha ou parâmetros de autenticação. `_site/songs.json` contém URLs absolutas das mídias; o `songs.json` original permanece com seus caminhos relativos. A saída usa uma lista explícita de arquivos permitidos e rejeita arquivos desconhecidos já existentes em `_site/` para não incluí-los em uma publicação posterior.

Também é possível servir o projeto original em HTTP e abrir `index.html`: sem `library-public.json`, a interface lê `songs.json` e os índices em `difficulty-zone/`, deduplica os canais virtuais e agrupa níveis altos em `27+`. Nesse modo de compatibilidade, nome/artista vêm do nome da pasta, só Single/Double possuem níveis indexados e o diagnóstico se limita aos arquivos catalogados. O build do Pages oferece os metadados completos extraídos dos SSC. Use um servidor HTTP para testar; abrir via `file://` bloqueia a leitura de JSON em navegadores comuns.

## Recursos

- Gerenciamento de arquivos pelo navegador, com upload, download, criação de pastas e compartilhamento.
- Catalogação de músicas, áudios, stepfiles e imagens em `Songs/`.
- Geração de índices por modo e nível em `difficulty-zone/`.
- Ferramentas de diagnóstico para JSON, arquivos SSC e nomes com caracteres especiais.
- Conversão de áudio pelo gerenciador, usando FFmpeg.

## Requisitos

- PHP 8.1 ou superior, com suporte a JSON, para geração e administração. A consulta pelo HTML publicado não precisa de PHP.
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
php -S localhost:8000 router.php
```

Acesse [http://localhost:8000/index.php](http://localhost:8000/index.php) para abrir o gerenciador ou [http://localhost:8000/index.html](http://localhost:8000/index.html) para consultar a biblioteca pública. A senha de acesso é configurada no campo `SENHA` de `files_config.json`. O router bloqueia configurações e diretórios internos no servidor local; não execute o servidor local sem ele.

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
3. Entre no gerenciador e abra [regenerate_songs.html](http://localhost:8000/regenerate_songs.html).
4. Clique em **Analisar biblioteca** e confira alterações e ocorrências.
5. Use **Publicar catálogo** para substituir `songs.json` depois de revisar a prévia.

Pelo terminal, publique diretamente ou gere apenas uma prévia:

```text
php generate_songs_json.php --preview
php generate_songs_json.php
php generate_songs_json.php --force
```

O terminal e a interface usam `app/catalog.php`, sem executar nem criar os `index.php` das categorias. `--force` ignora o cache dos metadados SSC. A prévia não altera o catálogo publicado. A publicação usa arquivo temporário, validação JSON e substituição do destino; falhas preservam o arquivo anterior. Um bloqueio compartilhado impede gerações simultâneas de catálogo e dificuldades.

### Interface alternativa

`regenerate_songs.html` chama a API autenticada. `GET api_generate_songs.php` fornece o token da sessão; operações usam POST e `X-CSRF-Token` ou `xsrf`. `action=preview` analisa; `action=publish` exige também o `fingerprint` retornado pela prévia. Uma biblioteca alterada desde a prévia exige nova análise (HTTP 409). O antigo GET com `?force=true` não publica nem sobrescreve índices.

A tela permite filtrar músicas por nome, artista, canal, modo, nível e ocorrências, ouvir áudio e baixar o relatório JSON. Os filtros de modo e nível precisam corresponder ao mesmo chart. A lista usa paginação e os áudios são carregados sob demanda.

O relatório identifica ausência de áudio/charts, referências inexistentes, possíveis duplicatas por título e artista, subpastas ignoradas e problemas estruturais de SSC. Não valida toda a sintaxe das notas do motor. Ocorrências são informativas: músicas não são removidas automaticamente. Subpastas usadas por efeitos também aparecem no relatório. Metadados de `.sm` não são extraídos.

O cache fica em `.runtime/metadata.json`, com invalidação por tamanho, data de modificação e data de alteração do arquivo. Use reanálise completa quando arquivos forem substituídos preservando esses atributos. A comparação de alterações considera inclusão, remoção e lista de arquivos; não é uma comparação binária dos áudios ou imagens.

## Gerar índices de dificuldade

Depois de atualizar `songs.json`, execute na raiz:

```powershell
php generate_difficulty_zone.php
```

Para acompanhar os detalhes:

```powershell
php generate_difficulty_zone.php --debug
```

Também é possível acessar `generate_difficulty_zone.php` pelo navegador após entrar no gerenciador. A página apresenta um formulário e só gera arquivos mediante POST com token válido. A geração reutiliza metadados SSC válidos em cache e grava cada JSON por substituição atômica, com manifesto por último. O conjunto dos arquivos de dificuldade não é uma transação única.

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

Se uma música não aparecer, confira a estrutura das pastas, as extensões aceitas e as permissões de leitura; depois analise a biblioteca na interface ou execute `php generate_songs_json.php --preview --force`. Para falhas de leitura de charts, consulte também [ERRO_SSC_CORROMPIDO.md](ERRO_SSC_CORROMPIDO.md).

O gerador de catálogo permite até 600 segundos; o de dificuldades, 300 segundos. Ambos configuram 512 MB de memória. Bibliotecas maiores podem exigir ajustes nesses valores e nos limites do servidor.

## Estrutura do código e verificação

- `app/security.php`: sessão, autenticação e validação de POST/CSRF.
- `app/catalog.php`, `app/charts.php`, `app/storage.php`: escaneamento, metadados, cache e gravação.
- `app/config.php`, `app/manager_login.php`, `app/cloud.php`, `app/filesystem.php`: componentes do gerenciador.
- `app/manager_view.php`, `assets/`: interface, estilos e JavaScript.

Execute os testes de catálogo, dificuldades, cache e falhas sem alterar a biblioteca real:

```powershell
php tests/catalog_test.php
node tests/catalog_ui_test.cjs
node tests/static_catalog_test.cjs
```

Com o servidor local iniciado, verifique autenticação, CSRF, leitura e prévia HTTP:

```powershell
python tests/http_test.py http://localhost:8000
```

## Configuração do servidor HTTP

O Apache deve permitir `.htaccess` (`AllowOverride` adequado e `mod_rewrite` ativo). O arquivo fornecido bloqueia acesso HTTP a `files_config.json`, `app/`, `tests/`, `.runtime/`, `.trash/` e `.shares/` e habilita compressão de JSON quando `mod_deflate` está disponível. Essas configurações não são aplicadas pelo PHP nem pelo Nginx.

No Nginx, adapte as regras ao prefixo da instalação, antes do handler PHP:

```nginx
location ~* ^/piu-songs/(?:app|tests|\.runtime|\.trash|\.shares)(?:/|$) { deny all; }
location ~* ^/piu-songs/(?:files_config|zip_progress)\.json$ { deny all; }
gzip on;
gzip_types application/json text/css application/javascript;
```

Use cache com revalidação (ETag/Last-Modified) para `songs.json` e `difficulty-zone/`, e cache privado/desabilitado para respostas autenticadas. Não marque o catálogo com cache imutável: ele é atualizado no mesmo endereço. Arquivos de configuração e diretórios internos não devem ser servidos publicamente. O servidor HTTP em uso deve ser verificado após aplicar essas regras.
