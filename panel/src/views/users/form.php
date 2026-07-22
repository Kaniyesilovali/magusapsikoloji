<?php
use Panel\Csrf;
use Panel\Rbac;
/** @var array|null $user @var array $roles @var array $actor */

$isNew    = $user === null;
$isSelf   = !$isNew && (int) $user['id'] === (int) $actor['id'];
$action   = $isNew ? url('/kullanicilar/yeni') : url("/kullanicilar/{$user['id']}/duzenle");
$field    = static fn (string $key, string $fallback = ''): string => old($key, $fallback);
$inputCls = 'w-full px-3 py-2.5 rounded-xl border border-warm-tertiary bg-warm focus:bg-white focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 text-sm';
?>

<header class="mb-6">
  <a href="<?= e(url('/kullanicilar')) ?>" class="text-sm text-ink-muted hover:text-ink">← Kullanıcılar</a>
  <h1 class="text-2xl font-semibold text-ink mt-2"><?= $isNew ? 'Yeni kullanıcı' : 'Kullanıcıyı düzenle' ?></h1>
</header>

<form method="post" action="<?= e($action) ?>" class="bg-white border border-warm-tertiary rounded-2xl p-6 space-y-5 max-w-xl">
  <?= Csrf::field() ?>

  <div>
    <label for="full_name" class="block text-sm font-medium text-ink mb-1.5">Ad Soyad</label>
    <input type="text" id="full_name" name="full_name" required class="<?= $inputCls ?>"
           value="<?= e($field('full_name', (string) ($user['full_name'] ?? ''))) ?>">
    <?php if ($message = error_for('full_name')): ?>
      <p class="text-xs text-accent-dark mt-1.5"><?= e($message) ?></p>
    <?php endif; ?>
  </div>

  <div>
    <label for="email" class="block text-sm font-medium text-ink mb-1.5">E-posta</label>
    <input type="email" id="email" name="email" required class="<?= $inputCls ?>"
           value="<?= e($field('email', (string) ($user['email'] ?? ''))) ?>">
    <?php if ($message = error_for('email')): ?>
      <p class="text-xs text-accent-dark mt-1.5"><?= e($message) ?></p>
    <?php else: ?>
      <p class="text-xs text-ink-light mt-1.5">Giriş için kullanılır. Davet bağlantısı bu adrese gönderilir.</p>
    <?php endif; ?>
  </div>

  <div>
    <label for="phone" class="block text-sm font-medium text-ink mb-1.5">Telefon <span class="text-ink-light font-normal">(isteğe bağlı)</span></label>
    <input type="tel" id="phone" name="phone" class="<?= $inputCls ?>"
           value="<?= e($field('phone', (string) ($user['phone'] ?? ''))) ?>">
    <?php if ($message = error_for('phone')): ?>
      <p class="text-xs text-accent-dark mt-1.5"><?= e($message) ?></p>
    <?php endif; ?>
  </div>

  <div>
    <label for="role" class="block text-sm font-medium text-ink mb-1.5">Rol</label>
    <?php if ($isSelf): ?>
      <p class="px-3 py-2.5 rounded-xl bg-warm-secondary text-sm text-ink-muted"><?= e(Rbac::label($user['role'])) ?></p>
      <p class="text-xs text-ink-light mt-1.5">Kendi rolünüzü değiştiremezsiniz. Bunu başka bir süper admin yapabilir.</p>
    <?php else: ?>
      <select id="role" name="role" required class="<?= $inputCls ?>">
        <?php $selectedRole = $field('role', (string) ($user['role'] ?? Rbac::CLIENT)); ?>
        <?php foreach ($roles as $role): ?>
          <option value="<?= e($role) ?>" <?= $selectedRole === $role ? 'selected' : '' ?>><?= e(Rbac::label($role)) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if ($message = error_for('role')): ?>
        <p class="text-xs text-accent-dark mt-1.5"><?= e($message) ?></p>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <?php if (!$isNew): ?>
    <div>
      <label for="status" class="block text-sm font-medium text-ink mb-1.5">Durum</label>
      <?php if ($isSelf): ?>
        <p class="px-3 py-2.5 rounded-xl bg-warm-secondary text-sm text-ink-muted">Aktif</p>
        <p class="text-xs text-ink-light mt-1.5">Kendi hesabınızı askıya alamazsınız.</p>
      <?php else: ?>
        <?php $selectedStatus = $field('status', (string) $user['status']); ?>
        <select id="status" name="status" required class="<?= $inputCls ?>">
          <option value="active"    <?= $selectedStatus === 'active'    ? 'selected' : '' ?>>Aktif</option>
          <option value="invited"   <?= $selectedStatus === 'invited'   ? 'selected' : '' ?>>Davetli (şifre belirlenmedi)</option>
          <option value="suspended" <?= $selectedStatus === 'suspended' ? 'selected' : '' ?>>Askıda (giriş yapamaz)</option>
        </select>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="bg-warm rounded-xl p-4 text-sm text-ink-muted">
      Kullanıcı <strong>Davetli</strong> olarak oluşturulur. Şifresini kendisi, e-postasına gidecek
      bağlantıyla belirler — panelde kimsenin şifresi görünmez.
    </div>
  <?php endif; ?>

  <div class="flex gap-3 pt-1">
    <button type="submit" class="bg-primary hover:bg-primary-hover text-white text-sm font-medium px-5 py-2.5 rounded-xl transition-colors">
      <?= $isNew ? 'Oluştur ve davet gönder' : 'Kaydet' ?>
    </button>
    <a href="<?= e(url('/kullanicilar')) ?>" class="text-sm text-ink-muted hover:text-ink px-4 py-2.5">Vazgeç</a>
  </div>
</form>
