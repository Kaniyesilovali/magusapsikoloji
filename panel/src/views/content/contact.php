<?php
use Panel\Csrf;
/** @var array $data @var string $sha @var array $fields @var array $actor */

$inputCls = 'w-full px-3 py-2.5 rounded-xl border border-warm-tertiary bg-warm focus:bg-white focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 text-sm';
?>

<header class="mb-6">
  <h1 class="text-2xl font-semibold text-ink">İletişim bilgileri</h1>
  <p class="text-sm text-ink-light mt-1">
    Sitenin alt bilgisinde, iletişim sayfasında ve WhatsApp düğmesinde görünen bilgiler.
  </p>
</header>

<div class="mb-4 px-4 py-3 rounded-xl border border-warm-tertiary bg-white text-sm text-ink-muted">
  Kaydettiğinizde değişiklik depoya işlenir ve site otomatik olarak yeniden yayınlanır —
  yayına yansıması birkaç dakika sürer.
</div>

<form method="post" action="<?= e(url('/icerik/iletisim')) ?>" class="bg-white border border-warm-tertiary rounded-2xl p-6 space-y-5 max-w-xl">
  <?= Csrf::field() ?>
  <input type="hidden" name="sha" value="<?= e($sha) ?>">

  <?php foreach ($fields as $key => [$label, $hint]): ?>
    <div>
      <label for="<?= e($key) ?>" class="block text-sm font-medium text-ink mb-1.5"><?= e($label) ?></label>
      <input type="text" id="<?= e($key) ?>" name="<?= e($key) ?>" class="<?= $inputCls ?>"
             value="<?= e(old($key, (string) ($data[$key] ?? ''))) ?>">
      <?php if ($message = error_for($key)): ?>
        <p class="text-xs text-accent-dark mt-1.5"><?= e($message) ?></p>
      <?php elseif ($hint !== ''): ?>
        <p class="text-xs text-ink-light mt-1.5"><?= e($hint) ?></p>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>

  <div class="flex gap-3 pt-1">
    <button type="submit" class="bg-primary hover:bg-primary-hover text-white text-sm font-medium px-5 py-2.5 rounded-xl transition-colors">
      Kaydet ve yayınla
    </button>
  </div>
</form>
