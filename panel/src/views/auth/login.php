<?php use Panel\Csrf; ?>
<h1 class="page-title text-xl mb-5">Giriş yap</h1>

<form method="post" action="<?= e(url('/giris')) ?>" class="space-y-4">
  <?= Csrf::field() ?>

  <div>
    <label for="email" class="field-label">E-posta</label>
    <input type="email" id="email" name="email" required autocomplete="username" autofocus
           value="<?= e(old('email')) ?>" class="field">
  </div>

  <div>
    <label for="password" class="field-label">Şifre</label>
    <input type="password" id="password" name="password" required autocomplete="current-password" class="field">
  </div>

  <button type="submit" class="btn btn-primary w-full justify-center">Giriş yap</button>
</form>

<p class="mt-5">
  <a href="<?= e(url('/sifremi-unuttum')) ?>" class="btn-text">Şifremi unuttum</a>
</p>
