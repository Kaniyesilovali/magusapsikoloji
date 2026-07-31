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
use Panel\Controllers\CaseFileController;
use Panel\Controllers\CheckinController;
use Panel\Controllers\ClientController;
use Panel\Controllers\ConsentController;
use Panel\Controllers\ContentController;
use Panel\Controllers\DashboardController;
use Panel\Controllers\FaqController;
use Panel\Controllers\NoteController;
use Panel\Controllers\PaymentController;
use Panel\Controllers\ProfileController;
use Panel\Controllers\ReportController;
use Panel\Controllers\SetupController;
use Panel\Controllers\SystemController;
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
// Check-in bu kuralın dışında: o akış oturumu hiç kullanmıyor, yetkisini
// bağlantıdaki jetondan alıyor. Tarayıcıda açık bir oturum bulunduğu için
// birini şifre ekranına göndermek, giriş gerektirmeyen bir formun önüne giriş
// duvarı koymak olurdu.
$MUST_CHANGE_ALLOWED = ['/profil/sifre', '/cikis'];
$currentUser = Auth::user();
if ($currentUser !== null && (int) $currentUser['must_change_password'] === 1
    && !in_array($path, $MUST_CHANGE_ALLOWED, true)
    && !str_starts_with($path, '/check-in/')) {
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

// ── Seanslar arası check-in (giriş gerektirmez) ─────────────────
// Teşekkür sayfası jeton desenine de uyduğu için ÖNCE tanımlanmalı; rotalar
// tanımlanma sırasıyla eşleşiyor.
$router->get('/check-in/tesekkurler', [CheckinController::class, 'thanks']);
$router->get('/check-in/{token}',     [CheckinController::class, 'form']);
$router->post('/check-in/{token}',    [CheckinController::class, 'submit']);

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
// Panel erişimi: hesap yeni kayıtta kendiliğinden açılır; bunlar sonradan
// açma, daveti yenileme ve erişimi kapatma/açma içindir.
$router->post('/danisanlar/{id}/panel-erisimi', [ClientController::class, 'grantAccess']);
$router->post('/danisanlar/{id}/davet-gonder',  [ClientController::class, 'resendInvite']);
$router->post('/danisanlar/{id}/erisim',        [ClientController::class, 'toggleAccess']);
// Check-in döngüsünü başlatan/sürdüren el hareketi; haftalık gönderim cron'da.
$router->post('/danisanlar/{id}/check-in', [ClientController::class, 'sendCheckin']);
// Haftalık gönderim anahtarı — görüşmeci sayfasındaki kopya.
$router->post('/danisanlar/{id}/check-in-gonderim', [ClientController::class, 'toggleCheckinAuto']);
// Soru metinleri ve gönderim listesi panelden düzenlenir. Yol bilerek
// `/check-in/` altında DEĞİL: o önek giriş gerektirmeyen jeton rotasına ve
// şifre-değiştirme muafiyetine ait.
$router->get('/check-in-sorulari',           [CheckinController::class, 'questions']);
$router->post('/check-in-sorulari',          [CheckinController::class, 'saveQuestions']);
$router->post('/check-in-sorulari/alicilar', [CheckinController::class, 'saveRecipients']);
// Hangi alanların sorulacağı görüşmeci başına değişir.
$router->get('/danisanlar/{id}/alanlar',  [CheckinController::class, 'domains']);
$router->post('/danisanlar/{id}/alanlar', [CheckinController::class, 'saveDomains']);
$router->get('/danisanlar/{id}/onam',     [ConsentController::class, 'printForm']);
$router->get('/danisanlar/{id}/dosya',    [CaseFileController::class, 'form']);
$router->post('/danisanlar/{id}/dosya',   [CaseFileController::class, 'save']);

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

$router->get('/raporlar',                 [ReportController::class, 'index']);

$router->get('/odemeler',                    [PaymentController::class, 'index']);
$router->get('/odemeler/{id}',               [PaymentController::class, 'show']);
$router->post('/odemeler/{id}/ucret',        [PaymentController::class, 'setFee']);
$router->post('/odemeler/{id}/tahsilat',     [PaymentController::class, 'storePayment']);
$router->post('/odemeler/tahsilat/{id}/sil', [PaymentController::class, 'destroyPayment']);

$router->get('/sistem',          [SystemController::class, 'index']);
$router->post('/sistem/guncelle', [SystemController::class, 'migrate']);
$router->post('/sistem/test-eposta', [SystemController::class, 'testMail']);

$router->get('/icerik',               [ContentController::class, 'index']);
$router->get('/icerik/iletisim',      [ContentController::class, 'contact']);
$router->post('/icerik/iletisim',     [ContentController::class, 'saveContact']);
$router->get('/icerik/sss',           [FaqController::class, 'index']);
$router->get('/icerik/sss-duzenle',   [FaqController::class, 'edit']);
$router->post('/icerik/sss-duzenle',  [FaqController::class, 'save']);

$router->get('/onam-formu',  [ConsentController::class, 'index']);
$router->post('/onam-formu', [ConsentController::class, 'update']);

$router->get('/kayitlar', [AuditController::class, 'index']);

$router->dispatch($method, $path);
