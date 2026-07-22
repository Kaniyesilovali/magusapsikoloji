<?php
declare(strict_types=1);

namespace Panel\Controllers;

use DateTimeImmutable;
use Exception;
use Panel\Audit;
use Panel\Auth;
use Panel\ClientScope;
use Panel\Db;
use Panel\Rbac;
use Panel\Settings;
use Panel\View;
use PDOException;

/**
 * Danışan kayıtları.
 *
 * Görünürlük iki katmanlı: yöneticiler tüm kayıtları, terapistler yalnız
 * kendi danışanlarını görür. Filtre tek yerde (visibilityFilter) kurulur ve
 * hem listeye hem tekil kayda uygulanır — "listede gizle ama URL'den aç"
 * boşluğu bu sayede oluşmaz.
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
            'title'   => 'Danışanlar',
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

        // Terapist, danışanın başka terapistlerle olan randevularını görmez.
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

        // KVKK: hassas kayıt görüntülemeleri de izlenebilir olmalı.
        Audit::log('client.viewed', 'client', $id);

        View::render('clients/show', [
            'title'        => $client['full_name'],
            'client'       => $client,
            'appointments' => $appointments,
            'actor'        => $actor,
        ]);
    }

    public function createForm(): void
    {
        $actor = Auth::requirePermission('client.create');

        View::render('clients/form', [
            'title'      => 'Yeni Danışan',
            'client'     => null,
            'therapists' => $this->therapistOptions(),
            'accounts'   => $this->accountOptions(null),
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
            'INSERT INTO clients (user_id, full_name, phone, email, birth_date, primary_therapist_id,
                                  status, consent_at, consent_version, created_by, created_at)
             VALUES (?, ?, ?, ?, ?, ?, \'active\', ?, ?, ?, NOW())',
            [
                $input['user_id'],
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
        flash('success', 'Danışan kaydı oluşturuldu.');
        redirect("/danisanlar/{$clientId}");
    }

    public function editForm(int $id): void
    {
        $actor  = $this->requireUpdate();
        $client = $this->find($id, $actor);

        View::render('clients/form', [
            'title'      => 'Danışanı Düzenle',
            'client'     => $client,
            'therapists' => $this->therapistOptions(
                $client['primary_therapist_id'] !== null ? (int) $client['primary_therapist_id'] : null
            ),
            'accounts'   => $this->accountOptions($client['user_id'] !== null ? (int) $client['user_id'] : null),
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
        // istediği danışanın birincil terapisti yapabilirdi.
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
                SET user_id = ?, full_name = ?, phone = ?, email = ?, birth_date = ?,
                    primary_therapist_id = ?, consent_at = ?, consent_version = ?, updated_at = NOW()
              WHERE id = ?',
            [
                $input['user_id'],
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

        Audit::log('client.updated', 'client', $id, ['consent' => $consentAt !== null]);
        flash('success', 'Danışan kaydı güncellendi.');
        redirect("/danisanlar/{$id}");
    }

    /** Arşivleme geri alınabilir; kayıt ve randevu geçmişi korunur. */
    public function archive(int $id): void
    {
        $actor  = $this->requireUpdate();
        $client = $this->find($id, $actor);

        $newStatus = $client['status'] === 'archived' ? 'active' : 'archived';
        Db::run('UPDATE clients SET status = ?, updated_at = NOW() WHERE id = ?', [$newStatus, $id]);

        Audit::log('client.archived', 'client', $id, ['status' => $newStatus]);
        flash('success', $newStatus === 'archived'
            ? 'Danışan arşivlendi. Kayıt ve randevu geçmişi duruyor.'
            : 'Danışan yeniden etkinleştirildi.');
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

        Audit::log('client.deleted', 'client', $id, ['full_name' => $client['full_name']]);
        flash('success', 'Danışan kaydı ve bağlı tüm randevu/seans notu kayıtları silindi.');
        redirect('/danisanlar');
    }

    // ── Yardımcılar ─────────────────────────────────────────────

    private function requireView(): array
    {
        $user = Auth::requireLogin();
        if (!Rbac::canAny($user, ['client.view.all', 'client.view.own'])) {
            Audit::log('access.denied', 'permission', null, ['permission' => 'client.view']);
            View::error(403, 'Yetkiniz yok', 'Danışan kayıtlarını görüntüleme yetkiniz bulunmuyor.');
            exit;
        }
        return $user;
    }

    private function requireUpdate(): array
    {
        $user = $this->requireView();
        if (!Rbac::can($user, 'client.update')) {
            Audit::log('access.denied', 'permission', null, ['permission' => 'client.update']);
            View::error(403, 'Yetkiniz yok', 'Danışan kaydını değiştirme yetkiniz bulunmuyor.');
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
            "SELECT c.*, t.full_name AS therapist_name, u.email AS account_email
               FROM clients c
          LEFT JOIN users t ON t.id = c.primary_therapist_id
          LEFT JOIN users u ON u.id = c.user_id
              WHERE c.id = ? AND {$scope}
              LIMIT 1",
            $params
        );

        if ($client === null) {
            View::error(404, 'Danışan bulunamadı', 'Kayıt silinmiş olabilir ya da görüntüleme yetkiniz yok.');
            exit;
        }
        return $client;
    }

    /** @param bool $isManager Terapist ataması ve hesap bağlama yalnız yöneticide. */
    private function input(bool $isManager, ?array $current = null): array
    {
        $therapistId = $isManager ? post('primary_therapist_id') : (string) ($current['primary_therapist_id'] ?? '');
        $userId      = $isManager ? post('user_id')              : (string) ($current['user_id'] ?? '');

        return [
            'full_name'            => post('full_name'),
            'phone'                => post('phone') !== '' ? post('phone') : null,
            'email'                => post('email') !== '' ? mb_strtolower(post('email')) : null,
            'birth_date'           => post('birth_date') !== '' ? post('birth_date') : null,
            'primary_therapist_id' => $therapistId !== '' ? (int) $therapistId : null,
            'user_id'              => $userId !== '' ? (int) $userId : null,
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

        if ($input['user_id'] !== null) {
            $account = Db::one('SELECT id, role FROM users WHERE id = ? LIMIT 1', [$input['user_id']]);
            if ($account === null || $account['role'] !== Rbac::CLIENT) {
                $errors['user_id'] = 'Panel hesabı yalnız "Danışan" rolündeki bir kullanıcı olabilir.';
            } else {
                // clients.user_id benzersiz; başka kayda bağlıysa veritabanı hatası yerine
                // anlaşılır bir mesaj gösterilir.
                $taken = $current === null
                    ? Db::value('SELECT id FROM clients WHERE user_id = ?', [$input['user_id']])
                    : Db::value('SELECT id FROM clients WHERE user_id = ? AND id <> ?', [$input['user_id'], $current['id']]);
                if ($taken) {
                    $errors['user_id'] = 'Bu panel hesabı başka bir danışan kaydına bağlı.';
                }
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

    /** Danışan rolündeki, henüz başka kayda bağlanmamış panel hesapları. */
    private function accountOptions(?int $currentUserId): array
    {
        return Db::all(
            'SELECT u.id, u.full_name, u.email
               FROM users u
          LEFT JOIN clients c ON c.user_id = u.id
              WHERE u.role = ? AND (c.id IS NULL OR u.id = ?)
           ORDER BY u.full_name',
            [Rbac::CLIENT, $currentUserId ?? 0]
        );
    }

    private function consentVersion(): string
    {
        return Settings::get('consent_version', '1.0');
    }
}
