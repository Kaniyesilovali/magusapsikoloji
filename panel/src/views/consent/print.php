<?php
/** @var array $client @var string $text @var string $version @var string $title */
// Düzen kullanılmaz: çıktıda menü/kenar çubuğu olmasın diye tam sayfa burada kurulur.
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> — <?= e($client['full_name']) ?></title>
<link rel="stylesheet" href="<?= e(url('/assets/panel.css')) ?>">
<style>
  @page { margin: 20mm; }
  @media print {
    .no-print { display: none; }
    body { background: #fff; }
  }
</style>
</head>
<body class="font-sans bg-warm-secondary">

<div class="no-print bg-ink text-white px-4 py-3 flex flex-wrap items-center justify-between gap-3">
  <span class="text-sm">Açık rıza formu — <?= e($client['full_name']) ?></span>
  <span class="flex items-center gap-3">
    <button type="button" data-print class="bg-primary hover:bg-primary-hover text-white text-sm font-medium px-4 py-2 rounded-xl">
      Yazdır
    </button>
    <a href="<?= e(url("/danisanlar/{$client['id']}")) ?>" class="text-sm text-white/70 hover:text-white">Kayda dön</a>
  </span>
</div>

<main class="max-w-3xl mx-auto bg-white my-6 px-10 py-10 print:my-0 print:px-0 print:py-0">
  <header class="border-b border-warm-tertiary pb-4 mb-6">
    <h1 class="text-lg font-semibold text-ink">Mağusa Psikoloji</h1>
    <p class="text-sm text-ink-muted mt-0.5">Aydınlatma metni ve açık rıza beyanı</p>
    <p class="text-xs text-ink-light mt-2">Metin sürümü: <?= e($version) ?></p>
  </header>

  <article class="text-sm text-ink leading-relaxed whitespace-pre-wrap"><?= e($text) ?></article>

  <section class="mt-10 pt-6 border-t border-warm-tertiary text-sm text-ink">
    <dl class="grid grid-cols-2 gap-y-3 mb-10">
      <dt class="text-ink-muted">Ad Soyad</dt>
      <dd><?= e($client['full_name']) ?></dd>
      <dt class="text-ink-muted">Tarih</dt>
      <dd>……… / ……… / ……………</dd>
    </dl>

    <div class="grid grid-cols-2 gap-8">
      <div>
        <p class="text-ink-muted text-xs mb-10">Danışan imzası</p>
        <div class="border-t border-ink/40"></div>
      </div>
      <div>
        <p class="text-ink-muted text-xs mb-10">Kurum yetkilisi</p>
        <div class="border-t border-ink/40"></div>
      </div>
    </div>
  </section>
</main>

<script src="<?= e(url('/assets/panel.js')) ?>" defer></script>
</body>
</html>
