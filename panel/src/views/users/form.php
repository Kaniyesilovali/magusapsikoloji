<?php
use Panel\Csrf;
use Panel\Rbac;
/** @var array|null $user @var array $roles @var array $actor */

$isNew  = $user === null;
$isSelf = !$isNew && (int) $user['id'] === (int) $actor['id'];
$action = $isNew ? url('/kullanicilar/yeni') : url("/kullanicilar/{$user['id']}/duzenle");
$field  = static fn (string $key, string $fallback = ''): string => old($key, $fallback);
// Değiştirilemeyen alanlar alan gibi görünsün ama alan olmadıkları belli olsun:
// aynı kutu, soluk zemin, çerçevesiz odak.
$readonlyCls = 'field bg-warm-secondary text-ink-muted';
?>

<div class="max-w-2xl">

<header class="mb-6">
  <a href="<?= e(url('/kullanicilar')) ?>" class="btn-text btn-text-quiet">← Kullanıcılar</a>
  <p class="eyebrow mt-3">Kullanıcı</p>
  <h1 class="page-title mt-2"><?= $isNew ? 'Yeni kullanıcı' : 'Kullanıcıyı düzenle' ?></h1>
</header>

<form method="post" action="<?= e($action) ?>" class="sheet">
  <div class="sheet-body space-y-5">
    <?= Csrf::field() ?>

    <div>
      <label for="full_name" class="field-label">Ad Soyad</label>
      <input type="text" id="full_name" name="full_name" required class="field"
             value="<?= e($field('full_name', (string) ($user['full_name'] ?? ''))) ?>">
      <?php if ($message = error_for('full_name')): ?>
        <p class="field-error"><?= e($message) ?></p>
      <?php endif; ?>
    </div>

    <div>
      <label for="email" class="field-label">E-posta</label>
      <input type="email" id="email" name="email" required class="field"
             value="<?= e($field('email', (string) ($user['email'] ?? ''))) ?>">
      <?php if ($message = error_for('email')): ?>
        <p class="field-error"><?= e($message) ?></p>
      <?php else: ?>
        <p class="field-hint">Giriş için kullanılır. Davet bağlantısı bu adrese gönderilir.</p>
      <?php endif; ?>
    </div>

    <div>
      <label for="phone" class="field-label">
        Telefon <span class="text-ink-light font-normal">(isteğe bağlı)</span>
      </label>
      <input type="tel" id="phone" name="phone" class="field"
             value="<?= e($field('phone', (string) ($user['phone'] ?? ''))) ?>">
      <?php if ($message = error_for('phone')): ?>
        <p class="field-error"><?= e($message) ?></p>
      <?php endif; ?>
    </div>

    <div>
      <?php // Kendi kaydında rol bir girdi değil; o yüzden label yerine düz metin etiketi. ?>
      <?php if ($isSelf): ?>
        <p class="field-label">Rol</p>
        <p class="<?= $readonlyCls ?>"><?= e(Rbac::label($user['role'])) ?></p>
        <p class="field-hint">Kendi rolünüzü değiştiremezsiniz. Bunu başka bir süper admin yapabilir.</p>
      <?php else: ?>
        <label for="role" class="field-label">Rol</label>
        <select id="role" name="role" required class="field">
          <?php // Yeni kayıtta listenin ilk rolü seçili gelir; eski varsayılan
                // (Görüşmeci) artık bu listede yok, hesabı görüşmeci kaydı açıyor. ?>
          <?php $selectedRole = $field('role', (string) ($user['role'] ?? ($roles[0] ?? ''))); ?>
          <?php foreach ($roles as $role): ?>
            <option value="<?= e($role) ?>" <?= $selectedRole === $role ? 'selected' : '' ?>>
              <?= e(Rbac::label($role)) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if ($message = error_for('role')): ?>
          <p class="field-error"><?= e($message) ?></p>
        <?php elseif ($isNew): ?>
          <p class="field-hint">
            Görüşmeci hesabı buradan açılmaz; görüşmeci kaydı oluşturulurken kendiliğinden açılır.
          </p>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <?php if (!$isNew): ?>
      <div>
        <?php if ($isSelf): ?>
          <p class="field-label">Durum</p>
          <p class="<?= $readonlyCls ?>">Aktif</p>
          <p class="field-hint">Kendi hesabınızı askıya alamazsınız.</p>
        <?php else: ?>
          <?php $selectedStatus = $field('status', (string) $user['status']); ?>
          <label for="status" class="field-label">Durum</label>
          <select id="status" name="status" required class="field">
            <option value="active"    <?= $selectedStatus === 'active'    ? 'selected' : '' ?>>Aktif</option>
            <option value="invited"   <?= $selectedStatus === 'invited'   ? 'selected' : '' ?>>Davetli (şifre belirlenmedi)</option>
            <option value="suspended" <?= $selectedStatus === 'suspended' ? 'selected' : '' ?>>Askıda (giriş yapamaz)</option>
          </select>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <?php // note-info beyaz zeminlidir; beyaz yaprağın içinde ayrışması için warm veriliyor. ?>
      <div class="note note-info">
        Kullanıcı <strong>Davetli</strong> olarak oluşturulur. Şifresini kendisi, e-postasına gidecek
        bağlantıyla belirler — panelde kimsenin şifresi görünmez.
      </div>
    <?php endif; ?>
  </div>

  <div class="sheet-foot flex items-center gap-4">
    <button type="submit" class="btn btn-primary">
      <?= $isNew ? 'Oluştur ve davet gönder' : 'Kaydet' ?>
    </button>
    <a href="<?= e(url('/kullanicilar')) ?>" class="btn-text btn-text-quiet">Vazgeç</a>
  </div>
</form>

</div>
