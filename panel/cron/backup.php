<?php
declare(strict_types=1);

/**
 * Otomatik şifreli veritabanı yedeği — cPanel cron'undan günde bir çalıştırılır.
 *
 *   0 3 * * *  /opt/cpanel/ea-php83/root/usr/bin/php /home/<kullanıcı>/public_html/panel/cron/backup.php
 *
 * Kurulum (bir kez):
 *   php panel/cron/backup.php --anahtar-uret     # anahtarı üretir, config'e yazılır
 *
 * Kurtarma:
 *   php panel/cron/backup.php --coz <dosya> <hedef.sql>
 *   sonra phpMyAdmin → İçe Aktar
 *
 * Gece 03:00 seçilmesinin sebebi yük değil tutarlılık: seans saatlerinin dışında
 * alınan yedek, yarım kalmış bir kaydın ortasına denk gelmiyor.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Bu betik yalnızca komut satırından çalıştırılabilir.\n");
}

define('PANEL_BASE', '');
require dirname(__DIR__) . '/src/bootstrap.php';

use Panel\Backup;
use Panel\Settings;

$args = array_slice($argv, 1);

// ── Anahtar üretimi ───────────────────────────────────────────
if (in_array('--anahtar-uret', $args, true)) {
    echo "Aşağıdaki satırı config.php içindeki 'security' bölümüne ekleyin:\n\n";
    echo "    'backup_key' => '" . Backup::newKey() . "',\n\n";
    echo "Sonra bu değeri parola yöneticinize de kaydedin — note_key'den AYRI bir\n";
    echo "kayıt olarak. Bu anahtar kaybolursa yedekler açılamaz.\n";
    exit(0);
}

// ── Kurtarma ──────────────────────────────────────────────────
if (($args[0] ?? '') === '--coz') {
    $source = $args[1] ?? '';
    $target = $args[2] ?? '';

    if ($source === '' || $target === '') {
        exit("Kullanım: php backup.php --coz <yedek dosyası> <hedef.sql>\n");
    }

    try {
        Backup::decrypt($source, $target);
        echo "✓ Çözüldü: {$target}\n";
        echo "phpMyAdmin → İçe Aktar ile geri yükleyebilirsiniz.\n";
        exit(0);
    } catch (Throwable $e) {
        fwrite(STDERR, '✗ ' . $e->getMessage() . "\n");
        exit(1);
    }
}

// ── Yedek alma ────────────────────────────────────────────────
$startedAt = date('Y-m-d H:i:s');

try {
    $result = Backup::run();

    $summary = sprintf(
        '%s — %s, %d tablo%s',
        $result['file'],
        self_readable($result['bytes']),
        $result['tables'],
        $result['removed'] > 0 ? ", {$result['removed']} eski yedek silindi" : ''
    );

    Settings::set('backup_last_run', $startedAt);
    Settings::set('backup_last_result', $summary);

    echo $summary . "\n";
    exit(0);
} catch (Throwable $e) {
    // Başarısızlık sessiz kalmamalı: yedek alınmadığı gün, alınmadığı fark
    // edilmeyen gündür. Hem ekrana hem Sistem ekranına yazılır.
    Settings::set('backup_last_run', $startedAt);
    Settings::set('backup_last_result', 'HATA: ' . $e->getMessage());

    fwrite(STDERR, '✗ Yedek alınamadı: ' . $e->getMessage() . "\n");
    exit(1);
}

function self_readable(int $bytes): string
{
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 1) . ' MB';
    }
    return max(1, (int) round($bytes / 1024)) . ' KB';
}
