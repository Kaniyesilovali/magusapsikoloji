<?php
use Panel\Scales;
/** @var list<array<string,mixed>> $scales @var int $scaleMax */

// Soruların listesi — kaç tane, hangi cümleyle, hangi yönde.
//
// Satırın sırası ekrandaki sırasıdır: form da eğri de bu sırayla çiziliyor.
// Adı boş bırakılan yuva yok sayılır; ekleme işlemi bu, ayrı bir "ekle"
// düğmesi ve JS gerekmiyor.
//
// Yön (yüksek değer iyi mi kötü mü) ADI DEĞİL, VERİDİR: eğrinin nasıl okunacağı
// ona bağlı. Koddan gelen üç ölçekte kilitli — geçmişi on iki hafta geriye giden
// bir eğrinin yönünü değiştirmek, o haftaları da baş aşağı okutur.
$open  = count(array_filter($scales, static fn (array $s): bool => (bool) $s['enabled']));
$slots = max(1, $scaleMax - count($scales));

/** Tek satır. Yeni yuvalar için $scale null gelir. */
$scaleRow = static function (int $index, ?array $scale): void {
    $name    = 'olcek[' . $index . ']';
    $id      = 'olcek_' . $index;
    $builtin = (bool) ($scale['builtin'] ?? false);
    $isNew   = $scale === null;
    ?>
    <div class="px-5 py-4 border-t border-warm-secondary">
      <input type="hidden" name="<?= e($name) ?>[anahtar]" value="<?= e((string) ($scale['key'] ?? '')) ?>">

      <div class="flex items-start justify-between gap-3 flex-wrap">
        <label class="flex items-center gap-2 cursor-pointer" for="<?= e($id) ?>_acik">
          <input type="checkbox" id="<?= e($id) ?>_acik" name="<?= e($name) ?>[acik]" value="1"
                 <?= ($scale['enabled'] ?? true) ? 'checked' : '' ?>>
          <span class="text-sm text-ink">
            <?= $isNew ? 'Boş yuva — ad verilirse eklenir' : 'Formda sorulsun' ?>
          </span>
        </label>

        <?php if (!$isNew && !$builtin): ?>
          <label class="flex items-center gap-2 cursor-pointer" for="<?= e($id) ?>_sil">
            <input type="checkbox" id="<?= e($id) ?>_sil" name="<?= e($name) ?>[sil]" value="1">
            <span class="text-xs text-accent-dark">Listeden kaldır</span>
          </label>
        <?php elseif ($builtin): ?>
          <span class="chip chip-neutral">kapatılabilir, silinemez</span>
        <?php endif; ?>
      </div>

      <div class="mt-3">
        <label class="field-label" for="<?= e($id) ?>_soru">Bireye sorulan cümle</label>
        <input class="field" type="text" id="<?= e($id) ?>_soru" name="<?= e($name) ?>[soru]"
               maxlength="200" value="<?= e((string) ($scale['question'] ?? '')) ?>"
               placeholder="Örn. Bu hafta iştahın nasıldı?">
      </div>

      <div class="mt-2 grid gap-2 sm:grid-cols-4">
        <span>
          <label class="field-label" for="<?= e($id) ?>_ad">Panelde görünen ad</label>
          <input class="field" type="text" id="<?= e($id) ?>_ad" name="<?= e($name) ?>[ad]"
                 maxlength="60" value="<?= e((string) ($scale['label'] ?? '')) ?>"
                 placeholder="Örn. İştah">
        </span>
        <span>
          <label class="field-label" for="<?= e($id) ?>_alt">1 ucu</label>
          <input class="field" type="text" id="<?= e($id) ?>_alt" name="<?= e($name) ?>[alt]"
                 maxlength="60" value="<?= e((string) ($scale['low'] ?? '')) ?>"
                 placeholder="çok kötü">
        </span>
        <span>
          <label class="field-label" for="<?= e($id) ?>_ust">10 ucu</label>
          <input class="field" type="text" id="<?= e($id) ?>_ust" name="<?= e($name) ?>[ust]"
                 maxlength="60" value="<?= e((string) ($scale['high'] ?? '')) ?>"
                 placeholder="çok iyi">
        </span>
        <span>
          <label class="field-label" for="<?= e($id) ?>_yon">Yön</label>
          <?php if ($builtin): ?>
            <?php // Kilitli: yönü değişirse geçmiş de baş aşağı okunur. ?>
            <input type="hidden" name="<?= e($name) ?>[yon]" value="<?= (int) $scale['direction'] ?>">
            <p class="field" id="<?= e($id) ?>_yon">
              <?= (int) $scale['direction'] === Scales::UP_BAD ? '10 kötü (kilitli)' : '10 iyi (kilitli)' ?>
            </p>
          <?php else: ?>
            <select class="field" id="<?= e($id) ?>_yon" name="<?= e($name) ?>[yon]">
              <option value="<?= Scales::UP_GOOD ?>"
                      <?= (int) ($scale['direction'] ?? Scales::UP_GOOD) === Scales::UP_GOOD ? 'selected' : '' ?>>
                10 iyi
              </option>
              <option value="<?= Scales::UP_BAD ?>"
                      <?= (int) ($scale['direction'] ?? Scales::UP_GOOD) === Scales::UP_BAD ? 'selected' : '' ?>>
                10 kötü
              </option>
            </select>
          <?php endif; ?>
        </span>
      </div>
    </div>
    <?php
};
?>

<div class="sheet-head"
     data-tour="21" data-tour-title="Soruları merkez belirler"
     data-tour-text="Kaç soru, hangi cümleyle, hangi sırayla. Adı boş bırakılan yuva yok sayılır — ekleme işlemi bu. Yön (yüksek değer iyi mi kötü mü) eğrinin nasıl okunacağını belirler; koddan gelen üç ölçekte kilitlidir, çünkü değiştirmek geçmiş haftaları da baş aşağı okuturdu. Kapatılan sorunun cevapları silinmez.">
  <div>
    <h2 class="sheet-title">Sorular</h2>
    <p class="text-xs text-ink-light mt-1">
      Her soru 1–10 arası bir kaydırıcı. Şu an
      <span class="num"><?= (int) $open ?></span> soru açık; en fazla
      <span class="num"><?= (int) $scaleMax ?></span> olabilir. Sıralama buradaki
      sıra — formda ve eğride aynı sırayla görünür.
    </p>
  </div>
</div>

<?php $index = 0; ?>
<?php foreach ($scales as $scale): ?>
  <?php $scaleRow($index++, $scale); ?>
<?php endforeach; ?>

<?php for ($i = 0; $i < $slots; $i++): ?>
  <?php $scaleRow($index++, null); ?>
<?php endfor; ?>

<div class="px-5 pb-4">
  <p class="field-hint">
    Kapatılan sorunun <strong>geçmiş cevapları durur</strong>: eğri o satırı
    veri bitene kadar çizmeye devam eder. Listeden kaldırmak da veriyi silmez —
    ölçmeyi bırakmak, ölçtüğünü unutmak değil. Yeni bir soru eklerseniz eğrisi
    ilk cevabın geldiği haftadan başlar; geçmiş haftalar boş kalır, sıfır sayılmaz.
  </p>
</div>
