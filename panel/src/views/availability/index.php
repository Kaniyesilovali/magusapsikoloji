<?php
use Panel\Csrf;
use Panel\Rbac;
use Panel\Scheduling;
/** @var array|null $therapist @var array $therapists @var array $hours @var array $timeOff */
/** @var array|null $calendar @var string $mode @var DateTimeImmutable $anchor */
/** @var DateTimeImmutable $monthStart @var DateTimeImmutable $rangeStart @var array $actor */

$isManager = Rbac::can($actor, 'availability.manage.all');
$isMonth   = $mode === 'ay';
$today     = date('Y-m-d');

// Haftalık şablonu güne göre grupla — boş günler de satır olarak görünsün.
$byDay = array_fill_keys(array_keys(Scheduling::WEEKDAYS), []);
foreach ($hours as $row) {
    $byDay[(int) $row['weekday']][] = $row;
}

/** Görünüm, çapa ve seçili terapist bağlantılar arasında taşınır. */
$link = static function (array $over = []) use ($mode, $anchor, $therapist, $isManager): string {
    $params = ['gorunum' => $mode, 'tarih' => $anchor->format('Y-m-d')];
    if ($isManager && $therapist !== null) {
        $params['terapist'] = (int) $therapist['id'];
    }
    return url('/musaitlik?' . http_build_query(array_merge($params, $over)));
};

$prev = $isMonth ? $monthStart->modify('-1 month') : $rangeStart->modify('-7 days');
$next = $isMonth ? $monthStart->modify('+1 month') : $rangeStart->modify('+7 days');
?>

<?php // Başlık üstü satırı kimin takvimine bakıldığını söylüyor: sayfanın
      // tamamı (şablon, izinler, takvim) tek bir terapiste ait ve yönetici
      // ekranı başkasının seçili hâliyle bırakıp geri dönebiliyor. ?>
