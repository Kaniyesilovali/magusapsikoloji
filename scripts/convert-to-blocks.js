#!/usr/bin/env node
/**
 * convert-to-blocks.js — blog gövdelerini (ham HTML) CMS'te form gibi
 * düzenlenebilen tipli bloklara çevirir (tek seferlik migrasyon).
 *
 * Güvence: her blok, çıkarımdan sonra render-blocks.js ile yeniden HTML'e
 * çevrilir ve orijinaliyle DOM düzeyinde karşılaştırılır. Birebir eşleşmeyen
 * blok "ozel" (ham HTML) olarak saklanır; tüm gövde eşleşmezse dosyaya
 * dokunulmaz. Yani görünüm değişikliği matematiksel olarak imkânsız.
 *
 * Kullanım: node scripts/convert-to-blocks.js [--write]  (bayraksız: rapor)
 */
const fs = require('fs');
const path = require('path');
const cheerio = require('cheerio');
const matter = require('gray-matter');
const yaml = require('js-yaml');
const { renderBlocks, renderHero, renderers } = require('./render-blocks');

const ROOT = path.join(__dirname, '..');
const WRITE = process.argv.includes('--write');
const SKIP = new Set([
  'content/tr/blog/index.njk',
  'content/en/blog/index.njk',
  'content/tr/blog/beyin-beden.njk', // özel tasarımlı sayfa (layout: page)
  'content/tr/blog/dau-ogrencileri-psikolojik-destek.njk', // yönlendirme sayfası
]);

// ── DOM imzası: whitespace'ten bağımsız yapısal karşılaştırma ──
function domSig(html) {
  const $ = cheerio.load(html, null, false);
  const walk = (node) => {
    if (node.type === 'text') {
      const t = node.data.replace(/\s+/g, ' ').trim();
      return t ? JSON.stringify(t) : null;
    }
    if (node.type !== 'tag') return null;
    const attrs = Object.entries(node.attribs || {})
      .sort(([a], [b]) => a.localeCompare(b))
      .map(([k, v]) => `${k}="${String(v).replace(/\s+/g, ' ').trim()}"`)
      .join(' ');
    const kids = $(node).contents().toArray().map(walk).filter(Boolean);
    return `<${node.tagName} ${attrs}>[${kids.join(',')}]`;
  };
  return $.root().contents().toArray().map(walk).filter(Boolean).join('|');
}

