<?php
declare(strict_types=1);

namespace Panel\Controllers;

use DateTimeImmutable;
use Panel\Audit;
use Panel\Auth;
use Panel\Db;
use Panel\Rbac;
use Panel\Scheduling;
use Panel\Schema;
use Panel\View;

/**
 * Özel zaman — terapistin kendisi için kapattığı saatler.
 *
 * Takvimde kırmızı "Dolu" olarak durur ve o saate randevu yazılamaz (bkz.
 * Scheduling::busyConflict). İzinden farkı bu: izin uyarır, özel zaman engeller.
 *
 * Yetki müsaitlikle aynı: terapist yalnız kendininkini, yönetim hepsininkini
 * açıp kapatabilir. Not yalnız terapistin kendisine ait — yönetici saati taşır
 * ya da siler ama notu ne görür ne değiştirir.
 */
final class BusyTimeController
{
    public function createForm(): void
    {
        $actor = $this->requireAccess();

        View::render('appointments/busy_form', [
            'title'      => 'Özel Zaman',
            'busy'       => null,
            'therapists' => $this->isManager($actor) ? $this->therapistOptions() : [],
            'ownNote'    => !$this->isManager($actor),
            'defaults'   => [
                'therapist_id' => (string) ((int) query('terapist') ?: ''),
                'date'         => query('tarih') !== '' ? query('tarih') : date('Y-m-d'),
            ],
            'actor'      => $actor,
        ]);
    }

    public function store(): void
    {
        $actor       = $this->requireAccess();
        $input       = $this->input();
        $therapistId = $this->isManager($actor) ? (int) $input['therapist_id'] : (int) $actor['id'];

        [$start, $end, $errors] = $this->validate($input, $therapistId);

        if ($this->isManager($actor)) {
            $exists = Db::value(
                'SELECT id FROM users WHERE id = ? AND role = ? AND status = \'active\'',
                [$therapistId, Rbac::THERAPIST]
            );
            if (!$exists) {
                $errors['therapist_id'] = 'Aktif bir terapist seçin.';
            }
        }

        if ($errors !== [] || $start === null || $end === null) {
            remember_input($input, $errors);
            flash('error', 'Özel zaman kaydedilemedi.');
            redirect('/randevular/ozel-zaman/yeni');
        }

        $id = Db::insert(
            'INSERT INTO busy_times (therapist_id, starts_at, ends_at, note, created_by, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())',
            [
                $therapistId,
                $start->format('Y-m-d H:i:s'),
                $end->format('Y-m-d H:i:s'),
                !$this->isManager($actor) && $input['note'] !== '' ? $input['note'] : null,
                $actor['id'],
            ]
        );
        $this->log('availability.busy_added', $id, $therapistId, $start, $end);

        flash('success', sprintf(
            'Özel zaman kaydedildi: %s %s–%s. Bu saate randevu yazılamaz.',
            tr_date_label($start->format('Y-m-d')),
            $start->format('H:i'),
            $end->format('H:i')
        ));
        redirect('/randevular?tarih=' . $start->format('Y-m-d'));
    }

    public function editForm(int $id): void
    {
        $actor = $this->requireAccess();
        $busy  = $this->find($id, $actor);
        $own   = (int) $busy['therapist_id'] === (int) $actor['id'];

        // Not başkasının ekranına hiç taşınmaz; gizlemek şablona bırakılmaz.
        if (!$own) {
            $busy['note'] = null;
        }

        View::render('appointments/busy_form', [
            'title'      => 'Özel Zaman',
            'busy'       => $busy,
            'therapists' => [],
            'ownNote'    => $own,
            'defaults'   => [],
            'actor'      => $actor,
        ]);
    }

