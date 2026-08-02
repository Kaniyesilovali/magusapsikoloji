<?php
declare(strict_types=1);

namespace Panel\Controllers;

use Panel\Audit;
use Panel\Auth;
use Panel\ClientScope;
use Panel\Db;
use Panel\Money;
use Panel\Rbac;
use Panel\Scheduling;
use Panel\Settings;
use Panel\View;

/**
 * Onam formu — danışana imzalatılan tek metin.
 *
 * Kurumda iki ayrı kâğıt dolaşıyordu: psikoloğun süreç anlaşması (seans süresi,
 * iptal, gizlilik, gönüllülük) ve panelin KVKK aydınlatma metni. Danışan aynı
 * masada iki kez imza atıyordu ve ikisi de aynı şeyi söylüyordu: "bu bilgiler
 * burada kalır". Tek forma indirildi; KVKK bölümleri, gizlilik başlığının
 * altına, aynı dille yazılmış bir alt bölüm olarak girdi.
 *
 * Metin veritabanında sürümlü tutulur; danışan kaydındaki `consent_version`
 * hangi metne onay verildiğini gösterir. Bu yüzden metin değişince sürüm de
 * değişmek ZORUNDA — aksi hâlde iki farklı metne aynı sürüm numarasıyla onam
 * verilmiş görünür ve kayıt ispat değerini kaybeder.
 */
final class ConsentController
{
    // Birleştirilmiş metin, eski KVKK taslağıyla aynı kâğıt değil: hiç
    // elle kaydedilmemiş kurumlar 2.0'dan başlasın (bkz. 009 numaralı göç).
    private const DEFAULT_VERSION = '2.0';

    public function index(): void
    {
        $actor = Auth::requirePermission('consent.manage');

        View::render('consent/index', [
            'title'   => 'Onam formu',
            'text'    => Settings::get('consent_text', self::starterText()),
            'version' => Settings::get('consent_version', self::DEFAULT_VERSION),
            'isDraft' => Settings::get('consent_text') === '',
            'signed'  => (int) Db::value('SELECT COUNT(*) FROM clients WHERE consent_at IS NOT NULL'),
            'actor'   => $actor,
        ]);
    }

    public function update(): void
    {
        Auth::requirePermission('consent.manage');

        $text    = trim((string) ($_POST['consent_text'] ?? ''));
        $version = post('consent_version');

        $currentText    = Settings::get('consent_text', self::starterText());
        $currentVersion = Settings::get('consent_version', self::DEFAULT_VERSION);

        // Hata hâlinde yazılan metin geri veriliyor (bkz. old(), views/consent/index.php).
        // Eskiden gönderim reddedilince textarea kayıttaki metinle yeniden
        // doluyordu: sürüm numarası yüzünden geri çevrilen bir düzeltme,
        // yarım saatlik yazıyı da götürüyordu. Kaydedilmeyen bir metni geri
        // vermemek, "önce sürümü yükselt" kuralını cezaya çeviriyordu.
        $input = ['consent_text' => $text, 'consent_version' => $version];

        if (mb_strlen($text) < 100) {
            remember_input($input, ['consent_text' => 'Metin en az birkaç paragraf olmalı.']);
            flash('error', 'Metin çok kısa görünüyor. Onam formu en az birkaç paragraf olmalı.');
            redirect('/onam-formu');
        }
        if ($version === '' || mb_strlen($version) > 20) {
            remember_input($input, ['consent_version' => 'Sürüm numarası boş bırakılamaz (en fazla 20 karakter).']);
            flash('error', 'Sürüm numarası boş bırakılamaz (en fazla 20 karakter).');
            redirect('/onam-formu');
        }

        // Sürüm, verilmiş onamların hangi metne ait olduğunu gösteren tek bağdır.
        if ($text !== $currentText && $version === $currentVersion) {
            $next = self::nextVersion($currentVersion);
            // Önerilen sürüm alana yazılı gelir: kural yerinde duruyor, ama
            // uygulaması tek tuş — sürümü yükseltmek isteyen kişi yeniden
            // kaydete basıyor, istemeyen sayıyı kendisi değiştiriyor.
            remember_input(
                ['consent_text' => $text, 'consent_version' => $next],
                ['consent_version' => "Metin değişti; sürüm de değişmeli. Önerilen: {$next}"]
            );
            flash('error', 'Metni değiştirdiniz. Sürüm numarasını da yükseltin (ör. ' . $currentVersion . ' → ' . $next . '); daha önce imzalanmış formlar eski sürüme bağlı kalır. Yazdığınız metin duruyor, önerilen sürüm alana yazıldı.');
            redirect('/onam-formu');
        }

        Settings::set('consent_text', $text);
        Settings::set('consent_version', $version);

        Audit::log('consent.updated', 'settings', null, [
            'eski_surum' => $currentVersion,
            'yeni_surum' => $version,
        ]);

        flash('success', "Onam formu {$version} sürümü olarak kaydedildi.");
        if ($version !== $currentVersion) {
            flash('warning', 'Yeni sürüm yalnız bundan sonra imzalanacak formlar için geçerlidir. Mevcut danışanlardan yeniden onam alınması gerekip gerekmediğini değerlendirin.');
        }
        redirect('/onam-formu');
    }

