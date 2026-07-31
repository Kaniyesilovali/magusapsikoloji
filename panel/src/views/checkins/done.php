<?php
use Panel\Checkins;
// Gönderimden sonraki tek ekran. Kısa: iş bitti, sayfada yapılacak şey yok.
// Metinleri panelden düzenlenebilir — formun geri kalanıyla aynı ekrandan
// (bkz. Checkins::TEXTS), yoksa teşekkür cümlesi merkezin dilinin dışında kalır.
?>

<div class="sheet-body">
  <h1 class="page-title"><?= e(Checkins::text('tesekkur_baslik')) ?></h1>
  <p class="page-sub">
    <?= e(Checkins::text('tesekkur')) ?>
  </p>
  <p class="text-sm text-ink-muted mt-4">
    <?= e(Checkins::text('tesekkur_alt')) ?>
  </p>
</div>
