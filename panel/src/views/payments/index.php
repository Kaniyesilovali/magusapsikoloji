<?php
use Panel\Money;
use Panel\Rbac;
/** @var array $rows @var array $totals @var DateTimeImmutable $from @var DateTimeImmutable $to */
/** @var array $nav @var string $status @var array $statusLabels */
/** @var array $therapists @var int $therapistFilter @var array $actor */

$isMonth = (bool) $nav['isMonth'];

/** Dönem değişirken kip, durum ve terapist filtreleri korunur. */
$link = static function (array $params) use ($status, $therapistFilter, $isMonth): string {
    // Serbest aralıktayken kip her bağlantıda taşınıyor: oklar aralığı kendi
    // uzunluğu kadar kaydırdığında sonuç tam bir aya denk gelebilir ve kip
    // aralığın şeklinden okunsaydı ekran kendiliğinden ay kipine düşerdi.
    // `ay` parametresi verilmiş bir bağlantı zaten ay kipine gidiyor.
    if (!$isMonth && !isset($params['ay'])) {
        $params['gorunum'] = 'aralik';
    }
    if ($status !== 'hepsi') {
        $params['durum'] = $status;
    }
    if ($therapistFilter > 0) {
        $params['terapist'] = (string) $therapistFilter;
    }
    return url('/odemeler?' . http_build_query($params));
};

// "Kısmi" de "ödenmedi" gibi bir bakiye demek — ikisi de insan kararı bekliyor,
// bu yüzden aynı rozeti taşırlar; farkı metin söyler.
$statusChip = [
    'belirsiz' => 'chip-neutral',
    'odenmedi' => 'chip-stop',
    'kismi'    => 'chip-stop',
    'odendi'   => 'chip-go',
];

// Birey bu listede yalnız kendi seanslarını görür: her satırda kendi adını
// tekrarlamak bilgi taşımaz, o yüzden "Birey" sütunu ona çizilmez. Ad, birey
// kaydına bağlantıdır ve bireyin o kaydı açma yetkisi de yok.
$isClient = ($actor['role'] ?? '') === Rbac::CLIENT;

// Dönem denetimi bugüne kadar iki şeritti: üstte oklar ve seçer seçmez giden
// bir ay kutusu, hemen altında kendi "Göster"i olan bir tarih aralığı. İkisi de
// aynı soruyu cevaplıyordu — hangi aralığa bakıyorum — ve hangisinin kazandığı
// denenmeden anlaşılmıyordu. Artık tek şerit: anahtar hangi denetimin
// görüneceğine karar veriyor, filtreler ayrı satırda kendi işini yapıyor.
// Filtreler dönem değişirken, dönem de filtre uygulanırken korunmalı; ikisi de
// gizli alan olarak karşı forma taşınıyor.
$periodParams = $isMonth
    ? ['ay' => $from->format('Y-m')]
    : [
        'gorunum'   => 'aralik',
        'baslangic' => $from->format('Y-m-d'),
        'bitis'     => $to->format('Y-m-d'),
    ];

$filterLine = [];
if ($therapistFilter > 0) {
    foreach ($therapists as $therapist) {
        if ($therapistFilter === (int) $therapist['id']) {
            $filterLine[] = (string) $therapist['full_name'];
            break;
        }
    }
} elseif ($therapists !== []) {
    $filterLine[] = 'Tüm terapistler';
}
$filterLine[] = $status === 'hepsi' ? 'tüm durumlar' : mb_strtolower($statusLabels[$status], 'UTF-8');
if (!Rbac::can($actor, 'payment.view.all')) {
    $filterLine[] = 'yalnız kendi randevularınız';
}
?>

<?php // Tur adımları 20'den başlar; 10–19 kenar çubuğunun (bkz. views/layout.php). ?>
<header class="mb-6"
        data-tour="20" data-tour-title="Bekleyen tutar"
        data-tour-text="Bu ekranın var oluş sebebi en üstteki satır: seçili dönemde kaç seans var ve ne kadarı tahsil edilmedi. Başlık dönemin kendisi, altındaki satır uygulanan filtreler.">
  <?php // Başlık üstü satırı menü öbeğini değil, bakılan dönemin özetini
        // söylüyor: bekleyen tutar bu ekranın var oluş sebebi. ?>
  <p class="eyebrow">
    <span class="num"><?= (int) $totals['count'] ?></span> seans
    <?php if (Money::isPositive($totals['outstanding'])): ?>
      · bekleyen <span class="num"><?= e(Money::format($totals['outstanding'])) ?></span>
    <?php else: ?>
      · bekleyen yok
    <?php endif; ?>
  </p>
  <h1 class="page-title mt-2">
    <?= e(tr_range_label($from->format('Y-m-d'), $to->format('Y-m-d'))) ?>
  </h1>
  <p class="page-sub"><?= e(implode(' · ', $filterLine)) ?></p>
