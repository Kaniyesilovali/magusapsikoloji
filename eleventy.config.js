const { execSync } = require('child_process');
const schemas = require('./scripts/schemas');
const blocks = require('./scripts/render-blocks');

const gitDates = require('./scripts/git-dates');

module.exports = function (eleventyConfig) {
  // Statik dosyalar olduğu gibi kopyalanır
  eleventyConfig.addPassthroughCopy('assets');
  eleventyConfig.addPassthroughCopy('dist');
  eleventyConfig.addPassthroughCopy({ 'static/robots.txt': 'robots.txt' });
  // Tarayıcılar /favicon.ico'yu kökten ister; assets/ altındakiler <link> ile bağlanıyor.
  eleventyConfig.addPassthroughCopy({ 'static/favicon.ico': 'favicon.ico' });
  eleventyConfig.addPassthroughCopy({ 'static/.htaccess': '.htaccess' });
  eleventyConfig.addPassthroughCopy({ admin: 'admin' });
  // CMS ekranına noindex başlığı — yalnız /admin/ dizinini etkiler.
  eleventyConfig.addPassthroughCopy({ 'static/admin.htaccess': 'admin/.htaccess' });
  // PHP yönetim paneli: kaynak repoda durur, site ile aynı FTP hattından yayınlanır.
  // Sırlar panel/ dizininin dışındaki config dosyasında olduğu için buraya kopyalanmaz.
  eleventyConfig.addPassthroughCopy({ panel: 'panel' });
  // Herkese açık onam formu: site kökünde duran tek PHP dosyası. Metni panelin
  // veritabanından okur — depoda ikinci bir kopya tutulmaz, yoksa panelde
  // kaydedilen metin ile sitedeki kopya arasında bir derleme boyu fark açılırdı.
  // Adres .htaccess'te uzantısız hâline bağlanıyor: /onam-formu, /en/consent-form.
  eleventyConfig.addPassthroughCopy({ 'static/onam-formu.php': 'onam-formu.php' });

  // llms.txt'nin "son yayın" satırı: dosya her derlemede yeniden üretildiği için
  // tarih de derleme anından gelir — içerik dosyalarında ayrıca elle tutulmaz.
  eleventyConfig.addGlobalData('buildDate', () => new Date().toISOString().slice(0, 10));

  // CSS sürüm damgası. Cloudflare /dist/output.css'i 7 gün önbellekliyor
  // (max-age=604800) ve HTML dinamik servis edildiği için deploy sonrası
  // eski stil dosyası ziyaretçilere günlerce servis edilebiliyordu.
  // İçerikten türetilen kısa özet her derlemede adresi değiştiriyor;
  // stil değişmediyse adres de değişmiyor, önbellek boşa harcanmıyor.
  eleventyConfig.addGlobalData('cssVersion', () => {
    try {
      const css = require('fs').readFileSync('./dist/output.css');
      return require('crypto').createHash('md5').update(css).digest('hex').slice(0, 8);
    } catch {
      return String(Date.now());
    }
  });

  eleventyConfig.setTemplateFormats(['html', 'md', 'njk']);

  // JSON-LD filtreleri — scripts/schemas.js tek kaynak
  eleventyConfig.addFilter('jsonldBreadcrumb', (items) => JSON.stringify(schemas.breadcrumb(items)));
  // Article.dateModified ve .image render anında geçersiz kılınır:
  // tarih git'ten (scripts/git-dates.js), görsel sayfanın kendi kart görselinden.
  // Frontmatter'daki dateModified elle güncellenmiyordu ve image tüm yazılarda
  // og-image.jpg'yi gösteriyordu — o dosya sunucuda 404.
  eleventyConfig.addFilter('jsonldArticle', (o, dateModified, image) =>
    JSON.stringify(schemas.article({
      ...o,
      dateModified: dateModified || o.dateModified,
      image: image || o.image,
    })));
  // Göreli yolu mutlak URL'ye çevirir; şema alanları tam adres ister.
  // 2026-09-07 → "7 Eylül 2026" / "7 September 2026"
  eleventyConfig.addFilter('trDate', (iso, lang) => {
    if (!iso) return '';
    const [y, m, d] = String(iso).split('-').map(Number);
    const aylar = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
    const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    const name = (lang === 'en' ? months : aylar)[m - 1];
    return `${d} ${name} ${y}`;
  });

  eleventyConfig.addFilter('absUrl', (u) =>
    !u ? null : (/^https?:\/\//.test(u) ? u : 'https://magusapsikoloji.com' + u));

  // SSS cevaplarında satır içi markdown (kaynak bağlantıları) render edilir.
  // Görünür bölüm ile FAQPage şeması aynı metni taşımalı — Google bunu şart koşuyor —
  // bu yüzden ikisi de aynı filtreden geçer. Google FAQ cevabında <a> etiketine izin verir.
  eleventyConfig.addFilter('mdInline', (s) => blocks.inline(s));
  eleventyConfig.addFilter('jsonldFaq', (items) => JSON.stringify(schemas.faqPage(
    items.map((it) => ({ ...it, a: blocks.inline(it.a) })))));
  eleventyConfig.addFilter('flattenFaq', (categories) => categories.flatMap((c) => c.items));

  // Frontmatter'daki kuruluş şemasına telefon / konum / çalışma saatleri /
  // Google İşletme Profili bağlantısı derleme anında eklenir (scripts/schemas.js).
  // Veri _data/contact.json'da tek yerde durur; yer tutucu değerler basılmaz.
  eleventyConfig.addFilter('orgEnrich', (raw, contact) => schemas.enrichRawSchema(raw, contact));

  // Aynı şemayı besleyen çalışma saatlerini sayfada görünür satırlara çevirir —
  // görünür içerik ile yapılandırılmış veri tek kaynaktan gelsin diye.
  // Kapalı günler `_data/contact.json`'da hiç yok; burada da uydurulmaz, basılmaz.
  eleventyConfig.addFilter('hoursLines', (hours, lang) => {
    if (!Array.isArray(hours) || !hours.length) return [];
    const order = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    const names = {
      tr: ['Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'],
      en: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
    };
    const label = names[lang] || names.en;
    return hours.map((h) => {
      const idx = [].concat(h.days || []).map((d) => order.indexOf(d))
        .filter((i) => i >= 0).sort((a, b) => a - b);
      if (!idx.length) return null;
      // Üç ve daha uzun kesintisiz dizi aralık olarak yazılır: "Pazartesi – Cuma"
      const run = idx.every((v, i) => i === 0 || v === idx[i - 1] + 1);
      const days = run && idx.length >= 3
        ? label[idx[0]] + ' – ' + label[idx[idx.length - 1]]
        : idx.map((i) => label[i]).join(', ');
      return { days, time: h.opens + '–' + h.closes };
    }).filter(Boolean);
  });

  // Sitemap <lastmod>: git tarihi ile yazarın beyan ettiği tarihten hangisi yeniyse o.
  // Hiçbiri yoksa null döner ve sitemap o URL için lastmod yazmaz — uydurmaktansa boş bırakılır.
  eleventyConfig.addFilter('lastmod', (page) =>
    gitDates.resolve(page.inputPath, page.data.dateModified, page.data.datePublished));

  // Blog gövdesi: frontmatter'daki hero + bloklar → HTML (scripts/render-blocks.js)
  eleventyConfig.addFilter('renderHero', (hero, category, readingTime, url, updated, updatedLabel) =>
    blocks.renderHero(hero, { category, readingTime, url, updated, updatedLabel }));
  eleventyConfig.addFilter('renderBlocks', (list) => blocks.renderBlocks(list));

  // Index kart sıralaması: cardOrder'sız yeni yazılar en üstte (yeni→eski),
  // ardından migre edilen kartlar orijinal el sıralamasıyla
  eleventyConfig.addFilter('indexSort', (pages) => {
    const withOrder = pages.filter((p) => p.data.cardOrder != null).sort((a, b) => a.data.cardOrder - b.data.cardOrder);
    const noOrder = pages.filter((p) => p.data.cardOrder == null)
      .sort((a, b) => String(b.data.datePublished || '').localeCompare(String(a.data.datePublished || '')));
    return [...noOrder, ...withOrder];
  });

  // Koleksiyonlar: blog ve hizmet sayfaları (index'ler hariç), tarihe göre yeni→eski
  const byDate = (a, b) => (b.data.datePublished || '').localeCompare(a.data.datePublished || '');
  eleventyConfig.addCollection('blog_tr', (api) =>
    api.getFilteredByGlob('content/tr/blog/*').filter((p) => !p.inputPath.endsWith('index.html') && !p.inputPath.endsWith('index.njk')).sort(byDate));
  eleventyConfig.addCollection('blog_en', (api) =>
    api.getFilteredByGlob('content/en/blog/*').filter((p) => !p.inputPath.endsWith('index.html') && !p.inputPath.endsWith('index.njk')).sort(byDate));
  eleventyConfig.addCollection('services_tr', (api) =>
    api.getFilteredByGlob('content/tr/hizmetler/*').filter((p) => !p.inputPath.endsWith('index.html') && !p.inputPath.endsWith('index.njk')));
  eleventyConfig.addCollection('services_en', (api) =>
    api.getFilteredByGlob('content/en/services/*').filter((p) => !p.inputPath.endsWith('index.html') && !p.inputPath.endsWith('index.njk')));

  return {
    // İçerik dosyaları .njk uzantılı (Sveltia CMS html+frontmatter kabul etmiyor);
    // gövdeler düz HTML olduğundan Nunjucks'tan değişmeden geçer.
    // Dikkat: içerik gövdesine {{ }} veya {% %} yazılmamalı.
    htmlTemplateEngine: false,
    markdownTemplateEngine: 'njk',
    dir: {
      input: 'content',
      includes: '../_includes',
      layouts: '../_layouts',
      data: '../_data',
      output: '_site',
    },
  };
};
