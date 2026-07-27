<?php
declare(strict_types=1);

namespace Panel;

/**
 * Şemada bir sütun var mı?
 *
 * Deploy ile migration arasında bir boşluk var: yeni sürüm sunucuya çıkar, ama
 * veritabanı güncellemesi Sistem ekranından elle uygulanana kadar yeni sütunlar
 * yoktur. Bu aralıkta yeni alana körü körüne yazan kod, ilgisiz ekranları da
 * çökertirdi. Bu yüzden yeni alanlar varlığı kontrol edilerek kullanılır ve
 * güncelleme uygulanmadan önce ekran sadece o alan olmadan çalışır.
 *
 * Varlık kontrolü information_schema'dan yapılır, SHOW ile değil. SHOW ifadeleri
 * MySQL'in sunucu tarafı hazırlanmış ifade protokolünde yer tutucu kabul etmiyor;
 * Db bağlantısı ATTR_EMULATE_PREPARES=false ile açıldığı için `SHOW COLUMNS … LIKE ?`
 * her çağrıda hata atıyordu ve hata yutulduğu için sonuç daima "sütun yok" oluyordu.
 * Ödemeler ve görüşmeci dosyası ekranları, migration uygulanmış olmasına rağmen
 * bu yüzden 503 veriyordu. information_schema düz bir SELECT'tir; olmayan tablo
 * ya da sütun hata değil, sıfır satır döndürür — bu yüzden try/catch de gerekmez.
 *
 * Tablo ve sütun adları yalnız kod içindeki sabitlerden gelir, kullanıcı
 * girdisinden değil.
 */
final class Schema
{
    /** @var array<string,bool> İstek başına önbellek — her çağrıda information_schema'ya gidilmesin. */
    private static array $cache = [];

    public static function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $found = (int) Db::value(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column]
        ) > 0;

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

    /** Görüşmeci dosyası (004_case_files) uygulanmış mı? */
    public static function caseFilesReady(): bool
    {
        return self::hasTable('case_files');
    }

    /** Seanslar arası check-in (005_checkins) uygulanmış mı? */
    public static function checkinsReady(): bool
    {
        return self::hasTable('checkins') && self::hasTable('checkin_requests');
    }

    public static function hasTable(string $table): bool
    {
        $key = 'table:' . $table;
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $found = (int) Db::value(
            'SELECT COUNT(*) FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$table]
        ) > 0;

        return self::$cache[$key] = $found;
    }
}
