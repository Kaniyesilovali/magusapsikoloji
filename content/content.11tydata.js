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