<?php // Tur adımları 20'den başlar; 10–19 kenar çubuğunun (bkz. views/layout.php). ?>
<header class="mb-6"
        data-tour="20" data-tour-title="Bir kural, bir engel değil"
        data-tour-text="Sayfanın tamamı tek bir terapiste ait; üstteki satır kimin takvimine baktığınızı söyler. Buradaki saatler randevu kaydını engellemez — dışına çıkıldığında yalnız uyarı verir. Takvim şablonu ve izinleri gerçek tarihlerin üzerinde bir arada gösterir.">
  <p class="eyebrow"><?= e($therapist === null ? 'Terapist yok' : (string) $therapist['full_name']) ?></p>
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
  <form method="get" action="<?= e(url('/musaitlik')) ?>" data-autosubmit
        class="ctl mb-4">
    <input type="hidden" name="gorunum" value="<?= e($mode) ?>">
    <input type="hidden" name="tarih" value="<?= e($anchor->format('Y-m-d')) ?>">
    <label for="terapist" class="eyebrow">Terapist</label>
    <select id="terapist" name="terapist" class="field w-auto py-1.5 text-[0.8125rem]">
      <?php foreach ($therapists as $option): ?>
        <option value="<?= (int) $option['id'] ?>" <?= (int) $therapist['id'] === (int) $option['id'] ? 'selected' : '' ?>>
          <?= e($option['full_name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <button class="btn-text" data-no-js>Göster</button>
  </form>
<?php endif; ?>

<?php // ── Takvim ────────────────────────────────────────────────────────────
      // Şablon haftalık bir kuraldır, izin ise belirli günlere yazılır; ikisi
      // ancak gerçek tarihlerin üzerinde bir arada okunur. Düzenleme aşağıda:
      // haftalık bir kuralı tek bir günün kutusundan silmek yanıltıcı olurdu. ?>
<section class="mb-8">
  <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <h2 class="page-title text-[1.125rem]">
      <?php if ($isMonth): ?>
        <?= e(tr_month_name((int) $monthStart->format('n')) . ' ' . $monthStart->format('Y')) ?>
      <?php else: ?>
        <?= e(tr_range_label($rangeStart->format('Y-m-d'), $rangeStart->modify('+6 days')->format('Y-m-d'))) ?>
      <?php endif; ?>
    </h2>

    <div class="ctl">
      <nav class="flex items-center gap-1.5" aria-label="<?= $isMonth ? 'Ay' : 'Hafta' ?>">
        <a href="<?= e($link(['tarih' => $prev->format('Y-m-d')])) ?>" class="btn btn-quiet btn-sm">←&nbsp;Önceki</a>
        <a href="<?= e($link(['tarih' => $today])) ?>" class="btn btn-quiet btn-sm"><?= $isMonth ? 'Bu ay' : 'Bu hafta' ?></a>
        <a href="<?= e($link(['tarih' => $next->format('Y-m-d')])) ?>" class="btn btn-quiet btn-sm">Sonraki&nbsp;→</a>
      </nav>
      <nav class="seg" aria-label="Görünüm">
        <?php foreach (['hafta' => 'Hafta', 'ay' => 'Ay'] as $value => $label): ?>
          <a href="<?= e($link(['gorunum' => $value])) ?>"
             <?= $mode === $value ? 'aria-current="true"' : '' ?>><?= e($label) ?></a>
        <?php endforeach; ?>
      </nav>

      <?php // Terapist seçici sayfanın tamamına ait (şablon ve izin listeleri de
            // ona bağlı), bu yüzden yukarıda ayrı duruyor; ay yalnız takvimi
            // ilgilendirdiği için burada. ?>
      <form method="get" action="<?= e(url('/musaitlik')) ?>" data-autosubmit
            class="flex flex-wrap items-center gap-2">
        <input type="hidden" name="gorunum" value="<?= e($mode) ?>">
        <?php if ($isManager): ?>
          <input type="hidden" name="terapist" value="<?= (int) $therapist['id'] ?>">
        <?php endif; ?>
        <?php // Görünüm düğmelerinden biri de "Ay"; ikinci bir "Ay" etiketi yanı
              // başında dururken hangisinin ne yaptığı okunmuyordu. ?>
        <select id="tarih" name="tarih" aria-label="Ay seç" class="field w-auto py-1.5 text-[0.8125rem]">
          <?php foreach (tr_month_options($anchor) as $value => $label): ?>
            <option value="<?= e($value) ?>" <?= $value === $anchor->format('Y-m-d') ? 'selected' : '' ?>>
              <?= e($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <button class="btn-text" data-no-js>Göster</button>
      </form>
    </div>
  </div>

  <?php if ($isMonth): ?>
    <?php $monthKey = $monthStart->format('Y-m'); ?>
    <div class="cal-scroll">
      <div class="sheet cal-min">
        <div class="cal-month-head">
          <?php foreach (Scheduling::WEEKDAYS as $name): ?>
            <span><?= e($name) ?></span>
          <?php endforeach; ?>
        </div>
        <div class="cal-month">
          <?php foreach ($calendar['days'] as $date => $day): ?>
            <div class="cal-cell <?= substr($date, 0, 7) !== $monthKey ? 'is-out' : '' ?> <?= $date === $today ? 'is-today' : '' ?>">
              <span class="cal-daynum num"><?= e((new DateTimeImmutable($date))->format('j')) ?></span>

              <?php foreach ($day['work'] as $band): ?>
                <span class="cal-item is-work num"><?= e($band['start']) ?>–<?= e($band['end']) ?></span>
              <?php endforeach; ?>
              <?php if ($day['work'] === [] && $day['off'] === []): ?>
                <span class="cal-item-none">—</span>
              <?php endif; ?>

              <?php foreach ($day['off'] as $band): ?>
                <span class="cal-item is-off-day" title="<?= e($band['reason'] ?? 'İzin') ?>">
                  <span class="cal-item-name">İzin<?= $band['reason'] !== null ? ' · ' . e($band['reason']) : '' ?></span>
                </span>
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php else: ?>
    <?php $short = [1 => 'Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz']; ?>
    <div class="cal-scroll">
      <div class="cal-min">
        <div class="cal-head">
          <?php foreach (array_keys($calendar['days']) as $date): ?>
            <?php $day = new DateTimeImmutable($date); ?>
            <div class="cal-head-day <?= $date === $today ? 'is-today' : '' ?>">
              <span>
                <?= e($short[(int) $day->format('N')]) ?>
                <span class="num ml-0.5"><?= e($day->format('j')) ?></span>
              </span>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="rail rail-<?= (int) $calendar['hours'] ?>h">
          <?php for ($h = 0; $h <= $calendar['hours']; $h++): ?>
            <div class="rail-hour at-<?= $h * 12 ?>">
              <span><?= sprintf('%02d:00', $calendar['startHour'] + $h) ?></span>
            </div>
          <?php endfor; ?>

          <?php if ($hours === []): ?>
            <p class="rail-empty">Şablon girilmemiş; şu an her saat uygun sayılıyor.</p>
          <?php endif; ?>

          <div class="cal-track">
            <?php foreach ($calendar['days'] as $date => $day): ?>
              <div class="cal-col <?= $date === $today ? 'is-today' : '' ?>">
                <?php foreach ($day['work'] as $band): ?>
                  <?php if ($band['at'] === null) { continue; } ?>
                  <div class="rail-band at-<?= (int) $band['at'] ?> dur-<?= (int) $band['dur'] ?>">
                    <span class="num"><?= e($band['start']) ?>–<?= e($band['end']) ?></span>
                  </div>
                <?php endforeach; ?>

                <?php // İzin şablonun üstüne biner: o gün çalışma saati yazılı olsa
                      // bile geçerli olan izindir. ?>
                <?php foreach ($day['off'] as $band): ?>
                  <?php if ($band['at'] === null) { continue; } ?>
                  <div class="rail-off at-<?= (int) $band['at'] ?> dur-<?= (int) $band['dur'] ?>"
                       title="<?= e($band['reason'] ?? 'İzin') ?>">
                    <span>İzin</span>
                    <span class="rail-off-time num"><?= e($band['label']) ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</section>

<section class="sheet mb-6"
         data-tour="21" data-tour-title="Haftalık şablon"
         data-tour-text="Her hafta tekrar eden kural: gün, başlangıç, bitiş. Bir güne birden çok aralık girilebilir (öğle arası böyle çıkar). Şablon hiç girilmemişse her saat uygun sayılır. Haftalık bir kural tek bir günün kutusundan silinemez — düzenleme hep burada.">
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
    <?php // Takvim kaydettikten sonra bulunduğu yerde kalsın diye taşınır. ?>
    <input type="hidden" name="gorunum" value="<?= e($mode) ?>">
    <input type="hidden" name="tarih" value="<?= e($anchor->format('Y-m-d')) ?>">
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

<section class="sheet"
         data-tour="22" data-tour-title="İzinler"
         data-tour-text="Şablon haftalık bir kural, izin belirli günlere yazılır: tatil, kongre, rapor. Tarih ve saat birlikte verilir, yani yarım günlük bir izin de girilebilir. Geçmiş izinler listeden kendiliğinden düşer.">
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
          <a href="<?= e($link(['gorunum' => 'hafta', 'tarih' => substr((string) $row['starts_at'], 0, 10)])) ?>"
             class="btn-text btn-text-quiet">Takvimde göster</a>
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
    <?php // Takvim kaydettikten sonra bulunduğu yerde kalsın diye taşınır. ?>
    <input type="hidden" name="gorunum" value="<?= e($mode) ?>">
    <input type="hidden" name="tarih" value="<?= e($anchor->format('Y-m-d')) ?>">
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
