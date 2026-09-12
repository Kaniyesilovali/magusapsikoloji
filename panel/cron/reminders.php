<?php
declare(strict_types=1);

/**
 * Randevu hatırlatma e-postaları — cPanel cron'undan saatte bir çalıştırılır.
 *
 *   /usr/local/bin/php /home/<kullanıcı>/public_html/panel/cron/reminders.php
 *
 * Saatte bir çalışması güvenlidir: her randevu `reminder_sent_at` ile bir kez
 * işaretlenir, ikinci kez uyarı gitmez. Gönderim başarısız olursa alan boş
 * bırakılır ve sonraki koşuda yeniden denenir; deneme, randevu pencereden
 * çıkınca kendiliğinden durur — sonsuza kadar tekrar eden bir kuyruk oluşmaz.
 *
 * Çıktısı olan her koşuyu cPanel "Cron E-posta" adresine yollar. Bu yüzden betik
 * yalnız iş yaptığında ya da bir şey bozulduğunda yazar: gönderilecek randevu
 * yoksa tek satır bile basmaz ve o saat için e-posta çıkmaz. Saatte bir koşan bir
 * işin "0 aday" demesi haber değil; günde yirmi dört kez söylenince gerçek uyarı
 * da o yığının içinde kaybolur. Her koşunun sonucu yine settings'e yazılır, panel
 * → Sistem ekranı sessiz koşuları da gösterir.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Bu betik yalnızca komut satırından çalıştırılabilir.\n");
}

define('PANEL_BASE', '');
require dirname(__DIR__) . '/src/bootstrap.php';

use Panel\Db;
use Panel\Notifications;
use Panel\Schema;
use Panel\Scheduling;
use Panel\Settings;

$startedAt = date('Y-m-d H:i:s');

if (!Schema::remindersReady()) {
    // Bu bir arıza: cron koşuyor ama hiçbir hatırlatma gidemiyor. Sessiz kalırsa
    // kimse fark etmez, o yüzden STDERR'e yazıp sıfırdan farklı çıkıyoruz.
    fwrite(STDERR, "Hatırlatma alanı veritabanında yok. Panel → Sistem ekranından bekleyen güncellemeleri uygulayın.\n");
    exit(1);
}

if (Settings::get('reminders_enabled', '1') !== '1') {
    // Kapalı olması verilmiş bir karardır, arıza değil — sessizce geçiyoruz.
    // Panel → Sistem ekranı "kapalı" yazdığı için durum yine görünür.
    Settings::set('reminder_last_run', $startedAt);
    Settings::set('reminder_last_result', 'kapalı');
    exit(0);
}

$hours = max(1, min(168, (int) Settings::get('reminder_hours_before', '24')));

// Pencere: şu an ile N saat sonrası arası. Geçmiş randevular hiç alınmaz, bu
// yüzden cron günlerce durmuş olsa bile eski randevular için uyarı gitmez.
$due = Db::all(
    "SELECT a.id, a.starts_at, a.location,
            c.full_name AS client_name, c.email AS client_email,
            t.full_name AS therapist_name
       FROM appointments a
       JOIN clients c ON c.id = a.client_id
       JOIN users   t ON t.id = a.therapist_id
      WHERE a.reminder_sent_at IS NULL
        AND a.status IN ('scheduled', 'confirmed')
        AND a.starts_at > NOW()
        AND a.starts_at <= DATE_ADD(NOW(), INTERVAL ? HOUR)
        AND c.email IS NOT NULL AND c.email <> ''
   ORDER BY a.starts_at",
    [$hours]
);

$sent = 0;
$failed = 0;

foreach ($due as $row) {
    if (Notifications::reminder($row)) {
        Db::run('UPDATE appointments SET reminder_sent_at = NOW() WHERE id = ?', [$row['id']]);
        $sent++;
        printf("  gönderildi  %s  %s\n", substr((string) $row['starts_at'], 0, 16), $row['client_email']);
    } else {
        $failed++;
        printf("  BAŞARISIZ   %s  %s\n", substr((string) $row['starts_at'], 0, 16), $row['client_email']);
    }
}

$summary = sprintf(
    '%d gönderildi, %d başarısız, %d aday (%d saatlik pencere)',
    $sent,
    $failed,
    count($due),
    $hours
);

Settings::set('reminder_last_run', $startedAt);
Settings::set('reminder_last_result', $summary);

// Özet yalnız yapılacak bir iş çıktığında basılır; boş geçen saatler sessizdir.
if ($sent > 0 || $failed > 0) {
    echo $summary . "\n";
}

// Başarısızlık varsa cron'un dikkat çekmesi için sıfırdan farklı çıkılır.
exit($failed > 0 ? 1 : 0);
