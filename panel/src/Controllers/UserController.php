<?php
declare(strict_types=1);

namespace Panel\Controllers;

use Panel\Audit;
use Panel\Auth;
use Panel\Db;
use Panel\Invites;
use Panel\Rbac;
use Panel\View;
use PDOException;

final class UserController
{
    public function index(): void
    {
        $actor = Auth::requirePermission('user.view');

        // Admin, admin/süper admin hesaplarını listede görmez; yönetemediğini görmesin.
        $visibleRoles = Rbac::assignableRoles($actor);
        $placeholders = implode(',', array_fill(0, count($visibleRoles), '?'));

        $search = query('q');
        $params = $visibleRoles;
        $where  = "(u.role IN ({$placeholders}) OR u.id = ?)";
        $params[] = $actor['id'];

        if ($search !== '') {
            $where .= ' AND (u.full_name LIKE ? OR u.email LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $users = Db::all(
            "SELECT u.*, c.full_name AS creator_name
               FROM users u
          LEFT JOIN users c ON c.id = u.created_by
              WHERE {$where}
           ORDER BY FIELD(u.role, 'super_admin','admin','therapist','client'), u.full_name",
            $params
        );

        View::render('users/index', [
            'title'  => 'Kullanıcılar',
            'users'  => $users,
            'search' => $search,
            'actor'  => $actor,
        ]);
    }

    public function createForm(): void
    {
        $actor = Auth::requirePermission('user.create');
        View::render('users/form', [
            'title'      => 'Yeni Kullanıcı',
            'user'       => null,
            'roles'      => $this->creatableRoles($actor),
            'actor'      => $actor,
        ]);
    }

    public function store(): void
    {
        $actor = Auth::requirePermission('user.create');

        $input  = ['full_name' => post('full_name'), 'email' => mb_strtolower(post('email')), 'phone' => post('phone'), 'role' => post('role')];
        $errors = $this->validate($input, $actor, null);

        // Birey hesabı buradan açılmaz: hesap birey kaydının parçasıdır ve
        // onunla birlikte açılır. Buradan açılan bir birey hesabı hiçbir kayda
        // bağlı olmadığı için giriş yapar ama hiçbir şey göremezdi.
        if ($input['role'] === Rbac::CLIENT) {
            $errors['role'] = 'Birey hesabı Bireyler ekranından, kayıtla birlikte açılır.';
        }

        if ($errors !== []) {
            remember_input($input, $errors);
            flash('error', 'Formda eksik veya hatalı alanlar var.');
            redirect('/kullanicilar/yeni');
        }

        // Şifre kullanıcı tarafından davet bağlantısıyla belirlenir; buraya
        // asla oturum açılamayacak rastgele bir hash konur.
        $userId = Db::insert(
            'INSERT INTO users (email, password_hash, full_name, phone, role, status, created_by, created_at)
             VALUES (?, ?, ?, ?, ?, \'invited\', ?, NOW())',
            [
                $input['email'],
                password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT),
                $input['full_name'],
                $input['phone'] !== '' ? $input['phone'] : null,
                $input['role'],
                $actor['id'],
            ]
        );

        if ($input['role'] === Rbac::THERAPIST) {
            Db::run('INSERT IGNORE INTO therapist_profiles (user_id) VALUES (?)', [$userId]);
        }

        Audit::log('user.created', 'user', $userId, ['role' => $input['role'], 'email' => $input['email']]);

        $invite = $this->sendInvite($userId, $input['full_name'], $input['email'], $actor['full_name']);
        flash('info', 'Kullanıcı oluşturuldu.');
        $this->shareInvite($input['full_name'], $input['email'], $invite);

        redirect('/kullanicilar');
    }

    public function editForm(int $id): void
    {
        $actor  = Auth::requirePermission('user.update');
        $target = $this->findManageable($id, $actor);

        View::render('users/form', [
            'title' => 'Kullanıcıyı Düzenle',
            'user'  => $target,
            'roles' => Rbac::assignableRoles($actor),
            'actor' => $actor,
        ]);
    }

