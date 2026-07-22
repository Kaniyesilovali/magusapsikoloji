<?php
declare(strict_types=1);

namespace Panel;

use RuntimeException;

/**
 * Üç sürücülü basit e-posta gönderici:
 *   mail — PHP mail() → sunucunun kendi posta servisi. cPanel'de en güvenilir yol:
 *          ağa çıkmaz, alan adı Cloudflare arkasındayken de çalışır, SPF doğal geçer.
 *   smtp — uzak SMTP sunucusu (AUTH LOGIN + SSL/STARTTLS)
 *   log  — dosyaya yazar (yerel geliştirme)
 *
 * Harici bağımlılık yok: cPanel'de composer garantisi olmadığı için SMTP istemcisi
 * burada elle yazılmıştır.
 *
 * ⚠ smtp kullanılacaksa host, Cloudflare tarafından proxy'lenen bir ad OLMAMALI
 * (ör. mail.<alanadi>): Cloudflare SMTP portlarını geçirmez, bağlantı zaman aşımına
 * uğrar. Ya 'mail' sürücüsünü ya da sunucunun proxy'siz gerçek adını kullanın.
 */
final class Mailer
{
    private static ?string $lastError = null;

    /** Son gönderim neden başarısız oldu? Yöneticiye gösterilir. */
    public static function lastError(): ?string
    {
        return self::$lastError;
    }

    public static function send(string $to, string $subject, string $htmlBody): bool
    {
        $driver = (string) Config::get('mail.driver', 'log');
        self::$lastError = null;

        try {
            $sent = match ($driver) {
                'smtp'  => self::sendSmtp($to, $subject, $htmlBody),
                'mail'  => self::sendMail($to, $subject, $htmlBody),
                default => self::sendLog($to, $subject, $htmlBody),
            };

            if (!$sent) {
                self::$lastError = "'{$driver}' sürücüsü gönderimi reddetti.";
            }
            return $sent;
        } catch (\Throwable $e) {
            self::$lastError = $e->getMessage();
            error_log('[panel] e-posta gönderilemedi: ' . $e->getMessage());
            return false;
        }
    }

    private static function headers(): array
    {
        $from     = (string) Config::get('mail.from');
        $fromName = (string) Config::get('mail.from_name', '');
        $encoded  = $fromName !== '' ? '=?UTF-8?B?' . base64_encode($fromName) . "?= <{$from}>" : $from;

        return [
            'MIME-Version'              => '1.0',
            'Content-Type'              => 'text/html; charset=UTF-8',
            'Content-Transfer-Encoding' => '8bit',
            'From'                      => $encoded,
            'Reply-To'                  => $from,
        ];
    }

    private static function sendMail(string $to, string $subject, string $body): bool
    {
        $headers = [];
        foreach (self::headers() as $key => $value) {
            $headers[] = "{$key}: {$value}";
        }

        // 5. parametre zarf göndericisini (-f) ayarlar. Bu olmadan zarf adresi
        // sunucunun varsayılan hesabı olur, SPF hizalanmaz ve ileti spam'e düşer.
        $from = (string) Config::get('mail.from');

        return mail(
            $to,
            '=?UTF-8?B?' . base64_encode($subject) . '?=',
            $body,
            implode("\r\n", $headers),
            '-f' . $from
        );
    }

    private static function sendLog(string $to, string $subject, string $body): bool
    {
        $path = (string) Config::get('mail.log_path', sys_get_temp_dir() . '/panel-mail.log');
        $entry = sprintf(
            "\n===== %s =====\nKime: %s\nKonu: %s\n\n%s\n",
            date('c'),
            $to,
            $subject,
            $body
        );
        return file_put_contents($path, $entry, FILE_APPEND | LOCK_EX) !== false;
    }