</header>

<div class="grid grid-cols-2 lg:grid-cols-3 gap-3 mb-6">
  <div class="sheet">
    <div class="sheet-body">
      <p class="text-xl font-medium text-ink num"><?= e(Money::format($totals['fee'])) ?></p>
      <p class="eyebrow mt-1">Toplam ücret</p>
    </div>
  </div>
  <div class="sheet">
    <div class="sheet-body">
      <p class="text-xl font-medium text-primary-dark num"><?= e(Money::format($totals['paid'])) ?></p>
      <p class="eyebrow mt-1">Tahsil edilen</p>
    </div>
  </div>
  <div class="sheet">
    <div class="sheet-body">
      <p class="text-xl font-medium num <?= Money::isPositive($totals['outstanding']) ? 'text-accent-dark' : 'text-ink' ?>">
        <?= e(Money::format($totals['outstanding'])) ?>
      </p>
      <p class="eyebrow mt-1">Bekleyen (iptaller hariç)</p>
    </div>
  </div>
</div>

<?php // ── Dönem ─────────────────────────────────────────────────────────────
      // Tek şerit: oklar, ay/aralık anahtarı ve seçili olanın kendi denetimi.
      // Ödemelere neredeyse hep ay ay bakılıyor, bu yüzden "Ay" seçer seçmez
      // gidiyor; serbest aralık düğmesini koruyor çünkü orada iki alan birlikte
      // kuruluyor ve her değişiklikte yenilemek yarım bir aralığı uygulardı. ?>
<div class="ctl mb-3"
     data-tour="21" data-tour-title="Dönem"
     data-tour-text="Ödemelere neredeyse hep ay ay bakılır: “Ay” seçilir seçilmez gider. Serbest bir aralık için “Aralık” — orada iki tarih birlikte kurulduğu için “Göster” düğmesi duruyor, yoksa yarım bir aralık uygulanırdı. Oklar seçili dönemi bir adım öteler.">
  <nav class="flex items-center gap-1.5" aria-label="Dönem">
    <a href="<?= e($link($nav['prev'])) ?>" class="btn btn-quiet btn-sm">←&nbsp;Önceki</a>
    <a href="<?= e($link(['ay' => date('Y-m')])) ?>" class="btn btn-quiet btn-sm">Bu ay</a>
    <a href="<?= e($link($nav['next'])) ?>" class="btn btn-quiet btn-sm">Sonraki&nbsp;→</a>
  </nav>

  <nav class="seg" aria-label="Dönem türü">
    <a href="<?= e($link(['ay' => $from->format('Y-m')])) ?>"
       <?= $isMonth ? 'aria-current="true"' : '' ?>>Ay</a>
    <?php // Kip burada açıkça veriliyor: ay kipindeyken bağlantı zaten tam bir
          // aya işaret ediyor ve kip taşınmasaydı düğme kendine dönerdi. ?>
    <a href="<?= e($link(['gorunum' => 'aralik', 'baslangic' => $from->format('Y-m-d'), 'bitis' => $to->format('Y-m-d')])) ?>"
       <?= $isMonth ? '' : 'aria-current="true"' ?>>Aralık</a>
  </nav>

  <?php if ($isMonth): ?>
    <form method="get" action="<?= e(url('/odemeler')) ?>" data-autosubmit
          class="flex flex-wrap items-center gap-2">
      <?php if ($status !== 'hepsi'): ?>
        <input type="hidden" name="durum" value="<?= e($status) ?>">
      <?php endif; ?>
      <?php if ($therapistFilter > 0): ?>
        <input type="hidden" name="terapist" value="<?= (int) $therapistFilter ?>">
      <?php endif; ?>
      <label for="ay" class="sr-only">Ay seç</label>
      <select id="ay" name="ay" class="field w-auto py-1.5 text-[0.8125rem]">
        <?php foreach (tr_month_options($from, 'Y-m') as $value => $label): ?>
          <option value="<?= e($value) ?>" <?= $value === $from->format('Y-m') ? 'selected' : '' ?>>
            <?= e($label) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button class="btn-text" data-no-js>Aya git</button>
    </form>
  <?php else: ?>
    <form method="get" action="<?= e(url('/odemeler')) ?>"
          class="flex flex-wrap items-center gap-2">
      <?php // Kip formda da taşınıyor: kullanıcının kurduğu aralık tam bir aya
            // denk geldiğinde ekran kendiliğinden ay kipine düşmemeli. ?>
      <input type="hidden" name="gorunum" value="aralik">
      <?php if ($status !== 'hepsi'): ?>
        <input type="hidden" name="durum" value="<?= e($status) ?>">
      <?php endif; ?>
      <?php if ($therapistFilter > 0): ?>
        <input type="hidden" name="terapist" value="<?= (int) $therapistFilter ?>">
      <?php endif; ?>
      <label for="baslangic" class="sr-only">Başlangıç</label>
      <input type="date" id="baslangic" name="baslangic" value="<?= e($from->format('Y-m-d')) ?>"
             class="field w-auto py-1.5 text-[0.8125rem] num">
      <span class="text-ink-light" aria-hidden="true">→</span>
      <label for="bitis" class="sr-only">Bitiş</label>
      <input type="date" id="bitis" name="bitis" value="<?= e($to->format('Y-m-d')) ?>"
             class="field w-auto py-1.5 text-[0.8125rem] num">
      <button class="btn btn-quiet btn-sm">Göster</button>
      <?php // Serbest aralıkta okların ne yaptığı görünmüyor; söylenmezse
            // kullanıcının kurduğu aralığı bozdukları sanılır. ?>
      <span class="text-xs text-ink-light">Oklar aralığı kendi uzunluğu kadar kaydırır.</span>
    </form>
  <?php endif; ?>
