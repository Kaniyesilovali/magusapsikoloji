<?php
declare(strict_types=1);

namespace Panel;

/**
 * Onam — metin, sürüm, bağlantı ve verilmiş onayların kaydı.
 *
 * Onam formu seansın İÇİNDE okutuluyordu ve bu iki türlü de kötüydü: metin
 * okunmuyordu ("süreden gidiyor" diye gerilen birey sayfayı çeviriyordu) ya
 * da seansın ilk on dakikası okumaya gidiyordu. Oysa onamın hukuki değeri
 * imzada değil, ANLAŞILMIŞ olmasında. Bu yüzden metin artık seanstan önce,
 * evde, kişinin kendi hızında okunuyor.
 *
 * Üç kural yapıyı belirliyor:
 *
 *  1. Bağlantı GİRİŞ GEREKTİRMEZ — check-in'deki gerekçenin aynısı (bkz.
 *     Checkins). Yetki bağlantının kendisinde: tek kullanımlık, süreli, tek
 *     bir kişiye ait 256 bitlik bir jeton.
 *  2. Çevrimiçi tik KISMİ onamdır. Metnin okunduğunu gösterir, onamı
 *     kapatmaz. Kapanış seansta olur: ıslak imza ya da online görüşmede
 *     sözlü beyan. `clients.consent_at` yalnız kapanınca dolar — panelin
 *     bütün sayaçları ve rozetleri bu yüzden anlamını koruyor.
 *  3. Akış YALNIZ çevrimiçi olamaz. Yaşlı birey var, interneti kullanmayan
 *     birey var; onlarda kâğıt yolu (yüz yüze okuma, açıklama, imza) hiç
 *     değişmeden duruyor. Bağlantı bir seçenek, bir önkoşul değil.
 *
 * Sürüm, verilmiş onamın hangi metne ait olduğunu gösteren tek bağdır ve
 * artık geri getirilebilir: her sürümün metni `consent_versions`'ta duruyor.
 *
 * Metnin bir de İngilizcesi var — yalnız ÇIKTI için, aynı sürüm numarasıyla
 * (bkz. currentTextEn). Bağlantı ve panel Türkçe kalıyor: çeviri, Türkçe
 * okumayan bireyin masasına konan kâğıt, ayrı bir onam metni değil.
 */
final class Consent
{
    /**
     * Bağlantının geçerlilik süresi (gün).
     *
     * On dört gün: telefonda randevu ile seans arasındaki tipik aralığı
     * rahatça kapsıyor, ama unutulmuş bir bağlantının aylar sonra
     * tıklanmasına izin vermiyor — o tık, o gün okunmuş bir metnin kanıtı
     * sayılamazdı.
     */
    public const TTL_DAYS = 14;

    /** Hiç metin kaydedilmemiş kurumda geçerli sürüm (bkz. starterText). */
    public const DEFAULT_VERSION = '2.2';

    // ── Metin ve sürüm ──────────────────────────────────────────

    public static function currentText(): string
    {
        return (string) Settings::get('consent_text', self::starterText());
    }

    /**
     * Formun İngilizce karşılığı — yalnız çıktı için.
     *
     * Merkeze Türkçe okumayan birey geliyor (KKTC'de öğrenci, yabancı
     * uyruklu çalışan) ve masaya konan kâğıt onun okuyamadığı bir metin
     * olduğunda "bilgilendirilmiş" onam olmuyordu; kâğıdı çeviren psikolog
     * oluyordu. Çeviri paneldeki metnin YANINDA duruyor: aynı sürüm, iki dil.
     *
     * Çevrimiçi bağlantı bilerek Türkçe kalıyor. Onamın hukuki bağı Türkçe
     * metne; İngilizce kâğıt onun çevirisi ve çıktının üstünde bunu söylüyor.
     */
    public static function currentTextEn(): string
    {
        return (string) Settings::get('consent_text_en', self::starterTextEn());
    }

    public static function currentVersion(): string
    {
        return (string) Settings::get('consent_version', self::DEFAULT_VERSION);
    }

