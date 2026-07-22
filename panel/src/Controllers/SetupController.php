<?php
declare(strict_types=1);

namespace Panel\Controllers;

use Panel\Audit;
use Panel\Auth;
use Panel\Crypto;
use Panel\Db;
use Panel\Migrator;
use Panel\Rbac;
use Panel\View;

/**
 * Tek seferlik ilk kurulum: şema oluşturma + ilk süper admin.
 *
 * cPanel'de SSH garantisi olmadığı için tarayıcıdan çalıştırılabilir. Kapı,
 * "hiç kullanıcı yok" koşuluyla kapatılır: ilk süper admin oluşur oluşmaz
 * bu rotaların tamamı 404 döner, yeniden açılamaz.
 */
final class SetupController
{
    private function guard(): void
    {
        if (!Migrator::needsSetup()) {
            View::error(404, 'Sayfa bulunamadı', 'Kurulum zaten tamamlanmış.');
            exit;
        }
    }

    public function index(): void
    {
        $this->guard();

        $schemaReady = Migrator::schemaReady();

        View::render('setup/index', [
            'title'       => 'İlk kurulum',
            'schemaReady' => $schemaReady,
            'pending'     => $schemaReady ? Migrator::pending() : [],
            'sodiumOk'    => Crypto::available(),
        ], 'auth_layout');
    }

    public function migrate(): void
    {
        $this->guard();

        try {
            $ran = Migrator::run();
        } catch (\Throwable $e) {
            flash('error', 'Şema kurulamadı: ' . $e->getMessage());
            redirect('/kurulum');
        }

        flash($ran === [] ? 'info' : 'success', $ran === []
            ? 'Uygulanacak yeni tablo yok, şema zaten güncel.'
            : count($ran) . ' migration uygulandı: ' . implode(', ', $ran));
        redirect('/kurulum');
    }

    public function store(): void
    {
        $this->guard();

        if (!Migrator::schemaReady()) {
            flash('error', 'Önce veritabanı tablolarını oluşturun.');
            redirect('/kurulum');
        }

        $name     = post('full_name');
        $email    = mb_strtolower(post('email'));
        $password = (string) ($_POST['password'] ?? '');
        $confirm  = (string) ($_POST['password_confirm'] ?? '');

        $problem = match (true) {
            mb_strlen($name) < 3                            => 'Ad soyad en az 3 karakter olmalı.',
            !filter_var($email, FILTER_VALIDATE_EMAIL)      => 'Geçerli bir e-posta adresi girin.',
            default                                          => Auth::passwordProblem($password, $confirm),
        };

        if ($problem !== null) {
            remember_input(['full_name' => $name, 'email' => $email]);
            flash('error', $problem);
            redirect('/kurulum');
        }

        $id = Db::insert(
            'INSERT INTO users (email, password_hash, full_name, role, status, created_at)
             VALUES (?, ?, ?, ?, \'active\', NOW())',
            [$email, password_hash($password, PASSWORD_DEFAULT), $name, Rbac::SUPER_ADMIN]
        );

        Audit::log('user.created', 'user', $id, ['role' => Rbac::SUPER_ADMIN, 'via' => 'ilk kurulum']);

        flash('success', 'Süper admin hesabı oluşturuldu. Şimdi giriş yapabilirsiniz.');
        redirect('/giris');
    }
}
