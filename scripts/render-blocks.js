/**
 * render-blocks.js — blog yazısı hero + içerik bloklarını HTML'e çevirir.
 *
 * Tek kaynak: hem Eleventy build'i (eleventy.config.js filtreleri) hem de
 * convert-to-blocks.js dönüştürücüsünün birebirlik doğrulaması bu modülü
 * kullanır. Buradaki sınıf dizgileri mevcut yazılardan çıkarılmıştır;
 * değiştirilirse tüm blog yazılarının görünümü değişir.
 *
 * Metin alanları Markdown'dır (yalnızca satır içi: **kalın**, *italik*,
 * [bağlantı](url)); paragraflar boş satırla ayrılır.
 */
const MarkdownIt = require('markdown-it');

const md = new MarkdownIt({ html: false, linkify: false, typographer: false });
// Satır içi etiketlere sitenin sınıflarını ekle
md.renderer.rules.strong_open = () => '<strong class="text-ink">';
md.renderer.rules.link_open = (tokens, idx) =>
  `<a href="${md.utils.escapeHtml(tokens[idx].attrGet('href') || '')}" class="text-primary underline hover:no-underline">`;

const esc = (s) =>
  String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

const inline = (s) => md.renderInline(String(s ?? ''));

// Markdown metnini boş satırlardan paragraflara böler
const paras = (s) => String(s ?? '').split(/\n\s*\n/).map((p) => p.trim()).filter(Boolean);

// ── Parça üreticiler ──────────────────────────────────────
const H2 = (t) => `<h2 class="font-serif text-2xl text-ink mb-4">${inline(t)}</h2>`;

function proseParas(text, { lastMb = false } = {}) {
  const ps = paras(text);
  return ps
    .map((p, i) => {
      const last = i === ps.length - 1 && !lastMb;
      return `<p class="text-ink-muted leading-relaxed${last ? '' : ' mb-4'}">${inline(p)}</p>`;
    })
    .join('\n');
}

const CHECK_SVG =
  '<svg class="w-4 h-4 text-primary shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4"></path></svg>';

// Bilgi kutusu ton → tam sınıf dizgisi (Tailwind taraması için literal kalmalı)
const TONE_BOX = {
  yesil: 'bg-primary/5 rounded-2xl p-6 border border-primary/15',
  turuncu: 'bg-accent/5 rounded-2xl p-6 border border-accent/15',
};
const GRID_COLS = { 2: 'grid sm:grid-cols-2 gap-4', 3: 'grid sm:grid-cols-3 gap-4' };

