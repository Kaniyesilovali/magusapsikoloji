#!/usr/bin/env node
/**
 * diff-check.js — migrasyon doğrulama aracı
 * Eski site çıktısı ile yeni Eleventy çıktısını SEO-kritik alanlar üzerinden karşılaştırır.
 *
 * Kullanım: node scripts/diff-check.js <eski-dir> <yeni-dir> [--only path1,path2] [--verbose]
 * Örnek:    node scripts/diff-check.js .snapshot _site
 *
 * Birebir eşit olması gerekenler: canonical, title, meta description, robots,
 * hreflang üçlüsü, OG/Twitter, JSON-LD (deep compare), h1, iç linkler, görünür metin.
 * Bilinçli farklar WHITELIST ile yönetilir.
 */

const fs = require('fs');
const path = require('path');
const cheerio = require('cheerio');

const [oldDir, newDir] = process.argv.slice(2);
if (!oldDir || !newDir) {
  console.error('Usage: node scripts/diff-check.js <old-dir> <new-dir> [--only p1,p2] [--verbose]');
  process.exit(2);
}
const VERBOSE = process.argv.includes('--verbose');
const onlyArg = process.argv.find((a, i) => process.argv[i - 1] === '--only');
const ONLY = onlyArg ? onlyArg.split(',') : null;

// Bilinçli/kabul edilmiş farklar
const WHITELIST = {
  // Kaldırılan script src'leri (runtime FAQ widget'ı server-side render'a taşındı)
  removedScripts: ['assets/js/faq.js'],
  // Bu sayfa-yollarında JSON-LD farkı raporlanır ama fail etmez (FAQ konsolidasyonu)
  jsonLdRelaxed: [],
  // Metin karşılaştırmasında yok sayılacak eski placeholder kalıpları
  textIgnorePatterns: [/\+90 5XX[X\s]*XXX XX XX/g, /905XXXXXXXXX/g, /9055555/g],
  // Eski sitede zaten kırık olan, migrasyonda düzeltilen linkler
  fixedLinks: ['/en/blog/when-to-see-a-psychologist.html', '/en/blog/psychologist-vs-psychiatrist.html'],
};

function listHtml(dir, base = '') {
  const out = [];
  for (const e of fs.readdirSync(path.join(dir, base), { withFileTypes: true })) {
    const rel = path.join(base, e.name);
    if (e.isDirectory()) {
      if (['node_modules', '.git', 'deploy', 'admin', '_site', '.snapshot', 'scripts', 'src', '_layouts', '_includes', '_data', 'content'].includes(e.name)) continue;
      out.push(...listHtml(dir, rel));
    } else if (e.name.endsWith('.html')) {
      out.push(rel);
    }
  }
  return out;
}

function norm(s) { return (s || '').replace(/\s+/g, ' ').trim(); }

