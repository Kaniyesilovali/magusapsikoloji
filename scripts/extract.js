#!/usr/bin/env node
/**
 * extract.js — mevcut HTML sayfalarını Eleventy içerik dosyalarına dönüştürür.
 *
 * Modlar:
 *   node scripts/extract.js --chrome     # nav/footer/gdpr/whatsapp include'larını üretir
 *   node scripts/extract.js              # tüm sayfaları content/ altına migre eder
 *   node scripts/extract.js blog/x.html  # sadece verilen sayfaları migre eder
 *
 * Gövdeler HTML olarak verbatim korunur; head meta/schema'lar front matter'a taşınır.
 * Şema üretimi scripts/schemas.js ile doğrulanır — birebir üretilemeyen şemalar
 * rawSchemas olarak saklanır (diff garantisi).
 */

const fs = require('fs');
const path = require('path');
const cheerio = require('cheerio');
const yaml = require('js-yaml');
const schemas = require('./schemas');

const ROOT = path.join(__dirname, '..');
const SITE_URL = 'https://magusapsikoloji.com';
const OG_IMAGE_DEFAULT = SITE_URL + '/assets/images/og-image.jpg';

const warns = [];
function warn(file, msg) { warns.push(`${file}: ${msg}`); }

function load(rel) {
  return cheerio.load(fs.readFileSync(path.join(ROOT, rel), 'utf8'));
}

function sortKeys(o) {
  if (Array.isArray(o)) return o.map(sortKeys);
  if (o && typeof o === 'object') {
    return Object.keys(o).sort().reduce((acc, k) => { acc[k] = sortKeys(o[k]); return acc; }, {});
  }
  return o;
}
function deepEq(a, b) { return JSON.stringify(sortKeys(a)) === JSON.stringify(sortKeys(b)); }

// ─────────────────────────────────────────────────────────────
// CHROME MODU
// ─────────────────────────────────────────────────────────────
function extractChrome() {
  const out = (name, html) =>
    fs.writeFileSync(path.join(ROOT, '_includes/chrome', name), html.trim() + '\n');

  // Nav + footer: minimal chrome'lu blog sayfalarından
  for (const [lang, srcPage, toggleText] of [
    ['tr', 'blog/anksiyete-nedir.html', 'EN'],
    ['en', 'en/blog/what-is-anxiety.html', 'TR'],
  ]) {
    const $ = load(srcPage);

    const $nav = $('nav#navbar');
    if (!$nav.length) throw new Error(`nav bulunamadı: ${srcPage}`);
    // Dil düğmesi: karşı-dil linkini şablon değişkenine çevir
    let toggled = 0;
    $nav.find('a').each((_, el) => {
      if ($(el).text().trim() === toggleText) { $(el).attr('href', '__LANG_TOGGLE__'); toggled++; }
    });
    if (toggled !== 1) throw new Error(`${srcPage}: dil düğmesi ${toggled} kez bulundu (1 bekleniyordu)`);
    out(`nav-${lang}.njk`, $.html($nav).replace('__LANG_TOGGLE__', '{{ langToggleUrl }}'));

    const $footer = $('footer');
    if (!$footer.length) throw new Error(`footer bulunamadı: ${srcPage}`);
    out(`footer-${lang}.njk`, $.html($footer));
  }

  // GDPR banner+modal ve WhatsApp butonu: ana sayfalardan
  for (const [lang, srcPage] of [['tr', 'index.html'], ['en', 'en/index.html']]) {
    const $ = load(srcPage);
    const $banner = $('#cookieBanner');
    const $modal = $('#cookieModal');
    if (!$banner.length || !$modal.length) throw new Error(`GDPR blokları bulunamadı: ${srcPage}`);
    out(`gdpr-${lang}.njk`, $.html($banner) + '\n\n' + $.html($modal));

    const $wa = $('a[href^="https://wa.me"]').filter((_, el) => /position:\s*fixed/.test($(el).attr('style') || ''));
    if ($wa.length !== 1) throw new Error(`WhatsApp float butonu bulunamadı: ${srcPage}`);
    out(`whatsapp-${lang}.njk`, $.html($wa));
  }

  console.log('✓ chrome include\'ları üretildi (_includes/chrome/)');
}

