<?php
declare(strict_types=1);

namespace Panel\Controllers;

use Panel\Audit;
use Panel\Auth;
use Panel\Checkins;
use Panel\ClientScope;
use Panel\Crypto;
use Panel\Db;
use Panel\Ecosystem;
use Panel\Scales;
use Panel\Schema;
use Panel\Settings;
use Panel\View;

/**
 * Seanslar arası check-in formu — panelin GİRİŞ GEREKTİRMEYEN tek ekranı.
 *
 * Yetki bağlantının kendisinde: tek kullanımlık, süreli, tek bir görüşmeciye
 * ait 256 bitlik bir jeton. Bunun önüne giriş ekranı koymak teknik olarak
 * kolaydı; döngünün ölçtüğü tek şey doldurma oranı olduğu için pahalı olurdu.
 * Şifre hatırlamak zorunda kalan biri formu telefonda otuz saniyede doldurmaz.
 *
 * Bu yüzden buradaki her yanıt bilinçli olarak az şey söyler: geçersiz bir
 * bağlantı "bu bağlantı geçerli değil" der, kimin bağlantısı olduğunu ya da
 * neden geçersiz olduğunu ele vermez. Sayfada görüşmecinin adı yalnız jeton
 * geçerliyken görünür.
 */
final class CheckinController
{
    public function form(string $token): void
    {
        if (!Schema::checkinsReady()) {
            $this->closed('Bu bağlantı şu anda kullanılamıyor', 'Sistem henüz hazır değil. Lütfen merkezimizle iletişime geçin.');
            return;
        }

        $request = Checkins::request($token);
        $state   = Checkins::state($request);

        if ($state !== 'ok') {
            $this->explain($state);
            return;
        }

        // İkinci sayfa yalnız göç uygulanmışsa çizilir. Deploy ile migration
        // arasındaki boşlukta form çökmez, eski hâliyle çalışır.
        $age = Ecosystem::ageFrom($request['birth_date'] === null ? null : (string) $request['birth_date']);

        View::render('checkins/form', [
            'title'     => 'Haftalık check-in',
            'token'     => $token,
            'firstName' => $this->firstName((string) $request['full_name']),
            // Şifreleme kapalıysa cümle alanı hiç gösterilmez: saklanamayacak
            // bir şeyi yazdırmak, yazanın güvenine ihanet eder.
            'noteOpen'  => Crypto::available(),
            'domains'   => Schema::ecosystemReady()
                ? Ecosystem::openFor((int) $request['client_id'], $age)
                : [],
            // Soru dosyaya göre uyarlanmış olabilir: ebeveyn dosyasında
            // "çocuğunun sırtını", ergen dosyasında "senin sırtını".
            'prompt'    => Ecosystem::promptFor($request),
        ], 'checkin_layout');
    }

    public function submit(string $token): void
    {
        if (!Schema::checkinsReady()) {
            $this->closed('Bu bağlantı şu anda kullanılamıyor', 'Sistem henüz hazır değil. Lütfen merkezimizle iletişime geçin.');
            return;
        }

        $request = Checkins::request($token);
        $state   = Checkins::state($request);

        if ($state !== 'ok') {
            $this->explain($state);
            return;
        }

        // Cevaplar açık ölçeklere göre toplanıyor, sabit üç alana göre değil.
        // `olcek[anahtar]` yeni formun adlandırması; tek başına `anahtar` ise
        // bu sürümden önce açılmış, hâlâ bir sekmede duran formun.
        $posted = (array) ($_POST['olcek'] ?? []);
        $scores = [];
        foreach (Scales::all(true) as $scale) {
            $key          = $scale['key'];
            $scores[$key] = Checkins::score((string) ($posted[$key] ?? post($key)));
        }

        $saved = Checkins::save(
            $request,
            $scores,
            Crypto::available() ? post('note') : ''
        );

        // İkinci sayfa: hiçbir alana dokunulmamış olması da geçerli bir cevap.
        // Bu yüzden burada doğrulama yok, yalnız yazma var.
        Ecosystem::saveMarks($saved['id'], (array) ($_POST['alan'] ?? []));
        $eventSaved = Ecosystem::saveEvent(
            $saved['id'],
            post('olay_var') !== '',
            Crypto::available() ? post('olay') : ''
        );

        // Kayıt tutulur ama İÇERİĞİ asla loglanmaz — seans notundaki kural.
        // Aktör boş: bu isteği yapan kişi panele giriş yapmış değil.
        Audit::log('checkin.submitted', 'client', (int) $request['client_id']);

        if (!$saved['noteSaved'] || !$eventSaved) {
            flash('warning', 'Puanların kaydedildi, ama yazdığın metin güvenli biçimde '
                . 'saklanamadı ve kaydedilmedi. Söylemek istediğin bir şey varsa seansta paylaşabilirsin.');
        }

        redirect('/check-in/tesekkurler');
    }

