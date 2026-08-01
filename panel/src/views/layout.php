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
// 'icon' menü daraltıldığında görünen işarettir; çizimler _icons.php'de.
$groups = [
    ['label' => 'Merkez', 'items' => [
        ['path' => '/',            'label' => 'Bugün',        'icon' => 'bugun',        'permissions' => ['dashboard.view']],
        ['path' => '/randevular',  'label' => 'Randevular',   'icon' => 'randevular',   'permissions' => ['appointment.view.all', 'appointment.view.own']],
        ['path' => '/danisanlar',  'label' => 'Görüşmeciler', 'icon' => 'gorusmeciler', 'permissions' => ['client.view.all', 'client.view.own']],
        // Check-in menüde kendi satırı: soru metnini ve haftalık e-postanın
        // kime çıktığını yönetici de düzenliyor (bkz. Rbac → checkin.manage) ve
        // yönetici bir görüşmeci sayfasındaki check-in bölümünü hiç görmüyor —
        // oradan girilen bir bağlantı olarak kalsaydı, ekran ona kapalıydı.
        ['path' => '/check-in-sorulari', 'label' => 'Check-in', 'icon' => 'checkin', 'permissions' => ['checkin.manage']],
        ['path' => '/musaitlik',   'label' => 'Müsaitlik',    'icon' => 'musaitlik',    'permissions' => ['availability.manage.all', 'availability.manage.own']],
        ['path' => '/odemeler',    'label' => 'Ödemeler',     'icon' => 'odemeler',     'permissions' => ['payment.view.all', 'payment.view.own']],
        ['path' => '/raporlar',    'label' => 'Raporlar',     'icon' => 'raporlar',     'permissions' => ['payment.view.all']],
    ]],
    ['label' => 'Site', 'items' => [
        ['path' => '/icerik',      'label' => 'Site içeriği', 'icon' => 'icerik',       'permissions' => ['content.manage']],
        ['path' => '/onam-formu',  'label' => 'Onam formu',   'icon' => 'onam',         'permissions' => ['consent.manage']],
    ]],
    ['label' => 'Yönetim', 'items' => [
        ['path' => '/kullanicilar', 'label' => 'Kullanıcılar',     'icon' => 'kullanicilar', 'permissions' => ['user.view']],
        ['path' => '/kayitlar',     'label' => 'Sistem kayıtları', 'icon' => 'kayitlar',     'permissions' => ['audit.view']],
        ['path' => '/sistem',       'label' => 'Sistem',           'icon' => 'sistem',       'permissions' => ['settings.manage']],
    ]],
];

$sideIcons = require __DIR__ . '/_icons.php';

// Kişi arama kutusu menünün kendisine ait: günün en sık işi "şu kişinin kaydını
// aç" ve yolu bugüne kadar dört adımdı (Görüşmeciler → ara → Filtrele → Aç).
// Yalnız görüşmeci görebilenlere çiziliyor; görüşmecinin kendi hesabında yok.
$canFindClients = Rbac::canAny($authUser, ['client.view.all', 'client.view.own']);

