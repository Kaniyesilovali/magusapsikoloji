<?php
use Panel\Scheduling;
/** Hafta takvimi — gün cetvelinin yan yana dizilmiş yedi kopyası.
 *  Yedi sütun tek bir saat penceresini paylaşır (bkz. Rail::week), böylece
 *  aynı yatay çizgi her sütunda aynı saati gösterir. Zemine doğrudan çizilir,
 *  bir kartın içine konmaz: hafta sayfanın kendisidir. */
/** @var array $rail @var array $days @var string $today @var bool $canCreate @var bool $canEdit */

$short = [1 => 'Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'];
$nowAt = $rail['days'][$today]['nowAt'] ?? null;

// Cetvel iptalleri çizmediği için "boş hafta" sayımı $total'dan gelemez:
// yalnız iptal edilmiş randevusu olan bir hafta dolu sayılırdı.
$drawn     = array_sum(array_map(static fn (array $d): int => count($d['slots']), $rail['days']));
$cancelled = $total - $drawn;
?>

<div class="cal-scroll">
  <div class="cal-min">

    <div class="cal-head">
      <?php foreach (array_keys($rail['days']) as $date): ?>
        <?php $day = new DateTimeImmutable($date); ?>
        <div class="cal-head-day <?= $date === $today ? 'is-today' : '' ?>">
          <span>
            <?= e($short[(int) $day->format('N')]) ?>
            <span class="num ml-0.5"><?= e($day->format('j')) ?></span>
          </span>
          <?php if ($canCreate): ?>
            <a href="<?= e(url('/randevular/yeni?tarih=' . $date)) ?>"
               class="cal-add" aria-label="<?= e(tr_date_label($date, false)) ?> için randevu ekle">+</a>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="rail rail-<?= (int) $rail['hours'] ?>h">
      <?php for ($h = 0; $h <= $rail['hours']; $h++): ?>
        <div class="rail-hour at-<?= $h * 12 ?>">
          <span><?= sprintf('%02d:00', $rail['startHour'] + $h) ?></span>
        </div>
      <?php endfor; ?>

      <?php // "Şimdi" çizgisi haftanın tamamını keser: pencere ortak olduğu için
            // konumu her sütunda aynı, ayrıca bugünün sütunu zaten işaretli. ?>
      <?php if ($nowAt !== null): ?>
        <div class="rail-now at-<?= (int) $nowAt ?>"><span><?= e(date('H:i')) ?></span></div>
      <?php endif; ?>

      <?php if ($drawn === 0): ?>
        <p class="rail-empty">Bu hafta randevu yok.</p>
      <?php endif; ?>

      <div class="cal-track">
        <?php foreach ($rail['days'] as $date => $day): ?>
          <div class="cal-col <?= $date === $today ? 'is-today' : '' ?>">
            <?php foreach ($day['slots'] as $slot): ?>
              <?php
              $row = $slot['row'];
              // Kutunun içine ne sığdığı seansın kendi süresinden gelir; 30
              // dakikalık bir seansa iki satır sığmaz, zorlamak taşırırdı.
              $dense = $slot['dur'] <= 8;
              $target = $canEdit
                  ? "/randevular/{$row['id']}/duzenle"
                  : "/danisanlar/{$row['client_id']}";
              ?>
              <a href="<?= e(url($target)) ?>"
                 class="rail-slot cal-slot at-<?= (int) $slot['at'] ?> dur-<?= (int) $slot['dur'] ?> lane-<?= (int) $slot['lane'] ?>-<?= (int) $slot['lanes'] ?> <?= $tone[$row['status']] ?? '' ?>"
                 title="<?= e($slot['start']->format('H:i') . '–' . $slot['end']->format('H:i') . ' · ' . $row['client_name'] . ' · ' . $row['therapist_name'] . ' · ' . Scheduling::statusLabel($row['status'])) ?>">
                <span class="rail-time num block"><?= e($slot['start']->format('H:i')) ?><span class="rail-end">–<?= e($slot['end']->format('H:i')) ?></span></span>
                <span class="rail-name block truncate"><?= e($row['client_name']) ?></span>
                <?php if (!$dense): ?>
                  <span class="rail-meta block truncate"><?= e($row['therapist_name']) ?></span>
                <?php endif; ?>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>
</div>

<?php // İptal saati gerçekten serbest bırakır, o yüzden takvimde yer kaplamaz.
      // Kaydın kendisi durur; nerede durduğu söylenmezse kaybolmuş görünürdü. ?>
<?php if ($cancelled > 0): ?>
  <p class="text-xs text-ink-light mt-3">
    Bu hafta iptal edilen <span class="num"><?= (int) $cancelled ?></span> randevu takvimde yer kaplamıyor;
    <a href="<?= e($link(['gorunum' => 'liste'])) ?>" class="btn-text">liste görünümünde</a> görebilirsiniz.
  </p>
<?php endif; ?>
