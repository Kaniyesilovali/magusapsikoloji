#!/usr/bin/env node
/**
 * FAQ Generator
 * Reads faq-data.json → rebuilds FAQ sections in sss.html and en/faq.html
 * Usage: node generate-faq.js
 */

const fs = require('fs');
const path = require('path');

const dataPath  = path.join(__dirname, 'faq-data.json');
const START_TAG = '<!-- FAQ:START -->';
const END_TAG   = '<!-- FAQ:END -->';

const data = JSON.parse(fs.readFileSync(dataPath, 'utf8'));

function buildSchemaItems(categories) {
  return categories.flatMap(cat =>
    cat.items.map(item => ({
      '@type': 'Question',
      name: item.q,
      acceptedAnswer: { '@type': 'Answer', text: item.a }
    }))
  );
}

function buildSchemaBlock(categories) {
  const items = buildSchemaItems(categories);
  return `  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": ${JSON.stringify(items, null, 4).replace(/^/gm, '    ').trimStart()}
  }
  </script>`;
}

function buildFaqHtml(categories) {
  const navPills = categories.map(cat =>
    `      <a href="#${cat.id}" class="text-xs font-medium px-3.5 py-1.5 rounded-full border border-warm-tertiary bg-warm hover:bg-primary/10 hover:border-primary/30 hover:text-primary transition-colors text-ink-muted">${cat.title}</a>`
  ).join('\n');

  const categoryBlocks = categories.map(cat => {
    const items = cat.items.map(item => `        <article class="py-6 border-b border-warm-secondary last:border-0" itemprop="mainEntity" itemscope itemtype="https://schema.org/Question">
          <h3 class="font-semibold text-ink mb-2.5 leading-snug" itemprop="name">${item.q}</h3>
          <div itemprop="acceptedAnswer" itemscope itemtype="https://schema.org/Answer">
            <p class="text-sm text-ink-muted leading-relaxed" itemprop="text">${item.a}</p>
          </div>
        </article>`).join('\n');

    return `      <section id="${cat.id}" class="mb-14">
        <h2 class="font-serif text-lg text-ink mb-1 pb-3 border-b-2 border-primary/20">${cat.title}</h2>
        <div>
${items}
        </div>
      </section>`;
  }).join('\n\n');

  return `${START_TAG}
    <nav class="flex flex-wrap gap-2 mb-10" aria-label="FAQ categories">
${navPills}
    </nav>

    <div itemscope itemtype="https://schema.org/FAQPage">
${categoryBlocks}
    </div>
${END_TAG}`;
}

function injectFaq(pagePath, categories) {
  let html = fs.readFileSync(pagePath, 'utf8');

  const startIdx = html.indexOf(START_TAG);
  const endIdx   = html.indexOf(END_TAG);

  if (startIdx === -1 || endIdx === -1) {
    console.error(`ERROR: FAQ:START / FAQ:END markers not found in ${pagePath}`);
    process.exit(1);
  }

  const replacement = buildFaqHtml(categories);
  html = html.slice(0, startIdx) + replacement + html.slice(endIdx + END_TAG.length);

  // Update schema block if markers exist
  const schemaStart = html.indexOf('<!-- SCHEMA:FAQ:START -->');
  const schemaEnd   = html.indexOf('<!-- SCHEMA:FAQ:END -->');
  if (schemaStart !== -1 && schemaEnd !== -1) {
    const schemaBlock = buildSchemaBlock(categories);
    html = html.slice(0, schemaStart) +
      '<!-- SCHEMA:FAQ:START -->\n' + schemaBlock + '\n  <!-- SCHEMA:FAQ:END -->' +
      html.slice(schemaEnd + '<!-- SCHEMA:FAQ:END -->'.length);
  }

  fs.writeFileSync(pagePath, html, 'utf8');
  const total = categories.reduce((sum, c) => sum + c.items.length, 0);
  console.log(`✓ ${path.relative(__dirname, pagePath)} updated — ${categories.length} categories, ${total} questions`);
}

// TR
injectFaq(path.join(__dirname, 'sss.html'), data.tr.categories);

// EN
injectFaq(path.join(__dirname, 'en/faq.html'), data.en.categories);
