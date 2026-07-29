<?php
use Panel\Csrf;
use Panel\Invites;
use Panel\Rbac;
use Panel\Scheduling;
/** @var array $client @var array $appointments @var array $actor */
/** @var bool $canOpenCaseFile @var bool $hasCaseFile */
/** @var bool $canSeeCheckins @var list<array> $checkins @var int $checkinTotal */
/** @var ?array $checkinPending @var ?array $checkinLink */

$age      = age_from($client['birth_date']);
$isActive = $client['status'] === 'active';

$chip = [
    'scheduled' => 'chip-neutral',
    'confirmed' => 'chip-go',
    'completed' => 'chip-done',
    'cancelled' => 'chip-stop',
    'no_show'   => 'chip-stop',
];
?>

<header class="mb-6">
  <a href="<?= e(url('/danisanlar')) ?>" class="btn-text btn-text-quiet">← Görüşmeciler</a>
  <div class="flex flex-wrap items-start justify-between gap-3 mt-2">
    <div>
      <h1 class="page-title">
        <?= e($client['full_name']) ?>
        <?php if (!$isActive): ?>
          <span class="chip chip-done align-middle ml-1">Arşiv</span>
        <?php endif; ?>
      </h1>
      <p class="page-sub">
        Kayıt: <span class="num"><?= e(dt($client['created_at'], 'd.m.Y')) ?></span>
        <?php if ($client['therapist_name'] !== null): ?>
          — Terapist: <?= e($client['therapist_name']) ?>
        <?php endif; ?>
      </p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <?php if (Rbac::can($actor, 'appointment.create') && $isActive): ?>
        <a href="<?= e(url('/randevular/yeni?danisan=' . (int) $client['id'])) ?>" class="btn btn-primary">
          Randevu ver
        </a>
      <?php endif; ?>
      <?php // Dosya yalnız terapistlerde: içerik şifreli ve yalnız yazarına açık. ?>
      <?php if ($canOpenCaseFile): ?>
        <a href="<?= e(url("/danisanlar/{$client['id']}/dosya")) ?>" class="btn btn-quiet">
          <?= $hasCaseFile ? 'Dosya ✓' : 'Dosya' ?>
        </a>
      <?php endif; ?>
      <?php if (Rbac::can($actor, 'client.update')): ?>
        <a href="<?= e(url("/danisanlar/{$client['id']}/duzenle")) ?>" class="btn btn-quiet">Düzenle</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<?php if ($client['consent_at'] === null): ?>
  <div class="note note-stop mb-6">
    Bu kayıt için <strong>açık rıza</strong> işaretlenmemiş. Seans verisi işlemeden önce
    aydınlatma metni sunulup rıza alınmalı ve kayıt düzenlenerek işaretlenmelidir.
  </div>
<?php endif; ?>

<?php // Örüntüler idari alanların ÜSTÜNDE: seansa girmeden önceki 40 saniyede
      // bakılan yer burası. Aşağı inerse hiç bakılmaz. Eşiği geçen bir şey
      // yoksa kendisi hiç çizilmiyor. ?>
<?php if ($canSeeCheckins): ?>
  <?php require __DIR__ . '/_patterns.php'; ?>
<?php endif; ?>

<div class="grid lg:grid-cols-3 gap-4 mb-6">
  <section class="sheet lg:col-span-2">
    <div class="sheet-head">
      <h2 class="sheet-title">İletişim</h2>
    </div>
    <dl class="sheet-body grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
      <div>
        <dt class="eyebrow">Telefon</dt>
        <dd class="text-ink num mt-0.5"><?= e($client['phone'] ?? '—') ?></dd>
      </div>
      <div>
        <dt class="eyebrow">E-posta</dt>
        <dd class="text-ink break-all mt-0.5"><?= e($client['email'] ?? '—') ?></dd>
      </div>
      <div>
        <dt class="eyebrow">Doğum tarihi</dt>
        <dd class="text-ink mt-0.5">
          <?= e($client['birth_date'] !== null ? tr_date_label($client['birth_date'], false) : '—') ?>
          <?php if ($age !== null): ?><span class="text-ink-light">(<?= $age ?>)</span><?php endif; ?>
        </dd>
      </div>
    </dl>
  </section>

  <section class="sheet">
    <div class="sheet-head">
      <h2 class="sheet-title">KVKK</h2>
    </div>
    <div class="sheet-body">
      <?php if ($client['consent_at'] !== null): ?>
        <p class="text-sm text-ink">Açık rıza alındı.</p>
        <p class="text-xs text-ink-light mt-1">
          <span class="num"><?= e(dt($client['consent_at'])) ?></span><br>
          Metin sürümü: <?= e((string) $client['consent_version']) ?>
        </p>
      <?php else: ?>
        <p class="text-sm text-ink-muted">Rıza kaydı yok.</p>
      <?php endif; ?>
      <a href="<?= e(url("/danisanlar/{$client['id']}/riza")) ?>" target="_blank" rel="noopener"
         class="btn-text inline-block mt-3">
        Rıza formunu yazdır →
      </a>
    </div>
  </section>