// Kutu arşivi de tarar (durum=all): adı yazan kişi o kaydın arşivde olup
// olmadığını bilmiyor, "bulunamadı" demek onu ikinci bir aramaya yolluyordu.
// Listenin kendi filtresi parametresiz gelindiğinde hâlâ aktif kayıtlarda kalır.
// $step yalnız geniş ekrandaki kopyaya veriliyor: tur, aynı kutuyu iki kez
// anlatmasın. Mobil kopya turun gözünde zaten yok — kapalı <details> içindeki
// öğe ekranda görünmüyor ve o adım atlanıyor (bkz. panel.js).
$findBox = static function (string $id, string $extra = '', ?int $step = null) use ($canFindClients): void {
    if (!$canFindClients) {
        return;
    }
    ?>
    <form method="get" action="<?= e(url('/danisanlar')) ?>" class="side-find <?= e($extra) ?>" role="search"
          <?php if ($step !== null): ?>data-tour="<?= $step ?>" data-tour-title="Kişi ara"
          data-tour-text="Günün en sık işi bir kaydı açmak. Adın ilk harfleri yeter; arama arşivdeki kayıtları da tarar, kutu her ekranda burada."<?php endif; ?>>
      <input type="hidden" name="durum" value="all">
      <label for="<?= e($id) ?>" class="sr-only">Kişi ara</label>
      <svg class="side-find-mark" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
        <circle cx="7" cy="7" r="4.4" fill="none" stroke="currentColor" stroke-width="1.3"/>
        <path d="M10.4 10.4 14 14" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
      </svg>
      <input type="search" id="<?= e($id) ?>" name="q" placeholder="Kişi ara…"
             autocomplete="off" data-side-label="Kişi ara">
    </form>
    <?php
};

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

// Daraltma durumu çerezde tutuluyor, localStorage'da değil: panel çok sayfalı,
// her tıklama tam bir sayfa yüklüyor. İstemciden okunsaydı her sayfa önce geniş
// menüyle boyanır, betik çalışınca daralırdı. Sunucu çerezi okuyup <body>'yi
// doğru sınıfla basınca o sıçrama hiç olmuyor. Çerezi panel.js yazar.
$navTight = ($_COOKIE['panel_nav'] ?? '') === 'tight';

// ── Tanıtım turu ────────────────────────────────────────────────────────
// Adımların metni burada değil, anlattıkları ekranların kendi işaretlemesinde
// duruyor (data-tour; bkz. panel.js). Layout'un işi iki şey: turu başlatan
// düğme ve "bu kişi turu gördü mü" bilgisi.
//
// Görüldü bilgisi çerezde tutuluyor, veritabanında değil — menü daraltma
// durumu da öyle (bkz. $navTight). Bir karşılama ekranı için users tablosuna
// sütun açmak, göç gerektiren bir şema değişikliğini yalnızca "bir daha
// gösterme" için ödemek olurdu. Bedeli: tur tarayıcı başına bir kez çıkar.
// Değer kişi kimliğiyle işaretli, çünkü merkezde ortak bir bilgisayar var ve
// birinin turu görmüş olması ötekini karşılamasız bırakmamalı.
$tourVersion = 1;
$tourKey  = (int) ($authUser['id'] ?? 0) . '-' . $tourVersion;
$tourSeen = in_array($tourKey, explode(',', (string) ($_COOKIE['panel_tur'] ?? '')), true);

// Panel bir kez tanıtılır. Kenar çubuğunun adımları (10–19) yalnız turu hiç
// görmemiş kişiye açılır; sonraki her "Tanıtım turu" o ekranın kendi
// adımlarıyla (20+) başlar. Menüyü ikinci kez anlatmak, ilk anlatışı da
// değersizleştirirdi — basan kişi o an baktığı ekranı soruyor.
$tourIntro = !$tourSeen;

// Kendiliğinden yalnız "Bugün"de açılır: panelin ilk düştüğü ekran orası.
// Başka bir sayfada açılsaydı, tur birinin işini yaptığı yerde önüne çıkmış
// olurdu — istendiğinde menüdeki düğme her ekranda duruyor.
$tourAuto = $tourIntro && $here === '/';

// Menü daraldığında kişinin adı da kısalır. mb_strtoupper 'i' harfini 'I'
// yapardı; Türkçede karşılığı 'İ'.
$sideInitials = static function (?string $name): string {
    $parts = preg_split('/\s+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $letters = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $letters .= mb_substr($part, 0, 1, 'UTF-8');
    }
    return mb_strtoupper(strtr($letters, ['i' => 'İ']), 'UTF-8');
};
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
<body class="min-h-screen font-sans<?= $navTight ? ' nav-tight' : '' ?>">

