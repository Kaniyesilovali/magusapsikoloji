<?php /** @var int $code @var string $title @var string $message @var bool $loggedIn */ ?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= (int) $code ?> — <?= e($title) ?></title>
<link rel="stylesheet" href="<?= e(url('/assets/panel.css')) ?>">
</head>
<body class="min-h-screen font-sans flex items-center justify-center px-4 bg-warm">
  <div class="text-center max-w-sm">
    <p class="text-5xl font-semibold text-warm-tertiary"><?= (int) $code ?></p>
    <h1 class="text-lg font-semibold text-ink mt-3"><?= e($title) ?></h1>
    <?php if ($message !== ''): ?>
      <p class="text-sm text-ink-muted mt-2 leading-relaxed"><?= e($message) ?></p>
    <?php endif; ?>
    <a href="<?= e(url($loggedIn ? '/' : '/giris')) ?>"
       class="inline-block mt-6 bg-primary hover:bg-primary-hover text-white text-sm font-medium px-5 py-2.5 rounded-xl transition-colors">
      <?= $loggedIn ? 'Panele dön' : 'Girişe dön' ?>
    </a>
  </div>
</body>
</html>
