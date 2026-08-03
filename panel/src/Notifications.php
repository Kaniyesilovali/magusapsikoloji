<?php
declare(strict_types=1);

namespace Panel;

/**
 * Randevu e-postaları.
 *
 * İçerik bilinçli olarak yalın: tarih, saat, terapist, görüşme yeri. Randevunun
 * idari notu ve bireyin diğer bilgileri e-postaya konmaz — e-posta şifresiz bir
 * kanaldır ve sağlık verisi çağrıştıran içerik taşımamalıdır.
 *
 * Gönderim başarısız olursa kayıt işlemi geri alınmaz; çağıran taraf kullanıcıyı
 * uyarır. Randevunun kaydedilmiş olması, e-postanın gitmiş olmasından önemlidir.
 */
final class Notifications
{
    private const SUBJECTS = [
        'created'   => 'Randevunuz oluşturuldu',
        'updated'   => 'Randevunuz güncellendi',
        'cancelled' => 'Randevunuz iptal edildi',
    ];

    /**
     * Yaklaşan randevu hatırlatması. Yalnız bireye gider — terapist zaten
     * kendi takvimine bakıyor ve her seans için ikinci bir e-posta gürültüdür.
     *
     * @param array<string,mixed> $row appointment + client_name + client_email + therapist_name
     */
    public static function reminder(array $row): bool
    {
        if (($row['client_email'] ?? null) === null) {
            return false;
        }

        $when = tr_date_label(substr((string) $row['starts_at'], 0, 10))
            . ' saat ' . dt($row['starts_at'], 'H:i');

        return Mailer::send(
            (string) $row['client_email'],
            'Randevu hatırlatması — Mağusa Psikoloji',
            Mailer::template(
                'Yaklaşan randevunuz',
                "Merhaba {$row['client_name']},\n\nRandevunuzu hatırlatmak istedik.\n\n"
                . "Zaman: {$when}\nTerapist: {$row['therapist_name']}\n"
                . 'Görüşme: ' . Scheduling::locationLabel($row['location']),
                null,
                null,
                'Gelemeyecekseniz lütfen merkezimizi arayın; saatinizi başka bir bireye açabiliriz.'
            )
        );
    }

    /**
     * Haftalık check-in daveti.
     *
     * İçerik e-postanın şifresiz bir kanal olduğunu varsayıyor: soruların ne
     * olduğu yazılmaz, "check-in" denip bağlantı verilir. Terapist adı da yok —
     * iletiyi başkası görürse kişinin terapide olduğunu ele vermesin.
     *
     * Metnin tek işi bir tıklama almak: süre ("yarım dakika") ve sayı ("üç
     * soru") bu yüzden başta duruyor.
     *
     * @param array<string,mixed> $row full_name + email taşıyan satır
     */
    public static function checkinRequest(array $row, string $link): bool
    {
        if (($row['email'] ?? null) === null) {
            return false;
        }

        $firstName = (string) (preg_split('/\s+/', trim((string) $row['full_name']))[0] ?? '');

        return Mailer::send(
            (string) $row['email'],
            'Haftalık check-in — Mağusa Psikoloji',
            Mailer::template(
                'Bu hafta nasıl geçti?',
                "Merhaba {$firstName},\n\nÜç soruluk kısa bir check-in: yaklaşık yarım dakika sürüyor "
                . "ve seanslar arasında nasıl gittiğini takip etmeye yarıyor.",
                'Check-in\'i doldur',
                $link,
                'Bağlantı ' . Checkins::TTL_DAYS . ' gün geçerli ve yalnız bir kez kullanılabilir. '
                . 'Kimseyle paylaşmayın. Bu adrese gelen yanıtlar takip edilmemektedir.'
            )
        );
    }

