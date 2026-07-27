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

    /**
     * Yerel değişkenler `__` ile başlar çünkü extract() onların alanına giriyor:
     * EXTR_SKIP var olanın üzerine YAZMAZ, yani buradaki her sade ad şablona
     * giden aynı adlı veriyi sessizce yutar. `$file` böyle kaybolmuştu —
     * cases/form.php'de dosya kaydı yerine şablonun yolu geliyor, `$file['...']`
     * de yarı çizilmiş sayfanın ortasında TypeError'a düşüyordu.
     */
    private static function capture(string $__template, array $__data): string
    {
        $__file = self::DIR . str_replace(['..', '\\'], '', $__template) . '.php';
        if (!is_file($__file)) {
            throw new \RuntimeException("Şablon bulunamadı: {$__template}");
        }
        extract($__data, EXTR_SKIP);
        ob_start();
        try {
            require $__file;
            return (string) ob_get_clean();
        } catch (\Throwable $e) {
            // Yarım çıktı tamponda kalırsa istek sonunda basılır ve hata
            // sayfasının önüne yapışır; iki sayfa iç içe görünür.
            ob_end_clean();
            throw $e;
        }
    }

    /** Hata sayfası — düzenden bağımsız çalışır (bootstrap sırasında da kullanılabilir). */
    public static function error(int $code, string $title, string $message = ''): void
    {
        if (!headers_sent()) {
            http_response_code($code);
            header('Content-Type: text/html; charset=utf-8');
        }
        // Hata sayfası tek başına bir belgedir: yarım kalmış her çıktı atılır,
        // yoksa iki <html> aynı sayfada üst üste binerdi.
        while (ob_get_level() > 0) {
            ob_end_clean();
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
