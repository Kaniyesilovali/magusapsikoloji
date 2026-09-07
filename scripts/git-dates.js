const { execSync } = require('child_process');

/**
 * Sayfa başına gerçek son değişiklik tarihi (YYYY-MM-DD), kaynağı git.
 *
 * Bir dosyanın ne zaman değiştiğinin tek dürüst kaydı git geçmişidir; frontmatter'daki
 * tarih yazarın beyanıdır ve güncellemede çoğu zaman elle değiştirilmez.
 *
 * Tek `git log` çağrısı, yeni→eski sırada: bir yol ilk görüldüğünde en yeni tarihidir.
 * Sığ klonda (fetch-depth: 1) harita eksik kalır ve frontmatter tarihine düşülür —
 * bu yüzden deploy iş akışı fetch-depth: 0 ile checkout yapar.
 *
 * Hem sitemap <lastmod> hem sayfadaki görünür "Son güncelleme" satırı buradan beslenir;
 * ikisinin ayrışmaması için tek kaynak.
 */
const map = (() => {
  const m = new Map();
  try {
    const out = execSync('git log --pretty=format:%cI --name-only --no-renames -- content', {
      encoding: 'utf8',
      maxBuffer: 64 * 1024 * 1024,
    });
    let commitDate = null;
    for (const line of out.split('\n')) {
      if (!line) continue;
      if (/^\d{4}-\d{2}-\d{2}T/.test(line)) commitDate = line.slice(0, 10);
      else if (commitDate && !m.has(line)) m.set(line, commitDate);
    }
  } catch (e) {
    // git yok ya da geçmiş erişilemez: frontmatter tarihleri kullanılır.
  }
  return m;
})();

/** Dosya yolundan git tarihi (yoksa null). */
function forPath(inputPath) {
  return map.get(String(inputPath || '').replace(/^\.\//, '')) || null;
}

/**
 * Git tarihi ile yazarın beyan ettiği tarihlerden hangisi yeniyse o.
 * Hiçbiri yoksa null — uydurmaktansa boş bırakılır.
 */
function resolve(inputPath, ...declared) {
  const dates = [forPath(inputPath), ...declared]
    .filter(Boolean)
    .map((d) => String(d).slice(0, 10))
    .filter((d) => /^\d{4}-\d{2}-\d{2}$/.test(d));
  return dates.length ? dates.sort().pop() : null;
}

module.exports = { map, forPath, resolve };
