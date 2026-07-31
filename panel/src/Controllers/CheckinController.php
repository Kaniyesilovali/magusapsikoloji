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
use Panel\Schema;
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

        $saved = Checkins::save(
            $request,
            Checkins::score(post('mood')),
            Checkins::score(post('sleep_quality')),
            Checkins::score(post('anxiety')),
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
        View::render('checkins/done', ['title' => 'Teşekkürler'], 'checkin_layout');
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
            'measures'   => Checkins::MEASURES,
            'ready'      => $ready,
            // Anahtar yalnız sütun varsa çevrilebilir; yoksa liste bilgi
            // amaçlı çizilir ve neyin eksik olduğunu söyler.
            'switchable' => Schema::checkinDeliveryReady(),
            'roster'     => $ready ? Checkins::roster($actor) : [],
        ]);
    }

    /**
     * Haftalık e-posta kimlere gidecek.
     *
     * İşaretlenmemiş her satır kapatılır — ama YALNIZ bu kullanıcının gördüğü
     * satırlar. Liste ClientScope ile sınırlı olduğu için terapistin kaydettiği
     * form, görmediği bir görüşmecinin anahtarını sessizce kapatamaz.
     */
    public function saveRecipients(): void
    {
        $actor = Auth::requirePermission('checkin.manage');

        if (!Schema::checkinDeliveryReady()) {
            flash('error', 'Gönderim anahtarı için bekleyen bir veritabanı güncellemesi var. '
                . 'Sistem ekranından uygulanınca bu liste kaydedilebilir.');
            redirect('/check-in-sorulari');
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

        flash(
            'success',
            $changed === 0
                ? 'Gönderim listesinde değişiklik yok.'
                : $changed . ' görüşmecide haftalık gönderim güncellendi.'
        );
        redirect('/check-in-sorulari');
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

        $input = [];
        foreach (array_keys(Checkins::QUESTIONS) as $field) {
            $input[$field] = post($field);
        }
        Checkins::saveQuestions($input);

        // İçerik loglanmaz; kimin ne zaman değiştirdiği yeter. Soru metni
        // klinik veri değil ama değişimi cevapların anlamını kaydırır, bu
        // yüzden iz kalması gerekiyor.
        Audit::log('checkin.questions_updated', 'settings', null, ['aktor' => (int) $actor['id']]);

        flash('success', 'Check-in soruları kaydedildi. Bundan sonra üretilen bağlantılar yeni metni gösterir.');
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
