<?php
declare(strict_types=1);

namespace Panel\Controllers;

use DateTimeImmutable;
use Exception;
use Panel\Audit;
use Panel\Auth;
use Panel\ClientScope;
use Panel\Db;
use Panel\Money;
use Panel\Notifications;
use Panel\Rbac;
use Panel\Scheduling;
use Panel\Schema;
use Panel\View;

/**
 * Randevu takvimi.
 *
 * Kayıt kuralları [Scheduling] sınıfında; burada yalnız yetki, doğrulama ve
 * ekran akışı var. Çakışma kaydı engeller, mesai dışı/izin yalnız uyarır ve
 * kullanıcının açık onayıyla geçilebilir.
 */
final class AppointmentController
{
    private const MIN_DURATION = 15;
    private const MAX_DURATION = 240;

    public function index(): void
    {
        $actor = $this->requireView();

        $weekStart = $this->weekStart(query('hafta'));
        $weekEnd   = $weekStart->modify('+7 days');

        [$scope, $params] = $this->visibilityFilter($actor);

        // Terapist filtresi yalnız tüm takvimi görenler için anlamlı.
        $therapistFilter = (int) query('terapist');
        if ($therapistFilter > 0 && Rbac::can($actor, 'appointment.view.all')) {
            $scope   .= ' AND a.therapist_id = ?';
            $params[] = $therapistFilter;
        } else {
            $therapistFilter = 0;
        }

        $rows = Db::all(
            "SELECT a.*, c.full_name AS client_name, c.phone AS client_phone, t.full_name AS therapist_name,
                    EXISTS (SELECT 1 FROM session_notes n WHERE n.appointment_id = a.id) AS has_note
               FROM appointments a
               JOIN clients c ON c.id = a.client_id
               JOIN users   t ON t.id = a.therapist_id
              WHERE a.starts_at >= ? AND a.starts_at < ? AND {$scope}
           ORDER BY a.starts_at",
            array_merge([$weekStart->format('Y-m-d H:i:s'), $weekEnd->format('Y-m-d H:i:s')], $params)
        );

        // Gün gün grupla — boş günler de görünsün diye anahtarlar önden açılır.
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $days[$weekStart->modify("+{$i} days")->format('Y-m-d')] = [];
        }
        foreach ($rows as $row) {
            $days[substr((string) $row['starts_at'], 0, 10)][] = $row;
        }

