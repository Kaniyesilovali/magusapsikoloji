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


/**
 * Kuruluş şemasını Google İşletme Profili verisiyle zenginleştirir.
 *
 * Sayfaların frontmatter'ındaki kuruluş bloğu 38 dosyada tekrar ediyor. Telefon,
 * konum ve çalışma saatleri profil onaylandıkça gelecek; bunları 38 dosyaya elle
 * yazmak yerine derleme anında `_data/contact.json`dan ekliyoruz — tek kaynak.
 *
 * Alan yoksa ya da hâlâ yer tutucuysa (`5XX`, `XXX`) hiçbir şey yazılmaz:
 * eksik alan, uydurma alandan iyidir.
 */
function isPlaceholder(v) {
  return v == null || String(v).trim() === '' || /X{2,}/i.test(String(v));
}

function enrichOrganization(node, contact) {
  const types = [].concat(node['@type'] || []);
  const isOrg = String(node['@id'] || '').endsWith('#organization')
    || types.some((t) => t === 'LocalBusiness' || t === 'MedicalOrganization');
  if (!isOrg || !contact) return node;

  const out = { ...node };

  if (!isPlaceholder(contact.phoneE164) && !out.telephone) {
    out.telephone = contact.phoneE164;
  }

  if (!isPlaceholder(contact.postalCode) && out.address && typeof out.address === 'object') {
    out.address = { ...out.address, postalCode: String(contact.postalCode) };
  }

  if (!isPlaceholder(contact.latitude) && !isPlaceholder(contact.longitude) && !out.geo) {
    out.geo = {
      '@type': 'GeoCoordinates',
      latitude: Number(contact.latitude),
      longitude: Number(contact.longitude),
    };
  }

  // [{ days: ['Monday', ...], opens: '09:00', closes: '18:00' }, ...]
  if (Array.isArray(contact.openingHours) && contact.openingHours.length && !out.openingHoursSpecification) {
    out.openingHoursSpecification = contact.openingHours.map((h) => ({
      '@type': 'OpeningHoursSpecification',
      dayOfWeek: h.days,
      opens: h.opens,
      closes: h.closes,
    }));
  }

  if (!isPlaceholder(contact.googleBusinessUrl)) {
    const same = [].concat(out.sameAs || []);
    if (!same.includes(contact.googleBusinessUrl)) same.push(contact.googleBusinessUrl);
    out.sameAs = same;
    if (!out.hasMap) out.hasMap = contact.googleBusinessUrl;
  }

  return out;
}

/** Frontmatter'daki ham JSON-LD dizesini alır, zenginleştirip geri döner. */
function enrichRawSchema(raw, contact) {
  let node;
  try {
    node = JSON.parse(raw);
  } catch (e) {
    return raw; // bozuk JSON'a dokunma — derlemeyi düşürmektense olduğu gibi bas
  }
  return JSON.stringify(enrichOrganization(node, contact));
}

module.exports = { breadcrumb, article, faqPage, enrichOrganization, enrichRawSchema };
