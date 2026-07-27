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
 * KVKK aydınlatma ve açık rıza metni.
 *
 * Metin veritabanında sürümlü tutulur; görüşmeci kaydındaki `consent_version`
 * hangi metne rıza verildiğini gösterir. Bu yüzden metin değişince sürüm de
 * değişmek ZORUNDA — aksi hâlde iki farklı metne aynı sürüm numarasıyla rıza
 * verilmiş görünür ve kayıt ispat değerini kaybeder.
 */
final class ConsentController
{
    private const DEFAULT_VERSION = '1.0';

    public function index(): void
    {
        $actor = Auth::requirePermission('consent.manage');

        View::render('consent/index', [
            'title'   => 'KVKK Metni',
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
            flash('error', 'Metin çok kısa görünüyor. Aydınlatma metni en az birkaç paragraf olmalı.');
            redirect('/kvkk');
        }
        if ($version === '' || mb_strlen($version) > 20) {
            flash('error', 'Sürüm numarası boş bırakılamaz (en fazla 20 karakter).');
            redirect('/kvkk');
        }

        // Sürüm, verilmiş rızaların hangi metne ait olduğunu gösteren tek bağdır.
        if ($text !== $currentText && $version === $currentVersion) {
            flash('error', 'Metni değiştirdiniz. Sürüm numarasını da yükseltin (ör. ' . $currentVersion . ' → ' . self::nextVersion($currentVersion) . '); daha önce verilmiş rızalar eski sürüme bağlı kalır.');
            redirect('/kvkk');
        }

        Settings::set('consent_text', $text);
        Settings::set('consent_version', $version);

        Audit::log('consent.updated', 'settings', null, [
            'eski_surum' => $currentVersion,
            'yeni_surum' => $version,
        ]);

        flash('success', "KVKK metni {$version} sürümü olarak kaydedildi.");
        if ($version !== $currentVersion) {
            flash('warning', 'Yeni sürüm yalnız bundan sonra alınacak rızalar için geçerlidir. Mevcut görüşmecilerden yeniden rıza alınması gerekip gerekmediğini değerlendirin.');
        }
        redirect('/kvkk');
    }

    /** Görüşmeciye imzalatılacak yazdırılabilir form. */
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
            'title'   => 'Açık rıza formu',
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
     */
    private static function starterText(): string
    {
        return <<<'METIN'
        AYDINLATMA METNİ VE AÇIK RIZA BEYANI

        [Kurum unvanı], veri sorumlusu sıfatıyla, psikolojik danışmanlık hizmeti
        kapsamında kişisel verilerinizi aşağıda açıklanan çerçevede işlemektedir.

        1. İŞLENEN VERİLER
        Kimlik ve iletişim bilgileriniz (ad soyad, telefon, e-posta, doğum tarihi),
        randevu kayıtlarınız ve seans sürecine ilişkin notlar işlenmektedir. Seans
        notları özel nitelikli kişisel veri (sağlık verisi) niteliğindedir.

        2. İŞLEME AMACI
        Verileriniz; randevunuzun planlanması, hizmetin sunulması, sürecin
        takibi ve yasal saklama yükümlülüklerinin yerine getirilmesi amacıyla
        işlenir.

        3. HUKUKİ SEBEP
        Sağlık verileriniz, KVKK m.6 uyarınca açık rızanıza dayanılarak işlenir.
        Diğer verileriniz sözleşmenin kurulması ve ifası ile meşru menfaat
        hukuki sebeplerine dayanır.

        4. AKTARIM
        Verileriniz, açık rızanız olmaksızın üçüncü kişilerle paylaşılmaz. Yasal
        olarak yetkili kamu kurumlarının talebi hâlinde mevzuat kapsamında
        paylaşım yapılabilir.

        5. SAKLAMA VE GÜVENLİK
        Seans notları şifrelenerek saklanır ve yalnız notu düzenleyen terapist
        tarafından görüntülenebilir. Kayıtlar, [saklama süresi] süresince
        saklanır ve sürenin sonunda imha edilir.

        6. HAKLARINIZ
        KVKK m.11 kapsamında; verilerinize erişme, düzeltilmesini veya silinmesini
        isteme, işlemeye itiraz etme haklarına sahipsiniz. Başvurularınızı
        [iletişim adresi] üzerinden iletebilirsiniz.

        AÇIK RIZA BEYANI
        Yukarıdaki aydınlatma metnini okudum ve anladım. Sağlık verilerim dâhil
        kişisel verilerimin belirtilen amaçlarla işlenmesine açık rızam ile
        onay veriyorum.
        METIN;
    }
}
