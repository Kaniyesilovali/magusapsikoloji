<?php
use Panel\Csrf;
use Panel\Rbac;
use Panel\Scheduling;
/** @var array $days @var DateTimeImmutable $weekStart @var int $total */
/** @var array $therapists @var int $therapistFilter @var array $actor */

$weekEnd  = $weekStart->modify('+6 days');
$filterQs = $therapistFilter > 0 ? '&terapist=' . $therapistFilter : '';
$today    = date('Y-m-d');

$canEdit   = Rbac::can($actor, 'appointment.update');
$canCancel = Rbac::can($actor, 'appointment.cancel');

$statusTone = [
    'scheduled' => 'bg-warm-secondary text-ink-muted',
    'confirmed' => 'bg-primary/10 text-primary-dark',
    'completed' => 'bg-sage/20 text-primary-dark',
    'cancelled' => 'bg-accent/10 text-accent-dark line-through',
    'no_show'   => 'bg-amber-100 text-amber-900',
];
?>

<header class="flex flex-wrap items-center justify-between gap-3 mb-6">
  <div>
    <h1 class="text-2xl font-semibold text-ink">Randevular</h1>
    <p class="text-sm text-ink-light mt-1">
      <?= e(tr_date_label($weekStart->format('Y-m-d'), false)) ?> – <?= e(tr_date_label($weekEnd->format('Y-m-d'), false)) ?>
      · <?= $total ?> randevu
    </p>
  </div>
  <?php if (Rbac::can($actor, 'appointment.create')): ?>
    <a href="<?= e(url('/randevular/yeni?tarih=' . $weekStart->format('Y-m-d'))) ?>"
       class="bg-primary hover:bg-primary-hover text-white text-sm font-medium px-4 py-2.5 rounded-xl transition-colors">
      Yeni randevu
    </a>
  <?php endif; ?>
</header>

