<?php
use Panel\Config;
use Panel\Csrf;
/** @var array $user @var bool $forced */
$inputCls = 'w-full px-3 py-2.5 rounded-xl border border-warm-tertiary bg-warm focus:bg-white focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 text-sm';
$min = (int) Config::get('security.password_min', 10);
?>

<header class="mb-6">
  <?php if (!$forced): ?>
    <a href="<?= e(url('/profil')) ?>" class="text-sm text-ink-muted hover:text-ink">← Profilim</a>
  <?php endif; ?>
  <h1 class="text-2xl font-semibold text-ink mt-2">Şifre değiştir</h1>
  <?php if ($forced): ?>
    <p class="text-sm text-accent-dark mt-1">Devam edebilmek için yeni bir şifre belirlemeniz gerekiyor.</p>
  <?php endif; ?>
</header>

<form method="post" action="<?= e(url('/profil/sifre')) ?>" class="bg-white border border-warm-tertiary rounded-2xl p-6 space-y-5 max-w-md">
  <?= Csrf::field() ?>

  <div>
    <label for="current_password" class="block text-sm font-medium text-ink mb-1.5">Mevcut şifreniz</label>
    <input type="password" id="current_password" name="current_password" required autocomplete="current-password" class="<?= $inputCls ?>">
  </div>

  <div>
    <label for="password" class="block text-sm font-medium text-ink mb-1.5">Yeni şifre</label>
    <input type="password" id="password" name="password" required autocomplete="new-password" minlength="<?= $min ?>" class="<?= $inputCls ?>">
    <p class="text-xs text-ink-light mt-1.5">En az <?= $min ?> karakter, yalnızca rakam olamaz.</p>
  </div>

  <div>
    <label for="password_confirm" class="block text-sm font-medium text-ink mb-1.5">Yeni şifre (tekrar)</label>
    <input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password" class="<?= $inputCls ?>">
  </div>

  <div class="pt-1">
    <button type="submit" class="bg-primary hover:bg-primary-hover text-white text-sm font-medium px-5 py-2.5 rounded-xl transition-colors">
      Şifreyi güncelle
    </button>
  </div>
</form>
