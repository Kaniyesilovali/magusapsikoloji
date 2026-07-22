<?php
use Panel\Rbac;
/** @var array $clients @var string $search @var string $status @var array $actor */

$inputCls = 'px-3 py-2 rounded-xl border border-warm-tertiary bg-white focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 text-sm';
?>

<header class="flex flex-wrap items-center justify-between gap-3 mb-6">
  <div>
    <h1 class="text-2xl font-semibold text-ink">Danışanlar</h1>
    <p class="text-sm text-ink-light mt-1">
      <?= count($clients) ?> kayıt
      <?php if (!Rbac::can($actor, 'client.view.all')): ?> — yalnız kendi danışanlarınız<?php endif; ?>
    </p>
  </div>
  <?php if (Rbac::can($actor, 'client.create')): ?>
    <a href="<?= e(url('/danisanlar/yeni')) ?>"
       class="bg-primary hover:bg-primary-hover text-white text-sm font-medium px-4 py-2.5 rounded-xl transition-colors">
      Yeni danışan
    </a>
  <?php endif; ?>
</header>

<form method="get" action="<?= e(url('/danisanlar')) ?>" class="mb-4 flex flex-wrap gap-2">
  <input type="search" name="q" value="<?= e($search) ?>" placeholder="Ad, telefon veya e-posta…"
         class="w-full sm:w-72 <?= $inputCls ?>">
  <select name="durum" class="<?= $inputCls ?>">
    <option value="active"   <?= $status === 'active'   ? 'selected' : '' ?>>Aktif</option>
    <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Arşiv</option>
    <option value="all"      <?= $status === 'all'      ? 'selected' : '' ?>>Tümü</option>
  </select>
  <button class="text-sm text-primary hover:text-primary-dark font-medium px-2">Filtrele</button>
</form>

<div class="bg-white border border-warm-tertiary rounded-2xl overflow-hidden">
  <?php if ($clients === []): ?>
    <p class="px-5 py-10 text-sm text-ink-light text-center">Kayıt bulunamadı.</p>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-warm text-ink-muted text-xs uppercase tracking-wide">
          <tr>
            <th class="text-left font-medium px-5 py-3">Ad Soyad</th>
            <th class="text-left font-medium px-5 py-3">Terapist</th>
            <th class="text-left font-medium px-5 py-3">Sonraki randevu</th>
            <th class="text-left font-medium px-5 py-3">Açık rıza</th>
            <th class="px-5 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-warm-secondary">
          <?php foreach ($clients as $row): ?>
            <tr class="hover:bg-warm/60">
              <td class="px-5 py-3">
                <a href="<?= e(url("/danisanlar/{$row['id']}")) ?>" class="font-medium text-ink hover:text-primary">
                  <?= e($row['full_name']) ?>
                </a>
                <?php if ($row['status'] === 'archived'): ?>
                  <span class="text-xs px-2 py-0.5 rounded-full bg-warm-secondary text-ink-muted ml-1">Arşiv</span>
                <?php endif; ?>
                <?php if ($row['phone'] !== null): ?>
                  <br><span class="text-xs text-ink-light tabular-nums"><?= e($row['phone']) ?></span>
                <?php endif; ?>
              </td>
              <td class="px-5 py-3 text-ink-muted"><?= e($row['therapist_name'] ?? '—') ?></td>
              <td class="px-5 py-3 text-ink-muted text-xs tabular-nums"><?= e(dt($row['next_at'])) ?></td>
              <td class="px-5 py-3">
                <?php if ($row['consent_at'] !== null): ?>
                  <span class="text-xs px-2 py-0.5 rounded-full bg-primary/10 text-primary-dark">Alındı</span>
                <?php else: ?>
                  <span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-900">Eksik</span>
                <?php endif; ?>
              </td>
              <td class="px-5 py-3 text-right whitespace-nowrap">
                <a href="<?= e(url("/danisanlar/{$row['id']}")) ?>" class="text-primary hover:text-primary-dark text-xs font-medium">Aç</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
