<?php
declare(strict_types=1);

namespace Panel\Controllers;

use Panel\Audit;
use Panel\Auth;
use Panel\Github;
use Panel\Json;
use Panel\View;
use RuntimeException;

/**
 * Site verilerinin panelden düzenlenmesi (`_data/*.json`).
 *
 * Ham JSON düzenletilmez, alan alan form gösterilir: tek bir eksik virgül siteyi
 * derlenemez hâle getirirdi ve hatayı ancak deploy başarısız olunca fark ederdik.
 * Bilinmeyen anahtarlar okunduğu gibi korunur — panel, tanımadığı alanı silmez.
 */
final class ContentController
{
    private const CONTACT_PATH = '_data/contact.json';

    /** Formda gösterilen alanlar: anahtar => [etiket, ipucu] */
    private const CONTACT_FIELDS = [
        'whatsapp'      => ['WhatsApp numarası', 'Yalnız rakam, ülke koduyla: 905331234567'],
        'phone'         => ['Telefon (görünen)', 'Sitede yazıldığı gibi: +90 533 123 45 67'],
        'email'         => ['E-posta', ''],
        'instagram'     => ['Instagram adresi', ''],
        'facebook'      => ['Facebook adresi', ''],
        'copyrightYear' => ['Telif yılı', 'Alt bilgide görünen yıl'],
    ];

    public function index(): void
    {
        $actor = Auth::requirePermission('content.manage');

        View::render('content/index', [
            'title'      => 'Site içeriği',
            'configured' => Github::configured(),
            'actor'      => $actor,
        ]);
    }

    public function contact(): void
    {
        $actor = Auth::requirePermission('content.manage');

        if (!Github::configured()) {
            $this->renderUnconfigured($actor);
            return;
        }

        try {
            $file = Github::read(self::CONTACT_PATH);
            $data = Json::decode($file['content'], 'contact.json');
        } catch (RuntimeException $e) {
            View::error(502, 'İçerik okunamadı', $e->getMessage());
            return;
        }

        View::render('content/contact', [
            'title'  => 'İletişim bilgileri',
            'data'   => $data,
            'sha'    => $file['sha'],
            'fields' => self::CONTACT_FIELDS,
            'actor'  => $actor,
        ]);
    }

    public function saveContact(): void
    {
        $actor = Auth::requirePermission('content.manage');

        $input  = [];
        foreach (array_keys(self::CONTACT_FIELDS) as $key) {
            $input[$key] = post($key);
        }
        $errors = $this->validateContact($input);

        if ($errors !== []) {
            remember_input($input, $errors);
            flash('error', 'Formda hatalı alanlar var.');
            redirect('/icerik/iletisim');
        }

        try {
            // Dosya yeniden okunur: yalnız düzenlenen alanlar değişsin, tanınmayan
            // anahtarlar ve sıralama olduğu gibi kalsın.
            $file = Github::read(self::CONTACT_PATH);

            // Form açıldığından beri dosya değiştiyse yazma reddedilir: aksi hâlde
            // bu formdaki eski değerler, aradaki değişikliğin üzerine yazardı.
            if ($file['sha'] !== post('sha')) {
                remember_input($input);
                flash('error', 'Bu dosya siz formu açtıktan sonra değişmiş. Sayfayı yenileyip değişikliğinizi tekrar yapın — hiçbir şeyin üzerine yazılmadı.');
                redirect('/icerik/iletisim');
            }

            $data = Json::decode($file['content'], 'contact.json');

            $changed = [];
            foreach ($input as $key => $value) {
                if ((string) ($data[$key] ?? '') !== $value) {
                    $changed[] = $key;
                }
                $data[$key] = $value;
            }

            if ($changed === []) {
                flash('info', 'Değişiklik yok; dosyaya dokunulmadı.');
                redirect('/icerik/iletisim');
            }

            Github::write(
                self::CONTACT_PATH,
                Json::pretty($data),
                $file['sha'],
                "Update contact details from the panel\n\nFields: " . implode(', ', $changed)
                . "\nChanged by: {$actor['full_name']}"
            );
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect('/icerik/iletisim');
        }

        Audit::log('content.updated', 'file', null, ['path' => self::CONTACT_PATH, 'alanlar' => $changed]);

        flash('success', 'İletişim bilgileri kaydedildi.');
        flash('info', 'Site yeniden yayınlanıyor. Değişikliğin görünmesi birkaç dakika sürer.');
        redirect('/icerik/iletisim');
    }

    // ── Yardımcılar ─────────────────────────────────────────────

    private function validateContact(array $input): array
    {
        $errors = [];

        if (preg_match('/^[0-9]{10,15}$/', $input['whatsapp']) !== 1) {
            $errors['whatsapp'] = 'Yalnız rakam, ülke koduyla birlikte 10–15 hane olmalı (ör. 905331234567).';
        }
        if (preg_match('/^[0-9 +()-]{7,25}$/', $input['phone']) !== 1) {
            $errors['phone'] = 'Telefon numarası geçersiz.';
        }
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Geçerli bir e-posta adresi girin.';
        }
        if (preg_match('/^[12][0-9]{3}$/', $input['copyrightYear']) !== 1) {
            $errors['copyrightYear'] = 'Dört haneli bir yıl girin.';
        }
        foreach (['instagram', 'facebook'] as $key) {
            if ($input[$key] !== '' && !filter_var($input[$key], FILTER_VALIDATE_URL)) {
                $errors[$key] = 'Geçerli bir adres girin (https:// ile başlamalı).';
            }
        }

        return $errors;
    }

    private function renderUnconfigured(array $actor): void
    {
        View::render('content/unconfigured', [
            'title' => 'İçerik yönetimi',
            'actor' => $actor,
        ]);
    }
}
