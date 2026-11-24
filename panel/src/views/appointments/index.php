<?php
use Panel\Rbac;
/** @var string $mode @var DateTimeImmutable $anchor @var DateTimeImmutable $monthStart */
/** @var DateTimeImmutable $rangeStart @var DateTimeImmutable $rangeEnd */
/** @var array $days @var array|null $rail @var int $total */
/** @var array $therapists @var int $therapistFilter @var string $therapistParam @var bool $teamView @var bool $canBlock @var array $actor */

$today   = date('Y-m-d');
$isMonth = $mode === 'ay';

$canEdit       = Rbac::can($actor, 'appointment.update');
$canCancel     = Rbac::can($actor, 'appointment.cancel');
$canCreate     = Rbac::can($actor, 'appointment.create');
$canWriteNotes = Rbac::canAny($actor, ['note.write', 'note.read.own']);

$chip = [
    'scheduled' => 'chip-neutral',
    'confirmed' => 'chip-go',
    'completed' => 'chip-done',
    'cancelled' => 'chip-stop',
    'no_show'   => 'chip-stop',
];
// Takvim kutusunun tonu durumu söyler; rozet için yer yok.
$tone = [
    'confirmed' => 'is-confirmed',
    'completed' => 'is-done',
    'no_show'   => 'is-off',
    'cancelled' => 'is-cancelled',
];

/** Görünüm, çapa ve terapist filtresi bağlantılar arasında taşınır. */
$link = static function (array $over = []) use ($mode, $anchor, $therapistParam): string {
    $params = ['gorunum' => $mode, 'tarih' => $anchor->format('Y-m-d')];
    if ($therapistParam !== '') {
        $params['terapist'] = $therapistParam;
    }
    return url('/randevular?' . http_build_query(array_merge($params, $over)));
};

// Bir adım = görünümün kendi birimi. Ay görünümünde hafta hafta ilerlemek,
// hafta görünümünde ay ay atlamak kullanıcıyı kaybettirirdi.
$prev = $isMonth ? $monthStart->modify('-1 month') : $rangeStart->modify('-7 days');
$next = $isMonth ? $monthStart->modify('+1 month') : $rangeStart->modify('+7 days');

// Başlık üstü satırı "Randevular" demiyor artık — menüde zaten işaretli ve
// başlığın kendisi tarih aralığı. Yerine hangi kayıtlara bakıldığı yazıyor:
// filtre açıkken bunu söyleyen tek yer aşağıdaki açılır kutuydu ve seçili
// terapistle gelen bir bağlantı sessizce eksik bir takvim gösteriyordu.
// Ekip takvimini gören terapistte boş seçim "kendi takvimi" demek.
$filterLabel = $teamView && $therapistParam === '' ? 'Benim takvimim' : 'Tüm terapistler';
foreach ($therapists as $therapist) {
    if ($therapistFilter === (int) $therapist['id']) {
        $filterLabel = (string) $therapist['full_name'];
        break;
    }
}
?>

<?php // Tur adımları 20'den başlar; 10–19 kenar çubuğunun (bkz. views/layout.php). ?>
<header class="flex flex-wrap items-end justify-between gap-4 mb-6"
        data-tour="20" data-tour-title="Neye bakıyorsunuz"
        data-tour-text="Başlık, ekranda duran aralığın kendisidir: bir hafta ya da bir ay. Üstündeki satır hangi terapistin randevularını gördüğünüzü söyler.">
  <div>
    <p class="eyebrow"><?= e($filterLabel) ?></p>
    <h1 class="page-title mt-2">
      <?php if ($isMonth): ?>
        <?= e(tr_month_name((int) $monthStart->format('n')) . ' ' . $monthStart->format('Y')) ?>
      <?php else: ?>
        <?= e(tr_range_label($rangeStart->format('Y-m-d'), $rangeStart->modify('+6 days')->format('Y-m-d'))) ?>
      <?php endif; ?>
    </h1>
    <p class="page-sub"><span class="num"><?= $total ?></span> randevu</p>
  </div>
  <div class="flex flex-wrap gap-2">
    <?php if ($canBlock): ?>
      <?php // Yönetici tek bir terapistin takvimine bakıyorsa seçim forma taşınır. ?>
      <a href="<?= e(url('/randevular/ozel-zaman/yeni' . ($therapistFilter > 0 && !$teamView ? '?terapist=' . $therapistFilter : ''))) ?>"
         class="btn btn-quiet">Özel zaman</a>
    <?php endif; ?>
    <?php if ($canCreate): ?>
      <a href="<?= e(url('/randevular/yeni?tarih=' . $rangeStart->format('Y-m-d'))) ?>" class="btn btn-primary">Yeni randevu</a>
    <?php endif; ?>
  </div>
