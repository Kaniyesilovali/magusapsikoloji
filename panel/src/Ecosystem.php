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

    /**
     * Ebeveyne sorulan tek soru — VARSAYILAN. Görüşmeciye göre uyarlanmış hâli
     * için promptFor(); ergen dosyasında "çocuğunun" yanlış kişiye sesleniyor.
     */
    public const PROMPT = 'Bu hafta çocuğunun sırtını hangileri itti, hangileri karşıdan esti?';

    /**
     * Bir dosyaya elle eklenebilecek alan sayısı.
     *
     * Dört: sözlükte karşılığı olmayan şeyler ("Dans kursu", "Babanın
     * nöbetleri") tek tek değerli ama bunlar çoğaldıkça form ortak dilini
     * kaybediyor ve iki çocuğun şeridi karşılaştırılamaz hâle geliyor. Zaten
     * MAX_OPEN sınırı ortak: dört özel alan açan bir dosyada sözlükten yalnız
     * yedi alan kalır.
     */
    public const MAX_CUSTOM = 4;

    /** Elle eklenen alanların anahtarları. Sözlükte karşılıkları yok. */
    public const CUSTOM_KEYS = ['ozel1', 'ozel2', 'ozel3', 'ozel4'];

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
        return isset(self::DOMAINS[$key]) || self::isCustom($key);
    }

    /** Sözlükte olmayan, dosyaya elle eklenmiş alan mı? */
    public static function isCustom(string $key): bool
    {
        return in_array($key, self::CUSTOM_KEYS, true);
    }

    /**
     * Halkadaki çipin adı.
     *
     * Sözlük alanlarının kısa adı elle yazıldı (72 piksellik çipe "Ekran ve
     * dijital dünya" sığmıyor); uyarlanmış ve elle eklenmiş adlarda o eli
     * kimse tutmuyor, bu yüzden ilk kelime alınıp kısaltılıyor. Kısa ad tek
     * başına anlam taşımak zorunda değil: halkada her çipin altında zaten
     * çocuğun adı ve bağlam duruyor.
     */
    public static function shorten(string $label): string
    {
        $label = trim($label);
        $first = preg_split('/\s+/u', $label, -1, PREG_SPLIT_NO_EMPTY)[0] ?? $label;

        return mb_strlen($first, 'UTF-8') > 9 ? mb_substr($first, 0, 8, 'UTF-8') . '…' : $first;
    }

    /**
     * Bu görüşmeciye sorulan cümle.
     *
     * Kaydın kendi satırından okunur; boşsa koddaki varsayılan. Ebeveyn
     * dosyasında "çocuğunun sırtını", ergen dosyasında "senin sırtını" —
     * ikisini tek cümleye sıkıştıran bir metin ikisine de yabancı gelir.
     *
     * @param array<string,mixed>|null $client
     */
    public static function promptFor(?array $client): string
    {
        $custom = trim((string) ($client['checkin_prompt'] ?? ''));

        // Dosyaya özel metin yoksa merkezin kendi varsayılanı geçerli; o da
        // yoksa koddaki cümle. Varsayılan Checkins'in metin kütüğünde duruyor
        // (bkz. Checkins::TEXTS) çünkü formun bütün metinleri tek ekrandan
        // düzenleniyor — halkanınki ayrı bir yere düşseydi orada aranmazdı.
        return $custom !== '' ? $custom : Checkins::text('halka_soru');
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
     * Metinler de burada birleşiyor: satırda ad ya da ipucu yazılıysa o, yoksa
     * sözlükteki karşılığı. Elle eklenmiş alanların (ozel1…) sözlükte karşılığı
     * yok, bu yüzden adı boş kalmış bir özel satır hiç çizilmez — adsız bir çip
     * ebeveyne "bunu neye göre işaretleyeyim?" diye sorardı.
     *
     * @return list<array{key:string,label:string,short:string,hint:string}>
     */
    public static function openFor(int $clientId, ?int $age = null): array
    {
        $open  = array_fill_keys(self::defaultsFor($age), 0);
        $texts = [];

        foreach (self::rowsFor($clientId) as $row) {
            $key = (string) $row['domain_key'];
            if (!self::known($key)) {
                continue;
            }
            if ((int) $row['enabled'] === 1) {
                $open[$key] = (int) $row['sort'];
            } else {
                unset($open[$key]);
            }
            $texts[$key] = $row;
        }

        asort($open);

        $domains = [];
        foreach (array_slice(array_keys($open), 0, self::MAX_OPEN) as $key) {
            $label = trim((string) ($texts[$key]['label'] ?? ''));
            $hint  = trim((string) ($texts[$key]['hint'] ?? ''));

            if ($label === '') {
                if (self::isCustom($key)) {
                    continue;   // adı silinmiş özel alan — çizilecek bir şey yok
                }
                $label = self::label($key, $age);
            }
            if ($hint === '') {
                $hint = self::DOMAINS[$key]['hint'] ?? '';
            }

            $domains[] = [
                'key'   => $key,
                'label' => $label,
                // Kısa ad yalnız sözlükteki ad AYNEN duruyorsa elle yazılmış
                // hâlinden gelir; uyarlanmış adın kısası da uyarlanmış olmalı.
                'short' => $label === (self::DOMAINS[$key]['label'] ?? null)
                    ? self::DOMAINS[$key]['short']
                    : self::shorten($label),
                'hint'  => $hint,
            ];
        }

        return $domains;
    }

    /**
     * Bir dosyanın `ecosystem_domains` satırları — metin sütunları varsa
     * onlarla birlikte.
     *
     * Göç uygulanmadan da çalışması gerekiyor (deploy ile göç arasındaki
     * boşluk): sütunlar yoksa sorgu NULL seçer ve her şey varsayılana düşer.
     *
     * @return list<array<string,mixed>>
     */
    private static function rowsFor(int $clientId): array
    {
        if (!Schema::ecosystemReady()) {
            return [];
        }

        $texts = Schema::ecosystemTextsReady() ? 'label, hint' : 'NULL AS label, NULL AS hint';

        return Db::all(
            "SELECT domain_key, enabled, sort, {$texts}
               FROM ecosystem_domains WHERE client_id = ? ORDER BY sort, id",
            [$clientId]
        );
    }

    /**
     * Uyarlama ekranının satırları: sözlüğün tamamı + elle eklenen yuvalar.
     *
     * Kutulara O AN GEÇERLİ metin yazılıyor, boş bir kutu değil: terapist
     * ebeveynin gördüğü kelimenin üstünde çalışmalı. Varsayılanla birebir aynı
     * kalan metin kaydedilmiyor (bkz. saveDomains), yani "değiştirmeden kaydet"
     * dosyayı sözlüğe bağlı bırakıyor.
     *
     * @return list<array{key:string,label:string,hint:string,default_label:string,default_hint:string,open:bool,core:bool,custom:bool}>
     */
    public static function form(int $clientId, ?int $age = null): array
    {
        $open  = array_fill_keys(self::defaultsFor($age), true);
        $texts = [];

        foreach (self::rowsFor($clientId) as $row) {
            $key = (string) $row['domain_key'];
            if (!self::known($key)) {
                continue;
            }
            $open[$key]  = (int) $row['enabled'] === 1;
            $texts[$key] = $row;
        }

        $fields = [];
        foreach ([...array_keys(self::DOMAINS), ...self::CUSTOM_KEYS] as $key) {
            $custom       = self::isCustom($key);
            $defaultLabel = $custom ? '' : self::label($key, $age);
            $defaultHint  = $custom ? '' : self::DOMAINS[$key]['hint'];

            $label = trim((string) ($texts[$key]['label'] ?? ''));
            $hint  = trim((string) ($texts[$key]['hint'] ?? ''));

            $fields[] = [
                'key'           => $key,
                'label'         => $label !== '' ? $label : $defaultLabel,
                'hint'          => $hint !== '' ? $hint : $defaultHint,
                'default_label' => $defaultLabel,
                'default_hint'  => $defaultHint,
                // Adı olmayan özel yuva açık görünemez: ortada bir alan yok.
                'open'          => ($open[$key] ?? false) && !($custom && $label === ''),
                'core'          => !$custom && self::DOMAINS[$key]['core'],
                'custom'        => $custom,
            ];
        }

        return $fields;
    }

    /**
     * Şeridin satır başlıkları: anahtar → o dosyada geçerli ad.
     *
     * KAPALI ve elle eklenmiş alanlar da var. Sebebi geçmiş: sekiz hafta önce
     * işaretlenmiş bir alan bugün kapatılmış olabilir, ama şeritte satırı
     * duruyor ve başlıksız kalamaz.
     *
     * @return array<string,string>
     */
    public static function labelsFor(int $clientId, ?int $age = null): array
    {
        $labels = [];
        foreach (array_keys(self::DOMAINS) as $key) {
            $labels[$key] = self::label($key, $age);
        }

        foreach (self::rowsFor($clientId) as $row) {
            $key   = (string) $row['domain_key'];
            $label = trim((string) ($row['label'] ?? ''));
            if (self::known($key) && $label !== '') {
                $labels[$key] = $label;
            }
        }

        return $labels;
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
     * Satır başlıkları o dosyanın kendi adlarıyla yazılır (uyarlanmış "Nine ve
     * dede", elle eklenmiş "Dans kursu"): terapist şeride bakarken ebeveynin
     * gördüğü kelimeyi görmeli, yoksa iki ekran aynı haftayı iki dille anlatır.
     *
     * @param list<array<string,mixed>> $checkins eskiden yeniye sıralı
     * @param int $clientId  0 ise başlıklar sözlükten okunur (uyarlama aranmaz)
     * @return array{rows:list<array{key:string,label:string,cells:list<int>}>,events:array<int,?string>}
     */
    public static function strip(array $checkins, ?int $age = null, int $clientId = 0): array
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

        $labels = $clientId > 0
            ? self::labelsFor($clientId, $age)
            : [];

        // Sıra sözlükteki sıra, elle eklenenler en sonda: şeridin satırları
        // haftadan haftaya yer değiştirmemeli, gözün aradığı satır aynı yerde
        // kalmalı.
        $rows = [];
        foreach ([...array_keys(self::DOMAINS), ...self::CUSTOM_KEYS] as $key) {
            if (!isset($marks[$key])) {
                continue;
            }
            $cells = [];
            foreach ($ids as $id) {
                $cells[] = $marks[$key][$id] ?? self::CALM;
            }
            $rows[] = [
                'key'   => $key,
                // Adı silinmiş bir özel alanın geçmişi yine görünür: veri
                // duruyor, yalnız başlığı kayıp.
                'label' => $labels[$key] ?? (self::isCustom($key) ? 'Elle eklenen alan' : self::label($key, $age)),
                'cells' => $cells,
            ];
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
     * Terapistin seçtiği alan setini ve o dosyaya uyarlanmış metinleri yazar.
     *
     * Varsayılandan farkı olmayan satır YAZILMAZ: tablo yalnız sapmaları
     * tutuyor. Böylece ileride çekirdeğe bir alan eklenirse ya da sözlükteki
     * bir ipucu düzeltilirse, uyarlanmamış dosyalarda kendiliğinden görünür.
     * "Sapma" artık üç şeyden biri olabilir: açık/kapalı durumu, ad, ipucu.
     *
     * Adı boş bırakılan özel alan hiç yazılmaz — silme işlemi bu. Geçmişteki
     * işaretleri `ecosystem_marks` içinde kalır; şeritte başlıksız değil,
     * "Elle eklenen alan" olarak görünür (bkz. strip).
     *
     * MAX_OPEN burada da uygulanıyor — arayüz engellese bile.
     *
     * @param array<string,array<string,string>> $input alan anahtarı → [acik, ad, ipucu]
     */
    public static function saveDomains(int $clientId, array $input, ?int $age = null): void
    {
        if (!Schema::ecosystemReady()) {
            return;
        }

        $texts    = Schema::ecosystemTextsReady();
        $defaults = self::defaultsFor($age);
        $rows     = [];
        $openCount = 0;

        foreach ([...array_keys(self::DOMAINS), ...self::CUSTOM_KEYS] as $key) {
            $field  = (array) ($input[$key] ?? []);
            $custom = self::isCustom($key);

            // Metin sütunları yoksa elle eklenen alan da yok: adı saklanamayan
            // bir çip ebeveyne "ozel1" diye görünürdü.
            if ($custom && !$texts) {
                continue;
            }

            $label = self::clean($field['ad'] ?? '', 60);
            $hint  = self::clean($field['ipucu'] ?? '', 120);

            // Sözlüktekiyle birebir aynı metin sapma değildir: kaydedilmezse
            // koddaki metin ileride düzeltildiğinde bu dosya da düzelir.
            if (!$custom) {
                if ($label === self::label($key, $age)) {
                    $label = '';
                }
                if ($hint === (self::DOMAINS[$key]['hint'] ?? '')) {
                    $hint = '';
                }
            } elseif ($label === '') {
                continue;   // adsız özel alan = yok
            }

            $isOpen = ($field['acik'] ?? '') !== '' && $openCount < self::MAX_OPEN;
            if ($isOpen) {
                $openCount++;
            }

            $isDefault = !$custom && in_array($key, $defaults, true);
            if ($isOpen === $isDefault && $label === '' && $hint === '') {
                continue;   // her yönüyle varsayılan — satır gerekmiyor
            }

            $rows[] = [$key, $isOpen, $texts ? $label : '', $texts ? $hint : ''];
        }

        Db::run('DELETE FROM ecosystem_domains WHERE client_id = ?', [$clientId]);

        $sort = 0;
        foreach ($rows as [$key, $isOpen, $label, $hint]) {
            $columns = $texts
                ? 'client_id, domain_key, label, hint, enabled, sort, created_at'
                : 'client_id, domain_key, enabled, sort, created_at';
            $values  = $texts
                ? [$clientId, $key, $label === '' ? null : $label, $hint === '' ? null : $hint, $isOpen ? 1 : 0, $sort++]
                : [$clientId, $key, $isOpen ? 1 : 0, $sort++];
            $marks   = implode(', ', array_fill(0, count($values), '?'));

            Db::run("INSERT INTO ecosystem_domains ({$columns}) VALUES ({$marks}, NOW())", $values);
        }
    }

    /**
     * Halkanın üstündeki soruyu bu dosyaya uyarlar. Boş bırakılan alan
     * varsayılana döner — koddaki cümle ileride değişirse dosya da değişsin.
     */
    public static function savePrompt(int $clientId, string $text): void
    {
        if (!Schema::ecosystemTextsReady()) {
            return;
        }

        $text = self::clean($text, 200);
        Db::run(
            'UPDATE clients SET checkin_prompt = ?, updated_at = NOW() WHERE id = ?',
            [$text === '' || $text === self::PROMPT ? null : $text, $clientId]
        );
    }

    /** Tek satırlık serbest metin: kırpılır, satır sonları temizlenir. */
    private static function clean(string $raw, int $max): string
    {
        return trim(mb_substr(preg_replace('/\s+/u', ' ', $raw) ?? '', 0, $max));
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
