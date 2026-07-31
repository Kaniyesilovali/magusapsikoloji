<?php
use Panel\Checkins;
use Panel\Csrf;
use Panel\Ecosystem;
/** @var string $token @var string $firstName @var bool $noteOpen @var string $prompt */
/** @var list<array{key:string,label:string,hint:string}> $domains */

// Üç soru, üç kaydırıcı, bir isteğe bağlı cümle. Ölçek yönü her sorunun altında
// ayrıca yazıyor: kaygıda yüksek değer kötüdür, diğer ikisinde iyidir ve bunu
// hatırlatmayan bir form yanlış veri toplar.
//
// Sayfadaki her cümle panelden düzenlenebilir (bkz. Checkins::TEXTS): merkezin
// dili ile yazılımın dili aynı formda yan yana durmasın diye.
$texts   = Checkins::texts();
$scales  = Checkins::measures();
?>

<div class="sheet-body">
  <h1 class="page-title">Haftalık check-in</h1>
  <p class="page-sub">
    <?php if ($firstName !== ''): ?>Merhaba <?= e($firstName) ?>, <?php endif; ?>
    <?= e($texts['giris']) ?>
  </p>
</div>

<form method="post" action="<?= e(url('/check-in/' . $token)) ?>">
  <?= Csrf::field() ?>

  <?php foreach (Checkins::questions() as $field => $question): ?>
    <?php
    $scale = $scales[$field];
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
        <?= e($texts['cumle_baslik']) ?> <span class="text-ink-light">(isteğe bağlı)</span>
      </label>
      <textarea class="field mt-2" id="note" name="note" rows="3" maxlength="2000"
                placeholder="<?= e($texts['cumle_ornek']) ?>"></textarea>
      <p class="field-hint">
        <?= e($texts['cumle_ipucu']) ?>
      </p>
    </div>
  <?php endif; ?>

  <?php if ($domains !== []): ?>
    <?php // ── İkinci sayfa: haftanın hâli ───────────────────────────────
          // Ayrı bir gönderim değil, aynı formun devamı: aynı jeton, aynı POST,
          // aynı CSRF. Ayrı sayfa olsaydı yarıda bırakılan her doldurma, birinci
          // sayfayı da çöpe atardı.
          //
          // Üç hâl radyo düğmesi olarak duruyor, "dokununca dönen" bir düğme
          // olarak değil. Döngüsel düğmede "zorladı" demek iki dokunuş eder ve
          // üçüncü hâl görünmez kalır; üç seçenek yan yana durunca hem tek
          // dokunuş, hem JS'siz çalışır, hem ekran okuyucuya anlamlı gelir. ?>
    <div class="ci-q ci-wind-head">
      <p class="ci-q-label"><?= e($prompt) ?></p>
      <p class="ci-scale mt-2">
        <span>↑ iyi geldi</span>
        <span>↓ zorladı</span>
      </p>
      <p class="field-hint"><?= e($texts['halka_ipucu']) ?></p>
    </div>

    <?php // Halka JS ile kuruluyor: aşağıdaki liste hem JS kapalıyken çalışan
          // gerçek form, hem de halkanın veri kaynağı. İki ayrı işaretleme
          // yazmamak için halka bu radyoları gizlemiyor, onlara dokunuyor. ?>
    <div class="ci-ring-slot" data-ring data-center="<?= e($firstName !== '' ? $firstName : 'Bu hafta') ?>"></div>

    <div class="ci-dom-list" data-ring-source>
    <?php foreach ($domains as $domain): ?>
      <?php $name = 'alan[' . $domain['key'] . ']'; ?>
      <fieldset class="ci-dom" data-domain="<?= e($domain['key']) ?>" data-short="<?= e($domain['short']) ?>">
        <legend class="sr-only"><?= e($domain['label']) ?></legend>
        <div class="ci-dom-text">
          <span class="ci-dom-label" aria-hidden="true"><?= e($domain['label']) ?></span>
          <span class="ci-dom-hint"><?= e($domain['hint']) ?></span>
        </div>

        <div class="ci-wind">
          <?php foreach ([
              ['value' => Ecosystem::TAILWIND, 'glyph' => '↑', 'text' => 'iyi geldi', 'class' => 'is-up'],
              ['value' => Ecosystem::CALM,     'glyph' => '·', 'text' => 'öne çıkmadı', 'class' => 'is-calm'],
              ['value' => Ecosystem::HEADWIND, 'glyph' => '↓', 'text' => 'zorladı', 'class' => 'is-down'],
          ] as $option): ?>
            <?php $id = 'alan_' . $domain['key'] . '_' . ($option['value'] + 1); ?>
            <input class="ci-wind-in sr-only" type="radio"
                   id="<?= e($id) ?>" name="<?= e($name) ?>" value="<?= (int) $option['value'] ?>"
                   <?= $option['value'] === Ecosystem::CALM ? 'checked' : '' ?>>
            <label class="ci-wind-opt <?= e($option['class']) ?>" for="<?= e($id) ?>">
              <span aria-hidden="true"><?= $option['glyph'] ?></span>
              <span class="sr-only"><?= e($domain['label'] . ' — ' . $option['text']) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </fieldset>
    <?php endforeach; ?>
    </div>

    <?php // ✦ çipi her zaman açık ve tek dokunuş. Şeritte dikey çapa olarak
          // çizilecek; ekolojik okumanın en değerli tek verisi bu. ?>
    <div class="ci-q">
      <label class="ci-event" for="olayVar">
        <input type="checkbox" id="olayVar" name="olay_var" value="1" class="ci-event-in sr-only">
        <span class="ci-event-chip" aria-hidden="true">✦</span>
        <span class="ci-q-label"><?= e($texts['olay_baslik']) ?></span>
      </label>
      <p class="field-hint"><?= e($texts['olay_ipucu']) ?></p>

      <?php if ($noteOpen): ?>
        <label for="olay" class="sr-only">Ne olduğunu birkaç kelimeyle yazabilirsin</label>
        <input type="text" id="olay" name="olay" maxlength="120" class="field mt-3"
               placeholder="<?= e($texts['olay_ornek']) ?>">
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="sheet-foot">
    <button class="btn btn-primary w-full justify-center"><?= e($texts['gonder']) ?></button>
  </div>
</form>
