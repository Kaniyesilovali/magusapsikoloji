<?php /** @var int $code @var string $title @var string $message @var bool $loggedIn */ ?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= (int) $code ?> — <?= e($title) ?></title>
<link rel="stylesheet" href="<?= e(asset('/assets/panel.css')) ?>">
</head>
<body class="min-h-screen font-sans flex items-center justify-center px-4 bg-warm-secondary">
  <div class="text-center max-w-sm">
    <p class="eyebrow num text-base"><?= (int) $code ?></p>
    <h1 class="page-title mt-2"><?= e($title) ?></h1>
    <?php if ($message !== ''): ?>
      <p class="text-sm text-ink-muted mt-2 leading-relaxed"><?= e($message) ?></p>
    <?php endif; ?>
    <a href="<?= e(url($loggedIn ? '/' : '/giris')) ?>" class="btn btn-primary mt-6">
      <?= $loggedIn ? 'Panele dön' : 'Girişe dön' ?>
    </a>
  </div>
</body>
</html>
