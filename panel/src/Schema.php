<?php
declare(strict_types=1);

namespace Panel;

use PDOException;

/**
 * Şemada bir sütun var mı?
 *
 * Deploy ile migration arasında bir boşluk var: yeni sürüm sunucuya çıkar, ama
 * veritabanı güncellemesi Sistem ekranından elle uygulanana kadar yeni sütunlar
 * yoktur. Bu aralıkta yeni alana körü körüne yazan kod, ilgisiz ekranları da
 * çökertirdi. Bu yüzden yeni alanlar varlığı kontrol edilerek kullanılır ve
 * güncelleme uygulanmadan önce ekran sadece o alan olmadan çalışır.
 *
 * Tablo ve sütun adları yalnız kod içindeki sabitlerden gelir, kullanıcı
 * girdisinden değil.
 */
final class Schema
{
    /** @var array<string,bool> İstek başına önbellek — her çağrıda SHOW COLUMNS atılmasın. */
    private static array $cache = [];

    public static function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        try {
            $found = Db::one("SHOW COLUMNS FROM `{$table}` LIKE ?", [$column]) !== null;
        } catch (PDOException) {
            $found = false;
        }

        return self::$cache[$key] = $found;
    }

    /** Ödeme takibi (002_payments) uygulanmış mı? */
    public static function paymentsReady(): bool
    {
        return self::hasColumn('appointments', 'fee');
    }

    /** Hatırlatma e-postaları (003_reminders) uygulanmış mı? */
    public static function remindersReady(): bool
    {
        return self::hasColumn('appointments', 'reminder_sent_at');
    }

    /** Danışan dosyası (004_case_files) uygulanmış mı? */
    public static function caseFilesReady(): bool
    {
        return self::hasTable('case_files');
    }

    public static function hasTable(string $table): bool
    {
        $key = 'table:' . $table;
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        try {
            $found = Db::one('SHOW TABLES LIKE ?', [$table]) !== null;
        } catch (PDOException) {
            $found = false;
        }

        return self::$cache[$key] = $found;
    }
}
