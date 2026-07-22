<?php use Panel\Csrf; ?>
<h1 class="page-title text-xl mb-5">Şifremi unuttum</h1>
<p class="text-sm text-ink-muted -mt-3 mb-5">
  E-posta adresinizi girin; hesabınız varsa sıfırlama bağlantısı gönderilir.
</p>

<form method="post" action="<?= e(url('/sifremi-unuttum')) ?>" class="space-y-4">
  <?= Csrf::field() ?>

  <div>
    <label for="email" class="field-label">E-posta</label>
    <input type="email" id="email" name="email" required autocomplete="username" autofocus
           value="<?= e(old('email')) ?>" class="field">
  </div>

  <button type="submit" class="btn btn-primary w-full justify-center">
    Sıfırlama bağlantısı gönder
  </button>
</form>

<p class="mt-5">
  <a href="<?= e(url('/giris')) ?>" class="btn-text btn-text-quiet">← Girişe dön</a>
</p>
