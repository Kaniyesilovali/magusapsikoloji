<?php
declare(strict_types=1);

/**
 * Panelin tek giriş noktası. .htaccess tüm istekleri buraya yönlendirir.
 */

// /panel/index.php → PANEL_BASE = "/panel"  (php -S ile kök çalışırken "")
define('PANEL_BASE', rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/'))), '/'));

require __DIR__ . '/src/bootstrap.php';

use Panel\Auth;
use Panel\Controllers\AuditController;
use Panel\Controllers\AuthController;
use Panel\Controllers\DashboardController;
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

$router->get('/profil',         [ProfileController::class, 'edit']);
$router->post('/profil',        [ProfileController::class, 'update']);
$router->get('/profil/sifre',   [ProfileController::class, 'passwordForm']);
$router->post('/profil/sifre',  [ProfileController::class, 'updatePassword']);

$router->get('/kayitlar', [AuditController::class, 'index']);

$router->dispatch($method, $path);
