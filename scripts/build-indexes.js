#!/usr/bin/env node
/**
 * build-indexes.js — tek seferlik migrasyon adımı.
 * Eski elle yazılmış blog/hizmet index kartlarını:
 *  1) ilgili içerik dosyalarının front matter'ına (card + cardOrder) taşır,
 *  2) index sayfalarındaki kart grid'ini Nunjucks koleksiyon döngüsüyle değiştirir
 *     ve dosyayı .njk'ye çevirir.
 */
const fs = require('fs');
const path = require('path');
const cheerio = require('cheerio');
const yaml = require('js-yaml');

const ROOT = path.join(__dirname, '..');
const norm = (s) => (s || '').replace(/\s+/g, ' ').trim();

function readFm(file) {
  const c = fs.readFileSync(file, 'utf8');
  const m = c.match(/^---\n([\s\S]*?)\n---\n([\s\S]*)$/);
  return { fm: yaml.load(m[1]), body: m[2] };
}
function writeFm(file, fm, body) {
  fs.writeFileSync(file, '---\n' + yaml.dump(fm, { lineWidth: -1, noRefs: true }) + '---\n' + body);
}

function contentFileForHref(href) {
  // /blog/x.html → content/tr/blog/x.html ; /en/... → content/en/...
  const rel = href.replace(/^\//, '');
  return path.join(ROOT, rel.startsWith('en/') ? `content/${rel}` : `content/tr/${rel}`);
}

// ── BLOG INDEX ──
function blogLoop(collection) {
  return `
{%- for p in collections.${collection} | indexSort %}
          <a href="{{ p.url }}" class="group bg-warm border border-warm-tertiary rounded-2xl overflow-hidden hover:shadow-lg transition-all">
            <div class="{{ p.data.card.gradientClass or 'h-44 bg-gradient-to-br from-primary/10 to-sage/15 flex items-center justify-center text-5xl' }}">{{ p.data.card.emoji }}</div>
            <div class="p-5">
              <div class="flex items-center gap-2 mb-3">
                <span class="{{ p.data.card.badgeClass or 'text-xs bg-primary/10 text-primary font-medium px-2.5 py-0.5 rounded-full' }}">{{ p.data.card.category or p.data.category }}</span>
                <span class="text-xs text-ink-light">{{ p.data.card.readingTime or p.data.readingTime }}</span>
              </div>
              <h2 class="font-semibold text-ink text-sm mb-2 group-hover:text-primary transition-colors leading-snug">{{ p.data.card.title or p.data.title }}</h2>
              <p class="text-xs text-ink-muted line-clamp-2 leading-relaxed">{{ p.data.card.excerpt or p.data.description }}</p>
            </div>
          </a>
{%- endfor %}
`;
}

function migrateBlogIndex(srcPage, indexFile, collection) {
  const $ = cheerio.load(fs.readFileSync(path.join(ROOT, srcPage), 'utf8'));
  const $grid = $('#blogGrid');
  if (!$grid.length) throw new Error(`#blogGrid yok: ${srcPage}`);
  let order = 0;
  $grid.children('a').each((_, a) => {
    order++;
    const $a = $(a);
    const file = contentFileForHref($a.attr('href'));
    if (!fs.existsSync(file)) { console.log(`  ⚠ içerik dosyası yok, kart atlandı: ${$a.attr('href')}`); return; }
    const spans = $a.find('span');
    const { fm, body } = readFm(file);
    fm.cardOrder = order;
    fm.card = {
      gradientClass: $a.children('div').first().attr('class'),
      emoji: norm($a.children('div').first().text()),
      badgeClass: spans.eq(0).attr('class'),
      category: norm(spans.eq(0).text()),
      readingTime: norm(spans.eq(1).text()),
      title: norm($a.find('h2, h3').first().text()),
      excerpt: norm($a.find('p').last().text()),
    };
    writeFm(file, fm, body);
  });
  console.log(`  ✓ ${order} kart front matter'a taşındı (${srcPage})`);

  // index dosyasında grid'i döngüyle değiştir, .njk'ye çevir
  const idx = readFm(path.join(ROOT, indexFile));
  if (/{{|{%/.test(idx.body)) throw new Error(`${indexFile} gövdesinde njk karakterleri var!`);
  const $i = cheerio.load(idx.body, null, false);
  const $igrid = $i('#blogGrid');
  if (!$igrid.length) throw new Error(`#blogGrid yok: ${indexFile}`);
  $igrid.html(blogLoop(collection));
  fs.writeFileSync(path.join(ROOT, indexFile.replace(/\.html$/, '.njk')), '---\n' + yaml.dump(idx.fm, { lineWidth: -1, noRefs: true }) + '---\n' + $i.html());
  fs.unlinkSync(path.join(ROOT, indexFile));
  console.log(`  ✓ ${indexFile} → njk döngü`);
}

// ── HİZMET INDEX ──
function serviceLoop(collection, anchorClass) {
  return `
{%- for p in collections.${collection} | indexSort %}
          <a href="{{ p.url }}" class="${anchorClass}">
            <h2 class="font-semibold text-ink mb-2 group-hover:text-primary transition-colors">{{ p.data.card.title or p.data.title }}</h2>
            <p class="text-sm text-ink-muted leading-relaxed mb-4">{{ p.data.card.excerpt or p.data.description }}</p>
            <span class="inline-flex items-center gap-1 text-xs font-medium text-primary">{{ p.data.card.linkLabel }} <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></span>
          </a>
{%- endfor %}
`;
}

function migrateServiceIndex(srcPage, indexFile, collection, hrefPrefix) {
  const $ = cheerio.load(fs.readFileSync(path.join(ROOT, srcPage), 'utf8'));
  const $cards = $(`main div.grid > a[href^="${hrefPrefix}"]`);
  if (!$cards.length) throw new Error(`hizmet kartı yok: ${srcPage}`);
  const anchorClass = $cards.first().attr('class');
  let order = 0;
  $cards.each((_, a) => {
    order++;
    const $a = $(a);
    const file = contentFileForHref($a.attr('href'));
    if (!fs.existsSync(file)) { console.log(`  ⚠ içerik dosyası yok, kart atlandı: ${$a.attr('href')}`); return; }
    const { fm, body } = readFm(file);
    fm.cardOrder = order;
    fm.card = {
      title: norm($a.find('h2, h3').first().text()),
      excerpt: norm($a.find('p').first().text()),
      linkLabel: norm($a.find('span').first().text()),
    };
    writeFm(file, fm, body);
  });
  console.log(`  ✓ ${order} hizmet kartı front matter'a taşındı (${srcPage})`);

  const idx = readFm(path.join(ROOT, indexFile));
  if (/{{|{%/.test(idx.body)) throw new Error(`${indexFile} gövdesinde njk karakterleri var!`);
  const $i = cheerio.load(idx.body, null, false);
  const $icards = $i(`div.grid > a[href^="${hrefPrefix}"]`);
  if (!$icards.length) throw new Error(`hizmet kartı yok: ${indexFile}`);
  $icards.first().parent().html(serviceLoop(collection, anchorClass));
  fs.writeFileSync(path.join(ROOT, indexFile.replace(/\.html$/, '.njk')), '---\n' + yaml.dump(idx.fm, { lineWidth: -1, noRefs: true }) + '---\n' + $i.html());
  fs.unlinkSync(path.join(ROOT, indexFile));
  console.log(`  ✓ ${indexFile} → njk döngü`);
}

const only = process.argv[2];
if (!only || only === 'blog') {
  migrateBlogIndex('blog/index.html', 'content/tr/blog/index.html', 'blog_tr');
  migrateBlogIndex('en/blog/index.html', 'content/en/blog/index.html', 'blog_en');
}
if (!only || only === 'services' || only === 'services-tr') {
  migrateServiceIndex('hizmetler/index.html', 'content/tr/hizmetler/index.html', 'services_tr', '/hizmetler/');
}
if (!only || only === 'services' || only === 'services-en') {
  migrateServiceIndex('en/services/index.html', 'content/en/services/index.html', 'services_en', '/en/services/');
}
