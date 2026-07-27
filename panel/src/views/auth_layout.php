<?php
/** @var string $content @var array $flashes @var string $title */
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title ?? 'Giriş') ?> — Mağusa Psikoloji</title>
<link rel="icon" href="/assets/images/favicon.png">
<link rel="stylesheet" href="<?= e(asset('/assets/panel.css')) ?>">
</head>
<?php // Giriş ekranı panelin koyu chrome rengini kaplar; giriş yapıldığında aynı
      // renk kenar çubuğuna çekilir. Kapıdan içeri girme hissi bundan geliyor. ?>
<body class="min-h-screen font-sans flex items-center justify-center px-4 py-12 bg-chrome">

<div class="w-full max-w-sm">
  <div class="mb-7">
    <p class="font-serif text-white text-lg">Mağusa Psikoloji</p>
    <p class="eyebrow text-white/40 mt-1">Yönetim paneli</p>
  </div>

  <?php foreach ($flashes as $flash): ?>
    <?php
    $tone = match ($flash['type']) {
        'success'          => 'note-ok',
        'error', 'warning' => 'note-stop',
        default            => 'note-info',
    };
    ?>
    <div class="note <?= $tone ?> mb-4"><?= e($flash['message']) ?></div>
  <?php endforeach; ?>

  <div class="sheet p-6">
    <?= $content ?>
  </div>

  <p class="mt-6">
    <a href="/" class="text-xs text-white/40 hover:text-white/70">← magusapsikoloji.com</a>
  </p>
</div>

<?php // Giriş ekranlarında betiğin tek işi şifre alanının "Göster" düğmesi. ?>
<script src="<?= e(asset('/assets/panel.js')) ?>" defer></script>
</body>
</html>
