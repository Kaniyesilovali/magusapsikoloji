<?php
declare(strict_types=1);

/**
 * Haftalık check-in hatırlatmaları — cPanel cron'undan haftada bir çalıştırılır.
 *
 *   0 9 * * 1  /usr/local/bin/php /home/<kullanıcı>/public_html/panel/cron/checkins.php
 *
 * Kime gider: döngüsü BAŞLATILMIŞ görüşmecilere. Kayıt olması yetmez; terapistin
 * görüşmeci sayfasından bir kez "Check-in bağlantısı gönder" demesi gerekir.
 * Neden böyle: "aktif tüm görüşmeciler" desek, cron kurulduğu an merkezin bütün
 * listesine e-posta çıkardı. Pilot üç-dört kişiyle yürüyor ve kime gittiğine
 * terapist karar veriyor; ayrıca bir "check-in açık" alanı eklemeye gerek yok,
 * ilk bağlantının kendisi kaydın ta kendisi.
 *
 * Günde bir çalıştırılsa da güvenlidir: aynı hafta doldurmuş ya da son altı gün
 * içinde bağlantı almış kimseye ikinci ileti gitmez.
 *
 * Israr etmez: son doldurulan check-in'den bu yana üç bağlantı cevapsız
 * kaldıysa o kişiye gönderim durur. Dördüncü, beşinci hatırlatma doldurma oranını
 * yükseltmiyor; kanalı değiştirmek gerekiyor (bkz. check-in-plani.md, Faz 5).
 * Terapistin elle gönderdiği bağlantı her zaman çalışır — durma yalnız cron için.
 *
 * Çıktı cron tarafından e-posta ile gönderilir, bu yüzden özet tek ekrana sığar.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Bu betik yalnızca komut satırından çalıştırılabilir.\n");
}

define('PANEL_BASE', '');
require dirname(__DIR__) . '/src/bootstrap.php';

use Panel\Checkins;
use Panel\Db;
use Panel\Notifications;
use Panel\Schema;
use Panel\Settings;

/** Aynı kişiye bu kadar gün geçmeden ikinci bağlantı gitmez. */
const MIN_DAYS_BETWEEN = 6;

/** Son dolan check-in'den beri bu kadar cevapsız bağlantıdan sonra susulur. */
const MAX_UNANSWERED = 3;

$startedAt = date('Y-m-d H:i:s');

if (!Schema::checkinsReady()) {
    exit("Check-in tabloları veritabanında yok. Panel → Sistem ekranından bekleyen güncellemeleri uygulayın.\n");
}

if (Settings::get('checkins_enabled', '1') !== '1') {
    echo "Check-in hatırlatmaları kapalı (settings.checkins_enabled = 0). Hiçbir şey yapılmadı.\n";
    Settings::set('checkin_last_run', $startedAt);
    Settings::set('checkin_last_result', 'kapalı');
    exit(0);
}

$due = Db::all(
    'SELECT c.id, c.full_name, c.email
       FROM clients c
      WHERE c.status = \'active\'
        AND c.email IS NOT NULL AND c.email <> \'\'
        -- Döngü başlatılmış: terapist en az bir kez bağlantı göndermiş.
        AND EXISTS (SELECT 1 FROM checkin_requests r WHERE r.client_id = c.id)
        -- Bu hafta zaten doldurmuş.
        AND NOT EXISTS (SELECT 1 FROM checkins k
                         WHERE k.client_id = c.id
                           AND YEARWEEK(k.created_at, 1) = YEARWEEK(NOW(), 1))
        -- Son günlerde bağlantı almış (cron günde bir koşsa da tekrar gitmesin).
        AND NOT EXISTS (SELECT 1 FROM checkin_requests r2
                         WHERE r2.client_id = c.id
                           AND r2.created_at > DATE_SUB(NOW(), INTERVAL ? DAY))
        -- Üst üste cevapsız kalan bağlantı sayısı sınırın altında.
        AND (SELECT COUNT(*) FROM checkin_requests r3
              WHERE r3.client_id = c.id
                AND r3.completed_at IS NULL
                AND r3.created_at > COALESCE(
                      (SELECT MAX(k2.created_at) FROM checkins k2 WHERE k2.client_id = c.id),
                      \'1000-01-01 00:00:00\')) < ?
   ORDER BY c.full_name',
    [MIN_DAYS_BETWEEN, MAX_UNANSWERED]
);

$sent   = 0;
$failed = 0;

foreach ($due as $row) {
    // Jeton her hâlükârda üretilir ve satırı kalır: gönderim başarısız olsa da
    // "bu haftaya bağlantı üretildi" bilgisi doldurma oranının paydasıdır.
    $token = Checkins::createRequest((int) $row['id']);

    if (Notifications::checkinRequest($row, Checkins::link($token))) {
        Checkins::markSent($token);
        $sent++;
        printf("  gönderildi  %s\n", $row['email']);
    } else {
        $failed++;
        printf("  BAŞARISIZ   %s\n", $row['email']);
    }
}

$summary = sprintf('%d gönderildi, %d başarısız, %d aday', $sent, $failed, count($due));

Settings::set('checkin_last_run', $startedAt);
Settings::set('checkin_last_result', $summary);

echo $summary . "\n";

// Başarısızlık varsa cron'un dikkat çekmesi için sıfırdan farklı çıkılır.
exit($failed > 0 ? 1 : 0);
