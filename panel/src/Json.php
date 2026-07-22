<?php
declare(strict_types=1);

namespace Panel;

use RuntimeException;

/**
 * Depodaki JSON veri dosyalarını okuma/yazma biçimi.
 *
 * Biçim, elle yazılmış dosyalarla birebir aynı tutulur (2 boşluk girinti,
 * kaçırılmamış Unicode ve eğik çizgi, sonda satır sonu). Aksi hâlde paneldeki
 * tek kelimelik bir düzeltme, dosyanın tamamını yeniden biçimlendiren dev bir
 * commit üretir ve git geçmişi okunmaz hâle gelir.
 */
final class Json
{
    /** @return array<mixed> */
    public static function decode(string $raw, string $label = 'JSON'): array
    {
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new RuntimeException("{$label} çözümlenemedi: " . json_last_error_msg());
        }
        return $data;
    }

    public static function pretty(mixed $data): string
    {
        $encoded = json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        if ($encoded === false) {
            throw new RuntimeException('JSON kodlanamadı: ' . json_last_error_msg());
        }

        // JSON_PRETTY_PRINT 4 boşluk kullanır, depodaki dosyalar 2. Dize içindeki
        // satır sonları JSON'da kaçırıldığı için satır başı boşlukları yalnız
        // girintidir; güvenle daraltılabilir.
        $encoded = preg_replace_callback(
            '/^ +/m',
            static fn (array $match): string => str_repeat(' ', (int) (strlen($match[0]) / 2)),
            $encoded
        );

        return (string) $encoded . "\n";
    }
}
