<?php
use Panel\Checkins;
use Panel\Csrf;
/** @var string $token @var string $firstName @var bool $noteOpen */

// Üç soru, üç kaydırıcı, bir isteğe bağlı cümle. Ölçek yönü her sorunun altında
// ayrıca yazıyor: kaygıda yüksek değer kötüdür, diğer ikisinde iyidir ve bunu
// hatırlatmayan bir form yanlış veri toplar.
?>

<div class="sheet-body">
  <h1 class="page-title">Haftalık check-in</h1>
  <p class="page-sub">
    <?php if ($firstName !== ''): ?>Merhaba <?= e($firstName) ?>, <?php endif; ?>
    bu üç soru yaklaşık yarım dakika sürer. Doğru ya da yanlış cevap yok;
    bu haftanın nasıl geçtiğini gösteren bir işaret bırakıyorsun.
  </p>
</div>

<form method="post" action="<?= e(url('/check-in/' . $token)) ?>">
  <?= Csrf::field() ?>

  <?php foreach (Checkins::QUESTIONS as $field => $question): ?>
    <?php
    $scale = Checkins::MEASURES[$field];
    $outId = $field . 'Out';
    ?>
    <div class="ci-q">
      <div class="ci-q-head">
        <label class="ci-q-label" for="<?= e($field) ?>">
          <?= e($question) ?>
        </label>
        <?php // Değeri betik güncelliyor; JS kapalıysa kaydırıcının yeri zaten söylüyor. ?>
        <output class="ci-out num" id="<?= e($outId) ?>" for="<?= e($field) ?>">5</output>
      </div>

      <input class="ci-range" type="range"
             id="<?= e($field) ?>" name="<?= e($field) ?>"
             min="1" max="10" step="1" value="5"
             list="ciTicks" data-range-output="#<?= e($outId) ?>"
             aria-describedby="<?= e($field) ?>Scale">

      <p class="ci-scale" id="<?= e($field) ?>Scale">
        <span>1 · <?= e($scale['low']) ?></span>
        <span>10 · <?= e($scale['high']) ?></span>
      </p>
    </div>
  <?php endforeach; ?>

  <?php // Kaydırıcıların altındaki çentikler — tarayıcı destekliyorsa çizer. ?>
  <datalist id="ciTicks">
    <?php for ($step = 1; $step <= 10; $step++): ?>
      <option value="<?= $step ?>"></option>
    <?php endfor; ?>
  </datalist>

  <?php if ($noteOpen): ?>
    <div class="ci-q">
      <label class="ci-q-label" for="note">
        Eklemek istediğin bir şey var mı? <span class="text-ink-light">(isteğe bağlı)</span>
      </label>
      <textarea class="field mt-2" id="note" name="note" rows="3" maxlength="2000"
                placeholder="Tek cümle yeter."></textarea>
      <p class="field-hint">
        Yazdıkların şifrelenerek saklanır ve yalnız terapistin görebilir.
      </p>
    </div>
  <?php endif; ?>

  <div class="sheet-foot">
    <button class="btn btn-primary w-full justify-center">Gönder</button>
  </div>
</form>
