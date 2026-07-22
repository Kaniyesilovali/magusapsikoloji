<?php
/** @var array $topics @var array $categories @var array $langs @var array $actor */

$editUrl = static fn (string $type, string $key, string $lang): string
    => url('/icerik/sss-duzenle?tip=' . rawurlencode($type) . '&anahtar=' . rawurlencode($key) . '&dil=' . $lang);
?>

<header class="mb-6">
  <h1 class="text-2xl font-semibold text-ink">SSS içeriği</h1>
  <p class="text-sm text-ink-light mt-1">
    Sayfalara gömülen konu blokları ve SSS sayfasının kategorileri. Değişiklik kaydedildiğinde
    site yeniden yayınlanır.
  </p>
</header>

<section class="bg-white border border-warm-tertiary rounded-2xl overflow-hidden mb-6">
  <div class="px-5 py-4 border-b border-warm-tertiary">
    <h2 class="font-medium text-ink">SSS sayfası kategorileri</h2>
    <p class="text-xs text-ink-light mt-1">Sitedeki SSS sayfasında başlıklar hâlinde görünür.</p>
  </div>

  <?php foreach ($langs as $lang => $langLabel): ?>
    <div class="px-5 py-4 <?= $lang !== array_key_first($langs) ? 'border-t border-warm-secondary' : '' ?>">
      <p class="text-xs uppercase tracking-wide text-ink-muted mb-2"><?= e($langLabel) ?></p>
      <?php if (empty($categories[$lang])): ?>
        <p class="text-sm text-ink-light">Kategori yok.</p>
      <?php else: ?>
        <ul class="divide-y divide-warm-secondary">
          <?php foreach ($categories[$lang] as $category): ?>
            <li class="py-2.5 flex flex-wrap items-center gap-x-4 gap-y-1">
              <span class="text-sm text-ink flex-1 min-w-48"><?= e($category['title']) ?></span>
              <span class="text-xs text-ink-light"><?= (int) $category['count'] ?> soru</span>
              <a href="<?= e($editUrl('sayfa', $category['id'], $lang)) ?>"
                 class="text-xs text-primary hover:text-primary-dark font-medium">Düzenle</a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</section>

<section class="bg-white border border-warm-tertiary rounded-2xl overflow-hidden">
  <div class="px-5 py-4 border-b border-warm-tertiary">
    <h2 class="font-medium text-ink">Konu blokları</h2>
    <p class="text-xs text-ink-light mt-1">
      <?= count($topics) ?> konu. Blog yazılarının ve hizmet sayfalarının altındaki
      soru-cevap bölümlerini besler; arama motorlarına da bu içerik sunulur.
    </p>
  </div>

  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-warm text-ink-muted text-xs uppercase tracking-wide">
        <tr>
          <th class="text-left font-medium px-5 py-3">Konu</th>
          <?php foreach ($langs as $langLabel): ?>
            <th class="text-left font-medium px-5 py-3"><?= e($langLabel) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody class="divide-y divide-warm-secondary">
        <?php foreach ($topics as $slug => $counts): ?>
          <tr class="hover:bg-warm/60">
            <td class="px-5 py-3 text-ink font-medium"><?= e($slug) ?></td>
            <?php foreach ($langs as $lang => $ignored): ?>
              <td class="px-5 py-3">
                <a href="<?= e($editUrl('konu', (string) $slug, $lang)) ?>" class="text-primary hover:text-primary-dark">
                  <?= (int) $counts[$lang] ?> soru
                </a>
              </td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<p class="text-xs text-ink-light mt-6">
  Konu anahtarları ve kategori kimlikleri panelden değiştirilemez — şablonlar bunlara ada göre
  başvuruyor, yeniden adlandırma sitedeki bölümü sessizce boşaltırdı. Yeni konu eklenmesi
  gerekirse söyleyin, şablon tarafıyla birlikte yapılmalı.
</p>
