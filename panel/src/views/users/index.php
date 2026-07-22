<?php
use Panel\Csrf;
use Panel\Rbac;
/** @var array $users @var string $search @var array $actor */

// Davetli: kişi henüz şifresini belirlemedi — daveti yenilemek gerekebilir, clay.
// Askıda: kapatılmış bir hesap, bekleyen karar yok — soluk durur.
$statusBadge = [
    'active'    => ['Aktif',   'chip-go'],
    'invited'   => ['Davetli', 'chip-stop'],
    'suspended' => ['Askıda',  'chip-done'],
];
?>

<header class="flex flex-wrap items-end justify-between gap-4 mb-6">
  <div>
    <p class="eyebrow">Yönetim</p>
    <h1 class="page-title mt-2">Kullanıcılar</h1>
    <p class="page-sub"><span class="num"><?= count($users) ?></span> kayıt</p>
  </div>
  <?php if (Rbac::can($actor, 'user.create')): ?>
    <a href="<?= e(url('/kullanicilar/yeni')) ?>" class="btn btn-primary">Yeni kullanıcı</a>
  <?php endif; ?>
</header>

<?php
// E-posta gönderilemediğinde davet bağlantısı burada gösterilir; yönetici
// WhatsApp gibi başka bir kanaldan iletir. Tek kullanımlık, 48 saat geçerli.
$manualInvite = $_SESSION['_invite_link'] ?? null;
unset($_SESSION['_invite_link']);
?>
<?php if ($manualInvite !== null): ?>
  <div class="sheet mb-4">
    <div class="sheet-body">
      <p class="sheet-title"><?= e($manualInvite['name']) ?> için şifre belirleme bağlantısı</p>
      <p class="text-xs text-ink-light mt-1 mb-3">
        <?= !empty($manualInvite['sent'])
            ? 'E-posta gönderildi. Ulaşmazsa (spam filtresi, yanlış adres) bu bağlantıyı doğrudan iletebilirsiniz.'
            : 'E-posta gönderilemedi; bu bağlantıyı kullanıcıya kendiniz iletin.' ?>
        48 saat geçerli ve tek kullanımlık. Yalnızca kişinin kendisine verin —
        bağlantıyı alan herkes bu hesabın şifresini belirleyebilir.
      </p>
      <div class="flex gap-2">
        <label for="inviteLink" class="sr-only">Şifre belirleme bağlantısı</label>
        <input id="inviteLink" type="text" readonly value="<?= e($manualInvite['url']) ?>"
               class="field flex-1 min-w-0 text-xs font-mono text-ink-muted">
        <button type="button" data-copy="#inviteLink" class="btn btn-primary shrink-0">Kopyala</button>
      </div>
    </div>
  </div>
<?php endif; ?>

<form method="get" action="<?= e(url('/kullanicilar')) ?>" class="mb-4">
  <label for="q" class="sr-only">Kullanıcı ara</label>
  <input type="search" id="q" name="q" value="<?= e($search) ?>" placeholder="Ad veya e-posta ile ara…"
         class="field w-full sm:w-72">
</form>

<div class="sheet">
  <?php if ($users === []): ?>
    <p class="sheet-empty">Kayıt bulunamadı.</p>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="tbl">
        <thead>
          <tr>
            <th>Ad Soyad</th>
            <th>Rol</th>
            <th>Durum</th>
            <th>Son giriş</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $row): ?>
            <?php
            $isSelf   = (int) $row['id'] === (int) $actor['id'];
            $canTouch = $isSelf || Rbac::canManageUser($actor, $row);
            [$statusText, $statusClass] = $statusBadge[$row['status']] ?? [$row['status'], 'chip-neutral'];
            ?>
            <tr>
              <td>
                <span class="person text-ink"><?= e($row['full_name']) ?></span>
                <?php if ($isSelf): ?><span class="text-xs text-ink-light ml-1">(siz)</span><?php endif; ?>
                <br><span class="text-xs text-ink-light"><?= e($row['email']) ?></span>
              </td>
              <td class="text-ink-muted"><?= e(Rbac::label($row['role'])) ?></td>
              <td><span class="chip <?= $statusClass ?>"><?= e($statusText) ?></span></td>
              <td class="text-xs text-ink-light num"><?= e(dt($row['last_login_at'])) ?></td>
              <td>
                <?php if ($canTouch): ?>
                  <div class="flex items-center justify-end gap-4 whitespace-nowrap">
                    <a href="<?= e(url("/kullanicilar/{$row['id']}/duzenle")) ?>"
                       class="btn-text btn-text-quiet">Düzenle</a>

                    <?php if ($row['status'] === 'invited'): ?>
                      <form method="post" action="<?= e(url("/kullanicilar/{$row['id']}/davet-gonder")) ?>">
                        <?= Csrf::field() ?>
                        <button class="btn-text btn-text-quiet">Daveti gönder</button>
                      </form>
                    <?php endif; ?>

                    <?php if (!$isSelf && Rbac::can($actor, 'user.delete')): ?>
                      <form method="post" action="<?= e(url("/kullanicilar/{$row['id']}/sil")) ?>"
                            data-confirm="<?= e($row['full_name']) ?> adlı kullanıcı silinsin mi? Bu işlem geri alınamaz.">
                        <?= Csrf::field() ?>
                        <button class="btn-text btn-text-quiet hover:text-accent-dark">Sil</button>
                      </form>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
