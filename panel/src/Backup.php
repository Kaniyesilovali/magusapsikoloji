<?php
declare(strict_types=1);

namespace Panel;

use PDO;
use RuntimeException;

/**
 * Otomatik şifreli veritabanı yedeği.
 *
 * Neden var: yedek bugüne kadar tamamen elle alınıyordu ve elle alınan yedek,
 * alınmayan yedektir. cPanel'in kendi tam yedeği de var ama onu cPanel geri
 * yükleyemiyor — hosting desteği gerekiyor. Buradaki dosya phpMyAdmin'e
 * doğrudan verilebilir.
 *
 * Neden şifreli: dökümde onam kayıtları, telefonlar, doğum tarihleri var.
 * (Seans notları zaten kendi içinde şifreli, ama gerisi düz metin.) Sunucuda
 * düz duran bir döküm, veritabanı erişimi olmayan birinin eline geçebilecek en
 * kolay tam kopyadır.
 *
 * Neden AYRI bir anahtar: `security.note_key` ile şifrelense, tek bir anahtarın
 * kaybı hem notları hem yedeği götürürdü — yedeğin varlık sebebi tam olarak bu
 * riskin ikiye bölünmesi. İki anahtar da parola yöneticisinde, veritabanından
 * ayrı durmalı.
 *
 * Sınırı açıkça söylemek gerekir: bu yedek AYNI SUNUCUDA duruyor. Sunucu
 * gidince yedek de gider. Ayda bir indirilmesi hâlâ gerekli; bu iş onun yerine
 * değil, onu unutulduğu haftalar için yapılıyor.
 */
final class Backup
{
    /** Şifreli dökümün uzantısı — şifresiz bir .sql ile karışmasın. */
    private const EXT = '.sql.gz.enc';

    /** Akış şifrelemesinde tek seferde işlenen parça. */
    private const CHUNK = 262144;   // 256 KB

    /** Kaç yedek saklanacağı. Günlük cron ile ~2 hafta geriye gidilebilir. */
    public const KEEP = 14;

    /**
     * Yedeklerin dizini — public_html'in DIŞINDA.
     *
     * Webroot altında olsaydı dosya adı tahmin edilebilir olmasa bile sunucu
     * yapılandırması bir gün değişip dizin listelemesi açılabilirdi. Şifreli
     * olması bu riski küçültür ama sıfırlamaz.
     */
    public static function dir(): string
    {
        $configured = trim((string) Config::get('backup.path', ''));
        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        return dirname(PANEL_ROOT, 2) . '/magusa-panel-backups';
    }

    public static function keyReady(): bool
    {
        return self::key(false) !== null;
    }

    /** @return ?string 32 baytlık ham anahtar; yoksa null. */
    private static function key(bool $strict = true): ?string
    {
        if (!function_exists('sodium_crypto_secretstream_xchacha20poly1305_init_push')) {
            if ($strict) {
                throw new RuntimeException('PHP sodium eklentisi yüklü değil; şifreli yedek alınamaz.');
            }
            return null;
        }

        $raw = base64_decode((string) Config::get('security.backup_key', ''), true);
        if ($raw === false || strlen($raw) !== SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES) {
            if ($strict) {
                throw new RuntimeException('security.backup_key geçersiz: 32 baytlık base64 anahtar bekleniyor.');
            }
            return null;
        }

        return $raw;
    }

    /** Yeni bir anahtar üretir — kurulumda bir kez, elle config'e yazılmak üzere. */
    public static function newKey(): string
    {
        return base64_encode(random_bytes(SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES));
    }

    /**
     * Yedeği alır, şifreler, eskileri siler.
     *
     * @return array{file:string,bytes:int,tables:int,removed:int}
     */
    public static function run(): array
    {
        $key = self::key();
        $dir = self::dir();

        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new RuntimeException("Yedek dizini oluşturulamadı: {$dir}");
        }

        [$sql, $tables] = self::dump();

        $path = $dir . '/' . date('Y-m-d_His') . self::EXT;
        self::writeEncrypted($path, gzencode($sql, 6), (string) $key);
        @chmod($path, 0600);

        // Döküm bellekten hemen düşsün: içinde bütün görüşmeci kayıtları var.
        sodium_memzero($sql);

