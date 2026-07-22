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
 * Sıkça sorulan sorular (`_data/faqdata.json`).
 *
 * Dosyanın iki bölümü var ve ikisi de aynı soru/cevap listesi biçiminde:
 *   topics.<konu>.<dil>              → sayfalara gömülen konu blokları
 *   pageCategories.<dil>[].items     → SSS sayfasının kategorileri
 * Bu yüzden tek düzenleme ekranı ikisine de hizmet eder.
 *
 * Konu anahtarları ve kategori kimlikleri panelden değiştirilemez: bunlara
 * şablonlardan ada göre başvuruluyor, panelden yeniden adlandırmak siteyi sessizce
 * boş bir bloğa düşürürdü. Panel yalnız soru/cevap metinlerini yönetir.
 */
final class FaqController
{
    private const PATH  = '_data/faqdata.json';
    private const LANGS = ['tr' => 'Türkçe', 'en' => 'İngilizce'];

    public function index(): void
    {
        $actor = Auth::requirePermission('content.manage');

        if (!Github::configured()) {
            View::render('content/unconfigured', ['title' => 'SSS içeriği', 'actor' => $actor]);
            return;
        }

        try {
            $data = Json::decode(Github::read(self::PATH)['content'], 'faqdata.json');
        } catch (RuntimeException $e) {
            View::error(502, 'İçerik okunamadı', $e->getMessage());
            return;
        }

        $topics = [];
        foreach ((array) ($data['topics'] ?? []) as $slug => $languages) {
            $topics[(string) $slug] = [
                'tr' => count((array) ($languages['tr'] ?? [])),
                'en' => count((array) ($languages['en'] ?? [])),
            ];
        }
        ksort($topics);

        $categories = [];
        foreach (self::LANGS as $lang => $ignored) {
            foreach ((array) ($data['pageCategories'][$lang] ?? []) as $category) {
                $categories[$lang][] = [
                    'id'    => (string) ($category['id'] ?? ''),
                    'title' => (string) ($category['title'] ?? ''),
                    'count' => count((array) ($category['items'] ?? [])),
                ];
            }
        }

        View::render('faq/index', [
            'title'      => 'SSS içeriği',
            'topics'     => $topics,
            'categories' => $categories,
            'langs'      => self::LANGS,
            'actor'      => $actor,
        ]);
    }

    public function edit(): void
    {
        $actor = Auth::requirePermission('content.manage');

        $type = query('tip');
        $key  = query('anahtar');
        $lang = query('dil', 'tr');

        try {
            $file   = Github::read(self::PATH);
            $data   = Json::decode($file['content'], 'faqdata.json');
            $target = $this->resolve($data, $type, $key, $lang);
        } catch (RuntimeException $e) {
            View::error(502, 'İçerik okunamadı', $e->getMessage());
            return;
        }

        if ($target === null) {
            View::error(404, 'İçerik bulunamadı', 'Bu konu ya da kategori dosyada yok.');
            return;
        }

        View::render('faq/edit', [
            'title'    => $target['label'],
            'type'     => $type,
            'key'      => $key,
            'lang'     => $lang,
            'label'    => $target['label'],
            'heading'  => $target['heading'],
            'items'    => $target['items'],
            'sha'      => $file['sha'],
            'langs'    => self::LANGS,
            'actor'    => $actor,
        ]);
    }

