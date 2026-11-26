const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const loader = fs.readFileSync(path.join(__dirname, '../assets/static-catalog.js'), 'utf8');
async function load(responses) {
  const calls = [];
  let active = 0, maximum = 0;
  const context = vm.createContext({ fetch: async url => {
    calls.push(url); active++; maximum = Math.max(maximum, active);
    await Promise.resolve(); active--;
    return { ok: Object.hasOwn(responses, url), status: Object.hasOwn(responses, url) ? 200 : 404, json: async () => responses[url] };
  } });
  vm.runInContext(loader, context);
  const result = await context.piuLoadStaticCatalog();
  assert(calls.every(url => !url.includes('.php')), 'Static UI called a PHP endpoint');
  assert(maximum <= 6, 'Unbounded parallel requests');
  return { result, calls };
}
(async () => {
  const snapshot = { version: 1, library: [], stats: { total_songs: 0 }, issues: [] };
  const fast = await load({ 'library-public.json': snapshot });
  assert.equal(fast.calls.length, 1, 'Snapshot should need one request');
  const song = { name: '<Song !&>', path: './Songs/Test/<Song !&>', type: 'folder', children: [
    {name: 'a #1.mp3', path: './Songs/Test/<Song !&>/a #1.mp3', type: 'file'},
    {name: 'chart.ssc', path: './Songs/Test/<Song !&>/chart.ssc', type: 'file'}
  ] };
  const tree = { children: [{ name: 'Songs', children: [
    { name: 'ALL', children: [song, {type: 'folder', path: '../unsafe', name: 'unsafe'}] },
    { name: 'Test', children: [song] }
  ] }] };
  const fallback = await load({
    'songs.json': tree,
    'difficulty-zone/manifest.json': { levels: { max: 26 }, generatedAt: '2026-11-26T00:00:00Z' },
    'difficulty-zone/single/12.json': [{songPath: song.path}, {songPath: song.path}],
    'difficulty-zone/double/27-plus.json': [{songPath: song.path}]
  });
  assert.equal(fallback.result.library.length, 1, 'Virtual channels duplicated a song');
  assert.equal(fallback.result.library[0].channels.length, 2);
  assert.equal(fallback.result.library[0].charts.length, 2);
  assert(fallback.result.library[0].charts.some(chart => chart.level === '27+'));
  assert.equal(fallback.result.library[0].audio[0], song.children[0].path);
  assert.equal(fallback.result.partial, true, 'Missing buckets should report partial metadata');
  const minimal = await load({ 'songs.json': tree });
  assert.equal(minimal.result.library.length, 1, 'Missing difficulty index blocked the library');
  assert.equal(minimal.result.partial, true);
  await assert.rejects(load({ 'library-public.json': { version: 99 } }), /inválida/);
  await assert.rejects(load({ 'songs.json': {} }), /inválida/);
  // Smoke-test the actual HTML entry in static mode, including cross-origin media URLs.
  const elements = {};
  class Element {
    constructor() { this.value = ''; this.checked = false; this.children = []; this.options = []; }
    append(...values) { this.children.push(...values); }
    replaceChildren(...values) { this.children = values; this.options = values; }
    add(value) { this.options.push(value); }
    addEventListener() {}
    setAttribute() {}
  }
  let requests = 0;
  const publicResult = { version:1, published:true, library:[{title:'Song', artist:'Artist', name:'Song', category:'Test', channels:['Test'], path:song.path,
    charts:[{mode:'single',level:12}],audio:[song.children[0].path],issues:[]}],
    media_base_url:'https://raw.githubusercontent.com/owner/repo/sha/', stats:{total_songs:1,total_categories:1},
    issues:[],changes:{added:[],removed:[],changed:[]} };
  const ui = vm.createContext({
    document: { documentElement: { dataset: { mode: 'static' } }, getElementById: id => elements[id] ||= new Element(), createElement: () => new Element() },
    Option: function(text,value) { this.text=text; this.value=value; }, URL, URLSearchParams, Blob, setTimeout,
    piuLoadStaticCatalog: async () => { requests++; return publicResult; },
    fetch: () => { throw new Error('Static mode used an admin endpoint'); }
  });
  vm.runInContext(fs.readFileSync(path.join(__dirname, '../assets/catalog.js'), 'utf8'), ui);
  await new Promise(setImmediate);
  assert.equal(requests, 1);
  assert.equal(elements.songs.children.length, 1);
  const audio = elements.songs.children[0].children[3].children[0];
  assert.equal(audio.src, 'https://raw.githubusercontent.com/owner/repo/sha/Songs/Test/%3CSong%20!%26%3E/a%20%231.mp3');
  assert.equal(elements.publish.disabled, true);
  await vm.runInContext("run('preview')", ui);
  assert.equal(requests, 2, 'Refresh should reload public JSON');
  assert.equal(vm.runInContext("mediaUrl('javascript:alert(1)')", ui), null);
  console.log('PASS: static snapshot, JSON fallback, virtual deduplication, partial indexes, no PHP, static initialization, refresh and external media');
})().catch(error => { console.error(error); process.exitCode = 1; });
