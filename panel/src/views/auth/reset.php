<?php
use Panel\Config;
use Panel\Csrf;
/** @var bool $invalid @var string $token @var array|null $invite */
?>

<?php if ($invalid): ?>

  <h1 class="page-title text-xl mb-5">Bağlantı geçersiz</h1>
  <p class="text-sm text-ink-muted -mt-3 mb-5">
    Bu bağlantının süresi dolmuş veya daha önce kullanılmış. Yeni bir bağlantı isteyebilirsiniz.
  </p>
  <a href="<?= e(url('/sifremi-unuttum')) ?>" class="btn btn-primary w-full justify-center">
    Yeni bağlantı iste
  </a>

<?php else: ?>

  <h1 class="page-title text-xl mb-5">
    <?= $invite['purpose'] === 'invite' ? 'Hesabınızı etkinleştirin' : 'Yeni şifre belirleyin' ?>
  </h1>
  <p class="text-sm text-ink-muted -mt-3 mb-5"><?= e((string) $invite['email']) ?></p>

  <form method="post" action="<?= e(url('/sifre-belirle')) ?>" class="space-y-4">
    <?= Csrf::field() ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">

    <div>
      <label for="password" class="field-label">Yeni şifre</label>
      <input type="password" id="password" name="password" required autocomplete="new-password" autofocus
             minlength="<?= (int) Config::get('security.password_min', 10) ?>" class="field">
      <p class="field-hint">En az <?= (int) Config::get('security.password_min', 10) ?> karakter.</p>
    </div>

    <div>
      <label for="password_confirm" class="field-label">Yeni şifre (tekrar)</label>
      <input type="password" id="password_confirm" name="password_confirm" required
             autocomplete="new-password" class="field">
    </div>

    <button type="submit" class="btn btn-primary w-full justify-center">Şifreyi kaydet</button>
  </form>

<?php endif; ?>