</div>

<?php
// Check-in eğrisi sayfanın üstünde, idari alanların önünde: terapist bu sayfayı
// "iki seans arasında ne oldu?" sorusuyla açıyor, hesap durumunu öğrenmek için
// değil. Bölüm yalnız terapiste çiziliyor (bkz. Rbac → checkin.view.own).
if ($canSeeCheckins) {
    require __DIR__ . '/_checkins.php';
}
?>

<?php
// Panel erişimi kendi bölümünde durur: "hesap açıldı mı, davet gitti mi, kişi
// girebiliyor mu" tek satırlık bir alan değil, üzerinde işlem yapılan bir durum.
$hasAccount   = $client['user_id'] !== null;
$accountState = $hasAccount ? (string) $client['account_status'] : 'none';
$canManage    = Rbac::can($actor, 'user.create');

[$accountChip, $accountChipClass, $accountLine] = match ($accountState) {
    'invited'   => ['Davet gönderildi', 'chip-stop', 'Davet e-postası gönderildi; görüşmeci şifresini henüz belirlemedi.'],
    'active'    => ['Açık',             'chip-go',   'Görüşmeci panele girip kendi randevularını ve ödeme durumunu görebiliyor.'],
    'suspended' => ['Kapalı',           'chip-done', 'Erişim kapatıldı. Hesap duruyor, istendiğinde yeniden açılabilir.'],
    default     => ['Hesap yok',        'chip-neutral', 'Bu görüşmecinin panel hesabı yok.'],
};

$manualInvite = Invites::pending('client');
?>

