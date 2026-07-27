<?php
use Panel\Config;
use Panel\Csrf;
/** @var array $user @var bool $forced */
$min = (int) Config::get('security.password_min', 10);
?>

<div class="max-w-2xl">

<header class="mb-6">
  <?php if (!$forced): ?>
    <a href="<?= e(url('/profil')) ?>" class="btn-text btn-text-quiet">← Profilim</a>
  <?php endif; ?>
  <p class="eyebrow <?= $forced ? '' : 'mt-3' ?>">Hesap</p>
  <h1 class="page-title mt-2">Şifre değiştir</h1>
  <?php if ($forced): ?>
    <p class="note note-stop mt-3">Devam edebilmek için yeni bir şifre belirlemeniz gerekiyor.</p>
  <?php endif; ?>
</header>

<form method="post" action="<?= e(url('/profil/sifre')) ?>" class="sheet">
  <div class="sheet-body space-y-5">
    <?= Csrf::field() ?>

    <div>
      <label for="current_password" class="field-label">Mevcut şifreniz</label>
      <input type="password" id="current_password" name="current_password" required
             autocomplete="current-password" class="field">
    </div>

    <div>
      <label for="password" class="field-label">Yeni şifre</label>
      <div class="field-reveal">
        <input type="password" id="password" name="password" required autocomplete="new-password"
               minlength="<?= $min ?>" class="field">
        <button type="button" class="btn-text btn-text-quiet field-reveal-btn" data-reveal="#password"
                aria-label="Şifreyi göster" aria-pressed="false" hidden>Göster</button>
      </div>
      <p class="field-hint">En az <?= $min ?> karakter, yalnızca rakam olamaz.</p>
    </div>

    <div>
      <label for="password_confirm" class="field-label">Yeni şifre (tekrar)</label>
      <div class="field-reveal">
        <input type="password" id="password_confirm" name="password_confirm" required
               autocomplete="new-password" class="field">
        <button type="button" class="btn-text btn-text-quiet field-reveal-btn" data-reveal="#password_confirm"
                aria-label="Şifreyi göster" aria-pressed="false" hidden>Göster</button>
      </div>
    </div>
  </div>

  <div class="sheet-foot">
    <button type="submit" class="btn btn-primary">Şifreyi güncelle</button>
  </div>
</form>

</div>
