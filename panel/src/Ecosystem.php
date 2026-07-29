<?php
declare(strict_types=1);

namespace Panel;

/**
 * "Haftanın hâli" — check-in formunun ikinci sayfasındaki alan sözlüğü.
 *
 * Bu sınıf henüz veritabanına dokunmuyor; yalnız **ne sorulacağını** tanımlıyor.
 * Amacı, aynı metnin üç ayrı yerde (ebeveyn formu, terapist şeridi, plan belgesi)
 * ayrı ayrı yazılmasını engellemek. Alan adı değişirse tek yerde değişir.
 *
 * Ebeveyn yüzünde "ekosistem", "mikrosistem", "Bronfenbrenner" kelimeleri
 * geçmez — bunlar panel içi teknik adlar (bkz. ekosistem-plani.md §1).
 *
 * Ölçek üç hâlli ve bilinçli olarak öyle: beş kademeli bir şiddet ölçeği
 * doldurma süresini uzatır, ayırt ediciliği artırmaz (§18).
 */
final class Ecosystem
{
    /** Rüzgâr yönü. Sakin varsayılandır: dokunulmayan alan eksik veri değil. */
    public const HEADWIND = -1;   // karşı rüzgâr — zorladı
    public const CALM     =  0;   // sakin — bu hafta öne çıkmadı
    public const TAILWIND =  1;   // sırt rüzgârı — iyi geldi

    /**
     * Bir görüşmecide aynı anda açık olabilecek en fazla alan sayısı.
     *
     * Kod seviyesinde sınırlanır, terapistin insafına bırakılmaz: 18 alanın
     * hepsi açıldığında form 90 saniyeden dört dakikaya çıkıyor ve doldurma
     * oranı — bu döngünün ölçtüğü tek şey — çöküyor.
     */
    public const MAX_OPEN = 11;

    /** Ebeveyne sorulan tek soru. Form başlığı da, e-posta dili de bundan türer. */
    public const PROMPT = 'Bu hafta çocuğunun sırtını hangileri itti, hangileri karşıdan esti?';

    /** Üç hâlin ebeveyne görünen adları. Kırmızı yok: zorlayan alan kehribar. */
    public const VALENCE_LABELS = [
        self::TAILWIND => 'iyi geldi',
        self::CALM     => 'öne çıkmadı',
        self::HEADWIND => 'zorladı',
    ];

