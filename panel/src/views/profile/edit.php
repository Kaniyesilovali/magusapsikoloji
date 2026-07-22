<?php
use Panel\Csrf;
use Panel\Rbac;
/** @var array $user */
$inputCls = 'w-full px-3 py-2.5 rounded-xl border border-warm-tertiary bg-warm focus:bg-white focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 text-sm';
?>

<header class="mb-6">
  <h1 class="text-2xl font-semibold text-ink">Profilim</h1>
  <p class="text-sm text-ink-light mt-1"><?= e(Rbac::label($user['role'])) ?></p>
</header>

<form method="post" action="<?= e(url('/profil')) ?>" class="bg-white border border-warm-tertiary rounded-2xl p-6 space-y-5 max-w-xl">
  <?= Csrf::field() ?>

  <div>
    <label for="full_name" class="block text-sm font-medium text-ink mb-1.5">Ad Soyad</label>
    <input type="text" id="full_name" name="full_name" required class="<?= $inputCls ?>"
           value="<?= e(old('full_name', (string) $user['full_name'])) ?>">
    <?php if ($message = error_for('full_name')): ?>
      <p class="text-xs text-accent-dark mt-1.5"><?= e($message) ?></p>
    <?php endif; ?>
  </div>

  <div>
    <label for="phone" class="block text-sm font-medium text-ink mb-1.5">Telefon</label>
    <input type="tel" id="phone" name="phone" class="<?= $inputCls ?>"
           value="<?= e(old('phone', (string) ($user['phone'] ?? ''))) ?>">
    <?php if ($message = error_for('phone')): ?>
      <p class="text-xs text-accent-dark mt-1.5"><?= e($message) ?></p>
    <?php endif; ?>
  </div>

  <div>
    <label class="block text-sm font-medium text-ink mb-1.5">E-posta</label>
    <p class="px-3 py-2.5 rounded-xl bg-warm-secondary text-sm text-ink-muted"><?= e($user['email']) ?></p>
    <p class="text-xs text-ink-light mt-1.5">E-posta aynı zamanda giriş bilginizdir; değişikliği yöneticinizden isteyin.</p>
  </div>

  <div class="pt-1">
    <button type="submit" class="bg-primary hover:bg-primary-hover text-white text-sm font-medium px-5 py-2.5 rounded-xl transition-colors">
      Kaydet
    </button>
  </div>
</form>

<div class="bg-white border border-warm-tertiary rounded-2xl p-6 mt-4 max-w-xl">
  <h2 class="font-medium text-ink">Şifre</h2>
  <p class="text-sm text-ink-light mt-1 mb-4">
    Son giriş: <?= e(dt($user['last_login_at'])) ?>
  </p>
  <a href="<?= e(url('/profil/sifre')) ?>"
     class="inline-block border border-warm-tertiary hover:border-primary hover:text-primary text-sm font-medium px-4 py-2.5 rounded-xl transition-colors">
    Şifremi değiştir
  </a>
</div>
