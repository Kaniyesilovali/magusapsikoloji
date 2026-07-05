#!/usr/bin/env node
/** survey-blocks2.js — iç bileşen yapıları + paragraf içi inline tag envanteri */
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

const inner = {}; // iç bileşen imzaları
const inline = {}; // p içi inline tag'ler
const ulClasses = {}; // doğrudan blok içi ul sınıfları

for (const file of files) {
  const rel = path.relative(ROOT, file);
  const { content } = matter(fs.readFileSync(file, 'utf8'));
  const $ = cheerio.load(content, null, false);
  const wrapper = $('.prose-custom').first();
  if (!wrapper.length) continue;
  wrapper.children().each((_, blk) => {
    $(blk).children('div, ul').each((_, comp) => {
      const c = ($(comp).attr('class') || '').trim();
      const kidSig = $(comp).children().toArray().map((k) => {
        const kc = ($(k).attr('class') || '').split(' ').slice(0, 3).join(' ');
        return `${k.tagName}[${kc}]`;
      }).slice(0, 3).join(' + ');
      const key = `${comp.tagName}[${c}] :: ${kidSig}`;
      (inner[key] ||= []).push(rel);
    });
    // p içi inline
    $(blk).find('p').each((_, p) => {
      $(p).children().each((_, k) => {
        const key = `${k.tagName}[${($(k).attr('class') || '').slice(0, 40)}]`;
        (inline[key] ||= []).push(rel);
      });
    });
  });
}

console.log('=== İÇ BİLEŞEN İMZALARI ===');
for (const [k, v] of Object.entries(inner).sort((a, b) => b[1].length - a[1].length)) {
  console.log(String(v.length).padStart(4), '|', k.slice(0, 170));
}
console.log('\n=== P İÇİ INLINE TAGLER ===');
for (const [k, v] of Object.entries(inline).sort((a, b) => b[1].length - a[1].length)) {
  console.log(String(v.length).padStart(4), '|', k, '|', v[0]);
}
