<?php
use Panel\Config;
use Panel\Csrf;
/** @var bool $schemaReady @var array $pending @var bool $sodiumOk */
$inputCls = 'w-full px-3 py-2.5 rounded-xl border border-warm-tertiary bg-warm focus:bg-white focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 text-sm';
?>

<h1 class="text-lg font-semibold text-ink mb-1">İlk kurulum</h1>
<p class="text-sm text-ink-light mb-6">Bu ekran yalnızca hiç kullanıcı yokken açılır; ilk hesap oluşunca kapanır.</p>

<?php if (!$sodiumOk): ?>
  <div class="mb-5 px-4 py-3 rounded-xl border border-amber-200 bg-amber-50 text-amber-900 text-sm">
    <strong>Uyarı:</strong> Seans notu şifreleme anahtarı (<code>security.note_key</code>) ayarlanmamış veya
    PHP <code>sodium</code> eklentisi yok. Panel çalışır, ancak şifreli seans notları özelliği kullanılamaz.
  </div>
<?php endif; ?>

<?php if (!$schemaReady || $pending !== []): ?>

  <div class="mb-5">
    <h2 class="text-sm font-medium text-ink mb-1">1. Veritabanı tabloları</h2>
    <p class="text-sm text-ink-muted mb-3">
      <?= $schemaReady
          ? 'Bekleyen güncelleme var: ' . e(implode(', ', $pending))
          : 'Tablolar henüz oluşturulmamış.' ?>
    </p>
    <form method="post" action="<?= e(url('/kurulum/tablolar')) ?>">
      <?= Csrf::field() ?>
      <button class="w-full bg-primary hover:bg-primary-hover text-white font-medium py-2.5 rounded-xl transition-colors text-sm">
        Tabloları oluştur
      </button>
    </form>
  </div>

  <p class="text-xs text-ink-light border-t border-warm-tertiary pt-4">
    Bu adımı SSH ile de yapabilirsiniz: <code>php panel/migrations/migrate.php</code>
  </p>

<?php else: ?>

  <h2 class="text-sm font-medium text-ink mb-3">2. Süper admin hesabı</h2>

  <form method="post" action="<?= e(url('/kurulum')) ?>" class="space-y-4">
    <?= Csrf::field() ?>

    <div>
      <label for="full_name" class="block text-sm font-medium text-ink mb-1.5">Ad Soyad</label>
      <input type="text" id="full_name" name="full_name" required autofocus class="<?= $inputCls ?>"
             value="<?= e(old('full_name')) ?>">
    </div>

    <div>
      <label for="email" class="block text-sm font-medium text-ink mb-1.5">E-posta</label>
      <input type="email" id="email" name="email" required autocomplete="username" class="<?= $inputCls ?>"
             value="<?= e(old('email')) ?>">
    </div>

    <div>
      <label for="password" class="block text-sm font-medium text-ink mb-1.5">Şifre</label>
      <input type="password" id="password" name="password" required autocomplete="new-password"
             minlength="<?= (int) Config::get('security.password_min', 10) ?>" class="<?= $inputCls ?>">
      <p class="text-xs text-ink-light mt-1.5">En az <?= (int) Config::get('security.password_min', 10) ?> karakter.</p>
    </div>

    <div>
      <label for="password_confirm" class="block text-sm font-medium text-ink mb-1.5">Şifre (tekrar)</label>
      <input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password" class="<?= $inputCls ?>">
    </div>

    <button type="submit" class="w-full bg-primary hover:bg-primary-hover text-white font-medium py-2.5 rounded-xl transition-colors text-sm">
      Hesabı oluştur
    </button>
  </form>

<?php endif; ?>
