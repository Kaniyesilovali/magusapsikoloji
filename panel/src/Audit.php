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
        'client.viewed'       => 'Görüşmeci kaydı görüntülendi',
        'client.created'      => 'Görüşmeci kaydı oluşturuldu',
        'client.updated'      => 'Görüşmeci kaydı güncellendi',
        'client.archived'     => 'Görüşmeci arşivlendi/geri alındı',
        'client.deleted'      => 'Görüşmeci kaydı kalıcı olarak silindi',
        'client.account_linked'     => 'Görüşmeciye panel hesabı açıldı',
        'client.account_reinvited'  => 'Görüşmeci daveti yeniden gönderildi',
        'client.account_suspended'  => 'Görüşmecinin panel erişimi kapatıldı',
        'client.account_restored'   => 'Görüşmecinin panel erişimi açıldı',
        'appointment.created' => 'Randevu oluşturuldu',
        'appointment.updated' => 'Randevu güncellendi',
        'appointment.status_changed' => 'Randevu durumu değişti',
        'appointment.cancelled' => 'Randevu iptal edildi',
        'availability.updated' => 'Çalışma saatleri değişti',
        'availability.time_off' => 'İzin kaydı değişti',
        'consent.updated'     => 'KVKK metni güncellendi',
        'content.updated'     => 'Site içeriği güncellendi',
        'payment.fee_set'     => 'Seans ücreti belirlendi',
        'payment.recorded'    => 'Tahsilat kaydedildi',
        'payment.deleted'     => 'Tahsilat kaydı silindi',
        'system.migrated'     => 'Veritabanı güncellemesi uygulandı',
        'note.read'           => 'Seans notu okundu',
        'note.written'        => 'Seans notu yazıldı',
        'case.read'           => 'Görüşmeci dosyası okundu',
        'case.written'        => 'Görüşmeci dosyası yazıldı',
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
