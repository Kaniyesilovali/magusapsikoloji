<?php
/** @var string $content @var array|null $authUser @var array $flashes @var string $title */
use Panel\Csrf;
use Panel\Rbac;

// Bir satır için listelenen yetkilerden herhangi biri yeterli: "hepsini gör" ile
// "yalnız kendininkini gör" aynı ekrana çıkar, kapsamı ekranın kendisi daraltır.
$nav = array_values(array_filter([
    ['path' => '/',             'label' => 'Panel',            'permissions' => ['dashboard.view']],
    ['path' => '/randevular',   'label' => 'Randevular',       'permissions' => ['appointment.view.all', 'appointment.view.own']],
    ['path' => '/danisanlar',   'label' => 'Danışanlar',       'permissions' => ['client.view.all', 'client.view.own']],
    ['path' => '/musaitlik',    'label' => 'Müsaitlik',        'permissions' => ['availability.manage.all', 'availability.manage.own']],
    ['path' => '/kullanicilar', 'label' => 'Kullanıcılar',     'permissions' => ['user.view']],
    ['path' => '/icerik',       'label' => 'Site İçeriği',     'permissions' => ['content.manage']],
    ['path' => '/kvkk',         'label' => 'KVKK Metni',       'permissions' => ['consent.manage']],
    ['path' => '/kayitlar',     'label' => 'Sistem Kayıtları', 'permissions' => ['audit.view']],
    ['path' => '/profil',       'label' => 'Profilim',         'permissions' => ['profile.self']],
], static fn (array $item): bool => Rbac::canAny($authUser, $item['permissions'])));

$here = current_path();
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title ?? 'Panel') ?> — Mağusa Psikoloji</title>
<link rel="icon" href="/assets/images/favicon.png">
<link rel="stylesheet" href="<?= e(url('/assets/panel.css')) ?>">
</head>
<body class="min-h-screen font-sans">

<div class="lg:flex">

  <!-- Kenar çubuğu -->
  <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:min-h-screen bg-ink text-warm-secondary shrink-0">
    <div class="px-6 py-6 border-b border-white/10">
      <p class="font-semibold text-white leading-tight">Mağusa Psikoloji</p>
      <p class="text-xs text-white/50 mt-0.5">Yönetim Paneli</p>
    </div>
    <nav class="flex-1 p-3 space-y-1">
      <?php foreach ($nav as $item): ?>
        <?php $active = $here === $item['path'] || ($item['path'] !== '/' && str_starts_with($here, $item['path'])); ?>
        <a href="<?= e(url($item['path'])) ?>"
           class="block px-3 py-2 rounded-lg text-sm transition-colors <?= $active ? 'bg-primary text-white font-medium' : 'text-white/70 hover:bg-white/10 hover:text-white' ?>">
          <?= e($item['label']) ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="p-3 border-t border-white/10">
      <p class="px-3 text-sm text-white truncate"><?= e($authUser['full_name'] ?? '') ?></p>
      <p class="px-3 text-xs text-white/50 mb-2"><?= e(Rbac::label($authUser['role'] ?? null)) ?></p>
      <form method="post" action="<?= e(url('/cikis')) ?>">
        <?= Csrf::field() ?>
        <button class="w-full text-left px-3 py-2 rounded-lg text-sm text-white/70 hover:bg-white/10 hover:text-white transition-colors">
          Çıkış yap
        </button>
      </form>
    </div>
  </aside>

  <!-- Mobil menü: JS gerektirmeyen <details> açılır menüsü -->
  <details class="lg:hidden bg-ink text-white">
    <summary class="flex items-center justify-between px-4 py-4">
      <span class="font-semibold">Mağusa Psikoloji</span>
      <span class="text-sm text-white/60">Menü ▾</span>
    </summary>
    <nav class="px-3 pb-3 space-y-1">
      <?php foreach ($nav as $item): ?>
        <a href="<?= e(url($item['path'])) ?>" class="block px-3 py-2 rounded-lg text-sm text-white/80 hover:bg-white/10">
          <?= e($item['label']) ?>
        </a>
      <?php endforeach; ?>
      <form method="post" action="<?= e(url('/cikis')) ?>" class="pt-1">
        <?= Csrf::field() ?>
        <button class="w-full text-left px-3 py-2 rounded-lg text-sm text-white/80 hover:bg-white/10">Çıkış yap</button>
      </form>
    </nav>
  </details>

  <!-- İçerik -->
  <main class="flex-1 min-w-0">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">

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

      <?= $content ?>
    </div>
  </main>
</div>

<script src="<?= e(url('/assets/panel.js')) ?>" defer></script>
</body>
</html>
