<?php
declare(strict_types=1);

namespace Panel;

use PDOException;

/**
 * migrations/*.sql dosyalarını sırayla uygular ve uygulananları kaydeder.
 * Hem CLI (migrations/migrate.php) hem de ilk kurulum ekranı bunu kullanır —
 * cPanel'de SSH olmayabileceği için web üzerinden de çalıştırılabilmesi gerekir.
 */
final class Migrator
{
    private const DIR = PANEL_ROOT . '/migrations';

    public static function ensureTrackingTable(): void
    {
        Db::run('CREATE TABLE IF NOT EXISTS schema_migrations (
            filename   VARCHAR(120) NOT NULL,
            applied_at DATETIME     NOT NULL,
            PRIMARY KEY (filename)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }

    /** @return string[] Henüz uygulanmamış dosya adları. */
    public static function pending(): array
    {
        self::ensureTrackingTable();
        $applied = array_column(Db::all('SELECT filename FROM schema_migrations'), 'filename');

        $files = glob(self::DIR . '/*.sql') ?: [];
        sort($files);

        return array_values(array_diff(array_map('basename', $files), $applied));
    }

    /**
     * Bekleyen migration'ları uygular.
     * @return string[] Uygulanan dosya adları.
     */
    public static function run(): array
    {
        $ran = [];
        foreach (self::pending() as $name) {
            $path = self::DIR . '/' . $name;

            // İfadeler satır sonundaki noktalı virgülle ayrılır; şema dosyalarında
            // dize içinde noktalı virgül bulunmaz.
            foreach (preg_split('/;\s*[\r\n]+/', (string) file_get_contents($path)) ?: [] as $sql) {
                $sql = trim($sql);
                if ($sql === '' || str_starts_with($sql, '--')) {
                    continue;
                }
                Db::pdo()->exec($sql);
            }

            Db::run('INSERT INTO schema_migrations (filename, applied_at) VALUES (?, NOW())', [$name]);
            $ran[] = $name;
        }
        return $ran;
    }

    /** Şema kurulmuş mu? (users tablosu var mı) */
    public static function schemaReady(): bool
    {
        try {
            Db::value('SELECT 1 FROM users LIMIT 1');
            return true;
        } catch (PDOException) {
            return false;
        }
    }

    /** Hiç kullanıcı yoksa panel "ilk kurulum" modundadır. */
    public static function needsSetup(): bool
    {
        if (!self::schemaReady()) {
            return true;
        }
        return (int) Db::value('SELECT COUNT(*) FROM users') === 0;
    }
}
