<?php
use Panel\Money;
use Panel\Scheduling;
/** @var DateTimeImmutable $month @var array $revenue @var array $appointments */
/** @var list<array> $utilisation @var list<array> $therapists @var ?int $therapistId */

// Üç soru, üç bölüm: bu ay ne kazandık, kaç seans boşa gitti, terapistler ne
// kadar doldu. Dördüncü bir kutu eklemek, üçünden birine bakılmamasına yol açar.
$saat = static fn (int $dakika): string => number_format($dakika / 60, 1, ',', '.');
?>

<header class="flex flex-wrap items-end justify-between gap-4 mb-6">
  <div>
    <p class="eyebrow">Yönetim</p>
    <h1 class="page-title mt-2"><?= e(tr_month_name((int) $month->format('n')) . ' ' . $month->format('Y')) ?></h1>
    <p class="page-sub">Gelir, gelmeme oranı ve terapist doluluğu.</p>
  </div>

  <form method="get" action="<?= e(url('/raporlar')) ?>" data-autosubmit class="flex flex-wrap items-center gap-2">
    <label for="ay" class="sr-only">Ay</label>
    <select id="ay" name="ay" class="field w-auto py-1.5 text-[0.8125rem]">
      <?php foreach (tr_month_options($month, 'Y-m') as $value => $label): ?>
        <option value="<?= e($value) ?>" <?= $value === $month->format('Y-m') ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>

    <?php if ($therapists !== []): ?>
      <label for="terapist" class="eyebrow ml-1">Terapist</label>
      <select id="terapist" name="terapist" class="field w-auto py-1.5 text-[0.8125rem]">
        <option value="">Tümü</option>
        <?php foreach ($therapists as $therapist): ?>
          <option value="<?= (int) $therapist['id'] ?>" <?= $therapistId === (int) $therapist['id'] ? 'selected' : '' ?>>
            <?= e($therapist['full_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    <?php endif; ?>

    <button class="btn-text" data-no-js>Göster</button>
  </form>
</header>

<section class="sheet mb-6">
  <div class="sheet-head"><h2 class="sheet-title">Gelir</h2></div>
  <div class="grid gap-px bg-warm-secondary sm:grid-cols-3">
    <div class="bg-white px-5 py-4">
      <p class="eyebrow">Toplam ücret</p>
      <p class="num text-xl text-ink mt-1"><?= e(Money::format($revenue['fee'])) ?></p>
      <p class="text-xs text-ink-light mt-1"><span class="num"><?= (int) $revenue['sessions'] ?></span> seans</p>
    </div>
    <div class="bg-white px-5 py-4">
      <p class="eyebrow">Tahsil edilen</p>
      <p class="num text-xl text-primary mt-1"><?= e(Money::format($revenue['paid'])) ?></p>
    </div>
    <div class="bg-white px-5 py-4">
      <p class="eyebrow">Bekleyen</p>
      <p class="num text-xl text-accent-dark mt-1"><?= e(Money::format($revenue['outstanding'])) ?></p>
      <p class="text-xs text-ink-light mt-1">İptal edilen seanslar borç sayılmaz.</p>
    </div>
  </div>
</section>

<section class="sheet mb-6">
  <div class="sheet-head">
    <div>
      <h2 class="sheet-title">Randevular</h2>
      <p class="text-xs text-ink-light mt-1">
        Gelmeme oranının paydası <strong>tamamlanan + gelmeyen</strong>. İptal, gelmeme
        değildir — önceden haber verilmiş bir değişikliktir; paydaya katmak oranı sulandırırdı.
      </p>
    </div>
    <?php if ($appointments['noShowRate'] !== null): ?>
      <span class="chip <?= $appointments['noShowRate'] >= 20 ? 'chip-stop' : 'chip-go' ?>">
        <span class="num">%<?= e(number_format($appointments['noShowRate'], 1, ',', '.')) ?></span> gelmedi
      </span>
    <?php endif; ?>
  </div>

  <?php if ($appointments['total'] === 0): ?>
    <p class="sheet-empty">Bu ay randevu yok.</p>
  <?php else: ?>
    <ul class="divide-y divide-warm-secondary">
      <?php foreach (['completed', 'no_show', 'confirmed', 'scheduled', 'cancelled'] as $status): ?>
        <li class="px-5 py-3 flex items-center gap-4">
          <span class="text-sm text-ink w-40 shrink-0"><?= e(Scheduling::statusLabel($status)) ?></span>
          <span class="num text-sm text-ink"><?= (int) ($appointments['counts'][$status] ?? 0) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
    <div class="sheet-foot">
      <p class="text-xs text-ink-muted">
        Toplam <span class="num"><?= (int) $appointments['total'] ?></span> randevu;
        sonucu belli olan <span class="num"><?= (int) $appointments['settled'] ?></span> tanesi orana giriyor.
        <?php if ($appointments['noShowRate'] === null): ?>
          Henüz sonucu belli olan seans yok, oran hesaplanmadı.
        <?php endif; ?>
      </p>
    </div>
  <?php endif; ?>
</section>

<section class="sheet">
  <div class="sheet-head">
    <div>
      <h2 class="sheet-title">Doluluk</h2>
      <p class="text-xs text-ink-light mt-1">
        Dolu saat / çalışma şablonundaki açık saat. İzinler kapasiteden düşülür.
      </p>
    </div>
  </div>

  <?php if ($utilisation === []): ?>
    <p class="sheet-empty">Aktif terapist yok.</p>
  <?php else: ?>
    <ul class="divide-y divide-warm-secondary">
      <?php foreach ($utilisation as $row): ?>
        <li class="px-5 py-3 flex flex-wrap items-center gap-x-4 gap-y-1">
          <span class="person text-sm text-ink w-48 shrink-0"><?= e($row['name']) ?></span>

          <?php if ($row['rate'] === null): ?>
            <?php // Şablonsuz terapistte oran uydurulmuyor: "her saat uygun"
                  // sonsuz kapasite demek ve payda tanımsız kalıyor. ?>
            <span class="chip chip-neutral">şablon yok</span>
            <span class="text-xs text-ink-light flex-1 min-w-48">
              Müsaitlik ekranından çalışma saatleri girilmeden doluluk hesaplanamaz.
            </span>
          <?php else: ?>
            <span class="chip <?= $row['rate'] >= 80 ? 'chip-stop' : 'chip-go' ?>">
              <span class="num">%<?= e(number_format($row['rate'], 1, ',', '.')) ?></span>
            </span>
            <span class="text-xs text-ink-light flex-1 min-w-48 num">
              <?= e($saat($row['booked'])) ?> / <?= e($saat((int) $row['capacity'])) ?> saat
              — <?= (int) $row['sessions'] ?> seans
            </span>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
    <div class="sheet-foot">
      <p class="text-xs text-ink-muted">
        %80 üstü doluluk uyarı rengiyle gösteriliyor; bu bir hedef değil, ara verecek
        yer kalmadığının işareti.
      </p>
    </div>
  <?php endif; ?>
</section>
