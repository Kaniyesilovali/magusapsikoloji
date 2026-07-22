#!/usr/bin/env node
/**
 * check-live.js — canlı sitenin SEO sağlığı (npm run check:live).
 *
 * check-site.js'den farkı: o, build çıktısını deploy'dan ÖNCE doğrular
 * (kırık iç link, hreflang karşılıklılığı, sitemap bütünlüğü) ve hata varsa
 * deploy'u durdurur. Bu betik ise yayında GERÇEKTE servis edileni ölçer —
 * 404 veren varlıklar, Cloudflare'ın araya girmesi, canlıda kalmış placeholder
 * gibi build'in göremeyeceği sorunlar buradan çıkar.
 *
 * Çıkış kodu: sorun varsa 1. CI'da değil, elle çalıştırmak için tasarlandı;
 * her koşuda ~80 istek atar.
 */
const SITE = process.env.SITE_URL || 'https://magusapsikoloji.com';
const CONCURRENCY = 6;
const TITLE_MAX = 60;   // Google ~600px'te keser, kabaca 60 karakter
const DESC_MAX = 160;
const DESC_MIN = 70;

/** Sabit sayıda eşzamanlı istek — siteyi yormadan tarar. */
async function pool(items, worker) {
  const out = new Array(items.length);
  let next = 0;
  await Promise.all(
    Array.from({ length: Math.min(CONCURRENCY, items.length) }, async () => {
      while (next < items.length) {
        const i = next++;
        try {
          out[i] = await worker(items[i]);
        } catch (e) {
          out[i] = { error: String((e && e.message) || e) };
        }
      }
    })
  );
  return out;
}

const attr = (tag, name) => {
  const m = tag.match(new RegExp(name + '\\s*=\\s*"([^"]*)"', 'i'));
  return m ? m[1] : null;
};

