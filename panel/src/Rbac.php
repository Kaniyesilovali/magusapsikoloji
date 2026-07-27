<?php
declare(strict_types=1);

namespace Panel;

/**
 * Rol/yetki matrisi — yetki kararlarının TEK kaynağı.
 *
 * Tasarım kuralı: seans notları özel nitelikli sağlık verisidir; okuma yetkisi
 * yalnızca notu yazan terapiste aittir. super_admin bilinçli olarak note.*
 * yetkilerinin dışında bırakılmıştır — "her şeyi yönetir" kuralının tek istisnası.
 */
final class Rbac
{
    public const SUPER_ADMIN = 'super_admin';
    public const ADMIN       = 'admin';
    public const THERAPIST   = 'therapist';
    public const CLIENT      = 'client';

    public const ROLE_LABELS = [
        self::SUPER_ADMIN => 'Süper Admin',
        self::ADMIN       => 'Admin',
        self::THERAPIST   => 'Terapist',
        self::CLIENT      => 'Görüşmeci',
    ];

    private const MATRIX = [
        self::SUPER_ADMIN => [
            'dashboard.view',
            'user.view', 'user.create', 'user.update', 'user.delete', 'user.manage_admins',
            'client.view.all', 'client.create', 'client.update', 'client.delete',
            'appointment.view.all', 'appointment.create', 'appointment.update', 'appointment.cancel',
            'availability.manage.all',
            'payment.view.all', 'payment.manage',
            'audit.view', 'settings.manage', 'consent.manage', 'content.manage',
            'profile.self',
        ],
        self::ADMIN => [
            'dashboard.view',
            'user.view', 'user.create', 'user.update', 'user.delete',
            'client.view.all', 'client.create', 'client.update',
            'appointment.view.all', 'appointment.create', 'appointment.update', 'appointment.cancel',
            'availability.manage.all',
            'payment.view.all', 'payment.manage',
            // KVKK metni işletme belgesidir, güvenlik ayarı değil: admin de düzenleyebilir.
            'consent.manage', 'content.manage',
            'profile.self',
        ],
        self::THERAPIST => [
            'dashboard.view',
            'client.view.own', 'client.update',
            'appointment.view.own', 'appointment.create', 'appointment.update', 'appointment.cancel',
            'availability.manage.own',
            // Terapist kendi seanslarının ücret/ödeme durumunu görür ve ücreti
            // girebilir; tahsilat kaydı (payment.manage) merkez yönetimindedir.
            'payment.view.own',
            'note.write', 'note.read.own',
            'profile.self',
        ],
        self::CLIENT => [
            'dashboard.view',
            'appointment.view.own',
            // Görüşmeci kendi seanslarının ücretini ve kendisi adına girilmiş
            // tahsilatları görür — kendi borcunu sormak için merkezi aramak
            // zorunda kalmasın diye. Yazma yetkisi yok: ücreti de tahsilatı da
            // merkez girer.
            'payment.view.own',
            'profile.self',
        ],
    ];

    public static function roles(): array
    {
        return array_keys(self::MATRIX);
    }

    public static function label(?string $role): string
    {
        return self::ROLE_LABELS[$role] ?? '—';
    }

    public static function can(?array $user, string $permission): bool
    {
        if ($user === null || ($user['status'] ?? '') !== 'active') {
            return false;
        }
        return in_array($permission, self::MATRIX[$user['role']] ?? [], true);
    }

    /** Verilen yetkilerden herhangi biri yeterli — "hepsi" ya da "yalnız kendi" ayrımı olan ekranlar için. */
    public static function canAny(?array $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (self::can($user, $permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Bir kullanıcının hedef kullanıcı üzerinde işlem yapıp yapamayacağı.
     * Kurallar:
     *  - Kimse kendi hesabını silemez / rolünü değiştiremez.
     *  - Yalnızca süper admin, süper adminleri yönetebilir ve süper admin atayabilir.
     *  - Admin; terapist ve görüşmeci hesaplarını yönetir, admin hesaplarına dokunamaz.
     */
    public static function canManageUser(array $actor, array $target): bool
    {
        if (!self::can($actor, 'user.update')) {
            return false;
        }
        if ($actor['role'] === self::SUPER_ADMIN) {
            return true;
        }
        if ($actor['role'] === self::ADMIN) {
            return in_array($target['role'], [self::THERAPIST, self::CLIENT], true);
        }
        return false;
    }

    /** Aktörün atayabileceği roller — form seçeneklerini ve POST doğrulamasını besler. */
    public static function assignableRoles(array $actor): array
    {
        return match ($actor['role']) {
            self::SUPER_ADMIN => [self::SUPER_ADMIN, self::ADMIN, self::THERAPIST, self::CLIENT],
            self::ADMIN       => [self::THERAPIST, self::CLIENT],
            default           => [],
        };
    }
}
