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
use Panel\Rail;
use Panel\Rbac;
use Panel\Scheduling;
use Panel\Schema;
use Panel\View;

/**
 * Randevu takvimi.
 *
 * Kayıt kuralları [Scheduling] sınıfında; burada yalnız yetki, doğrulama ve
 * ekran akışı var. Çakışma ve özel zaman kaydı engeller, mesai dışı/izin yalnız uyarır ve
 * kullanıcının açık onayıyla geçilebilir.
 *
 * Üç görünüm var ve üçü de aynı sorguyu paylaşır, yalnız aralıkları farklı:
 * **hafta** ölçekli takvim (varsayılan), **ay** kuş bakışı ızgara, **liste**
 * ise satır içi eylemlerin (onayla / tamamlandı / iptal) bulunduğu tek yer —
 * takvim kutusuna bir eylem çubuğu sığmaz, bu yüzden liste kaldırılmadı.
 */
final class AppointmentController
{
    private const MIN_DURATION = 15;
    private const MAX_DURATION = 240;

    /** ?gorunum= değerleri. */
    private const MODES = ['hafta', 'ay', 'liste'];

    public function index(): void
    {
        $actor = $this->requireView();

        $mode   = in_array(query('gorunum'), self::MODES, true) ? query('gorunum') : 'hafta';
        // 'hafta' eski bağlantılarda çapa parametresiydi; kırılmasın diye okunuyor.
        $anchor = $this->day(query('tarih') !== '' ? query('tarih') : query('hafta'));

        $monthStart = $anchor->modify('first day of this month');
        if ($mode === 'ay') {
            // Izgara tam haftalardan oluşur: ayın ilk gününün pazartesisinden
            // son gününün pazarına kadar. Komşu ayların günleri soluk çizilir.
            $rangeStart = $monthStart->modify('monday this week');
            $rangeEnd   = $monthStart->modify('last day of this month')->modify('monday this week')->modify('+7 days');
        } else {
            $rangeStart = $anchor->modify('monday this week');
            $rangeEnd   = $rangeStart->modify('+7 days');
        }

        [$scope, $params] = $this->visibilityFilter($actor);

        // Terapist filtresi. Yönetici için boş = tüm terapistler. Ekip takvimini
        // gören terapist için boş = kendi takvimi (günlük ekranı değişmesin),
        // 'tumu' = tüm ekip, kimlik = o terapist.
        $canAll          = Rbac::can($actor, 'appointment.view.all');
        $canTeam         = !$canAll && Rbac::can($actor, 'appointment.view.team');
        $therapistParam  = query('terapist');
        $therapistFilter = (int) $therapistParam;
        if ($canAll && $therapistFilter > 0) {
            $scope   .= ' AND a.therapist_id = ?';
            $params[] = $therapistFilter;
        } elseif ($canTeam && $therapistParam === 'tumu') {
            [$scope, $params] = ['1 = 1', []];
            $therapistFilter  = 0;
        } elseif ($canTeam && $therapistFilter > 0) {
            [$scope, $params] = ['a.therapist_id = ?', [$therapistFilter]];
        } else {
            $therapistFilter = 0;
            $therapistParam  = '';
        }
        if ($therapistFilter > 0) {
            $therapistParam = (string) $therapistFilter;
        }

        $rows = Db::all(
            "SELECT a.*, c.full_name AS client_name, c.phone AS client_phone, t.full_name AS therapist_name,
                    EXISTS (SELECT 1 FROM session_notes n WHERE n.appointment_id = a.id) AS has_note
               FROM appointments a
               JOIN clients c ON c.id = a.client_id
               JOIN users   t ON t.id = a.therapist_id
              WHERE a.starts_at >= ? AND a.starts_at < ? AND {$scope}
           ORDER BY a.starts_at",
            array_merge([$rangeStart->format('Y-m-d H:i:s'), $rangeEnd->format('Y-m-d H:i:s')], $params)
        );

        // Başka terapistin randevusu: yalnız takvimde yer tutmak için gerekenler
        // görünüme geçer. Kırpma burada, görünümde değil — bir şablon hatası
        // telefonu ya da idari notu sızdıramasın diye alanlar hiç taşınmaz.
        if ($canTeam) {
            $rows = array_map(
                static fn (array $r): array => (int) $r['therapist_id'] === (int) $actor['id']
                    ? $r + ['foreign' => false]
                    : self::teamRow($r),
                $rows
            );
        }

        // Özel zamanlar randevularla aynı kapsamı izler. Bireyin ekranına hiç
        // gelmez: terapistin özel saati yalnız ekibin bilgisidir.
        $busy = [];
        if ($canAll || $actor['role'] === Rbac::THERAPIST) {
            $busyOwner = match (true) {
                $therapistFilter > 0                  => $therapistFilter,
                $canAll, $therapistParam === 'tumu'   => null,
                default                               => (int) $actor['id'],
            };
            $busy = Scheduling::busyRows($rangeStart, $rangeEnd, $busyOwner, $actor);
        }

        // Gün gün grupla — boş günler de görünsün diye anahtarlar önden açılır.
        $days = [];
        for ($day = $rangeStart; $day < $rangeEnd; $day = $day->modify('+1 day')) {
            $days[$day->format('Y-m-d')] = [];
        }
        foreach ($rows as $row) {
            $days[substr((string) $row['starts_at'], 0, 10)][] = $row;
        }
        // Ay ve liste özel zamanı randevuların arasında, saat sırasıyla gösterir.
        foreach ($busy as $row) {
            $days[substr((string) $row['starts_at'], 0, 10)][] = $row;
        }
        if ($busy !== []) {
            foreach ($days as &$items) {
                usort($items, static fn (array $a, array $b): int
                    => strcmp((string) $a['starts_at'], (string) $b['starts_at']));
            }
            unset($items);
        }

        // Ay görünümünde sayaç yalnız o aya ait randevuları saymalı; ızgaranın
        // komşu ay günleri toplama karışırsa "Temmuz'da 34 randevu" yanlış olur.
        $total = $mode === 'ay'
            ? count(array_filter($rows, static fn (array $r): bool
                => substr((string) $r['starts_at'], 0, 7) === $monthStart->format('Y-m')))
            : count($rows);

        View::render('appointments/index', [
            'title'           => 'Randevular',
            'mode'            => $mode,
            'anchor'          => $anchor,
            'monthStart'      => $monthStart,
            'rangeStart'      => $rangeStart,
            'rangeEnd'        => $rangeEnd,
            'days'            => $days,
            // Cetvel iptalleri çizmez: iptal saati gerçekten serbest bırakır,
            // o yüzden takvimde de yer kaplamamalı. Liste ve ay ızgarası
            // gösterir — orada kayıt "olan biten"in dökümüdür.
            'rail'            => $mode === 'hafta'
                ? Rail::week(
                    array_merge(
                        array_values(array_filter($rows, static fn (array $r): bool => $r['status'] !== 'cancelled')),
                        $busy
                    ),
                    $rangeStart,
                    new DateTimeImmutable()
                )
                : null,
            'total'           => $total,
            'therapists'      => $canAll || $canTeam ? $this->therapistOptions() : [],
            'therapistFilter' => $therapistFilter,
            'therapistParam'  => $therapistParam,
            'teamView'        => $canTeam,
            'canBlock'        => Schema::busyTimesReady()
                && Rbac::canAny($actor, ['availability.manage.all', 'availability.manage.own']),
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
                // Bireyden gelindiyse kaydındaki tür hazır seçili gelir.
                'session_type' => $this->clientSessionType((int) query('danisan'), $actor),
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
        $this->applySessionType($appointmentId, $input['session_type']);

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

        // İptalde mesai dışı / izin / geçmiş tarih uyarısının anlamı yok: saat boşalıyor.
        $isCancelled  = $input['status'] === 'cancelled';
        $wasCancelled = $appointment['status'] === 'cancelled';

        if (!$isCancelled
            && $this->needsConfirmation((int) $input['therapist_id'], $start, (int) $input['duration_min'], $input, $id)) {
            redirect("/randevular/{$id}/duzenle");
        }

        // Gerekçe yalnız iptalde tutulur; randevu geri alınırsa silinir (bkz. setStatus).
        $reason = $isCancelled && $input['cancel_reason'] !== '' ? $input['cancel_reason'] : null;

        Db::run(
            'UPDATE appointments
                SET client_id = ?, therapist_id = ?, starts_at = ?, duration_min = ?,
                    location = ?, note = ?, status = ?, cancel_reason = ?, updated_at = NOW()
              WHERE id = ?',
            [
                $input['client_id'],
                $input['therapist_id'],
                $start->format('Y-m-d H:i:s'),
                $input['duration_min'],
                $input['location'],
                $input['note'] !== '' ? $input['note'] : null,
                $input['status'],
                $reason,
                $id,
            ]
        );

        $this->applyFee($id, $input['fee']);
        $this->applySessionType($id, $input['session_type']);

        // Formdan yapılan iptal, listedeki "İptal et" ile aynı izi bırakır.
        if ($isCancelled && !$wasCancelled) {
            Audit::log('appointment.cancelled', 'appointment', $id, ['reason' => (string) $reason]);
            $this->notify($id, 'cancelled', $actor, $input['notify']);

            flash('success', 'Randevu iptal edildi. Saat yeniden kullanılabilir.');
            redirect('/randevular?hafta=' . $start->format('Y-m-d'));
        }

        Audit::log('appointment.updated', 'appointment', $id, [
            'starts_at' => $start->format('Y-m-d H:i'),
            'status'    => $input['status'],
        ]);
        // Zaten iptal olan kayıttaki düzeltme bildirilmez: "Randevunuz güncellendi,
        // yeni zaman…" e-postası iptal edilmiş randevuyu geçerli gösterirdi.
        $this->notify($id, 'updated', $actor, $input['notify'] && !$isCancelled);

        flash('success', 'Randevu güncellendi.');
        redirect('/randevular?hafta=' . $start->format('Y-m-d'));
    }

    /** Hızlı durum değişikliği — liste ekranındaki düğmeler. */
    public function setStatus(int $id): void
    {
        $actor       = $this->requireUpdate();
        $appointment = $this->find($id, $actor);

        $status = post('status');
        // İptal buradan geçmez: gerekçe ve bildirim kendi akışında (cancel, update).
        if (!in_array($status, ['scheduled', 'confirmed', 'completed', 'no_show'], true)) {
            flash('error', 'Geçersiz durum.');
            redirect('/randevular?hafta=' . substr((string) $appointment['starts_at'], 0, 10));
        }

        // "Gelmedi" saati serbest bırakmıştı; araya özel zaman girilmişse
        // randevu o saati sessizce geri alamaz.
        if (in_array($status, Scheduling::BLOCKING_STATUSES, true)
            && !in_array($appointment['status'], Scheduling::BLOCKING_STATUSES, true)) {
            $busy = Scheduling::busyConflict(
                (int) $appointment['therapist_id'],
                new DateTimeImmutable((string) $appointment['starts_at']),
                (int) $appointment['duration_min']
            );
            if ($busy !== null) {
                flash('error', Scheduling::busyMessage($busy));
                redirect('/randevular?hafta=' . substr((string) $appointment['starts_at'], 0, 10));
            }
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

    /**
     * Ekip takvimindeki başka bir terapistin randevusu. Beyaz liste: yeni bir
     * sütun (ücret, hatırlatma vb.) eklendiğinde kendiliğinden görünmesin.
     * client_id de düşer — bağlantı kurulmaz, kutu salt okunurdur.
     */
    private static function teamRow(array $r): array
    {
        return [
            'id'             => $r['id'],
            'therapist_id'   => $r['therapist_id'],
            'starts_at'      => $r['starts_at'],
            'duration_min'   => $r['duration_min'],
            'status'         => $r['status'],
            'location'       => $r['location'],
            'client_name'    => $r['client_name'],
            'therapist_name' => $r['therapist_name'],
            'client_id'      => null,
            'client_phone'   => null,
            'note'           => null,
            'cancel_reason'  => null,
            'has_note'       => 0,
            'foreign'        => true,
        ];
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
        // Birey: yalnız kendi kaydına bağlı randevular (users.id ↔ clients.user_id).
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
            'cancel_reason'     => mb_substr(post('cancel_reason'), 0, 255),
            'confirm_warnings'  => post('confirm_warnings'),
            'notify'            => isset($_POST['notify']),
            'fee'               => post('fee'),
            'session_type'      => post('session_type'),
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
     * Seans türü de ücret gibi ayrı yazılır: 014 göçü uygulanmadan sütun yok.
     * Alan formda yoksa (göç öncesi açılmış sekme) dokunulmaz.
     */
    private function applySessionType(int $appointmentId, string $type): void
    {
        if (!array_key_exists('session_type', $_POST) || !Schema::sessionTypesReady()) {
            return;
        }
        Db::run('UPDATE appointments SET session_type = ? WHERE id = ?', [$type, $appointmentId]);
    }

    /** Bireyin kaydındaki seans türü — yalnız aktörün görebildiği bir kayıtsa. */
    private function clientSessionType(int $clientId, array $actor): string
    {
        if ($clientId <= 0 || !Schema::sessionTypesReady()) {
            return '';
        }
        [$scope, $params] = ClientScope::filter($actor);
        array_unshift($params, $clientId);
        return (string) Db::value(
            "SELECT c.session_type FROM clients c WHERE c.id = ? AND ({$scope}) LIMIT 1",
            $params
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

        // Birey, aktörün görebildiği kayıtlardan biri olmalı.
        [$scope, $params] = ClientScope::filter($actor);
        array_unshift($params, (int) $input['client_id']);
        $client = Db::one("SELECT c.id FROM clients c WHERE c.id = ? AND ({$scope}) LIMIT 1", $params);
        if ($client === null) {
            $errors['client_id'] = 'Birey seçin.';
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

        if (Schema::sessionTypesReady() && !array_key_exists($input['session_type'], Scheduling::SESSION_TYPES)) {
            $errors['session_type'] = 'Seans türünü seçin.';
        }

        if ($input['fee'] !== '' && Money::parse($input['fee']) === null) {
            $errors['fee'] = 'Ücreti sayı olarak girin (ör. 1500 ya da 1.500,00).';
        }

        // İptal ayrı bir yetkidir; yetkisi olmayan mevcut durumu koruyabilir ama
        // randevuyu "iptal"e çeviremez.
        if ($current !== null) {
            $allowed = ['scheduled', 'confirmed', 'completed', 'no_show', (string) $current['status']];
            if (Rbac::can($actor, 'appointment.cancel')) {
                $allowed[] = 'cancelled';
            }
            if (!in_array($input['status'], $allowed, true)) {
                $errors['status'] = 'Geçersiz durum.';
            }
        }

        // Saat tutmayan durumlar (iptal, gelmedi) çakışmaz; yeni kayıtta formdaki
        // durum okunmaz, hep "planlandı"dır.
        $holdsSlot = $current === null || in_array($input['status'], Scheduling::BLOCKING_STATUSES, true);

        // Çakışma yalnız diğer alanlar geçerliyse sorulabilir.
        if ($errors === [] && $start !== null && $holdsSlot) {
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

        // Özel zaman onayla geçilmez.
        if ($errors === [] && $start !== null && $holdsSlot) {
            $busy = Scheduling::busyConflict((int) $input['therapist_id'], $start, $duration);
            if ($busy !== null) {
                $errors['time'] = Scheduling::busyMessage($busy);
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
     * Aktörün randevu yazabileceği bireyler.
     * Düzenlemede kaydın kendi bireyi listeye zorla eklenir; arşivlenmiş bir
     * bireyin randevusu aksi hâlde kaydedilemez hâle gelirdi.
     */
    private function clientOptions(array $actor, ?int $includeId = null): array
    {
        [$scope, $params] = ClientScope::filter($actor);
        $params[] = $includeId ?? 0;

        // Seans türü, formda birey değişince türü ön doldurmak için taşınır.
        $type = Schema::sessionTypesReady() ? ', c.session_type' : '';

        return Db::all(
            "SELECT c.id, c.full_name{$type} FROM clients c
              WHERE ({$scope}) AND (c.status = 'active' OR c.id = ?)
           ORDER BY c.full_name",
            $params
        );
    }

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
}
