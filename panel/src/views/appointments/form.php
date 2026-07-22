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

$startsAt   = $isNew ? null : (string) $appointment['starts_at'];
$inputCls   = 'w-full px-3 py-2.5 rounded-xl border border-warm-tertiary bg-warm focus:bg-white focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 text-sm';
$isTherapist = $actor['role'] === Rbac::THERAPIST;
$needsAck    = error_for('confirm_warnings') !== null;
?>

<header class="mb-6">
  <a href="<?= e(url('/randevular')) ?>" class="text-sm text-ink-muted hover:text-ink">← Randevular</a>
  <h1 class="text-2xl font-semibold text-ink mt-2"><?= $isNew ? 'Yeni randevu' : 'Randevuyu düzenle' ?></h1>
</header>

<?php if ($clients === []): ?>
  <div class="bg-white border border-warm-tertiary rounded-2xl p-6 max-w-xl">
    <p class="text-sm text-ink">Randevu yazılacak danışan kaydı yok.</p>
    <?php if (Rbac::can($actor, 'client.create')): ?>
      <a href="<?= e(url('/danisanlar/yeni')) ?>" class="inline-block mt-4 bg-primary hover:bg-primary-hover text-white text-sm font-medium px-4 py-2.5 rounded-xl">
        Önce danışan ekle
      </a>
    <?php else: ?>
      <p class="text-xs text-ink-light mt-2">Danışan kaydını merkez yönetimi oluşturur.</p>
    <?php endif; ?>
  </div>
<?php else: ?>