// ── Blok türleri ──────────────────────────────────────────
const renderers = {
  // Başlık + paragraflar
  metin(b) {
    const parts = [];
    if (b.baslik) parts.push(H2(b.baslik));
    if (b.metin) parts.push(proseParas(b.metin));
    return `<div>\n${parts.join('\n')}\n</div>`;
  },

  // Renkli bilgi kutusu (tek paragraf)
  'bilgi-kutusu'(b) {
    return (
      `<div class="${TONE_BOX[b.ton] || TONE_BOX.yesil}">\n` +
      `<p class="text-sm text-ink font-medium mb-2">${inline(b.baslik)}</p>\n` +
      `<p class="text-sm text-ink-muted leading-relaxed">${inline(b.metin)}</p>\n` +
      `</div>`
    );
  },

  // Onay işaretli liste (başlık + açıklamalı maddeler)
  'onayli-liste'(b) {
    const items = (b.maddeler || [])
      .map((m) => {
        const inner = m.aciklama
          ? `<div>\n<p class="text-sm font-medium text-ink">${inline(m.baslik)}</p>\n<p class="text-xs text-ink-muted mt-0.5">${inline(m.aciklama)}</p>\n</div>`
          : `<div>\n<p class="text-sm font-medium text-ink">${inline(m.baslik)}</p>\n</div>`;
        return `<li class="flex items-start gap-3">\n${CHECK_SVG}\n${inner}\n</li>`;
      })
      .join('\n');
    const parts = [];
    if (b.baslik) parts.push(H2(b.baslik));
    if (b.giris) parts.push(proseParas(b.giris, { lastMb: true }));
    parts.push(`<ul class="space-y-3">\n${items}\n</ul>`);
    if (b.kapanis) parts.push(`<p class="text-ink-muted leading-relaxed mt-4">${inline(b.kapanis)}</p>`);
    return `<div>\n${parts.join('\n')}\n</div>`;
  },

  // Yan yana karşılaştırma kartları (kart = başlık + madde listesi)
  'kart-grid'(b) {
    const grid = GRID_COLS[Number(b.kolon)] || GRID_COLS[3];
    const cards = (b.kartlar || [])
      .map((k) => {
        const lis = (k.maddeler || []).map((m) => `<li>• ${inline(m)}</li>`).join('\n');
        return (
          `<div class="bg-warm rounded-xl p-4 border border-warm-tertiary">\n` +
          `<p class="text-xs font-medium text-ink mb-3">${inline(k.baslik)}</p>\n` +
          `<ul class="text-xs text-ink-muted space-y-1.5">\n${lis}\n</ul>\n</div>`
        );
      })
      .join('\n');
    const parts = [];
    if (b.baslik) parts.push(H2(b.baslik));
    if (b.giris) parts.push(proseParas(b.giris, { lastMb: true }));
    parts.push(`<div class="${grid}">\n${cards}\n</div>`);
    return `<div>\n${parts.join('\n')}\n</div>`;
  },

  // Alt alta başlıklı kartlar
  'kart-yigini'(b) {
    const cards = (b.kartlar || [])
      .map(
        (k) =>
          `<div class="bg-warm rounded-xl p-5 border border-warm-tertiary">\n` +
          `<h3 class="font-semibold text-ink text-sm mb-1.5">${inline(k.baslik)}</h3>\n` +
          `<p class="text-xs text-ink-muted leading-relaxed">${inline(k.metin)}</p>\n</div>`
      )
      .join('\n');
    const parts = [];
    if (b.baslik) parts.push(H2(b.baslik));
    if (b.giris) parts.push(proseParas(b.giris, { lastMb: true }));
    parts.push(`<div class="space-y-3">\n${cards}\n</div>`);
    return `<div>\n${parts.join('\n')}\n</div>`;
  },

  // Numaralı adımlar (yeşil daire içinde sıra numarası)
  adimlar(b) {
    const steps = (b.adimlar || [])
      .map(
        (a, i) =>
          `<div class="flex gap-4 items-start">\n` +
          `<div class="w-9 h-9 rounded-full bg-primary flex items-center justify-center text-white text-sm font-bold shrink-0">${i + 1}</div>\n` +
          `<div>\n<h2 class="font-semibold text-ink mb-2">${inline(a.baslik)}</h2>\n` +
          `<p class="text-sm text-ink-muted leading-relaxed">${inline(a.metin)}</p>\n</div>\n</div>`
      )
      .join('\n');
    const stack = `<div class="space-y-5">\n${steps}\n</div>`;
    if (!b.baslik && !b.giris) return stack;
    const parts = [];
    if (b.baslik) parts.push(H2(b.baslik));
    if (b.giris) parts.push(proseParas(b.giris, { lastMb: true }));
    parts.push(stack);
    return `<div>\n${parts.join('\n')}\n</div>`;
  },

  // Numaralı kısa maddeler (daire numara + tek cümle kartları)
  'numarali-liste'(b) {
    const renk = b.stil === 'koyu' ? 'bg-primary text-white' : 'bg-primary/10 text-primary';
    const items = (b.maddeler || [])
      .map(
        (m, i) =>
          `<div class="flex gap-4 bg-warm rounded-xl p-4 border border-warm-tertiary">\n` +
          `<span class="w-7 h-7 rounded-full ${renk} text-xs font-semibold flex items-center justify-center shrink-0">${i + 1}</span>\n` +
          `<p class="text-sm text-ink-muted self-center">${inline(m)}</p>\n</div>`
      )
      .join('\n');
    const parts = [];
    if (b.baslik) parts.push(H2(b.baslik));
    if (b.giris) parts.push(proseParas(b.giris, { lastMb: true }));
    parts.push(`<div class="space-y-3">\n${items}\n</div>`);
    if (b.kapanis) parts.push(`<p class="text-sm text-ink-muted mt-4">${inline(b.kapanis)}</p>`);
    return `<div>\n${parts.join('\n')}\n</div>`;
  },

  // Serbest HTML (tasarımcı blokları — CMS'te "teknik" olarak işaretli)
  ozel(b) {
    return String(b.html ?? '');
  },
};

function renderBlocks(blocks) {
  if (!Array.isArray(blocks) || !blocks.length) return '';
  const inner = blocks
    .map((b) => {
      const fn = renderers[b.type];
      if (!fn) throw new Error(`Bilinmeyen blok türü: ${b.type}`);
      return fn(b);
    })
    .join('\n\n');
  return (
    `<section class="py-12 bg-white">\n<div class="max-w-3xl mx-auto px-5 lg:px-8">\n` +
    `<div class="prose-custom space-y-8">\n${inner}\n</div>\n</div>\n</section>`
  );
}

/**
 * Hero: kırıntı + kategori rozeti + başlık + giriş paragrafı.
 * opts: { category, readingTime, url } — sayfa frontmatter'ından gelir;
 * hero.kategori / hero.okumaSuresi doluysa onlar öncelikli (nadir farklar).
 */
function renderHero(hero, opts = {}) {
  if (!hero) return '';
  const category = hero.kategori || opts.category || '';
  const readingTime = hero.okumaSuresi || opts.readingTime || '';
  const en = String(opts.url || '').startsWith('/en/');
  const blogRoot = en ? '/en/blog/' : '/blog/';
  const badge = hero.rozetSinifi || 'text-xs bg-primary/10 text-primary font-medium px-3 py-1 rounded-full';
  return (
    `<section class="pt-32 pb-12 bg-warm">\n<div class="max-w-3xl mx-auto px-5 lg:px-8">\n` +
    `<div class="flex items-center gap-2 text-xs text-ink-muted mb-6">\n` +
    `<a href="${blogRoot}" class="hover:text-primary transition-colors">Blog</a>\n` +
    `<span>/</span><span>${esc(category)}</span>\n</div>\n` +
    `<div class="flex items-center gap-3 mb-5">\n` +
    `<span class="${esc(badge)}">${esc(category)}</span>\n` +
    `<span class="text-xs text-ink-light">${esc(readingTime)}</span>\n</div>\n` +
    `<h1 class="font-serif text-3xl lg:text-4xl text-ink leading-tight mb-5">${inline(hero.baslik)}</h1>\n` +
    `<p class="text-ink-muted text-lg leading-relaxed">${inline(hero.giris)}</p>\n</div>\n</section>`
  );
}

module.exports = { renderBlocks, renderHero, renderers };
