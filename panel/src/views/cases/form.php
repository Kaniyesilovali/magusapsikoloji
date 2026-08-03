<?php
use Panel\Csrf;
/** @var array $client @var array|null $file @var string $body @var bool $available @var array $actor */
?>

<header class="mb-6">
  <a href="<?= e(url("/danisanlar/{$client['id']}")) ?>" class="btn-text btn-text-quiet">← <?= e($client['full_name']) ?></a>
  <p class="eyebrow mt-3">Birey dosyası</p>
  <h1 class="page-title mt-2"><?= e($client['full_name']) ?></h1>
  <p class="page-sub">
    Başvuru nedeni, öykü, formülasyon ve plan. Seans notlarından ayrıdır —
    burası sürecin tamamını taşır.
  </p>
</header>

<?php if (!$available): ?>
  <div class="note note-stop">
    <strong>Şifreleme kullanılamıyor.</strong>
    Birey dosyası yalnız şifreli saklanabilir. Sunucuda <code>sodium</code> eklentisi kapalı
    ya da <code>note_key</code> geçersiz olduğu için dosya yazılamıyor.
    cPanel → <strong>Select PHP Version</strong> ekranından <code>sodium</code> kutusunu işaretleyin.
  </div>

<?php else: ?>
  <form method="post" action="<?= e(url("/danisanlar/{$client['id']}/dosya")) ?>" class="sheet">
    <div class="sheet-body">
      <?= Csrf::field() ?>

      <label for="body" class="field-label">Dosya</label>
      <textarea id="body" name="body" rows="20" required class="field leading-relaxed"
                placeholder="Başvuru nedeni&#10;&#10;Öykü&#10;&#10;Formülasyon&#10;&#10;Plan ve hedefler"><?= e($body) ?></textarea>

      <p class="field-hint mt-3">
        Bu dosya veritabanına <strong>şifreli</strong> yazılır ve yalnız siz okuyabilirsiniz —
        yöneticiler ve süper admin dahil kimse göremez. Sistem kayıtlarına yalnız
        "dosya yazıldı" bilgisi düşer, içerik düşmez.
      </p>
    </div>

    <div class="sheet-foot flex flex-wrap items-center gap-3">
      <button type="submit" class="btn btn-primary"><?= $file === null ? 'Dosyayı oluştur' : 'Dosyayı kaydet' ?></button>
      <a href="<?= e(url("/danisanlar/{$client['id']}")) ?>" class="btn-text btn-text-quiet">Vazgeç</a>
      <?php if ($file !== null): ?>
        <span class="text-xs text-ink-light ml-auto">
          Son güncelleme: <span class="num"><?= e(dt($file['updated_at'] ?? $file['created_at'])) ?></span>
        </span>
      <?php endif; ?>
    </div>
  </form>
<?php endif; ?>
