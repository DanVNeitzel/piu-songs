'use strict';
// Public data only: this loader never calls a PHP endpoint or asks for a password.
(function (root) {
  async function json(path, optional = false) {
    const response = await fetch(path, { cache: 'no-cache' });
    if (!response.ok) {
      if (optional && response.status === 404) return null;
      throw new Error(`Não foi possível carregar ${path} (HTTP ${response.status}).`);
    }
    return response.json();
  }
  function localPath(value) {
    return typeof value === 'string' && /^\.\/Songs\//.test(value) && !value.split('/').includes('..');
  }
  function fromTree(catalog) {
    const channels = catalog.children?.find(item => item.name === 'Songs')?.children;
    if (!Array.isArray(channels)) throw new Error('O catálogo de músicas possui uma estrutura inválida.');
    const songs = new Map();
    for (const channel of channels) {
      for (const item of channel.children || []) {
        if (item.type !== 'folder' || !localPath(item.path)) continue;
        let song = songs.get(item.path);
        if (!song) {
          const files = (item.children || []).filter(file => file.type === 'file' && localPath(file.path));
          const audio = files.filter(file => /\.(mp3|wav|ogg)$/i.test(file.name)).map(file => file.path);
          const issues = [];
          if (!audio.length) issues.push({ code: 'missing_audio', path: item.path, message: 'Música sem áudio no catálogo.' });
          if (!files.some(file => /\.(ssc|sm)$/i.test(file.name))) issues.push({ code: 'missing_chart', path: item.path, message: 'Música sem chart no catálogo.' });
          song = { name: item.name, title: item.name, artist: '', category: item.path.split('/')[2], path: item.path,
            channels: [], charts: [], audio, banner: files.find(file => /\.(webp|png|jpe?g|gif|avif|bmp)$/i.test(file.name))?.path || null, issues };
          songs.set(item.path, song);
        }
        if (!song.channels.includes(channel.name)) song.channels.push(channel.name);
      }
    }
    return { songs, channels };
  }
  root.piuLoadStaticCatalog = async function () {
    // Preferred Pages build: a single snapshot with parsed SSC metadata.
    const snapshot = await json('library-public.json', true);
    if (snapshot) {
      if (snapshot.version !== 1 || !Array.isArray(snapshot.library) || !snapshot.stats || !Array.isArray(snapshot.issues)) throw new Error('Versão inválida do catálogo público.');
      return snapshot;
    }
    // Also supports an ordinary HTTP server serving the checked-in JSON files.
    const { songs, channels } = fromTree(await json('songs.json'));
    const manifest = await json('difficulty-zone/manifest.json', true);
    let partial = !manifest;
    if (manifest) {
      const max = Math.min(26, Number(manifest.levels?.max) || 26);
      const jobs = [];
      for (const mode of ['single', 'double']) {
        for (let level = 1; level <= max; level++) jobs.push({ mode, level: String(level), file: `${level}.json` });
        jobs.push({ mode, level: '27+', file: '27-plus.json' });
      }
      // Limit network concurrency for slower connections.
      let next = 0;
      await Promise.all(Array.from({ length: 6 }, async () => {
        while (next < jobs.length) {
          const job = jobs[next++];
          const entries = await json(`difficulty-zone/${job.mode}/${job.file}`, true);
          if (!Array.isArray(entries)) { partial = true; continue; }
          for (const entry of entries) {
            const song = songs.get(entry.songPath);
            if (song && !song.charts.some(chart => chart.mode === job.mode && chart.level === job.level)) song.charts.push({ mode: job.mode, level: job.level });
          }
        }
      }));
    }
    const library = [...songs.values()];
    const issues = library.flatMap(song => song.issues);
    return { version: 1, published: true, generated_at: manifest?.generatedAt || null, media_base_url: '', partial,
      fallback: true, library, issues, changes: { added: [], removed: [], changed: [] },
      stats: { total_songs: library.length, total_categories: channels.length, cache_hits: 0, issues: issues.length } };
  };
})(globalThis);
