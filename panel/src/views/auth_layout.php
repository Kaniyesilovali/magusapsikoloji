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
<link rel="stylesheet" href="<?= e(url('/assets/panel.css')) ?>">
</head>
<body class="min-h-screen font-sans flex items-center justify-center px-4 py-12 bg-warm">

<div class="w-full max-w-sm">
  <div class="text-center mb-8">
    <p class="font-semibold text-ink text-lg">Mağusa Psikoloji</p>
    <p class="text-sm text-ink-light mt-0.5">Yönetim Paneli</p>
  </div>

  <?php foreach ($flashes as $flash): ?>
    <?php
    $tone = match ($flash['type']) {
        'success' => 'bg-primary/10 text-primary-dark border-primary/25',
        'error'   => 'bg-accent/10 text-accent-dark border-accent/30',
        'warning' => 'bg-amber-50 text-amber-900 border-amber-200',
        default   => 'bg-white text-ink-muted border-warm-tertiary',
    };
    ?>
    <div class="mb-4 px-4 py-3 rounded-xl border text-sm <?= $tone ?>"><?= e($flash['message']) ?></div>
  <?php endforeach; ?>

  <div class="bg-white border border-warm-tertiary rounded-2xl p-6 shadow-sm">
    <?= $content ?>
  </div>

  <p class="text-center text-xs text-ink-light mt-6">
    <a href="/" class="hover:text-ink-muted">← magusapsikoloji.com</a>
  </p>
</div>

</body>
</html>