    /**
     * Onam formu bağlantısı — seanstan önce okunsun diye.
     *
     * İçerik disiplini check-in davetiyle aynı: e-posta şifresiz bir kanal.
     * Terapist adı yazılmaz, hangi merkeze ne için gelindiği anlatılmaz;
     * iletiyi başkası görürse kişinin terapide olduğunu ele vermesin.
     *
     * Metnin tek işi bir tıklama almak, ama check-in'den bir farkla: burada
     * "yarım dakika" denemez, metin uzun. Onun yerine NEDEN okunması gerektiği
     * ve okumanın seansta zaman kazandıracağı söyleniyor — Gökçe'nin sahadan
     * söylediği şey tam buydu, kimse süreden gideceğini düşündüğü bir metni
     * önemseyerek okumuyor.
     *
     * @param array<string,mixed> $row full_name + email taşıyan satır
     */
    public static function consentRequest(array $row, string $link): bool
    {
        if (($row['email'] ?? null) === null) {
            return false;
        }

        $firstName = (string) (preg_split('/\s+/', trim((string) $row['full_name']))[0] ?? '');

        return Mailer::send(
            (string) $row['email'],
            'Onam formu — Mağusa Psikoloji',
            Mailer::template(
                'İlk seansımızdan önce okumanız için',
                "Merhaba {$firstName},\n\nSüreç boyunca geçerli olacak çerçeveyi anlatan bir metin: "
                . "seansların işleyişi, gizlilik, bilgilerinizin nasıl saklandığı ve haklarınız. "
                . "Sayfanın sonunda bir onay kutusu var.\n\n"
                . "Kendi zamanınızda okuyabilmeniz için önceden gönderiyoruz; "
                . "böylece ilk seansta okumaya vakit ayırmamız gerekmiyor.",
                'Formu oku ve onayla',
                $link,
                'Bağlantı ' . Consent::TTL_DAYS . ' gün geçerli ve yalnız bir kez kullanılabilir. '
                . 'Kimseyle paylaşmayın. Bu adrese gelen yanıtlar takip edilmemektedir.'
            )
        );
    }

    /**
     * @param  string $event created | updated | cancelled
     * @param  int|null $actorId İşlemi yapan kişiye kendi eylemi bildirilmez.
     * @return list<string> Gönderilemeyen adresler.
     */
    public static function appointment(int $appointmentId, string $event, ?int $actorId = null): array
    {
        if (!isset(self::SUBJECTS[$event])) {
            return [];
        }

        $row = Db::one(
            'SELECT a.*, c.full_name AS client_name, c.email AS client_email, c.user_id AS client_user_id,
                    t.id AS therapist_user_id, t.full_name AS therapist_name, t.email AS therapist_email
               FROM appointments a
               JOIN clients c ON c.id = a.client_id
               JOIN users   t ON t.id = a.therapist_id
              WHERE a.id = ? LIMIT 1',
            [$appointmentId]
        );
        if ($row === null) {
            return [];
        }

        $when     = tr_date_label(substr((string) $row['starts_at'], 0, 10)) . ' saat ' . dt($row['starts_at'], 'H:i');
        $location = Scheduling::locationLabel($row['location']);
        $failed   = [];

        // ── Birey ──────────────────────────────────────────────
        if ($row['client_email'] !== null && (int) $row['client_user_id'] !== (int) $actorId) {
            $body = match ($event) {
                'cancelled' => "Merhaba {$row['client_name']},\n\n{$when} tarihli randevunuz iptal edilmiştir. Yeni bir randevu için merkezimizle iletişime geçebilirsiniz.",
                'updated'   => "Merhaba {$row['client_name']},\n\nRandevunuz güncellendi.\n\nYeni zaman: {$when}\nTerapist: {$row['therapist_name']}\nGörüşme: {$location}",
                default     => "Merhaba {$row['client_name']},\n\nRandevunuz oluşturuldu.\n\nZaman: {$when}\nTerapist: {$row['therapist_name']}\nGörüşme: {$location}",
            };

            $sent = Mailer::send(
                (string) $row['client_email'],
                self::SUBJECTS[$event] . ' — Mağusa Psikoloji',
                Mailer::template(
                    self::SUBJECTS[$event],
                    $body,
                    null,
                    null,
                    'Değişiklik talebiniz için lütfen merkezimizi arayın. Bu adrese gelen yanıtlar takip edilmemektedir.'
                )
            );
            if (!$sent) {
                $failed[] = (string) $row['client_email'];
            }
        }

        // ── Terapist ─────────────────────────────────────────────
        // Kendi yaptığı değişiklik için terapiste e-posta gitmez; yalnız randevuyu
        // başkası (yönetim) düzenlediğinde takvimi değiştiği haber verilir.
        if ($row['therapist_email'] !== null && (int) $row['therapist_user_id'] !== (int) $actorId) {
            $heading = match ($event) {
                'cancelled' => 'Takviminizden bir randevu düştü',
                'updated'   => 'Takviminizdeki bir randevu güncellendi',
                default     => 'Takviminize yeni randevu eklendi',
            };

            $sent = Mailer::send(
                (string) $row['therapist_email'],
                $heading . ' — Mağusa Psikoloji',
                Mailer::template(
                    $heading,
                    "Merhaba {$row['therapist_name']},\n\nZaman: {$when}\nBirey: {$row['client_name']}\nGörüşme: {$location}",
                    'Takvimi aç',
                    rtrim((string) Config::get('app.url'), '/') . '/randevular?hafta=' . substr((string) $row['starts_at'], 0, 10)
                )
            );
            if (!$sent) {
                $failed[] = (string) $row['therapist_email'];
            }
        }

        return $failed;
    }
}
