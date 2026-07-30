<?php
declare(strict_types=1);

namespace Panel;

use DateTimeImmutable;
use RuntimeException;

/**
 * Seanslar arası check-in — üç sayı, isteğe bağlı tek cümle, haftada bir.
 *
 * Döngünün tamamı bu sınıftan geçiyor: jeton üretimi, jetonun doğrulanması,
 * kaydın yazılması, terapistin gördüğü geçmiş ve eğrinin geometrisi. Cron da
 * denetleyici de aynı kurallara uymak zorunda; iki yerde yazılsaydı biri
 * gevşediğinde fark edilmezdi.
 *
 * İki kural yapıyı belirliyor:
 *
 *  1. Form GİRİŞ GEREKTİRMEZ. Ölçülen tek şey doldurma oranı ve o oranın önüne
 *     konan her adım (giriş, şifre hatırlama) onu düşürür. Yetki, bağlantının
 *     kendisinde: tek kullanımlık, süreli, yalnız o görüşmeciye ait.
 *  2. Cümle özel nitelikli sağlık verisi — yalnız şifreli saklanır, seans
 *     notuyla aynı kuralda (bkz. Crypto). Sayılar şifrelenmez: eğriyi çizmek
 *     için sıralanıp toplanmaları gerekiyor ve tek başına "7" bir şey söylemez.
 */
final class Checkins
{
    /**
     * Bağlantının geçerlilik süresi (gün).
     *
     * On gün: haftalık ritimde kaçırılan bir haftayı affeder ama sıradaki
     * hatırlatmadan çok sonra doldurulan bir bağlantıyı kabul etmez — üç hafta
     * önceki haftaya ait bir ölçüm eğride gürültüdür.
     */
    public const TTL_DAYS = 10;

    /** Eğride ve tabloda gösterilen en fazla kayıt. */
    public const HISTORY = 12;

    // ── Bağlantı ────────────────────────────────────────────────

