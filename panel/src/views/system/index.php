<?php
use Panel\Csrf;
/** @var array $pending @var array $applied @var array $checks @var array $reminder @var array $mail @var array $actor */
?>

<header class="mb-6">
  <p class="eyebrow">Yönetim</p>
  <h1 class="page-title mt-2">Sistem</h1>
  <p class="page-sub">Sunucu durumu ve veritabanı güncellemeleri.</p>
</header>

<?php if ($pending !== []): ?>
  <?php // Bekleyen göç bir uyarı değil, bir karar: rozet clay, yaprağın kendisi sakin. ?>
  <section class="sheet mb-6">
    <div class="sheet-head">
      <h2 class="sheet-title">Bekleyen veritabanı güncellemesi</h2>
      <span class="chip chip-stop">uygulanmadı</span>
    </div>
    <div class="sheet-body">
      <p class="text-sm text-ink-muted mb-4">
        Panelin yeni sürümü, veritabanında henüz bulunmayan tablo veya alanlar kullanıyor.
        Uygulanmadan ilgili ekranlar hata verir.
      </p>

      <ul class="text-sm text-ink font-mono bg-warm rounded-md px-4 py-3 mb-4 space-y-1">
        <?php foreach ($pending as $file): ?>
          <li><?= e($file) ?></li>
        <?php endforeach; ?>
      </ul>

      <form method="post" action="<?= e(url('/sistem/guncelle')) ?>"
            data-confirm="Veritabanı güncellemesi uygulanacak. Bu işlem geri alınamaz. Önce yedek aldığınızdan emin olun. Devam edilsin mi?">
        <?= Csrf::field() ?>
        <button class="btn btn-primary">Bekleyen güncellemeleri uygula</button>
      </form>

      <p class="field-hint">
        Uygulamadan önce cPanel → yedekleme ekranından veritabanının yedeğini almanız önerilir.
      </p>
    </div>
  </section>
<?php else: ?>
  <div class="note note-info mb-6">Veritabanı güncel — bekleyen güncelleme yok.</div>
<?php endif; ?>

<section class="sheet mb-6">
  <div class="sheet-head">
    <h2 class="sheet-title">Sunucu durumu</h2>
  </div>
  <ul class="divide-y divide-warm-secondary">
    <?php foreach ($checks as $check): ?>
      <li class="px-5 py-3 flex flex-wrap items-center gap-x-4 gap-y-1">
        <span class="text-sm text-ink w-56 shrink-0"><?= e($check['label']) ?></span>
        <span class="chip <?= $check['ok'] ? 'chip-go' : 'chip-stop' ?>">
          <?= $check['ok'] ? 'tamam' : 'dikkat' ?>
        </span>
        <span class="text-xs text-ink-light flex-1 min-w-48"><?= e($check['detail']) ?></span>
      </li>
    <?php endforeach; ?>
  </ul>
</section>

<?php // Davet e-postası ulaşmadığında bakılacak ilk yer burası: hangi sürücü,
      // hangi adresten, ve tek tıkla gerçek bir gönderim denemesi. ?>
<section class="sheet mb-6">
  <div class="sheet-head">
    <div>
      <h2 class="sheet-title">E-posta gönderimi</h2>
      <p class="text-xs text-ink-light mt-1">
        Davet, şifre sıfırlama ve randevu hatırlatma e-postalarının tümü bu ayarlardan çıkar.
      </p>
    </div>
    <span class="chip <?= $mail['live'] ? 'chip-go' : 'chip-stop' ?>">
      <?= $mail['live'] ? 'açık' : 'kapalı' ?>
    </span>
  </div>

  <ul class="divide-y divide-warm-secondary">
    <li class="px-5 py-3 flex flex-wrap items-center gap-x-4 gap-y-1">
      <span class="text-sm text-ink w-56 shrink-0">Sürücü</span>
      <span class="text-xs text-ink-light flex-1 min-w-48">
        <code><?= e($mail['driver']) ?></code> — <?= e($mail['detail']) ?>
      </span>
    </li>
    <li class="px-5 py-3 flex flex-wrap items-center gap-x-4 gap-y-1">
      <span class="text-sm text-ink w-56 shrink-0">Gönderen adres</span>
      <span class="text-xs text-ink-light flex-1 min-w-48">
        <?= $mail['from'] !== '' ? e($mail['from']) : 'tanımlı değil — gönderim büyük olasılıkla reddedilir' ?>
      </span>
    </li>
  </ul>

  <div class="sheet-foot">
    <?php if (!$mail['live']): ?>
      <p class="note note-stop mb-3">
        Sürücü <code>log</code> — e-postalar kimseye gitmiyor, yalnız dosyaya yazılıyor.
        Davet bağlantılarını elle iletmeniz gerekir. Yapılandırma dosyasındaki
        <code>mail.driver</code> değerini <code>mail</code> ya da <code>smtp</code> yapın.
      </p>
    <?php endif; ?>

    <form method="post" action="<?= e(url('/sistem/test-eposta')) ?>">
      <?= Csrf::field() ?>
      <button class="btn btn-quiet">Test e-postası gönder</button>
    </form>
    <p class="field-hint">
      İleti kendi adresinize (<?= e((string) $actor['email']) ?>) gider; sonuç ve hata varsa sebebi burada yazar.
    </p>
  </div>
