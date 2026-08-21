<?php
/**
 * Herkese açık onam formu — https://magusapsikoloji.com/onam-formu
 *
 * Panel içindeki çıktının (consent/print.php) ikizi, iki farkla:
 *   • Üstünde panel yok. Buraya gelen kişi bir yönetici değil, kâğıdı
 *     seanstan önce okumak isteyen biri; "kayda dön" gibi bağlantılar onu
 *     giriş ekranına götürürdü.
 *   • Hiçbir şey kaydetmez. Sayfa jeton taşımıyor, kimin okuduğunu bilmiyor
 *     ve bilmemeli — bu yüzden en altta tik de yok. Kaydı tutulan onam,
 *     bireye özel `/onam/{jeton}` bağlantısıyla verilir (bkz. Consent).
 *
 * Metin doğrudan panelin veritabanından okunuyor: onam ekranında Kaydet'e
 * basıldığı an burası da değişir. İkinci bir yayımlama adımı bilinçli olarak
 * yok — iki kopya olsaydı biri eskir ve sürüm numarası yanlış bir metnin
 * üstünde dururdu.
 *
 * @var string      $text     yayımlanan metin (o dilin, o sürümün metni)
 * @var string      $version  kâğıda basılan sürüm numarası
 * @var string      $lang     'tr' | 'en'
 * @var string      $name     daima '' — kâğıdın üstündeki ad elle doldurulur
 * @var string      $signedOn imza tarihinin başlangıç değeri
 * @var array|null  $online   daima null — burada kimse tanınmıyor
 * @var string      $urlTr    Türkçe sayfanın tam adresi (canonical/hreflang)
 * @var string      $urlEn    İngilizce sayfanın tam adresi
 * @var bool        $hasEn    çeviri yayımlanmış mı (taslak çeviri yayımlanmaz)
 * @var string|null $effective bu sürümün yürürlük tarihi (yoksa null)
 */
$en = $lang === 'en';

$page = $en
    ? [
        'title'   => 'Informed consent form | Famagusta Psychology Centre',
        'desc'    => 'The current text of the informed consent form signed at Famagusta Psychology Centre: '
                   . 'the therapy process, confidentiality, how personal information is kept, and voluntariness.',
        'lead'    => 'This is the current text of the informed consent form signed at our centre. '
                   . 'You can read it here before your first session and print it if you wish.',
        'note'    => 'Nothing is recorded on this page: reading it is not consent and no appointment is booked here. '
                   . 'The form is signed in session. If we sent you a personal consent link, please use that link instead.',
        'print'   => 'Print',
        'other'   => 'Türkçe metin',
        'foot'    => 'Text version',
        'since'   => 'in effect since',
        'contact' => 'Contact us',
        'home'    => 'Famagusta Psychology Centre',
    ]
    : [
        'title'   => 'Bilgilendirilmiş Onam Formu | Mağusa Psikoloji Merkezi',
        'desc'    => 'Mağusa Psikoloji Merkezi\'nde imzalanan bilgilendirilmiş onam formunun güncel metni: '
                   . 'psikoterapi süreci, gizlilik, kişisel bilgilerin kaydı ve gönüllülük.',
        'lead'    => 'Merkezimizde imzalanan bilgilendirilmiş onam formunun güncel metni. '
                   . 'İlk görüşmenizden önce buradan okuyabilir, isterseniz çıktısını alabilirsiniz.',
        'note'    => 'Bu sayfada hiçbir kayıt tutulmaz: metni okumak onam vermek değildir ve buradan randevu '
                   . 'oluşmaz. Form seansta imzalanır. Size özel bir onam bağlantısı gönderildiyse onayınızı '
                   . 'o bağlantıdan verin.',
        'print'   => 'Yazdır',
        'other'   => 'English version',
        'foot'    => 'Metin sürümü',
        'since'   => 'yürürlük',
        'contact' => 'Bize ulaşın',
        'home'    => 'Mağusa Psikoloji Merkezi',
    ];

// Sitenin kendi adres düzeni: TR kökte, EN /en/ altında (bkz. content/).
$homeUrl    = $en ? '/en/' : '/';
$contactUrl = $en ? '/en/contact.html' : '/iletisim.html';
?>
<!doctype html>
<html lang="<?= $en ? 'en' : 'tr' ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($page['title']) ?></title>
<meta name="description" content="<?= e($page['desc']) ?>">
<?php
// Adres iki kapıdan da açılıyor (/onam-formu ve /onam-formu?dil=en), kanonik
// tek: dil başına bir sayfa. Çeviri yayımlanmamışsa İngilizce bir adres yokmuş
// gibi davranılıyor — olmayan bir sayfayı hreflang ile duyurmak, arama
// motoruna bulamayacağı bir söz vermek olurdu.
?>
<link rel="canonical" href="<?= e($en ? $urlEn : $urlTr) ?>">
<link rel="alternate" hreflang="tr" href="<?= e($urlTr) ?>">
<?php if ($hasEn): ?>
  <link rel="alternate" hreflang="en" href="<?= e($urlEn) ?>">
<?php endif; ?>
<link rel="alternate" hreflang="x-default" href="<?= e($urlTr) ?>">
<link rel="icon" href="/assets/images/favicon.png">
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

<?php // Şerit yazdırılmaz: kâğıtta yalnız formun kendisi kalır. ?>
<div class="no-print bg-chrome text-white px-4 py-3 flex flex-wrap items-center justify-between gap-3">
  <a href="<?= e($homeUrl) ?>" class="font-serif text-lg"><?= e($page['home']) ?></a>
  <span class="flex items-center gap-4">
    <button type="button" data-print class="btn btn-primary"><?= e($page['print']) ?></button>
    <?php if ($hasEn): ?>
      <a href="<?= e($en ? $urlTr : $urlEn) ?>" class="text-sm text-white/70 hover:text-white">
        <?= e($page['other']) ?>
      </a>
    <?php endif; ?>
    <a href="<?= e($contactUrl) ?>" class="text-sm text-white/70 hover:text-white"><?= e($page['contact']) ?></a>
  </span>
</div>

<?php
// Sayfanın ne OLMADIĞINI söyleyen iki cümle. Panelde onam, bireye özel bir
// bağlantıyla ve kaydı tutularak veriliyor; buradaki metin herkese açık ve
// kayıtsız. İkisi karışırsa "onayladım" diyen biri ile panelde onamı boş
// görünen kayıt karşı karşıya gelir.
?>
<div class="no-print max-w-3xl mx-auto mt-6 px-4">
  <p class="text-sm text-ink-muted"><?= e($page['lead']) ?></p>
  <p class="note note-info mt-3"><?= e($page['note']) ?></p>
</div>

<?php
// Kâğıdın kendisi ortak parçada — paneldeki çıktıyla aynı kâğıt olsun diye
// (bkz. consent/_paper.php). Ad ve tarih burada da doldurulabilir alandır:
// evinde yazdırıp gelen kişi kâğıdı hazır getirebilir.
require __DIR__ . '/_paper.php';
?>

<p class="no-print max-w-3xl mx-auto mb-10 px-4 text-xs text-ink-light">
  <?= e($page['foot']) ?> <span class="num"><?= e($version) ?></span><?php
    if ($effective !== null): ?> — <?= e($page['since']) ?>
    <span class="num"><?= e(dt($effective, $en ? 'j F Y' : 'd.m.Y')) ?></span><?php endif; ?>
</p>

<script src="<?= e(asset('/assets/panel.js')) ?>" defer></script>
</body>
</html>
