'use strict';
let token = '', preview = null, rows = [], page = 0;
const size = 40;
const $ = id => document.getElementById(id);
const staticMode = document.documentElement?.dataset.mode === 'static';
function status(message, error = false) { $('status').textContent = message; $('status').className = error ? 'warning' : 'ok'; }
async function request(data) {
  const response = await fetch('api_generate_songs.php', data ? { method: 'POST', headers: { 'X-CSRF-Token': token }, body: new URLSearchParams(data) } : { cache: 'no-store' });
  const result = await response.json();
  if (!response.ok || !result.success) throw new Error(result.error || 'Falha ao processar a solicitação.');
  return result;
}
function options(id, values, label) {
  const select = $(id), previous = select.value;
  select.replaceChildren(new Option(label, ''));
  [...new Set(values)].sort((a, b) => a.localeCompare(b, 'pt-BR', { numeric: true })).forEach(value => select.add(new Option(value, value)));
  if ([...select.options].some(option => option.value === previous)) select.value = previous;
}
function update(result) {
  preview = result;
  $('results').hidden = false;
  $('countSongs').textContent = result.stats.total_songs;
  $('countChannels').textContent = result.stats.total_categories;
  $('countIssues').textContent = result.issues.length;
  $('cacheHits').textContent = result.stats.cache_hits;
  const c = result.changes;
  $('changes').textContent = staticMode
    ? (result.generated_at ? `Atualizado em ${new Date(result.generated_at).toLocaleString('pt-BR')}.` : 'Biblioteca carregada do catálogo disponível.')
    : `${c.added.length} adicionadas · ${c.removed.length} removidas · ${c.changed.length} com lista de arquivos alterada`;
  $('changeDetails').textContent = JSON.stringify(c, null, 2);
  $('issueDetails').textContent = result.issues.length ? result.issues.map(issue => `${issue.path}\n${issue.message}`).join('\n\n') : 'Nenhum problema encontrado nas verificações de estrutura.';
  options('channel', result.library.flatMap(song => song.channels), 'Todos os canais');
  options('mode', result.library.flatMap(song => song.charts.map(chart => chart.mode)), 'Todos os modos');
  for (const option of $('mode').options) if (option.value) option.text = ({single: 'Single', double: 'Double', 'pump-couple': 'Co-op', 'pump-routine': 'Routine'})[option.value] || option.value;
  options('level', result.library.flatMap(song => song.charts.map(chart => String(chart.level))), 'Todos os níveis');
  $('publish').disabled = result.published;
  $('downloadReport').disabled = false;
  filter();
}
function filter() {
  const query = $('search').value.toLocaleLowerCase('pt-BR'), mode = $('mode').value, level = $('level').value, channel = $('channel').value;
  rows = (preview?.library || []).filter(song => {
    return (!query || `${song.title} ${song.name} ${song.artist}`.toLocaleLowerCase('pt-BR').includes(query)) &&
      (!channel || song.channels.includes(channel)) &&
      ((!mode && !level) || song.charts.some(chart => (!mode || chart.mode === mode) && (!level || String(chart.level) === level))) &&
      (!$('onlyIssues').checked || song.issues.length > 0);
  });
  page = 0; render();
}
function cell(row, text) { const td = document.createElement('td'); td.textContent = text; row.append(td); return td; }
function mediaUrl(path) {
  if (typeof path !== 'string' || !/^\.\/Songs\//.test(path) || path.split('/').includes('..')) return null;
  const encoded = path.split('/').map(part => encodeURIComponent(part)).join('/');
  const base = preview?.media_base_url;
  if (!base) return encoded;
  const url = new URL(base);
  if (!['https:', 'http:'].includes(url.protocol)) return null;
  return new URL(encoded, url).href;
}
function render() {
  $('songs').replaceChildren();
  for (const song of rows.slice(page * size, (page + 1) * size)) {
    const row = document.createElement('tr');
    const name = cell(row, song.title), detail = document.createElement('small');
    detail.textContent = `${song.artist || 'Artista não informado'} · ${song.category}`; name.append(detail);
    cell(row, song.charts.map(chart => `${chart.mode === 'single' ? 'S' : chart.mode === 'double' ? 'D' : chart.mode} ${chart.level}`).join(' · ') || (preview.fallback ? 'Sem nível indexado' : 'Sem metadados SSC'));
    const issues = cell(row, song.issues.length ? song.issues.map(issue => issue.message).join('\n') : 'OK');
    if (song.issues.length) issues.className = 'warning';
    const audioCell = cell(row, '');
    const source = song.audio.length ? mediaUrl(song.audio[0]) : null;
    if (source) {
      const audio = document.createElement('audio'); audio.controls = true; audio.preload = 'none';
      audio.src = source;
      audio.setAttribute('aria-label', `Ouvir ${song.title}`); audioCell.append(audio);
    }
    $('songs').append(row);
  }
  const pages = Math.max(1, Math.ceil(rows.length / size));
  $('pageInfo').textContent = `${rows.length} músicas · Página ${page + 1} de ${pages}`;
  $('previous').disabled = page === 0; $('next').disabled = page + 1 >= pages;
}
async function run(action) {
  if (staticMode) return loadStatic();
  const publish = action === 'publish';
  if (publish && !preview) return;
  $('scan').disabled = $('publish').disabled = true;
  status(publish ? 'Validando novamente e publicando…' : 'Escaneando a biblioteca e preparando a prévia…');
  try {
    const data = { action, force: $('force').checked ? '1' : '0' };
    if (publish) data.fingerprint = preview.fingerprint;
    const result = await request(data);
    update(result);
    status(result.published ? 'songs.json publicado. Gere os índices de dificuldade para atualizar a seleção por nível no jogo.' : 'Prévia pronta. Confira as alterações e os problemas antes de publicar.');
  } catch (error) {
    status(error.message, true);
    $('publish').disabled = true;
  } finally { $('scan').disabled = false; }
}
$('scan').addEventListener('click', () => run('preview'));
$('publish').addEventListener('click', () => run('publish'));
for (const id of ['search', 'channel', 'mode', 'level', 'onlyIssues']) $(id).addEventListener(id === 'search' ? 'input' : 'change', filter);
$('previous').addEventListener('click', () => { page--; render(); });
$('next').addEventListener('click', () => { page++; render(); });
$('downloadReport').addEventListener('click', () => {
  if (!preview) return;
  const blob = new Blob([JSON.stringify({ stats: preview.stats, changes: preview.changes, issues: preview.issues }, null, 2)], { type: 'application/json' });
  const url = URL.createObjectURL(blob), link = document.createElement('a');
  link.href = url; link.download = 'relatorio-biblioteca.json'; link.click(); setTimeout(() => URL.revokeObjectURL(url), 1000);
});
async function loadStatic() {
  $('scan').disabled = true;
  status('Carregando biblioteca…');
  try {
    const result = await globalThis.piuLoadStaticCatalog();
    update(result);
    status(result.partial ? 'Biblioteca carregada. Alguns índices de dificuldade não estão disponíveis.' : 'Biblioteca pronta para consulta.');
  } catch (error) { status(error.message, true); }
  finally { $('scan').disabled = false; }
}
if (staticMode) loadStatic();
else request().then(result => { token = result.xsrf; $('scan').disabled = false; status('Pronto para analisar a biblioteca.'); }).catch(error => status(error.message + ' Use o link Gerenciador para entrar.', true));
