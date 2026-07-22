<?php
declare(strict_types=1);

/**
 * Panelin tek giriş noktası. .htaccess tüm istekleri buraya yönlendirir.
 */

// Sürüm kapısı — bu dosya bilinçli olarak PHP 7 ile de AYRIŞTIRILABİLİR sözdizimi
// kullanır. Eski bir sürümde çalışılırsa diğer dosyalar ayrıştırma hatası verip
// boş bir 500 döndürürdü; buradan anlaşılır bir mesaj gösterilir.
if (PHP_VERSION_ID < 80100) {
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    exit(
        '<!doctype html><html lang="tr"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>PHP sürümü yetersiz</title></head>'
        . '<body style="font-family:system-ui,sans-serif;max-width:34rem;margin:4rem auto;padding:0 1.5rem;color:#2C3830;line-height:1.6">'
        . '<h1 style="font-size:1.25rem">PHP sürümü yetersiz</h1>'
        . '<p>Panel en az <strong>PHP 8.1</strong> gerektiriyor (önerilen: 8.3). '
        . 'Bu sunucuda çalışan sürüm: <strong>' . htmlspecialchars(PHP_VERSION, ENT_QUOTES, 'UTF-8') . '</strong>.</p>'
        . '<p>cPanel → <strong>Select PHP Version</strong> ekranından sürümü 8.3 yapın ve '
        . '<code>pdo_mysql</code>, <code>mbstring</code>, <code>openssl</code>, <code>sodium</code> '
        . 'eklentilerini işaretleyin.</p>'
        . '</body></html>'
    );
}