    /**
     * Alan sözlüğü.
     *
     * `hint` alanın ne kapsadığını söyler ve dokunuşta/altında görünür. Kısa
     * tutulmasının sebebi klinik değil davranışsal: ebeveyn açıklama okumaz,
     * göz ucuyla tarar.
     *
     * `short` yalnız halka düzeni için: 72 piksellik bir çipe "Ekran ve dijital
     * dünya" sığmıyor. Liste düzeninde ve terapist şeridinde tam ad kullanılır;
     * kısa ad hiçbir yerde tek başına anlam taşımak zorunda değil, çünkü halkada
     * her çipin altında zaten çocuğun adı ve bağlam duruyor.
     *
     * `core` olanlar her görüşmecide açık başlar. Diğerleri terapistin
     * görüşmeci sayfasından açtıklarıdır — hepsi kapalı doğar, çünkü "kardeş"
     * alanı tek çocuklu bir ailede her hafta boş kalırsa form kendini
     * gereksiz gösterir.
     *
     * @var array<string,array{label:string,short:string,hint:string,core:bool}>
     */
    public const DOMAINS = [
        // ── Çekirdek 8 ─────────────────────────────────────────
        'okul' => [
            'label' => 'Okul',
            'short' => 'Okul',
            'hint'  => 'ders yükü, öğretmen ilişkisi, sınav dönemi',
            'core'  => true,
        ],
        'arkadas' => [
            'label' => 'Arkadaşlar',
            'short' => 'Arkadaş',
            'hint'  => 'akran ilişkileri, dışlanma, yeni arkadaşlık',
            'core'  => true,
        ],
        // "Ev" bilinçli olarak evin iklimini sorar, ebeveynliği değil. "Ebeveyn
        // tutumun nasıldı?" sorusu formu her hafta bir öz-değerlendirmeye
        // çevirir ve üçüncü haftada doldurulmayı bırakır (§10).
        'ev' => [
            'label' => 'Ev',
            'short' => 'Ev',
            'hint'  => 'evin genel havası, gerginlik ya da huzur',
            'core'  => true,
        ],
        // Kıbrıs'ta büyükanne/dede günlük bakımın çekirdeğinde; ayrı alan
        // olmasının sebebi kültürel, klinik bir ekleme değil (§17).
        'buyukler' => [
            'label' => 'Büyükler',
            'short' => 'Büyükler',
            'hint'  => 'büyükanne, dede, geniş aile',
            'core'  => true,
        ],
        // Sayısal uyku puanından ayrı: o "nasıl uyudu", bu "düzen tuttu mu".
        'uyku' => [
            'label' => 'Uyku düzeni',
            'short' => 'Uyku',
            'hint'  => 'yatma saati, rutin, gece bölünmeleri',
            'core'  => true,
        ],
        'beden' => [
            'label' => 'Beden',
            'short' => 'Beden',
            'hint'  => 'hastalık, ağrı, iştah, ilaç',
            'core'  => true,
        ],
        'hareket' => [
            'label' => 'Hareket ve oyun',
            'short' => 'Hareket',
            'hint'  => 'spor, dışarıda geçen zaman, serbest oyun',
            'core'  => true,
        ],
        // Süre sorulmuyor. Dakika ölçümü doğru hatırlanmaz ve suçluluk üretir;
        // sorulan tek şey o haftanın ekranı iyi mi geldi, zorladı mı (§5).
        'ekran' => [
            'label' => 'Ekran ve dijital dünya',
            'short' => 'Ekran',
            'hint'  => 'telefon, oyun, sosyal medya — süre değil, etkisi',
            'core'  => true,
        ],

        // ── Koşullu (terapist açar; ilk sürümde kapalı) ────────
        'kardes' => [
            'label' => 'Kardeş',
            'short' => 'Kardeş',
            'hint'  => 'kardeş ilişkisi, rekabet, yakınlık',
            'core'  => false,
        ],
        'bakim_duzeni' => [
            'label' => 'Bakım düzeni',
            'short' => 'Bakım',
            'hint'  => 'iki ev, nöbetleşe bakım, geçişler',
            'core'  => false,
        ],
        'dil' => [
            'label' => 'Dil ve uyum',
            'short' => 'Dil',
            'hint'  => 'yeni dil, okulda anlaşılma, yerleşme',
            'core'  => false,
        ],
        'topluluk' => [
            'label' => 'Mahalle ve topluluk',
            'short' => 'Mahalle',
            'hint'  => 'komşuluk, dernek, kulüp',
            'core'  => false,
        ],
        // Varsayılan kapalı ve öyle kalmalı: aile kendisi istemedikçe inanç
        // sorulmaz. Açılması terapistin değil ailenin kararıdır.
        'inanc' => [
            'label' => 'İnanç ve gelenek',
            'short' => 'İnanç',
            'hint'  => 'ailenin kendi istediği durumda açılır',
            'core'  => false,
        ],
        'ekonomi' => [
            'label' => 'Ev ekonomisi',
            'short' => 'Ekonomi',
            'hint'  => 'geçim kaygısı, belirsizlik — ergen/yetişkin dosyalarında',
            'core'  => false,
        ],
    ];

    /**
     * Okul öncesi yaş bandında alanın adı değişir, alanın kendisi değil.
     *
     * Plan §8 bu yaş için ayrı bir alan seti öneriyordu; ayrı bir `kres` alanı
     * açmak yerine etiketi değiştiriyoruz. Sebep §18'in kendi kuralı: "tek kod
     * yolu, farklı liste". İki ayrı anahtar olsaydı şeritte aynı çocuğun
     * geçmişi, okula başladığı hafta ikiye bölünürdü.
     */
    public const PRESCHOOL_LABELS = [
        'okul' => 'Kreş / anaokulu',
    ];

    /** Okul öncesi sınırı. Akran verisi bu yaşta zayıf olduğu için arkadaş alanı kapalı başlar. */
    private const PRESCHOOL_MAX_AGE = 6;

    /**
     * Bir görüşmeci için açık başlayacak alanlar.
     *
     * Yaş bilinmiyorsa çekirdek 8 verilir — tahmin etmek yerine varsayılana
     * dönmek, yanlış alan setiyle sekiz hafta veri toplamaktan iyidir.
     *
     * @return list<string>
     */
    public static function defaultsFor(?int $age = null): array
    {
        $core = array_keys(array_filter(self::DOMAINS, static fn (array $d): bool => $d['core']));

        if ($age !== null && $age <= self::PRESCHOOL_MAX_AGE) {
            return array_values(array_diff($core, ['arkadas']));
        }

        return $core;
    }

    /** Ebeveyne gösterilecek alan adı — yaş bandına göre. */
    public static function label(string $key, ?int $age = null): string
    {
        if ($age !== null && $age <= self::PRESCHOOL_MAX_AGE && isset(self::PRESCHOOL_LABELS[$key])) {
            return self::PRESCHOOL_LABELS[$key];
        }

        return self::DOMAINS[$key]['label'] ?? $key;
    }

    /** Geçerli bir alan anahtarı mı? Form gönderimi bunu tek tek doğrular. */
    public static function known(string $key): bool
    {
        return isset(self::DOMAINS[$key]);
    }