    /** Gönderimden sonraki teşekkür sayfası — yenilenince form tekrar gönderilmesin. */
    public function thanks(): void
    {
        View::render('checkins/done', ['title' => Checkins::text('tesekkur_baslik')], 'checkin_layout');
    }

    // ── Sorular ve gönderim (panel içi, giriş gerektirir) ────────
    //
    // Yetki `checkin.manage` ve bu, eğriyi okuma yetkisinden (checkin.view.own)
    // ayrıdır: cevap sağlık verisidir, soru değil. Sorunun metni ile haftalık
    // e-postanın kime çıktığı merkezin işleyişine dair kararlar, bu yüzden
    // yöneticide de var. Bu ekranda hiçbir puan, hiçbir cümle görünmez —
    // yalnız ad, adres ve gönderim durumu.

    public function questions(): void
    {
        $actor = Auth::requirePermission('checkin.manage');

        // Göç uygulanmadan gönderim listesi çizilmez: liste checkin_requests
        // tablosunu okuyor ve olmayan tabloya sorulan soru ekranı çökertirdi.
        $ready = Schema::checkinsReady();

        View::render('checkins/questions', [
            'title'      => 'Haftalık check-in',
            'questions'  => Checkins::questions(),
            'defaults'   => Checkins::QUESTIONS,
            'measures'   => Checkins::measures(),
            'measureDefaults' => Checkins::MEASURES,
            // Ölçek listesi düzenlenebilir mi (010) — değilse ekran koddaki üç
            // soruyu gösterir ve neyin eksik olduğunu söyler.
            'scalesReady' => Schema::checkinScalesReady(),
            'scales'      => Scales::all(),
            'scaleMax'    => Scales::MAX,
            // Formun geri kalan metinleri: giriş, cümle alanı, halka, teşekkür.
            'texts'      => Checkins::texts(),
            'textFields' => Checkins::TEXTS,
            'ready'      => $ready,
            // Anahtar yalnız sütun varsa çevrilebilir; yoksa liste bilgi
            // amaçlı çizilir ve neyin eksik olduğunu söyler.
            'switchable' => Schema::checkinDeliveryReady(),
            'roster'     => $ready ? Checkins::roster($actor) : [],
            // Listedeki "Sırada" rozetinin karşılığı cron'un koşması. Cron hiç
            // kurulmamışsa ya da anahtar kapalıysa o rozet bir söz veriyor ve
            // kimse tutmuyor: gönderimin durumu bu yüzden listenin başında.
            'cron'       => $this->cronStatus(),
        ]);
    }

