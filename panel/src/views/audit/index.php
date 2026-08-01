<?php
use Panel\Audit;
use Panel\Rbac;
/** @var array $rows @var int $page @var int $pages @var int $total @var string $action */
?>

<?php // Tur adımları 20'den başlar; 10–19 kenar çubuğunun (bkz. views/layout.php). ?>
<header class="mb-6"
        data-tour="20" data-tour-title="Kim, neyi, ne zaman"
        data-tour-text="Panelde yapılan işlemlerin izi. KVKK kapsamında erişim izlenebilirliği için tutulur: bir kaydın kim tarafından açıldığını ya da silindiğini sonradan sorabilmek gerekiyor. Kayıtlar buradan değiştirilemez, yalnız okunur.">
  <p class="eyebrow">Yönetim</p>
  <h1 class="page-title mt-2">Sistem kayıtları</h1>
  <p class="page-sub">
    <span class="num"><?= number_format($total, 0, ',', '.') ?></span> kayıt — kim, neyi, ne zaman yaptı.
    KVKK kapsamında erişim izlenebilirliği için tutulur.
  </p>
</header>

<form method="get" action="<?= e(url('/kayitlar')) ?>" class="flex items-center gap-2 mb-4"
      data-tour="21" data-tour-title="İşleme göre süzün"
      data-tour-text="Kayıt sayısı hızla büyür ve aranan şey genelde tek bir işlem türüdür: giriş denemeleri, silinen kayıtlar, gönderilen davetler. Liste tarihe göre yeniden eskiye sıralıdır, altında sayfalama vardır.">
  <label for="islem" class="eyebrow">İşlem</label>
  <select id="islem" name="islem" class="field w-auto py-1.5 text-[0.8125rem]">
    <option value="">Tüm işlemler</option>
    <?php foreach (Audit::LABELS as $key => $label): ?>
      <option value="<?= e($key) ?>" <?= $action === $key ? 'selected' : '' ?>><?= e($label) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn-text">Filtrele</button>
</form>

<div class="sheet">
  <?php if ($rows === []): ?>
    <p class="sheet-empty">Kayıt yok.</p>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="tbl">
        <thead>
          <tr>
            <th>Zaman</th>
            <th>Kullanıcı</th>
            <th>İşlem</th>
            <th>Hedef</th>
            <th>IP</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td class="text-xs text-ink-muted num whitespace-nowrap">
                <?= e(dt($row['created_at'], 'd.m.Y H:i:s')) ?>
              </td>
              <td>
                <?php if ($row['actor_name'] !== null): ?>
                  <span class="person text-ink"><?= e($row['actor_name']) ?></span>
                  <span class="text-xs text-ink-light">(<?= e(Rbac::label($row['actor_role'])) ?>)</span>
                <?php else: ?>
                  <span class="text-ink-light">Oturum yok</span>
                <?php endif; ?>
              </td>
              <td class="text-ink-muted"><?= e(Audit::label((string) $row['action'])) ?></td>
              <td class="text-xs text-ink-light">
                <?= $row['entity_type'] !== null ? e($row['entity_type'] . ' #' . (string) $row['entity_id']) : '—' ?>
                <?php if ($row['meta'] !== null): ?>
                  <br><span class="break-all"><?= e(mb_substr((string) $row['meta'], 0, 120)) ?></span>
                <?php endif; ?>
              </td>
              <td class="text-xs text-ink-light num"><?= e((string) $row['ip']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php if ($pages > 1): ?>
  <nav class="flex items-center justify-between mt-4">
    <?php $qs = $action !== '' ? '&islem=' . rawurlencode($action) : ''; ?>
    <?php if ($page > 1): ?>
      <a href="<?= e(url('/kayitlar') . '?sayfa=' . ($page - 1) . $qs) ?>" class="btn-text">← Önceki</a>
    <?php else: ?><span></span><?php endif; ?>

    <span class="text-xs text-ink-light num"><?= $page ?> / <?= $pages ?></span>

    <?php if ($page < $pages): ?>
      <a href="<?= e(url('/kayitlar') . '?sayfa=' . ($page + 1) . $qs) ?>" class="btn-text">Sonraki →</a>
    <?php else: ?><span></span><?php endif; ?>
  </nav>
<?php endif; ?>