<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
  <nav class="flex items-center gap-1 text-sm">
    <a href="<?= e(url('/randevular?hafta=' . $weekStart->modify('-7 days')->format('Y-m-d') . $filterQs)) ?>"
       class="px-3 py-2 rounded-xl bg-white border border-warm-tertiary text-ink-muted hover:text-ink">← Önceki</a>
    <a href="<?= e(url('/randevular?hafta=' . $today . $filterQs)) ?>"
       class="px-3 py-2 rounded-xl bg-white border border-warm-tertiary text-ink-muted hover:text-ink">Bu hafta</a>
    <a href="<?= e(url('/randevular?hafta=' . $weekStart->modify('+7 days')->format('Y-m-d') . $filterQs)) ?>"
       class="px-3 py-2 rounded-xl bg-white border border-warm-tertiary text-ink-muted hover:text-ink">Sonraki →</a>
  </nav>

  <?php if ($therapists !== []): ?>
    <form method="get" action="<?= e(url('/randevular')) ?>" class="flex items-center gap-2">
      <input type="hidden" name="hafta" value="<?= e($weekStart->format('Y-m-d')) ?>">
      <select name="terapist" class="px-3 py-2 rounded-xl border border-warm-tertiary bg-white text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
        <option value="">Tüm terapistler</option>
        <?php foreach ($therapists as $therapist): ?>
          <option value="<?= (int) $therapist['id'] ?>" <?= $therapistFilter === (int) $therapist['id'] ? 'selected' : '' ?>>
            <?= e($therapist['full_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button class="text-sm text-primary hover:text-primary-dark font-medium">Göster</button>
    </form>
  <?php endif; ?>
</div>

<div class="space-y-3">
  <?php foreach ($days as $date => $items): ?>
    <?php $isToday = $date === $today; ?>
    <section class="bg-white border rounded-2xl overflow-hidden <?= $isToday ? 'border-primary/40' : 'border-warm-tertiary' ?>">
      <div class="px-5 py-3 border-b <?= $isToday ? 'border-primary/25 bg-primary/5' : 'border-warm-tertiary bg-warm' ?> flex items-center justify-between">
        <h2 class="text-sm font-medium <?= $isToday ? 'text-primary-dark' : 'text-ink' ?>">
          <?= e(tr_date_label($date)) ?>
          <?php if ($isToday): ?><span class="text-xs font-normal">· bugün</span><?php endif; ?>
        </h2>
        <?php if (Rbac::can($actor, 'appointment.create')): ?>
          <a href="<?= e(url('/randevular/yeni?tarih=' . $date)) ?>" class="text-xs text-primary hover:text-primary-dark">+ ekle</a>
        <?php endif; ?>
      </div>

      <?php if ($items === []): ?>
        <p class="px-5 py-4 text-sm text-ink-light">Randevu yok.</p>
      <?php else: ?>
        <ul class="divide-y divide-warm-secondary">
          <?php foreach ($items as $appointment): ?>
            <?php
            $start = new DateTimeImmutable((string) $appointment['starts_at']);
            $end   = $start->modify('+' . (int) $appointment['duration_min'] . ' minutes');
            $tone  = $statusTone[$appointment['status']] ?? 'bg-warm-secondary text-ink-muted';
            ?>
            <li class="px-5 py-3.5">
              <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                <span class="text-sm font-medium text-ink tabular-nums w-28 shrink-0">
                  <?= e($start->format('H:i')) ?>–<?= e($end->format('H:i')) ?>
                </span>
                <span class="text-sm text-ink flex-1 min-w-40">
                  <a href="<?= e(url("/danisanlar/{$appointment['client_id']}")) ?>" class="hover:text-primary">
                    <?= e($appointment['client_name']) ?>
                  </a>
                  <?php if ($appointment['client_phone'] !== null): ?>
                    <span class="text-xs text-ink-light ml-1 tabular-nums"><?= e($appointment['client_phone']) ?></span>
                  <?php endif; ?>
                </span>
                <span class="text-xs text-ink-muted"><?= e($appointment['therapist_name']) ?></span>
                <span class="text-xs text-ink-light"><?= e(Scheduling::locationLabel($appointment['location'])) ?></span>
                <span class="text-xs px-2 py-0.5 rounded-full <?= $tone ?>"><?= e(Scheduling::statusLabel($appointment['status'])) ?></span>
              </div>

              <?php if ($appointment['note'] !== null): ?>
                <p class="text-xs text-ink-light mt-1.5 pl-0 sm:pl-32"><?= e($appointment['note']) ?></p>
              <?php endif; ?>
              <?php if ($appointment['status'] === 'cancelled' && $appointment['cancel_reason'] !== null): ?>
                <p class="text-xs text-accent-dark mt-1.5 pl-0 sm:pl-32">İptal gerekçesi: <?= e($appointment['cancel_reason']) ?></p>
              <?php endif; ?>

              <?php if ($canEdit && $appointment['status'] !== 'cancelled'): ?>
                <div class="flex flex-wrap items-center gap-2 mt-2 pl-0 sm:pl-32">
                  <a href="<?= e(url("/randevular/{$appointment['id']}/duzenle")) ?>" class="text-xs text-primary hover:text-primary-dark font-medium">Düzenle</a>

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
                        <button class="text-xs text-ink-muted hover:text-ink"><?= e($label) ?></button>
                      </form>
                    <?php endif; ?>
                  <?php endforeach; ?>

                  <?php if ($canCancel): ?>
                    <details class="inline-block">
                      <summary class="text-xs text-accent-dark hover:text-accent cursor-pointer">İptal et</summary>
                      <form method="post" action="<?= e(url("/randevular/{$appointment['id']}/iptal")) ?>"
                            class="mt-2 flex flex-wrap items-center gap-2">
                        <?= Csrf::field() ?>
                        <input type="text" name="cancel_reason" maxlength="255" placeholder="İptal gerekçesi (isteğe bağlı)"
                               class="px-3 py-1.5 rounded-lg border border-warm-tertiary bg-warm text-xs w-64 focus:bg-white focus:border-primary focus:outline-none">
                        <button class="text-xs text-white bg-accent-dark hover:bg-accent px-3 py-1.5 rounded-lg">Randevuyu iptal et</button>
                      </form>
                    </details>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  <?php endforeach; ?>
</div>
