// Exercise filtering and rendering without browser dependencies.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
class Element {
  constructor() { this.value = ''; this.checked = false; this.children = []; this.options = []; this.textContent = ''; }
  append(...children) { this.children.push(...children); }
  replaceChildren(...children) { this.children = children; this.options = children; }
  add(option) { this.options.push(option); }
  addEventListener() {}
  setAttribute() {}
}
const elements = {};
const context = vm.createContext({
  document: { getElementById: id => elements[id] ||= new Element(), createElement: () => new Element() },
  Option: function(text, value) { this.text = text; this.value = value; },
  fetch: async () => ({ ok: true, json: async () => ({ success: true, xsrf: 'fixture' }) }),
  URLSearchParams, URL, Blob, setTimeout
});
vm.runInContext(fs.readFileSync(require('node:path').join(__dirname, '../assets/catalog.js'), 'utf8'), context);
vm.runInContext(`
update({published:false, fingerprint:'fixture', stats:{total_songs:3,total_categories:2,cache_hits:1},
changes:{added:[],removed:[],changed:[]},issues:[],library:[
{title:'<img src=x onerror=alert(1)>',name:'Special !&',artist:'Artist',category:'Test',path:'./Songs/!&',channels:['ALL','Test'],charts:[{mode:'single',level:12},{mode:'double',level:20}],audio:['./Songs/!&/a #1.mp3'],issues:[]},
{title:'Other',name:'Other',artist:'Singer',category:'Test',path:'./Songs/Other',channels:['ALL'],charts:[{mode:'single',level:20}],audio:[],issues:[{message:'Missing audio'}]},
{title:'Third',name:'Third',artist:'Artist',category:'Test',path:'./Songs/Third',channels:['Test'],charts:[],audio:[],issues:[]}
]});`, context);
assert.equal(elements.songs.children.length, 3);
assert.equal(elements.songs.children[0].children[0].textContent, '<img src=x onerror=alert(1)>');
assert.equal(elements.songs.children[0].children[3].children[0].preload, 'none');
assert.equal(elements.songs.children[0].children[3].children[0].src, './Songs/!%26/a%20%231.mp3');
elements.mode.value = 'single'; elements.level.value = '20';
vm.runInContext('filter()', context);
assert.equal(elements.songs.children.length, 1, 'Mode and level must match the same chart');
assert.equal(elements.songs.children[0].children[0].textContent, 'Other');
elements.level.value = ''; elements.mode.value = ''; elements.channel.value = 'Test';
vm.runInContext('filter()', context);
assert.equal(elements.songs.children.length, 2);
elements.onlyIssues.checked = true;
vm.runInContext('filter()', context);
assert.equal(elements.songs.children.length, 0);
elements.channel.value = ''; elements.onlyIssues.checked = false; elements.search.value = 'singer';
vm.runInContext('filter()', context);
assert.equal(elements.songs.children.length, 1);
assert.equal(elements.songs.children[0].children[0].textContent, 'Other');
console.log('PASS: channel/mode/level/artist filters, issue filter, safe text rendering, encoded audio URL, lazy audio');
