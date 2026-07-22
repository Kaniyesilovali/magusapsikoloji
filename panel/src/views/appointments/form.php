<?php
use Panel\Csrf;
use Panel\Rbac;
use Panel\Scheduling;
/** @var array|null $appointment @var array $clients @var array $therapists @var array $defaults @var array $actor */

$isNew  = $appointment === null;
$action = $isNew ? url('/randevular/yeni') : url("/randevular/{$appointment['id']}/duzenle");

// Alan değeri: önce hatalı gönderimden dönen eski girdi, sonra kayıt/ön dolgu.
$field = static function (string $key, string $fallback = '') use ($defaults): string {
    return old($key, $fallback !== '' ? $fallback : (string) ($defaults[$key] ?? ''));
};

$startsAt    = $isNew ? null : (string) $appointment['starts_at'];
$isTherapist = $actor['role'] === Rbac::THERAPIST;
$needsAck    = error_for('confirm_warnings') !== null;
?>

<?php // Form sayfası dar tutulur: içerik sütunu 6xl, alanlar o genişlikte okunmuyor. ?>
<div class="max-w-2xl">

<header class="mb-6">
  <a href="<?= e(url('/randevular')) ?>" class="btn-text btn-text-quiet">← Randevular</a>
  <h1 class="page-title mt-2"><?= $isNew ? 'Yeni randevu' : 'Randevuyu düzenle' ?></h1>
</header>

<?php if ($clients === []): ?>
  <div class="sheet">
    <div class="sheet-body">
      <p class="text-sm text-ink">Randevu yazılacak danışan kaydı yok.</p>
      <?php if (Rbac::can($actor, 'client.create')): ?>
        <a href="<?= e(url('/danisanlar/yeni')) ?>" class="btn btn-primary mt-4">Önce danışan ekle</a>
      <?php else: ?>
        <p class="field-hint">Danışan kaydını merkez yönetimi oluşturur.</p>
      <?php endif; ?>
    </div>
  </div>
<?php else: ?>

