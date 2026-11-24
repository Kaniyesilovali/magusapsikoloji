<?php
use Panel\Money;
use Panel\Scheduling;
/** @var DateTimeImmutable $month @var array $revenue @var array $appointments */
/** @var list<array> $utilisation @var list<array> $therapists @var ?int $therapistId */
/** @var ?array $revenue @var array $density @var list<array> $trend @var ?string $type */
/** @var int $period @var DateTimeImmutable $from @var bool $scopeAll */

// Üst yarı yönetimin üç sorusu: ne kazandık, kaç seans boşa gitti, kim ne kadar
// doldu. Alt yarı (Yoğunluk) takvimin şekli: aydan aya seyir, hangi gün hangi
// saat, hangi seans türü ne zaman. Terapist ekranı aynı sayfa, yalnız kendi
// seanslarıyla ve gelir bölümü olmadan (bkz. ReportController).
$saat = static fn (int $dakika): string => number_format($dakika / 60, 1, ',', '.');

$baslik = $period === 1
    ? tr_month_name((int) $month->format('n')) . ' ' . $month->format('Y')
    : tr_month_name((int) $from->format('n')) . ($from->format('Y') !== $month->format('Y') ? ' ' . $from->format('Y') : '')
      . ' – ' . tr_month_name((int) $month->format('n')) . ' ' . $month->format('Y');

$kisaGun = [1 => 'Pzt', 2 => 'Sal', 3 => 'Çar', 4 => 'Per', 5 => 'Cum', 6 => 'Cmt', 7 => 'Paz'];
$saatEt  = static fn (int $h): string => sprintf('%02d', $h);
$turAdi  = static fn (string $k): string => $k === 'none' ? 'Türü girilmemiş' : Scheduling::sessionTypeLabel($k);
$yuzde   = static fn (int $n, int $toplam): string => $toplam > 0 ? '%' . number_format($n / $toplam * 100, 0, ',', '.') : '—';

// Isı tablosu: satır × sütun sayıları, her hücre en yoğun hücreye göre tonlanır.
// $rows: [anahtar => etiket], $cols: [anahtar => etiket], $data[satır][sütun].
$isi = static function (array $rows, array $cols, array $data, string $colSuffix) use ($yuzde): void {
    $max = 0;
    $colSum = [];
    foreach ($rows as $r => $_) {
        foreach ($cols as $c => $_) {
            $n = (int) ($data[$r][$c] ?? 0);
            $max = max($max, $n);
            $colSum[$c] = ($colSum[$c] ?? 0) + $n;
        }
    }
    $total = array_sum($colSum);
    ?>
    <div class="rep-scroll">
      <table class="heat">
        <thead>
          <tr>
            <th scope="col"><span class="sr-only">Satır</span></th>
            <?php foreach ($cols as $label): ?><th scope="col"><?= e($label) ?></th><?php endforeach; ?>
            <th scope="col" class="heat-sum">Toplam</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r => $rowLabel): ?>
            <?php $rowSum = 0; ?>
            <tr>
              <th scope="row"><?= e($rowLabel) ?></th>
              <?php foreach ($cols as $c => $colLabel): ?>
                <?php $n = (int) ($data[$r][$c] ?? 0); $rowSum += $n; ?>
                <td class="hc hc-<?= Panel\Reports::level($n, $max) ?> num"
                    title="<?= e($rowLabel . ' · ' . $colLabel . $colSuffix . ' — ' . $n . ' seans') ?>"><?= $n > 0 ? $n : '' ?></td>
              <?php endforeach; ?>
              <td class="heat-sum num"><?= $rowSum ?> <span class="text-ink-light"><?= e($yuzde($rowSum, $total)) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <th scope="row">Toplam</th>
            <?php foreach ($cols as $c => $_): ?><td class="heat-sum num"><?= (int) $colSum[$c] ?></td><?php endforeach; ?>
            <td class="heat-sum num"><?= $total ?></td>
          </tr>
        </tfoot>
      </table>
    </div>
    <?php
};

