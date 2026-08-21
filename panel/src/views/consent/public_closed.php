<?php
/**
 * Herkese açık onam adresi, metin henüz yayımlanmamışken.
 *
 * Kurum kendi metnini panele kaydedene kadar sayfanın basacağı bir kâğıt yok:
 * koddaki taslakta köşeli parantezli alanlar (saklama süresi, iletişim adresi)
 * doldurulmamış ve o taslak, kurumun metni sanılarak okunurdu.
 *
 * Panelin hata sayfası ile check-in kabuğu burada kullanılmıyor: ikisi de
 * ziyaretçiyi panele ("Girişe dön") ya da ona ait olmayan bir bağlantıya
 * ("bu bağlantı yalnız sana ait") gönderiyor. Buraya gelen kişi siteden geldi,
 * siteye dönmeli.
 *
 * @var string $heading @var string $message
 */
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php // Yayımlanmamış bir adres indekslenmemeli; yayımlandığında etiket düşer. ?>
<meta name="robots" content="noindex, nofollow">
<title><?= e($heading) ?> — Mağusa Psikoloji</title>
<link rel="icon" href="/assets/images/favicon.png">
<link rel="stylesheet" href="<?= e(asset('/assets/panel.css')) ?>">
</head>
<body class="min-h-screen font-sans flex items-center justify-center px-4 bg-warm-secondary">
  <div class="text-center max-w-sm">
    <p class="font-serif text-lg text-ink">Mağusa Psikoloji</p>
    <h1 class="page-title mt-3"><?= e($heading) ?></h1>
    <p class="text-sm text-ink-muted mt-2 leading-relaxed"><?= e($message) ?></p>
    <a href="/iletisim.html" class="btn btn-primary mt-6">Bize ulaşın</a>
  </div>
</body>
</html>
