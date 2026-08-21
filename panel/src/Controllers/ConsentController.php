<?php
declare(strict_types=1);

namespace Panel\Controllers;

use Panel\Audit;
use Panel\Auth;
use Panel\ClientScope;
use Panel\Consent;
use Panel\Db;
use Panel\Rbac;
use Panel\Schema;
use Panel\Settings;
use Panel\View;

/**
 * Onam formu — bireye imzalatılan tek metin.
 *
 * Kurumda iki ayrı kâğıt dolaşıyordu: psikoloğun süreç anlaşması (seans süresi,
 * iptal, gizlilik, gönüllülük) ve panelin KVKK aydınlatma metni. Birey aynı
 * masada iki kez imza atıyordu ve ikisi de aynı şeyi söylüyordu: "bu bilgiler
 * burada kalır". Tek forma indirildi; KVKK bölümleri, gizlilik başlığının
 * altına, aynı dille yazılmış bir alt bölüm olarak girdi.
 *
 * Metin veritabanında sürümlü tutulur; birey kaydındaki `consent_version`
 * hangi metne onay verildiğini gösterir. Bu yüzden metin değişince sürüm de
 * değişmek ZORUNDA — aksi hâlde iki farklı metne aynı sürüm numarasıyla onam
 * verilmiş görünür ve kayıt ispat değerini kaybeder.
 *
 * Metnin, sürümün ve verilmiş onayların kendisi Consent sınıfında; burada
 * yalnız ekranlar var. Üç tanesi (publicForm, publicApprove, publicThanks)
 * GİRİŞ GEREKTİRMEZ: birey metni seanstan önce, evinde okuyor.
 */
final class ConsentController
{
    public function index(): void
    {
        $actor = Auth::requirePermission('consent.manage');

        View::render('consent/index', [
            'title'   => 'Onam formu',
            'text'    => Consent::currentText(),
            'version' => Consent::currentVersion(),
            'isDraft' => Consent::isDraft(),
            // İngilizce yalnız çıktı için: aynı metnin, aynı sürümün çevirisi.
            'textEn'    => Consent::currentTextEn(),
            'isDraftEn' => Consent::isDraftEn(),
            // Türkçe metin çeviriden sonra değişmişse çeviri eskimiştir; ekran
            // bunu söylemezse İngilizce kâğıt sessizce yanlış basılır.
            'staleEn'   => Consent::translationStale(),
            'versionEn' => Consent::translatedVersion(),
            'signed'  => (int) Db::value('SELECT COUNT(*) FROM clients WHERE consent_at IS NOT NULL'),
            // Tiki işaretleyip henüz seansta kapanmamış kayıtlar: metnin
            // okunduğu ama imzanın beklendiği aralık. Onam ekranının kendisi
            // bu aralığı göstermezse kimse bakmıyor.
            'online'  => Schema::consentReady()
                ? (int) Db::value(
                    'SELECT COUNT(DISTINCT r.client_id) FROM consent_records r
                       JOIN clients c ON c.id = r.client_id
                      WHERE r.method = \'online\' AND r.revoked_at IS NULL AND c.consent_at IS NULL'
                )
                : 0,
            'actor'   => $actor,
        ]);
    }