<div class="lg:flex">

  <!-- Kenar çubuğu -->
  <aside id="yan-menu" class="side hidden lg:flex lg:flex-col lg:h-screen lg:sticky lg:top-0 bg-chrome shrink-0">
    <div class="side-head">
      <div class="side-brand">
        <p class="side-name">Mağusa Psikoloji</p>
        <p class="side-mark" aria-hidden="true">MP</p>
        <p class="eyebrow side-role">Yönetim paneli</p>
      </div>

      <!-- JS kapalıyken tıklandığında hiçbir şey olmayacağı için gizli geliyor,
           panel.js açıyor (bkz. [data-reveal] için aynı yöntem). -->
      <button type="button" class="side-toggle" hidden
              data-side-toggle data-side-path="<?= e(url('/')) ?>"
              aria-controls="yan-menu" aria-expanded="<?= $navTight ? 'false' : 'true' ?>"
              data-tour="12" data-tour-title="Menüyü daraltın"
              data-tour-text="Menü işaretlere iner, ekran genişler. Bir işaretin üzerine gelince adı yazar. Seçiminiz hatırlanır; sonraki girişte de daraltılmış açılır.">
        <svg viewBox="0 0 16 16" aria-hidden="true" focusable="false">
          <rect x="1.65" y="2.65" width="12.7" height="10.7" rx="2.4"
                fill="none" stroke="currentColor" stroke-width="1.3"/>
          <path d="M6.4 2.65v10.7" stroke="currentColor" stroke-width="1.3"/>
          <rect class="side-toggle-fill" x="2.75" y="3.75" width="2.5" height="8.5" rx="1.05"/>
        </svg>
        <span class="side-toggle-text">Menüyü daralt</span>
      </button>
    </div>

    <?php $findBox('kisiAra', '', 11); ?>

    <nav class="side-nav" aria-label="Panel menüsü"
         data-tour="10" data-tour-title="Panelin menüsü"
         data-tour-text="Üç öbek, üç ayrı iş: Merkez günlük işleyiştir, Site herkese açık sayfaların metni, Yönetim sistemin kendisi. Yalnız yetkiniz olan satırlar görünür — menüsü herkeste aynı değildir.">
      <?php foreach ($groups as $group): ?>
        <div class="nav-group">
          <p class="eyebrow nav-group-label"><?= e($group['label']) ?></p>
          <?php foreach ($group['items'] as $item): ?>
            <a href="<?= e(url($item['path'])) ?>" class="nav-link"
               data-side-label="<?= e($item['label']) ?>"
               <?= $isActive($item['path']) ? 'aria-current="page"' : '' ?>>
              <svg class="nav-icon" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><?= $sideIcons[$item['icon']] ?></svg>
              <span class="nav-full"><?= e($item['label']) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </nav>

    <div class="side-foot">
      <?php
      // Adlar menüye özel: extract() sayfa verisini bu alana döküyor, sade bir
      // $person sayfanın kendi $person'ını yutardı (bkz. View::capture).
      $sideName = (string) ($authUser['full_name'] ?? '');
      $sideRole = Rbac::label($authUser['role'] ?? null);
      ?>
      <?php // Turu başlatan düğme. JS kapalıyken yapacağı bir şey yok, o yüzden
            // gizli geliyor; panel.js yalnız o ekranda anlatacak bir adım varsa
            // açıyor. Yeri menünün dibi: bir daha aranmayacak ama arandığında
            // hep aynı yerde duran şeyler burada. ?>
      <button type="button" class="nav-link w-full" hidden
              data-tour-start
              data-tour-key="<?= e($tourKey) ?>"
              data-tour-path="<?= e(url('/')) ?>"
              <?= $tourIntro ? 'data-tour-intro' : '' ?>
              <?= $tourAuto ? 'data-tour-auto' : '' ?>
              data-side-label="Tanıtım turu">
        <svg class="nav-icon" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><?= $sideIcons['tur'] ?></svg>
        <span class="nav-full">Tanıtım turu</span>
      </button>

      <?php if (Rbac::can($authUser, 'profile.self')): ?>
        <a href="<?= e(url('/profil')) ?>" class="side-person"
           data-side-label="<?= e($sideName) ?>"
           data-tour="13" data-tour-title="Hesabınız"
           data-tour-text="Adınız, e-postanız ve şifreniz burada. Yanındaki satır rolünüzü söyler: panelde ne görebildiğiniz ona bağlı."
           <?= $isActive('/profil') ? 'aria-current="page"' : '' ?>>
          <span class="side-person-name"><?= e($sideName) ?></span>
          <span class="side-person-mark" aria-hidden="true"><?= e($sideInitials($sideName)) ?></span>
          <span class="eyebrow side-person-role"><?= e($sideRole) ?></span>
        </a>
      <?php else: ?>
        <div class="side-person">
          <span class="side-person-name"><?= e($sideName) ?></span>
          <span class="side-person-mark" aria-hidden="true"><?= e($sideInitials($sideName)) ?></span>
          <span class="eyebrow side-person-role"><?= e($sideRole) ?></span>
        </div>
      <?php endif; ?>
      <form method="post" action="<?= e(url('/cikis')) ?>" class="mt-1">
        <?= Csrf::field() ?>
        <button class="nav-link w-full" data-side-label="Çıkış yap">
          <svg class="nav-icon" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><?= $sideIcons['cikis'] ?></svg>
          <span class="nav-full">Çıkış yap</span>
        </button>
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
      <?php $findBox('kisiAraMobil', 'side-find-flush'); ?>
      <?php foreach ($groups as $group): ?>
        <div>
          <p class="eyebrow text-white/30 px-3 mb-1"><?= e($group['label']) ?></p>
          <?php foreach ($group['items'] as $item): ?>
            <a href="<?= e(url($item['path'])) ?>" class="nav-link"
               <?= $isActive($item['path']) ? 'aria-current="page"' : '' ?>>
              <svg class="nav-icon" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><?= $sideIcons[$item['icon']] ?></svg>
              <span class="nav-full"><?= e($item['label']) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
      <div class="border-t border-white/10 pt-2">
        <?php // Turun mobil kopyası. Menü kendisi tam ekranı kapladığı için
              // düğmeye basınca <details> kapanıyor (bkz. panel.js): tur,
              // üstünü örttüğü bir sayfayı anlatamaz. ?>
        <button type="button" class="nav-link w-full" hidden
                data-tour-start
                data-tour-key="<?= e($tourKey) ?>"
                data-tour-path="<?= e(url('/')) ?>"
                <?= $tourIntro ? 'data-tour-intro' : '' ?>
                <?= $tourAuto ? 'data-tour-auto' : '' ?>>
          <svg class="nav-icon" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><?= $sideIcons['tur'] ?></svg>
          <span class="nav-full">Tanıtım turu</span>
        </button>
        <?php if (Rbac::can($authUser, 'profile.self')): ?>
          <a href="<?= e(url('/profil')) ?>" class="nav-link">
            <svg class="nav-icon" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><?= $sideIcons['profil'] ?></svg>
            <span class="nav-full"><?= e($authUser['full_name'] ?? 'Profilim') ?></span>
          </a>
        <?php endif; ?>
        <form method="post" action="<?= e(url('/cikis')) ?>">
          <?= Csrf::field() ?>
          <button class="nav-link w-full">
            <svg class="nav-icon" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><?= $sideIcons['cikis'] ?></svg>
            <span class="nav-full">Çıkış yap</span>
          </button>
        </form>
      </div>
    </nav>
  </details>

  <!-- İçerik -->
  <main class="flex-1 min-w-0">
    <div class="page-wrap px-4 sm:px-8 py-8">

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
