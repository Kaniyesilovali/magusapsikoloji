<?php
/** @var array $topics @var array $categories @var array $langs @var array $actor */

$editUrl = static fn (string $type, string $key, string $lang): string
    => url('/icerik/sss-duzenle?tip=' . rawurlencode($type) . '&anahtar=' . rawurlencode($key) . '&dil=' . $lang);
?>

<header class="mb-6">
  <p class="eyebrow">Site içeriği</p>
  <h1 class="page-title mt-2">SSS içeriği</h1>
  <p class="page-sub">
    Sayfalara gömülen konu blokları ve SSS sayfasının kategorileri. Değişiklik kaydedildiğinde
    site yeniden yayınlanır.
  </p>
</header>

<section class="sheet mb-6">
  <div class="sheet-head">
    <div>
      <h2 class="sheet-title">SSS sayfası kategorileri</h2>
      <p class="text-xs text-ink-light mt-1">Sitedeki SSS sayfasında başlıklar hâlinde görünür.</p>
    </div>
  </div>

  <?php foreach ($langs as $lang => $langLabel): ?>
    <div class="px-5 py-4 <?= $lang !== array_key_first($langs) ? 'border-t border-warm-secondary' : '' ?>">
      <p class="eyebrow mb-2"><?= e($langLabel) ?></p>
      <?php if (empty($categories[$lang])): ?>
        <p class="text-sm text-ink-light">Kategori yok.</p>
      <?php else: ?>
        <ul class="divide-y divide-warm-secondary">
          <?php foreach ($categories[$lang] as $category): ?>
            <li class="py-2.5 flex flex-wrap items-center gap-x-4 gap-y-1">
              <span class="text-sm text-ink flex-1 min-w-48"><?= e($category['title']) ?></span>
              <span class="text-xs text-ink-light"><span class="num"><?= (int) $category['count'] ?></span> soru</span>
              <a href="<?= e($editUrl('sayfa', $category['id'], $lang)) ?>"
                 class="btn-text btn-text-quiet">Düzenle</a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</section>

<section class="sheet">
  <div class="sheet-head">
    <div>
      <h2 class="sheet-title">Konu blokları</h2>
      <p class="text-xs text-ink-light mt-1">
        <span class="num"><?= count($topics) ?></span> konu. Blog yazılarının ve hizmet sayfalarının
        altındaki soru-cevap bölümlerini besler; arama motorlarına da bu içerik sunulur.
      </p>
    </div>
  </div>

  <div class="overflow-x-auto">
    <table class="tbl">
      <thead>
        <tr>
          <th>Konu</th>
          <?php foreach ($langs as $langLabel): ?>
            <th><?= e($langLabel) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($topics as $slug => $counts): ?>
          <tr>
            <td class="text-ink font-medium"><?= e($slug) ?></td>
            <?php foreach ($langs as $lang => $ignored): ?>
              <td>
                <a href="<?= e($editUrl('konu', (string) $slug, $lang)) ?>" class="btn-text">
                  <span class="num"><?= (int) $counts[$lang] ?></span> soru
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
