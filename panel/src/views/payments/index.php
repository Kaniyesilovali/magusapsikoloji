<?php
use Panel\Money;
use Panel\Rbac;
/** @var array $rows @var array $totals @var DateTimeImmutable $from @var DateTimeImmutable $to */
/** @var string $status @var array $statusLabels @var array $therapists @var int $therapistFilter @var array $actor */

$inputCls = 'px-3 py-2 rounded-xl border border-warm-tertiary bg-white text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20';

$statusTone = [
    'belirsiz' => 'bg-warm-secondary text-ink-muted',
    'odenmedi' => 'bg-accent/10 text-accent-dark',
    'kismi'    => 'bg-amber-100 text-amber-900',
    'odendi'   => 'bg-primary/10 text-primary-dark',
];
?>

<header class="mb-6">
  <h1 class="text-2xl font-semibold text-ink">Ödemeler</h1>
  <p class="text-sm text-ink-light mt-1">
    <?= e(tr_date_label($from->format('Y-m-d'), false)) ?> – <?= e(tr_date_label($to->format('Y-m-d'), false)) ?>
    · <?= (int) $totals['count'] ?> seans
    <?php if (!Rbac::can($actor, 'payment.view.all')): ?> — yalnız kendi randevularınız<?php endif; ?>
  </p>
</header>

<div class="grid grid-cols-2 lg:grid-cols-3 gap-3 mb-6">
  <div class="bg-white border border-warm-tertiary rounded-2xl p-4">
    <p class="text-xl font-semibold text-ink tabular-nums"><?= e(Money::format($totals['fee'])) ?></p>
    <p class="text-xs text-ink-light mt-1">Toplam ücret</p>
  </div>
  <div class="bg-white border border-warm-tertiary rounded-2xl p-4">
    <p class="text-xl font-semibold text-primary-dark tabular-nums"><?= e(Money::format($totals['paid'])) ?></p>
    <p class="text-xs text-ink-light mt-1">Tahsil edilen</p>
  </div>
  <div class="bg-white border border-warm-tertiary rounded-2xl p-4">
    <p class="text-xl font-semibold <?= Money::isPositive($totals['outstanding']) ? 'text-accent-dark' : 'text-ink' ?> tabular-nums">
      <?= e(Money::format($totals['outstanding'])) ?>
    </p>
    <p class="text-xs text-ink-light mt-1">Bekleyen (iptaller hariç)</p>
  </div>
</div>

<form method="get" action="<?= e(url('/odemeler')) ?>" class="mb-4 flex flex-wrap items-end gap-2">
  <div>
    <label for="baslangic" class="block text-xs text-ink-muted mb-1">Başlangıç</label>
    <input type="date" id="baslangic" name="baslangic" value="<?= e($from->format('Y-m-d')) ?>" class="<?= $inputCls ?>">
  </div>
  <div>
    <label for="bitis" class="block text-xs text-ink-muted mb-1">Bitiş</label>
    <input type="date" id="bitis" name="bitis" value="<?= e($to->format('Y-m-d')) ?>" class="<?= $inputCls ?>">
  </div>
  <div>
    <label for="durum" class="block text-xs text-ink-muted mb-1">Durum</label>
    <select id="durum" name="durum" class="<?= $inputCls ?>">
      <option value="hepsi">Tümü</option>
      <?php foreach ($statusLabels as $key => $label): ?>
        <option value="<?= e($key) ?>" <?= $status === $key ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <?php if ($therapists !== []): ?>
    <div>
      <label for="terapist" class="block text-xs text-ink-muted mb-1">Terapist</label>
      <select id="terapist" name="terapist" class="<?= $inputCls ?>">
        <option value="">Tümü</option>
        <?php foreach ($therapists as $therapist): ?>
          <option value="<?= (int) $therapist['id'] ?>" <?= $therapistFilter === (int) $therapist['id'] ? 'selected' : '' ?>>
            <?= e($therapist['full_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  <?php endif; ?>
  <button class="bg-primary hover:bg-primary-hover text-white text-sm font-medium px-4 py-2 rounded-xl transition-colors">Göster</button>
</form>

<div class="bg-white border border-warm-tertiary rounded-2xl overflow-hidden">
  <?php if ($rows === []): ?>
    <p class="px-5 py-10 text-sm text-ink-light text-center">Bu aralıkta kayıt yok.</p>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-warm text-ink-muted text-xs uppercase tracking-wide">
          <tr>
            <th class="text-left font-medium px-5 py-3">Tarih</th>
            <th class="text-left font-medium px-5 py-3">Danışan</th>
            <th class="text-left font-medium px-5 py-3">Terapist</th>
            <th class="text-right font-medium px-5 py-3">Ücret</th>
            <th class="text-right font-medium px-5 py-3">Tahsil</th>
            <th class="text-left font-medium px-5 py-3">Durum</th>
            <th class="px-5 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-warm-secondary">
          <?php foreach ($rows as $row): ?>
            <tr class="hover:bg-warm/60">
              <td class="px-5 py-3 text-ink-muted text-xs tabular-nums whitespace-nowrap"><?= e(dt($row['starts_at'])) ?></td>
              <td class="px-5 py-3">
                <a href="<?= e(url("/danisanlar/{$row['client_id']}")) ?>" class="text-ink hover:text-primary"><?= e($row['client_name']) ?></a>
                <?php if ($row['status'] === 'cancelled'): ?>
                  <span class="text-xs px-2 py-0.5 rounded-full bg-warm-secondary text-ink-muted ml-1">İptal</span>
                <?php endif; ?>
              </td>
              <td class="px-5 py-3 text-ink-muted"><?= e($row['therapist_name']) ?></td>
              <td class="px-5 py-3 text-right tabular-nums text-ink"><?= e(Money::format($row['fee'])) ?></td>
              <td class="px-5 py-3 text-right tabular-nums text-ink-muted"><?= e(Money::format($row['paid'])) ?></td>
              <td class="px-5 py-3">
                <span class="text-xs px-2 py-0.5 rounded-full <?= $statusTone[$row['payment_status']] ?>">
                  <?= e($statusLabels[$row['payment_status']]) ?>
                </span>
              </td>
              <td class="px-5 py-3 text-right">
                <a href="<?= e(url("/odemeler/{$row['id']}")) ?>" class="text-primary hover:text-primary-dark text-xs font-medium">Aç</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