    /**
     * Haftalık gönderimi yapan cron'un durumu — Sistem ekranındaki üç değerin
     * bu ekrana ait olan kısmı (bkz. SystemController::checkinStatus).
     *
     * @return array{enabled:bool,lastRun:?string,lastResult:?string,stale:bool}
     */
    private function cronStatus(): array
    {
        $lastRun = Settings::get('checkin_last_run') ?: null;

        return [
            'enabled'    => Settings::get('checkins_enabled', '1') === '1',
            'lastRun'    => $lastRun,
            'lastResult' => Settings::get('checkin_last_result') ?: null,
            // Haftalık bir işin sekiz gündür koşmaması "çalışıyor" değildir.
            // Tarihi yazıp yeşil bir rozet göstermek, durmuş bir cron'u aylarca
            // görünmez kılar: ekranda hiçbir şey yanlış görünmez, yalnız kimseye
            // e-posta çıkmaz.
            'stale'      => $lastRun !== null && strtotime($lastRun) < time() - 8 * 86400,
        ];
    }

    /**
     * Görüşmecinin göreceği form — panelden, gönderimsiz.
     *
     * Metni yazan kişi bugüne kadar sonucu ancak gerçek bir bağlantı üretip
     * telefonda açarak görebiliyordu. Ters yazılmış bir uç ("kaygın ne kadar
     * azdı?"), adı boş bırakılmış bir ölçek ya da fazla uzamış bir form ilk kez
     * görüşmecide görünüyordu; düzeltmek de o hafta için geç oluyordu.
     *
     * Ekran formun KENDİ görünümünü çiziyor, kopyasını değil. İki şablon
     * olsaydı önizleme birkaç sürüm sonra sessizce gerçeği göstermeyi bırakır
     * ve yanlış bir güven verirdi.
     */
    public function preview(): void
    {
        $actor = Auth::requirePermission('checkin.manage');

        View::render('checkins/form', [
            'title'     => 'Önizleme — haftalık check-in',
            // Şablonun tek farkı bu bayrak: gönderim yok, jeton yok.
            'preview'   => true,
            'token'     => '',
            // Uydurma bir görüşmeci adı yerine önizleyenin kendi adı: sayfada
            // gerçek bir dosyaya ait hiçbir şey yok ama selamlamanın nereye
            // oturduğu görünüyor.
            'firstName' => $this->firstName((string) $actor['full_name']),
            'noteOpen'  => Crypto::available(),
            // Dosyaya uyarlanmamış hâl: sözlüğün varsayılan açık alanları.
            // Yaş verilmiyor, çünkü önizlemenin bir dosyası yok.
            'domains'   => Schema::ecosystemReady() ? Ecosystem::openFor(0) : [],
            'prompt'    => Ecosystem::PROMPT,
        ], 'checkin_layout');
    }

    /**
     * Haftalık e-posta kimlere gidecek — saveQuestions'ın bir parçası.
     *
     * Kendi rotası yok: metinlerle aynı formdan, aynı gönderimde geliyor.
     * Ayrı bir POST olduğu sürece iki "Kaydet" birbirinin kaydedilmemiş
     * değişikliklerini siliyordu.
     *
     * İşaretlenmemiş her satır kapatılır — ama YALNIZ bu kullanıcının gördüğü
     * satırlar. Liste ClientScope ile sınırlı olduğu için terapistin kaydettiği
     * form, görmediği bir görüşmecinin anahtarını sessizce kapatamaz.
     *
     * @return int Anahtarı değişen görüşmeci sayısı
     */
    private function saveRecipients(array $actor): int
    {
        // Göç uygulanmadıysa kutucuklar ekranda `disabled` çiziliyor ve hiçbiri
        // gönderilmiyor. O hâlde boş bir `alici` dizisi "hepsini kapat" demek
        // olurdu — bu yüzden liste burada hiç okunmuyor.
        if (!Schema::checkinDeliveryReady()) {
            return 0;
        }

        $wanted  = array_map('intval', array_keys((array) ($_POST['alici'] ?? [])));
        $changed = 0;

        foreach (Checkins::roster($actor) as $row) {
            $on = in_array((int) $row['id'], $wanted, true);
            if ($on === (bool) $row['auto']) {
                continue;
            }

            Checkins::setAuto((int) $row['id'], $on);
            // Bir kişiye e-posta gitmesinin durdurulması sonradan "neden
            // doldurmuyor?" diye sorulduğunda ilk bakılacak yer; izi kalmalı.
            Audit::log($on ? 'checkin.auto_on' : 'checkin.auto_off', 'client', (int) $row['id']);
            $changed++;
        }

        return $changed;
    }

