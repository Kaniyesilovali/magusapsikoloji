<?php
/** @var array|null $client @var array $terms */
/** @var string $text @var string $version @var string $title @var string $lang */
/** @var array|null $online @var bool $outdated @var bool $missing */
/** @var bool $draftEn @var bool $staleEn @var string $versionEn */
// Düzen kullanılmaz: çıktıda menü/kenar çubuğu olmasın diye tam sayfa burada kurulur.
// Panel kabuğu (yaprak/rozet) burada bilerek yok — bu bir kâğıt, bir ekran değil.
//
// Sayfanın üstündeki doldurulabilir alanlar (.fill) ekranda doldurulup öyle
// basılıyor: tarayıcı input'un o anki değerini yazdırır. Kaydedilmiyorlar —
// bu kâğıdın kaydı imzalı hâlidir, panel değil. Kaydı olmayan kişi için ad da
// böyle bir alandır (bkz. ConsentController::printBlank).
$name = $client === null ? '' : (string) $client['full_name'];

// Dil YALNIZ kâğıdı değiştiriyor. Üstteki şerit ve uyarılar Türkçe kalıyor:
// onlar danışanın değil, formu basan psikoloğun okuduğu yer. Çeviri ayrı bir
// onam metni değil, aynı sürümün İngilizcesi — kâğıdın başında bunu söyleyen
// bir cümle basılıyor (bkz. Consent::currentTextEn).
$en = $lang === 'en';

$paper = $en
    ? [
        'subtitle'    => 'Informed consent form',
        'version'     => 'Text version',
        'origin'      => 'This is an English translation of the Turkish informed consent form, version '
                       . $version . '. In the event of any discrepancy, the Turkish text prevails.',
        'terms'       => 'Session Terms',
        'fee'         => 'Session fee',
        'duration'    => 'Session length',
        'frequency'   => 'Meeting frequency',
        'minutes'     => 'minutes',
        'kin'         => 'Family Contact',
        'kinName'     => 'Name-Surname:',
        'kinRelation' => 'Relationship:',
        'kinPhone'    => 'Phone number:',
        'kinAddress'  => 'Address:',
        'clientName'  => 'Client Name-Surname',
        'date'        => 'Date',
        'clientSign'  => 'Client signature',
        'psySign'     => 'Psychologist signature',
    ]
    : [
        'subtitle'    => 'Bilgilendirilmiş onam formu',
        'version'     => 'Metin sürümü',
        'origin'      => '',
        'terms'       => 'Seans Koşulları',
        'fee'         => 'Seans ücreti',
        'duration'    => 'Seans süresi',
        'frequency'   => 'Görüşme sıklığı',
        'minutes'     => 'dakika',
        'kin'         => 'Aile Yakını Bilgisi',
        'kinName'     => 'İsim-Soyisim:',
        'kinRelation' => 'Yakınlık:',
        'kinPhone'    => 'Telefon Numarası:',
        'kinAddress'  => 'Adres:',
        'clientName'  => 'Danışan İsim-Soyisim',
        'date'        => 'Tarih',
        'clientSign'  => 'Danışan imzası',
        'psySign'     => 'Psikolog imzası',
    ];

