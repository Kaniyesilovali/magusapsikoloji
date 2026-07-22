<?php
declare(strict_types=1);

namespace Panel;

/**
 * Para tutarlarının okunması ve yazılması.
 *
 * Tutarlar veritabanında DECIMAL, PHP tarafında **dize** olarak taşınır.
 * Float'a çevrilmez: 0.1 + 0.2 gibi toplamlar kuruş kaydırır ve hesap
 * tutmaz. Toplamları MySQL yapar (SUM), PHP yalnız biçimlendirir.
 */
final class Money
{
    public const SYMBOL = '₺';

    /** 1234.5 → "1.234,50 ₺" */
    public static function format(string|float|int|null $amount): string
    {
        if ($amount === null || $amount === '') {
            return '—';
        }
        return number_format((float) $amount, 2, ',', '.') . ' ' . self::SYMBOL;
    }

    /**
     * Kullanıcı girdisini DECIMAL'e uygun dizeye çevirir.
     * "1.234,50" · "1234,50" · "1234.50" · "1234" kabul edilir.
     *
     * @return string|null null = geçersiz girdi
     */
    public static function parse(string $input): ?string
    {
        $value = str_replace([self::SYMBOL, ' ', "\u{00A0}"], '', trim($input));
        if ($value === '') {
            return null;
        }

        $hasComma = str_contains($value, ',');
        $hasDot   = str_contains($value, '.');

        if ($hasComma && $hasDot) {
            // İkisi de varsa sondaki ondalık ayracıdır: "1.234,50" ya da "1,234.50"
            $decimal = strrpos($value, ',') > strrpos($value, '.') ? ',' : '.';
            $value   = str_replace($decimal === ',' ? '.' : ',', '', $value);
            $value   = str_replace($decimal, '.', $value);
        } elseif ($hasComma) {
            $value = str_replace(',', '.', $value);
        } elseif ($hasDot) {
            // Tek nokta belirsiz: "1.234" binlik de olabilir, ondalık da.
            // Tam binlik kalıbına uyuyorsa binlik sayılır, yoksa ondalık.
            if (preg_match('/^\d{1,3}(\.\d{3})+$/', $value) === 1) {
                $value = str_replace('.', '', $value);
            }
        }

        if (preg_match('/^\d+(\.\d{1,2})?$/', $value) !== 1) {
            return null;
        }
        // 10 haneli DECIMAL(10,2): en fazla 8 tam basamak.
        if (strlen(explode('.', $value)[0]) > 8) {
            return null;
        }

        return $value;
    }

    /** Kuruş farklarını yutan karşılaştırma — "tam ödendi mi" kararı için. */
    public static function gte(string|float|null $a, string|float|null $b): bool
    {
        return round((float) $a, 2) >= round((float) $b, 2) - 0.001;
    }

    public static function isPositive(string|float|null $amount): bool
    {
        return round((float) $amount, 2) > 0;
    }
}
