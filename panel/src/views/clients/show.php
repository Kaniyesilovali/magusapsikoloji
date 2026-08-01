<?php
use Panel\Csrf;
use Panel\Invites;
use Panel\Rbac;
use Panel\Scheduling;
/** @var array $client @var array $appointments @var array $actor */
/** @var bool $canOpenCaseFile @var bool $hasCaseFile */
/** @var bool $canSeeCheckins @var bool $canManageCheckins */
/** @var list<array> $checkins @var int $checkinTotal */
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

// Panel erişimi kendi durumunu taşır: "hesap açıldı mı, davet gitti mi, kişi
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

<?php // Tur adımları 20'den başlar; 10–19 kenar çubuğunun (bkz. views/layout.php). ?>
<header class="mb-6"
        data-tour="20" data-tour-title="Kaydın kapağı"
        data-tour-text="Ad, kayıt tarihi ve birincil terapist. Sağdaki düğmeler bu kişiyle ilgili işler: randevu vermek, künyeyi düzenlemek, onam formunu yazdırmak. Arşivlenmiş bir kayıtta adın yanında rozeti durur.">
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

<?php
// ── Bekleyenler ────────────────────────────────────────────────────────
// İki sütunun da ÜSTÜNDE ve tam genişlikte duran tek şey: şu anda bir el
// hareketi bekleyen durumlar. İkisi de geçici — onam işaretlenince ve davet
// bağlantısı iletilince kendiliğinden kaybolurlar. Künyeye konsalardı, hem
// dar sütuna sığmaz hem de kalıcı bir alan gibi görünürlerdi.
?>
<?php if ($client['consent_at'] === null): ?>
  <div class="note note-stop mb-4">
    Bu kayıt için <strong>onam</strong> işaretlenmemiş. Seans verisi işlemeden önce
    onam formu imzalatılmalı ve kayıt düzenlenerek işaretlenmelidir.
  </div>
<?php endif; ?>

<?php if ($manualInvite !== null): ?>
  <?php // E-posta ulaşmazsa bağlantı elden iletilir: tek kullanımlık, 48 saat geçerli. ?>
  <section class="sheet mb-4">
    <div class="sheet-body">
      <p class="text-sm text-ink"><?= e($manualInvite['name']) ?> için şifre belirleme bağlantısı</p>
      <p class="text-sm text-ink-muted mt-1 mb-3">
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
  </section>
<?php endif; ?>

