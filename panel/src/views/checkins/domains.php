<?php
use Panel\Csrf;
use Panel\Ecosystem;
/** @var array $client @var ?int $age @var list<string> $open @var list<string> $defaults */

// Hangi alanların sorulacağı klinik bir karar, ayar değil: kardeşi olmayan bir
// çocuğa her hafta "Kardeş" sormak formu kendi gözünde gereksiz kılar ve
// doldurma oranını düşürür. Bu yüzden ekran "aç/kapat" değil, "bu çocuğun
// hayatında ne var" sorusu gibi kuruluyor.
$core = [];
$rest = [];
foreach (Ecosystem::DOMAINS as $key => $domain) {
    ($domain['core'] ? $core : $rest)[$key] = $domain;
}
?>

<header class="mb-6">
  <p class="eyebrow">
    <a href="<?= e(url('/danisanlar/' . (int) $client['id'])) ?>" class="btn-text btn-text-quiet">← <?= e($client['full_name']) ?></a>
  </p>
  <h1 class="page-title mt-2">Sorulan alanlar</h1>
  <p class="page-sub">
    Haftalık check-in'in ikinci sayfasında bu çocuk için hangi alanların
    görüneceği. Değişiklik bundan sonra üretilen bağlantılarda geçerli olur;
    geçmiş kayıtlar olduğu gibi kalır.
  </p>
</header>

<form method="post" action="<?= e(url('/danisanlar/' . (int) $client['id'] . '/alanlar')) ?>" class="sheet">
  <?= Csrf::field() ?>

  <div class="sheet-head">
    <div>
      <h2 class="sheet-title">Çekirdek</h2>
      <p class="text-xs text-ink-light mt-1">Her dosyada açık başlar.</p>
    </div>
  </div>

  <?php foreach ([$core, $rest] as $index => $group): ?>
    <?php if ($index === 1): ?>
      <div class="sheet-head border-t border-warm-secondary">
        <div>
          <h2 class="sheet-title">Koşullu</h2>
          <p class="text-xs text-ink-light mt-1">
            Yalnız o çocuğun hayatında karşılığı varsa açın. İnanç ve gelenek
            alanını ailenin kendisi istemedikçe açmayın.
          </p>
        </div>
      </div>
    <?php endif; ?>

    <?php foreach ($group as $key => $domain): ?>
      <label class="flex items-start gap-3 px-5 py-3 border-t border-warm-secondary cursor-pointer">
        <input type="checkbox" name="alan[]" value="<?= e($key) ?>"
               <?= in_array($key, $open, true) ? 'checked' : '' ?>
               class="mt-1 shrink-0">
        <span class="min-w-0">
          <span class="text-sm text-ink"><?= e(Ecosystem::label($key, $age)) ?></span>
          <?php if (in_array($key, $defaults, true)): ?>
            <span class="chip chip-neutral ml-1">varsayılan</span>
          <?php endif; ?>
          <span class="block text-xs text-ink-light"><?= e($domain['hint']) ?></span>
        </span>
      </label>
    <?php endforeach; ?>
  <?php endforeach; ?>

  <div class="sheet-foot">
    <button class="btn btn-primary">Kaydet</button>
    <p class="field-hint">
      En fazla <span class="num"><?= Ecosystem::MAX_OPEN ?></span> alan açılabilir; fazlası kaydedilmez.
      Sekizden sonrası halkada sıkışmaya başlar — <strong>gerçekten sorulacak</strong>
      olanı açın. Her ek alan doldurma süresini uzatıyor ve ölçtüğümüz tek şey
      formun doldurulup doldurulmadığı.
    </p>
  </div>
</form>
