<?php
declare(strict_types=1);

namespace Panel\Controllers;

use Panel\Audit;
use Panel\Auth;
use Panel\Crypto;
use Panel\Db;
use Panel\Github;
use Panel\Mailer;
use Panel\Migrator;
use Panel\Schema;
use Panel\Settings;
use Panel\View;
use Throwable;

/**
 * Sistem durumu ve veritabanı güncellemeleri.
 *
 * Neden var: ilk kurulum ekranı, ilk süper admin oluşur oluşmaz kalıcı olarak
 * 404'e kapanıyor ve cPanel'de SSH garantisi yok. Bu ikisi birleşince, kurulmuş
 * bir panelde yeni bir migration'ı uygulamanın hiçbir yolu kalmıyordu; şema
 * değişikliği getiren her sürüm sunucuda sessizce eksik kalırdı.
 *
 * Sadece süper adminde. Migration uygulamak geri alınamaz bir işlemdir.
 */
final class SystemController
{
    public function index(): void
    {
        $actor = Auth::requirePermission('settings.manage');

        View::render('system/index', [
            'title'    => 'Sistem',
            'pending'  => Migrator::pending(),
            'applied'  => Db::all('SELECT * FROM schema_migrations ORDER BY filename'),
            'checks'    => $this->checks(),
            'reminder'  => $this->reminderStatus(),
            'checkin'   => $this->checkinStatus(),
            'mail'      => Mailer::summary(),
            'phpBinary' => $this->phpBinary(),
            'actor'     => $actor,
        ]);
    }

    /**
     * Test e-postası — yöneticinin kendi adresine.
     *
     * Davet ve hatırlatma e-postaları gönderilmediğinde tek görünen belirti,
     * ilgili ekrandaki tek satırlık uyarıydı; sorunun yapılandırmada mı yoksa
     * alıcı tarafında mı olduğunu anlamanın yolu yoktu. Burada hem hata metni
     * hem de geçen süre gösteriliyor: 15 saniyeye dayanan bir gönderim, SMTP
     * portunun (çoğunlukla Cloudflare tarafından) kapatıldığını söyler.
     */
    public function testMail(): void
    {
        $actor = Auth::requirePermission('settings.manage');

        $to = trim((string) ($actor['email'] ?? ''));
        if ($to === '') {
            flash('error', 'Hesabınızda kayıtlı e-posta adresi yok; test gönderilemedi.');
            redirect('/sistem');
        }

        $started = microtime(true);
        $sent = Mailer::send(
            $to,
            'Mağusa Psikoloji panel — test e-postası',
            Mailer::template(
                'Test e-postası',
                'Bu ileti panelin Sistem ekranından gönderildi. Elinize ulaştıysa davet ve '
                . 'randevu hatırlatma e-postaları da sunucudan çıkıyor demektir.',
                null,
                null,
                'Gelen kutusunda değil de spam klasöründe bulduysanız alan adının SPF/DKIM kayıtları eksik olabilir.'
            )
        );
        $seconds = round(microtime(true) - $started, 1);
        $error   = Mailer::lastError();

        Audit::log('system.mail_tested', 'mail', null, [
            'alici'  => $to,
            'surucu' => (string) Mailer::summary()['driver'],
            'sonuc'  => $sent ? 'gonderildi' : ($error ?? 'bilinmiyor'),
        ]);

        if (!$sent) {
            flash('error', "Test e-postası gönderilemedi ({$seconds} sn): " . ($error ?? 'sebep bildirilmedi'));
            redirect('/sistem');
        }

        if (!Mailer::isLive()) {
            flash('warning', 'Sürücü “log” — ileti gönderilmedi, yalnız dosyaya yazıldı. '
                . 'Gerçek gönderim için yapılandırmadaki mail.driver değerini “mail” ya da “smtp” yapın.');
            redirect('/sistem');
        }

        flash('success', "Test e-postası {$to} adresine gönderildi ({$seconds} sn). "
            . 'Birkaç dakika içinde gelmezse spam klasörüne de bakın.');
        redirect('/sistem');
    }

    public function migrate(): void
    {
        $actor = Auth::requirePermission('settings.manage');

        $pending = Migrator::pending();
        if ($pending === []) {
            flash('info', 'Bekleyen güncelleme yok, veritabanı güncel.');
            redirect('/sistem');
        }

        try {
            $ran = Migrator::run();
        } catch (Throwable $e) {
            // Yarım kalan migration'ı gizlemek en kötüsü olurdu: hangi dosyanın
            // hangi ifadede patladığı Migrator'ün mesajında geliyor, aynen gösterilir.
            Audit::log('system.migrated', 'schema', null, ['hata' => $e->getMessage()]);
            flash('error', 'Güncelleme uygulanamadı: ' . $e->getMessage());
            redirect('/sistem');
        }

        Audit::log('system.migrated', 'schema', null, ['uygulanan' => $ran]);
        flash('success', count($ran) . ' güncelleme uygulandı: ' . implode(', ', $ran));
        redirect('/sistem');
    }

    // ── Yardımcılar ─────────────────────────────────────────────

