<?php
declare(strict_types=1);

/**
 * Şablonlarda ve denetleyicilerde kullanılan kısa yardımcılar.
 */

/** HTML kaçışı — şablonlarda değişken basılan HER yerde kullanılır. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Panel içi mutlak yol üretir: url('/kullanicilar') → /panel/kullanicilar */
function url(string $path = '/'): string
{
    return PANEL_BASE . '/' . ltrim($path, '/');
}

/** Panel köküne göre geçerli yol — menüde aktif bağlantıyı işaretlemek için. */
function current_path(): string
{
    $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
    if (PANEL_BASE !== '' && str_starts_with($path, PANEL_BASE)) {
        $path = substr($path, strlen(PANEL_BASE));
    }
    return '/' . trim($path, '/');
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');
}

function redirect(string $path): never
{
    header('Location: ' . url($path), true, 302);
    exit;
}

/** Tek seferlik bildirim. Tür: success | error | warning | info */
function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $flashes = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $flashes;
}

/** Doğrulama hatası sonrası formu yeniden doldurmak için. */
function remember_input(array $input, array $errors = []): void
{
    unset($input['password'], $input['password_confirm'], $input['_csrf']);
    $_SESSION['_old']    = $input;
    $_SESSION['_errors'] = $errors;
}

function old(string $key, string $default = ''): string
{
    return (string) ($_SESSION['_old'][$key] ?? $default);
}

function errors(): array
{
    return $_SESSION['_errors'] ?? [];
}

function error_for(string $key): ?string
{
    return $_SESSION['_errors'][$key] ?? null;
}

function clear_input_state(): void
{
    unset($_SESSION['_old'], $_SESSION['_errors']);
}

function post(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function query(string $key, string $default = ''): string
{
    $value = $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function client_ip(): string
{
    // Cloudflare arkasında gerçek istemci IP'si bu başlıkta gelir.
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', (string) $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '';
}

/** 2026-07-22 14:30 biçimi. */
function dt(?string $sqlDateTime, string $format = 'd.m.Y H:i'): string
{
    if (!$sqlDateTime) {
        return '—';
    }
    try {
        return (new DateTimeImmutable($sqlDateTime))->format($format);
    } catch (Exception) {
        return '—';
    }
}