        return [
            'file'    => basename($path),
            'bytes'   => (int) filesize($path),
            'tables'  => $tables,
            'removed' => self::rotate(),
        ];
    }

    /**
     * Dökümü PHP tarafında üretir.
     *
     * `mysqldump` daha iyisini yapardı ama cPanel'de `shell_exec` kapalı
     * olabiliyor ve yedeğin çalışması sunucu yapılandırmasının insafına
     * bırakılamaz. Bu klinik ölçeğinde tablolar birkaç MB; PDO ile okumak
     * yeterli ve her yerde çalışır.
     *
     * @return array{0:string,1:int} [sql, tablo sayısı]
     */
    private static function dump(): array
    {
        $pdo    = Db::pdo();
        $tables = array_column($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM), 0);

        $out = "-- Mağusa Psikoloji panel yedeği\n"
             . '-- ' . date('c') . "\n"
             . "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            $name   = (string) $table;
            $create = $pdo->query('SHOW CREATE TABLE `' . $name . '`')->fetch(PDO::FETCH_NUM);

            $out .= "DROP TABLE IF EXISTS `{$name}`;\n" . $create[1] . ";\n\n";

            // Satırlar tek tek çekiliyor: bütün tabloyu diziye almak, büyüyen
            // audit_log'da belleği gereksiz yere şişirirdi.
            $rows = $pdo->query('SELECT * FROM `' . $name . '`');
            while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
                $values = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $values[] = 'NULL';
                        continue;
                    }
                    // BLOB sütunları (şifreli notlar, nonce'lar) ikili veri;
                    // tırnak içine konursa bozulur, hex yazılır.
                    $values[] = self::isBinary((string) $value)
                        ? '0x' . bin2hex((string) $value)
                        : $pdo->quote((string) $value);
                }
                $out .= "INSERT INTO `{$name}` VALUES (" . implode(',', $values) . ");\n";
            }
            $out .= "\n";
        }

        $out .= "SET FOREIGN_KEY_CHECKS=1;\n";

        return [$out, count($tables)];
    }

    /** UTF-8 olmayan ya da NUL taşıyan değer ikili sayılır. */
    private static function isBinary(string $value): bool
    {
        return str_contains($value, "\0") || !mb_check_encoding($value, 'UTF-8');
    }

    /**
     * secretstream ile parça parça yazar.
     *
     * Tek bir secretbox çağrısı dökümün tamamını bir kez daha bellekte
     * çoğaltırdı; akış şifrelemesi 256 KB'lik parçalarla ilerliyor ve dosyanın
     * ortasından kesilmiş bir yedeği çözerken de belli oluyor.
     */
    private static function writeEncrypted(string $path, string $data, string $key): void
    {
        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new RuntimeException("Yedek dosyası yazılamadı: {$path}");
        }

        [$state, $header] = sodium_crypto_secretstream_xchacha20poly1305_init_push($key);
        fwrite($handle, $header);

        $total = strlen($data);
        for ($offset = 0; $offset < $total; $offset += self::CHUNK) {
            $chunk = substr($data, $offset, self::CHUNK);
            $last  = ($offset + self::CHUNK) >= $total;

            fwrite($handle, sodium_crypto_secretstream_xchacha20poly1305_push(
                $state,
                $chunk,
                '',
                $last ? SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL : 0
            ));
        }

        fclose($handle);
    }

    /**
     * Şifreli yedeği geri açar. Kurtarma anında elde yalnız bu kod ve anahtar
     * olacağı için çözme yolu yedeğin yanında durmalı — ayrı bir araca
     * bırakılırsa o araç kaybolur.
     */
    public static function decrypt(string $path, string $target): void
    {
        $key = self::key();

        $in = fopen($path, 'rb');
        if ($in === false) {
            throw new RuntimeException("Yedek okunamadı: {$path}");
        }

        $header = (string) fread($in, SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES);
        $state  = sodium_crypto_secretstream_xchacha20poly1305_init_pull($header, (string) $key);
        if ($state === false) {
            fclose($in);
            throw new RuntimeException('Yedek başlığı okunamadı: anahtar yanlış ya da dosya bozuk.');
        }

        $gz = '';
        while (!feof($in)) {
            $block = (string) fread($in, self::CHUNK + SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_ABYTES);
            if ($block === '') {
                break;
            }
            $result = sodium_crypto_secretstream_xchacha20poly1305_pull($state, $block);
            if ($result === false) {
                fclose($in);
                throw new RuntimeException('Yedek çözülemedi: anahtar yanlış ya da dosya bozuk.');
            }
            $gz .= $result[0];
        }
        fclose($in);

        $sql = gzdecode($gz);
        if ($sql === false) {
            throw new RuntimeException('Yedek açıldı ama sıkıştırma bozuk.');
        }

        if (file_put_contents($target, $sql) === false) {
            throw new RuntimeException("Çözülen yedek yazılamadı: {$target}");
        }
    }

    /** @return int Silinen eski yedek sayısı. */
    private static function rotate(): int
    {
        $files = self::files();
        if (count($files) <= self::KEEP) {
            return 0;
        }

        $removed = 0;
        foreach (array_slice($files, self::KEEP) as $file) {
            if (@unlink($file['path'])) {
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * Yeniden eskiye sıralı yedek listesi.
     *
     * @return list<array{path:string,name:string,bytes:int,time:int}>
     */
    public static function files(): array
    {
        $found = glob(self::dir() . '/*' . self::EXT) ?: [];

        $files = [];
        foreach ($found as $path) {
            $files[] = [
                'path'  => $path,
                'name'  => basename($path),
                'bytes' => (int) filesize($path),
                'time'  => (int) filemtime($path),
            ];
        }

        usort($files, static fn (array $a, array $b): int => $b['time'] <=> $a['time']);

        return $files;
    }

    /**
     * Sistem ekranının okuduğu özet.
     *
     * @return array{ready:bool,dir:string,count:int,newest:?array,bytes:int,lastRun:?string,lastResult:?string}
     */
    public static function status(): array
    {
        $files = self::files();

        return [
            'ready'      => self::keyReady(),
            'dir'        => self::dir(),
            'count'      => count($files),
            'newest'     => $files[0] ?? null,
            'bytes'      => array_sum(array_column($files, 'bytes')),
            'lastRun'    => Settings::get('backup_last_run', '') ?: null,
            'lastResult' => Settings::get('backup_last_result', '') ?: null,
        ];
    }
}
