<?php
use Panel\Csrf;
/** @var array|null $client @var array $therapists @var array $accounts @var bool $isManager @var array $actor */

$isNew    = $client === null;
$action   = $isNew ? url('/danisanlar/yeni') : url("/danisanlar/{$client['id']}/duzenle");
$field    = static fn (string $key, string $fallback = ''): string => old($key, $fallback);
$inputCls = 'w-full px-3 py-2.5 rounded-xl border border-warm-tertiary bg-warm focus:bg-white focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 text-sm';

// Rıza kutusunun ilk hâli: kayıtta rıza varsa işaretli gelir.
$consentChecked = errors() !== [] ? old('consent') !== '' : ($client['consent_at'] ?? null) !== null;
?>

<header class="mb-6">
  <a href="<?= e(url($isNew ? '/danisanlar' : "/danisanlar/{$client['id']}")) ?>" class="text-sm text-ink-muted hover:text-ink">
    ← <?= $isNew ? 'Danışanlar' : e($client['full_name']) ?>
  </a>
  <h1 class="text-2xl font-semibold text-ink mt-2"><?= $isNew ? 'Yeni danışan' : 'Danışanı düzenle' ?></h1>
</header>

<form method="post" action="<?= e($action) ?>" class="bg-white border border-warm-tertiary rounded-2xl p-6 space-y-5 max-w-xl">
  <?= Csrf::field() ?>

  <div>
    <label for="full_name" class="block text-sm font-medium text-ink mb-1.5">Ad Soyad</label>
    <input type="text" id="full_name" name="full_name" required class="<?= $inputCls ?>"
           value="<?= e($field('full_name', (string) ($client['full_name'] ?? ''))) ?>">
    <?php if ($message = error_for('full_name')): ?>
      <p class="text-xs text-accent-dark mt-1.5"><?= e($message) ?></p>
    <?php endif; ?>
  </div>

  <div class="grid sm:grid-cols-2 gap-4">
    <div>
      <label for="phone" class="block text-sm font-medium text-ink mb-1.5">Telefon</label>
      <input type="tel" id="phone" name="phone" class="<?= $inputCls ?>"
             value="<?= e($field('phone', (string) ($client['phone'] ?? ''))) ?>">
      <?php if ($message = error_for('phone')): ?>
        <p class="text-xs text-accent-dark mt-1.5"><?= e($message) ?></p>
      <?php endif; ?>
    </div>
    <div>
      <label for="birth_date" class="block text-sm font-medium text-ink mb-1.5">Doğum tarihi <span class="text-ink-light font-normal">(isteğe bağlı)</span></label>
      <input type="date" id="birth_date" name="birth_date" class="<?= $inputCls ?>"
             value="<?= e($field('birth_date', (string) ($client['birth_date'] ?? ''))) ?>">
      <?php if ($message = error_for('birth_date')): ?>
        <p class="text-xs text-accent-dark mt-1.5"><?= e($message) ?></p>
      <?php endif; ?>
    </div>
  </div>

  <div>
    <label for="email" class="block text-sm font-medium text-ink mb-1.5">E-posta <span class="text-ink-light font-normal">(isteğe bağlı)</span></label>
    <input type="email" id="email" name="email" class="<?= $inputCls ?>"
           value="<?= e($field('email', (string) ($client['email'] ?? ''))) ?>">
    <?php if ($message = error_for('email')): ?>
      <p class="text-xs text-accent-dark mt-1.5"><?= e($message) ?></p>
    <?php endif; ?>
  </div>

  <?php if ($isManager): ?>
    <div>
      <label for="primary_therapist_id" class="block text-sm font-medium text-ink mb-1.5">Birincil terapist</label>
      <?php $selected = $field('primary_therapist_id', (string) ($client['primary_therapist_id'] ?? '')); ?>
      <select id="primary_therapist_id" name="primary_therapist_id" class="<?= $inputCls ?>">
        <option value="">— seçilmedi —</option>
        <?php foreach ($therapists as $therapist): ?>
          <option value="<?= (int) $therapist['id'] ?>" <?= $selected === (string) $therapist['id'] ? 'selected' : '' ?>>
            <?= e($therapist['full_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?php if ($message = error_for('primary_therapist_id')): ?>
        <p class="text-xs text-accent-dark mt-1.5"><?= e($message) ?></p>
      <?php else: ?>
        <p class="text-xs text-ink-light mt-1.5">Danışanın kaydı bu terapiste görünür olur.</p>
      <?php endif; ?>
    </div>

    <div>
      <label for="user_id" class="block text-sm font-medium text-ink mb-1.5">Panel hesabı <span class="text-ink-light font-normal">(isteğe bağlı)</span></label>
      <?php $selectedAccount = $field('user_id', (string) ($client['user_id'] ?? '')); ?>
      <select id="user_id" name="user_id" class="<?= $inputCls ?>">
        <option value="">— bağlı değil —</option>
        <?php foreach ($accounts as $account): ?>
          <option value="<?= (int) $account['id'] ?>" <?= $selectedAccount === (string) $account['id'] ? 'selected' : '' ?>>
            <?= e($account['full_name']) ?> — <?= e($account['email']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?php if ($message = error_for('user_id')): ?>
        <p class="text-xs text-accent-dark mt-1.5"><?= e($message) ?></p>
      <?php else: ?>
        <p class="text-xs text-ink-light mt-1.5">
          Bağlanırsa danışan kendi randevularını panelden görebilir. Önce "Danışan" rolünde bir kullanıcı oluşturulmalıdır.
        </p>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="bg-warm rounded-xl p-4">
    <label class="flex gap-3 items-start cursor-pointer">
      <input type="checkbox" name="consent" value="1" class="mt-0.5 w-4 h-4 accent-primary" <?= $consentChecked ? 'checked' : '' ?>>
      <span class="text-sm text-ink">
        Aydınlatma metni okundu, danışandan <strong>açık rıza</strong> alındı.
        <span class="block text-xs text-ink-muted mt-1">
          Seans kayıtları özel nitelikli sağlık verisidir; rıza olmadan işlenemez.
          <?php if (!$isNew && $client['consent_at'] !== null): ?>
            <br>Kayıtlı rıza: <?= e(dt($client['consent_at'])) ?> — sürüm <?= e((string) $client['consent_version']) ?>
          <?php endif; ?>
        </span>
      </span>
    </label>
  </div>

  <div class="flex gap-3 pt-1">
    <button type="submit" class="bg-primary hover:bg-primary-hover text-white text-sm font-medium px-5 py-2.5 rounded-xl transition-colors">
      <?= $isNew ? 'Kaydı oluştur' : 'Kaydet' ?>
    </button>
    <a href="<?= e(url($isNew ? '/danisanlar' : "/danisanlar/{$client['id']}")) ?>" class="text-sm text-ink-muted hover:text-ink px-4 py-2.5">Vazgeç</a>
  </div>
</form>
