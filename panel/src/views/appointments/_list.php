<?php
use Panel\Csrf;
use Panel\Scheduling;
/** Liste — satır içi eylemlerin (onayla / tamamlandı / gelmedi / iptal) bulunduğu
 *  tek görünüm. Takvim kutusuna bir eylem çubuğu sığmadığı için kaldırılmadı.
 *  Hafta tek bir belge: yedi ayrı kart yerine gün başlıklarıyla bölünmüş
 *  sürekli bir liste. Günden güne göz kaydırmak böyle daha kolay. */
/** @var array $days @var string $today */
?>

<div class="sheet">
  <?php foreach ($days as $date => $items): ?>
    <?php $isToday = $date === $today; ?>

    <div class="flex items-center justify-between gap-3 px-5 py-2.5 border-b border-warm-tertiary <?= $isToday ? 'bg-primary-soft' : 'bg-warm' ?>">
      <h2 class="text-[0.8125rem] font-medium <?= $isToday ? 'text-primary-dark' : 'text-ink-muted' ?>">
        <?= e(tr_date_label($date, true, false)) ?>
        <?php if ($isToday): ?><span class="eyebrow ml-1.5 text-primary">Bugün</span><?php endif; ?>
      </h2>
      <?php if ($canCreate): ?>
        <a href="<?= e(url('/randevular/yeni?tarih=' . $date)) ?>" class="btn-text btn-text-quiet">Ekle</a>
      <?php endif; ?>
    </div>

    <?php if ($items === []): ?>
      <p class="px-5 py-3 text-sm text-ink-light border-b border-warm-tertiary">Randevu yok.</p>
    <?php else: ?>
      <ul class="border-b border-warm-tertiary">
        <?php foreach ($items as $index => $appointment): ?>
          <?php
          $start = new DateTimeImmutable((string) $appointment['starts_at']);
          $end   = $start->modify('+' . (int) $appointment['duration_min'] . ' minutes');
          $off   = in_array($appointment['status'], ['cancelled', 'no_show'], true);
          ?>
          <li class="px-5 py-3 <?= $index > 0 ? 'border-t border-warm-secondary' : '' ?>">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
              <span class="text-sm num w-28 shrink-0 <?= $off ? 'text-ink-light line-through' : 'text-ink' ?>">
                <?= e($start->format('H:i')) ?>–<?= e($end->format('H:i')) ?>
              </span>
              <span class="flex-1 min-w-40">
                <a href="<?= e(url("/danisanlar/{$appointment['client_id']}")) ?>" class="person text-sm text-ink hover:text-primary">
                  <?= e($appointment['client_name']) ?>
                </a>
                <?php if ($appointment['client_phone'] !== null): ?>
                  <span class="text-xs text-ink-light ml-1.5 num"><?= e($appointment['client_phone']) ?></span>
                <?php endif; ?>
              </span>
              <span class="text-xs text-ink-muted"><?= e($appointment['therapist_name']) ?></span>
              <span class="text-xs text-ink-light"><?= e(Scheduling::locationLabel($appointment['location'])) ?></span>
              <span class="chip <?= $chip[$appointment['status']] ?? 'chip-neutral' ?>"><?= e(Scheduling::statusLabel($appointment['status'])) ?></span>
            </div>

            <?php if ($appointment['note'] !== null): ?>
              <p class="text-xs text-ink-light mt-1.5 sm:pl-32"><?= e($appointment['note']) ?></p>
            <?php endif; ?>
            <?php if ($appointment['status'] === 'cancelled' && $appointment['cancel_reason'] !== null): ?>
              <p class="text-xs text-ink-muted mt-1.5 sm:pl-32">İptal gerekçesi: <?= e($appointment['cancel_reason']) ?></p>
            <?php endif; ?>

            <?php if ($canEdit && $appointment['status'] !== 'cancelled'): ?>
              <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mt-2 sm:pl-32">
                <a href="<?= e(url("/randevular/{$appointment['id']}/duzenle")) ?>" class="btn-text btn-text-quiet">Düzenle</a>

                <?php // Seans notu yalnız randevunun kendi terapistine görünür. ?>
                <?php if ($canWriteNotes && (int) $appointment['therapist_id'] === (int) $actor['id']): ?>
                  <a href="<?= e(url("/randevular/{$appointment['id']}/not")) ?>" class="btn-text btn-text-quiet">
                    <?= $appointment['has_note'] ? 'Seans notu ✓' : 'Seans notu' ?>
                  </a>
                <?php endif; ?>

                <?php
                $quick = [
                    'confirmed' => 'Onayla',
                    'completed' => 'Tamamlandı',
                    'no_show'   => 'Gelmedi',
                ];
                ?>
                <?php foreach ($quick as $status => $label): ?>
                  <?php if ($appointment['status'] !== $status): ?>
                    <form method="post" action="<?= e(url("/randevular/{$appointment['id']}/durum")) ?>">
                      <?= Csrf::field() ?>
                      <input type="hidden" name="status" value="<?= e($status) ?>">
                      <button class="btn-text btn-text-quiet"><?= e($label) ?></button>
                    </form>
                  <?php endif; ?>
                <?php endforeach; ?>

                <?php if ($canCancel): ?>
                  <details class="inline-block">
                    <summary class="btn-text btn-text-quiet hover:text-accent-dark">İptal et</summary>
                    <form method="post" action="<?= e(url("/randevular/{$appointment['id']}/iptal")) ?>"
                          class="mt-2 flex flex-wrap items-center gap-2">
                      <?= Csrf::field() ?>
                      <input type="text" name="cancel_reason" maxlength="255" placeholder="İptal gerekçesi (isteğe bağlı)"
                             class="field w-64 py-1.5 text-xs">
                      <label class="flex items-center gap-1.5 text-xs text-ink-muted cursor-pointer">
                        <input type="checkbox" name="notify" value="1" class="w-3.5 h-3.5 accent-primary" checked>
                        bildir
                      </label>
                      <button class="btn btn-danger btn-sm">Randevuyu iptal et</button>
                    </form>
                  </details>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  <?php endforeach; ?>
</div>