    public function update(int $id): void
    {
        $actor  = Auth::requirePermission('user.update');
        $target = $this->findManageable($id, $actor);
        $isSelf = (int) $target['id'] === (int) $actor['id'];

        $input = [
            'full_name' => post('full_name'),
            'email'     => mb_strtolower(post('email')),
            'phone'     => post('phone'),
            // Kimse kendi rolünü veya durumunu değiştiremez — yetki yükseltmeyi ve
            // yanlışlıkla kendini kilitlemeyi önler.
            'role'      => $isSelf ? $target['role']   : post('role'),
            'status'    => $isSelf ? $target['status'] : post('status'),
        ];

        $errors = $this->validate($input, $actor, $target);
        if ($errors !== []) {
            remember_input($input, $errors);
            flash('error', 'Formda eksik veya hatalı alanlar var.');
            redirect("/kullanicilar/{$id}/duzenle");
        }

        // Son süper admin rolünü/erişimini kaybederse panele kimse giremez.
        if ($target['role'] === Rbac::SUPER_ADMIN
            && ($input['role'] !== Rbac::SUPER_ADMIN || $input['status'] !== 'active')
            && $this->activeSuperAdminCount() <= 1) {
            flash('error', 'Sistemdeki tek süper admin hesabının rolü veya durumu değiştirilemez.');
            redirect("/kullanicilar/{$id}/duzenle");
        }

        Db::run(
            'UPDATE users SET full_name = ?, email = ?, phone = ?, role = ?, status = ?, updated_at = NOW() WHERE id = ?',
            [
                $input['full_name'],
                $input['email'],
                $input['phone'] !== '' ? $input['phone'] : null,
                $input['role'],
                $input['status'],
                $id,
            ]
        );

        if ($input['role'] === Rbac::THERAPIST) {
            Db::run('INSERT IGNORE INTO therapist_profiles (user_id) VALUES (?)', [$id]);
        }

        Audit::log('user.updated', 'user', $id, [
            'role'   => $input['role'],
            'status' => $input['status'],
        ]);

        flash('success', 'Kullanıcı güncellendi.');
        redirect('/kullanicilar');
    }

    public function destroy(int $id): void
    {
        $actor  = Auth::requirePermission('user.delete');
        $target = $this->findManageable($id, $actor);

        if ((int) $target['id'] === (int) $actor['id']) {
            flash('error', 'Kendi hesabınızı silemezsiniz.');
            redirect('/kullanicilar');
        }
        if ($target['role'] === Rbac::SUPER_ADMIN && $this->activeSuperAdminCount() <= 1) {
            flash('error', 'Sistemdeki tek süper admin hesabı silinemez.');
            redirect('/kullanicilar');
        }

        try {
            Db::run('DELETE FROM users WHERE id = ?', [$id]);
        } catch (PDOException) {
            // Terapistin randevu geçmişi varsa yabancı anahtar silmeyi engeller;
            // kayıt bütünlüğü korunsun diye hesap askıya alınır.
            Db::run('UPDATE users SET status = \'suspended\', updated_at = NOW() WHERE id = ?', [$id]);
            Audit::log('user.updated', 'user', $id, ['status' => 'suspended', 'reason' => 'silinemedi: bağlı kayıtlar var']);
            flash('warning', 'Bu kullanıcının randevu kayıtları olduğu için hesap silinemedi; erişimi kapatıldı (askıya alındı).');
            redirect('/kullanicilar');
        }

        Audit::log('user.deleted', 'user', $id, ['email' => $target['email'], 'role' => $target['role']]);
        flash('success', 'Kullanıcı silindi.');
        redirect('/kullanicilar');
    }