    public function update(): void
    {
        Auth::requirePermission('consent.manage');

        $text    = trim((string) ($_POST['consent_text'] ?? ''));
        $textEn  = trim((string) ($_POST['consent_text_en'] ?? ''));
        $version = post('consent_version');

        $currentText    = Consent::currentText();
        $currentTextEn  = Consent::currentTextEn();
        $currentVersion = Consent::currentVersion();

        // Hata hâlinde yazılan metin geri veriliyor (bkz. old(), views/consent/index.php).
        // Eskiden gönderim reddedilince textarea kayıttaki metinle yeniden
        // doluyordu: sürüm numarası yüzünden geri çevrilen bir düzeltme,
        // yarım saatlik yazıyı da götürüyordu. Kaydedilmeyen bir metni geri
        // vermemek, "önce sürümü yükselt" kuralını cezaya çeviriyordu.
        $input = ['consent_text' => $text, 'consent_text_en' => $textEn, 'consent_version' => $version];

        if (mb_strlen($text) < 100) {
            remember_input($input, ['consent_text' => 'Metin en az birkaç paragraf olmalı.']);
            flash('error', 'Metin çok kısa görünüyor. Onam formu en az birkaç paragraf olmalı.');
            redirect('/onam-formu');
        }
        // Çeviri boş bırakılabilir — kurumda İngilizce çıktı gerekmiyor olabilir.
        // Yarım bir çeviri ise kaydedilmiyor: masaya konan kâğıt, okunmayan bir
        // dilde eksik bir metin olamaz.
        if ($textEn !== '' && mb_strlen($textEn) < 100) {
            remember_input($input, ['consent_text_en' => 'Çeviri en az birkaç paragraf olmalı.']);
            flash('error', 'İngilizce metin çok kısa görünüyor. Çeviriyi tamamlayın ya da alanı tamamen boş bırakın.');
            redirect('/onam-formu');
        }
        if ($version === '' || mb_strlen($version) > 20) {
            remember_input($input, ['consent_version' => 'Sürüm numarası boş bırakılamaz (en fazla 20 karakter).']);
            flash('error', 'Sürüm numarası boş bırakılamaz (en fazla 20 karakter).');
            redirect('/onam-formu');
        }

        // Sürüm, verilmiş onamların hangi metne ait olduğunu gösteren tek bağdır.
        if ($text !== $currentText && $version === $currentVersion) {
            $next = Consent::nextVersion($currentVersion);
            // Önerilen sürüm alana yazılı gelir: kural yerinde duruyor, ama
            // uygulaması tek tuş — sürümü yükseltmek isteyen kişi yeniden
            // kaydete basıyor, istemeyen sayıyı kendisi değiştiriyor.
            remember_input(
                ['consent_text' => $text, 'consent_text_en' => $textEn, 'consent_version' => $next],
                ['consent_version' => "Metin değişti; sürüm de değişmeli. Önerilen: {$next}"]
            );
            flash('error', 'Metni değiştirdiniz. Sürüm numarasını da yükseltin (ör. ' . $currentVersion . ' → ' . $next . '); daha önce imzalanmış formlar eski sürüme bağlı kalır. Yazdığınız metin duruyor, önerilen sürüm alana yazıldı.');
            redirect('/onam-formu');
        }

        Settings::set('consent_text', $text);
        Settings::set('consent_version', $version);

        // Sürüm numarası, verilmiş onamın hangi metne ait olduğunu gösteren
        // tek bağ. Arşive yazılmasaydı o bağ bir sonraki düzenlemede kopardı:
        // eski metin ayarlardan silinir ve 2.0'a onam vermiş kişinin ne
        // okuduğu geri getirilemezdi.
        Consent::archive($version, $text);

        // Çeviri, yalnız GERÇEKTEN değiştiğinde hangi sürüme ait olduğunu
        // tazeliyor. Kaydet'e her basışta ilerleseydi, Türkçe metni düzenleyip
        // çeviriye dokunmayan bir kayıt eskimiş çeviriyi güncel gösterirdi —
        // panelin uyaracak bir şeyi kalmazdı (bkz. Consent::translatedVersion).
        //
        // İlk kayıt da tazeliyor: ekrandaki alan paneldeki taslak çeviriyle dolu
        // geliyor ve olduğu gibi kaydedilebilir. Değişmedi diye sürüm damgası
        // atılmasaydı, o çeviri hangi metne ait olduğunu hiç söylemezdi.
        $translated = $textEn !== ''
            && ($textEn !== $currentTextEn || Settings::get('consent_text_en_version') === '');

        Settings::set('consent_text_en', $textEn);
        if ($translated) {
            Settings::set('consent_text_en_version', $version);
            Consent::archiveTranslation($version, $textEn);
        }

        Audit::log('consent.updated', 'settings', null, [
            'eski_surum' => $currentVersion,
            'yeni_surum' => $version,
            'ceviri'     => $translated ? 'guncellendi' : 'degismedi',
        ]);

        flash('success', "Onam formu {$version} sürümü olarak kaydedildi."
            . ($translated ? ' İngilizce çeviri de kaydedildi.' : ''));
        if ($version !== $currentVersion) {
            flash('warning', 'Yeni sürüm yalnız bundan sonra imzalanacak formlar için geçerlidir. Mevcut bireylerden yeniden onam alınması gerekip gerekmediğini değerlendirin.');
        }
        // Metin yükseldi, çeviri yerinde kaldı: İngilizce çıktı artık başka bir
        // metni basıyor. Ekranın uyarısı kalıcı, bu satır o an söylenmiş hâli.
        if (!$translated && $textEn !== '' && Consent::translationStale()) {
            flash('warning', 'İngilizce çeviri ' . Consent::translatedVersion() . ' sürümüne ait; Türkçe metin ' . $version . '. Çeviriyi güncelleyene kadar İngilizce çıktı eski metni basar.');
        }
        redirect('/onam-formu');
    }

