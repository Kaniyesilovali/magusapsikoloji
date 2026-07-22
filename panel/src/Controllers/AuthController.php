<?php
declare(strict_types=1);

namespace Panel\Controllers;

use Panel\Audit;
use Panel\Auth;
use Panel\Config;
use Panel\Db;
use Panel\Mailer;
use Panel\Migrator;
use Panel\View;

final class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('/');
        }
        // Şema kurulmamış veya hiç hesap yoksa giriş anlamsız — kuruluma yönlendir.
        if (Migrator::needsSetup()) {
            redirect('/kurulum');
        }
        View::render('auth/login', ['title' => 'Giriş'], 'auth_layout');
    }

    public function login(): void
    {
        $email    = mb_strtolower(post('email'));
        $password = $_POST['password'] ?? '';

        $result = Auth::attempt($email, is_string($password) ? $password : '');

        if (!$result['ok']) {
            remember_input(['email' => $email]);
            flash('error', $result['error']);
            redirect('/giris');
        }

        $intended = $_SESSION['_intended'] ?? null;
        unset($_SESSION['_intended']);

        flash('success', 'Hoş geldiniz, ' . $result['user']['full_name'] . '.');

        // Yalnız panel içi göreli yollara dönülür (açık yönlendirme açığı olmasın).
        if (is_string($intended) && PANEL_BASE !== '' && str_starts_with($intended, PANEL_BASE . '/')) {
            header('Location: ' . $intended, true, 302);
            exit;
        }
        redirect('/');
    }

    public function logout(): void
    {
        if (Auth::check()) {
            Audit::log('auth.logout', 'user', Auth::id());
        }
        Auth::logout();
        flash('info', 'Çıkış yapıldı.');
        redirect('/giris');
    }

    // ── Şifremi unuttum ─────────────────────────────────────────

    public function showForgot(): void
    {
        View::render('auth/forgot', ['title' => 'Şifremi Unuttum'], 'auth_layout');
    }

    public function forgot(): void
    {
        $email = mb_strtolower(post('email'));
        $user  = Db::one('SELECT id, full_name, email, status FROM users WHERE email = ? LIMIT 1', [$email]);

        // Hesabın var olup olmadığı ifşa edilmez: mesaj her durumda aynı.
        if ($user !== null && $user['status'] !== 'suspended') {
            $token = Auth::createToken((int) $user['id'], 'reset', 2);
            $link  = rtrim((string) Config::get('app.url'), '/') . '/sifre-belirle?token=' . $token;

            Mailer::send(
                (string) $user['email'],
                'Şifre sıfırlama — Mağusa Psikoloji Paneli',
                Mailer::template(
                    'Şifre sıfırlama',
                    "Merhaba {$user['full_name']},\n\nPanel şifrenizi sıfırlamak için aşağıdaki düğmeye tıklayın. Bağlantı 2 saat geçerlidir.",
                    'Yeni şifre belirle',
                    $link,
                    'Bu isteği siz yapmadıysanız bu e-postayı yok sayabilirsiniz; şifreniz değişmez.'
                )
            );
            Audit::log('auth.password_reset_requested', 'user', (int) $user['id']);
        }

        flash('info', 'Kayıtlı bir hesap varsa şifre sıfırlama bağlantısı e-posta adresine gönderildi.');
        redirect('/giris');
    }

    // ── Davet / şifre belirleme ─────────────────────────────────

    public function showReset(): void
    {
        $token  = query('token');
        $invite = Auth::consumableToken($token);

        if ($invite === null) {
            View::render('auth/reset', [
                'title'   => 'Bağlantı geçersiz',
                'invalid' => true,
                'token'   => '',
            ], 'auth_layout');
            return;
        }

        View::render('auth/reset', [
            'title'   => $invite['purpose'] === 'invite' ? 'Hesabınızı etkinleştirin' : 'Yeni şifre belirleyin',
            'invalid' => false,
            'token'   => $token,
            'invite'  => $invite,
        ], 'auth_layout');
    }

    public function reset(): void
    {
        $token  = post('token');
        $invite = Auth::consumableToken($token);

        if ($invite === null) {
            flash('error', 'Bağlantı geçersiz veya süresi dolmuş. Lütfen yeni bir bağlantı isteyin.');
            redirect('/sifremi-unuttum');
        }

        $password = (string) ($_POST['password'] ?? '');
        $confirm  = (string) ($_POST['password_confirm'] ?? '');

        if ($problem = Auth::passwordProblem($password, $confirm)) {
            flash('error', $problem);
            header('Location: ' . url('/sifre-belirle') . '?token=' . rawurlencode($token), true, 302);
            exit;
        }

        Auth::setPassword((int) $invite['user_id'], $password);
        Auth::consumeToken((int) $invite['id']);
        Audit::log('auth.password_set', 'user', (int) $invite['user_id'], ['purpose' => $invite['purpose']]);

        flash('success', 'Şifreniz belirlendi. Şimdi giriş yapabilirsiniz.');
        redirect('/giris');
    }
}
