<?php
use Panel\Csrf;
/** @var string $type @var string $key @var string $lang @var string $label @var string $heading */
/** @var array $items @var string $sha @var array $langs @var array $actor */

$inputCls = 'w-full px-3 py-2.5 rounded-xl border border-warm-tertiary bg-warm focus:bg-white focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 text-sm';
$rows     = count($items);
?>

<header class="mb-6">
  <a href="<?= e(url('/icerik/sss')) ?>" class="text-sm text-ink-muted hover:text-ink">← SSS içeriği</a>
  <h1 class="text-2xl font-semibold text-ink mt-2"><?= e($heading) ?></h1>
  <p class="text-sm text-ink-light mt-1">
    <?= e($label) ?> · <?= e($langs[$lang] ?? $lang) ?> · <?= $rows ?> soru
  </p>

  <?php // Aynı içeriğin diğer dildeki karşılığına hızlı geçiş. ?>
  <nav class="flex gap-2 mt-3">
    <?php foreach ($langs as $code => $langLabel): ?>
      <a href="<?= e(url('/icerik/sss-duzenle?tip=' . rawurlencode($type) . '&anahtar=' . rawurlencode($key) . '&dil=' . $code)) ?>"
         class="text-xs px-3 py-1.5 rounded-lg <?= $code === $lang ? 'bg-primary text-white' : 'bg-white border border-warm-tertiary text-ink-muted hover:text-ink' ?>">
        <?= e($langLabel) ?>
      </a>
    <?php endforeach; ?>
  </nav>
</header>

<div class="mb-4 px-4 py-3 rounded-xl border border-warm-tertiary bg-white text-sm text-ink-muted">
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
    <div class="bg-white border border-warm-tertiary rounded-2xl p-5">
      <label for="kategori_basligi" class="block text-sm font-medium text-ink mb-1.5">Kategori başlığı</label>
      <input type="text" id="kategori_basligi" name="kategori_basligi" class="<?= $inputCls ?>" value="<?= e($heading) ?>">
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
    <div class="bg-white border rounded-2xl p-5 space-y-3 <?= $isNew ? 'border-dashed border-warm-tertiary' : 'border-warm-tertiary' ?>">
      <div class="flex items-center justify-between gap-3">
        <span class="text-xs text-ink-light"><?= $isNew ? 'Yeni kayıt' : '#' . ($i + 1) ?></span>
        <?php if (!$isNew): ?>
          <label class="flex items-center gap-1.5 text-xs text-accent-dark cursor-pointer">
            <input type="checkbox" name="sil[<?= $i ?>]" value="1" class="w-3.5 h-3.5 accent-primary">
            bu satırı sil
          </label>
        <?php endif; ?>
      </div>

      <div>
        <label class="block text-xs text-ink-muted mb-1">Soru</label>
        <input type="text" name="q[<?= $i ?>]" class="<?= $inputCls ?>" value="<?= e($question) ?>">
      </div>
      <div>
        <label class="block text-xs text-ink-muted mb-1">Cevap</label>
        <textarea name="a[<?= $i ?>]" rows="4" class="<?= $inputCls ?> leading-relaxed"><?= e($answer) ?></textarea>
      </div>
    </div>
  <?php endfor; ?>

  <div class="flex gap-3 pt-1">
    <button type="submit" class="bg-primary hover:bg-primary-hover text-white text-sm font-medium px-5 py-2.5 rounded-xl transition-colors">
      Kaydet ve yayınla
    </button>
    <a href="<?= e(url('/icerik/sss')) ?>" class="text-sm text-ink-muted hover:text-ink px-4 py-2.5">Vazgeç</a>
  </div>
</form>