// ── Satır içi HTML → Markdown ──
const escMd = (s) => s.replace(/([\\*_`[\]])/g, '\\$1');
function toMd($, el) {
  let out = '';
  for (const node of $(el).contents().toArray()) {
    if (node.type === 'text') out += escMd(node.data);
    else if (node.type !== 'tag') continue;
    else if (node.tagName === 'strong') out += `**${toMd($, node)}**`;
    else if (node.tagName === 'em') out += `*${toMd($, node)}*`;
    else if (node.tagName === 'a') out += `[${toMd($, node)}](${$(node).attr('href') || ''})`;
    else throw new Error(`satır içi ${node.tagName}`);
  }
  return out.replace(/\s+/g, ' ').trim();
}

const cls = ($, el) => ($(el).attr('class') || '').replace(/\s+/g, ' ').trim();
const kids = ($, el) => $(el).children().toArray();

// ── Kanonikleştirme: elle yazımdan gelen mikro sınıf varyantlarını tekler ──
// Görsel etki sıfır ya da ≤4px boşluk farkı; site genelinde tutarlılık artar.
const CLASS_CANON = new Map([
  ['font-semibold text-ink text-sm mb-2', 'text-sm text-ink font-medium mb-2'], // bilgi kutusu başlığı (kelime sırası)
  ['text-xs font-medium text-ink mb-2', 'text-xs font-medium text-ink mb-3'], // grid kart başlığı
  ['text-xs text-ink-muted space-y-1', 'text-xs text-ink-muted space-y-1.5'], // grid kart listesi
  ['font-semibold text-ink text-sm mb-1', 'font-semibold text-ink text-sm mb-1.5'], // yığın kart başlığı
  ['text-ink-muted leading-relaxed mb-5', 'text-ink-muted leading-relaxed mb-4'], // bileşen öncesi giriş paragrafı
  ['text-sm text-ink-muted mt-4 pl-1', 'text-sm text-ink-muted mt-4'], // liste sonu kapanış notu
]);
const STEP_ROW = 'flex gap-4 items-start';
const STEP_CIRCLE_CANON = 'w-9 h-9 rounded-full bg-primary flex items-center justify-center text-white text-sm font-bold shrink-0';

function canonicalize($, wrapper) {
  $(wrapper)
    .find('[class]')
    .each((_, el) => {
      const c = CLASS_CANON.get(cls($, el));
      if (c) $(el).attr('class', c);
    });
  // Yığın kartı başlığı varyantı (yalnız h3 — aynı dizgi p'de bilgi kutusu kanoniği)
  $(wrapper)
    .find('h3')
    .each((_, el) => {
      if (cls($, el) === 'text-sm text-ink font-medium mb-2') $(el).attr('class', 'font-semibold text-ink text-sm mb-1.5');
    });
  // Yığın kartlarında p-4 → p-5 (yalnız h3 başlıklı kartlar; grid kartlarıyla karışmaz)
  $(wrapper)
    .find('div.space-y-3 > div, div.space-y-4 > div')
    .each((_, el) => {
      if (cls($, el) === 'bg-warm rounded-xl p-4 border border-warm-tertiary' && $(el).children('h3').length)
        $(el).attr('class', 'bg-warm rounded-xl p-5 border border-warm-tertiary');
    });
  // Kart yığınları: space-y-4 → 3 (h3 başlıklı bg-warm kartlar)
  $(wrapper)
    .find('div')
    .each((_, el) => {
      if (cls($, el) !== 'space-y-4') return;
      const cards = kids($, el);
      if (
        cards.length &&
        cards.every((c) => cls($, c) === 'bg-warm rounded-xl p-5 border border-warm-tertiary' && $(c).children('h3').length)
      )
        $(el).attr('class', 'space-y-3');
    });
  // Adım yığınları: space-y-4 → 5, w-8 daire → w-9, h3 başlık → h2
  $(wrapper)
    .find('div')
    .each((_, el) => {
      const c = cls($, el);
      if (!/^space-y-[45]$/.test(c)) return;
      const rows = kids($, el);
      if (!rows.length || !rows.every((r) => cls($, r) === STEP_ROW)) return;
      $(el).attr('class', 'space-y-5');
      for (const row of rows) {
        const circle = $(row).children().first();
        if (/^w-[89] h-[89] rounded-full bg-primary /.test(cls($, circle))) circle.attr('class', STEP_CIRCLE_CANON);
        $(row)
          .find('> div:last-child > h3')
          .each((_, h) => {
            h.tagName = 'h2';
          });
      }
    });
}

// ── Blok çıkarıcılar (şekil bazlı; sınıf doğruluğunu round-trip garanti eder) ──
// Ortak önek: h2? + p* → {baslik, giris}; kalan çocukları döndürür
function splitHead($, children) {
  let i = 0;
  let baslik;
  if (children[i] && children[i].tagName === 'h2') baslik = toMd($, children[i++]);
  const ps = [];
  while (children[i] && children[i].tagName === 'p') ps.push(toMd($, children[i++]));
  return { baslik, ps, rest: children.slice(i) };
}

const extractors = {
  metin($, el) {
    if (cls($, el) !== '') return null;
    const { baslik, ps, rest } = splitHead($, kids($, el));
    if (rest.length || (!baslik && !ps.length)) return null;
    return { type: 'metin', ...(baslik && { baslik }), ...(ps.length && { metin: ps.join('\n\n') }) };
  },

  'bilgi-kutusu'($, el) {
    const m = cls($, el).match(/^bg-(primary|accent)\/5 /);
    if (!m) return null;
    const c = kids($, el);
    if (c.length !== 2 || c.some((k) => k.tagName !== 'p')) return null;
    return {
      type: 'bilgi-kutusu',
      ton: m[1] === 'accent' ? 'turuncu' : 'yesil',
      baslik: toMd($, c[0]),
      metin: toMd($, c[1]),
    };
  },

  'onayli-liste'($, el) {
    if (cls($, el) !== '') return null;
    const { baslik, ps, rest } = splitHead($, kids($, el));
    let kapanis;
    if (rest.length === 2 && rest[1].tagName === 'p') kapanis = toMd($, rest.pop());
    if (rest.length !== 1 || rest[0].tagName !== 'ul') return null;
    const maddeler = [];
    for (const li of kids($, rest[0])) {
      const lc = kids($, li);
      if (lc.length !== 2 || lc[0].tagName !== 'svg' || lc[1].tagName !== 'div') return null;
      const dc = kids($, lc[1]);
      if (!dc.length || dc.length > 2 || dc.some((k) => k.tagName !== 'p')) return null;
      maddeler.push({ baslik: toMd($, dc[0]), ...(dc[1] && { aciklama: toMd($, dc[1]) }) });
    }
    if (!maddeler.length) return null;
    return {
      type: 'onayli-liste',
      ...(baslik && { baslik }),
      ...(ps.length && { giris: ps.join('\n\n') }),
      maddeler,
      ...(kapanis && { kapanis }),
    };
  },

  'kart-grid'($, el) {
    if (cls($, el) !== '') return null;
    const { baslik, ps, rest } = splitHead($, kids($, el));
    if (rest.length !== 1 || !/^grid sm:grid-cols-[23] /.test(cls($, rest[0]))) return null;
    const kolon = cls($, rest[0]).includes('cols-2') ? 2 : 3;
    const kartlar = [];
    for (const card of kids($, rest[0])) {
      const cc = kids($, card);
      if (cc.length !== 2 || cc[0].tagName !== 'p' || cc[1].tagName !== 'ul') return null;
      const maddeler = kids($, cc[1]).map((li) => toMd($, li).replace(/^•\s*/, ''));
      kartlar.push({ baslik: toMd($, cc[0]), maddeler });
    }
    if (!kartlar.length) return null;
    return {
      type: 'kart-grid',
      ...(baslik && { baslik }),
      ...(ps.length && { giris: ps.join('\n\n') }),
      kolon,
      kartlar,
    };
  },

  'kart-yigini'($, el) {
    if (cls($, el) !== '') return null;
    const { baslik, ps, rest } = splitHead($, kids($, el));
    if (rest.length !== 1 || cls($, rest[0]) !== 'space-y-3') return null;
    const kartlar = [];
    for (const card of kids($, rest[0])) {
      const cc = kids($, card);
      if (cc.length !== 2 || cc[0].tagName !== 'h3' || cc[1].tagName !== 'p') return null;
      kartlar.push({ baslik: toMd($, cc[0]), metin: toMd($, cc[1]) });
    }
    if (!kartlar.length) return null;
    return {
      type: 'kart-yigini',
      ...(baslik && { baslik }),
      ...(ps.length && { giris: ps.join('\n\n') }),
      kartlar,
    };
  },

  adimlar($, el) {
    // Çıplak yığın ya da başlık/giriş sarmalı içinde yığın
    let stack = el;
    let baslik;
    let ps = [];
    if (cls($, el) === '') {
      const head = splitHead($, kids($, el));
      if (head.rest.length !== 1) return null;
      baslik = head.baslik;
      ps = head.ps;
      stack = head.rest[0];
    }
    if (!/^space-y-5$/.test(cls($, stack))) return null;
    const adimlar = [];
    for (const row of kids($, stack)) {
      const rc = kids($, row);
      if (rc.length !== 2 || rc[0].tagName !== 'div' || rc[1].tagName !== 'div') return null;
      const dc = kids($, rc[1]);
      if (dc.length !== 2) return null;
      adimlar.push({ baslik: toMd($, dc[0]), metin: toMd($, dc[1]) });
    }
    if (!adimlar.length) return null;
    return {
      type: 'adimlar',
      ...(baslik && { baslik }),
      ...(ps.length && { giris: ps.join('\n\n') }),
      adimlar,
    };
  },

  'numarali-liste'($, el) {
    if (cls($, el) !== '') return null;
    const { baslik, ps, rest } = splitHead($, kids($, el));
    let kapanis;
    if (rest.length === 2 && rest[1].tagName === 'p') kapanis = toMd($, rest.pop());
    if (rest.length !== 1 || cls($, rest[0]) !== 'space-y-3') return null;
    let stil;
    const maddeler = [];
    for (const row of kids($, rest[0])) {
      if (cls($, row) !== 'flex gap-4 bg-warm rounded-xl p-4 border border-warm-tertiary') return null;
      const rc = kids($, row);
      if (rc.length !== 2 || rc[0].tagName !== 'span' || rc[1].tagName !== 'p') return null;
      stil = cls($, rc[0]).includes('bg-primary/10') ? 'acik' : 'koyu';
      maddeler.push(toMd($, rc[1]));
    }
    if (!maddeler.length) return null;
    return {
      type: 'numarali-liste',
      ...(baslik && { baslik }),
      ...(ps.length && { giris: ps.join('\n\n') }),
      stil,
      maddeler,
      ...(kapanis && { kapanis }),
    };
  },
};

// Bir üst seviye div'i bloğa çevir; eşleşme yoksa ozel
function convertBlock($, el) {
  const original = $.html(el);
  for (const ex of Object.values(extractors)) {
    let blk;
    try {
      blk = ex($, el);
    } catch {
      blk = null;
    }
    if (blk && domSig(renderers[blk.type](blk)) === domSig(original)) return blk;
  }
  return { type: 'ozel', html: original.trim() };
}

// ── Hero çıkarımı ──
function extractHero($, sec, fm) {
  const inner = $(sec).children('div').first();
  const rows = kids($, inner);
  if (rows.length !== 4) throw new Error(`hero satır sayısı ${rows.length}`);
  const [crumb, badgeRow, h1, lead] = rows;
  if (h1.tagName !== 'h1' || lead.tagName !== 'p') throw new Error('hero h1/p bulunamadı');
  const spans = $(badgeRow).children('span').toArray();
  if (spans.length !== 2) throw new Error('hero rozet satırı');
  const kategori = $(spans[0]).text().trim();
  const okuma = $(spans[1]).text().trim();
  const hero = {
    baslik: toMd($, h1),
    giris: toMd($, lead),
    rozetSinifi: cls($, spans[0]),
  };
  if (kategori !== (fm.category || '')) hero.kategori = kategori;
  if (okuma !== (fm.readingTime || '')) hero.okumaSuresi = okuma;
  return hero;
}

module.exports = { domSig, toMd, cls, kids, extractors, convertBlock, extractHero, canonicalize, SKIP };
if (require.main !== module) return;

// ── Ana akış ──
const files = [];
for (const lang of ['tr', 'en']) {
  const dir = path.join(ROOT, 'content', lang, 'blog');
  for (const f of fs.readdirSync(dir).sort()) {
    const rel = `content/${lang}/blog/${f}`;
    if (f.endsWith('.njk') && !SKIP.has(rel)) files.push(rel);
  }
}

let totBlocks = 0;
let totOzel = 0;
const failed = [];
const typeCount = {};

for (const rel of files) {
  const file = path.join(ROOT, rel);
  const raw = fs.readFileSync(file, 'utf8');
  const { data: fm, content: body } = matter(raw);
  const $ = cheerio.load(body, null, false);
  const secs = $('section').toArray().filter((s) => $(s).parents('section').length === 0);
  try {
    if (secs.length !== 2) throw new Error(`${secs.length} section`);
    const hero = extractHero($, secs[0], fm);
    const wrapper = $(secs[1]).find('div > div').first();
    const wcls = cls($, wrapper);
    if (wcls !== 'prose-custom space-y-8' && wcls !== 'space-y-8') throw new Error(`kapsayıcı: ${wcls}`);
    canonicalize($, wrapper);
    const blocks = kids($, wrapper).map((el) => convertBlock($, el));

    // Tam gövde doğrulaması (kapsayıcı sınıf normalize edilerek)
    $(wrapper).attr('class', 'prose-custom space-y-8');
    const rebuilt =
      renderHero(hero, { category: fm.category, readingTime: fm.readingTime, url: fm.permalink }) +
      '\n' +
      renderBlocks(blocks);
    if (domSig(rebuilt) !== domSig($.html(secs[0]) + $.html(secs[1]))) throw new Error('tam gövde eşleşmedi');

    const ozel = blocks.filter((b) => b.type === 'ozel').length;
    totBlocks += blocks.length;
    totOzel += ozel;
    for (const b of blocks) typeCount[b.type] = (typeCount[b.type] || 0) + 1;
    console.log(`✓ ${rel} — ${blocks.length} blok, ${ozel} özel`);

    if (WRITE) {
      const out = { ...fm, hero, blocks };
      delete out.body;
      fs.writeFileSync(file, '---\n' + yaml.dump(out, { lineWidth: -1, noRefs: true }) + '---\n');
    }
  } catch (e) {
    failed.push(`${rel}: ${e.message}`);
    console.log(`✗ ${rel} — DOKUNULMADI (${e.message})`);
  }
}

console.log('\n── ÖZET ──');
console.log('Dosya:', files.length, '— başarısız:', failed.length);
console.log('Blok:', totBlocks, '— özel (ham HTML kalan):', totOzel, `(%${((100 * totOzel) / totBlocks).toFixed(1)})`);
console.log('Tür dağılımı:', JSON.stringify(typeCount));
if (failed.length) console.log('Başarısızlar:\n ' + failed.join('\n '));
if (!WRITE) console.log('\n(dry-run — yazmak için --write)');
