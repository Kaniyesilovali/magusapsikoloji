<?php
declare(strict_types=1);

namespace Panel\Controllers;

use DateTimeImmutable;
use Panel\Auth;
use Panel\Db;
use Panel\Rail;
use Panel\Rbac;
use Panel\View;

final class DashboardController
{
    public function index(): void
    {
        $user = Auth::requirePermission('dashboard.view');

        // Kapsam tek yerde kurulur, iki sorgu da aynı koşulu kullanır.
        // Danışan bağlantısı users.id ↔ clients.user_id üzerinden gider;
        // başka bir danışanın kaydına erişim yolu yoktur.
        if (Rbac::can($user, 'appointment.view.all')) {
            $scope  = '1';
            $params = [];
        } elseif (Rbac::can($user, 'appointment.view.own') && $user['role'] === Rbac::THERAPIST) {
            $scope  = 'a.therapist_id = ?';
            $params = [$user['id']];
        } else {
            $scope  = 'c.user_id = ?';
            $params = [$user['id']];
        }

        $select = 'SELECT a.*, c.full_name AS client_name, t.full_name AS therapist_name
                     FROM appointments a
                     JOIN clients c ON c.id = a.client_id
                     JOIN users   t ON t.id = a.therapist_id';

        // Cetvel bugünü çizer. İptal edilenler dışarıda: iptal saati gerçekten
        // serbest bırakır, o yüzden cetvelde de yer kaplamamalı. "Gelmedi" ise
        // kalır — seans o saate planlanmıştı ve günün şeklinin parçasıdır.
        $today = Db::all(
            "$select WHERE $scope AND DATE(a.starts_at) = CURDATE() AND a.status <> 'cancelled'
              ORDER BY a.starts_at",
            $params
        );

        $upcoming = Db::all(
            "$select WHERE $scope AND a.starts_at >= (CURDATE() + INTERVAL 1 DAY)
                       AND a.status IN ('scheduled','confirmed')
              ORDER BY a.starts_at LIMIT 6",
            $params
        );

        // Sayımlar ekranın başında değil sonunda: gün içinde bir kararı
        // değiştiren şey toplam danışan sayısı değil, günün şekli.
        $stats = [];
        if (Rbac::can($user, 'appointment.view.all')) {
            $stats = [
                ['label' => 'Aktif danışan',  'value' => (int) Db::value('SELECT COUNT(*) FROM clients WHERE status = \'active\'')],
                ['label' => 'Terapist',       'value' => (int) Db::value('SELECT COUNT(*) FROM users WHERE role = ? AND status = \'active\'', [Rbac::THERAPIST])],
                ['label' => 'Panel hesabı',   'value' => (int) Db::value('SELECT COUNT(*) FROM users WHERE status = \'active\'')],
                ['label' => 'Bekleyen davet', 'value' => (int) Db::value('SELECT COUNT(*) FROM users WHERE status = \'invited\'')],
            ];
        }

        $now = new DateTimeImmutable('now');

        View::render('dashboard/index', [
            'title'    => 'Bugün',
            'user'     => $user,
            'stats'    => $stats,
            'today'    => $today,
            'upcoming' => $upcoming,
            'now'      => $now,
            'rail'     => Rail::build($today, $now->setTime(0, 0), $now),
        ]);
    }
}