function extract(file) {
  const html = fs.readFileSync(file, 'utf8');
  const $ = cheerio.load(html);

  const hreflangs = {};
  $('link[rel="alternate"][hreflang]').each((_, el) => {
    hreflangs[$(el).attr('hreflang')] = $(el).attr('href');
  });

  const meta = (name, attr = 'name') => norm($(`meta[${attr}="${name}"]`).attr('content'));

  // Anahtar sırasından bağımsız karşılaştırma için derin sıralama
  const sortKeys = (o) => {
    if (Array.isArray(o)) return o.map(sortKeys);
    if (o && typeof o === 'object') return Object.keys(o).sort().reduce((acc, k) => { acc[k] = sortKeys(o[k]); return acc; }, {});
    return o;
  };
  const jsonld = [];
  $('script[type="application/ld+json"]').each((_, el) => {
    try { jsonld.push(sortKeys(JSON.parse($(el).html()))); } catch { jsonld.push({ __parseError: norm($(el).html()).slice(0, 80) }); }
  });
  jsonld.sort((a, b) => JSON.stringify(a).localeCompare(JSON.stringify(b)));

  // İç linkler: nav/footer şablonlaştırıldığı için sadece <main> içi karşılaştırılır
  const hrefs = new Set();
  const linkScope = $('main').length ? $('main') : $('body');
  linkScope.find('a[href]').each((_, el) => {
    const h = $(el).attr('href');
    if (h && !h.startsWith('http') && !h.startsWith('mailto:') && !h.startsWith('#')) hrefs.add(h.split('#')[0]);
  });

  // Script src'leri göreli/mutlak farkından bağımsız normalize et (../assets/x → assets/x)
  const normSrc = (s) => s.replace(/^(\.\.\/)+/, '').replace(/^\//, '');
  const scripts = [];
  $('script[src]').each((_, el) => scripts.push(normSrc($(el).attr('src'))));

  // Görünür metin: nav/footer şablonlaştırıldığı için sadece <main> karşılaştırılır.
  // Sunucuda render edilen SSS akordiyonu bilinçli bir ekleme — metin karşılaştırmasına girmez.
  $('script, style, noscript, [data-faq-accordion]').remove();
  let text = norm(($('main').length ? $('main') : $('body')).text());
  for (const p of WHITELIST.textIgnorePatterns) text = text.replace(p, '␀');

  return {
    title: norm($('title').text()),
    description: meta('description'),
    keywords: meta('keywords'),
    robots: meta('robots'),
    canonical: norm($('link[rel="canonical"]').attr('href')),
    hreflangs,
    og: {
      title: meta('og:title', 'property'), description: meta('og:description', 'property'),
      url: meta('og:url', 'property'), type: meta('og:type', 'property'), image: meta('og:image', 'property'),
      locale: meta('og:locale', 'property'),
    },
    twitter: { title: meta('twitter:title'), description: meta('twitter:description'), card: meta('twitter:card') },
    jsonld, hrefs, scripts,
    h1: norm($('h1').first().text() || ''),
    text,
  };
}

function sentences(text) {
  return text.split(/(?<=[.!?…])\s+/).map(norm).filter(s => s.length > 25);
}

let failures = 0, warnings = 0, checked = 0;

const oldFiles = listHtml(oldDir).filter(f => !ONLY || ONLY.includes(f));
for (const rel of oldFiles.sort()) {
  const oldFile = path.join(oldDir, rel);
  const newFile = path.join(newDir, rel);
  if (!fs.existsSync(newFile)) {
    // Eski dosya redirect stub'u olabilir; yine de eksikse raporla
    console.log(`✖ ${rel}: yeni çıktıda YOK`);
    failures++;
    continue;
  }
  checked++;
  const A = extract(oldFile), B = extract(newFile);
  const errs = [], warns = [];

  const eq = (name, a, b) => { if (a !== b) errs.push(`${name}:\n    eski: ${a}\n    yeni: ${b}`); };
  eq('title', A.title, B.title);
  eq('description', A.description, B.description);
  eq('keywords', A.keywords, B.keywords);
  eq('robots', A.robots, B.robots);
  eq('canonical', A.canonical, B.canonical);
  eq('h1', A.h1, B.h1);
  for (const k of new Set([...Object.keys(A.hreflangs), ...Object.keys(B.hreflangs)])) {
    eq(`hreflang[${k}]`, A.hreflangs[k], B.hreflangs[k]);
  }
  for (const k of Object.keys(A.og)) eq(`og:${k}`, A.og[k], B.og[k]);
  for (const k of Object.keys(A.twitter)) eq(`twitter:${k}`, A.twitter[k], B.twitter[k]);

  // JSON-LD deep compare
  const jA = JSON.stringify(A.jsonld), jB = JSON.stringify(B.jsonld);
  if (jA !== jB) {
    const msg = `json-ld farklı (eski ${A.jsonld.length} blok / yeni ${B.jsonld.length} blok)`;
    if (WHITELIST.jsonLdRelaxed.includes(rel)) warns.push(msg); else errs.push(msg + (VERBOSE ? `\n    eski: ${jA.slice(0, 400)}\n    yeni: ${jB.slice(0, 400)}` : ''));
  }

  // İç linkler: eskide olup yenide olmayan link = hata (düzeltilen kırık linkler hariç)
  for (const h of A.hrefs) if (!B.hrefs.has(h) && !WHITELIST.fixedLinks.includes(h)) errs.push(`kayıp iç link: ${h}`);
  for (const h of B.hrefs) if (!A.hrefs.has(h)) warns.push(`yeni iç link: ${h}`);

  // Scriptler: whitelist dışı kayıp script = hata
  for (const s of A.scripts) {
    if (!B.scripts.includes(s) && !WHITELIST.removedScripts.some(w => s.includes(w))) errs.push(`kayıp script: ${s}`);
  }

  // Görünür metin: eski cümleler yenide bulunmalı (eklemeler serbest, sadece info)
  const bText = B.text;
  const missing = sentences(A.text).filter(s => !bText.includes(s));
  if (missing.length) errs.push(`kayıp metin (${missing.length} cümle):` + missing.slice(0, 5).map(s => `\n    - ${s.slice(0, 120)}`).join(''));

  if (errs.length) {
    failures++;
    console.log(`✖ ${rel}`);
    for (const e of errs) console.log(`  ${e}`);
  } else if (VERBOSE) {
    console.log(`✓ ${rel}`);
  }
  if (warns.length && VERBOSE) for (const w of warns) console.log(`  ⚠ ${rel}: ${w}`);
  warnings += warns.length;
}

console.log(`\n${checked} sayfa kontrol edildi — ${failures} hatalı, ${warnings} uyarı.`);
process.exit(failures ? 1 : 0);
