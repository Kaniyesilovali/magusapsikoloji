<?php
declare(strict_types=1);

namespace Panel\Controllers;

use DateTimeImmutable;
use Exception;
use Panel\Audit;
use Panel\Auth;
use Panel\Db;
use Panel\Rail;
use Panel\Rbac;
use Panel\Scheduling;
use Panel\View;

/**
 * Terapist müsaitliği: haftalık çalışma şablonu + izin aralıkları.
 *
 * Randevu kaydını engellemez, yalnız [Scheduling::warnings] üzerinden uyarı
 * üretir. Şablon hiç girilmemişse uyarı da çıkmaz — boş şablon "her saat uygun"
 * demektir, "hiçbir saat uygun değil" değil.
 *
 * Ekranın üstündeki takvim şablonun **gerçek tarihlere düşmüş hâlidir**: kural
 * haftalıktır ama izin belirli günlere yazılır, ikisi ancak takvimde bir arada
 * okunur. Şablonun kendisi aşağıdaki gün satırlarından düzenlenir — bir haftalık
 * kuralı tek bir günün kutusundan silmek yanıltıcı olurdu.
 */
final class AvailabilityController
{
    /** ?gorunum= değerleri. */
    private const MODES = ['hafta', 'ay'];

    public function index(): void
    {
        $actor     = $this->requireAccess();
        $therapist = $this->target($actor);

        $mode   = in_array(query('gorunum'), self::MODES, true) ? query('gorunum') : 'hafta';
        $anchor = $this->day(query('tarih'));

        $monthStart = $anchor->modify('first day of this month');
        if ($mode === 'ay') {
            $rangeStart = $monthStart->modify('monday this week');
            $rangeEnd   = $monthStart->modify('last day of this month')->modify('monday this week')->modify('+7 days');
        } else {
            $rangeStart = $anchor->modify('monday this week');
            $rangeEnd   = $rangeStart->modify('+7 days');
        }

        $base = [
            'title'      => 'Müsaitlik',
            'therapists' => $this->therapistOptions(),
            'mode'       => $mode,
            'anchor'     => $anchor,
            'monthStart' => $monthStart,
            'rangeStart' => $rangeStart,
            'rangeEnd'   => $rangeEnd,
            'actor'      => $actor,
        ];

        if ($therapist === null) {
            View::render('availability/index', $base + [
                'therapist' => null,
                'hours'     => [],
                'timeOff'   => [],
                'calendar'  => null,
            ]);
            return;
        }

        $hours = Db::all(
            'SELECT * FROM working_hours WHERE therapist_id = ? ORDER BY weekday, start_time',
            [$therapist['id']]
        );

        // Geçmiş izinler listeyi doldurmasın.
        $timeOff = Db::all(
            'SELECT * FROM time_off WHERE therapist_id = ? AND ends_at >= NOW() ORDER BY starts_at',
            [$therapist['id']]
        );

        // Takvim, listeden farklı olarak geçmişi de çizer: geçen haftaya bakan
        // biri "o gün izinliydim" bilgisini görebilmeli.
        $visibleOff = Db::all(
            'SELECT * FROM time_off
              WHERE therapist_id = ? AND starts_at < ? AND ends_at > ?
           ORDER BY starts_at',
            [
                $therapist['id'],
                $rangeEnd->format('Y-m-d H:i:s'),
                $rangeStart->format('Y-m-d H:i:s'),
            ]
        );

        View::render('availability/index', $base + [
            'therapist' => $therapist,
            'hours'     => $hours,
            'timeOff'   => $timeOff,
            'calendar'  => $this->calendar($hours, $visibleOff, $rangeStart, $rangeEnd),
        ]);
    }