    /**
     * Çevirinin yazıldığı sürüm.
     *
     * Yalnız İngilizce metin GERÇEKTEN değiştiğinde güncelleniyor (bkz.
     * ConsentController::update). Türkçe metni düzenleyip sürümü yükselten
     * ama çeviriye dokunmayan bir kayıtta bu numara geride kalıyor — panelin
     * "çeviri eskidi" uyarısı buradan çıkıyor. Kaydet düğmesine basıldığı
     * için ilerleseydi, uyaracak bir şey kalmazdı.
     */
    public static function translatedVersion(): string
    {
        return (string) Settings::get('consent_text_en_version', self::currentVersion());
    }

    /** Türkçe metin çeviriden sonra değişmiş mi? */
    public static function translationStale(): bool
    {
        return self::translatedVersion() !== self::currentVersion();
    }

    /** Metin hiç kaydedilmemiş: panel her yerde "taslak" diyor. */
    public static function isDraft(): bool
    {
        return Settings::get('consent_text') === '';
    }

    /** Çeviri hiç kaydedilmemiş: çıktının üstünde "taslak" uyarısı duruyor. */
    public static function isDraftEn(): bool
    {
        return Settings::get('consent_text_en') === '';
    }

    /**
     * Bir sürümün metni — arşivden.
     *
     * Güncel sürüm sorulduğunda ayarlardaki metin dönüyor: arşiv göç ile
     * geldi, ondan önce kaydedilmiş bir sürümün satırı olmayabilir. Böyle bir
     * durumda null dönmek, çıktıyı boş bir kâğıda çevirirdi.
     */
    public static function versionText(string $version): ?string
    {
        if ($version === self::currentVersion()) {
            return self::currentText();
        }
        if (!Schema::consentReady()) {
            return null;
        }

        $text = Db::value('SELECT text FROM consent_versions WHERE version = ? LIMIT 1', [$version]);

        return $text === null ? null : (string) $text;
    }

    /**
     * Bir sürümün İngilizce metni — arşivden.
     *
     * Türkçedeki gerekçenin aynısı, tek farkla: bir sürümün çevirisi hiç
     * yazılmamış olabilir (kurum çeviriyi sonradan ekledi). Boş satır da null
     * dönüyor; çağıran o zaman eldeki çeviriyi basıyor (bkz.
     * ConsentController::renderPrint).
     */
    public static function versionTextEn(string $version): ?string
    {
        if ($version === self::translatedVersion()) {
            return self::currentTextEn();
        }
        if (!Schema::consentReady()) {
            return null;
        }

        $text = (string) (Db::value('SELECT text_en FROM consent_versions WHERE version = ? LIMIT 1', [$version]) ?? '');

        return $text === '' ? null : $text;
    }

    /**
     * Sürümün metnini arşive yazar — kaydedildiği anda, bir kez.
     *
     * Aynı sürüme ikinci kez farklı bir metin yazılamaz: ConsentController
     * metin değişince sürümü de değiştirmeye zorluyor, bu yüzden çakışma
     * ancak aynı metnin yeniden kaydedilmesidir ve o da bir şeyi değiştirmez.
     */
    public static function archive(string $version, string $text): void
    {
        if (!Schema::consentReady() || $version === '' || $text === '') {
            return;
        }

        Db::run(
            'INSERT INTO consent_versions (version, text, created_at) VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE text = VALUES(text)',
            [$version, $text]
        );
    }

    /**
     * Çeviriyi, çevirdiği sürümün satırına yazar.
     *
     * Türkçe metin de INSERT'e giriyor çünkü satır henüz olmayabilir: çeviri,
     * Türkçe metne dokunulmadan tek başına kaydedilebiliyor ve arşivde o
     * sürümün satırı yalnız Türkçe kaydedildiğinde açılıyor. Çakışmada yalnız
     * text_en güncelleniyor — Türkçe metnin arşivdeki hâli değişmez.
     */
    public static function archiveTranslation(string $version, string $textEn): void
    {
        if (!Schema::consentReady() || $version === '' || $textEn === '') {
            return;
        }

        Db::run(
            'INSERT INTO consent_versions (version, text, text_en, created_at) VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE text_en = VALUES(text_en)',
            [$version, self::currentText(), $textEn]
        );
    }

    // ── Bağlantı ────────────────────────────────────────────────

