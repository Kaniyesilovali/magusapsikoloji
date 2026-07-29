<?php
declare(strict_types=1);

namespace Panel;

/**
 * Ekolojik işaretlerle ruh hali arasındaki tekrarları arar.
 *
 * Burada istatistik YOK. 8–12 haftalık, tek kişilik, haftalık ölçümde korelasyon
 * katsayısı ya da p değeri gürültüyü bilgi diye sunar. Onun yerine önceden
 * yazılmış, sayılabilir, elle doğrulanabilir kurallar çalışıyor: her satırın
 * altındaki sayılar terapist tarafından tek tek sayılabilir.
 *
 * Eşikler tek savunma. Az haftada tek bir kötü gün üç haftalık bir ortalamayı
 * bir puandan fazla kaydırıyor ve kuralı tek başına geçirebiliyor; bu yüzden
 * hem işaretli hem işaretsiz tarafta en az MIN_WEEKS hafta aranıyor. Eşik
 * karşılanmıyorsa hiçbir şey döndürülmez — boş kutu, zayıf içgörüden iyidir.
 *
 * Dil kuralı da eşik kadar bağlayıcı: metinlerde "yüzünden", "sebebi", "yol
 * açıyor" geçmez. Bu kurallar nedensellik ölçmüyor, aynı haftalara denk gelmeyi
 * sayıyor. Her satır aynı cümleyle bitiyor: bu bir bulgu değil, bakılacak bir yer.
 */
final class Patterns
{
    /** Bir kuralın çalışması için her iki tarafta da gereken en az hafta sayısı. */
    private const MIN_WEEKS = 3;

    /** Gösterime değecek en küçük fark (1–10 ölçeğinde puan). */
    private const MIN_DIFF = 1.5;

    /** Terapistin seans öncesi 40 saniyesine sığan satır sayısı. */
    private const MAX_ROWS = 3;

    /** Her satırın altına düşen değişmez cümle. */
    public const DISCLAIMER = 'Bu bir bulgu değil, bakılacak bir yer.';

    /**
     * @param list<array<string,mixed>> $checkins eskiden yeniye sıralı
     * @param array{rows:list<array{key:string,label:string,cells:list<int>}>,events:array<int,?string>} $strip
     * @return array{rows:list<array{text:string,detail:string}>,anchors:list<array{label:string,before:list<int>,after:list<int>}>}
     */
    public static function find(array $checkins, array $strip): array
    {
        $mood = array_map(static fn (array $row): int => (int) $row['mood'], $checkins);

        $found = [];
        foreach ($strip['rows'] as $row) {
            foreach (
                [
                    [Ecosystem::HEADWIND, false, 'zorladığı'],
                    [Ecosystem::TAILWIND, false, 'desteklediği'],
                    [Ecosystem::HEADWIND, true,  'zorladığı'],
                ] as [$valence, $lagged, $verb]
            ) {
                $hit = self::compare($mood, $row['cells'], $valence, $lagged);
                if ($hit === null) {
                    continue;
                }

                $found[] = [
                    'weight' => abs($hit['diff']),
                    'text'   => self::sentence($row['label'], $verb, $lagged, $hit),
                    'detail' => sprintf(
                        '%d hafta işaretli, %d hafta değil. Ortalamalar: %s / %s.',
                        $hit['markedCount'],
                        $hit['restCount'],
                        number_format($hit['marked'], 1, ',', '.'),
                        number_format($hit['rest'], 1, ',', '.')
                    ),
                ];
            }
        }

        // En büyük fark önce: terapist üç satır okuyacaksa en belirgin olanları
        // okumalı. Eşiği geçen ama zayıf kalan satırlar listeden düşer.
        usort($found, static fn (array $a, array $b): int => $b['weight'] <=> $a['weight']);

        return [
            'rows'    => array_map(
                static fn (array $row): array => ['text' => $row['text'], 'detail' => $row['detail']],
                array_slice($found, 0, self::MAX_ROWS)
            ),
            'anchors' => self::anchors($checkins, $mood, $strip['events']),
        ];
    }

    // ── Kurallar ────────────────────────────────────────────────

