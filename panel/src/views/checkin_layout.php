<?php
/** @var string $content @var array $flashes @var string $title */

// Check-in düzeni panelin kendi chrome'unu KULLANMAZ: burada gezinen bir
// yönetici değil, telefonunda bir bağlantıya dokunmuş bir görüşmeci var. Menü,
// kenar çubuğu, "yönetim paneli" ibaresi — hepsi yanlış yere gelmiş hissi verir.
// Sayfada tek bir şey yapılabiliyor, o yüzden sayfada tek bir şey var.
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title ?? 'Check-in') ?> — Mağusa Psikoloji</title>
<link rel="icon" href="/assets/images/favicon.png">
<link rel="stylesheet" href="<?= e(asset('/assets/panel.css')) ?>">
</head>
<body class="min-h-screen font-sans px-4 py-8 sm:py-12">

<div class="w-full max-w-md mx-auto">
  <p class="font-serif text-lg text-ink">Mağusa Psikoloji</p>

  <?php foreach ($flashes as $flash): ?>
    <?php
    $tone = match ($flash['type']) {
        'success'          => 'note-ok',
        'error', 'warning' => 'note-stop',
        default            => 'note-info',
    };
    ?>
    <div class="note <?= $tone ?> mt-5"><?= e($flash['message']) ?></div>
  <?php endforeach; ?>

  <div class="sheet mt-5">
    <?= $content ?>
  </div>

  <p class="mt-5 text-xs text-ink-light">
    Bu bağlantı yalnız sana ait; başkasıyla paylaşmayın.
  </p>
</div>

<?php // Kaydırıcıların yanındaki sayıyı güncelleyen tek betik. ?>
<script src="<?= e(asset('/assets/panel.js')) ?>" defer></script>
</body>
</html>
