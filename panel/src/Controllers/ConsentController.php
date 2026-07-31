<?php
declare(strict_types=1);

namespace Panel\Controllers;

use Panel\Audit;
use Panel\Auth;
use Panel\ClientScope;
use Panel\Db;
use Panel\Rbac;
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

        if (mb_strlen($text) < 100) {
            flash('error', 'Metin çok kısa görünüyor. Onam formu en az birkaç paragraf olmalı.');
            redirect('/onam-formu');
        }
        if ($version === '' || mb_strlen($version) > 20) {
            flash('error', 'Sürüm numarası boş bırakılamaz (en fazla 20 karakter).');
            redirect('/onam-formu');
        }

        // Sürüm, verilmiş onamların hangi metne ait olduğunu gösteren tek bağdır.
        if ($text !== $currentText && $version === $currentVersion) {
            flash('error', 'Metni değiştirdiniz. Sürüm numarasını da yükseltin (ör. ' . $currentVersion . ' → ' . self::nextVersion($currentVersion) . '); daha önce imzalanmış formlar eski sürüme bağlı kalır.');
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

        // Düzen (layout) kullanılmaz: çıktıda menü ve kenar çubuğu olmamalı.
        View::render('consent/print', [
            'title'   => 'Onam formu',
            'client'  => $client,
            'text'    => Settings::get('consent_text', self::starterText()),
            'version' => Settings::get('consent_version', self::DEFAULT_VERSION),
        ], '');
    }

    // ── Yardımcılar ─────────────────────────────────────────────

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
