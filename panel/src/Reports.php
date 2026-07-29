<?php
declare(strict_types=1);

namespace Panel;

use DateTimeImmutable;

/**
 * Aylık raporlar: gelir, gelmeme oranı, terapist doluluğu.
 *
 * Üç sayı da veritabanında zaten duruyordu; eksik olan okuma tarafıydı. Merkez
 * "bu ay ne oldu" sorusunu bugüne kadar Ödemeler listesini gözle tarayarak
 * cevaplıyordu.
 *
 * Sayılar burada üretiliyor, görünümde değil: aynı oranın iki ekranda iki
 * farklı paydayla hesaplanması, raporun kendisine güveni bitirir.
 */
final class Reports
{
    /**
     * Gelir tablosu. Tutarlar kuruş cinsinden toplanıp sonda ikiye bölünüyor;
     * float toplamı birkaç yüz seansta kuruş kaydırıyor.
     *
     * @return array{fee:string,paid:string,outstanding:string,sessions:int}
     */
    public static function revenue(DateTimeImmutable $from, DateTimeImmutable $to, ?int $therapistId = null): array
    {
        [$where, $params] = self::scope($from, $to, $therapistId);

        $rows = Db::all(
            "SELECT a.status, a.fee,
                    (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.appointment_id = a.id) AS paid
               FROM appointments a
              WHERE {$where}",
            $params
        );

        $fee = $paid = $outstanding = 0;
        foreach ($rows as $row) {
            $rowFee  = (int) round(((float) ($row['fee'] ?? 0)) * 100);
            $rowPaid = (int) round(((float) $row['paid']) * 100);

            $fee  += $rowFee;
            $paid += $rowPaid;

            // İptal edilen seans borç sayılmaz; tahsil edilmişse tahsilatta kalır.
            // Ödemeler ekranındaki kuralın aynısı.
            if ($row['status'] !== 'cancelled' && $rowFee > $rowPaid) {
                $outstanding += $rowFee - $rowPaid;
            }
        }

        return [
            'fee'         => number_format($fee / 100, 2, '.', ''),
            'paid'        => number_format($paid / 100, 2, '.', ''),
            'outstanding' => number_format($outstanding / 100, 2, '.', ''),
            'sessions'    => count($rows),
        ];
    }

    /**
     * Durum dağılımı ve gelmeme oranı.
     *
     * Payda bilinçli olarak **tamamlanan + gelmeyen**: iptal edilen seans
     * gelmeme değildir, önceden haber verilmiş bir değişikliktir. İptalleri
     * paydaya katmak oranı sulandırır, payı da bozar. Henüz geçmemiş
     * randevular da dışarıda — sonucu belli olmayan bir seans orana giremez.
     *
     * @return array{counts:array<string,int>,total:int,noShowRate:?float,settled:int}
     */
    public static function appointments(DateTimeImmutable $from, DateTimeImmutable $to, ?int $therapistId = null): array
    {
        [$where, $params] = self::scope($from, $to, $therapistId);

        $counts = [];
        foreach (Db::all("SELECT a.status, COUNT(*) AS n FROM appointments a WHERE {$where} GROUP BY a.status", $params) as $row) {
            $counts[(string) $row['status']] = (int) $row['n'];
        }

        $completed = $counts['completed'] ?? 0;
        $noShow    = $counts['no_show']   ?? 0;
        $settled   = $completed + $noShow;

        return [
            'counts'     => $counts,
            'total'      => array_sum($counts),
            'settled'    => $settled,
            'noShowRate' => $settled > 0 ? round($noShow / $settled * 100, 1) : null,
        ];
    }