    /** Bireye imzalatılacak yazdırılabilir form. */
    public function printForm(int $clientId): void
    {
        $actor = Auth::requireLogin();
        if (!Rbac::canAny($actor, ['client.view.all', 'client.view.own'])) {
            Audit::log('access.denied', 'permission', null, ['permission' => 'client.view']);
            View::error(403, 'Yetkiniz yok', 'Birey kayıtlarını görüntüleme yetkiniz bulunmuyor.');
            exit;
        }

        [$scope, $params] = ClientScope::filter($actor);
        array_unshift($params, $clientId);

        $client = Db::one("SELECT c.* FROM clients c WHERE c.id = ? AND ({$scope}) LIMIT 1", $params);
        if ($client === null) {
            View::error(404, 'Birey bulunamadı');
            exit;
        }

        $this->renderPrint($client, self::lang());
    }

    /**
     * Kaydı henüz açılmamış kişi için boş form.
     *
     * İlk görüşmeye gelen kişinin kaydı çoğu zaman görüşmeden SONRA açılıyor —
     * onam ise görüşmeden önce imzalanması gereken kâğıt. Kayıt açmayı kâğıdın
     * önkoşulu yapmak, kapıda bekleyen birinin karşısında künye doldurtuyordu.
     * Bu çıktıda ad da elle yazılır; kayıt sonradan açılınca onam kutusu
     * işaretlenir (bkz. clients/form.php).
     */
    public function printBlank(): void
    {
        $actor = Auth::requireLogin();
        if (!Rbac::canAny($actor, ['client.view.all', 'client.view.own', 'consent.manage'])) {
            Audit::log('access.denied', 'permission', null, ['permission' => 'consent.print']);
            View::error(403, 'Yetkiniz yok', 'Onam formu çıktısı alma yetkiniz bulunmuyor.');
            exit;
        }

        $this->renderPrint(null, self::lang());
    }

    // ── Yardımcılar ─────────────────────────────────────────────

    /**
     * Çıktının dili — adres satırındaki ?dil=en.
     *
     * Ayrı bir yol (route) değil, aynı kâğıdın öteki dili: hangi bireye hangi
     * dilde çıktı verileceği kayda bağlı bir ayar değil, o masada verilen bir
     * karar. Tanınmayan her değer Türkçeye düşüyor.
     */
    private static function lang(): string
    {
        return query('dil') === 'en' ? 'en' : 'tr';
    }

