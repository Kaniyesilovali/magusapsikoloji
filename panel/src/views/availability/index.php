<?php
use Panel\Csrf;
use Panel\Rbac;
use Panel\Scheduling;
/** @var array|null $therapist @var array $therapists @var array $hours @var array $timeOff @var array $actor */

$isManager = Rbac::can($actor, 'availability.manage.all');

// Haftalık şablonu güne göre grupla — boş günler de satır olarak görünsün.
$byDay = array_fill_keys(array_keys(Scheduling::WEEKDAYS), []);
foreach ($hours as $row) {
    $byDay[(int) $row['weekday']][] = $row;
}
?>

<header class="mb-6">
  <p class="eyebrow">Merkez</p>
  <h1 class="page-title mt-2">Müsaitlik</h1>
  <p class="page-sub">
    Haftalık çalışma şablonu ve izinler. Randevu kaydını engellemez; dışına çıkıldığında uyarı verir.
  </p>
</header>

<?php if ($therapist === null): ?>
  <div class="sheet">
    <div class="sheet-body">
      <p class="text-sm text-ink">Kayıtlı terapist yok.</p>
      <?php if (Rbac::can($actor, 'user.create')): ?>
        <a href="<?= e(url('/kullanicilar/yeni')) ?>" class="btn btn-primary mt-4">Terapist hesabı oluştur</a>
      <?php endif; ?>
    </div>
  </div>
<?php else: ?>

<?php if ($isManager && $therapists !== []): ?>
  <form method="get" action="<?= e(url('/musaitlik')) ?>" class="mb-4 flex flex-wrap items-center gap-2">
    <label for="terapist" class="eyebrow">Terapist</label>
    <select id="terapist" name="terapist" class="field w-auto py-1.5 text-[0.8125rem]">
      <?php foreach ($therapists as $option): ?>
        <option value="<?= (int) $option['id'] ?>" <?= (int) $therapist['id'] === (int) $option['id'] ? 'selected' : '' ?>>
          <?= e($option['full_name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <button class="btn-text">Göster</button>
  </form>
<?php endif; ?>

<section class="sheet mb-6">
  <div class="sheet-head">
    <div>
      <h2 class="sheet-title">Haftalık çalışma saatleri</h2>
      <p class="text-xs text-ink-light mt-0.5">
        <span class="person"><?= e($therapist['full_name']) ?></span>
        <?php if ($hours === []): ?> — şablon girilmemiş; şu an her saat uygun sayılıyor.<?php endif; ?>
      </p>
    </div>
  </div>

  <ul>
    <?php // Gün anahtarları 0'dan başlamayabilir; ayırıcı için ayrı bir sayaç tutuluyor. ?>
    <?php $dayIndex = 0; ?>
    <?php foreach ($byDay as $weekday => $rows): ?>
      <li class="px-5 py-3 flex flex-wrap items-center gap-x-4 gap-y-2 <?= $dayIndex++ > 0 ? 'border-t border-warm-secondary' : '' ?>">
        <span class="text-sm text-ink w-28 shrink-0"><?= e(Scheduling::weekdayLabel($weekday)) ?></span>
        <?php if ($rows === []): ?>
          <span class="text-sm text-ink-light">—</span>
        <?php else: ?>
          <div class="flex flex-wrap gap-2">
            <?php foreach ($rows as $row): ?>
              <span class="chip chip-neutral gap-1.5 num">
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

  <form method="post" action="<?= e(url('/musaitlik/saat-ekle')) ?>"
        class="sheet-foot flex flex-wrap items-end gap-3">
    <?= Csrf::field() ?>
    <input type="hidden" name="therapist_id" value="<?= (int) $therapist['id'] ?>">
    <div>
      <label for="weekday" class="field-label">Gün</label>
      <select id="weekday" name="weekday" class="field w-auto py-1.5 text-[0.8125rem]">
        <?php foreach (Scheduling::WEEKDAYS as $value => $label): ?>
          <option value="<?= $value ?>"><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="start_time" class="field-label">Başlangıç</label>
      <input type="time" id="start_time" name="start_time" required value="09:00"
             class="field w-auto py-1.5 text-[0.8125rem] num">
    </div>
    <div>
      <label for="end_time" class="field-label">Bitiş</label>
      <input type="time" id="end_time" name="end_time" required value="18:00"
             class="field w-auto py-1.5 text-[0.8125rem] num">
    </div>
    <button class="btn btn-primary btn-sm">Ekle</button>
  </form>
</section>

<section class="sheet">
  <div class="sheet-head">
    <div>
      <h2 class="sheet-title">İzinler</h2>
      <p class="text-xs text-ink-light mt-0.5">Tatil, kongre, rapor… Geçmiş izinler listeden düşer.</p>
    </div>
  </div>

  <?php if ($timeOff === []): ?>
    <p class="sheet-empty">Yaklaşan izin kaydı yok.</p>
  <?php else: ?>
    <ul>
      <?php foreach ($timeOff as $index => $row): ?>
        <li class="px-5 py-3 flex flex-wrap items-center gap-x-4 gap-y-1 <?= $index > 0 ? 'border-t border-warm-secondary' : '' ?>">
          <span class="text-sm text-ink num">
            <?= e(dt($row['starts_at'])) ?> → <?= e(dt($row['ends_at'])) ?>
          </span>
          <span class="text-sm text-ink-muted flex-1 min-w-32"><?= e($row['reason'] ?? '—') ?></span>
          <form method="post" action="<?= e(url("/musaitlik/izin/{$row['id']}/sil")) ?>"
                data-confirm="İzin kaydı silinsin mi?">
            <?= Csrf::field() ?>
            <button class="btn-text btn-text-quiet hover:text-accent-dark">Sil</button>
          </form>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <?php // Tarih ile saat tek bir alan gibi okunsun diye yan yana; saat kutularının
        // etiketi görünmez ama ekran okuyucuya verilir. ?>
  <form method="post" action="<?= e(url('/musaitlik/izin-ekle')) ?>"
        class="sheet-foot flex flex-wrap items-end gap-3">
    <?= Csrf::field() ?>
    <input type="hidden" name="therapist_id" value="<?= (int) $therapist['id'] ?>">
    <div>
      <label for="start_date" class="field-label">Başlangıç</label>
      <div class="flex gap-2">
        <input type="date" id="start_date" name="start_date" required
               class="field w-auto py-1.5 text-[0.8125rem] num">
        <label for="leave_start_time" class="sr-only">Başlangıç saati</label>
        <input type="time" id="leave_start_time" name="start_time" required value="00:00"
               class="field w-auto py-1.5 text-[0.8125rem] num">
      </div>
    </div>
    <div>
      <label for="end_date" class="field-label">Bitiş</label>
      <div class="flex gap-2">
        <input type="date" id="end_date" name="end_date" required
               class="field w-auto py-1.5 text-[0.8125rem] num">
        <label for="leave_end_time" class="sr-only">Bitiş saati</label>
        <input type="time" id="leave_end_time" name="end_time" required value="23:59"
               class="field w-auto py-1.5 text-[0.8125rem] num">
      </div>
    </div>
    <div class="flex-1 min-w-48">
      <label for="reason" class="field-label">Gerekçe (isteğe bağlı)</label>
      <input type="text" id="reason" name="reason" maxlength="150" class="field py-1.5 text-[0.8125rem]">
    </div>
    <button class="btn btn-primary btn-sm">Ekle</button>
  </form>
</section>

<?php endif; ?>
