<?php
declare(strict_types=1);

namespace Panel;

use PDOException;

/**
 * Bireyin panel hesabı — `clients` kaydı ile `users` kaydı arasındaki bağ.
 *
 * Kural: birey kaydı açılınca hesabı da açılır. Ön büro "hesap açayım mı"
 * diye karar vermez, iki ekran arasında gidip gelmez; e-posta girilmişse
 * hesap kendiliğinden oluşur ve davet gider. E-posta yoksa kayıt yine de
 * açılır (telefonla gelen birey kaybolmasın) ve hesap sonradan tek düğmeyle
 * açılabilir.
 *
 * Bağ tek yönlüdür ve `clients.user_id` üzerinde benzersizdir: bir panel hesabı
 * yalnız bir birey kaydını açar. Bu sınıf o bağı kuran/koparan tek yerdir.
 */
final class ClientAccount
{
    /** Hesap açılamadı: kayıtta e-posta yok. */
    public const NO_EMAIL = 'no_email';
    /** Hesap açılamadı: e-posta başka bir hesapta kayıtlı. */
    public const TAKEN = 'taken';
    /** Kayda zaten bir hesap bağlı. */
    public const EXISTS = 'exists';
    /** Yeni hesap açıldı, davet gönderildi. */
    public const CREATED = 'created';

