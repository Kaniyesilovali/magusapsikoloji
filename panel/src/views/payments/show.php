<?php
use Panel\Csrf;
use Panel\Money;
use Panel\Scheduling;
/** @var array $appointment @var array $payments @var string $status @var array $statusLabels */
/** @var array $methods @var bool $canManage @var bool $canSetFee @var array $actor */

$inputCls = 'w-full px-3 py-2.5 rounded-xl border border-warm-tertiary bg-warm focus:bg-white focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 text-sm';
$day      = substr((string) $appointment['starts_at'], 0, 10);

// Kalan tutar: ücret girilmemişse "kalan" kavramı da yok.
$remaining = $appointment['fee'] === null
    ? null
    : number_format(max(0, ((float) $appointment['fee']) - ((float) $appointment['paid'])), 2, '.', '');
?>

<header class="mb-6">
  <a href="<?= e(url('/odemeler')) ?>" class="text-sm text-ink-muted hover:text-ink">← Ödemeler</a>
  <h1 class="text-2xl font-semibold text-ink mt-2"><?= e($appointment['client_name']) ?></h1>
  <p class="text-sm text-ink-light mt-1">
    <?= e(tr_date_label($day)) ?> saat <?= e(dt($appointment['starts_at'], 'H:i')) ?> ·
    <?= e($appointment['therapist_name']) ?> ·
    <?= e(Scheduling::statusLabel($appointment['status'])) ?>
  </p>
</header>

<div class="grid sm:grid-cols-3 gap-3 mb-6">
  <div class="bg-white border border-warm-tertiary rounded-2xl p-4">
    <p class="text-xl font-semibold text-ink tabular-nums"><?= e(Money::format($appointment['fee'])) ?></p>
    <p class="text-xs text-ink-light mt-1">Seans ücreti</p>
  </div>
  <div class="bg-white border border-warm-tertiary rounded-2xl p-4">
    <p class="text-xl font-semibold text-primary-dark tabular-nums"><?= e(Money::format($appointment['paid'])) ?></p>
    <p class="text-xs text-ink-light mt-1">Tahsil edilen</p>
  </div>
  <div class="bg-white border border-warm-tertiary rounded-2xl p-4">
    <p class="text-xl font-semibold tabular-nums <?= $remaining !== null && Money::isPositive($remaining) ? 'text-accent-dark' : 'text-ink' ?>">
      <?= e($remaining === null ? '—' : Money::format($remaining)) ?>
    </p>
    <p class="text-xs text-ink-light mt-1">Kalan · <?= e($statusLabels[$status]) ?></p>
  </div>
</div>

<?php if ($canSetFee): ?>
  <section class="bg-white border border-warm-tertiary rounded-2xl p-5 mb-6">
    <h2 class="font-medium text-ink mb-3">Seans ücreti</h2>
    <form method="post" action="<?= e(url("/odemeler/{$appointment['id']}/ucret")) ?>" class="flex flex-wrap items-end gap-3">
      <?= Csrf::field() ?>
      <div class="w-48">
        <label for="fee" class="block text-xs text-ink-muted mb-1">Tutar (₺)</label>
        <input type="text" id="fee" name="fee" inputmode="decimal" class="<?= $inputCls ?>"
               value="<?= e($appointment['fee'] !== null ? number_format((float) $appointment['fee'], 2, ',', '.') : '') ?>">
      </div>
      <button class="bg-primary hover:bg-primary-hover text-white text-sm font-medium px-4 py-2.5 rounded-xl transition-colors">Kaydet</button>
      <p class="text-xs text-ink-light basis-full">
        Boş bırakılırsa ücret "belirlenmedi" olur — bu, 0,00 ₺ (ücretsiz seans) ile aynı şey değildir.
      </p>
    </form>
  </section>
<?php endif; ?>

