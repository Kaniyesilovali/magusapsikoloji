<?php
declare(strict_types=1);

namespace Panel;

use DateTimeImmutable;

/**
 * Randevu zamanlamasının kuralları — çakışma, çalışma saati, izin.
 *
 * Ayrım bilinçli: **çakışma engeldir**, diğerleri yalnız uyarıdır.
 * İki randevu aynı anda gerçekten yapılamaz; ama merkez, mesai dışına veya
 * izin gününe bilerek randevu koymak isteyebilir (acil görüşme, telafi seansı).
 * Bu yüzden uyarılar formda gösterilir ve kullanıcı onaylarsa kayıt geçer.
 */
final class Scheduling
{
    public const STATUSES = [
        'scheduled' => 'Planlandı',
        'confirmed' => 'Onaylandı',
        'completed' => 'Tamamlandı',
        'cancelled' => 'İptal edildi',
        'no_show'   => 'Gelmedi',
    ];

    /** Takvimde yer kaplayan durumlar. İptal ve "gelmedi" slotu serbest bırakır. */
    public const BLOCKING_STATUSES = ['scheduled', 'confirmed', 'completed'];

    /** Yaklaşan randevu listelerinde gösterilen durumlar. */
    public const OPEN_STATUSES = ['scheduled', 'confirmed'];

    public const LOCATIONS = [
        'office' => 'Merkezde',
        'online' => 'Online',
    ];

    /** ISO-8601: 1 = Pazartesi … 7 = Pazar (working_hours.weekday ile aynı). */
    public const WEEKDAYS = [
        1 => 'Pazartesi',
        2 => 'Salı',
        3 => 'Çarşamba',
        4 => 'Perşembe',
        5 => 'Cuma',
        6 => 'Cumartesi',
        7 => 'Pazar',
    ];

    public static function statusLabel(?string $status): string
    {
        return self::STATUSES[$status] ?? '—';
    }

    public static function locationLabel(?string $location): string
    {
        return self::LOCATIONS[$location] ?? '—';
    }

    public static function weekdayLabel(int $weekday): string
    {
        return self::WEEKDAYS[$weekday] ?? '—';
    }

    /** Terapistin kendi varsayılanı varsa o, yoksa sistem ayarı, o da yoksa 50 dk. */
    public static function defaultDuration(?int $therapistId = null): int
    {
        if ($therapistId !== null) {
            $own = Db::value('SELECT default_session_minutes FROM therapist_profiles WHERE user_id = ?', [$therapistId]);
            if ($own !== null) {
                return (int) $own;
            }
        }
        return (int) Settings::get('default_session_minutes', '50');
    }

    public static function endOf(DateTimeImmutable $start, int $minutes): DateTimeImmutable
    {
        return $start->modify("+{$minutes} minutes");
    }

    /**
     * Aynı anda başka bir randevusu olan taraflar.
     *
     * Hem terapist hem görüşmeci için bakılır: bir görüşmeci aynı saatte iki farklı
     * terapiste yazılırsa bu da gerçek bir çakışmadır, yalnız terapist takvimine
     * bakmak bunu kaçırırdı.
     *
     * @return array<int,array> En fazla 5 çakışan kayıt.
     */
    public static function conflicts(
        int $therapistId,
        int $clientId,
        DateTimeImmutable $start,
        int $minutes,
        ?int $excludeId = null
    ): array {
        $end = self::endOf($start, $minutes);

        $sql = 'SELECT a.*, c.full_name AS client_name, t.full_name AS therapist_name
                  FROM appointments a
                  JOIN clients c ON c.id = a.client_id
                  JOIN users   t ON t.id = a.therapist_id
                 WHERE a.status IN (?, ?, ?)
                   AND (a.therapist_id = ? OR a.client_id = ?)
                   AND a.starts_at < ?
                   AND DATE_ADD(a.starts_at, INTERVAL a.duration_min MINUTE) > ?';
        $params = array_merge(self::BLOCKING_STATUSES, [
            $therapistId,
            $clientId,
            $end->format('Y-m-d H:i:s'),
            $start->format('Y-m-d H:i:s'),
        ]);

        if ($excludeId !== null) {
            $sql .= ' AND a.id <> ?';
            $params[] = $excludeId;
        }

        return Db::all($sql . ' ORDER BY a.starts_at LIMIT 5', $params);
    }

    /**
     * Engel olmayan ama sorulması gereken durumlar.
     * @return list<string> Kullanıcıya gösterilecek uyarı metinleri.
     */
    public static function warnings(int $therapistId, DateTimeImmutable $start, int $minutes): array
    {
        $end      = self::endOf($start, $minutes);
        $warnings = [];

        if ($start < new DateTimeImmutable()) {
            $warnings[] = 'Seçtiğiniz zaman geçmişte kalıyor.';
        }

        // Haftalık şablon hiç girilmemişse "mesai dışı" demek anlamsız olurdu.
        $hasTemplate = (int) Db::value('SELECT COUNT(*) FROM working_hours WHERE therapist_id = ?', [$therapistId]) > 0;
        if ($hasTemplate && !self::fitsWorkingHours($therapistId, $start, $end)) {
            $warnings[] = 'Randevu, terapistin çalışma saatleri dışında kalıyor.';
        }

        $timeOff = Db::one(
            'SELECT * FROM time_off WHERE therapist_id = ? AND starts_at < ? AND ends_at > ? LIMIT 1',
            [$therapistId, $end->format('Y-m-d H:i:s'), $start->format('Y-m-d H:i:s')]
        );
        if ($timeOff !== null) {
            $reason = $timeOff['reason'] !== null && $timeOff['reason'] !== ''
                ? ' (' . $timeOff['reason'] . ')'
                : '';
            $warnings[] = 'Terapist bu tarihte izinli' . $reason . '.';
        }

        return $warnings;
    }

    /** Randevu, o günün çalışma aralıklarından birinin içine tamamen sığıyor mu? */
    private static function fitsWorkingHours(int $therapistId, DateTimeImmutable $start, DateTimeImmutable $end): bool
    {
        // Gece yarısını aşan randevu hiçbir günlük aralığa sığmaz.
        if ($start->format('Y-m-d') !== $end->format('Y-m-d')) {
            return false;
        }

        return (int) Db::value(
            'SELECT COUNT(*) FROM working_hours
              WHERE therapist_id = ? AND weekday = ? AND start_time <= ? AND end_time >= ?',
            [$therapistId, (int) $start->format('N'), $start->format('H:i:s'), $end->format('H:i:s')]
        ) > 0;
    }

    /**
     * Form alanlarından ('2026-07-22' + '14:30') geçerli bir başlangıç zamanı üretir.
     * Geçersizse null döner — çağıran taraf doğrulama hatası yazar.
     */
    public static function parseStart(string $date, string $time): ?DateTimeImmutable
    {
        if ($date === '' || $time === '') {
            return null;
        }
        // 'H:i:s' de kabul edilsin: bazı tarayıcılar saniyeli değer gönderir.
        $time  = mb_substr($time, 0, 5);
        $value = DateTimeImmutable::createFromFormat('Y-m-d H:i', $date . ' ' . $time);
        if ($value === false) {
            return null;
        }

        // createFromFormat taşan değerleri (2026-02-31) sessizce çevirir;
        // geri yazıp karşılaştırmak bunu yakalar.
        $value = $value->setTime((int) $value->format('H'), (int) $value->format('i'));

        return $value->format('Y-m-d H:i') === $date . ' ' . $time ? $value : null;
    }
}
