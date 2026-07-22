<?php
use Panel\Csrf;
use Panel\Rbac;
/** @var array $users @var string $search @var array $actor */

$statusBadge = [
    'active'    => ['Aktif',    'bg-primary/10 text-primary-dark'],
    'invited'   => ['Davetli',  'bg-amber-100 text-amber-900'],
    'suspended' => ['Askıda',   'bg-accent/10 text-accent-dark'],
];
?>

<header class="flex flex-wrap items-center justify-between gap-3 mb-6">
  <div>
    <h1 class="text-2xl font-semibold text-ink">Kullanıcılar</h1>
    <p class="text-sm text-ink-light mt-1"><?= count($users) ?> kayıt</p>
  </div>
  <?php if (Rbac::can($actor, 'user.create')): ?>
    <a href="<?= e(url('/kullanicilar/yeni')) ?>"
       class="bg-primary hover:bg-primary-hover text-white text-sm font-medium px-4 py-2.5 rounded-xl transition-colors">
      Yeni kullanıcı
    </a>
  <?php endif; ?>
</header>

<?php
// E-posta gönderilemediğinde davet bağlantısı burada gösterilir; yönetici
// WhatsApp gibi başka bir kanaldan iletir. Tek kullanımlık, 48 saat geçerli.
$manualInvite = $_SESSION['_invite_link'] ?? null;
unset($_SESSION['_invite_link']);
?>
<?php if ($manualInvite !== null): ?>
  <div class="mb-4 bg-white border border-warm-tertiary rounded-2xl p-5">
    <p class="text-sm font-medium text-ink"><?= e($manualInvite['name']) ?> için şifre belirleme bağlantısı</p>
    <p class="text-xs text-ink-light mt-1 mb-3">
      <?= !empty($manualInvite['sent'])
          ? 'E-posta gönderildi. Ulaşmazsa (spam filtresi, yanlış adres) bu bağlantıyı doğrudan iletebilirsiniz.'
          : 'E-posta gönderilemedi; bu bağlantıyı kullanıcıya kendiniz iletin.' ?>
      48 saat geçerli ve tek kullanımlık. Yalnızca kişinin kendisine verin —
      bağlantıyı alan herkes bu hesabın şifresini belirleyebilir.
    </p>
    <div class="flex gap-2">
      <input id="inviteLink" type="text" readonly value="<?= e($manualInvite['url']) ?>"
             class="flex-1 min-w-0 px-3 py-2 rounded-xl border border-warm-tertiary bg-warm text-xs text-ink-muted font-mono">
      <button type="button" data-copy="#inviteLink"
              class="shrink-0 bg-primary hover:bg-primary-hover text-white text-sm font-medium px-4 py-2 rounded-xl transition-colors">
        Kopyala
      </button>
    </div>
  </div>
<?php endif; ?>

<form method="get" action="<?= e(url('/kullanicilar')) ?>" class="mb-4">
  <input type="search" name="q" value="<?= e($search) ?>" placeholder="Ad veya e-posta ile ara…"
         class="w-full sm:w-72 px-3 py-2 rounded-xl border border-warm-tertiary bg-white focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 text-sm">
</form>

<div class="bg-white border border-warm-tertiary rounded-2xl overflow-hidden">
  <?php if ($users === []): ?>
    <p class="px-5 py-10 text-sm text-ink-light text-center">Kayıt bulunamadı.</p>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-warm text-ink-muted text-xs uppercase tracking-wide">
          <tr>
            <th class="text-left font-medium px-5 py-3">Ad Soyad</th>
            <th class="text-left font-medium px-5 py-3">Rol</th>
            <th class="text-left font-medium px-5 py-3">Durum</th>
            <th class="text-left font-medium px-5 py-3">Son giriş</th>
            <th class="px-5 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-warm-secondary">
          <?php foreach ($users as $row): ?>
            <?php
            $isSelf   = (int) $row['id'] === (int) $actor['id'];
            $canTouch = $isSelf || Rbac::canManageUser($actor, $row);
            [$statusText, $statusClass] = $statusBadge[$row['status']] ?? [$row['status'], 'bg-warm-secondary text-ink-muted'];
            ?>
            <tr class="hover:bg-warm/60">
              <td class="px-5 py-3">
                <span class="font-medium text-ink"><?= e($row['full_name']) ?></span>
                <?php if ($isSelf): ?><span class="text-xs text-ink-light ml-1">(siz)</span><?php endif; ?>
                <br><span class="text-xs text-ink-light"><?= e($row['email']) ?></span>
              </td>
              <td class="px-5 py-3 text-ink-muted"><?= e(Rbac::label($row['role'])) ?></td>
              <td class="px-5 py-3">
                <span class="text-xs px-2 py-0.5 rounded-full <?= $statusClass ?>"><?= e($statusText) ?></span>
              </td>
              <td class="px-5 py-3 text-ink-light text-xs tabular-nums"><?= e(dt($row['last_login_at'])) ?></td>
              <td class="px-5 py-3">
                <?php if ($canTouch): ?>
                  <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                    <a href="<?= e(url("/kullanicilar/{$row['id']}/duzenle")) ?>" class="text-primary hover:text-primary-dark text-xs font-medium">Düzenle</a>

                    <?php if ($row['status'] === 'invited'): ?>
                      <form method="post" action="<?= e(url("/kullanicilar/{$row['id']}/davet-gonder")) ?>">
                        <?= Csrf::field() ?>
                        <button class="text-ink-muted hover:text-ink text-xs">Daveti gönder</button>
                      </form>
                    <?php endif; ?>

                    <?php if (!$isSelf && Rbac::can($actor, 'user.delete')): ?>
                      <form method="post" action="<?= e(url("/kullanicilar/{$row['id']}/sil")) ?>"
                            data-confirm="<?= e($row['full_name']) ?> adlı kullanıcı silinsin mi? Bu işlem geri alınamaz.">
                        <?= Csrf::field() ?>
                        <button class="text-accent-dark hover:text-accent text-xs">Sil</button>
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
