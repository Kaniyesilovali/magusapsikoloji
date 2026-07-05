#!/usr/bin/env node
/**
 * survey-blocks.js — blog gövdelerindeki blok desenlerini envanterler.
 * Her yazının prose-custom kapsayıcısındaki üst seviye çocukların
 * yapısal imzasını çıkarır; hero yapısını da doğrular.
 */
const fs = require('fs');
const path = require('path');
const cheerio = require('cheerio');
const matter = require('gray-matter');

const ROOT = path.join(__dirname, '..');
const files = [];
for (const lang of ['tr', 'en']) {
  const dir = path.join(ROOT, 'content', lang, 'blog');
  for (const f of fs.readdirSync(dir)) {
    if (f.endsWith('.njk') && f !== 'index.njk') files.push(path.join(dir, f));
  }
}

const signatures = {}; // signature -> [file examples]
const heroIssues = [];

function sig($, el) {
  const $el = $(el);
  const cls = ($el.attr('class') || '').trim();
  const tag = el.tagName;
  const kids = $el.children().toArray().map((k) => k.tagName).join(',');
  return `${tag}[${cls}] > (${kids})`;
}

for (const file of files) {
  const rel = path.relative(ROOT, file);
  const { content } = matter(fs.readFileSync(file, 'utf8'));
  const $ = cheerio.load(content, null, false);
  const sections = $('section').toArray().filter((s) => $(s).parents('section').length === 0);
  if (sections.length !== 2) {
    heroIssues.push(`${rel}: ${sections.length} üst seviye section`);
    continue;
  }
  // hero kontrolü
  const hero = $(sections[0]);
  const h1 = hero.find('h1');
  const heroClasses = hero.attr('class') || '';
  if (h1.length !== 1 || !heroClasses.includes('bg-warm')) {
    heroIssues.push(`${rel}: hero beklenen yapıda değil (h1=${h1.length}, cls=${heroClasses})`);
  }
  // hero'daki lead p sayısı
  const heroPs = hero.find('h1').nextAll('p').length;
  if (heroPs !== 1) heroIssues.push(`${rel}: hero'da h1 sonrası ${heroPs} paragraf`);

  // article blokları
  const wrapper = $(sections[1]).find('.prose-custom').first();
  if (!wrapper.length) {
    heroIssues.push(`${rel}: prose-custom bulunamadı`);
    continue;
  }
  wrapper.children().each((_, el) => {
    const s = sig($, el);
    (signatures[s] ||= []).push(rel);
  });
}

console.log('=== HERO / YAPI SORUNLARI ===');
heroIssues.forEach((x) => console.log(' !', x));
console.log('\n=== ÜST SEVİYE BLOK İMZALARI (adet | imza | örnek) ===');
const entries = Object.entries(signatures).sort((a, b) => b[1].length - a[1].length);
for (const [s, list] of entries) {
  console.log(String(list.length).padStart(4), '|', s.slice(0, 140), '|', list[0]);
}
console.log('\nToplam imza çeşidi:', entries.length, '— toplam blok:', entries.reduce((a, [, l]) => a + l.length, 0));
