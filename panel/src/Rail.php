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

        // 2) Pencere: randevuları tam saate yuvarlayarak sar. "Şimdi" bugünün
        //    penceresine girmiyorsa çizgi de görünmez, o yüzden onu da kapsa.
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

        if ($nowMin !== null) {
            $startHour = min($startHour, (int) floor($nowMin / 60));
            $endHour   = max($endHour, (int) ceil(($nowMin + 30) / 60));
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
        $laneEnds = [];   // şerit → o şeritteki son randevunun bitiş dakikası
        foreach ($items as $index => $item) {
            $lane = 0;
            while (isset($laneEnds[$lane]) && $laneEnds[$lane] > $item['startMin']) {
                $lane++;
            }
            $laneEnds[$lane]        = $item['startMin'] + $item['durMin'];
            $items[$index]['lane']  = $lane;
        }

        // Kaç şerit gerektiğini randevunun kendi kümesi belirler: sabah tek başına
        // duran bir seans, öğleden sonraki üçlü çakışma yüzünden daralmasın.
        $slots = [];
        foreach ($items as $item) {
            $concurrent = 0;
            foreach ($items as $other) {
                if ($other['startMin'] < $item['startMin'] + $item['durMin']
                    && $item['startMin'] < $other['startMin'] + $other['durMin']) {
                    $concurrent++;
                }
            }
            $lanes = min(self::MAX_LANES, max(1, $concurrent));

            $slots[] = [
                'row'   => $item['row'],
                'start' => $item['start'],
                'end'   => $item['end'],
                'at'    => self::clampUnits((int) round(($item['startMin'] - $windowStart) / self::UNIT), self::MAX_UNITS),
                'dur'   => max(1, self::clampUnits((int) round($item['durMin'] / self::UNIT), self::MAX_DUR)),
                'lane'  => min($lanes, $item['lane'] + 1),
                'lanes' => $lanes,
            ];
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
