<?php
declare(strict_types=1);

namespace Panel\Controllers;

use DateTimeImmutable;
use Exception;
use Panel\Audit;
use Panel\Auth;
use Panel\Db;
use Panel\Rbac;
use Panel\Reports;
use Panel\Scheduling;
use Panel\View;

/**
 * Rapor ekranı.
 *
 * İki görünüm, tek sayfa:
 *  - report.view.all (yönetim): bütün merkez, terapist seçilebilir; gelir dahil.
 *  - report.view.own (terapist): yalnız kendi seansları. Terapist kutusu yok,
 *    sorgudaki `terapist` parametresi yok sayılır; gelir bölümü gelmez.
 *    Böylece terapist kendi takviminin seyrini ve yoğunluğunu okur, ama
 *    "kim ne kadar doldu" karşılaştırması klinik olmayan bir yere taşınmaz.
 */
final class ReportController
{
    /** Dönem seçenekleri: seçilen ay dahil geriye kaç ay. */
    public const PERIODS = [1 => 'Yalnız bu ay', 3 => 'Son 3 ay', 6 => 'Son 6 ay', 12 => 'Son 12 ay'];

    /** Aylık seyir hep bu kadar ay gösterir; dönem seçiminden bağımsız. */
    private const TREND_MONTHS = 12;

    public function index(): void
    {
        $actor = Auth::requireLogin();
        $all   = Rbac::can($actor, 'report.view.all');

        if (!$all && !Rbac::can($actor, 'report.view.own')) {
            Audit::log('access.denied', 'permission', null, ['permission' => 'report.view']);
            View::error(403, 'Yetkiniz yok', 'Bu sayfayı görüntüleme yetkiniz bulunmuyor.');
            exit;
        }

        $month  = $this->month(query('ay')) ?? (new DateTimeImmutable())->modify('first day of this month')->setTime(0, 0);
        $period = array_key_exists((int) query('donem'), self::PERIODS) ? (int) query('donem') : 1;
        $from   = $month->modify(sprintf('-%d months', $period - 1));
        $to     = $month->modify('+1 month');

        $therapistId = $all
            ? ((int) query('terapist') > 0 ? (int) query('terapist') : null)
            : (int) $actor['id'];

        $type = array_key_exists(query('tur'), Scheduling::SESSION_TYPES) ? query('tur') : null;

        View::render('reports/index', [
            'title'        => 'Raporlar',
            'month'        => $month,
            'period'       => $period,
            'from'         => $from,
            'to'           => $to,
            'scopeAll'     => $all,
            'revenue'      => $all ? Reports::revenue($from, $to, $therapistId) : null,
            'appointments' => Reports::appointments($from, $to, $therapistId),
            'utilisation'  => Reports::utilisation($from, $to, $therapistId),
            'density'      => Reports::density($from, $to, $therapistId, $type),
            'trend'        => Reports::trend($month, self::TREND_MONTHS, $therapistId, $type),
            'type'         => $type,
            'therapists'   => $all
                ? Db::all('SELECT id, full_name FROM users WHERE role = ? AND status = \'active\' ORDER BY full_name', [Rbac::THERAPIST])
                : [],
            'therapistId'  => $therapistId,
            'actor'        => $actor,
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
