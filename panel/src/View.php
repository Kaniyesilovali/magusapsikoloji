<?php
declare(strict_types=1);

namespace Panel;

/** Düz PHP şablon motoru: görünüm çıktısı bir düzene (layout) gömülür. */
final class View
{
    private const DIR = __DIR__ . '/views/';

    public static function render(string $template, array $data = [], string $layout = 'layout'): void
    {
        $content = self::capture($template, $data);
        clear_input_state();

        if ($layout === '') {
            echo $content;
            return;
        }
        echo self::capture($layout, $data + [
            'content'  => $content,
            'authUser' => Auth::user(),
            'flashes'  => take_flashes(),
        ]);
    }

    private static function capture(string $template, array $data): string
    {
        $file = self::DIR . str_replace(['..', '\\'], '', $template) . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("Şablon bulunamadı: {$template}");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }

    /** Hata sayfası — düzenden bağımsız çalışır (bootstrap sırasında da kullanılabilir). */
    public static function error(int $code, string $title, string $message = ''): void
    {
        if (!headers_sent()) {
            http_response_code($code);
            header('Content-Type: text/html; charset=utf-8');
        }
        // Hata sayfası veritabanı erişilemezken de çizilebilmeli; oturum
        // sorgusu patlarsa "giriş yapılmamış" varsayılır.
        try {
            $loggedIn = Auth::check();
        } catch (\Throwable) {
            $loggedIn = false;
        }

        echo self::capture('errors/error', [
            'code'     => $code,
            'title'    => $title,
            'message'  => $message,
            'loggedIn' => $loggedIn,
        ]);
    }
}
