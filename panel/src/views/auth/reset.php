<?php
use Panel\Config;
use Panel\Csrf;
/** @var bool $invalid @var string $token @var array|null $invite */
?>

<?php if ($invalid): ?>

  <h1 class="text-lg font-semibold text-ink mb-1">Bağlantı geçersiz</h1>
  <p class="text-sm text-ink-muted mb-6">
    Bu bağlantının süresi dolmuş veya daha önce kullanılmış. Yeni bir bağlantı isteyebilirsiniz.
  </p>
  <a href="<?= e(url('/sifremi-unuttum')) ?>"
     class="block text-center w-full bg-primary hover:bg-primary-hover text-white font-medium py-2.5 rounded-xl transition-colors text-sm">
    Yeni bağlantı iste
  </a>

<?php else: ?>

  <h1 class="text-lg font-semibold text-ink mb-1">
    <?= $invite['purpose'] === 'invite' ? 'Hesabınızı etkinleştirin' : 'Yeni şifre belirleyin' ?>
  </h1>
  <p class="text-sm text-ink-light mb-6"><?= e((string) $invite['email']) ?></p>

  <form method="post" action="<?= e(url('/sifre-belirle')) ?>" class="space-y-4">
    <?= Csrf::field() ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">

    <div>
      <label for="password" class="block text-sm font-medium text-ink mb-1.5">Yeni şifre</label>
      <input type="password" id="password" name="password" required autocomplete="new-password" autofocus
             minlength="<?= (int) Config::get('security.password_min', 10) ?>"
             class="w-full px-3 py-2.5 rounded-xl border border-warm-tertiary bg-warm focus:bg-white focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 text-sm">
      <p class="text-xs text-ink-light mt-1.5">En az <?= (int) Config::get('security.password_min', 10) ?> karakter.</p>
    </div>

    <div>
      <label for="password_confirm" class="block text-sm font-medium text-ink mb-1.5">Yeni şifre (tekrar)</label>
      <input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password"
             class="w-full px-3 py-2.5 rounded-xl border border-warm-tertiary bg-warm focus:bg-white focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 text-sm">
    </div>

    <button type="submit"
            class="w-full bg-primary hover:bg-primary-hover text-white font-medium py-2.5 rounded-xl transition-colors text-sm">
      Şifreyi kaydet
    </button>
  </form>

<?php endif; ?>
