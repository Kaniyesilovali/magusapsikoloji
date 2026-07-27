<?php
/** @var string $content @var array|null $authUser @var array $flashes @var string $title */
use Panel\Csrf;
use Panel\Rbac;

// Bir satır için listelenen yetkilerden herhangi biri yeterli: "hepsini gör" ile
// "yalnız kendininkini gör" aynı ekrana çıkar, kapsamı ekranın kendisi daraltır.
//
// Menü üç öbeğe ayrıldı çünkü bunlar gerçekten üç ayrı iş: merkezin günlük
// işleyişi, public sitenin içeriği ve sistemin kendisi. Tek listede yan yana
// dururken "Müsaitlik" ile "Sistem Kayıtları" eşit ağırlıkta görünüyordu.
$groups = [
    ['label' => 'Merkez', 'items' => [
        ['path' => '/',            'label' => 'Bugün',        'permissions' => ['dashboard.view']],
        ['path' => '/randevular',  'label' => 'Randevular',   'permissions' => ['appointment.view.all', 'appointment.view.own']],
        ['path' => '/danisanlar',  'label' => 'Görüşmeciler',   'permissions' => ['client.view.all', 'client.view.own']],
        ['path' => '/musaitlik',   'label' => 'Müsaitlik',    'permissions' => ['availability.manage.all', 'availability.manage.own']],
        ['path' => '/odemeler',    'label' => 'Ödemeler',     'permissions' => ['payment.view.all', 'payment.view.own']],
    ]],
    ['label' => 'Site', 'items' => [
        ['path' => '/icerik',      'label' => 'Site içeriği', 'permissions' => ['content.manage']],
        ['path' => '/kvkk',        'label' => 'KVKK metni',   'permissions' => ['consent.manage']],
    ]],
    ['label' => 'Yönetim', 'items' => [
        ['path' => '/kullanicilar', 'label' => 'Kullanıcılar',     'permissions' => ['user.view']],
        ['path' => '/kayitlar',     'label' => 'Sistem kayıtları', 'permissions' => ['audit.view']],
        ['path' => '/sistem',       'label' => 'Sistem',           'permissions' => ['settings.manage']],
    ]],
];

$groups = array_values(array_filter(array_map(static function (array $group) use ($authUser): array {
    $group['items'] = array_values(array_filter(
        $group['items'],
        static fn (array $item): bool => Rbac::canAny($authUser, $item['permissions'])
    ));
    return $group;
}, $groups), static fn (array $group): bool => $group['items'] !== []));

$here = current_path();

$isActive = static fn (string $path): bool
    => $here === $path || ($path !== '/' && str_starts_with($here, $path));
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title ?? 'Panel') ?> — Mağusa Psikoloji</title>
<link rel="icon" href="/assets/images/favicon.png">
<link rel="stylesheet" href="<?= e(asset('/assets/panel.css')) ?>">
</head>
<body class="min-h-screen font-sans">

<div class="lg:flex">

  <!-- Kenar çubuğu -->
  <aside class="hidden lg:flex lg:flex-col lg:w-60 lg:h-screen lg:sticky lg:top-0 bg-chrome shrink-0">
    <div class="px-5 pt-6 pb-5">
      <p class="font-serif text-white text-[1.0625rem] leading-tight">Mağusa Psikoloji</p>
      <p class="eyebrow text-white/40 mt-1">Yönetim paneli</p>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 pb-3 space-y-5">
      <?php foreach ($groups as $group): ?>
        <div>
          <p class="eyebrow text-white/30 px-3 mb-1.5"><?= e($group['label']) ?></p>
          <?php foreach ($group['items'] as $item): ?>
            <a href="<?= e(url($item['path'])) ?>" class="nav-link"
               <?= $isActive($item['path']) ? 'aria-current="page"' : '' ?>>
              <?= e($item['label']) ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </nav>

    <div class="p-3 border-t border-white/10">
      <?php if (Rbac::can($authUser, 'profile.self')): ?>
        <a href="<?= e(url('/profil')) ?>" class="block px-3 py-2 rounded-md hover:bg-white/[0.07]"
           <?= $isActive('/profil') ? 'aria-current="page"' : '' ?>>
          <span class="block font-serif text-sm text-white truncate"><?= e($authUser['full_name'] ?? '') ?></span>
          <span class="block eyebrow text-white/35 mt-0.5"><?= e(Rbac::label($authUser['role'] ?? null)) ?></span>
        </a>
      <?php else: ?>
        <div class="px-3 py-2">
          <span class="block font-serif text-sm text-white truncate"><?= e($authUser['full_name'] ?? '') ?></span>
          <span class="block eyebrow text-white/35 mt-0.5"><?= e(Rbac::label($authUser['role'] ?? null)) ?></span>
        </div>
      <?php endif; ?>
      <form method="post" action="<?= e(url('/cikis')) ?>" class="mt-1">
        <?= Csrf::field() ?>
        <button class="nav-link w-full text-left">Çıkış yap</button>
      </form>
    </div>
  </aside>

  <!-- Mobil menü: JS gerektirmeyen <details> açılır menüsü -->
  <details class="lg:hidden bg-chrome">
    <summary class="flex items-center justify-between px-4 py-3.5">
      <span class="font-serif text-white">Mağusa Psikoloji</span>
      <span class="eyebrow text-white/50">Menü ▾</span>
    </summary>
    <nav class="px-3 pb-3 space-y-4">
      <?php foreach ($groups as $group): ?>
        <div>
          <p class="eyebrow text-white/30 px-3 mb-1"><?= e($group['label']) ?></p>
          <?php foreach ($group['items'] as $item): ?>
            <a href="<?= e(url($item['path'])) ?>" class="nav-link"
               <?= $isActive($item['path']) ? 'aria-current="page"' : '' ?>>
              <?= e($item['label']) ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
      <div class="border-t border-white/10 pt-2">
        <?php if (Rbac::can($authUser, 'profile.self')): ?>
          <a href="<?= e(url('/profil')) ?>" class="nav-link"><?= e($authUser['full_name'] ?? 'Profilim') ?></a>
        <?php endif; ?>
        <form method="post" action="<?= e(url('/cikis')) ?>">
          <?= Csrf::field() ?>
          <button class="nav-link w-full text-left">Çıkış yap</button>
        </form>
      </div>
    </nav>
  </details>

  <!-- İçerik -->
  <main class="flex-1 min-w-0">
    <div class="max-w-6xl mx-auto px-4 sm:px-8 py-8">

      <?php foreach ($flashes as $flash): ?>
        <?php
        // Panelde tek uyarı rengi var: "insan kararı gerekiyor". Uyarı ile hata
        // ayrı renkler kullandığında ikisi de zayıflıyordu.
        $tone = match ($flash['type']) {
            'success'          => 'note-ok',
            'error', 'warning' => 'note-stop',
            default            => 'note-info',
        };
        ?>
        <div class="note <?= $tone ?> mb-4"><?= e($flash['message']) ?></div>
      <?php endforeach; ?>

      <?= $content ?>
    </div>
  </main>
</div>

<script src="<?= e(asset('/assets/panel.js')) ?>" defer></script>
</body>
</html>
