<?php
declare(strict_types=1);

namespace Panel\Controllers;

use DateTimeImmutable;
use Exception;
use Panel\Auth;
use Panel\Db;
use Panel\Rbac;
use Panel\Reports;
use Panel\View;

/**
 * Aylık rapor ekranı.
 *
 * Merkez yönetimine açık: üç sayı da bütün merkezi kapsıyor ve terapistin kendi
 * doluluğunu buradan okuması gerekmiyor — kendi takvimi zaten önünde. Terapiste
 * açmak, "kim ne kadar doldu" karşılaştırmasını klinik olmayan bir yere taşırdı.
 */
final class ReportController
{
    public function index(): void
    {
        $actor = Auth::requirePermission('payment.view.all');

        $month = $this->month(query('ay')) ?? (new DateTimeImmutable())->modify('first day of this month')->setTime(0, 0);
        $from  = $month;
        $to    = $month->modify('+1 month');

        $therapistId = (int) query('terapist') > 0 ? (int) query('terapist') : null;

        View::render('reports/index', [
            'title'       => 'Raporlar',
            'month'       => $month,
            'from'        => $from,
            'to'          => $to,
            'revenue'     => Reports::revenue($from, $to, $therapistId),
            'appointments'=> Reports::appointments($from, $to, $therapistId),
            'utilisation' => Reports::utilisation($from, $to, $therapistId),
            'therapists'  => Db::all('SELECT id, full_name FROM users WHERE role = ? AND status = \'active\' ORDER BY full_name', [Rbac::THERAPIST]),
            'therapistId' => $therapistId,
            'actor'       => $actor,
        ]);
    }

    /** 'YYYY-MM' → ayın ilk günü; geçersizse null. */
    private function month(string $value): ?DateTimeImmutable
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $value)) {
            return null;
        }
        try {
            return (new DateTimeImmutable($value . '-01'))->setTime(0, 0);
        } catch (Exception) {
            return null;
        }
    }
}
