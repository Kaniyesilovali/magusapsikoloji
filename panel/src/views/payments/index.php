<?php
use Panel\Money;
use Panel\Rbac;
/** @var array $rows @var array $totals @var DateTimeImmutable $from @var DateTimeImmutable $to */
/** @var string $status @var array $statusLabels @var array $therapists @var int $therapistFilter @var array $actor */

// "Kısmi" de "ödenmedi" gibi bir bakiye demek — ikisi de insan kararı bekliyor,
// bu yüzden aynı rozeti taşırlar; farkı metin söyler.
$statusChip = [
    'belirsiz' => 'chip-neutral',
    'odenmedi' => 'chip-stop',
    'kismi'    => 'chip-stop',
    'odendi'   => 'chip-go',
];
?>

<header class="mb-6">
  <p class="eyebrow">Ödemeler</p>
  <h1 class="page-title mt-2">
    <?= e(tr_range_label($from->format('Y-m-d'), $to->format('Y-m-d'))) ?>
  </h1>
  <p class="page-sub">
    <span class="num"><?= (int) $totals['count'] ?></span> seans
    <?php if (!Rbac::can($actor, 'payment.view.all')): ?> — yalnız kendi randevularınız<?php endif; ?>
  </p>
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

<form method="get" action="<?= e(url('/odemeler')) ?>" class="mb-4 flex flex-wrap items-end gap-2">
  <div>
    <label for="baslangic" class="field-label">Başlangıç</label>
    <input type="date" id="baslangic" name="baslangic" value="<?= e($from->format('Y-m-d')) ?>"
           class="field w-auto py-1.5 text-[0.8125rem] num">
  </div>
  <div>
    <label for="bitis" class="field-label">Bitiş</label>
    <input type="date" id="bitis" name="bitis" value="<?= e($to->format('Y-m-d')) ?>"
           class="field w-auto py-1.5 text-[0.8125rem] num">
  </div>
  <div>
    <label for="durum" class="field-label">Durum</label>
    <select id="durum" name="durum" class="field w-auto py-1.5 text-[0.8125rem]">
      <option value="hepsi">Tümü</option>
      <?php foreach ($statusLabels as $key => $label): ?>
        <option value="<?= e($key) ?>" <?= $status === $key ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <?php if ($therapists !== []): ?>
    <div>
      <label for="terapist" class="field-label">Terapist</label>
      <select id="terapist" name="terapist" class="field w-auto py-1.5 text-[0.8125rem]">
        <option value="">Tümü</option>
        <?php foreach ($therapists as $therapist): ?>
          <option value="<?= (int) $therapist['id'] ?>" <?= $therapistFilter === (int) $therapist['id'] ? 'selected' : '' ?>>
            <?= e($therapist['full_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  <?php endif; ?>
  <button class="btn btn-quiet btn-sm">Göster</button>
</form>

<div class="sheet">
  <?php if ($rows === []): ?>
    <p class="sheet-empty">Bu aralıkta kayıt yok.</p>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="tbl">
        <thead>
          <tr>
            <th>Tarih</th>
            <th>Danışan</th>
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
              <td class="text-xs text-ink-muted num whitespace-nowrap"><?= e(dt($row['starts_at'])) ?></td>
              <td>
                <a href="<?= e(url("/danisanlar/{$row['client_id']}")) ?>" class="person text-ink hover:text-primary">
                  <?= e($row['client_name']) ?>
                </a>
                <?php if ($row['status'] === 'cancelled'): ?>
                  <span class="chip chip-done ml-1.5">İptal</span>
                <?php endif; ?>
              </td>
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