    /**
     * Cron komutunda gösterilecek PHP yorumlayıcısı.
     *
     * `/usr/local/bin/php` sunucunun **varsayılan** sürümüdür ve alan adı için
     * seçilen sürümle aynı olmak zorunda değil. Aradaki fark sessiz bir arızaya
     * yol açıyordu: cron koşuyor, veritabanına yazıyor, ama `openssl` bulunmadığı
     * için SMTP soketi açılamıyor ve hatırlatma e-postası hiç gönderilmeden
     * "başarısız" sayılıyordu. Web tarafı doğru sürümle çalıştığı için test
     * e-postası geçiyor, cron geçmiyordu — teşhisi en zor hâli.
     *
     * Bu yüzden komut, panelin o an çalıştığı sürümün cPanel/EasyApache yolundan
     * üretilir; PHP sürümü yükseltildiğinde ekrandaki komut kendiliğinden düzelir.
     * open_basedir yüzünden yol doğrulanamazsa da aynı yol yazılır: yanlış olma
     * ihtimali, varsayılan sürüme geri düşmenin sessiz arızayı geri getirme
     * ihtimalinden düşük.
     */
    private function phpBinary(): string
    {
        $eaPath = sprintf(
            '/opt/cpanel/ea-php%d%d/root/usr/bin/php',
            PHP_MAJOR_VERSION,
            PHP_MINOR_VERSION
        );

        foreach ([$eaPath, '/usr/local/bin/php'] as $candidate) {
            if (@is_executable($candidate)) {
                return $candidate;
            }
        }

        return $eaPath;
    }

    /**
     * Hatırlatma cron'unun durumu. Cron kurulmadıysa hiç çalışmamış olur ve
     * bunu ancak buradan görebilirsiniz — sessizce göndermemek, gönderdiğini
     * sanmaktan daha kötü bir hata değil ama fark edilmesi gerekir.
     *
     * @return array{ready:bool,enabled:bool,hours:int,lastRun:?string,lastResult:?string,queued:?int}
     */
    private function reminderStatus(): array
    {
        $ready = Schema::remindersReady();
        $hours = max(1, min(168, (int) Settings::get('reminder_hours_before', '24')));

        $queued = null;
        if ($ready) {
            $queued = (int) Db::value(
                "SELECT COUNT(*)
                   FROM appointments a
                   JOIN clients c ON c.id = a.client_id
                  WHERE a.reminder_sent_at IS NULL
                    AND a.status IN ('scheduled','confirmed')
                    AND a.starts_at > NOW()
                    AND a.starts_at <= DATE_ADD(NOW(), INTERVAL ? HOUR)
                    AND c.email IS NOT NULL AND c.email <> ''",
                [$hours]
            );
        }

        return [
            'ready'      => $ready,
            'enabled'    => Settings::get('reminders_enabled', '1') === '1',
            'hours'      => $hours,
            'lastRun'    => Settings::get('reminder_last_run') ?: null,
            'lastResult' => Settings::get('reminder_last_result') ?: null,
            'queued'     => $queued,
        ];
    }

    /**
     * Check-in döngüsünün durumu.
     *
     * Buradaki tek önemli sayı doldurma oranı: pilotun kararı ona bakılarak
     * veriliyor (bkz. panel/check-in-plani.md, Faz 5). Oran düşükse sorun kodda
     * değil hatırlatma kanalındadır — o yüzden gönderilen/doldurulan ayrı ayrı
     * gösteriliyor, tek bir yüzdeye indirilmiyor.
     *
     * @return array{ready:bool,enabled:bool,lastRun:?string,lastResult:?string,enrolled:int,sent:int,completed:int,pending:int}
     */
    private function checkinStatus(): array
    {
        $ready = Schema::checkinsReady();

        $counts = $ready
            ? Db::one(
                'SELECT COUNT(DISTINCT client_id) AS enrolled,
                        COUNT(*)                  AS sent,
                        SUM(completed_at IS NOT NULL) AS completed,
                        SUM(completed_at IS NULL AND expires_at > NOW()) AS pending
                   FROM checkin_requests'
            )
            : null;

        return [
            'ready'      => $ready,
            'enabled'    => Settings::get('checkins_enabled', '1') === '1',
            'lastRun'    => Settings::get('checkin_last_run') ?: null,
            'lastResult' => Settings::get('checkin_last_result') ?: null,
            'enrolled'   => (int) ($counts['enrolled'] ?? 0),
            'sent'       => (int) ($counts['sent'] ?? 0),
            'completed'  => (int) ($counts['completed'] ?? 0),
            'pending'    => (int) ($counts['pending'] ?? 0),
        ];
    }

    /** @return list<array{label:string,ok:bool,detail:string}> */
    private function checks(): array
    {
        $missingExtensions = array_values(array_filter(
            ['pdo_mysql', 'mbstring', 'openssl', 'sodium'],
            static fn (string $name): bool => !extension_loaded($name)
        ));

        return [
            [
                'label'  => 'PHP sürümü',
                'ok'     => PHP_VERSION_ID >= 80100,
                'detail' => PHP_VERSION . (PHP_VERSION_ID >= 80100 ? '' : ' — en az 8.1 gerekiyor'),
            ],
            [
                'label'  => 'PHP eklentileri',
                'ok'     => $missingExtensions === [],
                'detail' => $missingExtensions === []
                    ? 'pdo_mysql, mbstring, openssl, sodium yüklü'
                    : 'eksik: ' . implode(', ', $missingExtensions),
            ],
            [
                'label'  => 'Seans notu şifrelemesi',
                'ok'     => Crypto::available(),
                'detail' => Crypto::available()
                    ? 'çalışıyor'
                    : 'kullanılamıyor — sodium eklentisi kapalı ya da note_key geçersiz',
            ],
            [
                'label'  => 'İçerik yönetimi (GitHub)',
                'ok'     => Github::configured(),
                'detail' => Github::configured()
                    ? 'yapılandırılmış'
                    : 'token girilmemiş — site içeriği ekranları kapalı',
            ],
            [
                'label'  => 'Veritabanı',
                'ok'     => true,
                'detail' => 'bağlantı çalışıyor (bu sayfa veritabanından okundu)',
            ],
        ];
    }
}
