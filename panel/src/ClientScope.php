<?php
declare(strict_types=1);

namespace Panel;

/**
 * "Bu kullanıcı hangi birey kayıtlarını görebilir?" sorusunun tek cevabı.
 *
 * Hem birey listesinde hem randevu formunda aynı kural işlemeli; iki ayrı
 * yerde yazılsaydı biri gevşediğinde fark edilmezdi. Filtre `clients` tablosunun
 * `c` takma adıyla sorgulandığını varsayar.
 */
final class ClientScope
{
    /** @return array{0:string,1:array} WHERE parçası ve parametreleri */
    public static function filter(array $actor): array
    {
        if (Rbac::can($actor, 'client.view.all')) {
            return ['1 = 1', []];
        }

        if ($actor['role'] === Rbac::THERAPIST) {
            // Terapistin "kendi bireyi": birincil terapisti olduğu ya da fiilen
            // randevusunu gördüğü kişi. İkincisi olmadan, devredilen bir bireyin
            // geçmişi terapiste kapanırdı.
            return [
                '(c.primary_therapist_id = ?
                  OR EXISTS (SELECT 1 FROM appointments a2 WHERE a2.client_id = c.id AND a2.therapist_id = ?))',
                [$actor['id'], $actor['id']],
            ];
        }

        // Birey ve yetkisiz roller: yalnız kendi kaydı (varsa).
        return ['c.user_id = ?', [$actor['id']]];
    }
}
