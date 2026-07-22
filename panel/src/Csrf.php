<?php
declare(strict_types=1);

namespace Panel;

/** Oturum başına tek CSRF jetonu; her POST bu jetonla doğrulanır. */
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    public static function check(): bool
    {
        $sent = $_POST['_csrf'] ?? '';
        return is_string($sent)
            && !empty($_SESSION['_csrf'])
            && hash_equals($_SESSION['_csrf'], $sent);
    }

    /** Doğrulama başarısızsa isteği burada sonlandırır. */
    public static function verify(): void
    {
        if (!self::check()) {
            View::error(419, 'Oturum doğrulaması başarısız', 'Form çok uzun süre açık kaldı veya oturumunuz yenilendi. Sayfayı yenileyip tekrar deneyin.');
            exit;
        }
    }
}
