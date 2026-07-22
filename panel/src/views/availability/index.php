<?php
use Panel\Csrf;
use Panel\Rbac;
use Panel\Scheduling;
/** @var array|null $therapist @var array $therapists @var array $hours @var array $timeOff @var array $actor */

$isManager = Rbac::can($actor, 'availability.manage.all');
$inputCls  = 'px-3 py-2 rounded-xl border border-warm-tertiary bg-warm focus:bg-white focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 text-sm';

// Haftalık şablonu güne göre grupla — boş günler de satır olarak görünsün.
$byDay = array_fill_keys(array_keys(Scheduling::WEEKDAYS), []);
foreach ($hours as $row) {
    $byDay[(int) $row['weekday']][] = $row;
}
?>

<header class="mb-6">
  <h1 class="text-2xl font-semibold text-ink">Müsaitlik</h1>
  <p class="text-sm text-ink-light mt-1">
    Haftalık çalışma şablonu ve izinler. Randevu kaydını engellemez; dışına çıkıldığında uyarı verir.
  </p>
</header>

<?php if ($therapist === null): ?>
  <div class="bg-white border border-warm-tertiary rounded-2xl p-6">
    <p class="text-sm text-ink">Kayıtlı terapist yok.</p>
    <?php if (Rbac::can($actor, 'user.create')): ?>
      <a href="<?= e(url('/kullanicilar/yeni')) ?>" class="inline-block mt-4 bg-primary hover:bg-primary-hover text-white text-sm font-medium px-4 py-2.5 rounded-xl">
        Terapist hesabı oluştur
      </a>
    <?php endif; ?>
  </div>
<?php else: ?>

