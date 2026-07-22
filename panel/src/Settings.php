<?php
declare(strict_types=1);

namespace Panel;

/**
 * `settings` tablosundaki anahtar/değer ayarları.
 *
 * Config dosyasından farkı: bunlar çalışırken panelden değiştirilebilen işletme
 * ayarlarıdır (seans süresi, KVKK metni), sırlar değil. Sırlar config.php'de kalır.
 */
final class Settings
{
    /** @var array<string,string>|null İstek başına tek okuma. */
    private static ?array $cache = null;

    public static function get(string $key, string $default = ''): string
    {
        if (self::$cache === null) {
            self::$cache = [];
            foreach (Db::all('SELECT setting_key, setting_value FROM settings') as $row) {
                self::$cache[(string) $row['setting_key']] = (string) ($row['setting_value'] ?? '');
            }
        }
        $value = self::$cache[$key] ?? '';

        return $value !== '' ? $value : $default;
    }

    public static function set(string $key, string $value): void
    {
        Db::run(
            'INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()',
            [$key, $value]
        );
        self::$cache = null;
    }
}