</header>

<div class="ctl mb-4"
     data-tour="21" data-tour-title="Aynı randevular, üç okuma"
     data-tour-text="Hafta saatleriyle çizer, Ay hangi günün ne kadar dolu olduğunu söyler, Liste ise eylemleri taşıyan görünümdür — not, durum ve iptal orada. Oklar bir adım ileri geri gider; adımın boyu seçtiğiniz görünüm kadardır.">
    <nav class="flex items-center gap-1.5" aria-label="<?= $isMonth ? 'Ay' : 'Hafta' ?>">
      <a href="<?= e($link(['tarih' => $prev->format('Y-m-d')])) ?>" class="btn btn-quiet btn-sm">←&nbsp;Önceki</a>
      <a href="<?= e($link(['tarih' => $today])) ?>" class="btn btn-quiet btn-sm"><?= $isMonth ? 'Bu ay' : 'Bu hafta' ?></a>
      <a href="<?= e($link(['tarih' => $next->format('Y-m-d')])) ?>" class="btn btn-quiet btn-sm">Sonraki&nbsp;→</a>
    </nav>

    <?php // Aynı randevular, üç okuma biçimi. Liste eylemleri taşıyan tek görünüm. ?>
    <nav class="seg" aria-label="Görünüm">
      <?php foreach (['hafta' => 'Hafta', 'ay' => 'Ay', 'liste' => 'Liste'] as $value => $label): ?>
        <a href="<?= e($link(['gorunum' => $value])) ?>"
           <?= $mode === $value ? 'aria-current="true"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>

  <?php // Ay ve terapist tek formda: ikisi de "neye bakıyorum" sorusunun parçası,
        // ayrı ayrı iki "Göster" düğmesi aynı satırda gereksiz bir seçim yaratırdı.
        //
        // Ay seçicinin görünür etiketi yok: yanı başındaki görünüm düğmelerinden
        // biri de "Ay" ve ikisi farklı şey — aynı satırda iki "Ay" hangisinin ne
        // yaptığını okunmaz kılıyordu. Kutunun kendisi zaten "Temmuz 2026" yazıyor. ?>
  <form method="get" action="<?= e(url('/randevular')) ?>" data-autosubmit
        class="flex flex-wrap items-center gap-2">
    <input type="hidden" name="gorunum" value="<?= e($mode) ?>">

    <select id="tarih" name="tarih" aria-label="Ay seç" class="field w-auto py-1.5 text-[0.8125rem]">
      <?php foreach (tr_month_options($anchor) as $value => $label): ?>
        <option value="<?= e($value) ?>" <?= $value === $anchor->format('Y-m-d') ? 'selected' : '' ?>>
          <?= e($label) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <?php if ($therapists !== []): ?>
      <label for="terapist" class="eyebrow ml-1">Terapist</label>
      <?php // Terapistin etiketi kalıyor: "Tümü" tek başına neyin tümü olduğunu söylemez. ?>
      <select id="terapist" name="terapist" class="field w-auto py-1.5 text-[0.8125rem]">
        <?php if ($teamView): ?>
          <option value="">Benim takvimim</option>
          <option value="tumu" <?= $therapistParam === 'tumu' ? 'selected' : '' ?>>Tüm ekip</option>
        <?php else: ?>
          <option value="">Tümü</option>
        <?php endif; ?>
        <?php foreach ($therapists as $therapist): ?>
          <?php // Terapist kendi adını ayrıca görmez: "Benim takvimim" zaten o. ?>
          <?php if ($teamView && (int) $therapist['id'] === (int) $actor['id']) { continue; } ?>
          <option value="<?= (int) $therapist['id'] ?>" <?= $therapistFilter === (int) $therapist['id'] ? 'selected' : '' ?>>
            <?= e($therapist['full_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    <?php endif; ?>

    <button class="btn-text" data-no-js>Göster</button>
  </form>
</div>

<?php require __DIR__ . '/_' . ($mode === 'ay' ? 'month' : ($mode === 'liste' ? 'list' : 'week')) . '.php'; ?>