    /**
     * Terapist doluluğu: dolu dakika / açık dakika.
     *
     * Kapasite haftalık şablondan (working_hours) tarih tarih türetiliyor, izin
     * aralıkları düşülüyor. Şablon boş bırakılmış bir terapistte kapasite
     * hesaplanamaz — "her saat uygun" demek sonsuz kapasite demek olurdu ve
     * oran anlamsız çıkardı. O satır oran yerine "şablon yok" der.
     *
     * @return list<array{id:int,name:string,booked:int,capacity:?int,rate:?float,sessions:int}>
     */
    public static function utilisation(DateTimeImmutable $from, DateTimeImmutable $to, ?int $therapistId = null): array
    {
        $therapists = Db::all(
            'SELECT id, full_name FROM users WHERE role = ? AND status = \'active\''
            . ($therapistId !== null ? ' AND id = ?' : '')
            . ' ORDER BY full_name',
            $therapistId !== null ? [Rbac::THERAPIST, $therapistId] : [Rbac::THERAPIST]
        );

        $out = [];
        foreach ($therapists as $therapist) {
            $id = (int) $therapist['id'];

            $booked = Db::one(
                "SELECT COALESCE(SUM(duration_min), 0) AS dakika, COUNT(*) AS n
                   FROM appointments
                  WHERE therapist_id = ? AND status <> 'cancelled'
                    AND starts_at >= ? AND starts_at < ?",
                [$id, $from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')]
            );

            $capacity = self::capacity($id, $from, $to);
            $minutes  = (int) ($booked['dakika'] ?? 0);

            $out[] = [
                'id'       => $id,
                'name'     => (string) $therapist['full_name'],
                'booked'   => $minutes,
                'capacity' => $capacity,
                'rate'     => ($capacity !== null && $capacity > 0) ? round($minutes / $capacity * 100, 1) : null,
                'sessions' => (int) ($booked['n'] ?? 0),
            ];
        }

        return $out;
    }

    // ── İç işler ────────────────────────────────────────────────

    /**
     * Haftalık şablonun aralıktaki karşılığı, izinler düşülmüş hâlde.
     * Şablon hiç girilmemişse null.
     */
    private static function capacity(int $therapistId, DateTimeImmutable $from, DateTimeImmutable $to): ?int
    {
        $hours = Db::all('SELECT weekday, start_time, end_time FROM working_hours WHERE therapist_id = ?', [$therapistId]);
        if ($hours === []) {
            return null;
        }

        $byWeekday = [];
        foreach ($hours as $row) {
            $byWeekday[(int) $row['weekday']][] = [
                self::minutes((string) $row['start_time']),
                self::minutes((string) $row['end_time']),
            ];
        }

        $timeOff = Db::all(
            'SELECT starts_at, ends_at FROM time_off
              WHERE therapist_id = ? AND starts_at < ? AND ends_at > ?',
            [$therapistId, $to->format('Y-m-d H:i:s'), $from->format('Y-m-d H:i:s')]
        );

        $total = 0;
        for ($day = $from; $day < $to; $day = $day->modify('+1 day')) {
            foreach ($byWeekday[(int) $day->format('N')] ?? [] as [$start, $end]) {
                $total += max(0, $end - $start - self::offMinutes($timeOff, $day, $start, $end));
            }
        }

        return $total;
    }

    /** Bir günün belirli dilimine düşen izin dakikası. */
    private static function offMinutes(array $timeOff, DateTimeImmutable $day, int $start, int $end): int
    {
        $dayStart = $day->setTime(0, 0);
        $off      = 0;

        foreach ($timeOff as $row) {
            $offStart = new DateTimeImmutable((string) $row['starts_at']);
            $offEnd   = new DateTimeImmutable((string) $row['ends_at']);

            $from = max($start, (int) (($offStart->getTimestamp() - $dayStart->getTimestamp()) / 60));
            $to   = min($end,   (int) (($offEnd->getTimestamp()   - $dayStart->getTimestamp()) / 60));

            if ($to > $from) {
                $off += $to - $from;
            }
        }

        return $off;
    }

    private static function minutes(string $time): int
    {
        return (int) substr($time, 0, 2) * 60 + (int) substr($time, 3, 2);
    }

    /** @return array{0:string,1:array} */
    private static function scope(DateTimeImmutable $from, DateTimeImmutable $to, ?int $therapistId): array
    {
        $where  = 'a.starts_at >= ? AND a.starts_at < ?';
        $params = [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')];

        if ($therapistId !== null) {
            $where   .= ' AND a.therapist_id = ?';
            $params[] = $therapistId;
        }

        return [$where, $params];
    }
}
