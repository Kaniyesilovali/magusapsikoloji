<?php
use Panel\Csrf;
/** @var string $type @var string $key @var string $lang @var string $label @var string $heading */
/** @var array $items @var string $sha @var array $langs @var array $actor */

$rows = count($items);
?>

<div class="max-w-2xl">

<header class="mb-6">
  <a href="<?= e(url('/icerik/sss')) ?>" class="btn-text btn-text-quiet">← SSS içeriği</a>
  <p class="eyebrow mt-3">SSS içeriği</p>
  <h1 class="page-title mt-2"><?= e($heading) ?></h1>
  <p class="page-sub">
    <?= e($label) ?> · <?= e($langs[$lang] ?? $lang) ?> · <span class="num"><?= $rows ?></span> soru
  </p>

  <?php // Aynı içeriğin diğer dildeki karşılığına hızlı geçiş. ?>
  <nav class="flex gap-2 mt-3">
    <?php foreach ($langs as $code => $langLabel): ?>
      <a href="<?= e(url('/icerik/sss-duzenle?tip=' . rawurlencode($type) . '&anahtar=' . rawurlencode($key) . '&dil=' . $code)) ?>"
         class="btn btn-sm <?= $code === $lang ? 'btn-primary' : 'btn-quiet' ?>"
         <?= $code === $lang ? 'aria-current="page"' : '' ?>>
        <?= e($langLabel) ?>
      </a>
    <?php endforeach; ?>
  </nav>
</header>

<div class="note note-info mb-4">
  Kaydettiğinizde değişiklik depoya işlenir ve site yeniden yayınlanır — yayına yansıması
  birkaç dakika sürer. İki dil ayrı ayrı kaydedilir.
</div>

<form method="post" action="<?= e(url('/icerik/sss-duzenle')) ?>" class="space-y-4">
  <?= Csrf::field() ?>
  <input type="hidden" name="sha" value="<?= e($sha) ?>">
  <input type="hidden" name="tip" value="<?= e($type) ?>">
  <input type="hidden" name="anahtar" value="<?= e($key) ?>">
  <input type="hidden" name="dil" value="<?= e($lang) ?>">

  <?php if ($type === 'sayfa'): ?>
    <div class="sheet">
      <div class="sheet-body">
        <label for="kategori_basligi" class="field-label">Kategori başlığı</label>
        <input type="text" id="kategori_basligi" name="kategori_basligi" class="field"
               value="<?= e($heading) ?>">
      </div>
    </div>
  <?php endif; ?>

  <?php // Mevcut satırlar + sonda üç boş satır. İndeksler açıkça yazılır ki
        // "sil" kutusu doğru satırla eşleşsin (işaretsiz kutu POST'a girmez). ?>
  <?php for ($i = 0; $i < $rows + 3; $i++): ?>
    <?php
    $item     = $items[$i] ?? ['q' => '', 'a' => ''];
    $isNew    = $i >= $rows;
    $question = (string) ($item['q'] ?? '');
    $answer   = (string) ($item['a'] ?? '');
    ?>
    <?php // Boş satırlar kesikli çerçeveyle "henüz doldurulmadı" der. ?>
    <div class="sheet <?= $isNew ? 'border-dashed' : '' ?>">
      <div class="sheet-body space-y-3">
        <div class="flex items-center justify-between gap-3">
          <span class="text-xs text-ink-light"><?= $isNew ? 'Yeni kayıt' : '#' . ($i + 1) ?></span>
          <?php if (!$isNew): ?>
            <label for="sil_<?= $i ?>"
                   class="flex items-center gap-1.5 text-xs text-ink-light hover:text-accent-dark cursor-pointer">
              <input type="checkbox" id="sil_<?= $i ?>" name="sil[<?= $i ?>]" value="1"
                     class="w-3.5 h-3.5 accent-primary">
              bu satırı sil
            </label>
          <?php endif; ?>
        </div>

        <div>
          <label for="q_<?= $i ?>" class="field-label">Soru</label>
          <input type="text" id="q_<?= $i ?>" name="q[<?= $i ?>]" class="field" value="<?= e($question) ?>">
        </div>
        <div>
          <label for="a_<?= $i ?>" class="field-label">Cevap</label>
          <textarea id="a_<?= $i ?>" name="a[<?= $i ?>]" rows="4"
                    class="field leading-relaxed"><?= e($answer) ?></textarea>
        </div>
      </div>
    </div>
  <?php endfor; ?>

  <div class="flex items-center gap-4 pt-1">
    <button type="submit" class="btn btn-primary">Kaydet ve yayınla</button>
    <a href="<?= e(url('/icerik/sss')) ?>" class="btn-text btn-text-quiet">Vazgeç</a>
  </div>
</form>

</div>
