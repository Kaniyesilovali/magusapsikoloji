<?php
use Panel\Csrf;
/** @var array $data @var string $sha @var array $fields @var array $actor */
?>

<div class="max-w-2xl">

<header class="mb-6">
  <p class="eyebrow">Site içeriği</p>
  <h1 class="page-title mt-2">İletişim bilgileri</h1>
  <p class="page-sub">
    Sitenin alt bilgisinde, iletişim sayfasında ve WhatsApp düğmesinde görünen bilgiler.
  </p>
</header>

<div class="note note-info mb-4">
  Kaydettiğinizde değişiklik depoya işlenir ve site otomatik olarak yeniden yayınlanır —
  yayına yansıması birkaç dakika sürer.
</div>

<form method="post" action="<?= e(url('/icerik/iletisim')) ?>" class="sheet">
  <div class="sheet-body space-y-5">
    <?= Csrf::field() ?>
    <input type="hidden" name="sha" value="<?= e($sha) ?>">

    <?php foreach ($fields as $key => [$label, $hint]): ?>
      <div>
        <label for="<?= e($key) ?>" class="field-label"><?= e($label) ?></label>
        <input type="text" id="<?= e($key) ?>" name="<?= e($key) ?>" class="field"
               value="<?= e(old($key, (string) ($data[$key] ?? ''))) ?>">
        <?php if ($message = error_for($key)): ?>
          <p class="field-error"><?= e($message) ?></p>
        <?php elseif ($hint !== ''): ?>
          <p class="field-hint"><?= e($hint) ?></p>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="sheet-foot">
    <button type="submit" class="btn btn-primary">Kaydet ve yayınla</button>
  </div>
</form>

</div>