// Yatay çubuk listesi: [etiket => sayı], çubuk en büyüğe göre.
$cubuklar = static function (array $items, int $total) use ($yuzde): void {
    $max = $items === [] ? 0 : max($items);
    ?>
    <ul class="divide-y divide-warm-secondary">
      <?php foreach ($items as $label => $n): ?>
        <?php $w = $max > 0 ? (int) round($n / $max * 50) * 2 : 0; $tepe = $n > 0 && $n === $max; ?>
        <li class="px-5 py-2.5 flex items-center gap-4">
          <span class="text-sm text-ink w-28 shrink-0"><?= e((string) $label) ?></span>
          <span class="util" aria-hidden="true"><span class="util-fill util-<?= $w ?>"></span></span>
          <span class="num text-sm <?= $tepe ? 'text-primary-dark font-medium' : 'text-ink' ?> w-8 text-right"><?= (int) $n ?></span>
          <span class="num text-xs text-ink-light"><?= e($yuzde((int) $n, $total)) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
    <?php
};
?>

<?php // Tur adımları 20'den başlar; 10–19 kenar çubuğunun (bkz. views/layout.php). ?>
<header class="flex flex-wrap items-end justify-between gap-4 mb-6"
        data-tour="20" data-tour-title="Dönem seçimi"
        data-tour-text="Sağdaki kutulardan ay ve dönem seçilir: tek ay ya da o aya kadar son 3, 6, 12 ay. Seçim anında uygulanır. Aşağıda önce dönemin özeti, sonra takvimin yoğunluğu var.">
  <div>
    <p class="eyebrow"><?= $scopeAll ? 'Yönetim' : 'Kendi seanslarınız' ?></p>
    <h1 class="page-title mt-2"><?= e($baslik) ?></h1>
    <p class="page-sub">
      <?= $scopeAll ? 'Gelir, gelmeme oranı, doluluk' : 'Gelmeme oranı, doluluk' ?>
      ve gün, saat, seans türüne göre yoğunluk.
    </p>
  </div>

  <form method="get" action="<?= e(url('/raporlar')) ?>" data-autosubmit class="flex flex-wrap items-center gap-2">
    <label for="ay" class="sr-only">Ay</label>
    <select id="ay" name="ay" class="field w-auto py-1.5 text-[0.8125rem]">
      <?php foreach (tr_month_options($month, 'Y-m') as $value => $label): ?>
        <option value="<?= e($value) ?>" <?= $value === $month->format('Y-m') ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>

    <label for="donem" class="sr-only">Dönem</label>
    <select id="donem" name="donem" class="field w-auto py-1.5 text-[0.8125rem]">
      <?php foreach (Panel\Controllers\ReportController::PERIODS as $value => $label): ?>
        <option value="<?= (int) $value ?>" <?= $value === $period ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>

    <?php if ($type !== null): ?>
      <input type="hidden" name="tur" value="<?= e($type) ?>">
    <?php endif; ?>

    <?php if ($therapists !== []): ?>
      <label for="terapist" class="eyebrow ml-1">Terapist</label>
      <select id="terapist" name="terapist" class="field w-auto py-1.5 text-[0.8125rem]">
        <option value="">Tümü</option>
        <?php foreach ($therapists as $therapist): ?>
          <option value="<?= (int) $therapist['id'] ?>" <?= $therapistId === (int) $therapist['id'] ? 'selected' : '' ?>>
            <?= e($therapist['full_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    <?php endif; ?>

    <button class="btn-text" data-no-js>Göster</button>
  </form>
</header>

<?php if ($revenue !== null): ?>
<section class="sheet mb-6"
         data-tour="21" data-tour-title="Gelir"
         data-tour-text="Toplam ücret o ayki seansların fiyatı, tahsil edilen eldeki para, bekleyen ikisinin farkı. Bekleyen tutarın ayrıntısı Ödemeler ekranında: hangi seans, kimden.">
  <div class="sheet-head"><h2 class="sheet-title">Gelir</h2></div>
  <div class="grid gap-px bg-warm-secondary sm:grid-cols-3">
    <div class="bg-white px-5 py-4">
      <p class="eyebrow">Toplam ücret</p>
      <p class="num text-xl text-ink mt-1"><?= e(Money::format($revenue['fee'])) ?></p>
      <p class="text-xs text-ink-light mt-1"><span class="num"><?= (int) $revenue['sessions'] ?></span> seans</p>
    </div>
    <div class="bg-white px-5 py-4">
      <p class="eyebrow">Tahsil edilen</p>
      <p class="num text-xl text-primary mt-1"><?= e(Money::format($revenue['paid'])) ?></p>
    </div>
    <div class="bg-white px-5 py-4">
      <p class="eyebrow">Bekleyen</p>
      <p class="num text-xl text-accent-dark mt-1"><?= e(Money::format($revenue['outstanding'])) ?></p>
      <p class="text-xs text-ink-light mt-1">İptal edilen seanslar borç sayılmaz.</p>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="sheet mb-6">
  <div class="sheet-head">
    <div>
      <h2 class="sheet-title">Randevular</h2>
      <p class="text-xs text-ink-light mt-1">
        Gelmeme oranının paydası <strong>tamamlanan + gelmeyen</strong>. İptal, gelmeme
        değildir — önceden haber verilmiş bir değişikliktir; paydaya katmak oranı sulandırırdı.
      </p>
    </div>
    <?php if ($appointments['noShowRate'] !== null): ?>
      <span class="chip <?= $appointments['noShowRate'] >= 20 ? 'chip-stop' : 'chip-go' ?>">
        <span class="num">%<?= e(number_format($appointments['noShowRate'], 1, ',', '.')) ?></span> gelmedi
      </span>
    <?php endif; ?>
  </div>

  <?php if ($appointments['total'] === 0): ?>
    <p class="sheet-empty">Bu dönemde randevu yok.</p>
  <?php else: ?>
    <ul class="divide-y divide-warm-secondary">
      <?php foreach (['completed', 'no_show', 'confirmed', 'scheduled', 'cancelled'] as $status): ?>
        <li class="px-5 py-3 flex items-center gap-4">
          <span class="text-sm text-ink w-40 shrink-0"><?= e(Scheduling::statusLabel($status)) ?></span>
          <span class="num text-sm text-ink"><?= (int) ($appointments['counts'][$status] ?? 0) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
    <div class="sheet-foot">
      <p class="text-xs text-ink-muted">
        Toplam <span class="num"><?= (int) $appointments['total'] ?></span> randevu;
        sonucu belli olan <span class="num"><?= (int) $appointments['settled'] ?></span> tanesi orana giriyor.
        <?php if ($appointments['noShowRate'] === null): ?>
          Henüz sonucu belli olan seans yok, oran hesaplanmadı.
        <?php endif; ?>
      </p>
    </div>
  <?php endif; ?>
</section>

<section class="sheet mb-10"
         data-tour="22" data-tour-title="Doluluk"
         data-tour-text="Terapistin haftalık çalışma şablonuna göre dönemdeki saatlerinin ne kadarı seansla dolmuş. Payda Müsaitlik ekranındaki şablondan gelir — şablon girilmemişse bu bölüm bir şey söyleyemez.">
  <div class="sheet-head">
    <div>
      <h2 class="sheet-title">Doluluk</h2>
      <p class="text-xs text-ink-light mt-1">
        Dolu saat / çalışma şablonundaki açık saat. İzinler ve özel zamanlar kapasiteden düşülür.
      </p>
    </div>
  </div>

  <?php if ($utilisation === []): ?>
    <p class="sheet-empty">Aktif terapist yok.</p>
  <?php else: ?>
    <ul class="divide-y divide-warm-secondary">
      <?php foreach ($utilisation as $row): ?>
        <li class="px-5 py-3 flex flex-wrap items-center gap-x-4 gap-y-1">
          <span class="person text-sm text-ink w-48 shrink-0"><?= e($row['name']) ?></span>

          <?php if ($row['rate'] === null): ?>
            <?php // Şablonsuz terapistte oran uydurulmuyor: "her saat uygun"
                  // sonsuz kapasite demek ve payda tanımsız kalıyor. ?>
            <span class="chip chip-neutral">şablon yok</span>
            <span class="text-xs text-ink-light flex-1 min-w-48">
              Müsaitlik ekranından çalışma saatleri girilmeden doluluk hesaplanamaz.
            </span>
          <?php else: ?>
            <?php
            // Çubuk %100'de durur, sayı durmaz: şablon dışına açılan seanslar
            // oranı üstüne taşıyabiliyor. Adım %2 (bkz. .util-N).
            $dolu = (int) round(min($row['rate'], 100) / 2) * 2;
            $sikisik = $row['rate'] >= 80;
            ?>
            <span class="util" aria-hidden="true">
              <span class="util-fill util-<?= $dolu ?><?= $sikisik ? ' is-full' : '' ?>"></span>
            </span>
            <span class="num util-rate<?= $sikisik ? ' is-full' : '' ?>">%<?= e(number_format($row['rate'], 1, ',', '.')) ?></span>
            <span class="text-xs text-ink-light flex-1 min-w-48 num">
              <?= e($saat($row['booked'])) ?> / <?= e($saat((int) $row['capacity'])) ?> saat
              — <?= (int) $row['sessions'] ?> seans
            </span>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
    <div class="sheet-foot">
      <p class="text-xs text-ink-muted">
        Çubuk dönemin ne kadarının dolduğunu gösterir. %80'den sonra uyarı rengine döner;
        bu bir hedef değil, ara verecek yer kalmadığının işareti. Düşük doluluk ayrıca
        işaretlenmiyor — boş çubuk zaten görünüyor. Şablon saatleri dışına açılan
        seanslar oranı %100'ün üstüne taşıyabilir, çubuk orada dolu kalır.
      </p>
    </div>
  <?php endif; ?>
</section>

<?php
// ── Yoğunluk ────────────────────────────────────────────────────
// Tür filtresi yalnız bu yarıya uygulanır: gelir ve doluluk türe bölünmez,
// bölününce payda (çalışma şablonu) anlamını yitirir. O yüzden kendi formu var.
$gunler = [];
foreach (Scheduling::WEEKDAYS as $wd => $_) {
    $gunler[$wd] = $kisaGun[$wd];
}
$saatler = [];
foreach ($density['hours'] as $h) {
    $saatler[$h] = $saatEt($h);
}
?>

<header class="flex flex-wrap items-end justify-between gap-4 mb-4"
        data-tour="23" data-tour-title="Yoğunluk"
        data-tour-text="Takvimin şekli: hangi ay, hangi gün, hangi saat, hangi seans türü. Sayılan şey ayrılmış saat — iptaller dışarıda, gelmeyenler içeride. Sağdaki kutuyla tek bir seans türüne bakılabilir.">
  <div>
    <h2 class="page-title text-xl">Yoğunluk</h2>
    <p class="page-sub">
      İptal edilenler sayılmaz; gelinmeyen seanslar sayılır, çünkü o saat yine ayrılmıştı.
    </p>
  </div>

  <?php if ($density['typesReady']): ?>
    <form method="get" action="<?= e(url('/raporlar')) ?>" data-autosubmit class="flex flex-wrap items-center gap-2">
      <input type="hidden" name="ay" value="<?= e($month->format('Y-m')) ?>">
      <input type="hidden" name="donem" value="<?= (int) $period ?>">
      <?php if ($scopeAll && $therapistId !== null): ?>
        <input type="hidden" name="terapist" value="<?= (int) $therapistId ?>">
      <?php endif; ?>
      <label for="tur" class="eyebrow">Seans türü</label>
      <select id="tur" name="tur" class="field w-auto py-1.5 text-[0.8125rem]">
        <option value="">Tümü</option>
        <?php foreach (Scheduling::SESSION_TYPES as $value => $label): ?>
          <option value="<?= e($value) ?>" <?= $type === $value ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn-text" data-no-js>Göster</button>
    </form>
  <?php endif; ?>
</header>

<?php // ── Aylık seyir ── ?>
<?php
$seyirMax  = max(array_column($trend, 'booked') ?: [0]);
$seyirTur  = [];
foreach ($trend as $row) {
    foreach ($row['types'] as $k => $_) {
        $seyirTur[$k] = true;
    }
}
$seyirTur = array_values(array_filter(
    [...array_keys(Scheduling::SESSION_TYPES), 'none'],
    static fn (string $k): bool => isset($seyirTur[$k])
));
?>
<section class="sheet mb-6"
         data-tour="24" data-tour-title="Aylık seyir"
         data-tour-text="Seçilen aya kadar son on iki ay. Sütun o ayın seans sayısı; altındaki tablo tamamlanan, gelinmeyen, iptal ve seans türüne göre ayrıntıyı verir. Dönem seçiminden bağımsızdır.">
  <div class="sheet-head">
    <div>
      <h2 class="sheet-title">Aylık seyir</h2>
      <p class="text-xs text-ink-light mt-1">
        Son 12 ay<?= $type !== null ? ' · yalnız ' . e(Scheduling::sessionTypeLabel($type)) : '' ?>.
        Sütun: iptal dışındaki seanslar.
      </p>
    </div>
  </div>

  <?php if ($seyirMax === 0 && array_sum(array_column($trend, 'cancelled')) === 0): ?>
    <p class="sheet-empty">Son 12 ayda randevu yok.</p>
  <?php else: ?>
    <div class="rep-scroll">
      <div class="trend" role="img"
           aria-label="<?= e('Son 12 ayda aylık seans sayısı: ' . implode(', ', array_map(
               static fn (array $r): string => tr_month_name((int) $r['month']->format('n')) . ' ' . $r['booked'],
               $trend
           ))) ?>">
        <?php foreach ($trend as $row): ?>
          <?php
          $h = $seyirMax > 0 ? (int) round($row['booked'] / $seyirMax * 20) * 5 : 0;
          $secili = $row['month']->format('Y-m') === $month->format('Y-m');
          ?>
          <div class="trend-col<?= $secili ? ' is-current' : '' ?>">
            <span class="trend-n num"><?= (int) $row['booked'] ?></span>
            <span class="trend-track"><span class="trend-bar tbar-<?= $h ?>"></span></span>
            <span class="trend-label"><?= e(mb_substr(tr_month_name((int) $row['month']->format('n')), 0, 3)) ?><br><span class="num"><?= e($row['month']->format('y')) ?></span></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="rep-scroll border-t border-warm-secondary">
      <table class="rep-table">
        <thead>
          <tr>
            <th scope="col">Ay</th>
            <th scope="col">Seans</th>
            <th scope="col">Tamamlanan</th>
            <th scope="col">Gelinmedi</th>
            <th scope="col">İptal</th>
            <?php foreach ($seyirTur as $k): ?><th scope="col"><?= e($turAdi($k)) ?></th><?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach (array_reverse($trend) as $row): ?>
            <tr<?= $row['month']->format('Y-m') === $month->format('Y-m') ? ' class="is-current"' : '' ?>>
              <th scope="row"><?= e(tr_month_name((int) $row['month']->format('n')) . ' ' . $row['month']->format('Y')) ?></th>
              <td class="num"><?= (int) $row['booked'] ?></td>
              <td class="num"><?= (int) $row['completed'] ?></td>
              <td class="num"><?= (int) $row['noShow'] ?></td>
              <td class="num"><?= (int) $row['cancelled'] ?></td>
              <?php foreach ($seyirTur as $k): ?><td class="num"><?= (int) ($row['types'][$k] ?? 0) ?></td><?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php if ($density['total'] === 0): ?>
  <section class="sheet mb-6">
    <p class="sheet-empty">Bu dönemde<?= $type !== null ? ' bu türde' : '' ?> seans yok; yoğunluk hesaplanacak veri yok.</p>
  </section>
