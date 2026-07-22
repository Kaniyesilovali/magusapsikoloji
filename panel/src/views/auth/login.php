<?php use Panel\Csrf; ?>
<h1 class="text-lg font-semibold text-ink mb-1">Giriş yap</h1>
<p class="text-sm text-ink-light mb-6">Hesap bilgilerinizle devam edin.</p>

<form method="post" action="<?= e(url('/giris')) ?>" class="space-y-4">
  <?= Csrf::field() ?>

  <div>
    <label for="email" class="block text-sm font-medium text-ink mb-1.5">E-posta</label>
    <input type="email" id="email" name="email" required autocomplete="username" autofocus
           value="<?= e(old('email')) ?>"
           class="w-full px-3 py-2.5 rounded-xl border border-warm-tertiary bg-warm focus:bg-white focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 text-sm">
  </div>

  <div>
    <label for="password" class="block text-sm font-medium text-ink mb-1.5">Şifre</label>
    <input type="password" id="password" name="password" required autocomplete="current-password"
           class="w-full px-3 py-2.5 rounded-xl border border-warm-tertiary bg-warm focus:bg-white focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 text-sm">
  </div>

  <button type="submit"
          class="w-full bg-primary hover:bg-primary-hover text-white font-medium py-2.5 rounded-xl transition-colors text-sm">
    Giriş yap
  </button>
</form>

<p class="text-center mt-5">
  <a href="<?= e(url('/sifremi-unuttum')) ?>" class="text-sm text-primary hover:text-primary-dark">Şifremi unuttum</a>
</p>
