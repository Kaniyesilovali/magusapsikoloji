const { execFileSync } = require('child_process');

/**
 * Yazının metni değişmediği hâlde dosyasına dokunan commit'ler: telefon
 * numarası, sınıf adı, toplu bağlantı düzeltmesi gibi süpürmeler.
 *
 * Bunlar sayılırsa tek satırlık bir numara değişikliği 52 yazının tamamını
 * "bugün güncellendi" gösterir — okura da arama motoruna da yanlış bilgi.
 *
 * Yeni bir süpürmede commit mesajına `[lastmod skip]` yazmak yeter; aşağıdaki
 * liste yalnızca bu kural konmadan önce atılmış commit'ler için. Tam SHA.
 */
const SKIPPED_COMMITS = [
  // "Wire the real contact number through the site" — 52 dosyada yalnızca
  // wa.me bağlantısındaki placeholder numara gerçek numarayla değişti.
  'b58739e6fa70f0663c6dc8794aecb36aa2604d11',
];

const skipped = (() => {
  const s = new Set(SKIPPED_COMMITS);
  try {
    const out = execFileSync('git', ['log', '--grep=\\[lastmod skip\\]', '--pretty=format:%H'], {
      encoding: 'utf8',
      maxBuffer: 16 * 1024 * 1024,
    });
    for (const line of out.split('\n')) if (line.trim()) s.add(line.trim());
  } catch (e) {
    // git yok: yalnızca elle yazılmış liste geçerli.
  }
  return s;
})();

/**
 * Sayfa başına gerçek son değişiklik tarihi (YYYY-MM-DD), kaynağı git.
 *
 * Bir dosyanın ne zaman değiştiğinin tek dürüst kaydı git geçmişidir; frontmatter'daki
 * tarih yazarın beyanıdır ve güncellemede çoğu zaman elle değiştirilmez.
 *
 * Tek `git log` çağrısı, yeni→eski sırada: bir yol ilk görüldüğünde en yeni tarihidir.
 * Atlanan commit tarihi null'a çeker, yani o commit'te dokunulan dosyalar kayda
 * geçmez ve bir önceki gerçek değişikliklerine düşerler.
 *
 * Sığ klonda (fetch-depth: 1) harita eksik kalır ve frontmatter tarihine düşülür —
 * bu yüzden deploy iş akışı fetch-depth: 0 ile checkout yapar.
 *
 * Hem sitemap <lastmod> hem sayfadaki görünür "Son güncelleme" satırı buradan beslenir;
 * ikisinin ayrışmaması için tek kaynak.
 */
const map = (() => {
  const m = new Map();
  try {
    const out = execFileSync(
      'git',
      ['log', '--pretty=format:%H@%cI', '--name-only', '--no-renames', '--', 'content'],
      { encoding: 'utf8', maxBuffer: 64 * 1024 * 1024 }
    );
    let commitDate = null;
    for (const line of out.split('\n')) {
      if (!line) continue;
      const head = /^([0-9a-f]{40})@(\d{4}-\d{2}-\d{2})T/.exec(line);
      if (head) commitDate = skipped.has(head[1]) ? null : head[2];
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

module.exports = { map, forPath, resolve, skipped };