    public function addHours(): void
    {
        $actor       = $this->requireAccess();
        $therapistId = $this->authorizeTarget($actor, (int) post('therapist_id'));

        $weekday = (int) post('weekday');
        $start   = $this->time(post('start_time'));
        $end     = $this->time(post('end_time'));

        if (!isset(Scheduling::WEEKDAYS[$weekday]) || $start === null || $end === null) {
            flash('error', 'Gün ve saat bilgisi geçersiz.');
            redirect($this->backTo($actor, $therapistId));
        }
        if ($start >= $end) {
            flash('error', 'Bitiş saati başlangıçtan sonra olmalı.');
            redirect($this->backTo($actor, $therapistId));
        }

        $overlap = Db::one(
            'SELECT * FROM working_hours
              WHERE therapist_id = ? AND weekday = ? AND start_time < ? AND end_time > ?
              LIMIT 1',
            [$therapistId, $weekday, $end, $start]
        );
        if ($overlap !== null) {
            flash('error', sprintf(
                '%s günü için %s–%s aralığı zaten tanımlı. Önce onu silin.',
                Scheduling::weekdayLabel($weekday),
                substr((string) $overlap['start_time'], 0, 5),
                substr((string) $overlap['end_time'], 0, 5)
            ));
            redirect($this->backTo($actor, $therapistId));
        }

        Db::run(
            'INSERT INTO working_hours (therapist_id, weekday, start_time, end_time) VALUES (?, ?, ?, ?)',
            [$therapistId, $weekday, $start, $end]
        );
        Audit::log('availability.updated', 'user', $therapistId, [
            'weekday' => $weekday,
            'aralik'  => substr($start, 0, 5) . '–' . substr($end, 0, 5),
        ]);

        flash('success', 'Çalışma saati eklendi.');
        redirect($this->backTo($actor, $therapistId));
    }

    public function removeHours(int $id): void
    {
        $actor = $this->requireAccess();
        $row   = Db::one('SELECT * FROM working_hours WHERE id = ? LIMIT 1', [$id]);

        if ($row === null) {
            flash('error', 'Kayıt bulunamadı.');
            redirect('/musaitlik');
        }
        $therapistId = $this->authorizeTarget($actor, (int) $row['therapist_id']);

        Db::run('DELETE FROM working_hours WHERE id = ?', [$id]);
        Audit::log('availability.updated', 'user', $therapistId, ['silinen_gun' => (int) $row['weekday']]);

        flash('success', 'Çalışma saati silindi.');
        redirect($this->backTo($actor, $therapistId));
    }