<?php else: ?>

<?php // ── Gün × saat ── ?>
<section class="sheet mb-6"
         data-tour="25" data-tour-title="Gün × saat"
         data-tour-text="Her hücre o gün o saatte başlayan seans sayısı. Koyu hücre dönemin en yoğun dilimi; boş hücre o saatte hiç seans yok. Satır ve sütun sonlarındaki toplamlar gün ve saat yoğunluğunu verir.">
  <div class="sheet-head">
    <div>
      <h2 class="sheet-title">Gün × saat</h2>
      <p class="text-xs text-ink-light mt-1">
        Hücre: o gün o saatte başlayan seans sayısı (09:30 → 09). Ton, dönemin en yoğun hücresine göre.
      </p>
    </div>
    <span class="chip chip-neutral"><span class="num"><?= (int) $density['total'] ?></span> seans</span>
  </div>
  <div class="sheet-body">
    <?php $isi($gunler, $saatler, $density['grid'], ':00'); ?>
    <p class="heat-legend" aria-hidden="true">
      Az <span class="hc hc-1"></span><span class="hc hc-2"></span><span class="hc hc-3"></span><span class="hc hc-4"></span><span class="hc hc-5"></span> Çok
    </p>
  </div>
</section>

<?php // ── Gün ve saat yoğunluğu ── ?>
<div class="grid gap-6 lg:grid-cols-2 mb-6">
  <section class="sheet">
    <div class="sheet-head"><h2 class="sheet-title">Gün yoğunluğu</h2></div>
    <?php
    $gunSayi = [];
    foreach (Scheduling::WEEKDAYS as $wd => $label) {
        $gunSayi[$label] = (int) ($density['byWeekday'][$wd] ?? 0);
    }
    $cubuklar($gunSayi, $density['total']);
    ?>
  </section>

  <section class="sheet">
    <div class="sheet-head"><h2 class="sheet-title">Saat yoğunluğu</h2></div>
    <?php
    $saatSayi = [];
    foreach ($density['hours'] as $h) {
        $saatSayi[$saatEt($h) . ':00'] = (int) ($density['byHour'][$h] ?? 0);
    }
    $cubuklar($saatSayi, $density['total']);
    ?>
  </section>
