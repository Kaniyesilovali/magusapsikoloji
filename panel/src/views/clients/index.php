<?php
use Panel\Rbac;
/** @var array $clients @var string $search @var string $status @var array $actor */
/** @var array{active:int,archived:int,unconsented:int} $tally */

// Başlık üstü satırı merkezin durumunu söyler, listenin filtresini değil:
// "onamı eksik 2" sayfayı açan kişiye bugün yapılacak bir şey veriyor.
// Alt satır o an neye bakıldığını söylüyor — ikisi ayrı iş.
$statusLine = match ($status) {
    'archived' => 'Arşivlenmiş kayıtlar',
    'all'      => 'Aktif ve arşiv birlikte',
    default    => 'Aktif kayıtlar',
};
?>

<?php // Tur adımları 20'den başlar; 10–19 kenar çubuğunun (bkz. views/layout.php). ?>
<header class="flex flex-wrap items-end justify-between gap-4 mb-6"
        data-tour="20" data-tour-title="Merkezin durumu"
        data-tour-text="Üstteki satır listenin değil merkezin sayısıdır: kaç aktif kayıt, kaç arşiv, onamı eksik kaç kişi. Altındaki satır o an neye baktığınızı söyler.">
  <div>
    <p class="eyebrow">
      <span class="num"><?= $tally['active'] ?></span> aktif
      <?php if ($tally['archived'] > 0): ?>
        · <span class="num"><?= $tally['archived'] ?></span> arşiv
      <?php endif; ?>
      <?php if ($tally['unconsented'] > 0): ?>
        · onamı eksik <span class="num"><?= $tally['unconsented'] ?></span>
      <?php endif; ?>
    </p>
    <h1 class="page-title mt-2">Görüşmeciler</h1>
    <p class="page-sub">
      <?= e($statusLine) ?>
      <?php if ($search !== ''): ?>
        — “<?= e($search) ?>” için <span class="num"><?= count($clients) ?></span> sonuç
      <?php else: ?>
        — <span class="num"><?= count($clients) ?></span> kayıt
      <?php endif; ?>
      <?php if (!Rbac::can($actor, 'client.view.all')): ?> · yalnız kendi görüşmecileriniz<?php endif; ?>
    </p>
  </div>
  <?php if (Rbac::can($actor, 'client.create')): ?>
    <a href="<?= e(url('/danisanlar/yeni')) ?>" class="btn btn-primary">Yeni görüşmeci</a>
  <?php endif; ?>
</header>

<form method="get" action="<?= e(url('/danisanlar')) ?>" class="mb-4 flex flex-wrap items-center gap-2"
      data-tour="21" data-tour-title="Arama ve arşiv"
      data-tour-text="Ad, telefon ya da e-postanın bir parçası yeter. Arşivlenmiş bir kayıt aradığınızda Durum'u Arşiv ya da Tümü yapın — liste kendiliğinden yalnız aktif kayıtları gösterir.">
  <?php // Arama kutusunun yer tutucusu kendini anlatıyor; etiket ekran okuyucu için. ?>
  <label for="q" class="sr-only">Ara</label>
  <input type="search" id="q" name="q" value="<?= e($search) ?>" placeholder="Ad, telefon veya e-posta…"
         class="field w-full sm:w-72 py-1.5 text-[0.8125rem]">
  <label for="durum" class="eyebrow">Durum</label>
  <select id="durum" name="durum" class="field w-auto py-1.5 text-[0.8125rem]">
    <option value="active"   <?= $status === 'active'   ? 'selected' : '' ?>>Aktif</option>
    <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Arşiv</option>
    <option value="all"      <?= $status === 'all'      ? 'selected' : '' ?>>Tümü</option>
  </select>
  <button class="btn-text">Filtrele</button>
</form>

<div class="sheet"
     data-tour="22" data-tour-title="Kaydın kapısı"
     data-tour-text="Ada tıklamak o kişinin kaydını açar: randevuları, ödemeleri, onamı, seans dosyası ve seanslar arası check-in hep orada. Bu liste yalnız oraya giden yoldur.">
  <?php if ($clients === []): ?>
    <p class="sheet-empty">Kayıt bulunamadı.</p>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="tbl">
        <thead>
          <tr>
            <th>Ad Soyad</th>
            <th>Terapist</th>
            <th>Sonraki randevu</th>
            <th>Onam</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($clients as $row): ?>
            <tr>
              <td>
                <a href="<?= e(url("/danisanlar/{$row['id']}")) ?>" class="person text-ink hover:text-primary">
                  <?= e($row['full_name']) ?>
                </a>
                <?php if ($row['status'] === 'archived'): ?>
                  <span class="chip chip-done ml-1.5">Arşiv</span>
                <?php endif; ?>
                <?php if ($row['phone'] !== null): ?>
                  <br><span class="text-xs text-ink-light num"><?= e($row['phone']) ?></span>
                <?php endif; ?>
              </td>
              <td class="text-ink-muted"><?= e($row['therapist_name'] ?? '—') ?></td>
              <td class="text-xs text-ink-muted num"><?= e(dt($row['next_at'])) ?></td>
              <td>
                <?php if ($row['consent_at'] !== null): ?>
                  <span class="chip chip-go">Alındı</span>
                <?php else: ?>
                  <span class="chip chip-stop">Eksik</span>
                <?php endif; ?>
              </td>
              <td class="text-right whitespace-nowrap">
                <a href="<?= e(url("/danisanlar/{$row['id']}")) ?>" class="btn-text btn-text-quiet">Aç</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
