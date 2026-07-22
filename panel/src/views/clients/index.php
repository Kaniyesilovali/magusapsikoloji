<?php
use Panel\Rbac;
/** @var array $clients @var string $search @var string $status @var array $actor */
?>

<header class="flex flex-wrap items-end justify-between gap-4 mb-6">
  <div>
    <p class="eyebrow">Merkez</p>
    <h1 class="page-title mt-2">Danışanlar</h1>
    <p class="page-sub">
      <span class="num"><?= count($clients) ?></span> kayıt
      <?php if (!Rbac::can($actor, 'client.view.all')): ?> — yalnız kendi danışanlarınız<?php endif; ?>
    </p>
  </div>
  <?php if (Rbac::can($actor, 'client.create')): ?>
    <a href="<?= e(url('/danisanlar/yeni')) ?>" class="btn btn-primary">Yeni danışan</a>
  <?php endif; ?>
</header>

<form method="get" action="<?= e(url('/danisanlar')) ?>" class="mb-4 flex flex-wrap items-center gap-2">
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

<div class="sheet">
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
            <th>Açık rıza</th>
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