<form method="post" action="<?= e($action) ?>" class="bg-white border border-warm-tertiary rounded-2xl p-6 space-y-5 max-w-xl">
  <?= Csrf::field() ?>

  <div>
    <label for="client_id" class="block text-sm font-medium text-ink mb-1.5">Danışan</label>
    <?php $selectedClient = $field('client_id', (string) ($appointment['client_id'] ?? '')); ?>
    <select id="client_id" name="client_id" required class="<?= $inputCls ?>">
      <option value="">— seçin —</option>
      <?php foreach ($clients as $client): ?>
        <option value="<?= (int) $client['id'] ?>" <?= $selectedClient === (string) $client['id'] ? 'selected' : '' ?>>
          <?= e($client['full_name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php if ($message = error_for('client_id')): ?>
      <p class="text-xs text-accent-dark mt-1.5"><?= e($message) ?></p>
    <?php endif; ?>
  </div>

  <div>
    <label for="therapist_id" class="block text-sm font-medium text-ink mb-1.5">Terapist</label>
    <?php if ($isTherapist): ?>
      <p class="px-3 py-2.5 rounded-xl bg-warm-secondary text-sm text-ink-muted"><?= e($actor['full_name']) ?></p>
      <p class="text-xs text-ink-light mt-1.5">Randevuyu yalnız kendi takviminize yazabilirsiniz.</p>
    <?php else: ?>
      <?php $selectedTherapist = $field('therapist_id', (string) ($appointment['therapist_id'] ?? '')); ?>
      <select id="therapist_id" name="therapist_id" required class="<?= $inputCls ?>">
        <option value="">— seçin —</option>
        <?php foreach ($therapists as $therapist): ?>
          <option value="<?= (int) $therapist['id'] ?>" <?= $selectedTherapist === (string) $therapist['id'] ? 'selected' : '' ?>>
            <?= e($therapist['full_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?php if ($message = error_for('therapist_id')): ?>
        <p class="text-xs text-accent-dark mt-1.5"><?= e($message) ?></p>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <div class="grid sm:grid-cols-3 gap-4">
    <div>
      <label for="date" class="block text-sm font-medium text-ink mb-1.5">Tarih</label>
      <input type="date" id="date" name="date" required class="<?= $inputCls ?>"
             value="<?= e($field('date', $startsAt !== null ? substr($startsAt, 0, 10) : '')) ?>">
    </div>
    <div>
      <label for="time" class="block text-sm font-medium text-ink mb-1.5">Saat</label>
      <input type="time" id="time" name="time" required class="<?= $inputCls ?>"
             value="<?= e($field('time', $startsAt !== null ? substr($startsAt, 11, 5) : '')) ?>">
    </div>
    <div>
      <label for="duration_min" class="block text-sm font-medium text-ink mb-1.5">Süre (dk)</label>
      <input type="number" id="duration_min" name="duration_min" required min="15" max="240" step="5" class="<?= $inputCls ?>"
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
      <label for="location" class="block text-sm font-medium text-ink mb-1.5">Görüşme yeri</label>
      <?php $selectedLocation = $field('location', (string) ($appointment['location'] ?? 'office')); ?>
      <select id="location" name="location" required class="<?= $inputCls ?>">
        <?php foreach (Scheduling::LOCATIONS as $value => $label): ?>
          <option value="<?= e($value) ?>" <?= $selectedLocation === $value ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if ($message = error_for('location')): ?>
        <p class="text-xs text-accent-dark mt-1.5"><?= e($message) ?></p>
      <?php endif; ?>
    </div>

    <?php if (!$isNew): ?>
      <div>
        <label for="status" class="block text-sm font-medium text-ink mb-1.5">Durum</label>
        <?php $selectedStatus = $field('status', (string) $appointment['status']); ?>
        <select id="status" name="status" required class="<?= $inputCls ?>">
          <?php foreach (Scheduling::STATUSES as $value => $label): ?>
            <?php if ($value === 'cancelled' && $appointment['status'] !== 'cancelled') { continue; } ?>
            <option value="<?= e($value) ?>" <?= $selectedStatus === $value ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <p class="text-xs text-ink-light mt-1.5">İptal için listedeki "İptal et" düğmesini kullanın — gerekçe kaydedilir.</p>
      </div>
    <?php endif; ?>
  </div>

  <?php if (Panel\Schema::paymentsReady() && Rbac::canAny($actor, ['payment.view.all', 'payment.view.own'])): ?>
    <div class="sm:w-48">
      <label for="fee" class="block text-sm font-medium text-ink mb-1.5">Seans ücreti (₺)</label>
      <input type="text" id="fee" name="fee" inputmode="decimal" class="<?= $inputCls ?>"
             value="<?= e($field('fee', ($appointment['fee'] ?? null) !== null ? number_format((float) $appointment['fee'], 2, ',', '.') : '')) ?>">
      <?php if ($message = error_for('fee')): ?>
        <p class="text-xs text-accent-dark mt-1.5"><?= e($message) ?></p>
      <?php else: ?>
        <p class="text-xs text-ink-light mt-1.5">Boş bırakılabilir, sonradan Ödemeler ekranından da girilebilir.</p>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div>
    <label for="note" class="block text-sm font-medium text-ink mb-1.5">İdari not <span class="text-ink-light font-normal">(isteğe bağlı)</span></label>
    <input type="text" id="note" name="note" maxlength="255" class="<?= $inputCls ?>"
           value="<?= e($field('note', (string) ($appointment['note'] ?? ''))) ?>">
    <p class="text-xs text-ink-light mt-1.5">
      Yalnız organizasyon notu (ör. "faturayı kurum ödeyecek"). <strong>Klinik içerik yazmayın</strong> —
      seans notları şifreli olarak ayrı tutulur.
    </p>
  </div>

  <?php // Hatalı gönderim sonrası kullanıcının son tercihi korunur, yoksa açık gelir. ?>
  <?php $notifyChecked = errors() !== [] ? old('notify') !== '' : true; ?>
  <div class="bg-warm rounded-xl p-4">
    <label class="flex gap-3 items-start cursor-pointer">
      <input type="checkbox" name="notify" value="1" class="mt-0.5 w-4 h-4 accent-primary" <?= $notifyChecked ? 'checked' : '' ?>>
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
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
      <label class="flex gap-3 items-start cursor-pointer">
        <input type="checkbox" name="confirm_warnings" value="1" class="mt-0.5 w-4 h-4 accent-primary">
        <span class="text-sm text-amber-900">
          Yukarıdaki uyarıları gördüm, randevu yine de kaydedilsin.
          <span class="block text-xs mt-1"><?= e((string) error_for('confirm_warnings')) ?></span>
        </span>
      </label>
    </div>
  <?php endif; ?>

  <div class="flex gap-3 pt-1">
    <button type="submit" class="bg-primary hover:bg-primary-hover text-white text-sm font-medium px-5 py-2.5 rounded-xl transition-colors">
      <?= $isNew ? 'Randevuyu oluştur' : 'Kaydet' ?>
    </button>
    <a href="<?= e(url('/randevular')) ?>" class="text-sm text-ink-muted hover:text-ink px-4 py-2.5">Vazgeç</a>
  </div>
</form>

<?php endif; ?>
