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
/** @var array $consent @var list<array> $consentHistory */
/** @var ?array $consentPending @var ?array $consentLink @var bool $consentReady */

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
    'invited'   => ['Davet gönderildi', 'chip-stop', 'Davet e-postası gönderildi; birey şifresini henüz belirlemedi.'],
    'active'    => ['Açık',             'chip-go',   'Birey panele girip kendi randevularını ve ödeme durumunu görebiliyor.'],
    'suspended' => ['Kapalı',           'chip-done', 'Erişim kapatıldı. Hesap duruyor, istendiğinde yeniden açılabilir.'],
    default     => ['Hesap yok',        'chip-neutral', 'Bu bireyin panel hesabı yok.'],
};

$manualInvite = Invites::pending('client');
?>

<?php // Tur adımları 20'den başlar; 10–19 kenar çubuğunun (bkz. views/layout.php). ?>
<header class="mb-6"
        data-tour="20" data-tour-title="Kaydın kapağı"
        data-tour-text="Ad, kayıt tarihi ve birincil terapist. Sağdaki düğmeler bu kişiyle ilgili işler: randevu vermek, künyeyi düzenlemek, onam formunu yazdırmak. Arşivlenmiş bir kayıtta adın yanında rozeti durur.">
  <a href="<?= e(url('/danisanlar')) ?>" class="btn-text btn-text-quiet">← Bireyler</a>
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
      <?php // Onam çıktısı künyenin içindeydi; ilk seans öncesi en sık basılan
            // düğme, sağ sütunda telefon numarasının altında aranıyordu. Sayfanın
            // işleri burada duruyor, o da bir iş. Künyede yalnız durumu kaldı. ?>
      <a href="<?= e(url("/danisanlar/{$client['id']}/onam")) ?>" target="_blank" rel="noopener"
         class="btn btn-quiet">Onam formu</a>
      <?php // Bağlantı, telefonda randevu alınırken gönderilen şey: metin
            // seanstan ÖNCE, evde okunsun diye. Onam tamamlanmışsa düğme yok —
            // gönderecek bir şey kalmadı. ?>
      <?php if ($consentReady && $isActive && $consent['key'] !== 'tam' && Rbac::can($actor, 'client.update')): ?>
        <form method="post" action="<?= e(url("/danisanlar/{$client['id']}/onam-baglantisi")) ?>" class="inline">
          <?= Csrf::field() ?>
          <button class="btn btn-quiet">
            <?= $consentPending !== null ? 'Onam bağlantısını yenile' : 'Onam bağlantısı gönder' ?>
          </button>
        </form>
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
<?php
// ── Onam ───────────────────────────────────────────────────────────────
// Üç hâl, üç ayrı cümle. Eskiden tek bir kırmızı şerit vardı ve "onam
// işaretlenmemiş" diyordu; metni evinde okuyup onaylamış birinin kaydında da
// aynı şeyi diyordu. Bu, terapiste yapılmış bir işi yapılmamış gibi gösterip
// bireye ikinci kez okutmasına yol açardı — kaçınılmak istenen şeyin ta
// kendisi.
//
// Çevrimiçi onay KIRMIZI DEĞİL: kimse bir şeyi yanlış yapmadı, sıradaki adım
// seansta. Kırmızı, gerçekten eksik olan için ayrılıyor.
?>
<?php if ($consent['key'] === 'eksik'): ?>
  <div class="note note-stop mb-4">
    Bu kayıt için <strong>onam</strong> alınmamış. Seans verisi işlemeden önce
    onam formu okunmalı ve imzalanmalı.
    <?php if ($consentReady && $isActive): ?>
      Birey metni seanstan önce okusun isterseniz yukarıdan
      <strong>onam bağlantısı</strong> gönderebilirsiniz; internete girmeyen biri
      için formun çıktısını alıp yüz yüze okuyun.
    <?php endif; ?>
  </div>
