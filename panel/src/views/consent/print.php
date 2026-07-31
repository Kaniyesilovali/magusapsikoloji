<?php
/** @var array $client @var string $text @var string $version @var string $title */
// Düzen kullanılmaz: çıktıda menü/kenar çubuğu olmasın diye tam sayfa burada kurulur.
// Panel kabuğu (yaprak/rozet) burada bilerek yok — bu bir kâğıt, bir ekran değil.
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> — <?= e($client['full_name']) ?></title>
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
  <span class="text-sm">Onam formu — <span class="person"><?= e($client['full_name']) ?></span></span>
  <span class="flex items-center gap-4">
    <button type="button" data-print class="btn btn-primary">Yazdır</button>
    <a href="<?= e(url("/danisanlar/{$client['id']}")) ?>" class="text-sm text-white/70 hover:text-white">Kayda dön</a>
  </span>
</div>

<main class="max-w-3xl mx-auto bg-white my-6 px-10 py-10 print:my-0 print:px-0 print:py-0">
  <header class="border-b border-warm-tertiary pb-4 mb-6">
    <h1 class="font-serif text-lg font-medium text-ink">Mağusa Psikoloji</h1>
    <p class="text-sm text-ink-muted mt-0.5">Bilgilendirilmiş onam formu</p>
    <p class="eyebrow mt-2">Metin sürümü <span class="num"><?= e($version) ?></span></p>
  </header>

  <article class="text-sm text-ink leading-relaxed whitespace-pre-wrap"><?= e($text) ?></article>

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
    <dl class="grid grid-cols-2 gap-y-3 mb-10">
      <dt class="text-ink-muted">Danışan İsim-Soyisim</dt>
      <dd class="person"><?= e($client['full_name']) ?></dd>
      <dt class="text-ink-muted">Tarih</dt>
      <dd>……… / ……… / ……………</dd>
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