<?php
// ── İki sütun ──────────────────────────────────────────────────────────
// Solda sayfanın işi: terapist bu sayfayı "iki seans arasında ne oldu?"
// sorusuyla açıyor ve cevabı üstten alta okunuyor — örüntüler, check-in
// eğrisi, randevu geçmişi.
//
// Sağda künye: telefon, onam, hesap durumu. Bunlar bakılan şeyler, üzerinde
// çalışılan şeyler değil; tam genişlik yapraklar hâlinde alt alta dizildiklerinde
// klinik içeriği aşağı itiyor ve terapiste hiç kullanmadığı düğmelerin
// başlıklarını okutuyorlardı.
//
// Dar ekranda künye altta kalır: telefon numarası, sayfayı açma sebebinin
// önüne geçmemeli.
?>
<div class="lg:grid lg:grid-cols-[minmax(0,1fr)_19rem] lg:gap-6 lg:items-start">

  <div class="flex flex-col gap-6"
       data-tour="21" data-tour-title="Seans öncesi okunan sütun"
       data-tour-text="Bu sayfa çoğu zaman “iki seans arasında ne oldu?” diye açılır ve cevap üstten alta okunur: önce göze çarpan örüntüler, sonra check-in eğrisi, en altta randevu geçmişi. Eşiği geçen bir şey yoksa örüntüler bölümü hiç çizilmez.">
    <?php // Örüntüler en üstte: seansa girmeden önceki 40 saniyede bakılan yer
          // burası. Eşiği geçen bir şey yoksa kendisi hiç çizilmiyor. ?>
    <?php if ($canSeeCheckins): ?>
      <?php require __DIR__ . '/_patterns.php'; ?>
    <?php endif; ?>

    <?php
    // Check-in eğrisi yalnız terapiste çiziliyor (bkz. Rbac → checkin.view.own).
    //
    // Yöneticide o bölüm yok ama check-in'in idari yüzü var: bu kişiye haftalık
    // e-posta çıkıyor mu, hangi alanlar hangi kelimelerle soruluyor. Sayfada hiç
    // iz bırakmasaydı, o ayarları arayan yönetici burada boşluk görürdü — nitekim
    // gördü. Küçük kart yalnız kapıyı gösteriyor; ölçümler yine görünmüyor.
    if ($canSeeCheckins) {
        require __DIR__ . '/_checkins.php';
    } elseif ($canManageCheckins) {
        require __DIR__ . '/_checkin_admin.php';
    }
    ?>

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
  </div>

  <?php // Künye uzun sayfalarda yerinde kalır: eğri ve randevu geçmişi
        // kaydırılırken telefon numarası hâlâ ekranda. ?>
  <aside class="mt-6 lg:mt-0 lg:sticky lg:top-8"
         data-tour="22" data-tour-title="Künye"
         data-tour-text="Telefon, e-posta, onam durumu ve panel hesabı. Bunlar bakılan şeyler, üzerinde çalışılanlar değil — o yüzden dar bir sütunda, klinik içeriğin yanında duruyorlar. Dar ekranda alta iner.">
    <section class="sheet">
      <div class="sheet-head">
        <h2 class="sheet-title">Künye</h2>
      </div>

      <dl>
        <div class="px-5 py-3">
          <dt class="eyebrow">Telefon</dt>
          <dd class="text-sm text-ink num mt-0.5"><?= e($client['phone'] ?? '—') ?></dd>
        </div>
        <div class="px-5 py-3 border-t border-warm-secondary">
          <dt class="eyebrow">E-posta</dt>
          <dd class="text-sm text-ink break-all mt-0.5"><?= e($client['email'] ?? '—') ?></dd>
        </div>
        <div class="px-5 py-3 border-t border-warm-secondary">
          <dt class="eyebrow">Doğum tarihi</dt>
          <dd class="text-sm text-ink mt-0.5">
            <?= e($client['birth_date'] !== null ? tr_date_label($client['birth_date'], false) : '—') ?>
            <?php if ($age !== null): ?><span class="text-ink-light">(<?= $age ?>)</span><?php endif; ?>
          </dd>
        </div>

        <div class="px-5 py-3 border-t border-warm-secondary">
          <dt class="eyebrow">Onam</dt>
          <dd class="mt-1">
            <?php if ($client['consent_at'] !== null): ?>
              <span class="chip chip-go">Alındı</span>
              <p class="text-xs text-ink-light mt-1 num">
                <?= e(dt($client['consent_at'])) ?> · sürüm <?= e((string) $client['consent_version']) ?>
              </p>
            <?php else: ?>
              <span class="chip chip-stop">Eksik</span>
            <?php endif; ?>
            <a href="<?= e(url("/danisanlar/{$client['id']}/onam")) ?>" target="_blank" rel="noopener"
               class="btn-text inline-block mt-2">
              Onam formunu yazdır →
            </a>
          </dd>
        </div>

        <?php // Panel erişimi künyenin bir satırı: durumu her rol görür, üzerinde
              // işlem yalnız hesap açabilenlerde. Terapiste bugüne kadar tam bir
              // yaprak olarak, hiçbir düğmesi olmadan çiziliyordu. ?>
        <div class="px-5 py-3 border-t border-warm-secondary">
          <dt class="eyebrow">Panel erişimi</dt>
          <dd class="mt-1">
            <span class="chip <?= $accountChipClass ?>"><?= e($accountChip) ?></span>
            <p class="text-sm text-ink-muted mt-1.5"><?= e($accountLine) ?></p>
            <?php if ($hasAccount): ?>
              <?php // Hesabın adresi kayıttakinden ayrılabilir: kayıt sonradan
                    // düzenlenir, hesap eski adresle girmeye devam eder. Aynıysa
                    // yazılmıyor — künyede zaten bir satır yukarıda duruyor. ?>
              <?php if ((string) $client['account_email'] !== (string) $client['email']): ?>
                <p class="text-xs text-accent-dark mt-1 break-all">
                  Giriş adresi: <?= e((string) $client['account_email']) ?>
                </p>
              <?php endif; ?>
              <?php if ($client['account_last_login'] !== null): ?>
                <p class="text-xs text-ink-light mt-1 num">
                  Son giriş <?= e(dt($client['account_last_login'])) ?>
                </p>
              <?php endif; ?>
            <?php elseif ($client['email'] === null): ?>
              <p class="text-xs text-ink-light mt-1">
                Hesap açmak için önce kayda bir e-posta adresi eklenmeli.
              </p>
            <?php endif; ?>

            <?php if ($canManage): ?>
              <div class="flex flex-wrap gap-2 mt-2.5">
                <?php if (!$hasAccount): ?>
                  <?php if ($client['email'] !== null): ?>
                    <form method="post" action="<?= e(url("/danisanlar/{$client['id']}/panel-erisimi")) ?>">
                      <?= Csrf::field() ?>
                      <button class="btn btn-primary btn-sm">Hesap aç ve davet gönder</button>
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
          </dd>
        </div>
      </dl>

      <?php // Arşivleme ve silme künyenin ayağında: kaydın kendisine ait
            // işlemler, klinik içerikle aynı boyda bir yaprak değil. Silme
            // hâlâ onay soruyor ve gerekçesi düğmenin yanında duruyor. ?>
      <?php if (Rbac::canAny($actor, ['client.update', 'client.delete'])): ?>
        <div class="sheet-foot">
          <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
            <?php if (Rbac::can($actor, 'client.update')): ?>
              <form method="post" action="<?= e(url("/danisanlar/{$client['id']}/arsivle")) ?>">
                <?= Csrf::field() ?>
                <button class="btn-text btn-text-quiet"><?= $isActive ? 'Arşivle' : 'Arşivden çıkar' ?></button>
              </form>
            <?php endif; ?>

            <?php if (Rbac::can($actor, 'client.delete')): ?>
              <form method="post" action="<?= e(url("/danisanlar/{$client['id']}/sil")) ?>"
                    data-confirm="<?= e($client['full_name']) ?> kaydı, tüm randevuları ve seans notları kalıcı olarak silinecek. Bu işlem geri alınamaz. Devam edilsin mi?">
                <?= Csrf::field() ?>
                <button class="btn-text btn-text-danger">Kalıcı olarak sil</button>
              </form>
            <?php endif; ?>
          </div>
          <p class="field-hint">
            Arşivleme geri alınabilir ve randevu geçmişini korur. Kalıcı silme
            yalnız KVKK kapsamındaki silme talepleri içindir.
          </p>
        </div>
      <?php endif; ?>
    </section>
  </aside>
</div>
