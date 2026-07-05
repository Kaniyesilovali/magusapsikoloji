/**
 * JSON-LD şema üreticileri.
 * Hem Eleventy filtreleri (eleventy.config.js) hem de migrasyon doğrulaması
 * (extract.js) aynı üreticileri kullanır — tek kaynak.
 */

function breadcrumb(items) {
  return {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: items.map((it, i) => ({
      '@type': 'ListItem',
      position: i + 1,
      name: it.name,
      item: it.item,
    })),
  };
}

function article({ headline, datePublished, dateModified, inLanguage, image, orgName, orgUrl }) {
  return {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline,
    publisher: { '@type': 'Organization', name: orgName, url: orgUrl },
    datePublished,
    dateModified,
    inLanguage,
    image: { '@type': 'ImageObject', url: image },
    author: { '@type': 'Organization', name: orgName, url: orgUrl },
  };
}

function faqPage(items) {
  return {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: items.map((it) => ({
      '@type': 'Question',
      name: it.q,
      acceptedAnswer: { '@type': 'Answer', text: it.a },
    })),
  };
}

module.exports = { breadcrumb, article, faqPage };