    /**
     * Birey kaydı için hesabı açar ve davet gönderir.
     *
     * Davet bağlantısı e-posta gitse de gitmese de saklanır (bkz. Invites::share)
     * — yönetici gerekirse başka kanaldan iletir.
     *
     * @param  array $client  en az id, full_name, email, user_id taşıyan satır
     * @return array{status:string, user_id:?int}
     */
    public static function provision(array $client, array $actor): array
    {
        if ($client['user_id'] !== null) {
            return ['status' => self::EXISTS, 'user_id' => (int) $client['user_id']];
        }

        $email = trim((string) ($client['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['status' => self::NO_EMAIL, 'user_id' => null];
        }

        $name      = (string) $client['full_name'];
        $clientId  = (int) $client['id'];
        $existing  = Db::one('SELECT id, role FROM users WHERE email = ? LIMIT 1', [$email]);

        if ($existing !== null) {
            // Aynı adres bir terapist ya da yöneticiye aitse hesap devralınmaz:
            // o kişinin rolünü düşürmek, kendi ekranlarına erişimini keserdi.
            if ($existing['role'] !== Rbac::CLIENT) {
                return ['status' => self::TAKEN, 'user_id' => null];
            }
            // Birey rolünde ama başka bir kayda bağlıysa da devralınmaz;
            // iki birey tek hesabı paylaşamaz.
            if (Db::value('SELECT id FROM clients WHERE user_id = ?', [$existing['id']]) !== null) {
                return ['status' => self::TAKEN, 'user_id' => null];
            }
            $userId = (int) $existing['id'];
        } else {
            // Şifre davet bağlantısıyla belirlenir; buraya asla oturum
            // açılamayacak rastgele bir hash konur.
            $userId = Db::insert(
                'INSERT INTO users (email, password_hash, full_name, phone, role, status, created_by, created_at)
                 VALUES (?, ?, ?, ?, ?, \'invited\', ?, NOW())',
                [
                    $email,
                    password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT),
                    $name,
                    ($client['phone'] ?? '') !== '' ? $client['phone'] : null,
                    Rbac::CLIENT,
                    $actor['id'],
                ]
            );
            Audit::log('user.created', 'user', $userId, ['role' => Rbac::CLIENT, 'email' => $email, 'via' => 'birey kaydı']);
        }

        Db::run('UPDATE clients SET user_id = ?, updated_at = NOW() WHERE id = ?', [$userId, $clientId]);
        Audit::log('client.account_linked', 'client', $clientId, ['user_id' => $userId]);

        Invites::share($name, $email, Invites::sendToClient($userId, $name, $email), 'client');

        return ['status' => self::CREATED, 'user_id' => $userId];
    }

    /** Yeniden davet — şifre belirlenmeden bağlantının süresi dolduğunda. */
    public static function reinvite(array $client): void
    {
        $userId = (int) $client['user_id'];
        $name   = (string) $client['full_name'];
        $email  = (string) $client['account_email'];

        Invites::share($name, $email, Invites::sendToClient($userId, $name, $email), 'client');
        Audit::log('client.account_reinvited', 'client', (int) $client['id'], ['user_id' => $userId]);
    }

    /**
     * Erişimi kapatır/açar. Hesap silinmez, `suspended` olur: birey geri
     * geldiğinde aynı hesap yeniden açılır, geçmiş davet zinciri korunur.
     *
     * Şifresini hiç belirlememiş (`invited`) hesap açılırken `active` yapılmaz —
     * o hesabın yolu hâlâ davet bağlantısından geçer.
     */
    public static function setSuspended(array $client, bool $suspended): void
    {
        $userId = (int) $client['user_id'];
        $status = (string) $client['account_status'];

        if ($suspended) {
            if ($status === 'suspended') {
                return;
            }
            Db::run('UPDATE users SET status = \'suspended\', updated_at = NOW() WHERE id = ?', [$userId]);
        } else {
            if ($status !== 'suspended') {
                return;
            }
            // Şifresi hiç belirlenmemiş hesap davetli durumuna döner.
            $everSet = Db::value('SELECT last_login_at FROM users WHERE id = ?', [$userId]);
            Db::run(
                'UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?',
                [$everSet !== null ? 'active' : 'invited', $userId]
            );
        }

        Audit::log('client.account_' . ($suspended ? 'suspended' : 'restored'), 'client', (int) $client['id'], ['user_id' => $userId]);
    }

    /**
     * Bireyin adı/e-postası düzeltildiğinde hesabı da takip eder. Aksi hâlde
     * davet eski adrese gider ve panelde iki farklı isim görünürdü.
     *
     * E-posta başkasına aitse sessizce atlanır: kayıt düzenlemesi, hesabın
     * çakışması yüzünden bloke edilmemeli.
     */
    public static function sync(int $userId, string $name, ?string $email): void
    {
        if ($email !== null && Db::value('SELECT id FROM users WHERE email = ? AND id <> ?', [$email, $userId]) !== null) {
            $email = null;
        }

        if ($email !== null) {
            Db::run('UPDATE users SET full_name = ?, email = ?, updated_at = NOW() WHERE id = ?', [$name, $email, $userId]);
            return;
        }
        Db::run('UPDATE users SET full_name = ?, updated_at = NOW() WHERE id = ?', [$name, $userId]);
    }

    /**
     * Birey kaydı kalıcı silinirken hesabı da gider — KVKK silme talebi
     * "kayıt silindi ama giriş hesabı duruyor" ile karşılanmış olmaz.
     * Silinemezse (beklenmedik bağlı kayıt) hesap en azından kapatılır.
     */
    public static function purge(?int $userId): void
    {
        if ($userId === null) {
            return;
        }
        try {
            Db::run('DELETE FROM users WHERE id = ? AND role = ?', [$userId, Rbac::CLIENT]);
        } catch (PDOException) {
            Db::run('UPDATE users SET status = \'suspended\', updated_at = NOW() WHERE id = ?', [$userId]);
        }
    }

    /** Hesap açılamadığında ön büroya ne olduğunu söyleyen mesaj. */
    public static function explain(string $status): ?string
    {
        return match ($status) {
            self::NO_EMAIL => 'Kayıtta e-posta olmadığı için panel hesabı açılmadı. E-posta ekledikten sonra birey sayfasından açabilirsiniz.',
            self::TAKEN    => 'Bu e-posta adresi başka bir hesapta kayıtlı olduğu için panel hesabı açılmadı.',
            default        => null,
        };
    }
}
