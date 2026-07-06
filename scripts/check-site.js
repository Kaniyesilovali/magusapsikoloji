#!/usr/bin/env node
/**
 * check-site.js — build sonrası site bütünlüğü kontrolleri (CI'da npm run check).
 *  1. İç linkler: her <a href="/..."> hedefi build çıktısında var mı?
 *  2. hreflang: hedefler mevcut mu ve karşılıklı mı?
 *  3. sitemap.xml: listelenen her URL build çıktısında var mı?
 *  4. TR/EN eşleşmeyen sayfalar (uyarı).
 * Hata varsa exit 1 → deploy durur.
 */
const fs = require('fs');
const path = require('path');
const cheerio = require('cheerio');

const OUT = path.join(__dirname, '..', '_site');
const SITE_URL = 'https://magusapsikoloji.com';

const files = [];
(function walk(dir) {
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    const p = path.join(dir, e.name);
    if (e.isDirectory()) walk(p);
    else files.push(p);
  }
})(OUT);

const existing = new Set(files.map((f) => '/' + path.relative(OUT, f).split(path.sep).join('/')));
const urlExists = (u) => {
  const p = u.replace(SITE_URL, '').split('#')[0].split('?')[0];
  if (!p || p === '/') return existing.has('/index.html');
  return existing.has(p) || existing.has(p.replace(/\/$/, '') + '/index.html') || existing.has(p + '/index.html');
};

let errors = 0;
const warn = [];
const htmlFiles = files.filter((f) => f.endsWith('.html'));
const hreflangMap = {}; // rel → {tr,en}

for (const f of htmlFiles) {
  const rel = '/' + path.relative(OUT, f).split(path.sep).join('/');
  const $ = cheerio.load(fs.readFileSync(f, 'utf8'));
  if ($('meta[http-equiv="refresh"]').length) continue; // redirect stub

  $('a[href]').each((_, el) => {
    const h = $(el).attr('href');
    if (!h || !h.startsWith('/') || h.startsWith('//')) return;
    if (!urlExists(h)) { console.log(`✖ ${rel}: kırık link ${h}`); errors++; }
  });

  const hl = {};
  $('link[rel="alternate"][hreflang]').each((_, el) => { hl[$(el).attr('hreflang')] = $(el).attr('href'); });
  hreflangMap[rel] = hl;
  for (const [l, href] of Object.entries(hl)) {
    if (!urlExists(href)) { console.log(`✖ ${rel}: hreflang[${l}] hedefi yok: ${href}`); errors++; }
  }
  const lang = rel.startsWith('/en/') ? 'en' : 'tr';
  const other = lang === 'tr' ? 'en' : 'tr';
  // /admin/ CMS arayüzüdür, çeviri eşi beklenmez
  if (!hl[other] && !rel.startsWith('/admin/')) warn.push(`${rel}: ${other.toUpperCase()} eşi yok`);
}

// Karşılıklılık: A'nın en'i B ise, B'nin tr'si A olmalı
for (const [rel, hl] of Object.entries(hreflangMap)) {
  const lang = rel.startsWith('/en/') ? 'en' : 'tr';
  const other = lang === 'tr' ? 'en' : 'tr';
  const target = hl[other];
  if (!target) continue;
  const tPath = target.replace(SITE_URL, '') || '/';
  const tKey = tPath === '/' ? '/index.html' : (existing.has(tPath) ? tPath : tPath.replace(/\/$/, '') + '/index.html');
  const back = hreflangMap[tKey];
  const self = SITE_URL + (rel === '/index.html' ? '/' : rel.replace(/\/index\.html$/, '/'));
  if (back && back[lang] && back[lang] !== self && back[lang] !== SITE_URL + rel) {
    console.log(`✖ hreflang karşılıklı değil: ${rel} → ${tPath} → ${back[lang]}`);
    errors++;
  }
}

// Sitemap kontrolü
const sm = fs.readFileSync(path.join(OUT, 'sitemap.xml'), 'utf8');
const $sm = cheerio.load(sm, { xmlMode: true });
let smCount = 0;
$sm('url > loc').each((_, el) => {
  smCount++;
  const u = $sm(el).text();
  if (!urlExists(u)) { console.log(`✖ sitemap URL'i build'de yok: ${u}`); errors++; }
});

if (warn.length) {
  console.log('\n⚠ Çevirisi olmayan sayfalar (bilgi):');
  for (const w of warn) console.log('  - ' + w);
}
console.log(`\n${htmlFiles.length} sayfa, sitemap ${smCount} URL — ${errors} hata, ${warn.length} uyarı.`);
process.exit(errors ? 1 : 0);
