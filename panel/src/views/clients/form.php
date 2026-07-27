<?php
use Panel\Csrf;
/** @var array|null $client @var array $therapists @var bool $isManager @var array $actor */

$isNew  = $client === null;
$action = $isNew ? url('/danisanlar/yeni') : url("/danisanlar/{$client['id']}/duzenle");
$field  = static fn (string $key, string $fallback = ''): string => old($key, $fallback);

// Rıza kutusunun ilk hâli: kayıtta rıza varsa işaretli gelir.
$consentChecked = errors() !== [] ? old('consent') !== '' : ($client['consent_at'] ?? null) !== null;
?>

<?php // Form sayfası dar tutulur: içerik sütunu 6xl, alanlar o genişlikte okunmuyor. ?>
<div class="max-w-2xl">

<header class="mb-6">
  <?php // Geri bağlantısı burada eyebrow'un yerini tutuyor: nereden gelindiğini o söylüyor. ?>
  <a href="<?= e(url($isNew ? '/danisanlar' : "/danisanlar/{$client['id']}")) ?>" class="btn-text btn-text-quiet">
    ← <?= $isNew ? 'Görüşmeciler' : e($client['full_name']) ?>
  </a>
  <h1 class="page-title mt-2"><?= $isNew ? 'Yeni görüşmeci' : 'Görüşmeciyi düzenle' ?></h1>
</header>

<form method="post" action="<?= e($action) ?>" class="sheet">
  <?= Csrf::field() ?>

  <div class="sheet-body space-y-5">
    <div>
      <label for="full_name" class="field-label">Ad Soyad</label>
      <input type="text" id="full_name" name="full_name" required class="field"
             value="<?= e($field('full_name', (string) ($client['full_name'] ?? ''))) ?>">
      <?php if ($message = error_for('full_name')): ?>
        <p class="field-error"><?= e($message) ?></p>
      <?php endif; ?>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label for="phone" class="field-label">Telefon</label>
        <input type="tel" id="phone" name="phone" class="field num"
               value="<?= e($field('phone', (string) ($client['phone'] ?? ''))) ?>">
        <?php if ($message = error_for('phone')): ?>
          <p class="field-error"><?= e($message) ?></p>
        <?php endif; ?>
      </div>
      <div>
        <label for="birth_date" class="field-label">
          Doğum tarihi <span class="font-normal text-ink-light">(isteğe bağlı)</span>
        </label>
        <input type="date" id="birth_date" name="birth_date" class="field num"
               value="<?= e($field('birth_date', (string) ($client['birth_date'] ?? ''))) ?>">
        <?php if ($message = error_for('birth_date')): ?>
          <p class="field-error"><?= e($message) ?></p>
        <?php endif; ?>
      </div>
    </div>

    <div>
      <label for="email" class="field-label">
        E-posta <span class="font-normal text-ink-light">(isteğe bağlı)</span>
      </label>
      <input type="email" id="email" name="email" class="field"
             value="<?= e($field('email', (string) ($client['email'] ?? ''))) ?>">
      <?php if ($message = error_for('email')): ?>
        <p class="field-error"><?= e($message) ?></p>
      <?php elseif ($isNew): ?>
        <p class="field-hint">
          Randevu hatırlatmaları buraya gider. Ayrıca görüşmeci için bir panel hesabı açılır ve
          şifresini belirleyeceği davet bağlantısı bu adrese gönderilir — böylece kendi
          randevularını ve ödeme durumunu görebilir.
        </p>
      <?php endif; ?>
    </div>

    <?php if ($isManager): ?>
      <div>
        <label for="primary_therapist_id" class="field-label">Birincil terapist</label>
        <?php $selected = $field('primary_therapist_id', (string) ($client['primary_therapist_id'] ?? '')); ?>
        <select id="primary_therapist_id" name="primary_therapist_id" class="field">
          <option value="">— seçilmedi —</option>
          <?php foreach ($therapists as $therapist): ?>
            <option value="<?= (int) $therapist['id'] ?>" <?= $selected === (string) $therapist['id'] ? 'selected' : '' ?>>
              <?= e($therapist['full_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if ($message = error_for('primary_therapist_id')): ?>
          <p class="field-error"><?= e($message) ?></p>
        <?php else: ?>
          <p class="field-hint">Görüşmecinin kaydı bu terapiste görünür olur.</p>
        <?php endif; ?>
      </div>

    <?php endif; ?>

    <div class="bg-warm rounded-md p-4">
      <label class="flex gap-3 items-start cursor-pointer">
        <input type="checkbox" name="consent" value="1" class="mt-0.5 w-4 h-4 accent-primary"
               <?= $consentChecked ? 'checked' : '' ?>>
        <span class="text-sm text-ink">
          Aydınlatma metni okundu, görüşmeciden <strong>açık rıza</strong> alındı.
          <span class="block text-xs text-ink-muted mt-1">
            Seans kayıtları özel nitelikli sağlık verisidir; rıza olmadan işlenemez.
            <?php if (!$isNew && $client['consent_at'] !== null): ?>
              <br>Kayıtlı rıza: <span class="num"><?= e(dt($client['consent_at'])) ?></span> —
              sürüm <?= e((string) $client['consent_version']) ?>
            <?php endif; ?>
          </span>
        </span>
      </label>
    </div>
  </div>

  <div class="sheet-foot flex flex-wrap gap-3">
    <button type="submit" class="btn btn-primary"><?= $isNew ? 'Kaydı oluştur' : 'Kaydet' ?></button>
    <a href="<?= e(url($isNew ? '/danisanlar' : "/danisanlar/{$client['id']}")) ?>" class="btn btn-quiet">Vazgeç</a>
  </div>
</form>

</div>