<section class="bg-white border border-warm-tertiary rounded-2xl overflow-hidden mb-6">
  <div class="px-5 py-4 border-b border-warm-tertiary">
    <h2 class="font-medium text-ink">Tahsilatlar</h2>
  </div>

  <?php if ($payments === []): ?>
    <p class="px-5 py-8 text-sm text-ink-light text-center">Bu seans için tahsilat kaydı yok.</p>
  <?php else: ?>
    <ul class="divide-y divide-warm-secondary">
      <?php foreach ($payments as $payment): ?>
        <li class="px-5 py-3.5 flex flex-wrap items-center gap-x-4 gap-y-1">
          <span class="text-sm font-medium text-ink tabular-nums w-32 shrink-0"><?= e(Money::format($payment['amount'])) ?></span>
          <span class="text-xs text-ink-muted w-24"><?= e($methods[$payment['method']] ?? $payment['method']) ?></span>
          <span class="text-xs text-ink-light tabular-nums w-32"><?= e(dt($payment['paid_at'], 'd.m.Y')) ?></span>
          <span class="text-sm text-ink-muted flex-1 min-w-32"><?= e($payment['note'] ?? '') ?></span>
          <span class="text-xs text-ink-light"><?= e($payment['recorded_by'] ?? '—') ?></span>
          <?php if ($canManage): ?>
            <form method="post" action="<?= e(url("/odemeler/tahsilat/{$payment['id']}/sil")) ?>"
                  data-confirm="<?= e(Money::format($payment['amount'])) ?> tutarındaki tahsilat kaydı silinsin mi?">
              <?= Csrf::field() ?>
              <button class="text-xs text-accent-dark hover:text-accent">Sil</button>
            </form>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <?php if ($canManage): ?>
    <form method="post" action="<?= e(url("/odemeler/{$appointment['id']}/tahsilat")) ?>"
          class="px-5 py-4 border-t border-warm-tertiary bg-warm/50 flex flex-wrap items-end gap-3">
      <?= Csrf::field() ?>
      <div class="w-36">
        <label for="amount" class="block text-xs text-ink-muted mb-1">Tutar (₺)</label>
        <input type="text" id="amount" name="amount" inputmode="decimal" required class="<?= $inputCls ?> bg-white"
               value="<?= e($remaining !== null && Money::isPositive($remaining) ? number_format((float) $remaining, 2, ',', '.') : '') ?>">
      </div>
      <div class="w-36">
        <label for="method" class="block text-xs text-ink-muted mb-1">Yöntem</label>
        <select id="method" name="method" class="<?= $inputCls ?> bg-white">
          <?php foreach ($methods as $value => $label): ?>
            <option value="<?= e($value) ?>"><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="w-40">
        <label for="paid_date" class="block text-xs text-ink-muted mb-1">Tarih</label>
        <input type="date" id="paid_date" name="paid_date" required class="<?= $inputCls ?> bg-white" value="<?= e(date('Y-m-d')) ?>">
        <input type="hidden" name="paid_time" value="12:00">
      </div>
      <div class="flex-1 min-w-48">
        <label for="note" class="block text-xs text-ink-muted mb-1">Not (isteğe bağlı)</label>
        <input type="text" id="note" name="note" maxlength="255" class="<?= $inputCls ?> bg-white">
      </div>
      <button class="bg-primary hover:bg-primary-hover text-white text-sm font-medium px-4 py-2.5 rounded-xl transition-colors">
        Tahsilat ekle
      </button>
    </form>
  <?php else: ?>
    <p class="px-5 py-4 border-t border-warm-tertiary bg-warm/50 text-xs text-ink-light">
      Tahsilat kaydı merkez yönetimi tarafından girilir. Ücreti siz belirleyebilirsiniz.
    </p>
  <?php endif; ?>
</section>

<p class="text-xs text-ink-light">
  <a href="<?= e(url("/randevular?hafta={$day}")) ?>" class="text-primary hover:text-primary-dark">Randevuyu takvimde aç →</a>
</p>