        View::render('appointments/index', [
            'title'           => 'Randevular',
            'days'            => $days,
            'weekStart'       => $weekStart,
            'total'           => count($rows),
            'therapists'      => Rbac::can($actor, 'appointment.view.all') ? $this->therapistOptions() : [],
            'therapistFilter' => $therapistFilter,
            'actor'           => $actor,
        ]);
    }

    public function createForm(): void
    {
        $actor = Auth::requirePermission('appointment.create');

        View::render('appointments/form', [
            'title'       => 'Yeni Randevu',
            'appointment' => null,
            'clients'     => $this->clientOptions($actor),
            'therapists'  => $this->therapistOptions(),
            'defaults'    => [
                'client_id'    => (string) ((int) query('danisan') ?: ''),
                'therapist_id' => $actor['role'] === Rbac::THERAPIST ? (string) $actor['id'] : '',
                'date'         => query('tarih') !== '' ? query('tarih') : date('Y-m-d'),
                'time'         => query('saat'),
                'duration_min' => (string) Scheduling::defaultDuration(
                    $actor['role'] === Rbac::THERAPIST ? (int) $actor['id'] : null
                ),
            ],
            'actor'       => $actor,
        ]);
    }

    public function store(): void
    {
        $actor = Auth::requirePermission('appointment.create');

        $input  = $this->input($actor);
        $start  = Scheduling::parseStart($input['date'], $input['time']);
        $errors = $this->validate($input, $start, $actor, null);

        if ($errors !== [] || $start === null) {
            remember_input($input, $errors);
            flash('error', 'Randevu kaydedilemedi.');
            redirect('/randevular/yeni');
        }

        if ($this->needsConfirmation((int) $input['therapist_id'], $start, (int) $input['duration_min'], $input)) {
            redirect('/randevular/yeni');
        }

        $appointmentId = Db::insert(
            'INSERT INTO appointments (client_id, therapist_id, starts_at, duration_min, status, location, note, created_by, created_at)
             VALUES (?, ?, ?, ?, \'scheduled\', ?, ?, ?, NOW())',
            [
                $input['client_id'],
                $input['therapist_id'],
                $start->format('Y-m-d H:i:s'),
                $input['duration_min'],
                $input['location'],
                $input['note'] !== '' ? $input['note'] : null,
                $actor['id'],
            ]
        );

        $this->applyFee($appointmentId, $input['fee']);

        Audit::log('appointment.created', 'appointment', $appointmentId, [
            'starts_at' => $start->format('Y-m-d H:i'),
            'therapist' => (int) $input['therapist_id'],
        ]);
        $this->notify($appointmentId, 'created', $actor, $input['notify']);

        flash('success', 'Randevu oluşturuldu: ' . tr_date_label($start->format('Y-m-d')) . ' ' . $start->format('H:i') . '.');
        redirect('/randevular?hafta=' . $start->format('Y-m-d'));
    }

    public function editForm(int $id): void
    {
        $actor       = $this->requireUpdate();
        $appointment = $this->find($id, $actor);

        View::render('appointments/form', [
            'title'       => 'Randevuyu Düzenle',
            'appointment' => $appointment,
            'clients'     => $this->clientOptions($actor, (int) $appointment['client_id']),
            'therapists'  => $this->therapistOptions((int) $appointment['therapist_id']),
            'defaults'    => [],
            'actor'       => $actor,
        ]);
    }

    public function update(int $id): void
    {
        $actor       = $this->requireUpdate();
        $appointment = $this->find($id, $actor);

        $input  = $this->input($actor);
        $start  = Scheduling::parseStart($input['date'], $input['time']);
        $errors = $this->validate($input, $start, $actor, $appointment);

        if ($errors !== [] || $start === null) {
            remember_input($input, $errors);
            flash('error', 'Randevu güncellenemedi.');
            redirect("/randevular/{$id}/duzenle");
        }

        if ($this->needsConfirmation((int) $input['therapist_id'], $start, (int) $input['duration_min'], $input, $id)) {
            redirect("/randevular/{$id}/duzenle");
        }

        Db::run(
            'UPDATE appointments
                SET client_id = ?, therapist_id = ?, starts_at = ?, duration_min = ?,
                    location = ?, note = ?, status = ?, updated_at = NOW()
              WHERE id = ?',
            [
                $input['client_id'],
                $input['therapist_id'],
                $start->format('Y-m-d H:i:s'),
                $input['duration_min'],
                $input['location'],
                $input['note'] !== '' ? $input['note'] : null,
                $input['status'],
                $id,
            ]
        );

        $this->applyFee($id, $input['fee']);

        Audit::log('appointment.updated', 'appointment', $id, [
            'starts_at' => $start->format('Y-m-d H:i'),
            'status'    => $input['status'],
        ]);
        $this->notify($id, 'updated', $actor, $input['notify']);

        flash('success', 'Randevu güncellendi.');
        redirect('/randevular?hafta=' . $start->format('Y-m-d'));
    }

    /** Hızlı durum değişikliği — liste ekranındaki düğmeler. */
    public function setStatus(int $id): void
    {
        $actor       = $this->requireUpdate();
        $appointment = $this->find($id, $actor);

        $status = post('status');
        // İptal ayrı akış: gerekçe alınması gerekiyor.
        if (!in_array($status, ['scheduled', 'confirmed', 'completed', 'no_show'], true)) {
            flash('error', 'Geçersiz durum.');
            redirect('/randevular?hafta=' . substr((string) $appointment['starts_at'], 0, 10));
        }

        Db::run(
            'UPDATE appointments SET status = ?, cancel_reason = NULL, updated_at = NOW() WHERE id = ?',
            [$status, $id]
        );
        Audit::log('appointment.status_changed', 'appointment', $id, ['status' => $status]);

        flash('success', 'Randevu durumu "' . Scheduling::statusLabel($status) . '" olarak güncellendi.');
        redirect('/randevular?hafta=' . substr((string) $appointment['starts_at'], 0, 10));
    }

    public function cancel(int $id): void
    {
        $actor       = Auth::requirePermission('appointment.cancel');
        $appointment = $this->find($id, $actor);

        $reason = mb_substr(post('cancel_reason'), 0, 255);

        Db::run(
            'UPDATE appointments SET status = \'cancelled\', cancel_reason = ?, updated_at = NOW() WHERE id = ?',
            [$reason !== '' ? $reason : null, $id]
        );
        Audit::log('appointment.cancelled', 'appointment', $id, ['reason' => $reason]);
        $this->notify($id, 'cancelled', $actor, isset($_POST['notify']));

        flash('success', 'Randevu iptal edildi. Saat yeniden kullanılabilir.');
        redirect('/randevular?hafta=' . substr((string) $appointment['starts_at'], 0, 10));
    }

    // ── Yardımcılar ─────────────────────────────────────────────

    private function requireView(): array
    {
        $user = Auth::requireLogin();
        if (!Rbac::canAny($user, ['appointment.view.all', 'appointment.view.own'])) {
            Audit::log('access.denied', 'permission', null, ['permission' => 'appointment.view']);
            View::error(403, 'Yetkiniz yok', 'Randevuları görüntüleme yetkiniz bulunmuyor.');
            exit;
        }
        return $user;
    }

    private function requireUpdate(): array
    {
        $user = $this->requireView();
        if (!Rbac::can($user, 'appointment.update')) {
            Audit::log('access.denied', 'permission', null, ['permission' => 'appointment.update']);
            View::error(403, 'Yetkiniz yok', 'Randevu değiştirme yetkiniz bulunmuyor.');
            exit;
        }
        return $user;
    }

    /** @return array{0:string,1:array} */
    private function visibilityFilter(array $actor): array
    {
        if (Rbac::can($actor, 'appointment.view.all')) {
            return ['1 = 1', []];
        }
        if ($actor['role'] === Rbac::THERAPIST) {
            return ['a.therapist_id = ?', [$actor['id']]];
        }
        // Danışan: yalnız kendi kaydına bağlı randevular (users.id ↔ clients.user_id).
        return ['c.user_id = ?', [$actor['id']]];
    }

    private function find(int $id, array $actor): array
    {
        [$scope, $params] = $this->visibilityFilter($actor);
        array_unshift($params, $id);

        $appointment = Db::one(
            "SELECT a.*, c.full_name AS client_name, t.full_name AS therapist_name
               FROM appointments a
               JOIN clients c ON c.id = a.client_id
               JOIN users   t ON t.id = a.therapist_id
              WHERE a.id = ? AND {$scope}
              LIMIT 1",
            $params
        );

        if ($appointment === null) {
            View::error(404, 'Randevu bulunamadı', 'Kayıt silinmiş olabilir ya da görüntüleme yetkiniz yok.');
            exit;
        }
        return $appointment;
    }

    private function input(array $actor): array
    {
        return [
            // Terapist başkasının takvimine randevu yazamaz.
            'therapist_id'      => $actor['role'] === Rbac::THERAPIST ? (string) $actor['id'] : post('therapist_id'),
            'client_id'         => post('client_id'),
            'date'              => post('date'),
            'time'              => post('time'),
            'duration_min'      => post('duration_min'),
            'location'          => post('location'),
            'note'              => mb_substr(post('note'), 0, 255),
            'status'            => post('status', 'scheduled'),
            'confirm_warnings'  => post('confirm_warnings'),
            'notify'            => isset($_POST['notify']),
            'fee'               => post('fee'),
        ];
    }

    /**
     * Ücret ayrı bir UPDATE ile yazılır: sütun, veritabanı güncellemesi elle
     * uygulanana kadar mevcut olmayabilir ve ana INSERT'i ona bağlamak, güncelleme
     * yapılmadan randevu oluşturmayı tamamen bozardı.
     *
     * Alan formda hiç yoksa (yetkisi olmayan kullanıcı) dokunulmaz — aksi hâlde
     * boş POST, girilmiş ücreti sessizce silerdi.
     */
    private function applyFee(int $appointmentId, string $raw): void
    {
        if (!array_key_exists('fee', $_POST) || !Schema::paymentsReady()) {
            return;
        }
        Db::run(
            'UPDATE appointments SET fee = ? WHERE id = ?',
            [$raw === '' ? null : Money::parse($raw), $appointmentId]
        );
    }

    /**
     * Bildirim gönderimi kaydı geri almaz: randevunun kaydedilmiş olması,
     * e-postanın gitmiş olmasından önemlidir. Başarısızlık yalnız bildirilir.
     */
    private function notify(int $appointmentId, string $event, array $actor, bool $wanted): void
    {
        if (!$wanted) {
            return;
        }
        $failed = Notifications::appointment($appointmentId, $event, (int) $actor['id']);
        if ($failed !== []) {
            flash('warning', 'Randevu kaydedildi ama bildirim gönderilemedi: ' . implode(', ', $failed)
                . '. E-posta ayarlarını kontrol edin.');
        }
    }

    private function validate(array $input, ?DateTimeImmutable $start, array $actor, ?array $current): array
    {
        $errors = [];

        // Danışan, aktörün görebildiği kayıtlardan biri olmalı.
        [$scope, $params] = ClientScope::filter($actor);
        array_unshift($params, (int) $input['client_id']);
        $client = Db::one("SELECT c.id FROM clients c WHERE c.id = ? AND ({$scope}) LIMIT 1", $params);
        if ($client === null) {
            $errors['client_id'] = 'Danışan seçin.';
        }

        // Askıya alınmış terapiste yeni randevu yazılamaz; mevcut randevusu düzenlenebilir.
        $therapistUnchanged = $current !== null
            && (int) $input['therapist_id'] === (int) $current['therapist_id'];

        if (!$therapistUnchanged) {
            $therapist = Db::one(
                'SELECT id FROM users WHERE id = ? AND role = ? AND status = \'active\' LIMIT 1',
                [(int) $input['therapist_id'], Rbac::THERAPIST]
            );
            if ($therapist === null) {
                $errors['therapist_id'] = 'Aktif bir terapist seçin.';
            }
        }

        if ($start === null) {
            $errors['date'] = 'Geçerli bir tarih ve saat girin.';
        }

        $duration = (int) $input['duration_min'];
        if ($duration < self::MIN_DURATION || $duration > self::MAX_DURATION) {
            $errors['duration_min'] = 'Süre ' . self::MIN_DURATION . '–' . self::MAX_DURATION . ' dakika arasında olmalı.';
        }

        if (!array_key_exists($input['location'], Scheduling::LOCATIONS)) {
            $errors['location'] = 'Görüşme yerini seçin.';
        }

        if ($input['fee'] !== '' && Money::parse($input['fee']) === null) {
            $errors['fee'] = 'Ücreti sayı olarak girin (ör. 1500 ya da 1.500,00).';
        }

        // İptal, gerekçe alındığı için yalnız kendi akışından yapılır; düzenleme
        // formu mevcut durumu koruyabilir ama "iptal"e çeviremez.
        if ($current !== null) {
            $allowed = ['scheduled', 'confirmed', 'completed', 'no_show', (string) $current['status']];
            if (!in_array($input['status'], $allowed, true)) {
                $errors['status'] = 'Geçersiz durum.';
            }
        }

        // Çakışma yalnız diğer alanlar geçerliyse sorulabilir.
        if ($errors === [] && $start !== null) {
            $conflicts = Scheduling::conflicts(
                (int) $input['therapist_id'],
                (int) $input['client_id'],
                $start,
                $duration,
                $current !== null ? (int) $current['id'] : null
            );
            if ($conflicts !== []) {
                $clash = $conflicts[0];
                $errors['time'] = sprintf(
                    'Bu saat dolu: %s — %s / %s. Başka bir saat seçin.',
                    dt($clash['starts_at'], 'd.m.Y H:i'),
                    $clash['client_name'],
                    $clash['therapist_name']
                );
            }
        }

        return $errors;
    }

    /**
     * Mesai dışı / izin / geçmiş tarih uyarıları. Onay kutusu işaretlenmemişse
     * formu uyarılarla geri gönderir ve true döner.
     */
    private function needsConfirmation(
        int $therapistId,
        DateTimeImmutable $start,
        int $duration,
        array $input,
        ?int $excludeId = null
    ): bool {
        $warnings = Scheduling::warnings($therapistId, $start, $duration);
        if ($warnings === [] || $input['confirm_warnings'] === '1') {
            return false;
        }

        foreach ($warnings as $warning) {
            flash('warning', $warning);
        }
        remember_input($input, [
            'confirm_warnings' => 'Yine de kaydetmek istiyorsanız onay kutusunu işaretleyip tekrar gönderin.',
        ]);
        return true;
    }

    /** Kaydın mevcut terapisti askıya alınmışsa bile listede kalır — bkz. ClientController. */
    private function therapistOptions(?int $includeId = null): array
    {
        return Db::all(
            'SELECT id, full_name FROM users
              WHERE role = ? AND (status = \'active\' OR id = ?)
           ORDER BY full_name',
            [Rbac::THERAPIST, $includeId ?? 0]
        );
    }

    /**
     * Aktörün randevu yazabileceği danışanlar.
     * Düzenlemede kaydın kendi danışanı listeye zorla eklenir; arşivlenmiş bir
     * danışanın randevusu aksi hâlde kaydedilemez hâle gelirdi.
     */
    private function clientOptions(array $actor, ?int $includeId = null): array
    {
        [$scope, $params] = ClientScope::filter($actor);
        $params[] = $includeId ?? 0;

        return Db::all(
            "SELECT c.id, c.full_name FROM clients c
              WHERE ({$scope}) AND (c.status = 'active' OR c.id = ?)
           ORDER BY c.full_name",
            $params
        );
    }

    /** Pazartesi 00:00 — geçersiz parametre bugünün haftasına düşer. */
    private function weekStart(string $anchor): DateTimeImmutable
    {
        try {
            $day = $anchor !== '' ? new DateTimeImmutable($anchor) : new DateTimeImmutable();
        } catch (Exception) {
            $day = new DateTimeImmutable();
        }
        return $day->modify('monday this week')->setTime(0, 0);
    }
}