<?php if ($isManager && $therapists !== []): ?>
  <form method="get" action="<?= e(url('/musaitlik')) ?>" class="mb-4 flex flex-wrap items-center gap-2">
    <label for="terapist" class="text-sm text-ink-muted">Terapist</label>
    <select id="terapist" name="terapist" class="<?= $inputCls ?> bg-white">
      <?php foreach ($therapists as $option): ?>
        <option value="<?= (int) $option['id'] ?>" <?= (int) $therapist['id'] === (int) $option['id'] ? 'selected' : '' ?>>
          <?= e($option['full_name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <button class="text-sm text-primary hover:text-primary-dark font-medium">Göster</button>
  </form>
<?php endif; ?>

<section class="bg-white border border-warm-tertiary rounded-2xl overflow-hidden mb-6">
  <div class="px-5 py-4 border-b border-warm-tertiary">
    <h2 class="font-medium text-ink">Haftalık çalışma saatleri</h2>
    <p class="text-xs text-ink-light mt-1">
      <?= e($therapist['full_name']) ?>
      <?php if ($hours === []): ?> — şablon girilmemiş; şu an her saat uygun sayılıyor.<?php endif; ?>
    </p>
  </div>

  <ul class="divide-y divide-warm-secondary">
    <?php foreach ($byDay as $weekday => $rows): ?>
      <li class="px-5 py-3 flex flex-wrap items-center gap-x-4 gap-y-2">
        <span class="text-sm text-ink w-28 shrink-0"><?= e(Scheduling::weekdayLabel($weekday)) ?></span>
        <?php if ($rows === []): ?>
          <span class="text-sm text-ink-light">—</span>
        <?php else: ?>
          <div class="flex flex-wrap gap-2">
            <?php foreach ($rows as $row): ?>
              <span class="inline-flex items-center gap-2 text-xs bg-warm rounded-lg px-2.5 py-1.5 tabular-nums">
                <?= e(substr((string) $row['start_time'], 0, 5)) ?>–<?= e(substr((string) $row['end_time'], 0, 5)) ?>
                <form method="post" action="<?= e(url("/musaitlik/saat/{$row['id']}/sil")) ?>" class="inline">
                  <?= Csrf::field() ?>
                  <button class="text-ink-light hover:text-accent-dark" aria-label="Sil">×</button>
                </form>
              </span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>

  <form method="post" action="<?= e(url('/musaitlik/saat-ekle')) ?>" class="px-5 py-4 border-t border-warm-tertiary bg-warm/50 flex flex-wrap items-end gap-3">
    <?= Csrf::field() ?>
    <input type="hidden" name="therapist_id" value="<?= (int) $therapist['id'] ?>">
    <div>
      <label for="weekday" class="block text-xs text-ink-muted mb-1">Gün</label>
      <select id="weekday" name="weekday" class="<?= $inputCls ?> bg-white">
        <?php foreach (Scheduling::WEEKDAYS as $value => $label): ?>
          <option value="<?= $value ?>"><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="start_time" class="block text-xs text-ink-muted mb-1">Başlangıç</label>
      <input type="time" id="start_time" name="start_time" required value="09:00" class="<?= $inputCls ?> bg-white">
    </div>
    <div>
      <label for="end_time" class="block text-xs text-ink-muted mb-1">Bitiş</label>
      <input type="time" id="end_time" name="end_time" required value="18:00" class="<?= $inputCls ?> bg-white">
    </div>
    <button class="bg-primary hover:bg-primary-hover text-white text-sm font-medium px-4 py-2 rounded-xl transition-colors">Ekle</button>
  </form>
</section>

<section class="bg-white border border-warm-tertiary rounded-2xl overflow-hidden">
  <div class="px-5 py-4 border-b border-warm-tertiary">
    <h2 class="font-medium text-ink">İzinler</h2>
    <p class="text-xs text-ink-light mt-1">Tatil, kongre, rapor… Geçmiş izinler listeden düşer.</p>
  </div>

  <?php if ($timeOff === []): ?>
    <p class="px-5 py-6 text-sm text-ink-light">Yaklaşan izin kaydı yok.</p>
  <?php else: ?>
    <ul class="divide-y divide-warm-secondary">
      <?php foreach ($timeOff as $row): ?>
        <li class="px-5 py-3 flex flex-wrap items-center gap-x-4 gap-y-1">
          <span class="text-sm text-ink tabular-nums"><?= e(dt($row['starts_at'])) ?> → <?= e(dt($row['ends_at'])) ?></span>
          <span class="text-sm text-ink-muted flex-1 min-w-32"><?= e($row['reason'] ?? '—') ?></span>
          <form method="post" action="<?= e(url("/musaitlik/izin/{$row['id']}/sil")) ?>"
                data-confirm="İzin kaydı silinsin mi?">
            <?= Csrf::field() ?>
            <button class="text-xs text-accent-dark hover:text-accent">Sil</button>
          </form>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <form method="post" action="<?= e(url('/musaitlik/izin-ekle')) ?>" class="px-5 py-4 border-t border-warm-tertiary bg-warm/50 flex flex-wrap items-end gap-3">
    <?= Csrf::field() ?>
    <input type="hidden" name="therapist_id" value="<?= (int) $therapist['id'] ?>">
    <div>
      <label for="start_date" class="block text-xs text-ink-muted mb-1">Başlangıç</label>
      <div class="flex gap-2">
        <input type="date" id="start_date" name="start_date" required class="<?= $inputCls ?> bg-white">
        <input type="time" name="start_time" required value="00:00" class="<?= $inputCls ?> bg-white">
      </div>
    </div>
    <div>
      <label for="end_date" class="block text-xs text-ink-muted mb-1">Bitiş</label>
      <div class="flex gap-2">
        <input type="date" id="end_date" name="end_date" required class="<?= $inputCls ?> bg-white">
        <input type="time" name="end_time" required value="23:59" class="<?= $inputCls ?> bg-white">
      </div>
    </div>
    <div class="flex-1 min-w-48">
      <label for="reason" class="block text-xs text-ink-muted mb-1">Gerekçe (isteğe bağlı)</label>
      <input type="text" id="reason" name="reason" maxlength="150" class="<?= $inputCls ?> bg-white w-full">
    </div>
    <button class="bg-primary hover:bg-primary-hover text-white text-sm font-medium px-4 py-2 rounded-xl transition-colors">Ekle</button>
  </form>
</section>

<?php endif; ?>
