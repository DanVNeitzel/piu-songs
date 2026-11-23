# 🎵 Gerador de songs.json - Guia de Uso

## 📋 O que este script faz?

O `generate_songs_json.php` **escaneia diretamente** todas as pastas dentro de `Songs/`, encontra os arquivos de música (`.ssc`, `.mp3`, imagens) e gera um arquivo `songs.json` completo.

## ✅ Correções aplicadas (Caracteres Especiais)

O gerador foi **completamente reescrito** para lidar com caracteres especiais como `!@#$%¨&*(){}[]`:

### 🔧 Mudanças principais:

- ✅ **Escaneamento direto** - Não usa mais `index.php`, escaneia diretamente via PHP
- ✅ **scandir() nativo** - Mais confiável que `glob()` para caracteres especiais
- ✅ **rawurlencode()** - Todos os paths são URL-encoded corretamente
- ✅ **Caminhos absolutos** - Evita problemas com `__DIR__` e includes
- ✅ **Validação de arquivos** - `is_file()` garante que só arquivos são incluídos
- ✅ **Suporte completo** - MP3, WAV, OGG, SSC, SM, WEBP, PNG, JPG, JPEG, GIF, AVIF, BMP

## 🧪 Teste antes de gerar

### 1. Execute o teste de caracteres especiais:
```
http://localhost/piu-songs/test_special_chars.php
```

Este script vai verificar se músicas com caracteres especiais estão sendo encontradas corretamente.

## 🚀 Como usar

### 1. **(RECOMENDADO) Teste primeiro:**
```
http://localhost/piu-songs/debug_scan.php
```
Este script vai escanear **diretamente** as músicas com caracteres especiais e mostrar:
- ✅ Quais arquivos estão sendo encontrados
- ❌ Por que alguns arquivos estão sendo ignorados
- 📊 Quantos arquivos válidos existem em cada música

### 2. **Execute o gerador (MODO DEBUG):**
```
http://localhost/piu-songs/generate_songs_json.php?debug=1
```
O `?debug=1` mostra **todos os detalhes**:
- Cada música sendo escaneada
- Cada arquivo encontrado
- Por que músicas estão sendo puladas
- Totais de arquivos por categoria

### 3. **Se tudo estiver OK, gere a versão final:**
```
http://localhost/piu-songs/generate_songs_json.php
```

### 2. O script irá:
- ✅ Escanear todas as pastas em `Songs/`
- ✅ Gerar `index.php` automaticamente se não existir
- ✅ Processar cada pasta e seus arquivos
- ✅ Criar/atualizar o arquivo `songs.json`

### 3. Aguarde o processamento
O script pode demorar alguns minutos dependendo do número de músicas.

### 4. Verifique o resultado
Ao final, você verá:
- ✅ Total de pastas processadas
- ✅ Total de pastas puladas (se houver erros)
- ✅ Tamanho do arquivo gerado

## 🔧 Requisitos

1. **Arquivo `base_songs.json`** deve existir na mesma pasta
2. **Permissões de escrita** na pasta `piu-songs/`
3. **PHP 7.0+** instalado

## 📂 Estrutura esperada

```
piu-songs/
├── generate_songs_json.php
├── base_songs.json
├── songs.json (será gerado)
└── Songs/
    ├── 00-PIU XX/
    │   ├── index.php (será gerado se não existir)
    │   ├── (00) 1620 - Pory - (Percent X)/
    │   │   ├── 1620.mp3
    │   │   ├── 1620 - Pory - (Percent X).ssc
    │   │   └── banner.png
    │   └── ...
    ├── ALL/
    └── ...
```

## 🐛 Solução de problemas

### ❌ Erro: "Arquivo base_songs.json não encontrado"
**Solução:** Crie o arquivo `base_songs.json` com a estrutura base:
```json
{
  "children": [
    {
      "children": [],
      "name": "Songs",
      "path": "./Songs",
      "type": "folder"
    }
  ],
  "name": "root",
  "path": "./",
  "type": "folder"
}
```

### ❌ Músicas com caracteres especiais não aparecem
**Solução:** Execute o gerador novamente. A versão corrigida usa `rawurlencode()` nos paths.

### ❌ Erro de memória
**Solução:** O script já está configurado com `512M`. Se necessário, aumente em:
```php
ini_set('memory_limit', '1024M');
```

### ❌ Timeout
**Solução:** O script está configurado com 5 minutos. Aumente se necessário:
```php
set_time_limit(600); // 10 minutos
```

## � Problema: children[] vazio mesmo com arquivos

Se você está vendo `"children": []` no songs.json mas os arquivos existem na pasta:

### 🔍 Diagnóstico:

**1. Execute o debug_scan.php:**
```
http://localhost/piu-songs/debug_scan.php
```
Vai mostrar **exatamente** quais arquivos estão sendo encontrados.

**2. Execute o gerador com debug:**
```
http://localhost/piu-songs/generate_songs_json.php?debug=1
```
Vai mostrar o processo completo de escaneamento.

### 🔧 Possíveis causas:

❌ **Extensões incorretas** - Verifique se os arquivos têm extensões válidas:
- 🎵 Áudio: `.mp3`, `.wav`, `.ogg`
- 📋 Charts: `.ssc`, `.sm`  
- 🖼️ Imagens: `.webp`, `.png`, `.jpg`, `.jpeg`, `.gif`, `.avif`, `.bmp`

❌ **Arquivos em subpastas** - A estrutura deve ser:
```
Songs/XX/Nome da Música/
  ├── music.mp3       ✅ OK
  ├── chart.ssc       ✅ OK
  └── subfolder/      
      └── file.mp3    ❌ Será ignorado (não escaneia subpastas)
```

❌ **Permissões** - Verifique se o PHP tem permissão de leitura nas pastas.

❌ **Encoding** - No Windows, caracteres especiais podem causar problemas. O script já configura UTF-8 automaticamente.
### ❌ Problema: Erro de parsing SSC

Se você vê um erro como:
```
Expected "|", "}", or [01234567XxYyZzVHFMLK*BSEIa] but "l" found.
```

**Isso significa que o arquivo .ssc está CORROMPIDO**, não é problema do gerador.

**Solução:**

1. **Encontre o arquivo corrompido:**
```
http://localhost/piu-songs/find_corrupted_ssc.php
```
Este script escaneia TODOS os arquivos .ssc e mostra quais estão corrompidos.

2. **Corrija o problema:**
   - Re-baixe a música de uma fonte confiável
   - Ou remova temporariamente a pasta da música
   - Ou edite manualmente o arquivo .ssc (avançado)

**IMPORTANTE:** A aplicação agora mostra um alerta amigável quando encontra SSC corrompido e continua funcionando com as outras músicas.
## �🔄 Após gerar o songs.json

1. **Copie** o arquivo gerado para a pasta do engine:
   ```
   Copiar: piu-songs/songs.json
   Para: piuweb_phoenix/piured-engine/songs.json
   ```

2. **Recarregue** a página do PIU Web

3. **Limpe o cache** do navegador (Ctrl + Shift + R)

## ✨ Melhorias futuras

- [ ] Detecção automática de BPM e duração
- [ ] Geração de miniaturas automáticas
- [ ] Validação de arquivos .ssc
- [ ] Interface gráfica para gerenciamento

---

**Desenvolvido por:** Daniel Neitzel  
**Última atualização:** 04/06/2026