</div>

<?php // ── Seans türü ── ?>
<section class="sheet mb-6"
         data-tour="26" data-tour-title="Seans türü"
         data-tour-text="Hangi seans türünün ne kadar yer tuttuğu, ve hangi gün, hangi saatte yoğunlaştığı. Tür filtresi seçiliyken burada yalnız o tür görünür.">
  <div class="sheet-head">
    <div>
      <h2 class="sheet-title">Seans türü yoğunluğu</h2>
      <p class="text-xs text-ink-light mt-1">
        Tür, randevunun kendi türüdür (bireyin kaydındaki değil). Türü girilmemiş eski randevular ayrı satırda.
      </p>
    </div>
  </div>

  <?php if (!$density['typesReady']): ?>
    <p class="sheet-empty">Seans türü alanı henüz kurulmadı — Sistem ekranından bekleyen güncellemeyi uygulayın.</p>
  <?php else: ?>
    <?php
    $turSayi = [];
    $turSatir = [];
    foreach ($density['byType'] as $k => $n) {
        $turSayi[$turAdi($k)] = $n;
        $turSatir[$k] = $turAdi($k);
    }
    $cubuklar($turSayi, $density['total']);
    ?>
    <div class="sheet-body border-t border-warm-secondary">
      <h3 class="eyebrow mb-2">Tür × gün</h3>
      <?php $isi($turSatir, $gunler, $density['typeWeekday'], ''); ?>
    </div>
    <div class="sheet-body border-t border-warm-secondary">
      <h3 class="eyebrow mb-2">Tür × saat</h3>
      <?php $isi($turSatir, $saatler, $density['typeHour'], ':00'); ?>
    </div>
  <?php endif; ?>
</section>

<?php endif; ?>
