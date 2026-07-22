<?php
declare(strict_types=1);

namespace Panel\Controllers;

use Panel\Audit;
use Panel\Auth;
use Panel\Db;
use Panel\View;

final class AuditController
{
    private const PER_PAGE = 50;

    public function index(): void
    {
        Auth::requirePermission('audit.view');

        $page   = max(1, (int) query('sayfa', '1'));
        $action = query('islem');

        $where  = '1 = 1';
        $params = [];
        if ($action !== '' && array_key_exists($action, Audit::LABELS)) {
            $where   = 'a.action = ?';
            $params[] = $action;
        }

        $total = (int) Db::value("SELECT COUNT(*) FROM audit_log a WHERE {$where}", $params);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page  = min($page, $pages);

        // LIMIT/OFFSET parametre olarak bağlanamadığı için değerler int'e zorlanır.
        $offset = ($page - 1) * self::PER_PAGE;
        $rows = Db::all(
            "SELECT a.*, u.full_name AS actor_name, u.role AS actor_role
               FROM audit_log a
          LEFT JOIN users u ON u.id = a.actor_user_id
              WHERE {$where}
           ORDER BY a.created_at DESC, a.id DESC
              LIMIT " . self::PER_PAGE . ' OFFSET ' . $offset,
            $params
        );

        View::render('audit/index', [
            'title'  => 'Sistem Kayıtları',
            'rows'   => $rows,
            'page'   => $page,
            'pages'  => $pages,
            'total'  => $total,
            'action' => $action,
        ]);
    }
}
