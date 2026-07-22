<?php use Panel\Csrf; ?>
<h1 class="text-lg font-semibold text-ink mb-1">Şifremi unuttum</h1>
<p class="text-sm text-ink-light mb-6">E-posta adresinizi girin; hesabınız varsa sıfırlama bağlantısı gönderilir.</p>

<form method="post" action="<?= e(url('/sifremi-unuttum')) ?>" class="space-y-4">
  <?= Csrf::field() ?>

  <div>
    <label for="email" class="block text-sm font-medium text-ink mb-1.5">E-posta</label>
    <input type="email" id="email" name="email" required autocomplete="username" autofocus
           value="<?= e(old('email')) ?>"
           class="w-full px-3 py-2.5 rounded-xl border border-warm-tertiary bg-warm focus:bg-white focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 text-sm">
  </div>

  <button type="submit"
          class="w-full bg-primary hover:bg-primary-hover text-white font-medium py-2.5 rounded-xl transition-colors text-sm">
    Sıfırlama bağlantısı gönder
  </button>
</form>

<p class="text-center mt-5">
  <a href="<?= e(url('/giris')) ?>" class="text-sm text-ink-muted hover:text-ink">← Girişe dön</a>
</p>