const decode = (s) =>
  (s || '')
    .replace(/&amp;/g, '&')
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .replace(/&quot;/g, '"')
    .replace(/&#39;/g, "'")
    .replace(/&nbsp;/g, ' ')
    .trim();

const problems = [];
const fail = (line) => { problems.push(line); console.log('  ✗ ' + line); };
const ok = (line) => console.log('  ✓ ' + line);

async function main() {
  console.log(`Canlı tarama: ${SITE}\n`);

  const sitemap = await (await fetch(SITE + '/sitemap.xml')).text();
  const urls = [...sitemap.matchAll(/<loc>([^<]+)<\/loc>/g)].map((m) => m[1]);
  if (urls.length === 0) {
    console.error('sitemap.xml okunamadı ya da boş.');
    process.exit(1);
  }

  const pages = await pool(urls, async (url) => {
    const res = await fetch(url, { redirect: 'manual' });
    const html = res.status === 200 ? await res.text() : '';
    const cut = html.indexOf('</head>');
    const head = cut === -1 ? html.slice(0, 20000) : html.slice(0, cut + 7);

    const titleM = head.match(/<title[^>]*>([\s\S]*?)<\/title>/i);
    const descTag = (head.match(/<meta[^>]+name\s*=\s*"description"[^>]*>/i) || [])[0];

    const hreflang = {};
    for (const tag of head.match(/<link[^>]+hreflang[^>]*>/gi) || []) {
      hreflang[(attr(tag, 'hreflang') || '').toLowerCase()] = attr(tag, 'href');
    }

    // Sayfadan referans verilen her yerel varlık: <img>, og:image, ikonlar ve
    // JSON-LD içine gömülü mutlak adresler (schema logo'su burada geçiyor).
    const assets = new Set();
    for (const m of html.matchAll(/<img[^>]+src\s*=\s*"([^"]+)"/gi)) assets.add(m[1]);
    for (const m of head.matchAll(/(?:content|href)\s*=\s*"([^"]+\.(?:jpg|jpeg|png|svg|webp|ico|gif))"/gi)) assets.add(m[1]);
    for (const m of html.matchAll(/https?:\/\/[^"' ]+\.(?:jpg|jpeg|png|svg|webp|ico|gif)/gi)) assets.add(m[0]);

    return {
      url,
      status: res.status,
      location: res.headers.get('location'),
      title: decode(titleM && titleM[1]),
      desc: decode(descTag && attr(descTag, 'content')),
      hreflang,
      assets: [...assets],
      waNumbers: [...html.matchAll(/wa\.me\/([0-9A-Za-z]+)/g)].map((m) => m[1]),
    };
  });

  // ── Erişilebilirlik ────────────────────────────────────────────
  console.log(`── Sitemap URL'leri (${pages.length}) ──`);
  const unreachable = pages.filter((p) => p.status !== 200);
  unreachable.forEach((p) => fail(`HTTP ${p.status} — ${p.url}${p.location ? ' → ' + p.location : ''}`));
  if (!unreachable.length) ok('hepsi 200');

  // ── Görsel ve ikon varlıkları ──────────────────────────────────
  const assetUrls = [...new Set(pages.flatMap((p) => p.assets))]
    .filter((s) => !s.startsWith('data:'))
    .map((s) => (s.startsWith('http') ? s : SITE + (s.startsWith('/') ? s : '/' + s)))
    .filter((s) => s.startsWith(SITE));

  console.log(`\n── Görsel/ikon varlıkları (${assetUrls.length}) ──`);
  const assetRes = await pool(assetUrls, async (u) => ({ u, status: (await fetch(u, { method: 'HEAD' })).status }));
  const brokenAssets = assetRes.filter((r) => r.status !== 200);
  brokenAssets.forEach((r) => fail(`HTTP ${r.status} — ${r.u.replace(SITE, '')}`));
  if (!brokenAssets.length) ok('hepsi 200');

  // ── İletişim placeholder'ları ──────────────────────────────────
  // Canlıda kalmış placeholder, build'i kırmadığı için sessizce yayında kalır.
  console.log('\n── wa.me numaraları ──');
  const numbers = new Map();
  for (const p of pages) for (const n of p.waNumbers) numbers.set(n, (numbers.get(n) || 0) + 1);
  if (!numbers.size) ok('sayfalarda wa.me linki yok');
  for (const [number, count] of [...numbers].sort((a, b) => b[1] - a[1])) {
    const invalid = /[^0-9]/.test(number) || number.length < 10 || number.length > 15;
    invalid ? fail(`geçersiz numara "${number}" — ${count} sayfada`) : ok(`${number} — ${count} sayfada`);
  }

  // ── hreflang ───────────────────────────────────────────────────
  console.log('\n── hreflang ──');
  const missing = pages.filter((p) => p.status === 200 && !p.hreflang['x-default']);
  missing.forEach((p) => fail(`x-default yok — ${p.url.replace(SITE, '')}`));
  if (!missing.length) ok(`x-default her sayfada var (${new Set(pages.map((p) => p.hreflang['x-default'])).size} küme)`);

  // ── Uzunluklar (uyarı; deploy'u durdurmaz) ─────────────────────
  console.log('\n── Title / description uzunlukları (uyarı) ──');
  const longTitles = pages.filter((p) => p.title.length > TITLE_MAX);
  const badDescs = pages.filter((p) => p.desc && (p.desc.length > DESC_MAX || p.desc.length < DESC_MIN));
  longTitles
    .sort((a, b) => b.title.length - a.title.length)
    .forEach((p) => console.log(`  ! title ${p.title.length} — ${p.url.replace(SITE, '')}`));
  badDescs.forEach((p) => console.log(`  ! description ${p.desc.length} — ${p.url.replace(SITE, '')}`));
  const noDesc = pages.filter((p) => p.status === 200 && !p.desc);
  noDesc.forEach((p) => fail(`description yok — ${p.url.replace(SITE, '')}`));
  if (!longTitles.length && !badDescs.length && !noDesc.length) ok('hepsi sınırlar içinde');

  // ── Bot erişimi ────────────────────────────────────────────────
  // Cloudflare'ın bot yönetimi robots.txt'yi sarmalayabilir; repodaki dosya
  // temiz olsa bile canlıda engel çıkabilir, o yüzden gerçek istekle bakılır.
  console.log('\n── Bot erişimi ──');
  for (const bot of ['Googlebot/2.1', 'GPTBot/1.0', 'ClaudeBot/1.0', 'PerplexityBot/1.0']) {
    const res = await fetch(SITE + '/', { headers: { 'User-Agent': `Mozilla/5.0 (compatible; ${bot})` } });
    res.status === 200 ? ok(`${bot} → 200`) : fail(`${bot} → HTTP ${res.status} (engelleniyor)`);
  }

  console.log(
    problems.length
      ? `\n${problems.length} sorun bulundu.`
      : '\nSorun bulunamadı.'
  );
  process.exit(problems.length ? 1 : 0);
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