<?php elseif ($consent['key'] === 'cevrimici'): ?>
  <?php
  // Metin okundu, imza bekleniyor. Buradaki iki düğme seansın içinde,
  // konuşmanın ortasında basılacak şeyler — bu yüzden kayıt düzenleme
  // ekranında değil, sayfanın en üstünde ve tek dokunuşluk.
  $canClose = Rbac::can($actor, 'client.update') && $isActive;
  ?>
  <section class="sheet mb-4">
    <div class="sheet-body">
      <p class="text-sm text-ink"><strong>Onam çevrimiçi onaylandı</strong> — seansta kapanması bekleniyor.</p>
      <p class="text-sm text-ink-muted mt-1"><?= e($consent['detail']) ?></p>

      <?php if ($canClose): ?>
        <p class="text-sm text-ink-muted mt-3">
          Metni yeniden okutmanız gerekmiyor. Merkeze geldiyse
          <a href="<?= e(url("/danisanlar/{$client['id']}/onam")) ?>" target="_blank" rel="noopener"
             class="btn-text">formun çıktısını alıp</a>
          imzalatın; online görüşüyorsanız onayını sözlü olarak beyan etmesini isteyin.
        </p>

        <div class="flex flex-wrap items-start gap-3 mt-4">
          <form method="post" action="<?= e(url("/danisanlar/{$client['id']}/onam/imza")) ?>">
            <?= Csrf::field() ?>
            <button class="btn btn-primary">Islak imza alındı</button>
          </form>

          <?php // Sözlü onamın künyesi: ses/görüntü dosyası panele YÜKLENMİYOR,
                // merkezin kendi klasöründe duruyor. Buraya yalnız o dosyanın
                // nerede olduğu yazılıyor. ?>
          <form method="post" action="<?= e(url("/danisanlar/{$client['id']}/onam/sozlu")) ?>"
                class="flex flex-wrap items-start gap-2">
            <?= Csrf::field() ?>
            <div>
              <label for="kunye" class="sr-only">Kaydın nerede saklandığı</label>
              <input id="kunye" type="text" name="kunye" maxlength="200"
                     class="field text-sm" placeholder="Kayıt nerede duruyor? (isteğe bağlı)">
              <p class="field-hint mt-1">
                Yalnız dosyanın yerini yazın (ör. “onam klasörü, 02.08 ses kaydı”).
                Buraya klinik içerik yazılmaz.
              </p>
            </div>
            <button class="btn btn-quiet">Sözlü onam alındı</button>
          </form>
        </div>
      <?php endif; ?>
    </div>
  </section>
<?php endif; ?>

