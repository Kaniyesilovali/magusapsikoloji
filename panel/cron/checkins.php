<?php
declare(strict_types=1);

/**
 * Haftalık check-in hatırlatmaları — cPanel cron'undan haftada bir çalıştırılır.
 *
 *   0 9 * * 1  /usr/local/bin/php /home/<kullanıcı>/public_html/panel/cron/checkins.php
 *
 * Kime gider: döngüsü BAŞLATILMIŞ ve haftalık gönderimi AÇIK görüşmecilere.
 * Kayıt olması yetmez; terapistin görüşmeci sayfasından bir kez "Check-in
 * bağlantısı gönder" demesi gerekir. Neden böyle: "aktif tüm görüşmeciler"
 * desek, cron kurulduğu an merkezin bütün listesine e-posta çıkardı.
 *
 * İlk bağlantı döngüyü başlatır, clients.checkin_auto ise onu durdurup yeniden
 * açar (panel → Check-in → gönderim listesi, ya da görüşmeci sayfasındaki
 * anahtar). İkisi ayrı sorular: "bu kişiyle check-in yapıyor muyuz" ve "bu
 * dönem haftalık e-posta çıksın mı".
 *
 * Kimin sırada olduğunu bu dosya KENDİ hesaplamıyor: kural Checkins::due()
 * içinde ve panelin gönderim listesi de aynı yerden okuyor. İki kopya
 * olsaydı ekran "sırada" derken cron susabilir ve kimse fark etmezdi.
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

$due = Checkins::due();

// Kapalı olanlar sayılıyor ama listelenmiyor: özet cron e-postasında okunuyor ve
// "3 gönderildi" satırının yanında "2 kapalı" görmek, gönderimin azalmasının
// arıza mı yoksa verilmiş bir karar mı olduğunu tek bakışta söylüyor.
$off = Schema::checkinDeliveryReady()
    ? (int) Db::value('SELECT COUNT(*) FROM clients WHERE status = \'active\' AND checkin_auto = 0')
    : 0;

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

$summary = sprintf('%d gönderildi, %d başarısız, %d aday', $sent, $failed, count($due))
    . ($off > 0 ? sprintf(', %d kapalı', $off) : '');

Settings::set('checkin_last_run', $startedAt);
Settings::set('checkin_last_result', $summary);

echo $summary . "\n";

// Başarısızlık varsa cron'un dikkat çekmesi için sıfırdan farklı çıkılır.
exit($failed > 0 ? 1 : 0);
