<?php
/** @var array|null $client @var string $signedOn */
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
// onlar bireyin değil, formu basan psikoloğun okuduğu yer. Çeviri ayrı bir
// onam metni değil, aynı sürümün İngilizcesi — kâğıdın başında bunu söyleyen
// bir cümle basılıyor (bkz. Consent::currentTextEn).
$en = $lang === 'en';

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
  kaydedilmez.
</p>

<?php
// Metin, güncel sürüm DEĞİL, bireyin onayladığı sürüm olabilir. Bunu yalnız
// ekranda söylüyoruz: kâğıda geçen sürüm numarası zaten başlıkta duruyor ve
// masadaki kişiyi ilgilendiren şey okuduğu metnin önüne konmuş olması.
?>
<?php if (!empty($outdated)): ?>
  <p class="no-print max-w-3xl mx-auto mt-2 px-4 text-xs text-ink-muted">
    <strong>Dikkat:</strong> bu çıktı bireyin çevrimiçi onayladığı
    <span class="num"><?= e($version) ?></span> sürümünü basıyor, panelde kayıtlı
    güncel metni değil. Kişi okumadığı bir kâğıdı imzalamasın diye böyle.
    Güncel metne geçirmek isterseniz önce yeni bağlantı gönderip yeniden
    onaylatın.
  </p>
<?php endif; ?>
<?php if (!empty($missing) && !$en): ?>
  <p class="no-print max-w-3xl mx-auto mt-2 px-4 text-xs text-ink-muted">
    <strong>Dikkat:</strong> bireyin onayladığı sürümün metni arşivde
    bulunamadı (onay, sürüm arşivi kurulmadan önce alınmış olabilir). Kâğıt
    güncel metni basıyor — imzalatmadan önce iki metnin aynı olduğundan emin olun.
  </p>
<?php endif; ?>
<?php // Aynı eksik, İngilizce tarafta: o sürümün çevirisi hiç yazılmamış olabilir. ?>
<?php if (!empty($missing) && $en): ?>
  <p class="no-print max-w-3xl mx-auto mt-2 px-4 text-xs text-ink-muted">
    <strong>Dikkat:</strong> bireyin onayladığı sürümün İngilizce çevirisi yok.
    Kâğıt eldeki çeviriyi (<span class="num"><?= e($version) ?></span> sürümü)
    basıyor — imzalatmadan önce Türkçe formla karşılaştırın ya da Türkçe formu
    kullanın.
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

<?php
// Kâğıdın kendisi ortak parçada: panel çıktısı ile herkese açık sayfa
// (bkz. consent/public.php) aynı kâğıdı basmalı — sürüm numarasının verdiği
// söz, iki yerde iki farklı kâğıt basılırsa boşa çıkardı.
// Parça $text, $version, $lang, $name, $signedOn ve $online değişkenlerini
// buradaki kapsamdan okur.
require __DIR__ . '/_paper.php';
?>

<script src="<?= e(asset('/assets/panel.js')) ?>" defer></script>
</body>
</html>