    /** −1 / 0 / +1 dışındaki her değer "sakin" sayılır; form asla hata vermez. */
    public static function valence(mixed $value): int
    {
        $int = (int) $value;
        return in_array($int, [self::HEADWIND, self::CALM, self::TAILWIND], true) ? $int : self::CALM;
    }

    // ── Görüşmeci başına açık alanlar ───────────────────────────

    /**
     * Doğum tarihinden yaş. Yoksa null — tahmin edilmez.
     */
    public static function ageFrom(?string $birthDate): ?int
    {
        if ($birthDate === null || $birthDate === '') {
            return null;
        }
        try {
            $born = new \DateTimeImmutable($birthDate);
        } catch (\Exception) {
            return null;
        }

        return (int) $born->diff(new \DateTimeImmutable())->y;
    }

    /**
     * Bir görüşmecide formda gösterilecek alanlar, sırasıyla.
     *
     * Varsayılan set ile terapistin açtığı/kapattığı satırlar burada birleşir.
     * `ecosystem_domains` yalnız sapmaları tuttuğu için varsayılan tarafı kodda
     * kalıyor ve yeni bir çekirdek alan eklendiğinde mevcut görüşmecilerde
     * kendiliğinden açılıyor — her kayda satır yazmak gerekmiyor.
     *
     * MAX_OPEN burada uygulanır, kaydetme yolunda değil: veritabanında fazladan
     * satır bulunsa bile ebeveyn hiçbir zaman on birden fazla alan görmez.
     *
     * @return list<array{key:string,label:string,short:string,hint:string}>
     */
    public static function openFor(int $clientId, ?int $age = null): array
    {
        $open = array_fill_keys(self::defaultsFor($age), 0);

        if (Schema::ecosystemReady()) {
            $rows = Db::all(
                'SELECT domain_key, enabled, sort FROM ecosystem_domains WHERE client_id = ? ORDER BY sort, id',
                [$clientId]
            );
            foreach ($rows as $row) {
                $key = (string) $row['domain_key'];
                if (!self::known($key)) {
                    continue;
                }
                if ((int) $row['enabled'] === 1) {
                    $open[$key] = (int) $row['sort'];
                } else {
                    unset($open[$key]);
                }
            }
        }

        asort($open);

        $domains = [];
        foreach (array_slice(array_keys($open), 0, self::MAX_OPEN) as $key) {
            $domains[] = [
                'key'   => $key,
                'label' => self::label($key, $age),
                'short' => self::DOMAINS[$key]['short'],
                'hint'  => self::DOMAINS[$key]['hint'],
            ];
        }

        return $domains;
    }

    // ── Terapistin gördüğü şerit ────────────────────────────────

    /**
     * Ekolojik şerit: satırlar alan, sütunlar hafta.
     *
     * Girdi olarak check-in geçmişini alıyor (Checkins::history) — böylece
     * şeridin sütunları eğrinin noktalarıyla birebir aynı haftalara düşüyor.
     * Ayrı bir sorguyla kendi haftalarını üretseydi iki görsel kayardı ve
     * "sınav haftası okulda koyu, ruh hali bir hafta sonra düşük" cümlesi
     * gözle okunamaz olurdu; şeridin tek işi bu cümleyi okutmak.
     *
     * Yalnız **işaret almış** alanlar satır olur. Sekiz haftadır hiç
     * dokunulmamış bir alanın boş satırı, tabloyu okunmaz kılar.
     *
     * @param list<array<string,mixed>> $checkins eskiden yeniye sıralı
     * @return array{rows:list<array{key:string,label:string,cells:list<int>}>,events:array<int,?string>}
     */
    public static function strip(array $checkins, ?int $age = null): array
    {
        if ($checkins === [] || !Schema::ecosystemReady()) {
            return ['rows' => [], 'events' => []];
        }

        $ids = array_map(static fn (array $row): int => (int) $row['id'], $checkins);
        $in  = implode(',', array_fill(0, count($ids), '?'));

        $marks = [];
        foreach (Db::all("SELECT checkin_id, domain_key, valence FROM ecosystem_marks WHERE checkin_id IN ({$in})", $ids) as $row) {
            $marks[(string) $row['domain_key']][(int) $row['checkin_id']] = (int) $row['valence'];
        }

        $rows = [];
        foreach (self::DOMAINS as $key => $domain) {
            if (!isset($marks[$key])) {
                continue;
            }
            $cells = [];
            foreach ($ids as $id) {
                $cells[] = $marks[$key][$id] ?? self::CALM;
            }
            $rows[] = ['key' => $key, 'label' => self::label($key, $age), 'cells' => $cells];
        }

        // Olay etiketi burada çözülür — şeritteki dikey çapanın altında,
        // yalnız terapistin ekranında. Çözülemezse işaret yine görünür.
        $events = [];
        foreach (Db::all("SELECT checkin_id, label_ciphertext, label_nonce FROM ecosystem_events WHERE checkin_id IN ({$in})", $ids) as $row) {
            $label = null;
            if ($row['label_ciphertext'] !== null && $row['label_nonce'] !== null) {
                try {
                    $label = Crypto::decrypt((string) $row['label_ciphertext'], (string) $row['label_nonce']);
                } catch (\RuntimeException) {
                    $label = null;
                }
            }
            $events[(int) $row['checkin_id']] = $label;
        }

        return ['rows' => $rows, 'events' => $events];
    }

