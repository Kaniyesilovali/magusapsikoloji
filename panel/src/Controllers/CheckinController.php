<?php
declare(strict_types=1);

namespace Panel\Controllers;

use Panel\Audit;
use Panel\Checkins;
use Panel\Crypto;
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

        View::render('checkins/form', [
            'title'     => 'Haftalık check-in',
            'token'     => $token,
            'firstName' => $this->firstName((string) $request['full_name']),
            // Şifreleme kapalıysa cümle alanı hiç gösterilmez: saklanamayacak
            // bir şeyi yazdırmak, yazanın güvenine ihanet eder.
            'noteOpen'  => Crypto::available(),
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

        $noteSaved = Checkins::save(
            $request,
            Checkins::score(post('mood')),
            Checkins::score(post('sleep_quality')),
            Checkins::score(post('anxiety')),
            Crypto::available() ? post('note') : ''
        );

        // Kayıt tutulur ama İÇERİĞİ asla loglanmaz — seans notundaki kural.
        // Aktör boş: bu isteği yapan kişi panele giriş yapmış değil.
        Audit::log('checkin.submitted', 'client', (int) $request['client_id']);

        if (!$noteSaved) {
            flash('warning', 'Puanların kaydedildi, ama yazdığın cümle güvenli biçimde '
                . 'saklanamadı ve kaydedilmedi. Söylemek istediğin bir şey varsa seansta paylaşabilirsin.');
        }

        redirect('/check-in/tesekkurler');
    }

    /** Gönderimden sonraki teşekkür sayfası — yenilenince form tekrar gönderilmesin. */
    public function thanks(): void
    {
        View::render('checkins/done', ['title' => 'Teşekkürler'], 'checkin_layout');
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

    private function firstName(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];
        return (string) ($parts[0] ?? '');
    }
}
