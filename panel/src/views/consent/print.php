<?php
/** @var array|null $client @var array $terms */
/** @var string $text @var string $version @var string $title */
// Düzen kullanılmaz: çıktıda menü/kenar çubuğu olmasın diye tam sayfa burada kurulur.
// Panel kabuğu (yaprak/rozet) burada bilerek yok — bu bir kâğıt, bir ekran değil.
//
// Sayfanın üstündeki doldurulabilir alanlar (.fill) ekranda doldurulup öyle
// basılıyor: tarayıcı input'un o anki değerini yazdırır. Kaydedilmiyorlar —
// bu kâğıdın kaydı imzalı hâlidir, panel değil. Kaydı olmayan kişi için ad da
// böyle bir alandır (bkz. ConsentController::printBlank).
$name = $client === null ? '' : (string) $client['full_name'];
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> — <?= $name !== '' ? e($name) : 'boş form' ?></title>
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
    Onam formu —
    <?php if ($name !== ''): ?>
      <span class="person"><?= e($name) ?></span>
    <?php else: ?>
      <span class="text-white/70">kaydı olmayan kişi için boş form</span>
    <?php endif; ?>
  </span>
  <span class="flex items-center gap-4">
    <button type="button" data-print class="btn btn-primary">Yazdır</button>
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
  kaydedilmez. Seans koşulları bu görüşmeciye göre değişirse burada düzeltin —
  metnin kendisi herkeste aynı kalır.
</p>

<main class="max-w-3xl mx-auto bg-white my-6 px-10 py-10 print:my-0 print:px-0 print:py-0">
  <header class="border-b border-warm-tertiary pb-4 mb-6">
    <h1 class="font-serif text-lg font-medium text-ink">Mağusa Psikoloji</h1>
    <p class="text-sm text-ink-muted mt-0.5">Bilgilendirilmiş onam formu</p>
    <p class="eyebrow mt-2">Metin sürümü <span class="num"><?= e($version) ?></span></p>
  </header>

  <article class="text-sm text-ink leading-relaxed whitespace-pre-wrap"><?= e($text) ?></article>

  <?php
  // Seans koşulları: metnin dışında duran tek sayısal bölüm. İçeride olsalardı
  // her ücret değişikliği metni — dolayısıyla sürümü — değiştirirdi ve o güne
  // kadar imzalanmış formların bağlandığı sürüm anlamını yitirirdi.
  // Değerler ConsentController::terms() ile geliyor: en son yazılmış ücret,
  // terapistin varsayılan seans süresi. İkisi de burada düzeltilebilir.
  ?>
  <section class="mt-8 border border-ink/25 rounded-sm px-6 py-5 text-sm text-ink">
    <p class="font-medium underline underline-offset-4 mb-5">Seans Koşulları</p>
    <div class="grid grid-cols-3 gap-x-8 gap-y-2">
      <label for="fee" class="text-ink-muted">Seans ücreti</label>
      <label for="minutes" class="text-ink-muted">Seans süresi</label>
      <label for="frequency" class="text-ink-muted">Görüşme sıklığı</label>

      <input id="fee" type="text" class="fill num" value="<?= e($terms['fee']) ?>" autocomplete="off">
      <input id="minutes" type="text" class="fill num" value="<?= e((string) $terms['minutes']) ?> dakika" autocomplete="off">
      <input id="frequency" type="text" class="fill" value="<?= e($terms['frequency']) ?>" autocomplete="off">
    </div>
  </section>

  <?php
  // Aile yakını, metnin içinde söz verilen şeydir: "olağandışı bir durumda
  // ulaşabileceğimiz biri". Boş kutu olarak basılıyor, panelden doldurulmuyor —
  // bu bilgiyi danışan masada kendi eliyle yazar, kurum ondan önce bilmez.
  ?>
  <section class="mt-8 border border-ink/25 rounded-sm px-6 py-5 text-sm text-ink">
    <p class="font-medium underline underline-offset-4 mb-5">Aile Yakını Bilgisi</p>
    <div class="grid grid-cols-2 gap-x-8 gap-y-5">
      <p>İsim-Soyisim:</p>
      <p>Yakınlık:</p>
      <p>Telefon Numarası:</p>
      <p>Adres:</p>
    </div>
  </section>

  <section class="mt-10 pt-6 border-t border-warm-tertiary text-sm text-ink">
    <dl class="grid grid-cols-2 gap-x-8 gap-y-3 mb-10">
      <dt class="text-ink-muted self-center">Danışan İsim-Soyisim</dt>
      <dd>
        <?php if ($name !== ''): ?>
          <span class="person"><?= e($name) ?></span>
        <?php else: ?>
          <?php // Kaydı yoksa ad da kâğıdın üstünde yazılır — elle ya da klavyeyle. ?>
          <label for="clientName" class="sr-only">Danışan isim-soyisim</label>
          <input id="clientName" type="text" class="fill person" value="" autocomplete="off">
        <?php endif; ?>
      </dd>
      <dt class="text-ink-muted self-center">Tarih</dt>
      <dd>
        <label for="signedOn" class="sr-only">Tarih</label>
        <input id="signedOn" type="text" class="fill num" value="<?= e($terms['date']) ?>" autocomplete="off">
      </dd>
    </dl>

    <?php // Karşılıklı bir anlaşma: metin öyle diyor, kâğıt da öyle imzalanıyor. ?>
    <div class="grid grid-cols-2 gap-8">
      <div>
        <p class="eyebrow mb-10">Danışan imzası</p>
        <div class="border-t border-ink/40"></div>
      </div>
      <div>
        <p class="eyebrow mb-10">Psikolog imzası</p>
        <div class="border-t border-ink/40"></div>
      </div>
    </div>
  </section>
</main>

<script src="<?= e(asset('/assets/panel.js')) ?>" defer></script>
</body>
</html>
