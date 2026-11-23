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

    // ── Yoğunluk ────────────────────────────────────────────────

    /**
     * Randevuların gün, saat ve seans türüne göre dağılımı.
     *
     * Sayılan şey **ayrılmış saat**: iptal edilenler dışarıda, gelmeyenler
     * içeride. Gelmeyen bireyin saati yine de o gün o saatte tutulmuştu;
     * yoğunluk sorusu "kim geldi" değil "takvim ne zaman doluyor".
     *
     * Saat, seansın başladığı saattir (09:30 → 09). Sütun aralığı veriden
     * kurulur ama en az 09–18 gösterilir; tek seanslık bir ayda tablo tek
     * sütuna çökmesin.
     *
     * @return array{total:int,grid:array<int,array<int,int>>,byWeekday:array<int,int>,
     *               byHour:array<int,int>,hours:list<int>,byType:array<string,int>,
     *               typeWeekday:array<string,array<int,int>>,typeHour:array<string,array<int,int>>,
     *               typesReady:bool}
     */
    public static function density(DateTimeImmutable $from, DateTimeImmutable $to, ?int $therapistId = null, ?string $type = null): array
    {
        [$where, $params] = self::scope($from, $to, $therapistId);
        $typesReady = Schema::sessionTypesReady();

        if ($type !== null && $typesReady) {
            $where   .= ' AND a.session_type = ?';
            $params[] = $type;
        }

        $rows = Db::all(
            'SELECT a.starts_at, ' . ($typesReady ? 'a.session_type' : 'NULL AS session_type')
            . " FROM appointments a WHERE {$where} AND a.status <> 'cancelled'",
            $params
        );

        $grid = $byWeekday = $byHour = $byType = $typeWeekday = $typeHour = [];
        foreach ($rows as $row) {
            $at   = new DateTimeImmutable((string) $row['starts_at']);
            $wd   = (int) $at->format('N');
            $hour = (int) $at->format('G');
            $kind = (string) ($row['session_type'] ?? '') ?: 'none';

            $grid[$wd][$hour]         = ($grid[$wd][$hour] ?? 0) + 1;
            $byWeekday[$wd]           = ($byWeekday[$wd] ?? 0) + 1;
            $byHour[$hour]            = ($byHour[$hour] ?? 0) + 1;
            $byType[$kind]            = ($byType[$kind] ?? 0) + 1;
            $typeWeekday[$kind][$wd]  = ($typeWeekday[$kind][$wd] ?? 0) + 1;
            $typeHour[$kind][$hour]   = ($typeHour[$kind][$hour] ?? 0) + 1;
        }

        $first = $byHour === [] ? 9  : min(9,  min(array_keys($byHour)));
        $last  = $byHour === [] ? 18 : max(18, max(array_keys($byHour)));

        // Türler sabit sırada (Scheduling::SESSION_TYPES), türü girilmemiş eski
        // kayıtlar en sonda — sayıya göre sıralamak her ay sırayı değiştirirdi.
        $orderedTypes = [];
        foreach ([...array_keys(Scheduling::SESSION_TYPES), 'none'] as $key) {
            if (isset($byType[$key])) {
                $orderedTypes[$key] = $byType[$key];
            }
        }

        return [
            'total'       => count($rows),
            'grid'        => $grid,
            'byWeekday'   => $byWeekday,
            'byHour'      => $byHour,
            'hours'       => range($first, $last),
            'byType'      => $orderedTypes,
            'typeWeekday' => $typeWeekday,
            'typeHour'    => $typeHour,
            'typesReady'  => $typesReady,
        ];
    }

    /**
     * Aylık seyir: seçilen aydan geriye $months ay, her ay için durum dağılımı.
     *
     * "Seans" sütunu iptal dışındaki her şey (yoğunlukla aynı tanım); iptaller
     * ayrıca gösteriliyor ki seyirdeki bir düşüşün iptalden mi, az randevudan
     * mı geldiği okunabilsin.
     *
     * @return list<array{month:DateTimeImmutable,booked:int,completed:int,noShow:int,cancelled:int,types:array<string,int>}>
     */
    public static function trend(DateTimeImmutable $lastMonth, int $months, ?int $therapistId = null, ?string $type = null): array
    {
        $from = $lastMonth->modify(sprintf('-%d months', $months - 1));
        $to   = $lastMonth->modify('+1 month');

        [$where, $params] = self::scope($from, $to, $therapistId);
        $typesReady = Schema::sessionTypesReady();

        if ($type !== null && $typesReady) {
            $where   .= ' AND a.session_type = ?';
            $params[] = $type;
        }

        $out = [];
        for ($month = $from; $month < $to; $month = $month->modify('+1 month')) {
            $out[$month->format('Y-m')] = [
                'month' => $month, 'booked' => 0, 'completed' => 0, 'noShow' => 0, 'cancelled' => 0, 'types' => [],
            ];
        }

        $rows = Db::all(
            'SELECT a.starts_at, a.status, ' . ($typesReady ? 'a.session_type' : 'NULL AS session_type')
            . " FROM appointments a WHERE {$where}",
            $params
        );

        foreach ($rows as $row) {
            $key = substr((string) $row['starts_at'], 0, 7);
            if (!isset($out[$key])) {
                continue;
            }
            $status = (string) $row['status'];
            if ($status === 'cancelled') {
                $out[$key]['cancelled']++;
                continue;
            }
            $out[$key]['booked']++;
            if ($status === 'completed') {
                $out[$key]['completed']++;
            } elseif ($status === 'no_show') {
                $out[$key]['noShow']++;
            }
            $kind = (string) ($row['session_type'] ?? '') ?: 'none';
            $out[$key]['types'][$kind] = ($out[$key]['types'][$kind] ?? 0) + 1;
        }

        return array_values($out);
    }

    /**
     * Isı hücresinin tonu: 0 boş, 1–5 en yoğuna göre beşte bir dilimler.
     * Göreli ölçek bilinçli: bir terapistin "en yoğun saati" kendi takvimine
     * göre okunur, merkezin en yoğun saatine göre değil.
     */
    public static function level(int $value, int $max, int $steps = 5): int
    {
        if ($value <= 0 || $max <= 0) {
            return 0;
        }
        return max(1, (int) ceil($value / $max * $steps));
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