    /**
     * @param array<string,mixed>|null $client null = kaydı olmayan kişi için boş form
     * @param string                   $lang   'tr' | 'en' (bkz. lang())
     *
     * Kâğıt, bireyin ONAYLADIĞI sürümü basar — güncel sürümü değil. Kişi
     * 2.0'ı okuyup tikledikten sonra metin panelde 2.1 olduysa, masaya 2.1
     * koymak okumadığı bir kâğıdı imzalatmak olurdu. Böyle bir durumda çıktının
     * üstünde ayrıca bir uyarı satırı duruyor (bkz. consent/print.php).
     *
     * İngilizce kâğıt ayrı bir metin değil, bir sürümün çevirisi — ve kâğıda
     * basılan sürüm numarası, ELDEKİ ÇEVİRİNİN ait olduğu sürümdür. İstenen
     * sürümün çevirisi yoksa numarayı yine de o sürüm gibi basmak, kâğıdı
     * olmadığı bir metnin çevirisi gibi gösterirdi.
     */
    private function renderPrint(?array $client, string $lang = 'tr'): void
    {
        $current = Consent::currentVersion();
        $online  = $client === null ? null : Consent::latestOnline((int) $client['id']);

        // Basılması gereken sürüm: bireyin onayladığı, yoksa güncel olan.
        $version = $online === null ? $current : (string) $online['version'];

        if ($lang === 'en') {
            $text    = Consent::versionTextEn($version);
            $missing = $text === null;
            // O sürümün çevirisi yazılmamış: eldeki çeviri basılıyor ve kâğıda
            // onun sürümü yazılıyor.
            if ($missing) {
                $text    = Consent::currentTextEn();
                $version = Consent::translatedVersion();
            }
        } else {
            $text    = Consent::versionText($version);
            $missing = $text === null;
            // Arşivde yoksa (göç öncesinde onaylanmış bir sürüm) elde yalnız
            // güncel metin var. Boş kâğıt basmaktansa güncelini basıp bunu
            // söylemek doğru: kâğıdın üstündeki sürüm numarası neyin
            // imzalandığını yine de kayda geçiriyor.
            if ($missing) {
                $text    = Consent::currentText();
                $version = $current;
            }
        }

        // Düzen (layout) kullanılmaz: çıktıda menü ve kenar çubuğu olmamalı.
        View::render('consent/print', [
            'title'    => 'Onam formu',
            'client'   => $client,
            'signedOn' => self::signedOn($lang),
            'text'     => $text,
            'version'  => $version,
            'lang'     => $lang,
            // Çevrimiçi onay künyesi: kâğıt "bunu zaten okudunuz" diyor.
            // Seansta okuma değil doğrulama yapılmasının görünür karşılığı.
            'online'   => $online,
            // Metin, onaylanan sürümden sonra değişmiş mi?
            'outdated' => $online !== null && (string) $online['version'] !== $current,
            'missing'  => $missing && $online !== null,
            // Çevirinin kendi iki kusuru: hiç kaydedilmemiş olması (elde yalnız
            // paneldeki taslak var) ve Türkçe metnin gerisinde kalmış olması.
            // İkisi de yalnız İngilizce çıktıda ve yalnız ekranda söyleniyor.
            'draftEn'  => $lang === 'en' && Consent::isDraftEn(),
            'staleEn'  => $lang === 'en' && !Consent::isDraftEn() && Consent::translationStale(),
            'versionEn' => Consent::translatedVersion(),
        ], '');
    }

    /**
     * Kâğıdın üstünde kişiden kişiye değişen tek şey: imza tarihi.
     *
     * Metnin İÇİNE yazılmıyor. Metin sürümlüdür ve herkeste aynı olmak zorunda;
     * kâğıtta değişen şeyler bu yüzden çıktının üstünde doldurulan alanlar
     * olarak duruyor. Buradaki değer yalnız başlangıç değeridir: düzeltilebilir
     * ve hiçbir yere kaydedilmez — kâğıdın kaydı imzalı hâlidir.
     *
     * @param string $lang 'tr' | 'en'
     */
    private static function signedOn(string $lang = 'tr'): string
    {
        // İngilizce kâğıtta ay adı yazıyla: 03.08.2026'yı 3 Ağustos diye
        // okuyan da var, 8 Mart diye okuyan da. İmza tarihi tek okunmalı.
        return $lang === 'en' ? date('j F Y') : date('d.m.Y');
    }

    // ── Bireyin gördüğü sayfa (giriş gerektirmez) ─────────────
    //
    // Panelin giriş gerektirmeyen ikinci ekranı — birincisi check-in. Yetki
    // bağlantının kendisinde: tek kullanımlık, süreli, tek bir kişiye ait
    // 256 bitlik bir jeton.
    //
    // Buradaki her yanıt bilinçli olarak az şey söyler: geçersiz bir bağlantı
    // "bu bağlantı geçerli değil" der, kimin bağlantısı olduğunu ya da neden
    // geçersiz olduğunu ele vermez. Ad yalnız jeton geçerliyken görünür.

