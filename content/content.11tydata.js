/**
 * translationKey → TR/EN eş sayfa eşleşmesi.
 * hreflang etiketleri, nav dil düğmesi ve sitemap alternates buradan üretilir.
 *
 * Migre edilen sayfalarda `hreflangOverride` (orijinal URL'ler) varsa o kullanılır;
 * CMS ile eklenen yeni sayfalarda otomatik hesaplanır — TR/EN kayması yaşanmaz.
 */
module.exports = {
  eleventyComputed: {
    counterpartUrl: (data) => {
      if (!data.translationKey || !data.collections || !data.collections.all) return null;
      const other = data.lang === 'tr' ? 'en' : 'tr';
      const match = data.collections.all.find(
        (p) => p.data.translationKey === data.translationKey && p.data.lang === other
      );
      return match ? match.url : null;
    },
    langToggleUrl: (data) => {
      if (data.counterpartUrl) return data.counterpartUrl;
      return data.lang === 'tr' ? '/en/' : '/';
    },
    // Sayfanın SSS listesi tek yerden üretilir: önce sayfaya özel `faq`,
    // ardından paylaşılan `faqTopic` başlığının ilk 4 sorusu. Aynı soru iki kez
    // yazılmaz.
    //
    // Neden tek liste: `faq` frontmatter'ı eskiden yalnızca head'e JSON-LD
    // basıyordu, sayfada hiçbir yerde görünmüyordu — 28 sayfada görünmeyen
    // FAQPage işaretlemesi vardı. Google yapılandırılmış verinin sayfada
    // görünür olmasını şart koşuyor; ayrıca hem `faq` hem `faqTopic` taşıyan
    // sayfalarda iki ayrı FAQPage varlığı oluşuyordu. Artık liste hem görünür
    // bölümü hem JSON-LD'yi besliyor: tek varlık, tamamı görünür.
    faqItems: (data) => {
      const out = [];
      const seen = new Set();
      const add = (items) => {
        for (const item of items || []) {
          const q = String((item && item.q) || '').trim();
          const key = q.toLowerCase();
          if (!q || seen.has(key)) continue;
          seen.add(key);
          out.push(item);
        }
      };
      add(data.faq);
      const topic = data.faqTopic && data.faqdata && data.faqdata.topics[data.faqTopic];
      add(topic && topic[data.lang] ? topic[data.lang].slice(0, 4) : null);
      return out;
    },
    hreflangs: (data) => {
      // x-default her kümede sayfanın TR (varsayılan dil) sürümüne işaret eder;
      // TR eşi olmayan sayfalarda sayfanın kendisine düşer.
      if (data.hreflangOverride) {
        const o = data.hreflangOverride;
        return { ...o, xDefault: o.tr || o.en };
      }
      const self = data.site.url + data.page.url;
      const alt = data.counterpartUrl ? data.site.url + data.counterpartUrl : null;
      const tr = data.lang === 'tr' ? self : alt;
      return {
        tr,
        en: data.lang === 'en' ? self : alt,
        xDefault: tr || self,
      };
    },
  },
};
