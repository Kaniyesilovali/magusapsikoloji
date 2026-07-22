<?php
use Panel\Config;
use Panel\Csrf;
/** @var bool $schemaReady @var array $pending @var bool $sodiumOk */
?>

<h1 class="page-title text-xl mb-5">İlk kurulum</h1>
<p class="text-sm text-ink-muted -mt-3 mb-5">
  Bu ekran yalnızca hiç kullanıcı yokken açılır; ilk hesap oluşunca kapanır.
</p>

<?php if (!$sodiumOk): ?>
  <div class="note note-stop mb-5">
    <strong>Uyarı:</strong> Seans notu şifreleme anahtarı (<code>security.note_key</code>) ayarlanmamış veya
    PHP <code>sodium</code> eklentisi yok. Panel çalışır, ancak şifreli seans notları özelliği kullanılamaz.
  </div>
<?php endif; ?>

<?php if (!$schemaReady || $pending !== []): ?>

  <div class="mb-5">
    <h2 class="sheet-title mb-1">1. Veritabanı tabloları</h2>
    <p class="text-sm text-ink-muted mb-3">
      <?= $schemaReady
          ? 'Bekleyen güncelleme var: ' . e(implode(', ', $pending))
          : 'Tablolar henüz oluşturulmamış.' ?>
    </p>
    <form method="post" action="<?= e(url('/kurulum/tablolar')) ?>">
      <?= Csrf::field() ?>
      <button class="btn btn-primary w-full justify-center">Tabloları oluştur</button>
    </form>
  </div>

  <p class="text-xs text-ink-light border-t border-warm-tertiary pt-4">
    Bu adımı SSH ile de yapabilirsiniz: <code>php panel/migrations/migrate.php</code>
  </p>

<?php else: ?>

  <h2 class="sheet-title mb-3">2. Süper admin hesabı</h2>

  <form method="post" action="<?= e(url('/kurulum')) ?>" class="space-y-4">
    <?= Csrf::field() ?>

    <div>
      <label for="full_name" class="field-label">Ad Soyad</label>
      <input type="text" id="full_name" name="full_name" required autofocus class="field"
             value="<?= e(old('full_name')) ?>">
    </div>

    <div>
      <label for="email" class="field-label">E-posta</label>
      <input type="email" id="email" name="email" required autocomplete="username" class="field"
             value="<?= e(old('email')) ?>">
    </div>

    <div>
      <label for="password" class="field-label">Şifre</label>
      <input type="password" id="password" name="password" required autocomplete="new-password"
             minlength="<?= (int) Config::get('security.password_min', 10) ?>" class="field">
      <p class="field-hint">En az <?= (int) Config::get('security.password_min', 10) ?> karakter.</p>
    </div>

    <div>
      <label for="password_confirm" class="field-label">Şifre (tekrar)</label>
      <input type="password" id="password_confirm" name="password_confirm" required
             autocomplete="new-password" class="field">
    </div>

    <button type="submit" class="btn btn-primary w-full justify-center">Hesabı oluştur</button>
  </form>

<?php endif; ?>
