<?php
use Panel\Scheduling;
/** Ay ızgarası — ölçek burada bırakılır. Otuz günü zamana orantılı çizmek
 *  okunmaz olurdu; ay görünümü "hangi gün ne kadar dolu" sorusunu yanıtlar,
 *  "saat 14:00'te boşum" sorusunu değil. Onun yeri hafta görünümü. */
/** @var array $days @var DateTimeImmutable $monthStart @var string $today */

$monthKey = $monthStart->format('Y-m');
?>

<div class="cal-scroll">
  <div class="sheet cal-min">

    <div class="cal-month-head">
      <?php foreach (Scheduling::WEEKDAYS as $name): ?>
        <span><?= e($name) ?></span>
      <?php endforeach; ?>
    </div>

    <div class="cal-month">
      <?php foreach ($days as $date => $items): ?>
        <?php $out = substr($date, 0, 7) !== $monthKey; ?>
        <div class="cal-cell <?= $out ? 'is-out' : '' ?> <?= $date === $today ? 'is-today' : '' ?>">
          <div class="flex items-baseline justify-between gap-2">
            <span class="cal-daynum num"><?= e((new DateTimeImmutable($date))->format('j')) ?></span>
            <?php if ($canCreate): ?>
              <a href="<?= e(url('/randevular/yeni?tarih=' . $date)) ?>"
                 class="cal-add" aria-label="<?= e(tr_date_label($date, false)) ?> için randevu ekle">+</a>
            <?php endif; ?>
          </div>

          <?php foreach ($items as $appointment): ?>
            <?php $target = $canEdit
                ? "/randevular/{$appointment['id']}/duzenle"
                : "/danisanlar/{$appointment['client_id']}"; ?>
            <a href="<?= e(url($target)) ?>"
               class="cal-item <?= $tone[$appointment['status']] ?? '' ?>"
               title="<?= e(dt($appointment['starts_at'], 'H:i') . ' · ' . $appointment['client_name'] . ' · ' . $appointment['therapist_name'] . ' · ' . Scheduling::statusLabel($appointment['status'])) ?>">
              <span class="cal-item-time num"><?= e(dt($appointment['starts_at'], 'H:i')) ?></span>
              <span class="cal-item-name"><?= e($appointment['client_name']) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>

  </div>
</div>
