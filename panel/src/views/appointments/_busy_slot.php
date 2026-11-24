<?php
/** Cetveldeki özel zaman kutusu — hafta takvimi ve "Bugün" cetveli paylaşır.
 *  Sahibi notunu görür, diğerleri yalnız "Dolu" ve kimin saati olduğunu. */
/** @var array $slot @var array $row @var string $slotClass */

$busyTag   = $row['editable'] ? 'a' : 'div';
$busyRange = $slot['start']->format('H:i') . '–' . $slot['end']->format('H:i');
$busyNote  = (string) ($row['note'] ?? '');
?>
<<?= $busyTag ?> <?= $row['editable'] ? 'href="' . e(url("/randevular/ozel-zaman/{$row['id']}/duzenle")) . '"' : '' ?>
   class="rail-slot <?= e($slotClass) ?> is-busy at-<?= (int) $slot['at'] ?> dur-<?= (int) $slot['dur'] ?> lane-<?= (int) $slot['lane'] ?>-<?= (int) $slot['lanes'] ?>"
   title="<?= e($busyRange . ' · Dolu · ' . $row['therapist_name'] . ($busyNote !== '' ? ' · ' . $busyNote : '')) ?>">
  <?php if ($slot['dur'] <= 6): ?>
    <span class="flex items-baseline gap-1.5">
      <span class="rail-time num"><?= e($slot['start']->format('H:i')) ?></span>
      <span class="rail-name truncate">Dolu</span>
    </span>
  <?php else: ?>
    <span class="rail-time num block"><?= e($slot['start']->format('H:i')) ?><span class="rail-end">–<?= e($slot['end']->format('H:i')) ?></span></span>
    <span class="rail-name block truncate">Dolu</span>
    <?php if ($slot['dur'] > 8): ?>
      <span class="rail-meta block truncate"><?= e($busyNote !== '' ? $busyNote : $row['therapist_name']) ?></span>
    <?php endif; ?>
  <?php endif; ?>
</<?= $busyTag ?>>
