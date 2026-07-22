<?php
use Panel\Csrf;
/** @var array $pending @var array $applied @var array $checks @var array $reminder @var array $actor */
?>

<header class="mb-6">
  <h1 class="text-2xl font-semibold text-ink">Sistem</h1>
  <p class="text-sm text-ink-light mt-1">Sunucu durumu ve veritabanı güncellemeleri.</p>
</header>

<?php if ($pending !== []): ?>
  <section class="bg-white border border-amber-200 rounded-2xl p-6 mb-6">
    <h2 class="font-medium text-ink mb-1">Bekleyen veritabanı güncellemesi</h2>
    <p class="text-sm text-ink-muted mb-4">
      Panelin yeni sürümü, veritabanında henüz bulunmayan tablo veya alanlar kullanıyor.
      Uygulanmadan ilgili ekranlar hata verir.
    </p>

    <ul class="text-sm text-ink font-mono bg-warm rounded-xl px-4 py-3 mb-4 space-y-1">
      <?php foreach ($pending as $file): ?>
        <li><?= e($file) ?></li>
      <?php endforeach; ?>
    </ul>

    <form method="post" action="<?= e(url('/sistem/guncelle')) ?>"
          data-confirm="Veritabanı güncellemesi uygulanacak. Bu işlem geri alınamaz. Önce yedek aldığınızdan emin olun. Devam edilsin mi?">
      <?= Csrf::field() ?>
      <button class="bg-primary hover:bg-primary-hover text-white text-sm font-medium px-5 py-2.5 rounded-xl transition-colors">
        Bekleyen güncellemeleri uygula
      </button>
    </form>

    <p class="text-xs text-ink-light mt-3">
      Uygulamadan önce cPanel → yedekleme ekranından veritabanının yedeğini almanız önerilir.
    </p>
  </section>
<?php else: ?>
  <div class="mb-6 px-4 py-3 rounded-xl border border-warm-tertiary bg-white text-sm text-ink-muted">
    Veritabanı güncel — bekleyen güncelleme yok.
  </div>
<?php endif; ?>

<section class="bg-white border border-warm-tertiary rounded-2xl overflow-hidden mb-6">
  <div class="px-5 py-4 border-b border-warm-tertiary">
    <h2 class="font-medium text-ink">Sunucu durumu</h2>
  </div>
  <ul class="divide-y divide-warm-secondary">
    <?php foreach ($checks as $check): ?>
      <li class="px-5 py-3 flex flex-wrap items-center gap-x-4 gap-y-1">
        <span class="text-sm text-ink w-56 shrink-0"><?= e($check['label']) ?></span>
        <span class="text-xs px-2 py-0.5 rounded-full <?= $check['ok'] ? 'bg-primary/10 text-primary-dark' : 'bg-amber-100 text-amber-900' ?>">
          <?= $check['ok'] ? 'tamam' : 'dikkat' ?>
        </span>
        <span class="text-xs text-ink-light flex-1 min-w-48"><?= e($check['detail']) ?></span>
      </li>
    <?php endforeach; ?>
  </ul>
</section>

<section class="bg-white border border-warm-tertiary rounded-2xl overflow-hidden mb-6">
  <div class="px-5 py-4 border-b border-warm-tertiary">
    <h2 class="font-medium text-ink">Randevu hatırlatmaları</h2>
    <p class="text-xs text-ink-light mt-1">
      Danışanlara, randevudan <?= (int) $reminder['hours'] ?> saat önce e-posta gider.
      Gönderimi cPanel'deki cron çalıştırır; panel kendi kendine tetiklemez.
    </p>
  </div>

  <?php if (!$reminder['ready']): ?>
    <p class="px-5 py-6 text-sm text-ink-muted">
      Veritabanı güncellemesi bekleniyor — yukarıdaki düğmeyle uygulayın.
    </p>
  <?php else: ?>
    <ul class="divide-y divide-warm-secondary">
      <li class="px-5 py-3 flex flex-wrap items-center gap-x-4 gap-y-1">
        <span class="text-sm text-ink w-56 shrink-0">Durum</span>
        <span class="text-xs px-2 py-0.5 rounded-full <?= $reminder['enabled'] ? 'bg-primary/10 text-primary-dark' : 'bg-warm-secondary text-ink-muted' ?>">
          <?= $reminder['enabled'] ? 'açık' : 'kapalı' ?>
        </span>
        <span class="text-xs text-ink-light flex-1 min-w-48">
          <code>settings.reminders_enabled</code> değeri ile kapatılabilir.
        </span>
      </li>
      <li class="px-5 py-3 flex flex-wrap items-center gap-x-4 gap-y-1">
        <span class="text-sm text-ink w-56 shrink-0">Son çalışma</span>
        <span class="text-xs px-2 py-0.5 rounded-full <?= $reminder['lastRun'] === null ? 'bg-amber-100 text-amber-900' : 'bg-primary/10 text-primary-dark' ?>">
          <?= $reminder['lastRun'] === null ? 'hiç çalışmadı' : 'çalıştı' ?>
        </span>
        <span class="text-xs text-ink-light flex-1 min-w-48 tabular-nums">
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
          <?= (int) $reminder['queued'] ?> randevu bekliyor
          (önümüzdeki <?= (int) $reminder['hours'] ?> saat, e-posta adresi kayıtlı olanlar)
        </span>
      </li>
    </ul>

    <div class="px-5 py-4 border-t border-warm-tertiary bg-warm/50">
      <p class="text-xs text-ink-muted mb-2">
        cPanel → <strong>Cron Jobs</strong> → “Saatte bir” (<code>0 * * * *</code>) seçip komutu yapıştırın:
      </p>
      <pre class="text-xs bg-white border border-warm-tertiary rounded-xl p-3 overflow-x-auto"><code>/usr/local/bin/php <?= e(dirname(PANEL_ROOT)) ?>/panel/cron/reminders.php</code></pre>
    </div>
  <?php endif; ?>
</section>

<section class="bg-white border border-warm-tertiary rounded-2xl overflow-hidden">
  <div class="px-5 py-4 border-b border-warm-tertiary">
    <h2 class="font-medium text-ink">Uygulanmış güncellemeler</h2>
  </div>
  <?php if ($applied === []): ?>
    <p class="px-5 py-6 text-sm text-ink-light">Kayıt yok.</p>
  <?php else: ?>
    <ul class="divide-y divide-warm-secondary">
      <?php foreach ($applied as $row): ?>
        <li class="px-5 py-3 flex flex-wrap items-center gap-x-4 gap-y-1">
          <span class="text-sm text-ink font-mono flex-1 min-w-48"><?= e($row['filename']) ?></span>
          <span class="text-xs text-ink-light tabular-nums"><?= e(dt($row['applied_at'])) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>