</section>

<section class="sheet mb-6">
  <div class="sheet-head">
    <div>
      <h2 class="sheet-title">Randevu hatırlatmaları</h2>
      <p class="text-xs text-ink-light mt-1">
        Görüşmecilere, randevudan <span class="num"><?= (int) $reminder['hours'] ?></span> saat önce e-posta gider.
        Gönderimi cPanel'deki cron çalıştırır; panel kendi kendine tetiklemez.
      </p>
    </div>
  </div>

  <?php if (!$reminder['ready']): ?>
    <p class="px-5 py-6 text-sm text-ink-muted">
      Veritabanı güncellemesi bekleniyor — yukarıdaki düğmeyle uygulayın.
    </p>
  <?php else: ?>
    <ul class="divide-y divide-warm-secondary">
      <li class="px-5 py-3 flex flex-wrap items-center gap-x-4 gap-y-1">
        <span class="text-sm text-ink w-56 shrink-0">Durum</span>
        <span class="chip <?= $reminder['enabled'] ? 'chip-go' : 'chip-neutral' ?>">
          <?= $reminder['enabled'] ? 'açık' : 'kapalı' ?>
        </span>
        <span class="text-xs text-ink-light flex-1 min-w-48">
          <code>settings.reminders_enabled</code> değeri ile kapatılabilir.
        </span>
      </li>
      <li class="px-5 py-3 flex flex-wrap items-center gap-x-4 gap-y-1">
        <span class="text-sm text-ink w-56 shrink-0">Son çalışma</span>
        <span class="chip <?= $reminder['lastRun'] === null ? 'chip-stop' : 'chip-go' ?>">
          <?= $reminder['lastRun'] === null ? 'hiç çalışmadı' : 'çalıştı' ?>
        </span>
        <span class="text-xs text-ink-light flex-1 min-w-48 num">
          <?php if ($reminder['lastRun'] === null): ?>
            Cron kurulmamış olabilir — aşağıdaki komutu cPanel → Cron Jobs ekranına ekleyin.
          <?php else: ?>
            <?= e(dt($reminder['lastRun'])) ?> — <?= e((string) $reminder['lastResult']) ?>
          <?php endif; ?>
        </span>
      </li>
      <li class="px-5 py-3 flex flex-wrap items-center gap-x-4 gap-y-1">
        <span class="text-sm text-ink w-56 shrink-0">Sıradaki gönderim</span>
        <span class="text-xs text-ink-light flex-1 min-w-48">
          <span class="num"><?= (int) $reminder['queued'] ?></span> randevu bekliyor
          (önümüzdeki <span class="num"><?= (int) $reminder['hours'] ?></span> saat, e-posta adresi kayıtlı olanlar)
        </span>
      </li>
    </ul>

    <div class="sheet-foot">
      <p class="text-xs text-ink-muted mb-2">
        cPanel → <strong>Cron Jobs</strong> → “Saatte bir” (<code>0 * * * *</code>) seçip komutu yapıştırın:
      </p>
      <pre class="text-xs bg-white border border-warm-tertiary rounded-md p-3 overflow-x-auto"><code>/usr/local/bin/php <?= e(dirname(PANEL_ROOT)) ?>/panel/cron/reminders.php</code></pre>
    </div>
  <?php endif; ?>
</section>

<section class="sheet">
  <div class="sheet-head">
    <h2 class="sheet-title">Uygulanmış güncellemeler</h2>
  </div>
  <?php if ($applied === []): ?>
    <p class="sheet-empty">Kayıt yok.</p>
  <?php else: ?>
    <ul class="divide-y divide-warm-secondary">
      <?php foreach ($applied as $row): ?>
        <li class="px-5 py-3 flex flex-wrap items-center gap-x-4 gap-y-1">
          <span class="text-sm text-ink font-mono flex-1 min-w-48"><?= e($row['filename']) ?></span>
          <span class="text-xs text-ink-light num"><?= e(dt($row['applied_at'])) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>