    /** Danışana imzalatılacak yazdırılabilir form. */
    public function printForm(int $clientId): void
    {
        $actor = Auth::requireLogin();
        if (!Rbac::canAny($actor, ['client.view.all', 'client.view.own'])) {
            Audit::log('access.denied', 'permission', null, ['permission' => 'client.view']);
            View::error(403, 'Yetkiniz yok', 'Görüşmeci kayıtlarını görüntüleme yetkiniz bulunmuyor.');
            exit;
        }

        [$scope, $params] = ClientScope::filter($actor);
        array_unshift($params, $clientId);

        $client = Db::one("SELECT c.* FROM clients c WHERE c.id = ? AND ({$scope}) LIMIT 1", $params);
        if ($client === null) {
            View::error(404, 'Görüşmeci bulunamadı');
            exit;
        }

        $this->renderPrint($client, $actor);
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

        $this->renderPrint(null, $actor);
    }

    // ── Yardımcılar ─────────────────────────────────────────────

    /** @param array<string,mixed>|null $client null = kaydı olmayan kişi için boş form */
    private function renderPrint(?array $client, array $actor): void
    {
        // Düzen (layout) kullanılmaz: çıktıda menü ve kenar çubuğu olmamalı.
        View::render('consent/print', [
            'title'   => 'Onam formu',
            'client'  => $client,
            'terms'   => self::terms($client, $actor),
            'text'    => Settings::get('consent_text', self::starterText()),
            'version' => Settings::get('consent_version', self::DEFAULT_VERSION),
        ], '');
    }

    /**
     * Kâğıdın üstünde kişiden kişiye değişen tek şey: tarih ve seans koşulları.
     *
     * Bunlar metnin İÇİNE yazılmıyor. Metin sürümlüdür ve herkeste aynı olmak
     * zorunda — birinin ücreti için metni değiştirmek, sürümü de değiştirmek
     * ve o güne kadar imzalanmış bütün formları başka bir metne bağlamak
     * demekti. Değişen sayılar bu yüzden ayrı bir kutuda, çıktının üstünde
     * doldurulan alanlar olarak duruyor.
     *
     * Değerler yalnız başlangıç değeridir; kutunun içinde düzeltilebilir ve
     * hiçbir yere kaydedilmez — kâğıdın kaydı imzalı hâlidir.
     *
     * @param  array<string,mixed>|null $client
     * @return array{fee:string, minutes:int, frequency:string, date:string}
     */
    private static function terms(?array $client, array $actor): array
    {
        // Ücret, o kişiye en son yazılmış seans ücreti: konuşulan rakam
        // zaten odur, yeniden hatırlanması gerekmesin.
        $fee = '';
        if ($client !== null) {
            $last = Db::value(
                'SELECT fee FROM appointments
                  WHERE client_id = ? AND fee IS NOT NULL
               ORDER BY starts_at DESC LIMIT 1',
                [(int) $client['id']]
            );
            if ($last !== null) {
                $fee = Money::format((string) $last);
            }
        }

        // Süre, kaydın birincil terapistinin varsayılanı; kayıt yoksa formu
        // basan kişinin kendi varsayılanı (bkz. Scheduling::defaultDuration).
        $therapistId = $client['primary_therapist_id'] ?? $actor['id'];

        return [
            'fee'       => $fee,
            'minutes'   => Scheduling::defaultDuration($therapistId === null ? null : (int) $therapistId),
            // Metnin kendisi "haftalık veya iki haftada bir" diyor; kâğıtta
            // ikisinden hangisi olduğu yazılı olsun diye sık olanı öneriyoruz.
            'frequency' => 'Haftada bir',
            'date'      => date('d.m.Y'),
        ];
    }