<form method="post" action="<?= e($action) ?>" class="sheet">
  <?= Csrf::field() ?>

  <div class="sheet-body space-y-5">
    <div>
      <label for="client_id" class="field-label">Danışan</label>
      <?php $selectedClient = $field('client_id', (string) ($appointment['client_id'] ?? '')); ?>
      <select id="client_id" name="client_id" required class="field">
        <option value="">— seçin —</option>
        <?php foreach ($clients as $client): ?>
          <option value="<?= (int) $client['id'] ?>" <?= $selectedClient === (string) $client['id'] ? 'selected' : '' ?>>
            <?= e($client['full_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?php if ($message = error_for('client_id')): ?>
        <p class="field-error"><?= e($message) ?></p>
      <?php endif; ?>
    </div>

    <div>
      <?php if ($isTherapist): ?>
        <?php // Seçilecek bir şey yok: alan yerine sabit değer gösteriliyor, o yüzden
              // <label for> değil düz bir başlık kullanılıyor. ?>
        <p class="field-label">Terapist</p>
        <p class="bg-warm-secondary rounded-md px-3 py-2.5 text-sm text-ink-muted person">
          <?= e($actor['full_name']) ?>
        </p>
        <p class="field-hint">Randevuyu yalnız kendi takviminize yazabilirsiniz.</p>
      <?php else: ?>
        <label for="therapist_id" class="field-label">Terapist</label>
        <?php $selectedTherapist = $field('therapist_id', (string) ($appointment['therapist_id'] ?? '')); ?>
        <select id="therapist_id" name="therapist_id" required class="field">
          <option value="">— seçin —</option>
          <?php foreach ($therapists as $therapist): ?>
            <option value="<?= (int) $therapist['id'] ?>" <?= $selectedTherapist === (string) $therapist['id'] ? 'selected' : '' ?>>
              <?= e($therapist['full_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if ($message = error_for('therapist_id')): ?>
          <p class="field-error"><?= e($message) ?></p>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <div class="grid sm:grid-cols-3 gap-4">
      <div>
        <label for="date" class="field-label">Tarih</label>
        <input type="date" id="date" name="date" required class="field num"
               value="<?= e($field('date', $startsAt !== null ? substr($startsAt, 0, 10) : '')) ?>">
      </div>
      <div>
        <label for="time" class="field-label">Saat</label>
        <input type="time" id="time" name="time" required class="field num"
               value="<?= e($field('time', $startsAt !== null ? substr($startsAt, 11, 5) : '')) ?>">
      </div>
      <div>
        <label for="duration_min" class="field-label">Süre (dk)</label>
        <input type="number" id="duration_min" name="duration_min" required min="15" max="240" step="5"
               class="field num"
               value="<?= e($field('duration_min', (string) ($appointment['duration_min'] ?? ''))) ?>">
      </div>
    </div>
    <?php foreach (['date', 'time', 'duration_min'] as $key): ?>
      <?php if ($message = error_for($key)): ?>
        <p class="text-xs text-accent-dark -mt-3"><?= e($message) ?></p>
      <?php endif; ?>
    <?php endforeach; ?>

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label for="location" class="field-label">Görüşme yeri</label>
        <?php $selectedLocation = $field('location', (string) ($appointment['location'] ?? 'office')); ?>
        <select id="location" name="location" required class="field">
          <?php foreach (Scheduling::LOCATIONS as $value => $label): ?>
            <option value="<?= e($value) ?>" <?= $selectedLocation === $value ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if ($message = error_for('location')): ?>
          <p class="field-error"><?= e($message) ?></p>
        <?php endif; ?>
      </div>

      <?php if (!$isNew): ?>
        <div>
          <label for="status" class="field-label">Durum</label>
          <?php $selectedStatus = $field('status', (string) $appointment['status']); ?>
          <select id="status" name="status" required class="field">
            <?php foreach (Scheduling::STATUSES as $value => $label): ?>
              <?php if ($value === 'cancelled' && $appointment['status'] !== 'cancelled') { continue; } ?>
              <option value="<?= e($value) ?>" <?= $selectedStatus === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
          <p class="field-hint">İptal için listedeki "İptal et" düğmesini kullanın — gerekçe kaydedilir.</p>
        </div>
      <?php endif; ?>
    </div>

    <?php if (Panel\Schema::paymentsReady() && Rbac::canAny($actor, ['payment.view.all', 'payment.view.own'])): ?>
      <div class="sm:w-48">
        <label for="fee" class="field-label">Seans ücreti (₺)</label>
        <input type="text" id="fee" name="fee" inputmode="decimal" class="field num"
               value="<?= e($field('fee', ($appointment['fee'] ?? null) !== null ? number_format((float) $appointment['fee'], 2, ',', '.') : '')) ?>">
        <?php if ($message = error_for('fee')): ?>
          <p class="field-error"><?= e($message) ?></p>
        <?php else: ?>
          <p class="field-hint">Boş bırakılabilir, sonradan Ödemeler ekranından da girilebilir.</p>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div>
      <label for="note" class="field-label">
        İdari not <span class="font-normal text-ink-light">(isteğe bağlı)</span>
      </label>
      <input type="text" id="note" name="note" maxlength="255" class="field"
             value="<?= e($field('note', (string) ($appointment['note'] ?? ''))) ?>">
      <p class="field-hint">
        Yalnız organizasyon notu (ör. "faturayı kurum ödeyecek"). <strong>Klinik içerik yazmayın</strong> —
        seans notları şifreli olarak ayrı tutulur.
      </p>
    </div>

    <?php // Hatalı gönderim sonrası kullanıcının son tercihi korunur, yoksa açık gelir. ?>
    <?php $notifyChecked = errors() !== [] ? old('notify') !== '' : true; ?>
    <div class="bg-warm rounded-md p-4">
      <label class="flex gap-3 items-start cursor-pointer">
        <input type="checkbox" name="notify" value="1" class="mt-0.5 w-4 h-4 accent-primary"
               <?= $notifyChecked ? 'checked' : '' ?>>
        <span class="text-sm text-ink">
          E-posta ile bildir
          <span class="block text-xs text-ink-muted mt-1">
            Danışana (e-posta adresi kayıtlıysa) ve randevunun terapistine tarih/saat bilgisi gönderilir.
            İdari not ve diğer kayıt bilgileri e-postaya konmaz.
          </span>
        </span>
      </label>
    </div>

    <?php if ($needsAck): ?>
      <?php // Çakışma/müsaitlik uyarısı: panelin tek uyarı rengi clay, anlamı da tek —
            // "insan kararı gerekiyor". Kaydı ancak kullanıcı onaylarsa sürdürüyoruz. ?>
      <div class="note note-stop">
        <label class="flex gap-3 items-start cursor-pointer">
          <input type="checkbox" name="confirm_warnings" value="1" class="mt-0.5 w-4 h-4 accent-primary">
          <span>
            Yukarıdaki uyarıları gördüm, randevu yine de kaydedilsin.
            <span class="block text-xs mt-1"><?= e((string) error_for('confirm_warnings')) ?></span>
          </span>
        </label>
      </div>
    <?php endif; ?>
  </div>

  <div class="sheet-foot flex flex-wrap gap-3">
    <button type="submit" class="btn btn-primary"><?= $isNew ? 'Randevuyu oluştur' : 'Kaydet' ?></button>
    <a href="<?= e(url('/randevular')) ?>" class="btn btn-quiet">Vazgeç</a>
  </div>
</form>

<?php endif; ?>

</div>
