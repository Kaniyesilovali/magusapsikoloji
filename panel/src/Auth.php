<?php
declare(strict_types=1);

namespace Panel;

/**
 * Kimlik doğrulama: giriş, kilitleme, oturum, davet/şifre sıfırlama jetonları.
 *
 * Kullanıcı numaralandırmasını önlemek için hatalı e-posta ile hatalı şifre
 * aynı mesajı döndürür; kilit durumu da ayrı ayrı ifşa edilmez.
 */
final class Auth
{
    private const SESSION_KEY = 'user_id';
    private static ?array $cachedUser = null;

    // ── Oturum durumu ────────────────────────────────────────────

    public static function user(): ?array
    {
        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }
        $id = $_SESSION[self::SESSION_KEY] ?? null;
        if (!$id) {
            return null;
        }
        $user = Db::one('SELECT * FROM users WHERE id = ? LIMIT 1', [$id]);

        // Oturum açıkken hesap askıya alınmış/silinmişse oturumu düşür.
        if ($user === null || $user['status'] !== 'active') {
            self::logout();
            return null;
        }
        return self::$cachedUser = $user;
    }

    public static function id(): ?int
    {
        $id = $_SESSION[self::SESSION_KEY] ?? null;
        return $id ? (int) $id : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function requireLogin(): array
    {
        $user = self::user();
        if ($user === null) {
            $_SESSION['_intended'] = $_SERVER['REQUEST_URI'] ?? null;
            flash('warning', 'Devam etmek için giriş yapın.');
            redirect('/giris');
        }
        return $user;
    }

    public static function requirePermission(string $permission): array
    {
        $user = self::requireLogin();
        if (!Rbac::can($user, $permission)) {
            Audit::log('access.denied', 'permission', null, ['permission' => $permission]);
            View::error(403, 'Yetkiniz yok', 'Bu sayfayı görüntüleme yetkiniz bulunmuyor.');
            exit;
        }
        return $user;
    }

    // ── Giriş / çıkış ────────────────────────────────────────────

    /** @return array{ok:bool,error?:string,user?:array} */
    public static function attempt(string $email, string $password): array
    {
        $generic = ['ok' => false, 'error' => 'E-posta veya şifre hatalı.'];

        $user = Db::one('SELECT * FROM users WHERE email = ? LIMIT 1', [mb_strtolower($email)]);
        if ($user === null) {
            // Hesabın var olup olmadığı yanıt süresinden anlaşılmasın diye
            // gerçek bir doğrulamayla eşdeğer maliyette hash hesaplanır.
            password_hash($password, PASSWORD_DEFAULT);
            Audit::log('auth.login_failed', 'email', null, ['email' => $email, 'reason' => 'bilinmeyen hesap']);
            return $generic;
        }

        if ($user['locked_until'] !== null && strtotime($user['locked_until']) > time()) {
            Audit::log('auth.login_failed', 'user', (int) $user['id'], ['reason' => 'kilitli']);
            $minutes = (int) ceil((strtotime($user['locked_until']) - time()) / 60);
            return ['ok' => false, 'error' => "Çok fazla hatalı deneme yapıldı. Hesap {$minutes} dakika süreyle kilitli."];
        }

        if (!password_verify($password, (string) $user['password_hash'])) {
            self::registerFailure($user);
            return $generic;
        }

        if ($user['status'] === 'invited') {
            return ['ok' => false, 'error' => 'Hesabınız henüz etkinleştirilmemiş. E-postanızdaki şifre belirleme bağlantısını kullanın.'];
        }
        if ($user['status'] !== 'active') {
            Audit::log('auth.login_failed', 'user', (int) $user['id'], ['reason' => 'pasif hesap']);
            return ['ok' => false, 'error' => 'Hesabınız devre dışı bırakılmış. Yöneticinizle görüşün.'];
        }

        // Hash algoritması/maliyeti güncellendiyse sessizce yenile.
        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            Db::run('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        }

        self::startSession($user);
        return ['ok' => true, 'user' => $user];
    }

    private static function registerFailure(array $user): void
    {
        $max      = (int) Config::get('security.max_login_attempts', 5);
        $minutes  = (int) Config::get('security.lockout_minutes', 15);
        $attempts = (int) $user['failed_attempts'] + 1;

        if ($attempts >= $max) {
            Db::run(
                'UPDATE users SET failed_attempts = 0, locked_until = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ?',
                [$minutes, $user['id']]
            );
            Audit::log('auth.locked', 'user', (int) $user['id'], ['minutes' => $minutes]);
            return;
        }

        Db::run('UPDATE users SET failed_attempts = ? WHERE id = ?', [$attempts, $user['id']]);
        Audit::log('auth.login_failed', 'user', (int) $user['id'], ['attempt' => $attempts]);
    }

    private static function startSession(array $user): void
    {
        // Oturum sabitleme (session fixation) saldırısına karşı kimlik yenilenir.
        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = (int) $user['id'];
        $_SESSION['last_activity']   = time();
        self::$cachedUser = $user;

        Db::run(
            'UPDATE users SET last_login_at = NOW(), failed_attempts = 0, locked_until = NULL WHERE id = ?',
            [$user['id']]
        );
        Audit::log('auth.login', 'user', (int) $user['id']);
    }

    public static function logout(): void
    {
        self::$cachedUser = null;
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
            session_start();
        }
    }

    // ── Davet / şifre sıfırlama jetonları ────────────────────────

    /**
     * Tek kullanımlık jeton üretir ve hash'ini saklar (düz jeton veritabanında tutulmaz).
     * @return string E-posta bağlantısına konacak düz jeton.
     */
    public static function createToken(int $userId, string $purpose, int $hours = 48): string
    {
        $token = bin2hex(random_bytes(32));
        Db::run('DELETE FROM invites WHERE user_id = ? AND purpose = ? AND used_at IS NULL', [$userId, $purpose]);
        Db::run(
            'INSERT INTO invites (user_id, purpose, token_hash, expires_at, created_at)
             VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? HOUR), NOW())',
            [$userId, $purpose, hash('sha256', $token), $hours]
        );
        return $token;
    }

    /** Jetonu doğrular; geçerliyse kullanıcıyı döndürür. */
    public static function consumableToken(string $token): ?array
    {
        if ($token === '') {
            return null;
        }
        return Db::one(
            'SELECT i.*, u.email, u.full_name, u.status
               FROM invites i
               JOIN users u ON u.id = i.user_id
              WHERE i.token_hash = ? AND i.used_at IS NULL AND i.expires_at > NOW()
              LIMIT 1',
            [hash('sha256', $token)]
        );
    }

    public static function consumeToken(int $inviteId): void
    {
        Db::run('UPDATE invites SET used_at = NOW() WHERE id = ?', [$inviteId]);
    }

    // ── Şifre kuralları ──────────────────────────────────────────

    public static function passwordProblem(string $password, string $confirm): ?string
    {
        $min = (int) Config::get('security.password_min', 10);
        if (mb_strlen($password) < $min) {
            return "Şifre en az {$min} karakter olmalı.";
        }
        if ($password !== $confirm) {
            return 'Şifreler eşleşmiyor.';
        }
        if (preg_match('/^\d+$/', $password)) {
            return 'Şifre yalnızca rakamlardan oluşamaz.';
        }
        return null;
    }

    public static function setPassword(int $userId, string $password): void
    {
        Db::run(
            'UPDATE users
                SET password_hash = ?, must_change_password = 0, status = IF(status = \'invited\', \'active\', status),
                    failed_attempts = 0, locked_until = NULL, updated_at = NOW()
              WHERE id = ?',
            [password_hash($password, PASSWORD_DEFAULT), $userId]
        );
    }
}
