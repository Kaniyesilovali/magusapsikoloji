<?php
declare(strict_types=1);

namespace Panel\Controllers;

use Panel\Audit;
use Panel\Auth;
use Panel\Crypto;
use Panel\Db;
use Panel\Github;
use Panel\Migrator;
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
            'title'   => 'Sistem',
            'pending' => Migrator::pending(),
            'applied' => Db::all('SELECT * FROM schema_migrations ORDER BY filename'),
            'checks'  => $this->checks(),
            'actor'   => $actor,
        ]);
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