    public function resendInvite(int $id): void
    {
        $actor  = Auth::requirePermission('user.update');
        $target = $this->findManageable($id, $actor);

        $invite = $this->sendInvite((int) $target['id'], (string) $target['full_name'], (string) $target['email'], (string) $actor['full_name']);
        Audit::log('user.invite_resent', 'user', (int) $target['id'], ['sent' => $invite['sent']]);

        $this->shareInvite((string) $target['full_name'], (string) $target['email'], $invite);
        redirect('/kullanicilar');
    }

    // ── Yardımcılar ─────────────────────────────────────────────

    /** Hedef kullanıcıyı getirir; aktörün yetkisi yoksa isteği 403/404 ile bitirir. */
    private function findManageable(int $id, array $actor): array
    {
        $target = Db::one('SELECT * FROM users WHERE id = ? LIMIT 1', [$id]);
        if ($target === null) {
            View::error(404, 'Kullanıcı bulunamadı');
            exit;
        }
        // Kendi kaydını her zaman düzenleyebilir; başkası için matris kararı geçerli.
        if ((int) $target['id'] !== (int) $actor['id'] && !Rbac::canManageUser($actor, $target)) {
            Audit::log('access.denied', 'user', $id, ['reason' => 'kullanıcı yönetimi']);
            View::error(403, 'Yetkiniz yok', 'Bu kullanıcı üzerinde işlem yapma yetkiniz bulunmuyor.');
            exit;
        }
        return $target;
    }

    private function validate(array $input, array $actor, ?array $target): array
    {
        $errors = [];

        if (mb_strlen($input['full_name']) < 3) {
            $errors['full_name'] = 'Ad soyad en az 3 karakter olmalı.';
        }
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Geçerli bir e-posta adresi girin.';
        } else {
            $exists = $target === null
                ? Db::value('SELECT id FROM users WHERE email = ?', [$input['email']])
                : Db::value('SELECT id FROM users WHERE email = ? AND id <> ?', [$input['email'], $target['id']]);
            if ($exists) {
                $errors['email'] = 'Bu e-posta adresi başka bir hesapta kayıtlı.';
            }
        }
        if ($input['phone'] !== '' && !preg_match('/^[0-9 +()-]{7,20}$/', $input['phone'])) {
            $errors['phone'] = 'Telefon numarası geçersiz.';
        }
        if (!in_array($input['role'], Rbac::assignableRoles($actor), true)
            && !($target !== null && $input['role'] === $target['role'])) {
            $errors['role'] = 'Bu rolü atama yetkiniz yok.';
        }
        if (isset($input['status']) && !in_array($input['status'], ['active', 'invited', 'suspended'], true)) {
            $errors['status'] = 'Geçersiz durum.';
        }

        return $errors;
    }

    /**
     * Yeni hesap açarken seçilebilecek roller. Birey listede yoktur; o hesap
     * birey kaydıyla birlikte açılır (bkz. ClientAccount). Mevcut birey
     * hesapları bu ekrandan düzenlenmeye devam eder — assignableRoles hâlâ
     * bireyi içerir, yalnızca "yeni kayıt" yolu kapalıdır.
     *
     * @return array<int,string>
     */
    private function creatableRoles(array $actor): array
    {
        return array_values(array_filter(
            Rbac::assignableRoles($actor),
            static fn (string $role): bool => $role !== Rbac::CLIENT
        ));
    }

    private function activeSuperAdminCount(): int
    {
        return (int) Db::value(
            'SELECT COUNT(*) FROM users WHERE role = ? AND status = \'active\'',
            [Rbac::SUPER_ADMIN]
        );
    }

    /** @return array{sent:bool,link:string,error:?string} */
    private function sendInvite(int $userId, string $name, string $email, string $inviterName): array
    {
        return Invites::send($userId, $name, $email, $inviterName);
    }

    private function shareInvite(string $name, string $email, array $invite): void
    {
        Invites::share($name, $email, $invite);
    }
}