    public function save(): void
    {
        $actor = Auth::requirePermission('content.manage');

        $type = post('tip');
        $key  = post('anahtar');
        $lang = post('dil');
        $back = '/icerik/sss-duzenle?tip=' . rawurlencode($type) . '&anahtar=' . rawurlencode($key) . '&dil=' . rawurlencode($lang);

        $items = $this->collectItems();
        if ($items === null) {
            flash('error', 'Her satırda hem soru hem cevap dolu olmalı. Satırı silmek için "sil" kutusunu işaretleyin.');
            redirect($back);
        }
        if ($items === []) {
            flash('error', 'Liste boş bırakılamaz. En az bir soru/cevap kalmalı.');
            redirect($back);
        }

        try {
            $file = Github::read(self::PATH);

            // Form açıldığından beri dosya değiştiyse yazma reddedilir.
            if ($file['sha'] !== post('sha')) {
                flash('error', 'Bu dosya siz formu açtıktan sonra değişmiş. Sayfayı yenileyip değişikliğinizi tekrar yapın — hiçbir şeyin üzerine yazılmadı.');
                redirect($back);
            }

            $data   = Json::decode($file['content'], 'faqdata.json');
            $target = $this->resolve($data, $type, $key, $lang);

            if ($target === null) {
                flash('error', 'Düzenlenen bölüm dosyada bulunamadı.');
                redirect('/icerik/sss');
            }

            if ($type === 'konu') {
                $data['topics'][$key][$lang] = $items;
            } else {
                $data['pageCategories'][$lang][$target['index']]['items'] = $items;

                $title = post('kategori_basligi');
                if ($title !== '') {
                    $data['pageCategories'][$lang][$target['index']]['title'] = $title;
                }
            }

            Github::write(
                self::PATH,
                Json::pretty($data),
                $file['sha'],
                "Update FAQ content from the panel\n\nSection: {$type}/{$key} ({$lang}), " . count($items) . " entries"
                . "\nChanged by: {$actor['full_name']}"
            );
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect($back);
        }

        Audit::log('content.updated', 'file', null, [
            'path'   => self::PATH,
            'bolum'  => "{$type}/{$key}",
            'dil'    => $lang,
            'adet'   => count($items),
        ]);

        flash('success', 'SSS içeriği kaydedildi.');
        flash('info', 'Site yeniden yayınlanıyor. Değişikliğin görünmesi birkaç dakika sürer.');
        redirect($back);
    }

    // ── Yardımcılar ─────────────────────────────────────────────

    /**
     * Düzenlenecek bölümü bulur.
     * @return array{items:list<array>,label:string,heading:string,index:int}|null
     */
    private function resolve(array $data, string $type, string $key, string $lang): ?array
    {
        if (!isset(self::LANGS[$lang]) || $key === '') {
            return null;
        }

        if ($type === 'konu') {
            if (!isset($data['topics'][$key])) {
                return null;
            }
            return [
                'items'   => array_values((array) ($data['topics'][$key][$lang] ?? [])),
                'label'   => 'Konu: ' . $key,
                'heading' => $key,
                'index'   => -1,
            ];
        }

        if ($type === 'sayfa') {
            foreach ((array) ($data['pageCategories'][$lang] ?? []) as $index => $category) {
                if ((string) ($category['id'] ?? '') === $key) {
                    return [
                        'items'   => array_values((array) ($category['items'] ?? [])),
                        'label'   => 'Kategori: ' . (string) ($category['title'] ?? $key),
                        'heading' => (string) ($category['title'] ?? $key),
                        'index'   => (int) $index,
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Formdaki satırlardan listeyi kurar.
     *
     * `array_values` şart: JSON'da bu bir dizi, arada boşluk kalırsa PHP onu
     * nesne olarak kodlar ve şablonlardaki döngü sessizce çalışmaz hâle gelir.
     *
     * @return list<array{q:string,a:string}>|null null = doğrulama hatası
     */
    private function collectItems(): ?array
    {
        $questions = (array) ($_POST['q'] ?? []);
        $answers   = (array) ($_POST['a'] ?? []);
        $deleted   = (array) ($_POST['sil'] ?? []);

        $items = [];
        foreach ($questions as $index => $question) {
            if (isset($deleted[$index])) {
                continue;
            }

            $question = trim((string) $question);
            $answer   = trim((string) ($answers[$index] ?? ''));

            // Tamamen boş satır: sonda duran "yeni kayıt" kutuları.
            if ($question === '' && $answer === '') {
                continue;
            }
            if ($question === '' || $answer === '') {
                return null;
            }

            $items[] = ['q' => $question, 'a' => $answer];
        }

        return array_values($items);
    }
}
