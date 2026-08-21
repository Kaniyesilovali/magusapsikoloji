<?php
declare(strict_types=1);

/**
 * Panel önyükleme: yapılandırma, otomatik yükleyici, oturum, hata yönetimi.
 * index.php dışında hiçbir yerden doğrudan çağrılmaz.
 */

define('PANEL_ROOT', dirname(__DIR__));

// ── Yapılandırma ────────────────────────────────────────────────
// Config DAİMA panel dizininin dışında aranır. Aksi hâlde `npm run build`
// yerel config'i _site/panel/ içine kopyalar ve sırlar yayına çıkardı.
//   üretim (cPanel): /home/<kullanici>/magusa-panel-config/config.php
//   yerel (php -S -t panel): <repo>/magusa-panel-config/config.php
// Son aday CLI içindir: komut satırında DOCUMENT_ROOT yoktur, bu yüzden
// public_html'in bir üstü ancak panel dizininden iki seviye yukarı çıkarak
// bulunur. Bu olmadan cron ve migrate.php üretimde config'i bulamazdı.
$configCandidates = array_filter([
    getenv('MAGUSA_PANEL_CONFIG') ?: null,
    isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== ''
        ? dirname($_SERVER['DOCUMENT_ROOT']) . '/magusa-panel-config/config.php'
        : null,
    dirname(PANEL_ROOT) . '/magusa-panel-config/config.php',
    dirname(PANEL_ROOT, 2) . '/magusa-panel-config/config.php',
]);

$config = null;
foreach ($configCandidates as $candidate) {
    if (is_file($candidate)) {
        $config = require $candidate;
        break;
    }
}

if (!is_array($config)) {
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    exit('<h1>Panel yapılandırılmamış</h1><p>config.php bulunamadı. Kurulum için <code>docs/PANEL.md</code> dosyasına bakın.</p>');
}

// ── Otomatik yükleyici (PSR-4 benzeri, tek kök) ──────────────────
spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'Panel\\')) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen('Panel\\')));
    $file = __DIR__ . '/' . $relative . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

require_once __DIR__ . '/helpers.php';

Panel\Config::init($config);

date_default_timezone_set(Panel\Config::get('app.timezone', 'Europe/Nicosia'));
mb_internal_encoding('UTF-8');

// ── Hata yönetimi ───────────────────────────────────────────────
$debug = (bool) Panel\Config::get('app.debug', false);
ini_set('display_errors', $debug ? '1' : '0');
error_reporting(E_ALL);

set_exception_handler(static function (Throwable $e) use ($debug): void {
    error_log('[panel] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
    }
    if ($debug) {
        echo '<pre style="padding:1rem;font:13px/1.5 monospace">'
            . htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8') . '</pre>';
        return;
    }
    Panel\View::error(500, 'Beklenmeyen bir hata oluştu', 'Sorun kaydedildi. Lütfen tekrar deneyin.');
});

// Komut satırı araçları (migrate.php) oturum kullanmaz.
if (PHP_SAPI === 'cli') {
    return;
}

// ── Oturum ──────────────────────────────────────────────────────
// Panel tek dizinde çalıştığı için çerez yolu /panel ile sınırlanır;
// public siteye panel çerezi gönderilmez.
//
// Sitenin kökündeki herkese açık onam sayfası (bkz. static/onam-formu.php)
// PANEL_STATELESS ile geliyor ve oturum HİÇ açılmıyor. Açılsaydı her ziyaret
// bir oturum dosyası bırakırdı ve hiçbiri okunamazdı: çerezin yolu /panel,
// sayfa ise kökte — tarayıcı çerezi geri göndermezdi. $_SESSION yine de var
// olsun ki oturuma dokunan yardımcılar (flash, old, clear_input_state)
// koşulsuz çalışsın; yazılan hiçbir şey istek bitince kalmaz — bu sayfanın
// hatırlanacak bir hâli yok.
if (defined('PANEL_STATELESS') && PANEL_STATELESS) {
    $_SESSION = [];
    return;
}
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('MPSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => PANEL_BASE === '' ? '/' : PANEL_BASE . '/',
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

// Hareketsizlik zaman aşımı
$lifetime = (int) Panel\Config::get('security.session_lifetime', 1800);
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $lifetime) {
    Panel\Auth::logout();
    flash('warning', 'Uzun süre işlem yapılmadığı için oturumunuz kapatıldı.');
}
$_SESSION['last_activity'] = time();