    /**
     * Yeni bir check-in bağlantısı üretir ve düz jetonu döndürür.
     *
     * Eldeki doldurulmamış bağlantı SİLİNMEZ, süresi o anda doldurulur. İkisi
     * de eski bağlantıyı çalışmaz hâle getirir ama satırın kalması şart:
     * pilotun ölçtüğü tek sayı doldurma oranı ve o oranın paydası "gönderilen
     * bağlantı" sayısıdır. Cevapsız kalanları silen bir sürüm, cevapsız
     * kaldığını da silerdi — cron'un ısrarı kesme kuralı da bu satırları sayıyor.
     */
    public static function createRequest(int $clientId): string
    {
        $token = bin2hex(random_bytes(32));

        Db::run(
            'UPDATE checkin_requests SET expires_at = NOW()
              WHERE client_id = ? AND completed_at IS NULL AND expires_at > NOW()',
            [$clientId]
        );
        Db::run(
            'INSERT INTO checkin_requests (client_id, token_hash, expires_at, created_at)
             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? DAY), NOW())',
            [$clientId, hash('sha256', $token), self::TTL_DAYS]
        );

        return $token;
    }

    /** Gönderim başarılıysa işaretlenir; boş kalması "bağlantı elle iletildi" demek. */
    public static function markSent(string $token): void
    {
        Db::run(
            'UPDATE checkin_requests SET sent_at = NOW() WHERE token_hash = ?',
            [hash('sha256', $token)]
        );
    }

    public static function link(string $token): string
    {
        // app.url panel kökünü gösterir (bkz. config.example.php) — davet
        // bağlantıları da aynı köke ekleniyor.
        return rtrim((string) Config::get('app.url'), '/') . '/check-in/' . $token;
    }

    /**
     * Üretilen bağlantıyı bir sonraki sayfada göstermek üzere saklar — e-posta
     * gitmiş olsa bile.
     *
     * Davet bağlantılarındaki aynı gerekçe (bkz. Invites::share): gönderim
     * "başarılı" dönse de ileti spam'e düşebilir, adres yanlış olabilir. Pilotun
     * ölçtüğü şey doldurma oranı olduğu için bağlantıyı elden (WhatsApp, mesaj)
     * iletebilmek kanal denemesinin en ucuz yolu.
     */
    public static function share(string $name, string $token, bool $sent): void
    {
        $_SESSION['_checkin_link'] = [
            'name' => $name,
            'url'  => self::link($token),
            'sent' => $sent,
        ];
    }

    /** Saklanan bağlantıyı bir kez okur ve siler. */
    public static function pendingLink(): ?array
    {
        $link = $_SESSION['_checkin_link'] ?? null;
        unset($_SESSION['_checkin_link']);

        return $link;
    }

    /**
     * Jetonun kaydı — durumuna bakılmadan. Denetleyici "kullanılmış", "süresi
     * dolmuş" ve "hiç yok" ayrımını yapıp kibar bir mesaj gösterebilsin diye
     * filtresiz döner; geçerlilik kararı state() ile verilir.
     *
     * @return array<string,mixed>|null
     */
    public static function request(string $token): ?array
    {
        if (!preg_match('/^[0-9a-f]{32,128}$/', $token)) {
            return null;
        }

        // Halkanın sorusu dosyaya göre uyarlanmış olabilir; aynı sorguda gelsin
        // diye burada okunuyor. Göç uygulanmadan sütun yok, o zaman da koddaki
        // varsayılan geçerli (bkz. Ecosystem::promptFor).
        $prompt = Schema::ecosystemTextsReady() ? ', c.checkin_prompt' : '';

        return Db::one(
            'SELECT r.*, c.full_name, c.status AS client_status, c.birth_date' . $prompt . '
               FROM checkin_requests r
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
        // Arşivlenen görüşmeciye artık takip yapılmıyor; eldeki eski bağlantı da
        // o anda kapanmalı, yoksa arşivleme yarım bir işlem olurdu.
        if ($request['client_status'] !== 'active') {
            return 'closed';
        }
        return 'ok';
    }

    // ── Kayıt ───────────────────────────────────────────────────

    /** 1–10 aralığına sıkıştırır — kaydırıcıdan gelen değere güvenilmez. */
    public static function score(string $raw): int
    {
        return max(1, min(10, (int) $raw));
    }

    /**
     * Check-in'i yazar ve bağlantıyı tüketir.
     *
     * Kaydın id'si de dönüyor: ekolojik işaretler (bkz. Ecosystem) bu kayda
     * bağlanıyor ve ikinci sayfa aynı gönderimin parçası.
     *
     * @return array{id:int, noteSaved:bool} noteSaved, cümle yazıldıysa
     *         şifrelenerek saklanabildi mi (yazılmadıysa true).
     */
    public static function save(array $request, int $mood, int $sleep, int $anxiety, string $note): array
    {
        $cipher    = null;
        $nonce     = null;
        $noteSaved = true;

        if ($note !== '') {
            try {
                [$cipher, $nonce] = Crypto::encrypt(mb_substr($note, 0, 2000));
            } catch (RuntimeException $e) {
                // Sayılar yine kaydedilir: şifreleme kapalı olduğu için bütün
                // check-in'i düşürmek, doldurmuş birini eli boş göndermek olurdu.
                error_log('[panel] check-in cümlesi şifrelenemedi: ' . $e->getMessage());
                $noteSaved = false;
            }
        }

        $id = Db::insert(
            'INSERT INTO checkins (client_id, request_id, mood, sleep_quality, anxiety,
                                   note_ciphertext, note_nonce, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
            [$request['client_id'], $request['id'], $mood, $sleep, $anxiety, $cipher, $nonce]
        );
        Db::run('UPDATE checkin_requests SET completed_at = NOW() WHERE id = ?', [$request['id']]);

        return ['id' => $id, 'noteSaved' => $noteSaved];
    }

    // ── Terapistin gördüğü geçmiş ───────────────────────────────

    /**
     * En eskiden yeniye sıralı geçmiş; cümleler çözülmüş olarak gelir.
     *
     * Sıra eskiden yeniye çünkü çıktısı bir eğri: zaman soldan sağa akar.
     *
     * @return list<array<string,mixed>>
     */
    public static function history(int $clientId, int $limit = self::HISTORY): array
    {
        $rows = Db::all(
            'SELECT * FROM (
                 SELECT id, mood, sleep_quality, anxiety, note_ciphertext, note_nonce, created_at
                   FROM checkins
                  WHERE client_id = ?
               ORDER BY created_at DESC
                  LIMIT ' . max(1, $limit) . '
             ) recent ORDER BY created_at',
            [$clientId]
        );

        foreach ($rows as $index => $row) {
            $rows[$index]['note']       = null;
            $rows[$index]['note_error'] = false;

            if ($row['note_ciphertext'] === null || $row['note_nonce'] === null) {
                continue;
            }
            try {
                $rows[$index]['note'] = Crypto::decrypt(
                    (string) $row['note_ciphertext'],
                    (string) $row['note_nonce']
                );
            } catch (RuntimeException) {
                $rows[$index]['note_error'] = true;
            }
        }

        return $rows;
    }

    /** Kaç kayıt var? (geçmiş penceresinden bağımsız) */
    public static function count(int $clientId): int
    {
        return (int) Db::value('SELECT COUNT(*) FROM checkins WHERE client_id = ?', [$clientId]);
    }

    /** Bekleyen (doldurulmamış, süresi geçmemiş) bağlantı var mı? */
    public static function pendingRequest(int $clientId): ?array
    {
        return Db::one(
            'SELECT * FROM checkin_requests
              WHERE client_id = ? AND completed_at IS NULL AND expires_at > NOW()
           ORDER BY created_at DESC LIMIT 1',
            [$clientId]
        );
    }

    // ── Haftalık gönderim: kime gidiyor ─────────────────────────
    //
    // "Bu hafta kime e-posta çıkacak?" sorusunun tek cevabı bu bölüm. Cron da
    // (cron/checkins.php) panelin gönderim listesi de buradan okuyor: kural iki
    // yerde yazılsaydı ekran "sırada" derken cron susabilir ve fark edilmezdi.
    //
    // Anahtarın kendisi görüşmecinin satırında (clients.checkin_auto), ayrı bir
    // tabloda değil: tek bir evet/hayır ve kaydın ömrü boyunca en çok birkaç kez
    // değişiyor; geçmişini tutmak için tablo açmak, sorulmayan bir soruya cevap
    // saklamak olurdu. Kim ne zaman değiştirdi denetim kaydında duruyor.

    /** Aynı kişiye bu kadar gün geçmeden ikinci bağlantı gitmez. */
    public const MIN_DAYS_BETWEEN = 6;

    /** Son dolan check-in'den beri bu kadar cevapsız bağlantıdan sonra susulur. */
    public const MAX_UNANSWERED = 3;

    /**
     * Bu görüşmeciye haftalık e-posta çıkıyor mu?
     *
     * Sütun yoksa (göç uygulanmadıysa) "açık": panelin bugünkü davranışı bu ve
     * deploy ile göç arasındaki boşlukta gönderim kendiliğinden durmamalı.
     */
    public static function autoEnabled(array $client): bool
    {
        return !array_key_exists('checkin_auto', $client) || (int) $client['checkin_auto'] === 1;
    }

    /** @return bool Yazılabildi mi (göç uygulanmamışsa false). */
    public static function setAuto(int $clientId, bool $on): bool
    {
        if (!Schema::checkinDeliveryReady()) {
            return false;
        }

        Db::run('UPDATE clients SET checkin_auto = ?, updated_at = NOW() WHERE id = ?', [$on ? 1 : 0, $clientId]);

        return true;
    }

    /**
     * Cron şu an koşsa bağlantı gönderilecek görüşmeciler.
     *
     * Sıralama ve alanlar cron'un ihtiyacına göre: ad, e-posta, id.
     *
     * @return list<array<string,mixed>>
     */
    public static function due(): array
    {
        // Anahtar yokken koşul da yok: göç uygulanmamış panelde herkes açık.
        $auto = Schema::checkinDeliveryReady() ? ' AND c.checkin_auto = 1' : '';

        return Db::all(
            'SELECT c.id, c.full_name, c.email
               FROM clients c
              WHERE c.status = \'active\'
                AND c.email IS NOT NULL AND c.email <> \'\'' . $auto . '
                -- Döngü başlatılmış: terapist en az bir kez bağlantı göndermiş.
                AND EXISTS (SELECT 1 FROM checkin_requests r WHERE r.client_id = c.id)
                -- Bu hafta zaten doldurmuş.
                AND NOT EXISTS (SELECT 1 FROM checkins k
                                 WHERE k.client_id = c.id
                                   AND YEARWEEK(k.created_at, 1) = YEARWEEK(NOW(), 1))
                -- Son günlerde bağlantı almış (cron günde bir koşsa da tekrar gitmesin).
                AND NOT EXISTS (SELECT 1 FROM checkin_requests r2
                                 WHERE r2.client_id = c.id
                                   AND r2.created_at > DATE_SUB(NOW(), INTERVAL ? DAY))
                -- Üst üste cevapsız kalan bağlantı sayısı sınırın altında.
                AND (SELECT COUNT(*) FROM checkin_requests r3
                      WHERE r3.client_id = c.id
                        AND r3.completed_at IS NULL
                        AND r3.created_at > COALESCE(
                              (SELECT MAX(k2.created_at) FROM checkins k2 WHERE k2.client_id = c.id),
                              \'1000-01-01 00:00:00\')) < ?
           ORDER BY c.full_name',
            [self::MIN_DAYS_BETWEEN, self::MAX_UNANSWERED]
        );
    }

    /**
     * Panelin gönderim listesi: aktif görüşmeciler, anahtarları ve neden
     * gidip gitmediği.
     *
     * Görünürlük ClientScope ile sınırlı — terapist kendi görüşmecilerini,
     * yönetici hepsini görür. Listede PUAN YOK: bu ekran yöneticiye de açık ve
     * cevaplar klinik veri (bkz. Rbac → checkin.manage).
     *
     * @return list<array<string,mixed>>
     */
    public static function roster(array $actor): array
    {
        [$scope, $params] = ClientScope::filter($actor);

        $auto = Schema::checkinDeliveryReady() ? 'c.checkin_auto' : '1';

        $rows = Db::all(
            "SELECT c.id, c.full_name, c.email, {$auto} AS checkin_auto,
                    (SELECT COUNT(*) FROM checkin_requests r WHERE r.client_id = c.id) AS request_count,
                    (SELECT MAX(r2.created_at) FROM checkin_requests r2 WHERE r2.client_id = c.id) AS last_request_at,
                    (SELECT MAX(k.created_at) FROM checkins k WHERE k.client_id = c.id) AS last_checkin_at,
                    (SELECT COUNT(*) FROM checkins k2
                      WHERE k2.client_id = c.id
                        AND YEARWEEK(k2.created_at, 1) = YEARWEEK(NOW(), 1)) AS filled_this_week,
                    (SELECT COUNT(*) FROM checkin_requests r3
                      WHERE r3.client_id = c.id
                        AND r3.completed_at IS NULL
                        AND r3.created_at > COALESCE(
                              (SELECT MAX(k3.created_at) FROM checkins k3 WHERE k3.client_id = c.id),
                              '1000-01-01 00:00:00')) AS unanswered
               FROM clients c
              WHERE c.status = 'active' AND {$scope}
           ORDER BY c.full_name",
            $params
        );

        // "Sırada" kararını listeye ikinci kez hesaplatmıyoruz: cron ne
        // gönderecekse o. Geri kalan durumlar yalnız bunun AÇIKLAMASI.
        $dueIds = array_map(static fn (array $row): int => (int) $row['id'], self::due());

        foreach ($rows as $index => $row) {
            $rows[$index]['auto']  = self::autoEnabled($row);
            $rows[$index]['state'] = self::deliveryState($row, in_array((int) $row['id'], $dueIds, true));
        }

        return $rows;
    }

    /**
     * Bir satırın gönderim durumu — sırası önemli: önce insanın verdiği karar
     * (anahtar), sonra kaydın eksiği, sonra döngünün kendi ritmi.
     *
     * @param  array<string,mixed> $row  roster() satırı
     * @param  bool $due                 cron şu an koşsa bu kişiye gider mi
     * @return array{key:string, label:string, tone:string, detail:string}
     */
    public static function deliveryState(array $row, bool $due): array
    {
        $state = static fn (string $key, string $label, string $tone, string $detail): array
            => ['key' => $key, 'label' => $label, 'tone' => $tone, 'detail' => $detail];

        if (!self::autoEnabled($row)) {
            return $state('kapali', 'Kapalı', 'stop',
                'Haftalık e-posta gitmiyor. Terapist görüşmeci sayfasından elle bağlantı gönderebilir.');
        }
        if (trim((string) ($row['email'] ?? '')) === '') {
            return $state('eposta_yok', 'E-posta yok', 'stop',
                'Kayıtta adres yok — bağlantı üretilebilir ama elden iletilmesi gerekir.');
        }
        if ((int) $row['request_count'] === 0) {
            return $state('baslamadi', 'Başlamadı', 'neutral',
                'Döngü henüz başlamadı. İlk bağlantıyı terapist görüşmeci sayfasından gönderir; cron sonrasını sürdürür.');
        }
        if ((int) $row['unanswered'] >= self::MAX_UNANSWERED) {
            return $state('susuldu', 'Susuldu', 'stop',
                'Üst üste ' . (int) $row['unanswered'] . ' bağlantı cevapsız kaldı; ısrar edilmiyor. '
                . 'Elle gönderilen bir bağlantı doldurulunca gönderim kendiliğinden sürer.');
        }
        if ($due) {
            return $state('sirada', 'Sırada', 'go',
                'Cron bu koşuda bu kişiye bağlantı gönderir.');
        }
        if ((int) $row['filled_this_week'] > 0) {
            return $state('dolduruldu', 'Bu hafta doldu', 'done',
                'Bu haftanın check-in\'i kaydedildi; ikinci ileti çıkmaz.');
        }
        if ($row['last_request_at'] !== null
            && strtotime((string) $row['last_request_at']) > time() - self::MIN_DAYS_BETWEEN * 86400) {
            return $state('bekliyor', 'Bağlantı bekliyor', 'done',
                'Son ' . self::MIN_DAYS_BETWEEN . ' gün içinde bağlantı gönderildi, henüz doldurulmadı.');
        }

        return $state('acik', 'Açık', 'neutral',
            'Gönderim açık; sıradaki cron koşusunda değerlendirilir.');
    }

    // ── Eğrinin geometrisi ──────────────────────────────────────
    //
    // Üç ölçü tek eksene çakıştırılmıyor, üç ayrı satıra çiziliyor: ruh hali ve
    // uykuda yüksek iyidir, kaygıda yüksek kötüdür. Aynı kutuda üst üste çizilen
    // üç eğri, yükselen çizginin iyi mi kötü mü olduğunu okunmaz hâle getirir.
    // Ayrı satırlar aynı zaman eksenini ve aynı 1–10 ölçeğini paylaşır, bu yüzden
    // "uyku düştü, kaygı çıktı" hâlâ tek bakışta görülür.
    //
    // Ölçüler sabit piksel: SVG esnetilirse 2px çizgi ve 8px nokta ölçekle
    // bozulur. Daralan ekranda takvimdeki çözüm uygulanıyor — kutu kendi içinde
    // yatay kayar, sayfa kaymaz (bkz. panel.css → .cal-scroll).

    public const VIEW_W = 560;
    public const VIEW_H = 76;

    private const PLOT_LEFT   = 30;
    private const PLOT_RIGHT  = 516;   // sağda son değerin etiketine yer kalıyor
    private const PLOT_TOP    = 12;
    private const PLOT_BOTTOM = 62;

    /**
     * Bir ölçünün eğrisi.
     *
     * @param  list<array<string,mixed>> $rows  history() çıktısı (eskiden yeniye)
     * @param  string $field                    mood | sleep_quality | anxiety
     * @return array{points:string, dots:list<array{x:float,y:float,value:int,label:string}>, last:?array{x:float,y:float,value:int,label:string}, top:float, mid:float, bottom:float, left:int, right:int}
     */
    public static function curve(array $rows, string $field): array
    {
        $stamps = array_map(
            static fn (array $row): int => (int) strtotime((string) $row['created_at']),
            $rows
        );
        $first = $stamps === [] ? 0 : min($stamps);
        $last  = $stamps === [] ? 0 : max($stamps);
        $span  = max(1, $last - $first);

        $dots   = [];
        $points = [];
        foreach ($rows as $index => $row) {
            $value = self::score((string) $row[$field]);

            // Tek kayıtta zaman ekseni yok: nokta sağ uca konur, "buradan
            // başladı" demek için yeterli.
            $x = count($rows) === 1
                ? self::PLOT_RIGHT
                : self::PLOT_LEFT + ($stamps[$index] - $first) / $span * (self::PLOT_RIGHT - self::PLOT_LEFT);

            $dot = [
                'x'     => round($x, 1),
                'y'     => self::y($value),
                'value' => $value,
                'label' => dt((string) $row['created_at'], 'd.m.Y') . ' — ' . $value . '/10',
            ];

            $dots[]   = $dot;
            $points[] = $dot['x'] . ',' . $dot['y'];
        }

        return [
            'points' => implode(' ', $points),
            'dots'   => $dots,
            'last'   => $dots === [] ? null : $dots[count($dots) - 1],
            // Izgara ve eksen etiketleri de buradan okunuyor; görünüm geometriyi
            // ikinci kez hesaplamasın.
            'top'    => self::y(10),
            'mid'    => self::y(5.5),
            'bottom' => self::y(1),
            'left'   => self::PLOT_LEFT,
            'right'  => self::PLOT_RIGHT,
        ];
    }

    private static function y(int|float $value): float
    {
        $ratio = ($value - 1) / 9;
        return round(self::PLOT_BOTTOM - $ratio * (self::PLOT_BOTTOM - self::PLOT_TOP), 1);
    }

    /** Eğri satırlarının başlıkları ve ölçek yönü — görünüm bunu tekrar yazmasın. */
    public const MEASURES = [
        'mood'          => ['label' => 'Ruh hali', 'low' => 'çok kötü',  'high' => 'çok iyi'],
        'sleep_quality' => ['label' => 'Uyku',     'low' => 'çok kötü',  'high' => 'çok iyi'],
        'anxiety'       => ['label' => 'Kaygı',    'low' => 'hiç yok',   'high' => 'çok yoğun'],
    ];

    /** Soruların varsayılan metni. Düzenlenmiş hâli için [questions]. */
    public const QUESTIONS = [
        'mood'          => 'Bu hafta genel olarak ruh hâlin nasıldı?',
        'sleep_quality' => 'Uykun nasıldı?',
        'anxiety'       => 'Kaygı düzeyin ne kadardı?',
    ];

    /** Düzenlenmiş soru metinlerinin ayar anahtarı öneki. */
    private const QUESTION_SETTING = 'checkin_question_';

    /**
     * Formda gösterilecek soru metinleri.
     *
     * Cümlenin nasıl kurulduğu klinik bir tercih — soruyu soran terapist, panel
     * değil. Bu yüzden metin ayarlardan düzenlenebilir; koddaki QUESTIONS
     * varsayılan olarak durur ve boşaltılan alan ona geri döner.
     *
     * Değişen yalnız metin: alan adları ve dolayısıyla ölçeğin yönü sabit.
     * `anxiety` ters ölçeklidir (yüksek değer kötü) ve eğri bunu böyle çizer;
     * cümleyi ters çeviren bir düzenleme veriyi sessizce bozardı. Düzenleme
     * ekranı bu yüzden her sorunun altında yönünü ayrıca yazıyor.
     *
     * @return array<string,string>
     */
    public static function questions(): array
    {
        $questions = [];
        foreach (self::QUESTIONS as $field => $default) {
            $custom = trim((string) Settings::get(self::QUESTION_SETTING . $field, ''));
            $questions[$field] = $custom !== '' ? $custom : $default;
        }
        return $questions;
    }

    /**
     * Soru metinlerini kaydeder. Varsayılana eşit ya da boş bırakılan alan
     * ayarlardan silinir — böylece varsayılan ileride değişirse o soru
     * kendiliğinden yeni metne döner, eski kopyasında donup kalmaz.
     *
     * @param array<string,string> $input
     */
    public static function saveQuestions(array $input): void
    {
        foreach (self::QUESTIONS as $field => $default) {
            $text = trim(mb_substr((string) ($input[$field] ?? ''), 0, 200));
            Settings::set(self::QUESTION_SETTING . $field, $text === $default ? '' : $text);
        }
    }

    /**
     * Şeridin sütun başlığı: yalnız haftanın ilk günü, `27.07` biçiminde.
     *
     * Tam aralık ("27 Temmuz – 2 Ağustos 2026") burada sütunu on katına
     * çıkarıyor ve hücreler kareden bandımsı bir şeye dönüşüyordu. Şeritte
     * okunması gereken şey hafta adı değil, işaretlerin dizilişi; tam etiket
     * başlığın `title`'ında ve alttaki tabloda zaten duruyor.
     */
    public static function weekShort(string $sqlDateTime): string
    {
        try {
            $date = new DateTimeImmutable($sqlDateTime);
        } catch (\Exception) {
            return '—';
        }

        return $date->modify('monday this week')->format('d.m');
    }

    /**
     * Haftanın etiketi — tabloda tarihin yanında durur. Terapist "hangi hafta"
     * diye düşünüyor, "hangi gün" diye değil.
     */
    public static function weekLabel(string $sqlDateTime): string
    {
        try {
            $date = new DateTimeImmutable($sqlDateTime);
        } catch (\Exception) {
            return '—';
        }
        $monday = $date->modify('monday this week');

        return tr_range_label($monday->format('Y-m-d'), $monday->modify('+6 days')->format('Y-m-d'));
    }
}
