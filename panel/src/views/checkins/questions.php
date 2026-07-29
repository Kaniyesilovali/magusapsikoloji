<?php
use Panel\Csrf;
/** @var array<string,string> $questions @var array<string,string> $defaults */
/** @var array<string,array{label:string,low:string,high:string}> $measures */

// Metin düzenlenebilir, ölçek değil. Her sorunun altında yönü yazılı duruyor:
// terapist cümleyi değiştirirken 1'in ve 10'un ne anlama geldiğini görmezse
// "kaygın ne kadar azdı?" gibi ters bir soru yazar ve eğri sessizce yalan söyler.
?>

<header class="mb-6">
  <p class="eyebrow">Seanslar arası check-in</p>
  <h1 class="page-title mt-2">Check-in soruları</h1>
  <p class="page-sub">
    Görüşmeciye haftalık bağlantıda sorulan üç sorunun metni. Ölçek her soruda
    1–10 arası bir kaydırıcı; değişen yalnız cümle.
  </p>
</header>

<form method="post" action="<?= e(url('/check-in-sorulari')) ?>" class="sheet">
  <?= Csrf::field() ?>

  <?php foreach ($questions as $field => $text): ?>
    <?php $measure = $measures[$field]; ?>
    <div class="px-5 py-4 <?= $field === array_key_first($questions) ? '' : 'border-t border-warm-secondary' ?>">
      <label for="<?= e($field) ?>" class="field-label">
        <?= e($measure['label']) ?>
      </label>
      <input type="text" id="<?= e($field) ?>" name="<?= e($field) ?>"
             value="<?= e($text) ?>" maxlength="200" class="field">

      <p class="field-hint">
        Ölçek: <strong>1 · <?= e($measure['low']) ?></strong> → <strong>10 · <?= e($measure['high']) ?></strong>.
        Cümleyi bu yöne uygun kurun; ters çevrilmiş bir soru eğriyi baş aşağı okutur.
      </p>
      <?php if ($text !== $defaults[$field]): ?>
        <p class="field-hint">
          Varsayılan: <span class="text-ink-muted">“<?= e($defaults[$field]) ?>”</span>
          — alanı boşaltıp kaydederseniz buna geri döner.
        </p>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>

  <div class="sheet-foot">
    <button class="btn btn-primary">Kaydet</button>
    <p class="field-hint">
      Değişiklik yalnız bundan sonra üretilen bağlantılarda görünür; şu an
      görüşmecinin elinde bekleyen bir bağlantı varsa o eski metni gösterir.
      Daha önce verilmiş cevaplar etkilenmez — sayılar aynı ölçekte kalır.
    </p>
  </div>
</form>
