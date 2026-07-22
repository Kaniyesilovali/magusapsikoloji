<?php
use Panel\Csrf;
/** @var array $pending @var array $applied @var array $checks @var array $actor */
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