    public function update(int $id): void
    {
        $actor       = $this->requireAccess();
        $busy        = $this->find($id, $actor);
        $therapistId = (int) $busy['therapist_id'];
        $input       = $this->input();

        [$start, $end, $errors] = $this->validate($input, $therapistId);

        if ($errors !== [] || $start === null || $end === null) {
            remember_input($input, $errors);
            flash('error', 'Özel zaman güncellenemedi.');
            redirect("/randevular/ozel-zaman/{$id}/duzenle");
        }

        $note = $therapistId === (int) $actor['id']
            ? ($input['note'] !== '' ? $input['note'] : null)
            : $busy['note'];

        Db::run(
            'UPDATE busy_times SET starts_at = ?, ends_at = ?, note = ?, updated_at = NOW() WHERE id = ?',
            [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'), $note, $id]
        );
        $this->log('availability.busy_updated', $id, $therapistId, $start, $end);

        flash('success', 'Özel zaman güncellendi.');
        redirect('/randevular?tarih=' . $start->format('Y-m-d'));
    }

    public function destroy(int $id): void
    {
        $actor = $this->requireAccess();
        $busy  = $this->find($id, $actor);

        Db::run('DELETE FROM busy_times WHERE id = ?', [$id]);
        $this->log(
            'availability.busy_deleted',
            $id,
            (int) $busy['therapist_id'],
            new DateTimeImmutable((string) $busy['starts_at']),
            new DateTimeImmutable((string) $busy['ends_at'])
        );

        flash('success', 'Özel zaman silindi. Saat yeniden randevuya açık.');
        redirect('/randevular?tarih=' . substr((string) $busy['starts_at'], 0, 10));
    }

    // ── Yardımcılar ─────────────────────────────────────────────

    private function requireAccess(): array
    {
        $user = Auth::requireLogin();
        if (!Rbac::canAny($user, ['availability.manage.all', 'availability.manage.own'])) {
            Audit::log('access.denied', 'permission', null, ['permission' => 'availability.manage']);
            View::error(403, 'Yetkiniz yok', 'Özel zaman girme yetkiniz bulunmuyor.');
            exit;
        }
        if (!Schema::busyTimesReady()) {
            View::error(
                503,
                'Veritabanı güncellemesi bekliyor',
                'Özel zaman, Sistem ekranındaki bekleyen güncelleme uygulandıktan sonra kullanılabilir.'
            );
            exit;
        }
        return $user;
    }

    private function isManager(array $actor): bool
    {
        return Rbac::can($actor, 'availability.manage.all');
    }

    private function find(int $id, array $actor): array
    {
        $busy = Db::one(
            'SELECT b.*, t.full_name AS therapist_name
               FROM busy_times b
               JOIN users t ON t.id = b.therapist_id
              WHERE b.id = ? LIMIT 1',
            [$id]
        );

        if ($busy === null || !Scheduling::canManageBusy($actor, (int) $busy['therapist_id'])) {
            View::error(404, 'Özel zaman bulunamadı', 'Kayıt silinmiş olabilir ya da görüntüleme yetkiniz yok.');
            exit;
        }
        return $busy;
    }

    private function input(): array
    {
        return [
            'therapist_id' => post('therapist_id'),
            'date'         => post('date'),
            'start_time'   => post('start_time'),
            'end_time'     => post('end_time'),
            'note'         => mb_substr(post('note'), 0, 150),
        ];
    }

    /** @return array{0:?DateTimeImmutable, 1:?DateTimeImmutable, 2:array<string,string>} */
    private function validate(array $input, int $therapistId): array
    {
        $errors = [];
        $start  = Scheduling::parseStart($input['date'], $input['start_time']);
        $end    = Scheduling::parseStart($input['date'], $input['end_time']);

        if ($start === null || $end === null) {
            $errors['date'] = 'Geçerli bir tarih, başlangıç ve bitiş saati girin.';
        } elseif ($end <= $start) {
            $errors['date'] = 'Bitiş saati başlangıçtan sonra olmalı. Gün sonuna kadar kapatmak için 23:59 yazın.';
        } else {
            // Üst üste binmeye bu yönden de izin yok: yazılmış bir randevunun
            // üstü sessizce kapatılırsa takvim aynı saat için iki şey söylerdi.
            $minutes = intdiv($end->getTimestamp() - $start->getTimestamp(), 60);
            $clash   = Scheduling::conflicts($therapistId, 0, $start, $minutes)[0] ?? null;
            if ($clash !== null) {
                $errors['date'] = sprintf(
                    'Bu aralıkta randevu var: %s — %s. Önce randevuyu taşıyın ya da iptal edin.',
                    dt($clash['starts_at'], 'd.m.Y H:i'),
                    $clash['client_name']
                );
            }
        }

        return [$start, $end, $errors];
    }

    /** Not denetim kaydına yazılmaz: kayıtları okuyan, notu göremeyen kişidir. */
    private function log(
        string $action,
        int $id,
        int $therapistId,
        DateTimeImmutable $start,
        DateTimeImmutable $end
    ): void {
        Audit::log($action, 'busy_time', $id, [
            'therapist' => $therapistId,
            'baslangic' => $start->format('Y-m-d H:i'),
            'bitis'     => $end->format('Y-m-d H:i'),
        ]);
    }

    private function therapistOptions(): array
    {
        return Db::all(
            'SELECT id, full_name FROM users WHERE role = ? AND status = \'active\' ORDER BY full_name',
            [Rbac::THERAPIST]
        );
    }
}
