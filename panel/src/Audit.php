<?php
declare(strict_types=1);

namespace Panel;

/**
 * Erişim ve değişiklik kaydı. KVKK gereği "kim, hangi kaydı, ne zaman açtı/değiştirdi"
 * sorusunun cevabı buradan verilir; bu yüzden hassas kayıt görüntülemeleri de loglanır.
 */
final class Audit
{
    public const LABELS = [
        'auth.login'          => 'Giriş yapıldı',
        'auth.login_failed'   => 'Başarısız giriş denemesi',
        'auth.locked'         => 'Hesap kilitlendi',
        'auth.logout'         => 'Çıkış yapıldı',
        'access.denied'       => 'Yetkisiz erişim denemesi',
        'auth.password_reset_requested' => 'Şifre sıfırlama istendi',
        'auth.password_set'   => 'Şifre belirlendi',
        'auth.password_changed' => 'Şifre değiştirildi',
        'user.created'        => 'Kullanıcı oluşturuldu',
        'user.updated'        => 'Kullanıcı güncellendi',
        'user.deleted'        => 'Kullanıcı silindi',
        'user.invite_resent'  => 'Davet yeniden gönderildi',
        'profile.updated'     => 'Profil güncellendi',
        'client.viewed'       => 'Danışan kaydı görüntülendi',
        'note.read'           => 'Seans notu okundu',
        'note.written'        => 'Seans notu yazıldı',
    ];

    public static function log(string $action, ?string $entityType = null, ?int $entityId = null, array $meta = []): void
    {
        try {
            Db::run(
                'INSERT INTO audit_log (actor_user_id, action, entity_type, entity_id, ip, user_agent, meta, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
                [
                    Auth::id(),
                    $action,
                    $entityType,
                    $entityId,
                    client_ip(),
                    mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                    $meta === [] ? null : json_encode($meta, JSON_UNESCAPED_UNICODE),
                ]
            );
        } catch (\Throwable $e) {
            // Loglama, asıl işlemi asla düşürmemeli.
            error_log('[panel] audit yazılamadı: ' . $e->getMessage());
        }
    }

    public static function label(string $action): string
    {
        return self::LABELS[$action] ?? $action;
    }
}