    public function publicForm(string $token): void
    {
        if (!Schema::consentReady()) {
            $this->closed('Bu bağlantı şu anda kullanılamıyor', 'Sistem henüz hazır değil. Lütfen merkezimizle iletişime geçin.');
            return;
        }

        $request = Consent::request($token);
        $state   = Consent::state($request);

        if ($state !== 'ok') {
            $this->explain($state);
            return;
        }

        $this->renderLink($token, $request);
    }

    public function publicApprove(string $token): void
    {
        if (!Schema::consentReady()) {
            $this->closed('Bu bağlantı şu anda kullanılamıyor', 'Sistem henüz hazır değil. Lütfen merkezimizle iletişime geçin.');
            return;
        }

        $request = Consent::request($token);
        $state   = Consent::state($request);

        if ($state !== 'ok') {
            $this->explain($state);
            return;
        }

        // Sayfada gösterilen sürüm gizli alanda taşınıyor. Metin, kişi okurken
        // panelden düzenlenmiş olabilir; okuduğundan başka bir metne onam
        // yazmak, onamı ispat değeri olmayan bir tıklamaya çevirirdi.
        //
        // Kayda geçen sürüm de bu: bağlantının üretildiği andaki sürüm
        // (consent_requests.version) değil, kişinin GÖRDÜĞÜ sürüm.
        $version = Consent::currentVersion();

        if (post('surum') !== $version) {
            flash('warning', 'Form, siz okurken güncellendi. Yeni metni okuyup yeniden onaylamanız gerekiyor.');
            $this->renderLink($token, $request);
            return;
        }

        // Tik zorunlu: onam, kutunun işaretlenmesiyle veriliyor. İşaretsiz
        // gönderim sessizce kabul edilseydi kayıt bir şey ifade etmezdi.
        if (post('onay') === '') {
            flash('error', 'Devam etmek için en alttaki onay kutusunu işaretlemeniz gerekiyor.');
            $this->renderLink($token, $request);
            return;
        }

        Consent::approve($request, $version);

        // İçerik değil, olay loglanır. Aktör boş: bu isteği yapan kişinin
        // panelde oturumu yok.
        Audit::log('consent.approved', 'client', (int) $request['client_id'], ['surum' => $version]);

        redirect('/onam/tesekkurler');
    }

    public function publicThanks(): void
    {
        View::render('consent/link_done', ['title' => 'Onayınız alındı'], 'checkin_layout');
    }

    /** @param array<string,mixed> $request */
    private function renderLink(string $token, array $request): void
    {
        View::render('consent/link', [
            'title'     => 'Onam formu',
            'token'     => $token,
            'firstName' => $this->firstName((string) $request['full_name']),
            'text'      => Consent::currentText(),
            'version'   => Consent::currentVersion(),
            // Uzun bir hukuki metin telefon genişliğindeki kolonda okunmuyor;
            // kabuk bu sayfada geniş kuruluyor (bkz. checkin_layout).
            'wide'      => true,
        ], 'checkin_layout');
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
                'Bu formu zaten onayladınız',
                'Teşekkürler — onayınız kaydedildi. Seansta formun çıktısı üzerinden kısaca doğrulanacak, '
                . 'yeniden okumanız gerekmiyor.',
            ],
            'expired' => [
                'Bu bağlantının süresi doldu',
                'Bağlantılar ' . Consent::TTL_DAYS . ' gün geçerli. Seansınızdan önce onaylamak isterseniz '
                . 'merkezimize yazın, yeni bir bağlantı gönderelim.',
            ],
            'closed' => [
                'Bu bağlantı artık geçerli değil',
                'Kaydınız şu anda açık görünmüyor. Sorusu olan biri varsa merkezimizle iletişime geçebilir.',
            ],
            default => [
                'Bu bağlantı geçerli değil',
                'Bağlantı eksik kopyalanmış olabilir. Size iletilen adresin tamamını kullanmayı deneyin.',
            ],
        };

        $this->closed($title, $message);
    }

    private function closed(string $title, string $message): void
    {
        View::render('consent/link_closed', [
            'title'   => $title,
            'heading' => $title,
            'message' => $message,
        ], 'checkin_layout');
    }

    private function firstName(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];
        return (string) ($parts[0] ?? '');
    }
}
