<?php
declare(strict_types=1);

namespace Panel\Controllers;

use Panel\Audit;
use Panel\Auth;
use Panel\Db;
use Panel\View;

final class ProfileController
{
    public function edit(): void
    {
        $user = Auth::requirePermission('profile.self');
        View::render('profile/edit', ['title' => 'Profilim', 'user' => $user]);
    }

    public function update(): void
    {
        $user = Auth::requirePermission('profile.self');

        $fullName = post('full_name');
        $phone    = post('phone');
        $errors   = [];

        if (mb_strlen($fullName) < 3) {
            $errors['full_name'] = 'Ad soyad en az 3 karakter olmalı.';
        }
        if ($phone !== '' && !preg_match('/^[0-9 +()-]{7,20}$/', $phone)) {
            $errors['phone'] = 'Telefon numarası geçersiz.';
        }

        if ($errors !== []) {
            remember_input(['full_name' => $fullName, 'phone' => $phone], $errors);
            flash('error', 'Formda hatalı alanlar var.');
            redirect('/profil');
        }

        // E-posta ve rol bilinçli olarak buradan değiştirilemez: e-posta aynı zamanda
        // kimlik bilgisidir, değişikliği yönetici üzerinden yapılır.
        Db::run('UPDATE users SET full_name = ?, phone = ?, updated_at = NOW() WHERE id = ?', [
            $fullName,
            $phone !== '' ? $phone : null,
            $user['id'],
        ]);

        Audit::log('profile.updated', 'user', (int) $user['id']);
        flash('success', 'Profiliniz güncellendi.');
        redirect('/profil');
    }

    public function passwordForm(): void
    {
        $user = Auth::requirePermission('profile.self');
        View::render('profile/password', [
            'title'  => 'Şifre Değiştir',
            'user'   => $user,
            'forced' => (int) $user['must_change_password'] === 1,
        ]);
    }

    public function updatePassword(): void
    {
        $user    = Auth::requirePermission('profile.self');
        $current = (string) ($_POST['current_password'] ?? '');
        $new     = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');

        if (!password_verify($current, (string) $user['password_hash'])) {
            flash('error', 'Mevcut şifreniz hatalı.');
            redirect('/profil/sifre');
        }
        if ($problem = Auth::passwordProblem($new, $confirm)) {
            flash('error', $problem);
            redirect('/profil/sifre');
        }
        if ($new === $current) {
            flash('error', 'Yeni şifre eskisiyle aynı olamaz.');
            redirect('/profil/sifre');
        }

        Auth::setPassword((int) $user['id'], $new);
        Audit::log('auth.password_changed', 'user', (int) $user['id']);

        // Şifre değiştikten sonra oturum kimliği yenilenir.
        session_regenerate_id(true);

        flash('success', 'Şifreniz güncellendi.');
        redirect('/profil');
    }
}