    /**
     * Terapistin seçtiği alan setini yazar.
     *
     * Varsayılandan farkı olmayan satır silinir: tablo yalnız sapmaları
     * tutuyor. Böylece ileride çekirdeğe bir alan eklenirse, açık bırakılmış
     * görüşmecilerde kendiliğinden görünür.
     *
     * MAX_OPEN burada da uygulanıyor — arayüz engellese bile.
     *
     * @param list<string> $keys
     */
    public static function saveOpen(int $clientId, array $keys, ?int $age = null): void
    {
        if (!Schema::ecosystemReady()) {
            return;
        }

        $wanted   = [];
        foreach ($keys as $key) {
            if (self::known((string) $key) && !in_array($key, $wanted, true)) {
                $wanted[] = (string) $key;
            }
        }
        $wanted   = array_slice($wanted, 0, self::MAX_OPEN);
        $defaults = self::defaultsFor($age);

        Db::run('DELETE FROM ecosystem_domains WHERE client_id = ?', [$clientId]);

        $sort = 0;
        foreach (self::DOMAINS as $key => $domain) {
            $isOpen     = in_array($key, $wanted, true);
            $isDefault  = in_array($key, $defaults, true);
            if ($isOpen === $isDefault) {
                continue;   // varsayılanla aynı — satır yazmaya gerek yok
            }

            Db::run(
                'INSERT INTO ecosystem_domains (client_id, domain_key, enabled, sort, created_at)
                 VALUES (?, ?, ?, ?, NOW())',
                [$clientId, $key, $isOpen ? 1 : 0, $sort++]
            );
        }
    }

    // ── Haftalık kayıt ──────────────────────────────────────────

    /**
     * Bir check-in'e ekolojik işaretleri yazar.
     *
     * Sakin (0) işaret YAZILMAZ — satırın yokluğu zaten "bu hafta öne çıkmadı"
     * demek. Hiçbir alana dokunulmamış bir gönderim de bu yüzden tamamen
     * geçerli: tablo boş kalır, form doldurulmuş sayılır.
     *
     * @param array<string,mixed> $input alan anahtarı → −1/0/+1
     */
    public static function saveMarks(int $checkinId, array $input): void
    {
        if (!Schema::ecosystemReady()) {
            return;
        }

        foreach ($input as $key => $raw) {
            if (!self::known((string) $key)) {
                continue;
            }
            $valence = self::valence($raw);
            if ($valence === self::CALM) {
                continue;
            }

            Db::run(
                'INSERT INTO ecosystem_marks (checkin_id, domain_key, valence)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE valence = VALUES(valence)',
                [$checkinId, (string) $key, $valence]
            );
        }
    }

    /**
     * "✦ Bu hafta bir şey oldu" işareti.
     *
     * İşaretin kendisi (tarihi) klinik olarak yeterli; etiket isteğe bağlı ve
     * seans notuyla aynı kuralda şifreli saklanıyor. Şifreleme kapalıysa satır
     * yine yazılır, yalnız metin düşer — işareti kaybetmek, metni kaybetmekten
     * daha büyük kayıp olurdu.
     *
     * @return bool Etiket yazıldıysa saklanabildi mi (yazılmadıysa true).
     */
    public static function saveEvent(int $checkinId, bool $marked, string $label): bool
    {
        if (!$marked || !Schema::ecosystemReady()) {
            return true;
        }

        $cipher = null;
        $nonce  = null;
        $saved  = true;

        $label = trim(mb_substr($label, 0, 120));
        if ($label !== '') {
            try {
                [$cipher, $nonce] = Crypto::encrypt($label);
            } catch (\RuntimeException $e) {
                error_log('[panel] ekosistem olay etiketi şifrelenemedi: ' . $e->getMessage());
                $saved = false;
            }
        }

        Db::run(
            'INSERT INTO ecosystem_events (checkin_id, label_ciphertext, label_nonce, created_at)
             VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE label_ciphertext = VALUES(label_ciphertext),
                                     label_nonce      = VALUES(label_nonce)',
            [$checkinId, $cipher, $nonce]
        );

        return $saved;
    }
}