    /**
     * İşaretli haftaların ruh hali ortalamasıyla kalanlarınkini karşılaştırır.
     *
     * `$lagged` ise işaretin ETKİSİ değil, **ertesi haftası** ölçülür: son
     * haftadaki bir işaretin ertesi haftası olmadığı için o hafta düşer ve
     * pencere kendiliğinden bir kısalır.
     *
     * @param list<int> $mood
     * @param list<int> $cells
     * @return array{diff:float,marked:float,rest:float,markedCount:int,restCount:int}|null
     */
    private static function compare(array $mood, array $cells, int $valence, bool $lagged): ?array
    {
        $marked = [];
        $seen   = [];

        foreach ($cells as $index => $cell) {
            if ($cell !== $valence) {
                continue;
            }
            $at = $lagged ? $index + 1 : $index;
            if (!isset($mood[$at])) {
                continue;
            }
            $marked[$at] = $mood[$at];
            $seen[$at]   = true;
        }

        $rest = [];
        foreach ($mood as $index => $value) {
            if (!isset($seen[$index])) {
                $rest[] = $value;
            }
        }

        if (count($marked) < self::MIN_WEEKS || count($rest) < self::MIN_WEEKS) {
            return null;
        }

        $markedMean = array_sum($marked) / count($marked);
        $restMean   = array_sum($rest) / count($rest);
        $diff       = $markedMean - $restMean;

        if (abs($diff) < self::MIN_DIFF) {
            return null;
        }

        // Karşı rüzgârın ruh halini YÜKSELTTİĞİ ya da sırt rüzgârının
        // düşürdüğü durum: sayı eşiği geçse de anlatılabilir bir şey değil,
        // gösterilmez. Ters yönlü bir tesadüfü cümleye çevirmek, kuralın
        // güvenilirliğini olduğundan yüksek gösterir.
        if (($valence === Ecosystem::HEADWIND && $diff > 0) || ($valence === Ecosystem::TAILWIND && $diff < 0)) {
            return null;
        }

        return [
            'diff'        => $diff,
            'marked'      => $markedMean,
            'rest'        => $restMean,
            'markedCount' => count($marked),
            'restCount'   => count($rest),
        ];
    }

    /**
     * ✦ işaretinin çevresi: öncesindeki ve sonrasındaki üçer haftanın ruh hali.
     *
     * Yorum yok, yalnız görüntü. Bu kural bir eşik uygulamıyor çünkü bir şey
     * iddia etmiyor — terapist bakar, kendi okur.
     *
     * @param list<int> $mood
     * @param array<int,?string> $events
     * @return list<array{label:string,before:list<int>,after:list<int>}>
     */
    private static function anchors(array $checkins, array $mood, array $events): array
    {
        $anchors = [];
        foreach ($checkins as $index => $row) {
            if (!array_key_exists((int) $row['id'], $events)) {
                continue;
            }

            $before = array_slice($mood, max(0, $index - 3), min(3, $index));
            $after  = array_slice($mood, $index + 1, 3);

            if ($before === [] && $after === []) {
                continue;
            }

            $anchors[] = [
                'label'  => (string) ($events[(int) $row['id']] ?? ''),
                'before' => array_values($before),
                'after'  => array_values($after),
            ];
        }

        return $anchors;
    }

    /**
     * Cümle şablonu.
     *
     * "denk geliyor" ve "birlikte gidiyor" dışında bir bağ kurulmuyor: kural
     * nedensellik ölçmediği için nedensellik dili kullanamaz.
     */
    private static function sentence(string $label, string $verb, bool $lagged, array $hit): string
    {
        $marked = number_format($hit['marked'], 1, ',', '.');
        $rest   = number_format($hit['rest'], 1, ',', '.');

        if ($lagged) {
            return sprintf(
                '%s’in %s haftaları izleyen haftalarda ruh hali ortalaması %s; diğer haftalarda %s.',
                $label,
                $verb,
                $marked,
                $rest
            );
        }

        return sprintf(
            '%s’in %s haftalarda ruh hali ortalaması %s; diğer haftalarda %s.',
            $label,
            $verb,
            $marked,
            $rest
        );
    }
}
