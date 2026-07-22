const schemas = require('./scripts/schemas');
const blocks = require('./scripts/render-blocks');

module.exports = function (eleventyConfig) {
  // Statik dosyalar olduğu gibi kopyalanır
  eleventyConfig.addPassthroughCopy('assets');
  eleventyConfig.addPassthroughCopy('dist');
  eleventyConfig.addPassthroughCopy({ 'static/robots.txt': 'robots.txt' });
  eleventyConfig.addPassthroughCopy({ 'static/.htaccess': '.htaccess' });
  eleventyConfig.addPassthroughCopy({ admin: 'admin' });
  // PHP yönetim paneli: kaynak repoda durur, site ile aynı FTP hattından yayınlanır.
  // Sırlar panel/ dizininin dışındaki config dosyasında olduğu için buraya kopyalanmaz.
  eleventyConfig.addPassthroughCopy({ panel: 'panel' });

  eleventyConfig.setTemplateFormats(['html', 'md', 'njk']);

  // JSON-LD filtreleri — scripts/schemas.js tek kaynak
  eleventyConfig.addFilter('jsonldBreadcrumb', (items) => JSON.stringify(schemas.breadcrumb(items)));
  eleventyConfig.addFilter('jsonldArticle', (o) => JSON.stringify(schemas.article(o)));
  eleventyConfig.addFilter('jsonldFaq', (items) => JSON.stringify(schemas.faqPage(items)));
  eleventyConfig.addFilter('flattenFaq', (categories) => categories.flatMap((c) => c.items));

  // Blog gövdesi: frontmatter'daki hero + bloklar → HTML (scripts/render-blocks.js)
  eleventyConfig.addFilter('renderHero', (hero, category, readingTime, url) =>
    blocks.renderHero(hero, { category, readingTime, url }));
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