<?php if (!empty($consentLink)): ?>
  <?php
  // Bağlantı e-posta gitmiş olsa bile gösteriliyor: bu akışın asıl kanalı
  // WhatsApp ve panelde WhatsApp gönderimi yok (bkz. Consent::share).
  //
  // Kutuda üç yol var ve üçü de aynı adresi taşıyor. Sıralama, kurumda
  // gerçekten olan sıra: numara kayıtlıysa WhatsApp tek tık, e-posta adresi
  // varsa posta programı, ikisi de olmazsa kopyala. Hazır metin hepsinde aynı
  // (bkz. Consent::message) — çıplak bir bağlantı "bu da ne" diye bakılıp
  // tıklanmıyor, bağlantının yanına ne olduğu yazılmalı.
  $consentText = Panel\Consent::message(
      Panel\Consent::firstName((string) $client['full_name']),
      $consentLink['url']
  );
  ?>
  <section class="sheet mb-4">
    <div class="sheet-body">
      <p class="text-sm text-ink"><?= e($consentLink['name']) ?> için onam formu bağlantısı</p>
      <p class="text-sm text-ink-muted mt-1 mb-3">
        <?= !empty($consentLink['sent'])
            ? 'E-posta gönderildi. Ulaşmazsa (spam filtresi, yanlış adres) bu bağlantıyı doğrudan iletebilirsiniz.'
            : 'Bu bağlantıyı bireye kendiniz iletin — WhatsApp ya da mesajla.' ?>
        <?= Panel\Consent::TTL_DAYS ?> gün geçerli ve tek kullanımlık. Yalnızca kişinin kendisine verin.
      </p>

      <?php // Gönderim değil, kestirme: sohbet hazır metinle açılıyor, gönder
            // düğmesine terapist basıyor ve son hâli okuyor. ?>
      <div class="flex flex-wrap gap-2 mb-3">
        <a href="<?= e(wa_url($client['phone'] ?? null, $consentText)) ?>"
           target="_blank" rel="noopener" class="btn btn-primary">
          WhatsApp'tan gönder
        </a>
        <?php if (($client['email'] ?? '') !== ''): ?>
          <a href="mailto:<?= e((string) $client['email']) ?>?subject=<?= e(rawurlencode('Onam formu — Mağusa Psikoloji')) ?>&amp;body=<?= e(rawurlencode($consentText)) ?>"
             class="btn btn-quiet">E-posta taslağı aç</a>
        <?php endif; ?>
        <?php // Kutuyu kapatmak bağlantıyı iptal ETMEZ; iptal "yenile" düğmesinin işi. ?>
        <form method="post" action="<?= e(url("/danisanlar/{$client['id']}/onam-baglantisi/kapat")) ?>" class="inline">
          <?= Csrf::field() ?>
          <button class="btn btn-quiet">İletildi, kutuyu kapat</button>
        </form>
      </div>

      <?php if (wa_number($client['phone'] ?? null) === ''): ?>
        <p class="field-hint mb-3">
          Kayıtta kullanılabilir bir telefon numarası yok; WhatsApp kişiyi
          size seçtirir. Metin ve bağlantı hazır gelir.
        </p>
      <?php endif; ?>

      <div class="flex gap-2">
        <label for="consentLinkUrl" class="sr-only">Onam formu bağlantısı</label>
        <input id="consentLinkUrl" type="text" readonly value="<?= e($consentLink['url']) ?>"
               class="field flex-1 min-w-0 text-xs font-mono text-ink-muted">
        <button type="button" data-copy="#consentLinkUrl" class="btn btn-quiet shrink-0">Kopyala</button>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php if ($manualInvite !== null): ?>
  <?php // E-posta ulaşmazsa bağlantı elden iletilir: tek kullanımlık, 48 saat geçerli. ?>
  <section class="sheet mb-4">
    <div class="sheet-body">
      <p class="text-sm text-ink"><?= e($manualInvite['name']) ?> için şifre belirleme bağlantısı</p>
      <p class="text-sm text-ink-muted mt-1 mb-3">
        <?= !empty($manualInvite['sent'])
            ? 'E-posta gönderildi. Ulaşmazsa (spam filtresi, yanlış adres) bu bağlantıyı doğrudan iletebilirsiniz.'
            : 'E-posta gönderilemedi; bu bağlantıyı bireye kendiniz iletin.' ?>
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
            <span class="chip chip-<?= e($consent['tone']) ?>"><?= e($consent['label']) ?></span>
            <?php if ($client['consent_at'] !== null): ?>
              <p class="text-xs text-ink-light mt-1 num">
                <?= e(dt($client['consent_at'])) ?> · sürüm <?= e((string) $client['consent_version']) ?>
              </p>
            <?php endif; ?>

            <?php
            // Kısa geçmiş: kim, ne zaman, hangi yolla. "Bu kayıtta onam neden
            // yok?" sorusunun cevabı çoğu zaman bir zamanlar VAR OLDUĞU ve
            // kaldırıldığıdır; geri alınanlar bu yüzden listede kalıyor.
            ?>
            <?php if ($consentHistory !== []): ?>
              <ul class="mt-2 space-y-1">
                <?php foreach ($consentHistory as $row): ?>
                  <li class="text-xs text-ink-light<?= $row['revoked_at'] !== null ? ' line-through' : '' ?>">
                    <?= e(Panel\Consent::methodLabel((string) $row['method'])) ?> ·
                    <span class="num"><?= e(dt((string) $row['approved_at'], 'd.m.Y')) ?></span>
                    <span class="num">· <?= e((string) $row['version']) ?></span>
                    <?php if ($row['recorded_by_name'] !== null): ?>
                      — <?= e((string) $row['recorded_by_name']) ?>
                    <?php endif; ?>
                    <?php if ($row['reference'] !== null): ?>
                      <span class="block text-ink-light"><?= e((string) $row['reference']) ?></span>
                    <?php endif; ?>
                    <?php if ($row['revoked_at'] !== null): ?>
                      <span class="block no-underline">geri alındı</span>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>

            <?php if ($consentPending !== null && $consent['key'] === 'eksik'): ?>
              <p class="text-xs text-ink-light mt-2">
                Bağlantı gönderildi, henüz onaylanmadı
                (<span class="num"><?= e(dt((string) $consentPending['expires_at'], 'd.m.Y')) ?></span> tarihine kadar geçerli).
                <?php // Adres yalnız üretildiği oturumda gösterilebiliyor: kayıtta
                      // jetonun özeti duruyor, kendisi değil (bkz. Consent::share).
                      // Ekranda kutu yoksa bunu söylemek gerekiyor — yoksa "az önce
                      // vardı" diye aranan bir şey aranıyor. ?>
                <?php if (empty($consentLink)): ?>
                  Adresi yeniden görmek için <strong>yenilemek</strong> gerekir; o an
                  yeni bir bağlantı üretilir ve iletilmiş olan geçersiz olur.
                <?php endif; ?>
              </p>
            <?php endif; ?>
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