    public function addTimeOff(): void
    {
        $actor       = $this->requireAccess();
        $therapistId = $this->authorizeTarget($actor, (int) post('therapist_id'));

        $start = Scheduling::parseStart(post('start_date'), post('start_time'));
        $end   = Scheduling::parseStart(post('end_date'), post('end_time'));

        if ($start === null || $end === null) {
            flash('error', 'İzin başlangıç ve bitişi için geçerli tarih/saat girin.');
            redirect($this->backTo($actor, $therapistId));
        }
        if ($end <= $start) {
            flash('error', 'İzin bitişi başlangıçtan sonra olmalı.');
            redirect($this->backTo($actor, $therapistId));
        }

        // İzin, planlı randevuları otomatik iptal etmez; hangi randevuların
        // etkilendiği söylenir, kararı kullanıcı verir.
        $affected = (int) Db::value(
            'SELECT COUNT(*) FROM appointments
              WHERE therapist_id = ? AND status IN (\'scheduled\',\'confirmed\')
                AND starts_at < ? AND DATE_ADD(starts_at, INTERVAL duration_min MINUTE) > ?',
            [$therapistId, $end->format('Y-m-d H:i:s'), $start->format('Y-m-d H:i:s')]
        );

        Db::run(
            'INSERT INTO time_off (therapist_id, starts_at, ends_at, reason) VALUES (?, ?, ?, ?)',
            [
                $therapistId,
                $start->format('Y-m-d H:i:s'),
                $end->format('Y-m-d H:i:s'),
                post('reason') !== '' ? mb_substr(post('reason'), 0, 150) : null,
            ]
        );
        Audit::log('availability.time_off', 'user', $therapistId, [
            'baslangic' => $start->format('Y-m-d H:i'),
            'bitis'     => $end->format('Y-m-d H:i'),
        ]);

        flash('success', 'İzin kaydedildi.');
        if ($affected > 0) {
            flash('warning', "Bu aralıkta {$affected} planlı randevu var. Randevu ekranından yeniden düzenlemeniz gerekebilir.");
        }
        redirect($this->backTo($actor, $therapistId));
    }

    public function removeTimeOff(int $id): void
    {
        $actor = $this->requireAccess();
        $row   = Db::one('SELECT * FROM time_off WHERE id = ? LIMIT 1', [$id]);

        if ($row === null) {
            flash('error', 'Kayıt bulunamadı.');
            redirect('/musaitlik');
        }
        $therapistId = $this->authorizeTarget($actor, (int) $row['therapist_id']);

        Db::run('DELETE FROM time_off WHERE id = ?', [$id]);
        Audit::log('availability.time_off', 'user', $therapistId, ['silindi' => (int) $id]);

        flash('success', 'İzin kaydı silindi.');
        redirect($this->backTo($actor, $therapistId));
    }

    // ── Takvim geometrisi ───────────────────────────────────────

    /**
     * Haftalık şablonu ve izinleri gerçek tarihlere yayar.
     *
     * Randevu cetveliyle aynı 5 dakikalık ölçeği kullanır (bkz. Rail), böylece
     * müsaitlik ekranı randevu ekranıyla aynı yüksekliği aynı saate verir.
     *
     * @return array{startHour:int, hours:int, days:array<string, array{work:array, off:array}>}
     */
    private function calendar(array $hours, array $timeOff, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        [$startHour, $endHour] = $this->hourWindow($hours);
        $windowStart = $startHour * 60;
        $windowEnd   = $endHour * 60;

        $byWeekday = [];
        foreach ($hours as $row) {
            $byWeekday[(int) $row['weekday']][] = $row;
        }

        $days = [];
        for ($day = $from; $day < $to; $day = $day->modify('+1 day')) {
            $dayEnd = $day->modify('+1 day');

            $work = [];
            foreach ($byWeekday[(int) $day->format('N')] ?? [] as $row) {
                $band = Rail::band(
                    $this->minutes((string) $row['start_time']),
                    $this->minutes((string) $row['end_time']),
                    $windowStart,
                    $windowEnd
                );
                $work[] = [
                    'id'    => (int) $row['id'],
                    'start' => substr((string) $row['start_time'], 0, 5),
                    'end'   => substr((string) $row['end_time'], 0, 5),
                    'at'    => $band['at']  ?? null,
                    'dur'   => $band['dur'] ?? null,
                ];
            }

            $off = [];
            foreach ($timeOff as $row) {
                $start = new DateTimeImmutable((string) $row['starts_at']);
                $end   = new DateTimeImmutable((string) $row['ends_at']);
                if ($end <= $day || $start >= $dayEnd) {
                    continue;
                }

                // Birkaç güne yayılan izin her gün kendi diliminde çizilir.
                $fromMin = $start <= $day     ? 0        : (int) $start->format('G') * 60 + (int) $start->format('i');
                $toMin   = $end   >= $dayEnd  ? 24 * 60  : (int) $end->format('G')   * 60 + (int) $end->format('i');
                $band    = Rail::band($fromMin, $toMin, $windowStart, $windowEnd);

                $off[] = [
                    'id'     => (int) $row['id'],
                    'reason' => $row['reason'],
                    'label'  => $fromMin <= $windowStart && $toMin >= $windowEnd
                        ? 'Tüm gün'
                        : sprintf('%02d:%02d–%02d:%02d', intdiv($fromMin, 60), $fromMin % 60, intdiv($toMin, 60), $toMin % 60),
                    'at'     => $band['at']  ?? null,
                    'dur'    => $band['dur'] ?? null,
                ];
            }

            $days[$day->format('Y-m-d')] = ['work' => $work, 'off' => $off];
        }

        return [
            'startHour' => $startHour,
            'hours'     => $endHour - $startHour,
            'days'      => $days,
        ];
    }

    /**
     * Cetvelin saat penceresi — şablonun kendi uçlarından. İzinler pencereyi
     * genişletmez: tek bir tüm gün izni haftayı 24 saatlik bir şeride çevirirdi,
     * oysa okunması gereken şey çalışma saatlerinin şekli.
     *
     * @return array{0:int, 1:int}
     */
    private function hourWindow(array $hours): array
    {
        if ($hours === []) {
            return [9, 18];
        }

        $start = 24;
        $end   = 0;
        foreach ($hours as $row) {
            $start = min($start, intdiv($this->minutes((string) $row['start_time']), 60));
            $end   = max($end, (int) ceil($this->minutes((string) $row['end_time']) / 60));
        }

        $start = max(0, min($start, 23));
        $end   = min(24, max($end, $start + 4));

        // panel.css yalnız 14 saatlik cetvele kadar sınıf üretiyor.
        return [$start, $end - $start > 14 ? $start + 14 : $end];
    }

    /** 'HH:MM:SS' → gün başından beri geçen dakika. */
    private function minutes(string $time): int
    {
        return (int) substr($time, 0, 2) * 60 + (int) substr($time, 3, 2);
    }

    // ── Yardımcılar ─────────────────────────────────────────────

    /** Takvimin çapası, gece yarısına oturtulmuş — geçersiz parametre bugüne düşer. */
    private function day(string $anchor): DateTimeImmutable
    {
        try {
            $day = $anchor !== '' ? new DateTimeImmutable($anchor) : new DateTimeImmutable();
        } catch (Exception) {
            $day = new DateTimeImmutable();
        }
        return $day->setTime(0, 0);
    }

    private function requireAccess(): array
    {
        $user = Auth::requireLogin();
        if (!Rbac::canAny($user, ['availability.manage.all', 'availability.manage.own'])) {
            Audit::log('access.denied', 'permission', null, ['permission' => 'availability.manage']);
            View::error(403, 'Yetkiniz yok', 'Müsaitlik ayarlarına erişim yetkiniz bulunmuyor.');
            exit;
        }
        return $user;
    }

    /** Ekranda gösterilecek terapist; yönetici için ?terapist=, terapist için kendisi. */
    private function target(array $actor): ?array
    {
        if (!Rbac::can($actor, 'availability.manage.all')) {
            return Db::one('SELECT id, full_name FROM users WHERE id = ? LIMIT 1', [$actor['id']]);
        }

        $requested = (int) query('terapist');
        if ($requested > 0) {
            return Db::one(
                'SELECT id, full_name FROM users WHERE id = ? AND role = ? LIMIT 1',
                [$requested, Rbac::THERAPIST]
            );
        }
        return Db::one(
            'SELECT id, full_name FROM users WHERE role = ? AND status = \'active\' ORDER BY full_name LIMIT 1',
            [Rbac::THERAPIST]
        );
    }

    /** Yetkisiz hedefte isteği bitirir; geçerliyse terapist id'sini döndürür. */
    private function authorizeTarget(array $actor, int $therapistId): int
    {
        if (Rbac::can($actor, 'availability.manage.all')) {
            $exists = Db::value('SELECT id FROM users WHERE id = ? AND role = ?', [$therapistId, Rbac::THERAPIST]);
            if (!$exists) {
                View::error(404, 'Terapist bulunamadı');
                exit;
            }
            return $therapistId;
        }

        if ($therapistId !== (int) $actor['id']) {
            Audit::log('access.denied', 'user', $therapistId, ['reason' => 'başka terapistin müsaitliği']);
            View::error(403, 'Yetkiniz yok', 'Yalnız kendi müsaitliğinizi düzenleyebilirsiniz.');
            exit;
        }
        return (int) $actor['id'];
    }

    /**
     * Kaydettikten sonra takvim bulunduğu yerde kalır: uzak bir tarihe izin
     * yazan kullanıcı bu haftaya fırlatılmamalı.
     */
    private function backTo(array $actor, int $therapistId): string
    {
        $params = [];
        if (Rbac::can($actor, 'availability.manage.all')) {
            $params['terapist'] = $therapistId;
        }
        if (in_array(post('gorunum'), self::MODES, true)) {
            $params['gorunum'] = post('gorunum');
        }
        if (post('tarih') !== '') {
            $params['tarih'] = $this->day(post('tarih'))->format('Y-m-d');
        }

        return '/musaitlik' . ($params === [] ? '' : '?' . http_build_query($params));
    }

    /** 'HH:MM' → 'HH:MM:00'; geçersizse null. */
    private function time(string $value): ?string
    {
        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', mb_substr($value, 0, 5)) === 1
            ? mb_substr($value, 0, 5) . ':00'
            : null;
    }

    private function therapistOptions(): array
    {
        return Db::all(
            'SELECT id, full_name FROM users WHERE role = ? AND status = \'active\' ORDER BY full_name',
            [Rbac::THERAPIST]
        );
    }
}
