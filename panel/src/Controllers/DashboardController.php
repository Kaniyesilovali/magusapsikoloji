<?php
declare(strict_types=1);

namespace Panel\Controllers;

use Panel\Auth;
use Panel\Db;
use Panel\Rbac;
use Panel\View;

final class DashboardController
{
    public function index(): void
    {
        $user = Auth::requirePermission('dashboard.view');

        $stats       = [];
        $appointments = [];

        if (Rbac::can($user, 'appointment.view.all')) {
            $stats = [
                ['label' => 'Aktif kullanıcı',   'value' => (int) Db::value('SELECT COUNT(*) FROM users WHERE status = \'active\'')],
                ['label' => 'Terapist',          'value' => (int) Db::value('SELECT COUNT(*) FROM users WHERE role = ? AND status = \'active\'', [Rbac::THERAPIST])],
                ['label' => 'Danışan',           'value' => (int) Db::value('SELECT COUNT(*) FROM clients WHERE status = \'active\'')],
                ['label' => 'Bekleyen davet',    'value' => (int) Db::value('SELECT COUNT(*) FROM users WHERE status = \'invited\'')],
            ];
            $appointments = Db::all(
                'SELECT a.*, c.full_name AS client_name, t.full_name AS therapist_name
                   FROM appointments a
                   JOIN clients c ON c.id = a.client_id
                   JOIN users   t ON t.id = a.therapist_id
                  WHERE a.starts_at >= NOW() AND a.status IN (\'scheduled\',\'confirmed\')
               ORDER BY a.starts_at
                  LIMIT 8'
            );
        } elseif (Rbac::can($user, 'appointment.view.own') && $user['role'] === Rbac::THERAPIST) {
            $appointments = Db::all(
                'SELECT a.*, c.full_name AS client_name, t.full_name AS therapist_name
                   FROM appointments a
                   JOIN clients c ON c.id = a.client_id
                   JOIN users   t ON t.id = a.therapist_id
                  WHERE a.therapist_id = ? AND a.starts_at >= NOW() AND a.status IN (\'scheduled\',\'confirmed\')
               ORDER BY a.starts_at
                  LIMIT 8',
                [$user['id']]
            );
        } else {
            // Danışan: yalnızca kendi randevuları. Bağlantı users.id ↔ clients.user_id
            // üzerinden kurulur; başka bir danışanın kaydına erişim yolu yoktur.
            $appointments = Db::all(
                'SELECT a.*, c.full_name AS client_name, t.full_name AS therapist_name
                   FROM appointments a
                   JOIN clients c ON c.id = a.client_id
                   JOIN users   t ON t.id = a.therapist_id
                  WHERE c.user_id = ? AND a.starts_at >= NOW() AND a.status IN (\'scheduled\',\'confirmed\')
               ORDER BY a.starts_at
                  LIMIT 8',
                [$user['id']]
            );
        }

        View::render('dashboard/index', [
            'title'        => 'Panel',
            'user'         => $user,
            'stats'        => $stats,
            'appointments' => $appointments,
        ]);
    }
}
