# 🚨 ERRO SSC CORROMPIDO - GUIA RÁPIDO

## 🔍 O que aconteceu?

Você recebeu um erro como:
```
Expected "|", "}", or [01234567XxYyZzVHFMLK*BSEIa] but "l" found.
Line: 23264
```

**Isso NÃO é problema do gerador de songs.json!**  
É um arquivo `.ssc` corrompido que contém dados inválidos.

---

## ✅ SOLUÇÃO RÁPIDA

### 1️⃣ ENCONTRE O ARQUIVO CORROMPIDO
```
http://localhost/piu-songs/find_corrupted_ssc.php
```
Este script mostra:
- ❌ Quais músicas têm SSC corrompido
- 📍 Linha exata do erro
- 🔤 Qual caractere inválido foi encontrado

### 2️⃣ CORRIJA O PROBLEMA

**Opção A - Remover temporariamente:**
1. Vá na pasta da música problemática
2. Mova a pasta inteira para outro lugar (fora de `Songs/`)
3. Regenere o `songs.json`

**Opção B - Re-baixar:**
1. Apague a pasta da música
2. Baixe novamente de uma fonte confiável
3. Regenere o `songs.json`

**Opção C - Corrigir manualmente (avançado):**
1. Abra o arquivo `.ssc` em um editor de texto
2. Vá até a linha indicada
3. Remova os caracteres inválidos
4. Salve e teste novamente

### 3️⃣ REGENERE O SONGS.JSON
```
http://localhost/piu-songs/generate_songs_json.php
```

---

## 🛡️ PROTEÇÃO ADICIONADA

A aplicação agora tem **tratamento de erro robusto**:

✅ Se uma música tiver SSC corrompido:
- Mostra alerta amigável ao usuário
- Indica qual música está com problema
- **CONTINUA funcionando** com as outras músicas
- Não quebra mais a aplicação inteira

---

## 📋 CARACTERES VÁLIDOS EM SSC

Arquivos `.ssc` só podem ter estes caracteres nas notas:

✅ **Números:** `0`, `1`, `2`, `3`, `4`, `5`, `6`, `7`  
✅ **Letras maiúsculas:** `X`, `Y`, `Z`, `V`, `H`, `F`, `M`, `L`, `K`, `B`, `S`, `E`, `I`  
✅ **Letras minúsculas:** `x`, `y`, `z`, `a`  
✅ **Símbolos:** `*`, `|` (pipe), `{`, `}`  

❌ **QUALQUER OUTRO CARACTERE É INVÁLIDO!**

---

## 🔗 SCRIPTS ÚTEIS

| Script | URL | Função |
|--------|-----|--------|
| **Gerar songs.json** | `http://localhost/piu-songs/generate_songs_json.php` | Gera o arquivo JSON |
| **Debug completo** | `http://localhost/piu-songs/generate_songs_json.php?debug=1` | Mostra detalhes |
| **Encontrar SSC corrompido** | `http://localhost/piu-songs/find_corrupted_ssc.php` | Escaneia todos os SSC |
| **Testar caracteres especiais** | `http://localhost/piu-songs/debug_scan.php` | Testa músicas específicas |

---

**Última atualização:** 04/06/2026  
**Desenvolvido por:** Daniel Neitzel