// ─────────────────────────────────────────────────────────────
// SAYFA MİGRASYONU
// ─────────────────────────────────────────────────────────────
function listPages() {
  const pages = [];
  const walk = (dir) => {
    for (const e of fs.readdirSync(path.join(ROOT, dir || '.'), { withFileTypes: true })) {
      const rel = dir ? `${dir}/${e.name}` : e.name;
      if (e.isDirectory()) {
        if (['blog', 'hizmetler', 'en', 'en/blog', 'en/services'].includes(rel)) walk(rel);
      } else if (e.name.endsWith('.html')) {
        pages.push(rel);
      }
    }
  };
  walk('');
  return pages;
}

function contentPath(rel) {
  return rel.startsWith('en/') ? `content/${rel}` : `content/tr/${rel}`;
}

function urlPath(href) {
  // https://magusapsikoloji.com/blog/x.html → /blog/x.html
  return href.replace(SITE_URL, '') || '/';
}

// 1. geçiş: hreflang'lardan TR↔EN eşleşme haritası
function buildTranslationMap(pages) {
  const enToKey = {};
  for (const rel of pages) {
    if (rel.startsWith('en/')) continue;
    const $ = load(rel);
    const en = $('link[rel="alternate"][hreflang="en"]').attr('href');
    const key = rel.replace(/\.html$/, '').replace(/\/index$/, '');
    if (en) {
      const enPath = urlPath(en).replace(/^\//, '').replace(/\/$/, '/index.html');
      enToKey[enPath.endsWith('.html') ? enPath : enPath + 'index.html'] = key;
    }
  }
  return enToKey;
}

function extractPage(rel, enToKey) {
  const raw = fs.readFileSync(path.join(ROOT, rel), 'utf8');
  const $ = cheerio.load(raw);
  const isEn = rel.startsWith('en/');
  const fm = {};
  const norm = (s) => (s || '').replace(/\s+/g, ' ').trim();

  // Redirect stub'ları verbatim geçir
  if ($('meta[http-equiv="refresh"]').length) {
    return { fm: { permalink: '/' + rel, eleventyExcludeFromCollections: true }, body: raw };
  }

  // ── HEAD ──
  fm.title = norm($('title').text());
  fm.description = norm($('meta[name="description"]').attr('content'));
  const keywords = norm($('meta[name="keywords"]').attr('content'));
  if (keywords) fm.keywords = keywords;
  const robots = norm($('meta[name="robots"]').attr('content'));
  if (robots && robots !== 'index, follow') fm.robots = robots;

  const canonical = $('link[rel="canonical"]').attr('href');
  fm.permalink = '/' + rel;
  if (canonical && urlPath(canonical) !== '/' + rel && !('/' + rel).endsWith('/index.html')) {
    warn(rel, `canonical (${canonical}) dosya yolundan farklı`);
  }
  if (('/' + rel).endsWith('/index.html') && canonical && urlPath(canonical) === ('/' + rel).replace(/index\.html$/, '')) {
    fm.permalink = '/' + rel; // /blog/index.html → çıktı aynı, canonical /blog/ page.url'den gelir
  }

  // hreflang (orijinal değerler korunur)
  const hl = {};
  $('link[rel="alternate"][hreflang]').each((_, el) => {
    const l = $(el).attr('hreflang');
    if (l === 'tr') hl.tr = $(el).attr('href');
    else if (l === 'en') hl.en = $(el).attr('href');
    else if (l === 'x-default') hl.xDefault = $(el).attr('href');
    else warn(rel, `bilinmeyen hreflang: ${l}`);
  });
  if (Object.keys(hl).length) fm.hreflangOverride = hl;

  // translationKey
  if (!isEn) {
    fm.translationKey = rel.replace(/\.html$/, '').replace(/\/index$/, '');
  } else {
    const key = enToKey[rel.replace(/^en\//, 'en/')] || enToKey[rel];
    fm.translationKey = key || rel.replace(/\.html$/, '').replace(/\/index$/, '');
    if (!key) warn(rel, 'TR eşi bulunamadı (translationKey EN yolundan üretildi)');
  }

  // OG / Twitter
  const metaP = (k) => norm($(`meta[property="${k}"]`).attr('content'));
  const metaN = (k) => norm($(`meta[name="${k}"]`).attr('content'));
  const ogType = metaP('og:type');
  if (ogType && ogType !== 'website') fm.ogType = ogType;
  const ogUrl = metaP('og:url');
  if (ogUrl && urlPath(ogUrl) !== urlPath(canonical || ogUrl)) warn(rel, `og:url canonical'dan farklı: ${ogUrl}`);
  const ogTitle = metaP('og:title');
  if (ogTitle && ogTitle !== fm.title) fm.ogTitle = ogTitle;
  const ogDesc = metaP('og:description');
  if (ogDesc && ogDesc !== fm.description) fm.ogDescription = ogDesc;
  const ogImage = metaP('og:image');
  if (ogImage && ogImage !== OG_IMAGE_DEFAULT) fm.ogImage = ogImage;
  const ogLocale = metaP('og:locale');
  if (ogLocale) fm.ogLocale = ogLocale;
  const twCard = metaN('twitter:card');
  if (!twCard) fm.noTwitter = true;
  else if (twCard !== 'summary_large_image') fm.twitterCard = twCard;
  const twTitle = metaN('twitter:title');
  if (twTitle && twTitle !== (fm.ogTitle || fm.title)) fm.twitterTitle = twTitle;
  const twDesc = metaN('twitter:description');
  if (twDesc && twDesc !== (fm.ogDescription || fm.description)) fm.twitterDescription = twDesc;
  const twImage = metaN('twitter:image');
  if (twImage && twImage !== (fm.ogImage || OG_IMAGE_DEFAULT)) fm.twitterImage = twImage;

  // Tanınmayan og:/twitter:/article: metaları verbatim taşı
  const knownP = ['og:type', 'og:url', 'og:title', 'og:description', 'og:image', 'og:locale'];
  const knownN = ['twitter:card', 'twitter:title', 'twitter:description', 'twitter:image',
    'description', 'keywords', 'robots', 'viewport'];
  const metaExtra = [];
  $('head meta').each((_, el) => {
    const p = $(el).attr('property');
    const n = $(el).attr('name');
    if (p && !knownP.includes(p)) metaExtra.push({ attr: 'property', key: p, content: $(el).attr('content') || '' });
    else if (n && !knownN.includes(n)) metaExtra.push({ attr: 'name', key: n, content: $(el).attr('content') || '' });
  });
  if (metaExtra.length) fm.metaExtra = metaExtra;

  // Head içi <style> blokları
  const styles = [];
  $('head style').each((_, el) => styles.push(`<style>${$(el).html()}</style>`));
  if (styles.length) fm.headExtra = styles.join('\n');

  // ── JSON-LD (yalnız head içindekiler; gövdedekiler verbatim kalır) ──
  const rawSchemas = [];
  $('head script[type="application/ld+json"]').each((_, el) => {
    let obj;
    try { obj = JSON.parse($(el).html()); } catch (e) {
      warn(rel, 'JSON-LD parse edilemedi, raw saklandı');
      rawSchemas.push(norm($(el).html()));
      return;
    }
    if (obj['@type'] === 'BreadcrumbList') {
      const items = (obj.itemListElement || []).map((it) => ({ name: it.name, item: it.item }));
      if (deepEq(schemas.breadcrumb(items), obj)) { fm.breadcrumb = items; return; }
    } else if (obj['@type'] === 'Article') {
      const a = {
        headline: obj.headline,
        datePublished: obj.datePublished,
        dateModified: obj.dateModified,
        inLanguage: obj.inLanguage,
        image: obj.image && obj.image.url,
        orgName: obj.publisher && obj.publisher.name,
        orgUrl: obj.publisher && obj.publisher.url,
      };
      if (deepEq(schemas.article(a), obj)) { fm.articleSchema = a; return; }
    } else if (obj['@type'] === 'FAQPage') {
      const items = (obj.mainEntity || []).map((q) => ({ q: q.name, a: q.acceptedAnswer && q.acceptedAnswer.text }));
      if (deepEq(schemas.faqPage(items), obj)) { fm.faq = items; return; }
    }
    warn(rel, `şema üreticiyle birebir eşleşmedi (${obj['@type']}), rawSchemas'a alındı`);
    rawSchemas.push(JSON.stringify(obj));
  });
  if (rawSchemas.length) fm.rawSchemas = rawSchemas;

  // ── GÖVDE ──
  const $main = $('main');
  if (!$main.length) {
    warn(rel, '<main> yok — body içeriği olduğu gibi alındı (elle kontrol edin)');
    return { fm, body: $('body').html() };
  }

  // FAQ widget → faqTopic
  const $faqDiv = $main.find('#blog-faq');
  if ($faqDiv.length) {
    fm.faqTopic = $faqDiv.attr('data-topic');
    $faqDiv.remove();
    $main.find('script[src*="faq.js"]').remove();
  }

  // Related/CTA sökümü yalnızca blog ve hizmet sayfalarında (index'ler hariç) —
  // diğer sayfalarda benzer görünümlü bölümler yanlışlıkla sökülmesin
  const strippable = /(^|\/)(blog|hizmetler|services)\//.test(rel) && !rel.endsWith('index.html');

  // İlgili yazılar bölümü → related
  if (strippable) $main.children('section').each((_, sec) => {
    const $sec = $(sec);
    const $h2 = $sec.find('h2').first();
    if (!/\btext-xl\b/.test($h2.attr('class') || '')) return;
    const cards = $sec.find('a');
    const items = [];
    cards.each((_, a) => {
      const ps = $(a).find('p');
      if (ps.length === 2) items.push({ url: $(a).attr('href'), category: norm(ps.eq(0).text()), title: norm(ps.eq(1).text()) });
    });
    if (items.length && items.length === cards.length) {
      fm.relatedHeading = norm($h2.text());
      fm.related = items;
      $sec.remove();
    }
  });

  // CTA bölümü → cta (yalnız şablonla birebir aynı yapıysa)
  if (strippable) $main.children('section').each((_, sec) => {
    const $sec = $(sec);
    if (!/\bbg-primary\b/.test($sec.attr('class') || '')) return;
    const $a = $sec.find('a');
    const $h2 = $sec.find('h2');
    const $p = $sec.find('p');
    if ($a.length === 1 && $h2.length === 1 && $p.length === 1) {
      const secText = norm($sec.text());
      const parts = norm($h2.text() + ' ' + $p.text() + ' ' + $a.text());
      if (secText === parts) {
        fm.cta = { heading: norm($h2.text()), sub: norm($p.text()), label: norm($a.text()), href: $a.attr('href') };
        if ($a.attr('target') === '_blank') fm.cta.external = true;
        $sec.remove();
      }
    }
  });

  // Blog hero'dan bilgi alanları (gövde değişmez, sadece okunur)
  if (/(^|\/)blog\//.test(rel) && !rel.endsWith('index.html')) {
    const $hero = $main.children('section').first();
    const $badge = $hero.find('span[class*="rounded-full"]').first();
    if ($badge.length) fm.category = norm($badge.text());
    const $rt = $hero.find('span[class*="text-ink-light"]').first();
    if ($rt.length) fm.readingTime = norm($rt.text());
    if (fm.articleSchema) {
      fm.datePublished = fm.articleSchema.datePublished;
      fm.dateModified = fm.articleSchema.dateModified;
    }
  }

  fm.layout = (fm.faqTopic || fm.related || fm.cta) ? 'post.njk' : 'page.njk';

  return { fm, body: $main.html() };
}

function writeContent(rel, fm, body) {
  const dest = path.join(ROOT, contentPath(rel));
  fs.mkdirSync(path.dirname(dest), { recursive: true });
  const yamlStr = yaml.dump(fm, { lineWidth: -1, noRefs: true });
  fs.writeFileSync(dest, `---\n${yamlStr}---\n${body.trim()}\n`);
}

// ─────────────────────────────────────────────────────────────
function main() {
  const args = process.argv.slice(2);
  if (args.includes('--chrome')) { extractChrome(); return; }

  const all = listPages();
  const targets = args.length ? args : all.filter((p) => p !== 'index.html' && p !== 'en/index.html');
  const enToKey = buildTranslationMap(all);

  for (const rel of targets) {
    const { fm, body } = extractPage(rel, enToKey);
    writeContent(rel, fm, body);
    console.log(`✓ ${rel} → ${contentPath(rel)}`);
  }

  if (warns.length) {
    console.log('\n⚠ Uyarılar:');
    for (const w of warns) console.log('  - ' + w);
  }
}

main();