    /** '1.0' → '1.1'; sayısal olmayan sürümlerde ipucu vermeden döner. */
    private static function nextVersion(string $current): string
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
     * basıyor (consent/print.php). Metin, doğrudan danışana hitapla başlıyor.
     */
    private static function starterText(): string
    {
        return <<<'METIN'
        Bilgilendirilmiş Onam Formu, sahip olduğunuz haklarla ve sorumluluklarla
        ilgili sizi bilgilendirmek amacıyla oluşturulmuştur. Psikoloğunuzla
        karşılıklı olarak onayladığınız bir anlaşma niteliği taşıyacaktır.

        PSİKOTERAPİ SÜRECİNE YÖNELİK BİLGİLENDİRME

        - Seans süresi ortalama 45-50 dakikadır.
        - Seanslara düzenli ve zamanında katılım, sağlıklı bir psikoterapötik
          ilişki kurabilmek için önemli ve gereklidir.
        - Seansa danışan tarafından geç kalınması durumunda, yalnızca geriye kalan
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
        - Etik ilkeler ve gizliliğin korunması amacıyla, ses ve/veya görüntü kaydı
          almaya izin verilmemektedir.

        GİZLİLİK VE GÜVENİLİRLİK İLKESİ

        - Psikolog ve danışan arasında konuşulan her şey gizlidir ve üçüncü
          kişilerle paylaşılmaz.
        - Psikolog, seans notları da dahil olmak üzere, psikoterapi süreci boyunca
          tutacağı raporları ve danışanın kişisel bilgilerini, kimsenin
          ulaşamayacağı şekilde saklamak ve muhafaza etmekle yükümlüdür. Bu kural,
          psikoterapötik süreç sona erdikten sonra da geçerlidir.
        - Gizlilik ilkesinin, psikolog tarafından ihlal edilebileceği sadece 2 koşul
          mevcuttur:
          - Bu koşulların en önemlisi; danışanın kendine ve/veya bir başkasına zarar
            verme riski görülmesidir. Bu gibi durumlarda, psikolog gerekli mercilere
            bilgi vermek ve/veya danışanın bir aile yakınıyla iletişime geçmek
            durumundadır.
          - Diğer bir koşul ise herhangi bir hukuki süreçte, mahkeme kararıyla
            psikologdan bilgi istenilmesi durumudur. Bu durumda da psikolog yasal
            olarak, mahkeme kararını uygulamaya geçirmek zorundadır.
        - Psikoterapötik süreç içinde gerçekleşebilecek herhangi bir olağandışı
          durumda danışanı koruyabilmek ve erken müdahaleyi sağlayabilmek için bir
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
          danışanın gönüllülüğü çok önemlidir. Danışanın kendi isteğiyle ve hiçbir
          zorlama altında olmadan psikolojik destek istediğinden emin olunmalıdır.
        - Danışan, istediği zaman görüşmeleri sonlandırma hakkına sahiptir.
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
}
