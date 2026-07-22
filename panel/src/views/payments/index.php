<?php
use Panel\Money;
use Panel\Rbac;
/** @var array $rows @var array $totals @var DateTimeImmutable $from @var DateTimeImmutable $to */
/** @var array $nav @var string $status @var array $statusLabels */
/** @var array $therapists @var int $therapistFilter @var array $actor */

/** Dönem değişirken durum ve terapist filtreleri korunur. */
$link = static function (array $params) use ($status, $therapistFilter): string {
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

// Danışan bu listede yalnız kendi seanslarını görür: her satırda kendi adını
// tekrarlamak bilgi taşımaz, o yüzden "Danışan" sütunu ona çizilmez. Ad, danışan
// kaydına bağlantıdır ve danışanın o kaydı açma yetkisi de yok.
$isClient = ($actor['role'] ?? '') === Rbac::CLIENT;
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

<?php // ── Dönem ─────────────────────────────────────────────────────────────
      // Ödemelere neredeyse hep ay ay bakılıyor; aşağıdaki serbest aralık ise
      // istisna için duruyor. Bu yüzden iki ayrı denetim: buradaki düğme "şu
      // aya git" der, aşağıdaki "tam olarak bu aralığı göster". Tek forma
      // sıkıştırılsalardı hangisinin kazandığı gizli bir kural olurdu. ?>
<div class="flex flex-wrap items-center justify-between gap-3 mb-3">
  <div class="flex flex-wrap items-center gap-3">
    <nav class="flex items-center gap-1.5" aria-label="Dönem">
      <a href="<?= e($link($nav['prev'])) ?>" class="btn btn-quiet btn-sm">←&nbsp;Önceki</a>
      <a href="<?= e($link(['ay' => date('Y-m')])) ?>" class="btn btn-quiet btn-sm">Bu ay</a>
      <a href="<?= e($link($nav['next'])) ?>" class="btn btn-quiet btn-sm">Sonraki&nbsp;→</a>
    </nav>
    <?php // Serbest aralıkta okların ne yaptığı görünmüyor; söylenmezse
          // kullanıcının kurduğu aralığı bozdukları sanılır. ?>
    <?php if (!$nav['isMonth']): ?>
      <span class="text-xs text-ink-light">Oklar aralığı kendi uzunluğu kadar kaydırır.</span>
    <?php endif; ?>
  </div>

  <form method="get" action="<?= e(url('/odemeler')) ?>" class="flex flex-wrap items-center gap-2">
    <?php if ($status !== 'hepsi'): ?>
      <input type="hidden" name="durum" value="<?= e($status) ?>">
    <?php endif; ?>
    <?php if ($therapistFilter > 0): ?>
      <input type="hidden" name="terapist" value="<?= (int) $therapistFilter ?>">
    <?php endif; ?>
    <label for="ay" class="eyebrow">Ay</label>
    <select id="ay" name="ay" class="field w-auto py-1.5 text-[0.8125rem]">
      <?php foreach (tr_month_options($from, 'Y-m') as $value => $label): ?>
        <option value="<?= e($value) ?>" <?= $value === $from->format('Y-m') ? 'selected' : '' ?>>
          <?= e($label) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <button class="btn-text">Aya git</button>
  </form>
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
            <?php if (!$isClient): ?><th>Danışan</th><?php endif; ?>
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
