<?php
use Panel\Audit;
use Panel\Rbac;
/** @var array $rows @var int $page @var int $pages @var int $total @var string $action */
?>

<header class="mb-6">
  <h1 class="text-2xl font-semibold text-ink">Sistem kayıtları</h1>
  <p class="text-sm text-ink-light mt-1">
    <?= number_format($total, 0, ',', '.') ?> kayıt — kim, neyi, ne zaman yaptı.
    KVKK kapsamında erişim izlenebilirliği için tutulur.
  </p>
</header>

<form method="get" action="<?= e(url('/kayitlar')) ?>" class="mb-4">
  <select name="islem" class="w-full sm:w-72 px-3 py-2 rounded-xl border border-warm-tertiary bg-white text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
    <option value="">Tüm işlemler</option>
    <?php foreach (Audit::LABELS as $key => $label): ?>
      <option value="<?= e($key) ?>" <?= $action === $key ? 'selected' : '' ?>><?= e($label) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="ml-2 text-sm text-primary hover:text-primary-dark font-medium">Filtrele</button>
</form>

<div class="bg-white border border-warm-tertiary rounded-2xl overflow-hidden">
  <?php if ($rows === []): ?>
    <p class="px-5 py-10 text-sm text-ink-light text-center">Kayıt yok.</p>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-warm text-ink-muted text-xs uppercase tracking-wide">
          <tr>
            <th class="text-left font-medium px-5 py-3">Zaman</th>
            <th class="text-left font-medium px-5 py-3">Kullanıcı</th>
            <th class="text-left font-medium px-5 py-3">İşlem</th>
            <th class="text-left font-medium px-5 py-3">Hedef</th>
            <th class="text-left font-medium px-5 py-3">IP</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-warm-secondary">
          <?php foreach ($rows as $row): ?>
            <tr class="hover:bg-warm/60">
              <td class="px-5 py-3 text-ink-muted text-xs tabular-nums whitespace-nowrap"><?= e(dt($row['created_at'], 'd.m.Y H:i:s')) ?></td>
              <td class="px-5 py-3">
                <?php if ($row['actor_name'] !== null): ?>
                  <span class="text-ink"><?= e($row['actor_name']) ?></span>
                  <span class="text-xs text-ink-light">(<?= e(Rbac::label($row['actor_role'])) ?>)</span>
                <?php else: ?>
                  <span class="text-ink-light">Oturum yok</span>
                <?php endif; ?>
              </td>
              <td class="px-5 py-3 text-ink-muted"><?= e(Audit::label((string) $row['action'])) ?></td>
              <td class="px-5 py-3 text-xs text-ink-light">
                <?= $row['entity_type'] !== null ? e($row['entity_type'] . ' #' . (string) $row['entity_id']) : '—' ?>
                <?php if ($row['meta'] !== null): ?>
                  <br><span class="break-all"><?= e(mb_substr((string) $row['meta'], 0, 120)) ?></span>
                <?php endif; ?>
              </td>
              <td class="px-5 py-3 text-xs text-ink-light tabular-nums"><?= e((string) $row['ip']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php if ($pages > 1): ?>
  <nav class="flex items-center justify-between mt-4 text-sm">
    <?php $qs = $action !== '' ? '&islem=' . rawurlencode($action) : ''; ?>
    <?php if ($page > 1): ?>
      <a href="<?= e(url('/kayitlar') . '?sayfa=' . ($page - 1) . $qs) ?>" class="text-primary hover:text-primary-dark">← Önceki</a>
    <?php else: ?><span></span><?php endif; ?>

    <span class="text-ink-light text-xs"><?= $page ?> / <?= $pages ?></span>

    <?php if ($page < $pages): ?>
      <a href="<?= e(url('/kayitlar') . '?sayfa=' . ($page + 1) . $qs) ?>" class="text-primary hover:text-primary-dark">Sonraki →</a>
    <?php else: ?><span></span><?php endif; ?>
  </nav>
<?php endif; ?>
