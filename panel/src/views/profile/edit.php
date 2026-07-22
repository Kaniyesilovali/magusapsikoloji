<?php
use Panel\Csrf;
use Panel\Rbac;
/** @var array $user */
// E-posta değiştirilemez; alan gibi görünsün ama alan olmadığı belli olsun.
$readonlyCls = 'field bg-warm-secondary text-ink-muted';
?>

<div class="max-w-2xl">

<header class="mb-6">
  <p class="eyebrow">Hesap</p>
  <h1 class="page-title mt-2">Profilim</h1>
  <p class="page-sub"><?= e(Rbac::label($user['role'])) ?></p>
</header>

<form method="post" action="<?= e(url('/profil')) ?>" class="sheet">
  <div class="sheet-body space-y-5">
    <?= Csrf::field() ?>

    <div>
      <label for="full_name" class="field-label">Ad Soyad</label>
      <input type="text" id="full_name" name="full_name" required class="field"
             value="<?= e(old('full_name', (string) $user['full_name'])) ?>">
      <?php if ($message = error_for('full_name')): ?>
        <p class="field-error"><?= e($message) ?></p>
      <?php endif; ?>
    </div>

    <div>
      <label for="phone" class="field-label">Telefon</label>
      <input type="tel" id="phone" name="phone" class="field"
             value="<?= e(old('phone', (string) ($user['phone'] ?? ''))) ?>">
      <?php if ($message = error_for('phone')): ?>
        <p class="field-error"><?= e($message) ?></p>
      <?php endif; ?>
    </div>

    <div>
      <?php // Girdi olmadığı için <label> değil: label'ın "for"u yalnız alanları gösterebilir. ?>
      <p class="field-label">E-posta</p>
      <p class="<?= $readonlyCls ?>"><?= e($user['email']) ?></p>
      <p class="field-hint">
        E-posta aynı zamanda giriş bilginizdir; değişikliği yöneticinizden isteyin.
      </p>
    </div>
  </div>

  <div class="sheet-foot">
    <button type="submit" class="btn btn-primary">Kaydet</button>
  </div>
</form>

<div class="sheet mt-4">
  <div class="sheet-body">
    <h2 class="sheet-title">Şifre</h2>
    <p class="text-sm text-ink-light mt-1 mb-4">
      Son giriş: <span class="num"><?= e(dt($user['last_login_at'])) ?></span>
    </p>
    <a href="<?= e(url('/profil/sifre')) ?>" class="btn btn-quiet">Şifremi değiştir</a>
  </div>
</div>

</div>
