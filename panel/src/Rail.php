<?php
declare(strict_types=1);

namespace Panel;

use DateTimeImmutable;

/**
 * Gün cetvelinin geometrisi — randevu listesini "ölçekli çizilmiş gün"e çevirir.
 *
 * Panelin tek görsel iddiası bu: zaman yer kaplar. Bir seans süresi kadar yüksek
 * görünür, iki seans arasındaki boşluk gerçekten boştur. Liste bunu söyleyemez;
 * "14:00'te doksan dakika boşum" bilgisi ancak boşluk çizilirse okunur.
 *
 * Ölçü birimi 5 dakikadır. Konumlar CSS sınıfı olarak veriliyor (.at-N/.dur-N)
 * çünkü panelin CSP'si inline style'a izin vermiyor — bkz. panel/.htaccess.
 */
final class Rail
{
    /** Bir birim = 5 dakika. */
    private const UNIT = 5;

    /** panel.css yalnız bu aralıklar için sınıf üretir. */
    private const MAX_UNITS  = 168;  // 14 saat
    private const MAX_DUR    = 36;   // 180 dakika
    private const MAX_LANES  = 4;

    /** Hiç randevu yoksa gösterilen varsayılan pencere. */
    private const DEFAULT_START = 9;
    private const DEFAULT_END   = 18;

    /**
     * @param  array<int, array<string, mixed>> $appointments  starts_at + duration_min taşıyan satırlar
     * @return array{startHour:int, hours:int, nowAt:?int, slots:array<int, array<string, mixed>>}
     */
    public static function build(array $appointments, DateTimeImmutable $day, ?DateTimeImmutable $now = null): array
    {
        $dayKey = $day->format('Y-m-d');

        // 1) Günün randevularını dakikaya çevir.
        $items = [];
        foreach ($appointments as $row) {
            $start = new DateTimeImmutable((string) $row['starts_at']);
            if ($start->format('Y-m-d') !== $dayKey) {
                continue;
            }
            $duration = max(self::UNIT, (int) ($row['duration_min'] ?: 50));
            $items[] = [
                'row'       => $row,
                'startMin'  => (int) $start->format('G') * 60 + (int) $start->format('i'),
                'durMin'    => $duration,
                'start'     => $start,
                'end'       => $start->modify("+{$duration} minutes"),
            ];
        }

        usort($items, static fn (array $a, array $b): int => $a['startMin'] <=> $b['startMin']);

        // 2) Pencere: randevuları tam saate yuvarlayarak sar.
        $nowMin = ($now !== null && $now->format('Y-m-d') === $dayKey)
            ? (int) $now->format('G') * 60 + (int) $now->format('i')
            : null;

        if ($items === []) {
            $startHour = self::DEFAULT_START;
            $endHour   = self::DEFAULT_END;
        } else {
            $startHour = (int) floor(min(array_column($items, 'startMin')) / 60);
            $endHour   = (int) ceil(max(array_map(
                static fn (array $i): int => $i['startMin'] + $i['durMin'],
                $items
            )) / 60);
        }

        // "Şimdi" pencereye bitişikse pencere onu kapsayacak kadar genişler.
        // Uzaktaysa (gün bittikten saatler sonra) çizgi hiç çizilmez — yoksa
        // gece 23:00'te sırf çizgiyi göstermek için 14 saatlik cetvel çizilirdi.
        if ($nowMin !== null) {
            $nowHour = (int) floor($nowMin / 60);
            if ($nowHour >= $startHour - 1 && $nowHour <= $endHour) {
                $startHour = min($startHour, $nowHour);
                $endHour   = max($endHour, $nowHour + 1);
            } else {
                $nowMin = null;
            }
        }

        // En az 4 saat göster: tek randevulu bir gün ezilmiş bir şerit olmasın.
        if ($endHour - $startHour < 4) {
            $endHour = $startHour + 4;
        }
        $startHour = max(0, $startHour);
        $endHour   = min(24, max($endHour, $startHour + 1));
        if ($endHour - $startHour > self::MAX_UNITS / 12) {
            $endHour = $startHour + (int) (self::MAX_UNITS / 12);
        }

        $windowStart = $startHour * 60;

        // 3) Şerit dağıtımı. Üst üste binen randevular yan yana durur — ön büro
        //    tüm terapistleri tek cetvelde görüyor, çakışma orada normaldir.
        //
        //    Şerit sayısı randevu başına değil **küme** başına hesaplanır. Zincir
        //    çakışmada (A-B çakışır, B-C çakışır, A-C çakışmaz) randevu başına
        //    saymak A'ya 1/2, B'ye 2/3 verirdi ve iki kutu birbirinin üstüne
        //    binerdi. Küme = birbirine değen randevuların tamamı; hepsi aynı
        //    bölmeyi paylaşır. Sabah tek başına duran seans da öğleden sonraki
        //    üçlü çakışma yüzünden daralmaz, çünkü o ayrı bir kümedir.
        $clusters    = [];
        $current     = [];
        $clusterEnd  = null;

        foreach ($items as $item) {
            if ($clusterEnd !== null && $item['startMin'] >= $clusterEnd) {
                $clusters[] = $current;
                $current    = [];
                $clusterEnd = null;
            }
            $current[]  = $item;
            $clusterEnd = max($clusterEnd ?? 0, $item['startMin'] + $item['durMin']);
        }
        if ($current !== []) {
            $clusters[] = $current;
        }

        $slots = [];
        foreach ($clusters as $cluster) {
            $laneEnds = [];   // şerit → o şeritteki son randevunun bitiş dakikası
            $assigned = [];
            foreach ($cluster as $item) {
                $lane = 0;
                while (isset($laneEnds[$lane]) && $laneEnds[$lane] > $item['startMin']) {
                    $lane++;
                }
                $laneEnds[$lane] = $item['startMin'] + $item['durMin'];
                $assigned[]      = [$item, $lane];
            }
            $lanes = min(self::MAX_LANES, max(1, count($laneEnds)));

            foreach ($assigned as [$item, $lane]) {
                $slots[] = [
                    'row'   => $item['row'],
                    'start' => $item['start'],
                    'end'   => $item['end'],
                    'at'    => self::clampUnits((int) round(($item['startMin'] - $windowStart) / self::UNIT), self::MAX_UNITS),
                    'dur'   => max(1, self::clampUnits((int) round($item['durMin'] / self::UNIT), self::MAX_DUR)),
                    'lane'  => min($lanes, $lane + 1),
                    'lanes' => $lanes,
                ];
            }
        }

        return [
            'startHour' => $startHour,
            'hours'     => $endHour - $startHour,
            'nowAt'     => $nowMin === null
                ? null
                : self::clampUnits((int) round(($nowMin - $windowStart) / self::UNIT), ($endHour - $startHour) * 12),
            'slots'     => $slots,
        ];
    }

    private static function clampUnits(int $value, int $max): int
    {
        return max(0, min($max, $value));
    }
}