    /**
     * Bir görüşmecide hangi alanların, hangi kelimelerle sorulacağı.
     *
     * Yetki `checkin.manage`: soruyu yazan da, alanın adını ailenin kullandığı
     * ada çeviren de çoğu zaman merkezi yöneten kişi. Ekranı terapiste kapatmak
     * yerine yöneticiye açmak, uyarlamanın hiç yapılmamasını engelliyor.
     *
     * Bedeli açık: hangi alanların açık olduğu ("Bakım düzeni", "Kardeş") o aile
     * hakkında bir şey söyler ve bu ekran onu yöneticiye gösterir. Ölçümler
     * göstermiyor — puan, cümle ve eğri `checkin.view.own` ile terapistte kalıyor.
     *
     * Kaydın görünürlüğü ayrıca ClientScope ile sınırlı.
     */
    public function domains(int $clientId): void
    {
        $actor  = Auth::requirePermission('checkin.manage');
        $client = $this->scopedClient($clientId, $actor);

        if ($client === null) {
            View::error(404, 'Görüşmeci bulunamadı');
            return;
        }
        if (!Schema::ecosystemReady()) {
            flash('error', 'Bu ekran için bekleyen veritabanı güncellemesi var.');
            redirect('/danisanlar/' . $clientId);
        }

        $age = Ecosystem::ageFrom($client['birth_date'] === null ? null : (string) $client['birth_date']);

        View::render('checkins/domains', [
            'title'    => 'Sorulan alanlar',
            'client'   => $client,
            'age'      => $age,
            // Ekran hem açık/kapalı durumunu hem o dosyaya uyarlanmış metinleri
            // gösteriyor; ikisi de aynı satırdan geliyor (bkz. Ecosystem::form).
            'fields'   => Ecosystem::form($clientId, $age),
            'prompt'   => Ecosystem::promptFor($client),
            'promptDefault' => Ecosystem::PROMPT,
            // Metin uyarlaması göçe bağlı; kutular yalnız yazılabilecekse çizilir.
            'editable' => Schema::ecosystemTextsReady(),
        ]);
    }

    public function saveDomains(int $clientId): void
    {
        $actor  = Auth::requirePermission('checkin.manage');
        $client = $this->scopedClient($clientId, $actor);

        if ($client === null) {
            View::error(404, 'Görüşmeci bulunamadı');
            return;
        }

        $age = Ecosystem::ageFrom($client['birth_date'] === null ? null : (string) $client['birth_date']);
        Ecosystem::saveDomains($clientId, (array) ($_POST['alan'] ?? []), $age);
        Ecosystem::savePrompt($clientId, post('soru'));

        // İÇERİK loglanmaz: uyarlanmış ad bir ailenin hayatından bir ayrıntı
        // olabilir ("Babanın nöbetleri"). Kimin ne zaman değiştirdiği yeter.
        Audit::log('checkin.domains_updated', 'client', $clientId);
        flash('success', 'Sorulan alanlar ve metinler güncellendi. Bundan sonra üretilen bağlantılarda geçerli olur.');
        redirect('/danisanlar/' . $clientId);
    }

