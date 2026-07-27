<?php
use Panel\Csrf;
use Panel\Money;
use Panel\Rbac;
use Panel\Scheduling;
/** @var array $appointment @var array $payments @var string $status @var array $statusLabels */
/** @var array $methods @var bool $canManage @var bool $canSetFee @var array $actor */

$day = substr((string) $appointment['starts_at'], 0, 10);

// Görüşmeci kendi seansına bakıyor: başlıkta kendi adı yerine seansın kendisi
// durur, tahsilatı kimin kaydettiği de gösterilmez — o merkezin iç bilgisi.
$isClient = ($actor['role'] ?? '') === Rbac::CLIENT;

// Kalan tutar: ücret girilmemişse "kalan" kavramı da yok.
$remaining = $appointment['fee'] === null
    ? null
    : number_format(max(0, ((float) $appointment['fee']) - ((float) $appointment['paid'])), 2, '.', '');
?>

<header class="mb-6">
  <a href="<?= e(url('/odemeler')) ?>" class="btn-text btn-text-quiet">← Ödemeler</a>
  <h1 class="page-title mt-2">
    <?= e($isClient ? tr_date_label($day) . ' seansı' : $appointment['client_name']) ?>
  </h1>
  <p class="page-sub">
    <?php if (!$isClient): ?><?= e(tr_date_label($day)) ?> <?php endif; ?>saat
    <span class="num"><?= e(dt($appointment['starts_at'], 'H:i')) ?></span> ·
    <?= e($appointment['therapist_name']) ?> ·
    <?= e(Scheduling::statusLabel($appointment['status'])) ?>
  </p>
</header>

<div class="grid sm:grid-cols-3 gap-3 mb-6">
  <div class="sheet">
    <div class="sheet-body">
      <p class="text-xl font-medium text-ink num"><?= e(Money::format($appointment['fee'])) ?></p>
      <p class="eyebrow mt-1">Seans ücreti</p>
    </div>
  </div>
  <div class="sheet">
    <div class="sheet-body">
      <p class="text-xl font-medium text-primary-dark num"><?= e(Money::format($appointment['paid'])) ?></p>
      <p class="eyebrow mt-1">Tahsil edilen</p>
    </div>
  </div>
  <div class="sheet">
    <div class="sheet-body">
      <p class="text-xl font-medium num <?= $remaining !== null && Money::isPositive($remaining) ? 'text-accent-dark' : 'text-ink' ?>">
        <?= e($remaining === null ? '—' : Money::format($remaining)) ?>
      </p>
      <p class="eyebrow mt-1">Kalan · <?= e($statusLabels[$status]) ?></p>
    </div>
  </div>
</div>

<?php if ($canSetFee): ?>
  <section class="sheet mb-6">
    <div class="sheet-head">
      <h2 class="sheet-title">Seans ücreti</h2>
    </div>
    <form method="post" action="<?= e(url("/odemeler/{$appointment['id']}/ucret")) ?>" class="sheet-body">
      <?= Csrf::field() ?>
      <div class="flex flex-wrap items-end gap-3">
        <div class="w-48">
          <label for="fee" class="field-label">Tutar (₺)</label>
          <input type="text" id="fee" name="fee" inputmode="decimal" class="field num"
                 value="<?= e($appointment['fee'] !== null ? number_format((float) $appointment['fee'], 2, ',', '.') : '') ?>">
        </div>
        <button class="btn btn-primary">Kaydet</button>
      </div>
      <p class="field-hint">
        Boş bırakılırsa ücret "belirlenmedi" olur — bu, 0,00 ₺ (ücretsiz seans) ile aynı şey değildir.
      </p>
    </form>
  </section>
<?php endif; ?>

<section class="sheet mb-6">
  <div class="sheet-head">
    <h2 class="sheet-title">Tahsilatlar</h2>
  </div>

  <?php if ($payments === []): ?>
    <p class="sheet-empty">Bu seans için tahsilat kaydı yok.</p>
  <?php else: ?>
    <ul>
      <?php foreach ($payments as $index => $payment): ?>
        <li class="px-5 py-3 flex flex-wrap items-center gap-x-4 gap-y-1 <?= $index > 0 ? 'border-t border-warm-secondary' : '' ?>">
          <span class="text-sm font-medium text-ink num w-32 shrink-0"><?= e(Money::format($payment['amount'])) ?></span>
          <span class="text-xs text-ink-muted w-24"><?= e($methods[$payment['method']] ?? $payment['method']) ?></span>
          <span class="text-xs text-ink-light num w-32"><?= e(dt($payment['paid_at'], 'd.m.Y')) ?></span>
          <span class="text-sm text-ink-muted flex-1 min-w-32"><?= e($payment['note'] ?? '') ?></span>
          <?php if (!$isClient): ?>
            <span class="text-xs text-ink-light"><?= e($payment['recorded_by'] ?? '—') ?></span>
          <?php endif; ?>
          <?php if ($canManage): ?>
            <form method="post" action="<?= e(url("/odemeler/tahsilat/{$payment['id']}/sil")) ?>"
                  data-confirm="<?= e(Money::format($payment['amount'])) ?> tutarındaki tahsilat kaydı silinsin mi?">
              <?= Csrf::field() ?>
              <button class="btn-text btn-text-quiet hover:text-accent-dark">Sil</button>
            </form>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <?php if ($canManage): ?>
    <form method="post" action="<?= e(url("/odemeler/{$appointment['id']}/tahsilat")) ?>"
          class="sheet-foot flex flex-wrap items-end gap-3">
      <?= Csrf::field() ?>
      <div class="w-36">
        <label for="amount" class="field-label">Tutar (₺)</label>
        <input type="text" id="amount" name="amount" inputmode="decimal" required
               class="field py-1.5 text-[0.8125rem] num"
               value="<?= e($remaining !== null && Money::isPositive($remaining) ? number_format((float) $remaining, 2, ',', '.') : '') ?>">
      </div>
      <div class="w-36">
        <label for="method" class="field-label">Yöntem</label>
        <select id="method" name="method" class="field py-1.5 text-[0.8125rem]">
          <?php foreach ($methods as $value => $label): ?>
            <option value="<?= e($value) ?>"><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="w-40">
        <label for="paid_date" class="field-label">Tarih</label>
        <input type="date" id="paid_date" name="paid_date" required class="field py-1.5 text-[0.8125rem] num"
               value="<?= e(date('Y-m-d')) ?>">
        <input type="hidden" name="paid_time" value="12:00">
      </div>
      <div class="flex-1 min-w-48">
        <label for="note" class="field-label">Not (isteğe bağlı)</label>
        <input type="text" id="note" name="note" maxlength="255" class="field py-1.5 text-[0.8125rem]">
      </div>
      <button class="btn btn-primary btn-sm">Tahsilat ekle</button>
    </form>
  <?php else: ?>
    <p class="sheet-foot text-xs text-ink-light">
      <?= $isClient
          ? 'Ödemeleriniz merkez tarafından kaydedilir; burada göründükleri hâl kayıtlardaki hâlleridir. Eksik ya da hatalı gördüğünüz bir tutar varsa merkeze bildirin.'
          : 'Tahsilat kaydı merkez yönetimi tarafından girilir. Ücreti siz belirleyebilirsiniz.' ?>
    </p>
  <?php endif; ?>
</section>

<p>
  <a href="<?= e(url("/randevular?hafta={$day}")) ?>" class="btn-text">Randevuyu takvimde aç →</a>
</p>