<section class="sheet mb-6">
  <div class="sheet-head">
    <h2 class="sheet-title">Panel erişimi</h2>
    <span class="chip <?= $accountChipClass ?>"><?= e($accountChip) ?></span>
  </div>
  <div class="sheet-body">
    <p class="text-sm text-ink"><?= e($accountLine) ?></p>
    <?php if ($hasAccount): ?>
      <p class="text-xs text-ink-light mt-1 break-all">
        <?= e((string) $client['account_email']) ?>
        <?php if ($client['account_last_login'] !== null): ?>
          — son giriş <span class="num"><?= e(dt($client['account_last_login'])) ?></span>
        <?php endif; ?>
      </p>
    <?php elseif ($client['email'] === null): ?>
      <p class="text-xs text-ink-light mt-1">
        Hesap açmak için önce kayda bir e-posta adresi eklenmeli.
      </p>
    <?php endif; ?>

    <?php if ($manualInvite !== null): ?>
      <?php // E-posta ulaşmazsa bağlantı elden iletilir: tek kullanımlık, 48 saat geçerli. ?>
      <div class="bg-warm rounded-md p-4 mt-4">
        <p class="text-sm text-ink"><?= e($manualInvite['name']) ?> için şifre belirleme bağlantısı</p>
        <p class="text-xs text-ink-light mt-1 mb-3">
          <?= !empty($manualInvite['sent'])
              ? 'E-posta gönderildi. Ulaşmazsa (spam filtresi, yanlış adres) bu bağlantıyı doğrudan iletebilirsiniz.'
              : 'E-posta gönderilemedi; bu bağlantıyı görüşmeciye kendiniz iletin.' ?>
          48 saat geçerli ve tek kullanımlık. Yalnızca kişinin kendisine verin.
        </p>
        <div class="flex gap-2">
          <label for="inviteLink" class="sr-only">Şifre belirleme bağlantısı</label>
          <input id="inviteLink" type="text" readonly value="<?= e($manualInvite['url']) ?>"
                 class="field flex-1 min-w-0 text-xs font-mono text-ink-muted">
          <button type="button" data-copy="#inviteLink" class="btn btn-primary shrink-0">Kopyala</button>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($canManage): ?>
      <div class="flex flex-wrap gap-3 mt-4">
        <?php if (!$hasAccount): ?>
          <?php if ($client['email'] !== null): ?>
            <form method="post" action="<?= e(url("/danisanlar/{$client['id']}/panel-erisimi")) ?>">
              <?= Csrf::field() ?>
              <button class="btn btn-primary btn-sm">Panel hesabı aç ve davet gönder</button>
            </form>
          <?php else: ?>
            <a href="<?= e(url("/danisanlar/{$client['id']}/duzenle")) ?>" class="btn btn-quiet btn-sm">
              E-posta ekle
            </a>
          <?php endif; ?>
        <?php else: ?>
          <?php if ($accountState !== 'suspended'): ?>
            <form method="post" action="<?= e(url("/danisanlar/{$client['id']}/davet-gonder")) ?>">
              <?= Csrf::field() ?>
              <button class="btn btn-quiet btn-sm">
                <?= $accountState === 'invited' ? 'Daveti yenile' : 'Şifre bağlantısı gönder' ?>
              </button>
            </form>
          <?php endif; ?>
          <form method="post" action="<?= e(url("/danisanlar/{$client['id']}/erisim")) ?>"
                <?= $accountState !== 'suspended'
                    ? 'data-confirm="' . e($client['full_name']) . ' panele giremeyecek. Hesap silinmez, istediğinizde yeniden açabilirsiniz. Devam edilsin mi?"'
                    : '' ?>>
            <?= Csrf::field() ?>
            <button class="btn btn-quiet btn-sm">
              <?= $accountState === 'suspended' ? 'Erişimi aç' : 'Erişimi kapat' ?>
            </button>
          </form>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<section class="sheet">
  <div class="sheet-head">
    <h2 class="sheet-title">Randevu geçmişi</h2>
    <span class="text-xs text-ink-light"><span class="num"><?= count($appointments) ?></span> kayıt</span>
  </div>

  <?php if ($appointments === []): ?>
    <p class="sheet-empty">Randevu kaydı yok.</p>
  <?php else: ?>
    <ul>
      <?php foreach ($appointments as $index => $appointment): ?>
        <li class="px-5 py-3 flex flex-wrap items-center gap-x-4 gap-y-1 <?= $index > 0 ? 'border-t border-warm-secondary' : '' ?>">
          <span class="text-sm text-ink num w-36 shrink-0"><?= e(dt($appointment['starts_at'])) ?></span>
          <span class="text-sm text-ink-muted flex-1 min-w-40"><?= e($appointment['therapist_name']) ?></span>
          <span class="text-xs text-ink-light"><?= e(Scheduling::locationLabel($appointment['location'])) ?></span>
          <span class="chip <?= $chip[$appointment['status']] ?? 'chip-neutral' ?>">
            <?= e(Scheduling::statusLabel($appointment['status'])) ?>
          </span>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>

<?php if (Rbac::canAny($actor, ['client.update', 'client.delete'])): ?>
  <section class="sheet mt-6">
    <div class="sheet-head">
      <h2 class="sheet-title">Kayıt yönetimi</h2>
    </div>
    <div class="sheet-body">
      <p class="text-xs text-ink-light mb-4">
        Arşivleme geri alınabilir ve randevu geçmişini korur. Kalıcı silme yalnız KVKK
        kapsamındaki silme talepleri içindir.
      </p>
      <div class="flex flex-wrap gap-3">
        <?php if (Rbac::can($actor, 'client.update')): ?>
          <form method="post" action="<?= e(url("/danisanlar/{$client['id']}/arsivle")) ?>">
            <?= Csrf::field() ?>
            <button class="btn btn-quiet btn-sm"><?= $isActive ? 'Arşivle' : 'Arşivden çıkar' ?></button>
          </form>
        <?php endif; ?>

        <?php if (Rbac::can($actor, 'client.delete')): ?>
          <form method="post" action="<?= e(url("/danisanlar/{$client['id']}/sil")) ?>"
                data-confirm="<?= e($client['full_name']) ?> kaydı, tüm randevuları ve seans notları kalıcı olarak silinecek. Bu işlem geri alınamaz. Devam edilsin mi?">
            <?= Csrf::field() ?>
            <button class="btn btn-danger btn-sm">Kalıcı olarak sil</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </section>
<?php endif; ?>