    private static function sendSmtp(string $to, string $subject, string $body): bool
    {
        $host = (string) Config::get('mail.smtp.host');
        $port = (int) Config::get('mail.smtp.port', 465);
        $enc  = (string) Config::get('mail.smtp.encryption', 'ssl');
        $user = (string) Config::get('mail.smtp.user');
        $pass = (string) Config::get('mail.smtp.pass');
        $from = (string) Config::get('mail.from');

        $target = ($enc === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
        $socket = @stream_socket_client($target, $errno, $errstr, 15, STREAM_CLIENT_CONNECT);
        if (!$socket) {
            throw new RuntimeException("SMTP bağlantısı kurulamadı ({$errno}): {$errstr}");
        }
        stream_set_timeout($socket, 15);

        $read = static function () use ($socket): string {
            $data = '';
            while (($line = fgets($socket, 515)) !== false) {
                $data .= $line;
                // Çok satırlı yanıtlarda son satır "250 " biçimindedir (4. karakter boşluk).
                if (strlen($line) < 4 || $line[3] === ' ') {
                    break;
                }
            }
            return $data;
        };
        $cmd = static function (string $command, string $expect) use ($socket, $read): void {
            fwrite($socket, $command . "\r\n");
            $response = $read();
            if (!str_starts_with($response, $expect)) {
                throw new RuntimeException("SMTP beklenmeyen yanıt ({$expect} bekleniyordu): " . trim($response));
            }
        };

        $read(); // karşılama
        $ehlo = 'EHLO ' . (parse_url((string) Config::get('app.url'), PHP_URL_HOST) ?: 'localhost');
        $cmd($ehlo, '250');

        if ($enc === 'tls') {
            $cmd('STARTTLS', '220');
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('STARTTLS başarısız.');
            }
            $cmd($ehlo, '250');
        }

        if ($user !== '') {
            $cmd('AUTH LOGIN', '334');
            $cmd(base64_encode($user), '334');
            $cmd(base64_encode($pass), '235');
        }

        $cmd('MAIL FROM:<' . $from . '>', '250');
        $cmd('RCPT TO:<' . $to . '>', '250');
        $cmd('DATA', '354');

        $headerLines = [];
        foreach (self::headers() as $key => $value) {
            $headerLines[] = "{$key}: {$value}";
        }
        $headerLines[] = 'To: ' . $to;
        $headerLines[] = 'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=';
        $headerLines[] = 'Date: ' . date('r');

        // Nokta ile başlayan satırlar SMTP'de kaçırılmalı (RFC 5321 §4.5.2).
        $safeBody = preg_replace('/^\./m', '..', str_replace("\n", "\r\n", $body));
        $cmd(implode("\r\n", $headerLines) . "\r\n\r\n" . $safeBody . "\r\n.", '250');
        $cmd('QUIT', '221');
        fclose($socket);

        return true;
    }

    /** Basit, markasız HTML gövde — panel e-postalarının tek şablonu. */
    public static function template(string $heading, string $paragraph, ?string $buttonLabel = null, ?string $buttonUrl = null, string $footnote = ''): string
    {
        $button = '';
        if ($buttonLabel !== null && $buttonUrl !== null) {
            $button = '<p style="margin:24px 0"><a href="' . e($buttonUrl) . '" style="background:#4A7C6F;color:#fff;padding:12px 22px;border-radius:8px;text-decoration:none;display:inline-block;font-weight:600">' . e($buttonLabel) . '</a></p>'
                . '<p style="font-size:13px;color:#5A6B62">Düğme çalışmazsa bu adresi tarayıcınıza yapıştırın:<br><span style="word-break:break-all">' . e($buttonUrl) . '</span></p>';
        }

        return '<div style="font-family:system-ui,-apple-system,Segoe UI,sans-serif;max-width:560px;margin:0 auto;padding:24px;color:#2C3830">'
            . '<h1 style="font-size:20px;margin:0 0 12px">' . e($heading) . '</h1>'
            . '<p style="line-height:1.6;margin:0">' . nl2br(e($paragraph)) . '</p>'
            . $button
            . ($footnote !== '' ? '<p style="font-size:13px;color:#8A9E94;border-top:1px solid #E4DDD5;padding-top:16px;margin-top:24px">' . e($footnote) . '</p>' : '')
            . '</div>';
    }
}
