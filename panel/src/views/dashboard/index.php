<?php
use Panel\Rbac;
use Panel\Scheduling;
/** @var array $user @var array $stats @var array $today @var array $upcoming */
/** @var array $rail @var DateTimeImmutable $now */

$isStaff  = Rbac::can($user, 'appointment.view.all') || $user['role'] === Rbac::THERAPIST;
$canBook  = Rbac::can($user, 'appointment.create');
$seesAll  = Rbac::can($user, 'appointment.view.all');

// Günün özeti tek cümlede: kaç seans, ilki ne zaman, sonuncusu ne zaman biter.
// Bitiş en geç **biten** seanstan gelir, en geç başlayandan değil: 16:00'daki
// 90 dakikalık seans, 16:30'daki yarım saatlikten sonra biter.
$first = $today[0] ?? null;
$endsAt = null;
foreach ($rail['slots'] as $slot) {
    if ($endsAt === null || $slot['end'] > $endsAt) {
        $endsAt = $slot['end'];
    }
}

$tone = [
    'scheduled' => '',
    'confirmed' => 'is-confirmed',
    'completed' => 'is-done',
    'no_show'   => 'is-off',
];
$chip = [
    'scheduled' => 'chip-neutral',
    'confirmed' => 'chip-go',
    'completed' => 'chip-done',
    'no_show'   => 'chip-stop',
];
?>

<?php // Panelin en dar sayfası: gün sütunu sayfanın tamamına yayılmamalı. ?>
<div class="max-w-5xl">

<?php // Tur adımları 20'den başlar; 10–19 kenar çubuğunun (bkz. views/layout.php).
      // Metin anlattığı bölümün üzerinde duruyor ki bölüm değişince metin de
      // gözden kaçmasın. ?>
<header class="flex flex-wrap items-end justify-between gap-4 mb-7"
        data-tour="20" data-tour-title="Gün burada başlar"
        data-tour-text="Panel her girişte bu ekrana düşer: bugünün tarihi, kaç seans olduğu, ilk ve son saat. Tek satırda günün cevabı.">
  <div>
    <p class="eyebrow">Bugün</p>
    <h1 class="page-title mt-2"><?= e(tr_date_label($now->format('Y-m-d'))) ?></h1>
    <p class="page-sub">
      <?php if ($today === []): ?>
        <?= $isStaff ? 'Bugün için planlanmış seans yok.' : 'Bugün randevunuz yok.' ?>
      <?php else: ?>
        <span class="num"><?= count($today) ?></span> seans ·
        <span class="num"><?= e($first === null ? '' : (new DateTimeImmutable($first['starts_at']))->format('H:i')) ?></span>–<span
              class="num"><?= e($endsAt === null ? '' : $endsAt->format('H:i')) ?></span>
      <?php endif; ?>
    </p>
  </div>
  <?php if ($canBook): ?>
    <a href="<?= e(url('/randevular/yeni?tarih=' . $now->format('Y-m-d'))) ?>" class="btn btn-primary">Yeni randevu</a>
  <?php endif; ?>
</header>

<div class="<?= $isStaff ? 'lg:grid lg:grid-cols-[32rem_minmax(0,1fr)] lg:gap-10 lg:items-start' : '' ?>">