    /**
     * Yeni bir onam bağlantısı üretir ve düz jetonu döndürür.
     *
     * Eldeki doldurulmamış bağlantı SİLİNMEZ, süresi o anda doldurulur —
     * check-in'deki gerekçe (bkz. Checkins::createRequest): gönderilmiş ama
     * cevapsız kalmış bir bağlantının satırı, "kaç kişiye gitti, kaçı
     * onayladı" sorusunun paydası.
     */
    public static function createRequest(int $clientId): string
    {
        $token = bin2hex(random_bytes(32));

        Db::run(
            'UPDATE consent_requests SET expires_at = NOW()
              WHERE client_id = ? AND completed_at IS NULL AND expires_at > NOW()',
            [$clientId]
        );
        Db::run(
            'INSERT INTO consent_requests (client_id, token_hash, version, expires_at, created_at)
             VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? DAY), NOW())',
            [$clientId, hash('sha256', $token), self::currentVersion(), self::TTL_DAYS]
        );

        return $token;
    }

    /** Gönderim başarılıysa işaretlenir; boş kalması "bağlantı elle iletildi" demek. */
    public static function markSent(string $token): void
    {
        Db::run(
            'UPDATE consent_requests SET sent_at = NOW() WHERE token_hash = ?',
            [hash('sha256', $token)]
        );
    }

    public static function link(string $token): string
    {
        return rtrim((string) Config::get('app.url'), '/') . '/onam/' . $token;
    }

    /**
     * Üretilen bağlantıyı bir sonraki sayfada göstermek üzere saklar.
     *
     * E-posta gitmiş olsa bile: telefonda randevu alan kişiye bağlantı çoğu
     * zaman WhatsApp'tan gidiyor ve panelde SMS/WhatsApp gönderimi yok.
     * Kopyalanabilir kutu bu akışın ASIL yolu, yedeği değil.
     */
    public static function share(string $name, string $token, bool $sent): void
    {
        $_SESSION['_consent_link'] = [
            'name' => $name,
            'url'  => self::link($token),
            'sent' => $sent,
        ];
    }

    /** Saklanan bağlantıyı bir kez okur ve siler. */
    public static function pendingLink(): ?array
    {
        $link = $_SESSION['_consent_link'] ?? null;
        unset($_SESSION['_consent_link']);

        return $link;
    }

    /**
     * Jetonun kaydı — durumuna bakılmadan; geçerlilik kararı state() ile
     * veriliyor ki denetleyici kibar bir ayrım yapabilsin.
     *
     * @return array<string,mixed>|null
     */
    public static function request(string $token): ?array
    {
        if (!preg_match('/^[0-9a-f]{32,128}$/', $token)) {
            return null;
        }

        return Db::one(
            'SELECT r.*, c.full_name, c.status AS client_status
               FROM consent_requests r
               JOIN clients c ON c.id = r.client_id
              WHERE r.token_hash = ?
              LIMIT 1',
            [hash('sha256', $token)]
        );
    }

    /** ok | used | expired | closed | unknown */
    public static function state(?array $request): string
    {
        if ($request === null) {
            return 'unknown';
        }
        if ($request['completed_at'] !== null) {
            return 'used';
        }
        if (strtotime((string) $request['expires_at']) < time()) {
            return 'expired';
        }
        if ($request['client_status'] !== 'active') {
            return 'closed';
        }
        return 'ok';
    }

    /** Bekleyen (onaylanmamış, süresi geçmemiş) bağlantı var mı? */
    public static function pendingRequest(int $clientId): ?array
    {
        if (!Schema::consentReady()) {
            return null;
        }

        return Db::one(
            'SELECT * FROM consent_requests
              WHERE client_id = ? AND completed_at IS NULL AND expires_at > NOW()
           ORDER BY created_at DESC LIMIT 1',
            [$clientId]
        );
    }

    // ── Onayın kaydı ────────────────────────────────────────────

    /**
     * Çevrimiçi onay: birey metni okuyup en alttaki tiki işaretledi.
     *
     * `clients.consent_at`'e DOKUNMAZ. Tik metnin okunduğunu gösterir, onamı
     * kapatmaz — kapanış seansta, ıslak imza ya da sözlü beyanla olur. Kayıtta
     * "Alındı" yazması için henüz erken; yazsaydı, imza atılmamış bir dosya
     * panelde tamamlanmış görünürdü.
     *
     * ip ve user_agent burada saklanıyor çünkü tiki işaretleyenin oturumu yok:
     * onun kimliğinin tek karşılığı bağlantının kendisi ve isteğin izi.
     *
     * Sürüm DIŞARIDAN veriliyor ve bilerek `$request['version']` DEĞİL. İstek
     * satırındaki numara bağlantının ÜRETİLDİĞİ andaki sürüm; metin araya
     * girip düzenlenmiş olabilir ve kişi sayfada güncel metni okur. İkisi
     * karıştırılırsa kayıt, bireyin hiç görmediği bir sürüme onam vermiş
     * gibi görünür — sonra da çıktı o sürümün metnini basıp imzalatır.
     * Denetleyici, sayfada gösterilen sürümü gizli alanla geri alıp güncelle
     * karşılaştırdıktan SONRA burayı çağırıyor.
     */
    public static function approve(array $request, string $version): void
    {
        Db::run(
            'INSERT INTO consent_records
                 (client_id, method, version, approved_at, request_id, ip, user_agent, created_at)
             VALUES (?, \'online\', ?, NOW(), ?, ?, ?, NOW())',
            [
                (int) $request['client_id'],
                $version,
                (int) $request['id'],
                client_ip(),
                mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ]
        );

        Db::run('UPDATE consent_requests SET completed_at = NOW() WHERE id = ?', [(int) $request['id']]);
    }

    /**
     * Seansta kapanan onam: ıslak imza (paper) ya da sözlü beyan (verbal).
     *
     * Sürüm, varsa bireyin çevrimiçi onayladığı sürüm — güncel sürüm değil.
     * Kişi 2.0'ı okuyup onayladıysa ve metin arada 2.1 olduysa, imzaladığı şey
     * hâlâ 2.0'dır; çıktı da o sürümü basıyor (bkz. ConsentController::printForm).
     *
     * `reference` yalnız verbal içindir: ses/görüntü dosyası PANELDE DEĞİL,
     * merkezin kendi klasöründe duruyor. Buradaki satır o dosyanın nerede
     * olduğunu söyleyen kısa bir künye — klinik içerik yazılacak yer değil.
     */
    public static function record(int $clientId, string $method, int $actorId, string $reference = ''): void
    {
        $version = self::approvedVersion($clientId) ?? self::currentVersion();

        // Göç uygulanmadan da onam işaretlenebilmeli: kutucuk yolu panelin
        // bugünkü davranışı ve deploy ile göç arasındaki boşlukta kimse
        // "onam alamıyorum" durumunda kalmamalı. O aralıkta yazılan tek şey
        // kaydın kendi sütunları — yani bugüne kadar yazılanın aynısı.
        if (Schema::consentReady()) {
            Db::run(
                'INSERT INTO consent_records
                     (client_id, method, version, approved_at, recorded_by, reference, created_at)
                 VALUES (?, ?, ?, NOW(), ?, ?, NOW())',
                [
                    $clientId,
                    $method === 'verbal' ? 'verbal' : 'paper',
                    $version,
                    $actorId,
                    $reference === '' ? null : mb_substr($reference, 0, 200),
                ]
            );
        }

        Db::run(
            'UPDATE clients SET consent_at = NOW(), consent_version = ?, updated_at = NOW() WHERE id = ?',
            [$version, $clientId]
        );
    }

    /**
     * Onamı geri alır: açık imza/sözlü kayıtlar kapanır, kutucuk boşalır.
     *
     * Kayıt SİLİNMEZ. Bugüne kadar kutucuğun işareti kaldırılınca consent_at
     * ve consent_version sessizce NULL'lanıyordu ve verilmiş bir onamın izi
     * yalnız denetim kaydında kalıyordu. Verilmiş bir onam geri alınabilir,
     * ama verilmemiş sayılamaz.
     *
     * Çevrimiçi kayda dokunulmuyor: durum kendiliğinden "Çevrimiçi onaylandı"
     * hâline geri düşüyor — birey metni okumuştu, o hâlâ doğru.
     */
    public static function revoke(int $clientId, int $actorId): void
    {
        if (Schema::consentReady()) {
            Db::run(
                'UPDATE consent_records
                    SET revoked_at = NOW(), revoked_by = ?
                  WHERE client_id = ? AND method IN (\'paper\',\'verbal\') AND revoked_at IS NULL',
                [$actorId, $clientId]
            );
        }

        Db::run(
            'UPDATE clients SET consent_at = NULL, consent_version = NULL, updated_at = NOW() WHERE id = ?',
            [$clientId]
        );
    }

    // ── Okuma ───────────────────────────────────────────────────

    /**
     * Bir kaydın onam durumu — üç hâl, tek karar noktası.
     *
     * Görünümler bu kararı ikinci kez hesaplamasın diye Checkins::deliveryState
     * ile aynı biçimde dönüyor: anahtar, etiket, ton ve bir cümlelik açıklama.
     *
     *   eksik      Hiç onay yok. Bugünkü kırmızı şeridin karşılığı.
     *   cevrimici  Metin okundu ve tiklendi; seansta kapanması bekleniyor.
     *   tam        İmza atıldı ya da sözlü beyan alındı.
     *
     * @param  array<string,mixed> $client `consent_at` taşıyan satır
     * @return array{key:string, label:string, tone:string, detail:string}
     */
    public static function status(array $client): array
    {
        if ($client['consent_at'] !== null) {
            return [
                'key'    => 'tam',
                'label'  => 'Alındı',
                'tone'   => 'go',
                'detail' => 'Onam tamamlandı.',
            ];
        }

        $online = Schema::consentReady() ? self::latestOnline((int) $client['id']) : null;

        if ($online !== null) {
            return [
                'key'    => 'cevrimici',
                'label'  => 'Çevrimiçi onaylandı',
                'tone'   => 'neutral',
                'detail' => 'Birey metnin ' . (string) $online['version'] . ' sürümünü '
                    . dt((string) $online['approved_at']) . ' tarihinde okuyup onayladı. '
                    . 'Seansta ıslak imza ya da sözlü onam bekleniyor.',
            ];
        }

        return [
            'key'    => 'eksik',
            'label'  => 'Eksik',
            'tone'   => 'stop',
            'detail' => 'Bu kayıt için onam alınmamış.',
        ];
    }

    /** @return array<string,mixed>|null Bireyin en son çevrimiçi onayı. */
    public static function latestOnline(int $clientId): ?array
    {
        if (!Schema::consentReady()) {
            return null;
        }

        return Db::one(
            'SELECT * FROM consent_records
              WHERE client_id = ? AND method = \'online\' AND revoked_at IS NULL
           ORDER BY approved_at DESC LIMIT 1',
            [$clientId]
        );
    }

    /**
     * Bireyin onay verdiği sürüm — çıktının hangi metni basacağını belirler.
     *
     * Çevrimiçi onay varsa onun sürümü: kişi okuduğu metni imzalamalı. Yoksa
     * null döner ve çağıran güncel sürümü kullanır (kâğıt yolu — metin zaten
     * masada, o anki hâliyle okunuyor).
     */
    public static function approvedVersion(int $clientId): ?string
    {
        $online = self::latestOnline($clientId);

        return $online === null ? null : (string) $online['version'];
    }

    /**
     * Kaydın onam geçmişi — yeniden eskiye, geri alınanlar dahil.
     *
     * Geri alınanlar da geliyor: "bu kayıtta onam neden yok?" sorusunun cevabı
     * çoğu zaman bir zamanlar var olduğu ve kaldırıldığıdır.
     *
     * @return list<array<string,mixed>>
     */
    public static function history(int $clientId): array
    {
        if (!Schema::consentReady()) {
            return [];
        }

        return Db::all(
            'SELECT r.*, u.full_name AS recorded_by_name
               FROM consent_records r
          LEFT JOIN users u ON u.id = r.recorded_by
              WHERE r.client_id = ?
           ORDER BY r.approved_at DESC, r.id DESC',
            [$clientId]
        );
    }

    /** Kayıt satırının okunur adı. */
    public static function methodLabel(string $method): string
    {
        return match ($method) {
            'online' => 'Çevrimiçi onay',
            'paper'  => 'Islak imza',
            'verbal' => 'Sözlü onam',
            default  => $method,
        };
    }

    /** '1.0' → '1.1'; sayısal olmayan sürümlerde ipucu vermeden döner. */
    public static function nextVersion(string $current): string
    {
        if (preg_match('/^(\d+)\.(\d+)$/', $current, $parts) === 1) {
            return $parts[1] . '.' . ((int) $parts[2] + 1);
        }
        return $current . '-2';
    }

    /**
     * Başlangıç taslağı. Hukuki metin DEĞİLDİR; kurumun kendi bilgileriyle
     * doldurulup bir hukukçuya onaylatılması gerekir. Panelde her yerde
     * "taslak" olarak işaretlenir.
     *
     * Başlık burada yok: kâğıdın üstüne kurum adıyla birlikte çıktı sayfası
     * basıyor (consent/print.php). Metin, doğrudan bireye hitapla başlıyor.
     *
     * Kayıt yasağı maddesi 2.1'de ayrıldı: yasak SEANSIN kaydı için duruyor,
     * onamın kendisinin sözlü beyanı ondan ayrı. Bu cümle olmadan, online
     * bireyden onamının kaydını istemek kâğıdın verdiği sözle çelişiyordu.
     * Metin 012 numaralı göçle birebir aynı; ikisi birlikte değişir.
     */
    public static function starterText(): string
    {
        return <<<'METIN'
        Bilgilendirilmiş Onam Formu, sahip olduğunuz haklarla ve sorumluluklarla
        ilgili sizi bilgilendirmek amacıyla oluşturulmuştur. Psikoloğunuzla
        karşılıklı olarak onayladığınız bir anlaşma niteliği taşıyacaktır.

        PSİKOTERAPİ SÜRECİNE YÖNELİK BİLGİLENDİRME

        - Seans süresi ortalama 45-50 dakikadır.
        - Seanslara düzenli ve zamanında katılım, sağlıklı bir psikoterapötik
          ilişki kurabilmek için önemli ve gereklidir.
        - Seansa birey tarafından geç kalınması durumunda, yalnızca geriye kalan
          süre kadar görüşme yapılabilecektir. Eğer gecikme psikolog kaynaklıysa,
          psikolog süreyi tamamlamakla yükümlüdür.
        - Seans ücreti, seans öncesinde ödenmektedir.
        - Seansı erteleme veya iptal etmeniz gereken durumlarda, seans saatinden
          en az 24 saat öncesinde bilgi vermeniz beklenmektedir. Son 24 saat içinde
          iptal olan seanslar için seans ücreti talep edilmektedir.
        - Yeni randevular, psikoterapi sürecinin seyrine göre haftalık veya iki
          haftada bir olacak şekilde planlanır.
        - Online görüşmeler sırasında ise rahatsız edilme ihtimali olmayan, sessiz
          ve sakin, dikkat dağıtıcı unsurların olmadığı ortamlar tercih edilmelidir.
        - Seans süresince telefonlar sessizde olmalıdır.
        - Etik ilkeler ve gizliliğin korunması amacıyla, seansların ses ve/veya
          görüntü kaydının alınmasına izin verilmemektedir. Bu kural seansın kendisi
          içindir. Onamınızı çevrimiçi görüşmede sözlü olarak beyan etmeniz istenirse,
          alınan kısa kayıt yalnızca onamınızın belgesi olarak, seans içeriğinden ayrı
          biçimde ve aynı gizlilik kuralıyla saklanır.

        GİZLİLİK VE GÜVENİLİRLİK İLKESİ

        - Psikolog ve birey arasında konuşulan her şey gizlidir ve üçüncü
          kişilerle paylaşılmaz.
        - Psikolog, seans notları da dahil olmak üzere, psikoterapi süreci boyunca
          tutacağı raporları ve bireyin kişisel bilgilerini, kimsenin
          ulaşamayacağı şekilde saklamak ve muhafaza etmekle yükümlüdür. Bu kural,
          psikoterapötik süreç sona erdikten sonra da geçerlidir.
        - Gizlilik ilkesinin, psikolog tarafından ihlal edilebileceği sadece 2 koşul
          mevcuttur:
          - Bu koşulların en önemlisi; bireyin kendine ve/veya bir başkasına zarar
            verme riski görülmesidir. Bu gibi durumlarda, psikolog gerekli mercilere
            bilgi vermek ve/veya bireyin bir aile yakınıyla iletişime geçmek
            durumundadır.
          - Diğer bir koşul ise herhangi bir hukuki süreçte, mahkeme kararıyla
            psikologdan bilgi istenilmesi durumudur. Bu durumda da psikolog yasal
            olarak, mahkeme kararını uygulamaya geçirmek zorundadır.
        - Psikoterapötik süreç içinde gerçekleşebilecek herhangi bir olağandışı
          durumda bireyi koruyabilmek ve erken müdahaleyi sağlayabilmek için bir
          aile yakını bilgisi almak gereklidir.

        KİŞİSEL BİLGİLERİNİZİN KAYDI

        - Kaydınızda ad-soyadınız, iletişim bilgileriniz ve doğum tarihiniz,
          randevularınız ve seans sürecine ilişkin notlar tutulur. Seans notları,
          yasa önünde sağlık verisi sayılır; en korunaklı bilgi türüdür.
        - Bu bilgiler yalnızca randevunuzun planlanması, sürecin yürütülmesi ve
          takibi ile yasal saklama yükümlülükleri için kullanılır. Başka hiçbir
          amaçla kullanılmaz.
        - Seans notları şifrelenerek saklanır ve yalnızca notu tutan psikolog
          tarafından görüntülenebilir. Kayıtlar [saklama süresi] süresince saklanır,
          sürenin sonunda imha edilir.
        - Kaydınız, yukarıdaki gizlilik başlığında belirtilen iki koşul dışında
          hiç kimseyle paylaşılmaz.
        - Sağlık verisi niteliğindeki seans notlarınız, KVKK m.6 uyarınca yalnızca
          açık rızanızla işlenebilir; bu formu imzalamanız bu rızayı da kapsar.
          Diğer bilgileriniz sözleşmenin kurulması ve ifası hukuki sebebine dayanır.
        - KVKK m.11 kapsamında bilgilerinize erişme, düzeltilmesini veya silinmesini
          isteme ve işlenmesine itiraz etme haklarına sahipsiniz. Başvurularınızı
          [iletişim adresi] üzerinden iletebilirsiniz.

        GÖNÜLLÜLÜK İLKESİ

        - Psikoterapi sürecine başlamak ve sürecin devamlılığını sağlamak için
          bireyin gönüllülüğü çok önemlidir. Bireyin kendi isteğiyle ve hiçbir
          zorlama altında olmadan psikolojik destek istediğinden emin olunmalıdır.
        - Birey, istediği zaman görüşmeleri sonlandırma hakkına sahiptir.
        - Psikoterapötik sürecinize son vermek istemeniz, yeniden başlamaya ihtiyaç
          duyduğunuzda bir engel oluşturmaz. İstediğiniz zaman yeniden randevu
          alabilir ve sürecinize geri dönebilirsiniz.

        "Bu formu imzalayarak, yukarıda belirtilen hak ve sorumlulukları kabul
        ediyorum. Psikolojik destek süreci için gönüllü ve psikoloğum ile iş birliği
        içinde çalışmaya istekli olduğumu beyan ederim. Seans notlarım dahil kişisel
        bilgilerimin yukarıda anlatılan biçimde işlenmesine açık rızam ile onay
        veriyorum."
        METIN;
    }

    /**
     * Başlangıç taslağının İngilizcesi — starterText'in birebir karşılığı.
     *
     * Türkçesiyle aynı uyarı geçerli: hukuki metin DEĞİLDİR, köşeli parantezli
     * alanlar kurumun bilgileriyle doldurulmalı ve metin bir hukukçuya
     * onaylatılmalıdır. Panel kaydedilmemiş çeviriyi her yerde "taslak" diye
     * işaretler; çıktının üstünde de bunu söyler.
     *
     * Köşeli parantezler burada da Türkçe kalıyor ([saklama süresi],
     * [iletişim adresi]): doldurulacak yer olduklarını belli ediyorlar ve iki
     * metinde aynı görünmeleri, birini doldurup diğerini unutmayı zorlaştırıyor.
     *
     * İki metin birlikte değişir. Türkçesindeki bir maddeyi düzeltip buraya
     * dokunmayan bir değişiklik, İngilizce kâğıdı sessizce yanlış yapar.
     */
    public static function starterTextEn(): string
    {
        return <<<'TEXT'
        This Informed Consent Form has been prepared to inform you about your
        rights and responsibilities. It constitutes an agreement mutually
        approved by you and your psychologist.

        INFORMATION ON THE PSYCHOTHERAPY PROCESS

        - A session lasts 45-50 minutes on average.
        - Regular and punctual attendance is important and necessary in order to
          build a sound psychotherapeutic relationship.
        - If you arrive late for a session, the session can only be held for the
          remaining time. If the delay is caused by the psychologist, the
          psychologist is obliged to complete the full session time.
        - The session fee is paid before the session.
        - If you need to postpone or cancel a session, you are expected to give
          notice at least 24 hours before the session time. The session fee is
          charged for sessions cancelled within the last 24 hours.
        - New appointments are scheduled weekly or every two weeks, depending on
          the course of the psychotherapy process.
        - For online sessions, please choose a quiet and calm setting where you
          will not be disturbed and where there are no distractions.
        - Phones must be on silent during the session.
        - In order to uphold ethical principles and protect confidentiality,
          audio and/or video recording of sessions is not permitted. This rule
          applies to the session itself. If you are asked to state your consent
          verbally during an online meeting, the short recording taken is kept
          solely as the record of your consent, separately from session content
          and under the same rule of confidentiality.

        CONFIDENTIALITY AND TRUST

        - Everything discussed between the psychologist and the individual is
          confidential and is not shared with third parties.
        - The psychologist is obliged to keep and store all records kept
          throughout the psychotherapy process, including session notes, as well
          as the individual's personal information, in a manner inaccessible to
          anyone else. This rule also applies after the psychotherapeutic
          process has ended.
        - There are only 2 conditions under which the psychologist may breach
          confidentiality:
          - The most important of these is a perceived risk of the individual
            harming themselves and/or another person. In such cases the
            psychologist has to inform the relevant authorities and/or contact a
            family member of the individual.
          - The other condition is a request for information from the
            psychologist by court order in the course of legal proceedings. In
            this case the psychologist is legally obliged to comply with the
            court's decision.
        - In order to protect the individual and enable early intervention in any
          unusual situation that may arise during the psychotherapeutic process,
          the contact details of a family member are required.

        THE RECORD OF YOUR PERSONAL INFORMATION

        - Your record holds your name, contact details and date of birth, your
          appointments and notes concerning the course of your sessions. Session
          notes are considered health data in law; they are the most protected
          category of information.
        - This information is used only to schedule your appointments, to carry
          out and follow up the process, and to meet legal retention
          obligations. It is not used for any other purpose.
        - Session notes are stored encrypted and can only be viewed by the
          psychologist who wrote them. Records are kept for [saklama süresi] and
          are destroyed at the end of that period.
        - Your record is not shared with anyone, except under the two conditions
          set out in the confidentiality section above.
        - Your session notes, which constitute health data, may be processed
          only with your explicit consent under Article 6 of the Personal Data
          Protection Law (KVKK); signing this form includes that consent. Your
          other information is processed on the legal basis of the conclusion
          and performance of the agreement.
        - Under Article 11 of the KVKK you have the right to access your
          information, to request its correction or deletion, and to object to
          its processing. You may submit your requests via [iletişim adresi].

        THE PRINCIPLE OF VOLUNTARINESS

        - The individual's willingness is essential both for beginning psychotherapy
          and for the continuity of the process. It must be certain that the
          individual is seeking psychological support of their own accord and free
          of any coercion.
        - The individual has the right to end the meetings at any time.
        - Choosing to end your psychotherapeutic process is no obstacle should
          you need to begin again. You may book a new appointment at any time
          and return to your process.

        "By signing this form, I accept the rights and responsibilities set out
        above. I declare that I am taking part in the psychological support
        process voluntarily and that I am willing to work in cooperation with my
        psychologist. I give my explicit consent to the processing of my
        personal information, including my session notes, in the manner
        described above."
        TEXT;
    }
}
