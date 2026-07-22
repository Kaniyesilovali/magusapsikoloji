<?php
declare(strict_types=1);

namespace Panel\Controllers;

use Panel\Audit;
use Panel\Auth;
use Panel\Db;
use Panel\Rbac;
use Panel\Scheduling;
use Panel\View;

/**
 * Terapist müsaitliği: haftalık çalışma şablonu + izin aralıkları.
 *
 * Randevu kaydını engellemez, yalnız [Scheduling::warnings] üzerinden uyarı
 * üretir. Şablon hiç girilmemişse uyarı da çıkmaz — boş şablon "her saat uygun"
 * demektir, "hiçbir saat uygun değil" değil.
 */
final class AvailabilityController
{
    public function index(): void
    {
        $actor     = $this->requireAccess();
        $therapist = $this->target($actor);

        if ($therapist === null) {
            View::render('availability/index', [
                'title'      => 'Müsaitlik',
                'therapist'  => null,
                'therapists' => $this->therapistOptions(),
                'hours'      => [],
                'timeOff'    => [],
                'actor'      => $actor,
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

        View::render('availability/index', [
            'title'      => 'Müsaitlik',
            'therapist'  => $therapist,
            'therapists' => $this->therapistOptions(),
            'hours'      => $hours,
            'timeOff'    => $timeOff,
            'actor'      => $actor,
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

    // ── Yardımcılar ─────────────────────────────────────────────

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

    private function backTo(array $actor, int $therapistId): string
    {
        return Rbac::can($actor, 'availability.manage.all')
            ? '/musaitlik?terapist=' . $therapistId
            : '/musaitlik';
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