// /panel/index.php → PANEL_BASE = "/panel"  (php -S ile kök çalışırken "")
define('PANEL_BASE', rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/'))), '/'));

require __DIR__ . '/src/bootstrap.php';

use Panel\Auth;
use Panel\Controllers\AppointmentController;
use Panel\Controllers\AuditController;
use Panel\Controllers\AuthController;
use Panel\Controllers\AvailabilityController;
use Panel\Controllers\ClientController;
use Panel\Controllers\ConsentController;
use Panel\Controllers\ContentController;
use Panel\Controllers\DashboardController;
use Panel\Controllers\FaqController;
use Panel\Controllers\NoteController;
use Panel\Controllers\ProfileController;
use Panel\Controllers\SetupController;
use Panel\Controllers\UserController;
use Panel\Router;

// İstek yolunu panel köküne göre normalize et
$path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
if (PANEL_BASE !== '' && str_starts_with($path, PANEL_BASE)) {
    $path = substr($path, strlen(PANEL_BASE));
}
$path   = '/' . trim(rawurldecode($path), '/');
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

// Her POST isteği CSRF doğrulamasından geçer — tek yerde, unutulamaz.
if ($method === 'POST') {
    Panel\Csrf::verify();
}

// Şifre değiştirmeye zorlanan kullanıcı (davetle açılan hesap, yönetici sıfırlaması)
// başka hiçbir sayfada dolaşamaz.
$MUST_CHANGE_ALLOWED = ['/profil/sifre', '/cikis'];
$currentUser = Auth::user();
if ($currentUser !== null && (int) $currentUser['must_change_password'] === 1
    && !in_array($path, $MUST_CHANGE_ALLOWED, true)) {
    flash('warning', 'Devam etmeden önce yeni bir şifre belirlemelisiniz.');
    redirect('/profil/sifre');
}

$router = new Router();

// ── İlk kurulum (yalnız hiç kullanıcı yokken erişilebilir) ──────
$router->get('/kurulum',           [SetupController::class, 'index']);
$router->post('/kurulum',          [SetupController::class, 'store']);
$router->post('/kurulum/tablolar', [SetupController::class, 'migrate']);

// ── Kimlik doğrulama ────────────────────────────────────────────
$router->get('/giris',            [AuthController::class, 'showLogin']);
$router->post('/giris',           [AuthController::class, 'login']);
$router->post('/cikis',           [AuthController::class, 'logout']);
$router->get('/sifremi-unuttum',  [AuthController::class, 'showForgot']);
$router->post('/sifremi-unuttum', [AuthController::class, 'forgot']);
$router->get('/sifre-belirle',    [AuthController::class, 'showReset']);
$router->post('/sifre-belirle',   [AuthController::class, 'reset']);

// ── Panel ───────────────────────────────────────────────────────
$router->get('/', [DashboardController::class, 'index']);

$router->get('/kullanicilar',                    [UserController::class, 'index']);
$router->get('/kullanicilar/yeni',               [UserController::class, 'createForm']);
$router->post('/kullanicilar/yeni',              [UserController::class, 'store']);
$router->get('/kullanicilar/{id}/duzenle',       [UserController::class, 'editForm']);
$router->post('/kullanicilar/{id}/duzenle',      [UserController::class, 'update']);
$router->post('/kullanicilar/{id}/sil',          [UserController::class, 'destroy']);
$router->post('/kullanicilar/{id}/davet-gonder', [UserController::class, 'resendInvite']);

$router->get('/danisanlar',               [ClientController::class, 'index']);
$router->get('/danisanlar/yeni',          [ClientController::class, 'createForm']);
$router->post('/danisanlar/yeni',         [ClientController::class, 'store']);
$router->get('/danisanlar/{id}',          [ClientController::class, 'show']);
$router->get('/danisanlar/{id}/duzenle',  [ClientController::class, 'editForm']);
$router->post('/danisanlar/{id}/duzenle', [ClientController::class, 'update']);
$router->post('/danisanlar/{id}/arsivle', [ClientController::class, 'archive']);
$router->post('/danisanlar/{id}/sil',     [ClientController::class, 'destroy']);
$router->get('/danisanlar/{id}/riza',     [ConsentController::class, 'printForm']);

$router->get('/randevular',               [AppointmentController::class, 'index']);
$router->get('/randevular/yeni',          [AppointmentController::class, 'createForm']);
$router->post('/randevular/yeni',         [AppointmentController::class, 'store']);
$router->get('/randevular/{id}/duzenle',  [AppointmentController::class, 'editForm']);
$router->post('/randevular/{id}/duzenle', [AppointmentController::class, 'update']);
$router->post('/randevular/{id}/durum',   [AppointmentController::class, 'setStatus']);
$router->post('/randevular/{id}/iptal',   [AppointmentController::class, 'cancel']);
$router->get('/randevular/{id}/not',      [NoteController::class, 'form']);
$router->post('/randevular/{id}/not',     [NoteController::class, 'save']);

$router->get('/musaitlik',                [AvailabilityController::class, 'index']);
$router->post('/musaitlik/saat-ekle',     [AvailabilityController::class, 'addHours']);
$router->post('/musaitlik/saat/{id}/sil', [AvailabilityController::class, 'removeHours']);
$router->post('/musaitlik/izin-ekle',     [AvailabilityController::class, 'addTimeOff']);
$router->post('/musaitlik/izin/{id}/sil', [AvailabilityController::class, 'removeTimeOff']);

$router->get('/profil',         [ProfileController::class, 'edit']);
$router->post('/profil',        [ProfileController::class, 'update']);
$router->get('/profil/sifre',   [ProfileController::class, 'passwordForm']);
$router->post('/profil/sifre',  [ProfileController::class, 'updatePassword']);

$router->get('/icerik',               [ContentController::class, 'index']);
$router->get('/icerik/iletisim',      [ContentController::class, 'contact']);
$router->post('/icerik/iletisim',     [ContentController::class, 'saveContact']);
$router->get('/icerik/sss',           [FaqController::class, 'index']);
$router->get('/icerik/sss-duzenle',   [FaqController::class, 'edit']);
$router->post('/icerik/sss-duzenle',  [FaqController::class, 'save']);

$router->get('/kvkk',  [ConsentController::class, 'index']);
$router->post('/kvkk', [ConsentController::class, 'update']);

$router->get('/kayitlar', [AuditController::class, 'index']);

$router->dispatch($method, $path);
