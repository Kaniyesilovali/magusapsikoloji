<?php
declare(strict_types=1);

namespace Panel;

use RuntimeException;

/**
 * GitHub Contents API istemcisi — panelin içerik dosyalarını yazma yolu.
 *
 * Neden doğrudan diske yazılmıyor: site Eleventy ile derlenip FTPS ile atılıyor.
 * Sunucudaki `_site` çıktısına yazmak bir sonraki deploy'da silinirdi. Depo tek
 * gerçek kaynak; panel oraya commit atar, GitHub Actions siteyi yeniden yayınlar.
 *
 * Eşzamanlılık: her okuma dosyanın `sha` değerini de getirir, yazarken geri
 * gönderilir. Arada başkası (ya da bu chat'teki gibi başka bir oturum) aynı
 * dosyayı değiştirdiyse GitHub 409 döner ve yazma reddedilir — sessiz üzerine
 * yazma olmaz.
 */
final class Github
{
    private const API = 'https://api.github.com';

    public static function configured(): bool
    {
        return (string) Config::get('github.token', '') !== ''
            && (string) Config::get('github.repo', '') !== '';
    }

    /**
     * Dosyayı içeriğiyle ve sürüm kimliğiyle okur.
     * @return array{content:string,sha:string}
     */
    public static function read(string $path): array
    {
        $repo     = self::repo();
        $branch   = rawurlencode((string) Config::get('github.branch', 'main'));
        $response = self::request('GET', "/repos/{$repo}/contents/{$path}?ref={$branch}", null, $path);

        if (!isset($response['content'], $response['sha'])) {
            throw new RuntimeException("GitHub yanıtı beklenen alanları içermiyor: {$path}");
        }

        $decoded = base64_decode((string) $response['content'], true);
        if ($decoded === false) {
            throw new RuntimeException("Dosya içeriği çözülemedi: {$path}");
        }

        return ['content' => $decoded, 'sha' => (string) $response['sha']];
    }

    /** Dosyayı yeni içerikle commit'ler. $sha, read() ile alınan sürüm olmalı. */
    public static function write(string $path, string $content, string $sha, string $message): void
    {
        $repo = self::repo();
        self::request('PUT', "/repos/{$repo}/contents/{$path}", [
            'message' => $message,
            'content' => base64_encode($content),
            'sha'     => $sha,
            'branch'  => (string) Config::get('github.branch', 'main'),
        ], $path);
    }

    /** "sahip/depo" — baştaki/sondaki eğik çizgiler ayıklanır. */
    private static function repo(): string
    {
        return trim((string) Config::get('github.repo', ''), '/');
    }

    // ── Aktarım ─────────────────────────────────────────────────

    /**
     * @param  array<string,mixed>|null $body
     * @return array<string,mixed>
     */
    private static function request(string $method, string $endpoint, ?array $body, string $path): array
    {
        if (!self::configured()) {
            throw new RuntimeException('GitHub erişimi yapılandırılmamış (github.token / github.repo).');
        }

        $url     = self::API . $endpoint;
        $payload = $body !== null ? json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
        $headers = [
            'Accept: application/vnd.github+json',
            'Authorization: Bearer ' . (string) Config::get('github.token'),
            'User-Agent: MagusaPsikoloji-Panel',
            'X-GitHub-Api-Version: 2022-11-28',
        ];
        if ($payload !== null) {
            $headers[] = 'Content-Type: application/json';
        }

        [$status, $raw] = function_exists('curl_init')
            ? self::viaCurl($method, $url, $headers, $payload)
            : self::viaStream($method, $url, $headers, $payload);

        $decoded = json_decode($raw, true);
        $decoded = is_array($decoded) ? $decoded : [];

        if ($status >= 200 && $status < 300) {
            return $decoded;
        }

        throw new RuntimeException(self::errorMessage($status, $decoded, $path));
    }

    /** @return array{0:int,1:string} */
    private static function viaCurl(string $method, string $url, array $headers, ?string $payload): array
    {
        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        if ($payload !== null) {
            curl_setopt($handle, CURLOPT_POSTFIELDS, $payload);
        }

        $raw    = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $error  = curl_error($handle);
        curl_close($handle);

        if ($raw === false) {
            throw new RuntimeException('GitHub bağlantısı kurulamadı: ' . $error);
        }
        return [$status, (string) $raw];
    }

    /** cURL yoksa: allow_url_fopen ile aynı isteği kurar. */
    private static function viaStream(string $method, string $url, array $headers, ?string $payload): array
    {
        $context = stream_context_create(['http' => [
            'method'        => $method,
            'header'        => implode("\r\n", $headers),
            'content'       => $payload ?? '',
            'timeout'       => 20,
            'ignore_errors' => true,
        ]]);

        $raw = @file_get_contents($url, false, $context);
        if ($raw === false) {
            throw new RuntimeException('GitHub bağlantısı kurulamadı (cURL yok, allow_url_fopen da çalışmadı).');
        }

        // $http_response_header, file_get_contents tarafından bu kapsama enjekte edilir.
        $status = 0;
        foreach ($http_response_header ?? [] as $line) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $parts) === 1) {
                $status = (int) $parts[1];
            }
        }
        return [$status, $raw];
    }

    /** @param array<string,mixed> $decoded */
    private static function errorMessage(int $status, array $decoded, string $path): string
    {
        $detail = isset($decoded['message']) ? ' (' . (string) $decoded['message'] . ')' : '';

        return match (true) {
            $status === 401 || $status === 403 =>
                'GitHub erişimi reddedildi' . $detail . '. Token süresi dolmuş ya da "Contents: Read and write" izni verilmemiş olabilir.',
            $status === 404 =>
                "Dosya ya da depo bulunamadı: {$path}{$detail}. github.repo ayarını ve token'ın bu depoya erişimini kontrol edin.",
            $status === 409 || $status === 422 =>
                'Dosya bu ekranı açtığınızdan beri değişmiş' . $detail . '. Sayfayı yenileyip değişikliğinizi tekrar yapın — üzerine yazılmadı.',
            default =>
                "GitHub isteği başarısız (HTTP {$status}){$detail}.",
        };
    }
}
