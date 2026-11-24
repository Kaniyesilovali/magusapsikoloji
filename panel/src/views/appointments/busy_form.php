<?php
use Panel\Csrf;
/** Özel zaman — terapistin takvimde kapattığı saat aralığı. */
/** @var array|null $busy @var array $therapists @var bool $ownNote @var array $defaults @var array $actor */

$isNew  = $busy === null;
$action = $isNew ? url('/randevular/ozel-zaman/yeni') : url("/randevular/ozel-zaman/{$busy['id']}/duzenle");
$back   = $isNew ? '/randevular' : '/randevular?tarih=' . substr((string) $busy['starts_at'], 0, 10);

$field = static fn (string $key, string $saved = ''): string
    => old($key, $saved !== '' ? $saved : (string) ($defaults[$key] ?? ''));

$startsAt = $isNew ? '' : (string) $busy['starts_at'];
$endsAt   = $isNew ? '' : (string) $busy['ends_at'];
?>

<div class="max-w-2xl">

<header class="mb-6">
  <a href="<?= e(url($back)) ?>" class="btn-text btn-text-quiet">← Randevular</a>
  <h1 class="page-title mt-2"><?= $isNew ? 'Özel zaman ekle' : 'Özel zamanı düzenle' ?></h1>
  <p class="page-sub">Bu saatler takvimde kırmızı “Dolu” görünür ve randevu yazılamaz.</p>
</header>

<form method="post" action="<?= e($action) ?>" class="sheet">
  <?= Csrf::field() ?>

  <div class="sheet-body space-y-5">
    <?php if ($therapists !== []): ?>
      <div>
        <label for="therapist_id" class="field-label">Terapist</label>
        <?php $selected = $field('therapist_id'); ?>
        <select id="therapist_id" name="therapist_id" required class="field">
          <option value="">— seçin —</option>
          <?php foreach ($therapists as $therapist): ?>
            <option value="<?= (int) $therapist['id'] ?>" <?= $selected === (string) $therapist['id'] ? 'selected' : '' ?>>
              <?= e($therapist['full_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if ($message = error_for('therapist_id')): ?>
          <p class="field-error"><?= e($message) ?></p>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div>
        <p class="field-label">Terapist</p>
        <p class="bg-warm-secondary rounded-md px-3 py-2.5 text-sm text-ink-muted person">
          <?= e((string) ($busy['therapist_name'] ?? $actor['full_name'])) ?>
        </p>
      </div>
    <?php endif; ?>

    <div class="grid sm:grid-cols-3 gap-4">
      <div>
        <label for="date" class="field-label">Tarih</label>
        <input type="date" id="date" name="date" required class="field num"
               value="<?= e($field('date', substr($startsAt, 0, 10))) ?>">
      </div>
      <div>
        <label for="start_time" class="field-label">Başlangıç</label>
        <input type="text" inputmode="numeric" autocomplete="off" maxlength="5" size="5" placeholder="10:00" pattern="([01]?[0-9]|2[0-3])([:.]?[0-5][0-9])?" title="24 saat biçiminde yazın, örn. 14:00" data-time id="start_time" name="start_time" required class="field num"
               value="<?= e($field('start_time', substr($startsAt, 11, 5))) ?>">
      </div>
      <div>
        <label for="end_time" class="field-label">Bitiş</label>
        <input type="text" inputmode="numeric" autocomplete="off" maxlength="5" size="5" placeholder="12:00" pattern="([01]?[0-9]|2[0-3])([:.]?[0-5][0-9])?" title="24 saat biçiminde yazın, örn. 14:00" data-time id="end_time" name="end_time" required class="field num"
               value="<?= e($field('end_time', substr($endsAt, 11, 5))) ?>">
      </div>
    </div>
    <?php if ($message = error_for('date')): ?>
      <p class="text-xs text-accent-dark -mt-3"><?= e($message) ?></p>
    <?php endif; ?>

    <?php if ($ownNote): ?>
      <div>
        <label for="note" class="field-label">
          Not <span class="font-normal text-ink-light">(isteğe bağlı)</span>
        </label>
        <input type="text" id="note" name="note" maxlength="150" class="field"
               value="<?= e($field('note', (string) ($busy['note'] ?? ''))) ?>">
        <p class="field-hint">Notu panelde yalnız siz görürsünüz; diğerleri bu saatte yalnız “Dolu” görür.</p>
      </div>
    <?php endif; ?>
  </div>

  <div class="sheet-foot flex flex-wrap gap-3">
    <button type="submit" class="btn btn-primary">Kaydet</button>
    <a href="<?= e(url($back)) ?>" class="btn btn-quiet">Vazgeç</a>
  </div>
</form>

<?php if (!$isNew): ?>
  <form method="post" action="<?= e(url("/randevular/ozel-zaman/{$busy['id']}/sil")) ?>" class="mt-4"
        data-confirm="Özel zaman silinsin mi? Saat yeniden randevuya açılır.">
    <?= Csrf::field() ?>
    <button class="btn-text btn-text-danger">Özel zamanı sil</button>
  </form>
<?php endif; ?>

</div>