// Öteki dilin adresi — aynı kâğıt, aynı kişi. Boş formda da çalışıyor.
$other = url(current_path() . ($en ? '' : '?dil=en'));
?>
<!doctype html>
<html lang="<?= $en ? 'en' : 'tr' ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?><?= $en ? ' (EN)' : '' ?> — <?= $name !== '' ? e($name) : 'boş form' ?></title>
<link rel="stylesheet" href="<?= e(asset('/assets/panel.css')) ?>">
<style>
  @page { margin: 20mm; }
  @media print {
    .no-print { display: none; }
    body { background: #fff; }
  }
</style>
</head>
<body class="font-sans">

<div class="no-print bg-chrome text-white px-4 py-3 flex flex-wrap items-center justify-between gap-3">
  <span class="text-sm">
    Onam formu<?= $en ? ' (İngilizce)' : '' ?> —
    <?php if ($name !== ''): ?>
      <span class="person"><?= e($name) ?></span>
    <?php else: ?>
      <span class="text-white/70">kaydı olmayan kişi için boş form</span>
    <?php endif; ?>
  </span>
  <span class="flex items-center gap-4">
    <button type="button" data-print class="btn btn-primary">Yazdır</button>
    <?php // Dil kararı masada veriliyor: kâğıda bakarken tek tıkla öteki dile geçilir. ?>
    <a href="<?= e($other) ?>" class="text-sm text-white/70 hover:text-white">
      <?= $en ? 'Türkçe forma dön' : 'English version' ?>
    </a>
    <?php if ($client !== null): ?>
      <a href="<?= e(url("/danisanlar/{$client['id']}")) ?>" class="text-sm text-white/70 hover:text-white">Kayda dön</a>
    <?php else: ?>
      <a href="<?= e(url('/danisanlar/yeni')) ?>" class="text-sm text-white/70 hover:text-white">Kayıt aç</a>
    <?php endif; ?>
  </span>
</div>

<?php // Yazdırmadan önce doldurulacak yerleri bir kez söyleyen satır; kâğıda geçmez. ?>
<p class="no-print max-w-3xl mx-auto mt-4 px-4 text-xs text-ink-muted">
  Aşağıdaki çizgili alanlar doldurulabilir: yazdıkça kâğıda geçer, hiçbir yere
  kaydedilmez. Seans koşulları bu bireye göre değişirse burada düzeltin —
  metnin kendisi herkeste aynı kalır.
</p>

<?php
// Metin, güncel sürüm DEĞİL, danışanın onayladığı sürüm olabilir. Bunu yalnız
// ekranda söylüyoruz: kâğıda geçen sürüm numarası zaten başlıkta duruyor ve
// masadaki kişiyi ilgilendiren şey okuduğu metnin önüne konmuş olması.
?>
<?php if (!empty($outdated)): ?>
  <p class="no-print max-w-3xl mx-auto mt-2 px-4 text-xs text-ink-muted">
    <strong>Dikkat:</strong> bu çıktı danışanın çevrimiçi onayladığı
    <span class="num"><?= e($version) ?></span> sürümünü basıyor, panelde kayıtlı
    güncel metni değil. Kişi okumadığı bir kâğıdı imzalamasın diye böyle.
    Güncel metne geçirmek isterseniz önce yeni bağlantı gönderip yeniden
    onaylatın.
  </p>
<?php endif; ?>
<?php if (!empty($missing)): ?>
  <p class="no-print max-w-3xl mx-auto mt-2 px-4 text-xs text-ink-muted">
    <strong>Dikkat:</strong> danışanın onayladığı sürümün metni arşivde
    bulunamadı (onay, sürüm arşivi kurulmadan önce alınmış olabilir). Kâğıt
    güncel metni basıyor — imzalatmadan önce iki metnin aynı olduğundan emin olun.
  </p>
<?php endif; ?>

<?php
// Çevirinin iki kusuru, yalnız İngilizce çıktıda ve yalnız ekranda. İkisi de
// çıktıyı engellemiyor: masada bekleyen biri varken kâğıdı hiç vermemek değil,
// ne verdiğini bilerek vermek doğru olan.
?>
<?php if (!empty($draftEn)): ?>
  <p class="no-print max-w-3xl mx-auto mt-2 px-4 text-xs text-ink-muted">
    <strong>Dikkat:</strong> İngilizce metin panelde kaydedilmemiş; bu kâğıt
    paneldeki <strong>taslak çeviriyi</strong> basıyor. Köşeli parantezli alanlar
    (saklama süresi, iletişim adresi) doldurulmamış olabilir —
    <a href="<?= e(url('/onam-formu')) ?>" class="underline">Onam formu</a>
    ekranından çeviriyi gözden geçirip kaydedin.
  </p>
<?php endif; ?>
<?php if (!empty($staleEn)): ?>
  <p class="no-print max-w-3xl mx-auto mt-2 px-4 text-xs text-ink-muted">
    <strong>Dikkat:</strong> İngilizce çeviri <span class="num"><?= e($versionEn) ?></span>
    sürümüne ait, Türkçe metin ise <span class="num"><?= e($version) ?></span> sürümünde.
    Bu kâğıt Türkçesinden farklı bir metin olabilir;
    <a href="<?= e(url('/onam-formu')) ?>" class="underline">çeviriyi güncelleyin</a>
    ya da Türkçe formu kullanın.
  </p>
<?php endif; ?>

<main class="max-w-3xl mx-auto bg-white my-6 px-10 py-10 print:my-0 print:px-0 print:py-0">
  <header class="border-b border-warm-tertiary pb-4 mb-6">
    <h1 class="font-serif text-lg font-medium text-ink">Mağusa Psikoloji</h1>
    <p class="text-sm text-ink-muted mt-0.5"><?= e($paper['subtitle']) ?></p>
    <p class="eyebrow mt-2"><?= e($paper['version']) ?> <span class="num"><?= e($version) ?></span></p>
  </header>

  <?php
  // Çevirinin künyesi — kâğıda BASILIYOR. İngilizce kâğıdı imzalayan kişi,
  // imzaladığı şeyin hangi metnin çevirisi olduğunu ve bir anlaşmazlıkta
  // hangisinin geçerli sayılacağını kâğıdın kendisinden okuyabilmeli.
  ?>
  <?php if ($paper['origin'] !== ''): ?>
    <p class="text-xs text-ink-muted italic mb-6"><?= e($paper['origin']) ?></p>
  <?php endif; ?>

  <?php
  // Çevrimiçi onay künyesi — kâğıda BASILAN bir satır.
  //
  // Bu kutu, seansta okumaya harcanan zamanı ortadan kaldıran şeyin kendisi:
  // kâğıt, metnin ne zaman ve hangi sürümüyle okunduğunu söylüyor. Altındaki
  // imza artık bir okuma değil, bir doğrulama. Söylenmeseydi psikolog her
  // seansta yeniden "okudunuz mu?" diye sormak ya da hiç sormamak arasında
  // kalırdı ve ikisi de kötüydü.
  ?>
  <?php if (!empty($online)): ?>
    <section class="mb-6 border border-ink/25 rounded-sm px-6 py-4 text-sm text-ink">
      <?php if ($en): ?>
        <p>
          You read and approved version <span class="num"><?= e((string) $online['version']) ?></span>
          <?php // Tarih kâğıdın dilinde: imza satırındaki tarihle aynı biçim. ?>
          of this text online on <span class="num"><?= e(dt((string) $online['approved_at'], 'j F Y')) ?></span>.
          The signature below confirms that approval in session.
        </p>
      <?php else: ?>
        <p>
          Bu metnin <span class="num"><?= e((string) $online['version']) ?></span> sürümünü
          <span class="num"><?= e(dt((string) $online['approved_at'])) ?></span> tarihinde
          çevrimiçi olarak okuyup onayladınız. Aşağıdaki imza, o onayın seansta
          doğrulanmasıdır.
        </p>
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <article class="text-sm text-ink leading-relaxed whitespace-pre-wrap"><?= e($text) ?></article>

  <?php
  // Seans koşulları: metnin dışında duran tek sayısal bölüm. İçeride olsalardı
  // her ücret değişikliği metni — dolayısıyla sürümü — değiştirirdi ve o güne
  // kadar imzalanmış formların bağlandığı sürüm anlamını yitirirdi.
  // Değerler ConsentController::terms() ile geliyor: en son yazılmış ücret,
  // terapistin varsayılan seans süresi. İkisi de burada düzeltilebilir.
  ?>
  <section class="mt-8 border border-ink/25 rounded-sm px-6 py-5 text-sm text-ink">
    <p class="font-medium underline underline-offset-4 mb-5"><?= e($paper['terms']) ?></p>
    <div class="grid grid-cols-3 gap-x-8 gap-y-2">
      <label for="fee" class="text-ink-muted"><?= e($paper['fee']) ?></label>
      <label for="minutes" class="text-ink-muted"><?= e($paper['duration']) ?></label>
      <label for="frequency" class="text-ink-muted"><?= e($paper['frequency']) ?></label>

      <input id="fee" type="text" class="fill num" value="<?= e($terms['fee']) ?>" autocomplete="off">
      <input id="minutes" type="text" class="fill num" value="<?= e((string) $terms['minutes']) ?> <?= e($paper['minutes']) ?>" autocomplete="off">
      <input id="frequency" type="text" class="fill" value="<?= e($terms['frequency']) ?>" autocomplete="off">
    </div>
  </section>

  <?php
  // Aile yakını, metnin içinde söz verilen şeydir: "olağandışı bir durumda
  // ulaşabileceğimiz biri". Boş kutu olarak basılıyor, panelden doldurulmuyor —
  // bu bilgiyi danışan masada kendi eliyle yazar, kurum ondan önce bilmez.
  ?>
  <section class="mt-8 border border-ink/25 rounded-sm px-6 py-5 text-sm text-ink">
    <p class="font-medium underline underline-offset-4 mb-5"><?= e($paper['kin']) ?></p>
    <div class="grid grid-cols-2 gap-x-8 gap-y-5">
      <p><?= e($paper['kinName']) ?></p>
      <p><?= e($paper['kinRelation']) ?></p>
      <p><?= e($paper['kinPhone']) ?></p>
      <p><?= e($paper['kinAddress']) ?></p>
    </div>
  </section>

  <section class="mt-10 pt-6 border-t border-warm-tertiary text-sm text-ink">
    <dl class="grid grid-cols-2 gap-x-8 gap-y-3 mb-10">
      <dt class="text-ink-muted self-center"><?= e($paper['clientName']) ?></dt>
      <dd>
        <?php if ($name !== ''): ?>
          <span class="person"><?= e($name) ?></span>
        <?php else: ?>
          <?php // Kaydı yoksa ad da kâğıdın üstünde yazılır — elle ya da klavyeyle. ?>
          <label for="clientName" class="sr-only"><?= e($paper['clientName']) ?></label>
          <input id="clientName" type="text" class="fill person" value="" autocomplete="off">
        <?php endif; ?>
      </dd>
      <dt class="text-ink-muted self-center"><?= e($paper['date']) ?></dt>
      <dd>
        <label for="signedOn" class="sr-only"><?= e($paper['date']) ?></label>
        <input id="signedOn" type="text" class="fill num" value="<?= e($terms['date']) ?>" autocomplete="off">
      </dd>
    </dl>

    <?php // Karşılıklı bir anlaşma: metin öyle diyor, kâğıt da öyle imzalanıyor. ?>
    <div class="grid grid-cols-2 gap-8">
      <div>
        <p class="eyebrow mb-10"><?= e($paper['clientSign']) ?></p>
        <div class="border-t border-ink/40"></div>
      </div>
      <div>
        <p class="eyebrow mb-10"><?= e($paper['psySign']) ?></p>
        <div class="border-t border-ink/40"></div>
      </div>
    </div>
  </section>
</main>

<script src="<?= e(asset('/assets/panel.js')) ?>" defer></script>
</body>
</html>