<?php if ($isStaff): ?>
  <?php // ── Gün cetveli ────────────────────────────────────────────────────
        // Seanslar süreleri kadar yer kaplar; aradaki boşluk gerçek boşluktur.
        // Zemine doğrudan çizilir, bir kartın içine konmaz: bugün sayfanın
        // kendisidir, sayfadaki kutulardan biri değil. Genişliği sınırlı çünkü
        // elli dakikalık bir seans sayfayı baştan başa geçen bir şerit değildir.
        ?>
  <section>
    <div class="flex items-center justify-between mb-4">
      <h2 class="eyebrow">Gün cetveli</h2>
      <a href="<?= e(url('/randevular')) ?>" class="btn-text">Haftanın tamamı →</a>
    </div>

    <div class="rail rail-<?= (int) $rail['hours'] ?>h"
         data-tour="21" data-tour-title="Gün cetveli"
         data-tour-text="Seanslar süreleri kadar yer kaplar; aradaki boşluk gerçek boşluktur. Çizgi şu anı gösterir, kutunun rengi randevunun durumunu. Bir seansa tıklamak bireyin kaydını açar.">
      <?php for ($h = 0; $h <= $rail['hours']; $h++): ?>
        <div class="rail-hour at-<?= $h * 12 ?>">
          <span><?= sprintf('%02d:00', $rail['startHour'] + $h) ?></span>
        </div>
      <?php endfor; ?>

      <?php if ($rail['nowAt'] !== null): ?>
        <div class="rail-now at-<?= (int) $rail['nowAt'] ?>">
          <span><?= e($now->format('H:i')) ?></span>
        </div>
      <?php endif; ?>

      <div class="rail-track">
        <?php if ($rail['slots'] === []): ?>
          <p class="rail-empty">
            Bugün seans yok.
            <?php if ($canBook): ?>
              <a href="<?= e(url('/randevular/yeni?tarih=' . $now->format('Y-m-d'))) ?>" class="btn-text ml-1">Randevu ekle</a>
            <?php endif; ?>
          </p>
        <?php endif; ?>

        <?php foreach ($rail['slots'] as $slot): ?>
          <?php
          $row = $slot['row'];
          // Yoğunluk seansın kendi süresinden gelir: 30 dakikalık bir seansa üç
          // satır sığmaz, sığdırmaya çalışmak da kutuyu süresinden büyük yapardı.
          $dense = $slot['dur'] <= 6;
          $room  = $slot['dur'] >= 10;
          ?>
          <a href="<?= e(url("/danisanlar/{$row['client_id']}")) ?>"
             class="rail-slot at-<?= (int) $slot['at'] ?> dur-<?= (int) $slot['dur'] ?> lane-<?= (int) $slot['lane'] ?>-<?= (int) $slot['lanes'] ?> <?= $tone[$row['status']] ?? '' ?>">
            <?php if ($dense): ?>
              <span class="flex items-baseline gap-2">
                <span class="rail-time num"><?= e($slot['start']->format('H:i')) ?></span>
                <span class="rail-name truncate"><?= e($row['client_name']) ?></span>
              </span>
            <?php else: ?>
              <span class="flex items-center justify-between gap-2">
                <span class="rail-time num"><?= e($slot['start']->format('H:i')) ?><span class="rail-end">–<?= e($slot['end']->format('H:i')) ?></span></span>
                <span class="chip <?= $chip[$row['status']] ?? 'chip-neutral' ?>"><?= e(Scheduling::statusLabel($row['status'])) ?></span>
              </span>
              <span class="rail-name block truncate"><?= e($row['client_name']) ?></span>
              <?php if ($room): ?>
                <span class="rail-meta block truncate">
                  <?= e(Scheduling::locationLabel($row['location'])) ?>
                  <?php if ($seesAll): ?> · <?= e($row['therapist_name']) ?><?php endif; ?>
                </span>
              <?php endif; ?>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php // ── Sonraki günler ─────────────────────────────────────────────────
      // Birey için tek liste bu; personel için cetvelin devamı. ?>
<div class="<?= $isStaff ? 'mt-8 lg:mt-0 lg:sticky lg:top-8' : '' ?>">
<section class="sheet"
         data-tour="22" data-tour-title="<?= e($isStaff ? 'Sonraki günler' : 'Randevularınız') ?>"
         data-tour-text="<?= e($isStaff
             ? 'Bugünden sonrası: birkaç günlük ileriye bakış. Haftanın tamamı için Randevular ekranına geçin.'
             : 'Merkez sizin için bir randevu girdiğinde burada görünür. Gün, saat ve görüşmenin nerede yapılacağı bu listede.') ?>">
  <div class="sheet-head">
    <h2 class="sheet-title"><?= $isStaff ? 'Sonraki günler' : 'Randevularım' ?></h2>
    <?php if ($isStaff): ?>
      <a href="<?= e(url('/randevular')) ?>" class="btn-text">Randevular →</a>
    <?php endif; ?>
  </div>

  <?php if ($upcoming === []): ?>
    <p class="sheet-empty">
      Planlanmış randevu yok.
      <?php if (!$isStaff): ?><br>Randevunuz merkez tarafından girildiğinde burada görünür.<?php endif; ?>
    </p>
  <?php else: ?>
    <ul>
      <?php foreach ($upcoming as $index => $appointment): ?>
        <li class="px-5 py-3 flex flex-wrap items-center gap-x-4 gap-y-1 <?= $index > 0 ? 'border-t border-warm-secondary' : '' ?>">
          <?php // Yıl yazılmıyor: bu liste birkaç gün ilerisini gösteriyor. ?>
          <span class="text-sm num shrink-0">
            <span class="text-ink-light"><?= e(dt($appointment['starts_at'], 'd.m')) ?></span>
            <span class="text-ink ml-1"><?= e(dt($appointment['starts_at'], 'H:i')) ?></span>
          </span>
          <span class="person text-sm flex-1 min-w-32">
            <?= e($isStaff ? $appointment['client_name'] : $appointment['therapist_name']) ?>
          </span>
          <span class="text-xs text-ink-light"><?= e(Scheduling::locationLabel($appointment['location'])) ?></span>
          <span class="chip <?= $chip[$appointment['status']] ?? 'chip-neutral' ?>">
            <?= e(Scheduling::statusLabel($appointment['status'])) ?>
          </span>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>

<?php if ($stats !== []): ?>
  <?php // Sayımlar en altta ve tek satırda: bunlar bir karar değiştirmiyor,
        // yalnız merkezin büyüklüğünü hatırlatıyor. ?>
  <dl class="mt-6 flex flex-wrap gap-x-8 gap-y-2 px-1">
    <?php foreach ($stats as $stat): ?>
      <div class="flex items-baseline gap-2">
        <dd class="text-sm font-medium text-ink num"><?= (int) $stat['value'] ?></dd>
        <dt class="eyebrow"><?= e($stat['label']) ?></dt>
      </div>
    <?php endforeach; ?>
  </dl>
<?php endif; ?>
</div>
</div>

</div>