    public function saveQuestions(): void
    {
        $actor = Auth::requirePermission('checkin.manage');

        if (Schema::checkinScalesReady()) {
            // Ölçekler artık veri: sıra, ad, cümle, uçlar, yön ve açık/kapalı
            // tek listeden geliyor (bkz. Scales::save).
            Scales::save(array_values((array) ($_POST['olcek'] ?? [])));
        } else {
            // Göç uygulanmadan yalnız koddaki üç sorunun metni düzenlenebilir.
            $input = [];
            foreach (array_keys(Checkins::QUESTIONS) as $field) {
                $input[$field] = post($field);
            }
            Checkins::saveQuestions($input);
            Checkins::saveMeasures((array) ($_POST['olcek'] ?? []));
        }

        // Formun geri kalan metinleri aynı formda geliyor: tek "Kaydet", tek
        // denetim kaydı — ekran tek bir metin.
        Checkins::saveTexts((array) ($_POST['metin'] ?? []));

        // Gönderim listesi de aynı gönderime katıldı. Sayfa iki ayrı form ve
        // iki ayrı "Kaydet" taşıdığı sürece biri her zaman ötekini sessizce
        // çöpe atma riski taşıyordu; araya konan uyarı bunu haber veriyordu
        // ama ortadan kaldırmıyordu. Tek form, tek düğme, risk yok.
        $changed = $this->saveRecipients($actor);

        // İçerik loglanmaz; kimin ne zaman değiştirdiği yeter. Soru metni
        // klinik veri değil ama değişimi cevapların anlamını kaydırır, bu
        // yüzden iz kalması gerekiyor.
        Audit::log('checkin.questions_updated', 'settings', null, ['aktor' => (int) $actor['id']]);

        $message = 'Check-in metinleri kaydedildi. Bundan sonra üretilen bağlantılar yeni metni gösterir.';
        if ($changed > 0) {
            $message .= ' ' . $changed . ' görüşmecide haftalık gönderim güncellendi.';
        }

        flash('success', $message);
        redirect('/check-in-sorulari');
    }

    // ── Yardımcılar ─────────────────────────────────────────────

    /**
     * Kapanmış bağlantıların hepsi kibar ve kısa: kişi bir şeyi yanlış
     * yapmadı, bağlantının ömrü doldu. "Hata" dili burada yanlış olurdu.
     */
    private function explain(string $state): void
    {
        [$title, $message] = match ($state) {
            'used' => [
                'Bu check-in zaten dolduruldu',
                'Teşekkürler — bu haftanın check-in\'i kaydedildi. Bir sonraki hatırlatmada yeni bir bağlantı alacaksın.',
            ],
            'expired' => [
                'Bu bağlantının süresi doldu',
                'Bağlantılar ' . Checkins::TTL_DAYS . ' gün geçerli. Bir sonraki hatırlatmada yeni bir bağlantı alacaksın; '
                . 'daha önce doldurmak istersen merkezimize yazabilirsin.',
            ],
            'closed' => [
                'Bu bağlantı artık geçerli değil',
                'Takibin şu anda açık görünmüyor. Sorusu olan biri varsa merkezimizle iletişime geçebilir.',
            ],
            default => [
                'Bu bağlantı geçerli değil',
                'Bağlantı eksik kopyalanmış olabilir. E-postadaki adresin tamamını kullanmayı deneyin.',
            ],
        };

        $this->closed($title, $message);
    }

    private function closed(string $title, string $message): void
    {
        View::render('checkins/closed', [
            'title'   => $title,
            'heading' => $title,
            'message' => $message,
        ], 'checkin_layout');
    }

    /**
     * Kayıt bu terapistin görüş alanında mı? Yetki kararı ClientScope'ta,
     * burada yalnız uygulanıyor — ikinci bir görünürlük kuralı yazmamak için.
     */
    private function scopedClient(int $id, array $actor): ?array
    {
        [$scope, $params] = ClientScope::filter($actor);
        array_unshift($params, $id);

        return Db::one("SELECT c.* FROM clients c WHERE c.id = ? AND {$scope} LIMIT 1", $params);
    }

    private function firstName(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];
        return (string) ($parts[0] ?? '');
    }
}
