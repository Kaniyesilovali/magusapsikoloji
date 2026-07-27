<?php
declare(strict_types=1);

namespace Panel\Controllers;

use DateTimeImmutable;
use Exception;
use Panel\Audit;
use Panel\Auth;
use Panel\Checkins;
use Panel\ClientAccount;
use Panel\ClientScope;
use Panel\Db;
use Panel\Mailer;
use Panel\Notifications;
use Panel\Rbac;
use Panel\Schema;
use Panel\Settings;
use Panel\View;
use PDOException;

/**
 * Görüşmeci kayıtları.
 *
 * Görünürlük iki katmanlı: yöneticiler tüm kayıtları, terapistler yalnız
 * kendi görüşmecilerini görür. Filtre tek yerde (visibilityFilter) kurulur ve
 * hem listeye hem tekil kayda uygulanır — "listede gizle ama URL'den aç"
 * boşluğu bu sayede oluşmaz.
 *
 * Panel hesabı kayıtla birlikte açılır (bkz. ClientAccount): formda "hesap
 * bağla" diye bir alan yoktur, e-posta girilmişse hesap kendiliğinden oluşur
 * ve davet gider. Hesabı sonradan açmak/kapatmak görüşmeci sayfasındaki
 * düğmelerin işidir.
 */
final class ClientController
{
    public function index(): void
    {
        $actor = $this->requireView();

        [$scope, $params] = $this->visibilityFilter($actor);

        $status = query('durum', 'active');
        if (!in_array($status, ['active', 'archived', 'all'], true)) {
            $status = 'active';
        }
        if ($status !== 'all') {
            $scope   .= ' AND c.status = ?';
            $params[] = $status;
        }

        $search = query('q');
        if ($search !== '') {
            $scope   .= ' AND (c.full_name LIKE ? OR c.phone LIKE ? OR c.email LIKE ?)';
            $like     = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $clients = Db::all(
            "SELECT c.*, t.full_name AS therapist_name,
                    (SELECT MIN(a.starts_at)
                       FROM appointments a
                      WHERE a.client_id = c.id AND a.starts_at >= NOW()
                        AND a.status IN ('scheduled','confirmed')) AS next_at
               FROM clients c
          LEFT JOIN users t ON t.id = c.primary_therapist_id
              WHERE {$scope}
           ORDER BY c.full_name",
            $params
        );

        View::render('clients/index', [
            'title'   => 'Görüşmeciler',
            'clients' => $clients,
            'search'  => $search,
            'status'  => $status,
            'actor'   => $actor,
        ]);
    }

    public function show(int $id): void
    {
        $actor  = $this->requireView();
        $client = $this->find($id, $actor);

        // Terapist, görüşmecinin başka terapistlerle olan randevularını görmez.
        $onlyOwn = !Rbac::can($actor, 'appointment.view.all');
        $params  = [$id];
        $filter  = '';
        if ($onlyOwn) {
            $filter   = ' AND a.therapist_id = ?';
            $params[] = $actor['id'];
        }

        $appointments = Db::all(
            "SELECT a.*, t.full_name AS therapist_name
               FROM appointments a
               JOIN users t ON t.id = a.therapist_id
              WHERE a.client_id = ?{$filter}
           ORDER BY a.starts_at DESC
              LIMIT 50",
            $params
        );

        // Dosya bağlantısı yalnız terapiste gösterilir; varlığı da yalnız
        // kendi dosyası için sorgulanır, başkasınınki hiç aranmaz.
        $canOpenCaseFile = Schema::caseFilesReady() && Rbac::canAny($actor, ['note.write', 'note.read.own']);
        $hasCaseFile     = $canOpenCaseFile && Db::value(
            'SELECT id FROM case_files WHERE client_id = ? AND therapist_id = ?',
            [$id, $actor['id']]
        ) !== null;

        // Check-in eğrisi yalnız terapiste açık (bkz. Rbac → checkin.view.own).
        // Kaydın görünürlüğü zaten find() içinde ClientScope ile sınırlandı;
        // buraya gelen kayıt terapistin kendi görüşmecisi.
        $canSeeCheckins = Schema::checkinsReady() && Rbac::can($actor, 'checkin.view.own');
        $checkins       = $canSeeCheckins ? Checkins::history($id) : [];
        $checkinTotal   = $canSeeCheckins ? Checkins::count($id) : 0;

        // KVKK: hassas kayıt görüntülemeleri de izlenebilir olmalı.
        Audit::log('client.viewed', 'client', $id);
        if ($checkins !== []) {
            // Cümleler bu ekranda çözülüyor; okunduğu ayrıca kaydedilir.
            Audit::log('checkin.read', 'client', $id, ['kayit' => count($checkins)]);
        }

        View::render('clients/show', [
            'title'           => $client['full_name'],
            'client'          => $client,
            'appointments'    => $appointments,
            'canOpenCaseFile' => $canOpenCaseFile,
            'hasCaseFile'     => $hasCaseFile,
            'canSeeCheckins'  => $canSeeCheckins,
            'checkins'        => $checkins,
            'checkinTotal'    => $checkinTotal,
            'checkinPending'  => $canSeeCheckins ? Checkins::pendingRequest($id) : null,
            'checkinLink'     => Checkins::pendingLink(),
            'actor'           => $actor,
        ]);
    }

    /**
     * Check-in bağlantısı üretir ve görüşmeciye yollar.
     *
     * Bağlantı e-posta gitmiş olsa bile ekranda gösterilir: pilotun ölçtüğü şey
     * doldurma oranı ve oranı düşüren ilk şüpheli kanalın kendisi. Terapist aynı
     * bağlantıyı WhatsApp'tan yollayıp iki kanalı karşılaştırabilmeli.
     *
     * Haftalık gönderimi cron yapıyor (cron/checkins.php); bu düğme döngüyü
     * BAŞLATAN el hareketi — cron yalnız bir kez bağlantı almış görüşmecilere
     * devam eder.
     */
    public function sendCheckin(int $id): void
    {
        $actor  = Auth::requirePermission('checkin.view.own');
        $client = $this->find($id, $actor);

        if (!Schema::checkinsReady()) {
            flash('error', 'Check-in tabloları henüz kurulmamış. Sistem ekranından bekleyen veritabanı güncellemesini uygulayın.');
            redirect("/danisanlar/{$id}");
        }
        if ($client['status'] !== 'active') {
            flash('error', 'Arşivlenmiş görüşmeciye check-in gönderilmez. Önce kaydı arşivden çıkarın.');
            redirect("/danisanlar/{$id}");
        }

        $token = Checkins::createRequest($id);
        $sent  = $client['email'] !== null
            && Notifications::checkinRequest($client, Checkins::link($token));

        Checkins::share((string) $client['full_name'], $token, $sent);
        if ($sent) {
            Checkins::markSent($token);
        }

        Audit::log('checkin.requested', 'client', $id, ['eposta' => $sent]);

        if ($client['email'] === null) {
            flash('info', 'Kayıtta e-posta adresi yok; bağlantıyı aşağıdan kopyalayıp kendiniz iletin.');
        } elseif (!$sent) {
            flash('warning', 'E-posta gönderilemedi'
                . (Mailer::lastError() !== null ? ' (' . Mailer::lastError() . ')' : '')
                . '. Bağlantıyı aşağıdan kopyalayıp iletebilirsiniz.');
        } elseif (!Mailer::isLive()) {
            flash('warning', 'E-posta gönderimi kapalı (log sürücüsü) — ileti ÇIKMADI. '
                . 'Aşağıdaki bağlantıyı elle iletin.');
        } else {
            flash('success', 'Check-in bağlantısı ' . $client['email'] . ' adresine gönderildi.');
        }

        redirect("/danisanlar/{$id}");
    }

    public function createForm(): void
    {
        $actor = Auth::requirePermission('client.create');

        View::render('clients/form', [
            'title'      => 'Yeni Görüşmeci',
            'client'     => null,
            'therapists' => $this->therapistOptions(),
            'isManager'  => true,
            'actor'      => $actor,
        ]);
    }

    public function store(): void
    {
        $actor = Auth::requirePermission('client.create');

        $input  = $this->input(true);
        $errors = $this->validate($input, null);

        if ($errors !== []) {
            remember_input($input, $errors);
            flash('error', 'Formda eksik veya hatalı alanlar var.');
            redirect('/danisanlar/yeni');
        }

        $clientId = Db::insert(
            'INSERT INTO clients (full_name, phone, email, birth_date, primary_therapist_id,
                                  status, consent_at, consent_version, created_by, created_at)
             VALUES (?, ?, ?, ?, ?, \'active\', ?, ?, ?, NOW())',
            [
                $input['full_name'],
                $input['phone'],
                $input['email'],
                $input['birth_date'],
                $input['primary_therapist_id'],
                $input['consent'] ? date('Y-m-d H:i:s') : null,
                $input['consent'] ? $this->consentVersion() : null,
                $actor['id'],
            ]
        );

        Audit::log('client.created', 'client', $clientId, ['consent' => $input['consent']]);

        // Hesap kaydın parçasıdır, ayrı bir karar değil: e-posta varsa açılır ve
        // davet gider. Açılamadıysa kayıt yine de duruyor; nedeni söylenir.
        $account = ClientAccount::provision(
            ['id' => $clientId, 'user_id' => null] + $input,
            $actor
        );

        if ($account['status'] === ClientAccount::CREATED) {
            flash('success', 'Görüşmeci kaydı oluşturuldu ve panel hesabı açıldı.');
        } else {
            flash('success', 'Görüşmeci kaydı oluşturuldu.');
            if ($reason = ClientAccount::explain($account['status'])) {
                flash('info', $reason);
            }
        }

        redirect("/danisanlar/{$clientId}");
    }

    public function editForm(int $id): void
    {
        $actor  = $this->requireUpdate();
        $client = $this->find($id, $actor);

        View::render('clients/form', [
            'title'      => 'Görüşmeciyi Düzenle',
            'client'     => $client,
            'therapists' => $this->therapistOptions(
                $client['primary_therapist_id'] !== null ? (int) $client['primary_therapist_id'] : null
            ),
            'isManager'  => Rbac::can($actor, 'client.view.all'),
            'actor'      => $actor,
        ]);
    }

    public function update(int $id): void
    {
        $actor  = $this->requireUpdate();
        $client = $this->find($id, $actor);

        // Terapist yalnız iletişim bilgilerini düzeltebilir. Terapist ataması ve
        // panel hesabı bağlama yöneticide kalır — aksi hâlde bir terapist kendini
        // istediği görüşmecinin birincil terapisti yapabilirdi.
        $isManager = Rbac::can($actor, 'client.view.all');
        $input     = $this->input($isManager, $client);
        $errors    = $this->validate($input, $client);

        if ($errors !== []) {
            remember_input($input, $errors);
            flash('error', 'Formda eksik veya hatalı alanlar var.');
            redirect("/danisanlar/{$id}/duzenle");
        }

        // Rıza bir kez alınır; sonradan geri çekilebilir ama tarihi yeniden yazılmaz.
        $consentAt      = $client['consent_at'];
        $consentVersion = $client['consent_version'];
        if ($input['consent'] && $consentAt === null) {
            $consentAt      = date('Y-m-d H:i:s');
            $consentVersion = $this->consentVersion();
        } elseif (!$input['consent']) {
            $consentAt      = null;
            $consentVersion = null;
        }

        Db::run(
            'UPDATE clients
                SET full_name = ?, phone = ?, email = ?, birth_date = ?,
                    primary_therapist_id = ?, consent_at = ?, consent_version = ?, updated_at = NOW()
              WHERE id = ?',
            [
                $input['full_name'],
                $input['phone'],
                $input['email'],
                $input['birth_date'],
                $input['primary_therapist_id'],
                $consentAt,
                $consentVersion,
                $id,
            ]
        );

        // Bağlı hesap kaydı takip eder; yoksa düzeltilen e-posta ile hesabın
        // e-postası ayrışır ve davet eski adrese gitmeye devam ederdi.
        if ($client['user_id'] !== null) {
            ClientAccount::sync((int) $client['user_id'], $input['full_name'], $input['email']);
        }

        Audit::log('client.updated', 'client', $id, ['consent' => $consentAt !== null]);
        flash('success', 'Görüşmeci kaydı güncellendi.');
        redirect("/danisanlar/{$id}");
    }

    /** Arşivleme geri alınabilir; kayıt ve randevu geçmişi korunur. */
    public function archive(int $id): void
    {
        $actor  = $this->requireUpdate();
        $client = $this->find($id, $actor);

        $newStatus = $client['status'] === 'archived' ? 'active' : 'archived';
        Db::run('UPDATE clients SET status = ?, updated_at = NOW() WHERE id = ?', [$newStatus, $id]);

        // Arşivlenen görüşmeci panele giremez: kayıt "artık takip etmiyoruz"
        // demekken girişin açık kalması sessiz bir açık kapı olurdu.
        if ($client['user_id'] !== null) {
            ClientAccount::setSuspended($client, $newStatus === 'archived');
        }

        Audit::log('client.archived', 'client', $id, ['status' => $newStatus]);
        flash('success', $newStatus === 'archived'
            ? 'Görüşmeci arşivlendi. Kayıt ve randevu geçmişi duruyor; panel erişimi kapatıldı.'
            : 'Görüşmeci yeniden etkinleştirildi.');
        redirect("/danisanlar/{$id}");
    }

    /**
     * Kalıcı silme — KVKK "unutulma hakkı" talepleri için. Randevular ve onlara
     * bağlı seans notları da gider (şemadaki ON DELETE CASCADE), bu yüzden yalnız
     * süper adminde ve ayrı bir onayla.
     */
    public function destroy(int $id): void
    {
        $actor  = Auth::requirePermission('client.delete');
        $client = $this->find($id, $actor);

        try {
            Db::run('DELETE FROM clients WHERE id = ?', [$id]);
        } catch (PDOException) {
            flash('error', 'Kayıt silinemedi. Bağlı kayıtlar olabilir; arşivlemeyi deneyin.');
            redirect("/danisanlar/{$id}");
        }

        // Kayıt gidince giriş hesabı da gider: "silindi ama hâlâ giriş yapabiliyor"
        // bir KVKK silme talebini karşılamış olmaz.
        ClientAccount::purge($client['user_id'] !== null ? (int) $client['user_id'] : null);

        Audit::log('client.deleted', 'client', $id, ['full_name' => $client['full_name']]);
        flash('success', 'Görüşmeci kaydı, panel hesabı ve bağlı tüm randevu/seans notu kayıtları silindi.');
        redirect('/danisanlar');
    }

    // ── Panel erişimi ───────────────────────────────────────────

    /**
     * Hesabı sonradan açar — kayıt e-postasız girilmişse ya da bu değişiklikten
     * önce açılmış eski kayıtlar için. Yeni kayıtlarda kendiliğinden olduğu için
     * bu düğme yalnız hesabı olmayan kayıtlarda görünür.
     */
    public function grantAccess(int $id): void
    {
        $actor  = $this->requireAccountManage();
        $client = $this->find($id, $actor);

        // Arşivlenmiş kayda erişim açmak, arşivlemenin erişimi kapatmasıyla
        // çelişirdi; önce kayıt geri alınmalı.
        if ($client['status'] === 'archived') {
            flash('error', 'Arşivlenmiş görüşmeciye panel erişimi açılmaz. Önce kaydı arşivden çıkarın.');
            redirect("/danisanlar/{$id}");
        }

        $result = ClientAccount::provision($client, $actor);

        if ($result['status'] === ClientAccount::EXISTS) {
            flash('info', 'Bu görüşmecinin panel hesabı zaten var.');
        } elseif ($result['status'] !== ClientAccount::CREATED) {
            flash('error', (string) ClientAccount::explain($result['status']));
        }

        redirect("/danisanlar/{$id}");
    }

    /** Davet bağlantısının süresi dolduysa yenisini gönderir. */
    public function resendInvite(int $id): void
    {
        $actor  = $this->requireAccountManage();
        $client = $this->find($id, $actor);

        if ($client['user_id'] === null) {
            flash('error', 'Bu görüşmecinin panel hesabı yok.');
            redirect("/danisanlar/{$id}");
        }

        ClientAccount::reinvite($client);
        redirect("/danisanlar/{$id}");
    }

    /** Erişimi kapatır ya da yeniden açar. Hesap silinmez. */
    public function toggleAccess(int $id): void
    {
        $actor  = $this->requireAccountManage();
        $client = $this->find($id, $actor);

        if ($client['user_id'] === null) {
            flash('error', 'Bu görüşmecinin panel hesabı yok.');
            redirect("/danisanlar/{$id}");
        }

        $suspend = $client['account_status'] !== 'suspended';
        ClientAccount::setSuspended($client, $suspend);

        flash('success', $suspend
            ? 'Panel erişimi kapatıldı. Hesap duruyor, istediğinizde yeniden açabilirsiniz.'
            : 'Panel erişimi yeniden açıldı.');
        redirect("/danisanlar/{$id}");
    }

    // ── Yardımcılar ─────────────────────────────────────────────

    /**
     * Hesap işlemleri yönetimde kalır. Terapist görüşmeci kaydını düzeltebilir ama
     * kimin panele girebileceğine karar veremez — o karar giriş yetkisi dağıtmaktır.
     */
    private function requireAccountManage(): array
    {
        $user = $this->requireUpdate();
        if (!Rbac::can($user, 'user.create')) {
            Audit::log('access.denied', 'permission', null, ['permission' => 'user.create']);
            View::error(403, 'Yetkiniz yok', 'Panel hesabı açma/kapatma yetkiniz bulunmuyor.');
            exit;
        }
        return $user;
    }

    private function requireView(): array
    {
        $user = Auth::requireLogin();
        if (!Rbac::canAny($user, ['client.view.all', 'client.view.own'])) {
            Audit::log('access.denied', 'permission', null, ['permission' => 'client.view']);
            View::error(403, 'Yetkiniz yok', 'Görüşmeci kayıtlarını görüntüleme yetkiniz bulunmuyor.');
            exit;
        }
        return $user;
    }

    private function requireUpdate(): array
    {
        $user = $this->requireView();
        if (!Rbac::can($user, 'client.update')) {
            Audit::log('access.denied', 'permission', null, ['permission' => 'client.update']);
            View::error(403, 'Yetkiniz yok', 'Görüşmeci kaydını değiştirme yetkiniz bulunmuyor.');
            exit;
        }
        return $user;
    }

    /** @return array{0:string,1:array} WHERE parçası ve parametreleri */
    private function visibilityFilter(array $actor): array
    {
        return ClientScope::filter($actor);
    }

    /** Görünürlük dışındaki kayıt için 403 değil 404 döner — varlığı bile sızmasın. */
    private function find(int $id, array $actor): array
    {
        [$scope, $params] = $this->visibilityFilter($actor);
        array_unshift($params, $id);

        $client = Db::one(
            "SELECT c.*, t.full_name AS therapist_name,
                    u.email AS account_email, u.status AS account_status, u.last_login_at AS account_last_login
               FROM clients c
          LEFT JOIN users t ON t.id = c.primary_therapist_id
          LEFT JOIN users u ON u.id = c.user_id
              WHERE c.id = ? AND {$scope}
              LIMIT 1",
            $params
        );

        if ($client === null) {
            View::error(404, 'Görüşmeci bulunamadı', 'Kayıt silinmiş olabilir ya da görüntüleme yetkiniz yok.');
            exit;
        }
        return $client;
    }

    /** @param bool $isManager Terapist ataması yalnız yöneticide. */
    private function input(bool $isManager, ?array $current = null): array
    {
        $therapistId = $isManager ? post('primary_therapist_id') : (string) ($current['primary_therapist_id'] ?? '');

        return [
            'full_name'            => post('full_name'),
            'phone'                => post('phone') !== '' ? post('phone') : null,
            'email'                => post('email') !== '' ? mb_strtolower(post('email')) : null,
            'birth_date'           => post('birth_date') !== '' ? post('birth_date') : null,
            'primary_therapist_id' => $therapistId !== '' ? (int) $therapistId : null,
            'consent'              => isset($_POST['consent']),
        ];
    }

    private function validate(array $input, ?array $current): array
    {
        $errors = [];

        if (mb_strlen($input['full_name']) < 3) {
            $errors['full_name'] = 'Ad soyad en az 3 karakter olmalı.';
        }
        if ($input['phone'] !== null && !preg_match('/^[0-9 +()-]{7,20}$/', $input['phone'])) {
            $errors['phone'] = 'Telefon numarası geçersiz.';
        }
        if ($input['email'] !== null && !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Geçerli bir e-posta adresi girin.';
        }

        if ($input['birth_date'] !== null) {
            try {
                $birth = new DateTimeImmutable($input['birth_date']);
                if ($birth > new DateTimeImmutable()) {
                    $errors['birth_date'] = 'Doğum tarihi gelecekte olamaz.';
                }
            } catch (Exception) {
                $errors['birth_date'] = 'Geçerli bir tarih girin.';
            }
        }

        // Askıya alınmış terapist yeni atanamaz ama mevcut atama korunabilir —
        // aksi hâlde kaydı düzenlemek, geçmiş terapist bilgisini sessizce silerdi.
        $unchanged = $current !== null
            && (int) $input['primary_therapist_id'] === (int) ($current['primary_therapist_id'] ?? 0);

        if ($input['primary_therapist_id'] !== null && !$unchanged) {
            $ok = Db::value(
                'SELECT id FROM users WHERE id = ? AND role = ? AND status = \'active\'',
                [$input['primary_therapist_id'], Rbac::THERAPIST]
            );
            if (!$ok) {
                $errors['primary_therapist_id'] = 'Seçilen terapist bulunamadı.';
            }
        }

        return $errors;
    }

    /**
     * Aktif terapistler. Kaydın mevcut terapisti askıya alınmış olsa bile listede
     * kalır; yoksa tarayıcı ilk seçeneğe düşer ve düzenleme atamayı silerdi.
     *
     * @return array<int,array>
     */
    private function therapistOptions(?int $includeId = null): array
    {
        return Db::all(
            'SELECT id, full_name FROM users
              WHERE role = ? AND (status = \'active\' OR id = ?)
           ORDER BY full_name',
            [Rbac::THERAPIST, $includeId ?? 0]
        );
    }

    private function consentVersion(): string
    {
        return Settings::get('consent_version', '1.0');
    }
}