</div>

<?php // Filtre ayrı satırda: "hangi aralık" ile "o aralıkta neyi göster" iki
      // ayrı soru. Dönem gizli alanlarla korunuyor, yoksa filtre uygulamak
      // kullanıcıyı sessizce içinde bulunduğu aydan çıkarırdı. ?>
<form method="get" action="<?= e(url('/odemeler')) ?>" data-autosubmit
      class="ctl mb-4">
  <?php foreach ($periodParams as $name => $value): ?>
    <input type="hidden" name="<?= e($name) ?>" value="<?= e($value) ?>">
  <?php endforeach; ?>

  <label for="durum" class="eyebrow">Filtre</label>
  <select id="durum" name="durum" class="field w-auto py-1.5 text-[0.8125rem]">
    <option value="hepsi">Tüm durumlar</option>
    <?php foreach ($statusLabels as $key => $label): ?>
      <option value="<?= e($key) ?>" <?= $status === $key ? 'selected' : '' ?>><?= e($label) ?></option>
    <?php endforeach; ?>
  </select>

  <?php if ($therapists !== []): ?>
    <label for="terapist" class="sr-only">Terapist</label>
    <select id="terapist" name="terapist" class="field w-auto py-1.5 text-[0.8125rem]">
      <option value="">Tüm terapistler</option>
      <?php foreach ($therapists as $therapist): ?>
        <option value="<?= (int) $therapist['id'] ?>" <?= $therapistFilter === (int) $therapist['id'] ? 'selected' : '' ?>>
          <?= e($therapist['full_name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  <?php endif; ?>

  <button class="btn-text" data-no-js>Göster</button>
</form>

<div class="sheet"
     data-tour="22" data-tour-title="Ücret ile tahsilat ayrı şeyler"
     data-tour-text="Her satır bir seans. “Ücret” o seansın fiyatı, “Tahsil” bugüne kadar alınan tutar; ikisi arasındaki fark durumu belirler. Satıra girip ücreti yazabilir, kısmi tahsilat ekleyebilirsiniz — bir seans birden çok ödemeyle kapanabilir.">
  <?php if ($rows === []): ?>
    <p class="sheet-empty">Bu aralıkta kayıt yok.</p>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="tbl">
        <thead>
          <tr>
            <th>Tarih</th>
            <?php if (!$isClient): ?><th>Birey</th><?php endif; ?>
            <th>Terapist</th>
            <th class="text-right">Ücret</th>
            <th class="text-right">Tahsil</th>
            <th>Durum</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td class="text-xs text-ink-muted num whitespace-nowrap">
                <?= e(dt($row['starts_at'])) ?>
                <?php // Sütun gizlendiğinde iptal rozeti de onunla birlikte kaybolmasın. ?>
                <?php if ($isClient && $row['status'] === 'cancelled'): ?>
                  <span class="chip chip-done ml-1.5">İptal</span>
                <?php endif; ?>
              </td>
              <?php if (!$isClient): ?>
                <td>
                  <a href="<?= e(url("/danisanlar/{$row['client_id']}")) ?>" class="person text-ink hover:text-primary">
                    <?= e($row['client_name']) ?>
                  </a>
                  <?php if ($row['status'] === 'cancelled'): ?>
                    <span class="chip chip-done ml-1.5">İptal</span>
                  <?php endif; ?>
                </td>
              <?php endif; ?>
              <td class="text-ink-muted"><?= e($row['therapist_name']) ?></td>
              <td class="text-right num text-ink"><?= e(Money::format($row['fee'])) ?></td>
              <td class="text-right num text-ink-muted"><?= e(Money::format($row['paid'])) ?></td>
              <td>
                <span class="chip <?= $statusChip[$row['payment_status']] ?? 'chip-neutral' ?>">
                  <?= e($statusLabels[$row['payment_status']]) ?>
                </span>
              </td>
              <td class="text-right">
                <a href="<?= e(url("/odemeler/{$row['id']}")) ?>" class="btn-text btn-text-quiet">Aç</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
