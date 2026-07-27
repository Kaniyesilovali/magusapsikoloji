<?php
declare(strict_types=1);

namespace Panel;

/**
 * Davet gönderimi — "hesap açıldı, şifreni belirle" akışının tek kaynağı.
 *
 * Kullanıcı ekranından elle açılan hesaplar da, görüşmeci kaydıyla birlikte
 * kendiliğinden açılan hesaplar da buradan geçer; iki yerde ayrı yazılsaydı
 * biri düzeltilirken diğeri geride kalırdı.
 */
final class Invites
{
    /** Davet bağlantısının geçerlilik süresi (saat). */
    private const TTL_HOURS = 48;

    /**
     * Daveti gönderir. E-posta çalışmasa bile bağlantı üretilmiştir; çağıran taraf
     * onu ekranda gösterir ki yönetici WhatsApp gibi başka bir kanaldan iletebilsin.
     *
     * @return array{sent:bool,link:string,error:?string}
     */
    public static function send(int $userId, string $name, string $email, string $inviterName): array
    {
        $token = Auth::createToken($userId, 'invite', self::TTL_HOURS);
        $link  = rtrim((string) Config::get('app.url'), '/') . '/sifre-belirle?token=' . $token;

        $sent = Mailer::send(
            $email,
            'Mağusa Psikoloji paneline davet edildiniz',
            Mailer::template(
                'Hesabınız hazır',
                "Merhaba {$name},\n\n{$inviterName} sizin için Mağusa Psikoloji yönetim panelinde bir hesap oluşturdu. Aşağıdaki düğmeyle şifrenizi belirleyip giriş yapabilirsiniz. Bağlantı " . self::TTL_HOURS . " saat geçerlidir.",
                'Şifremi belirle',
                $link,
                'Bu bağlantıyı kimseyle paylaşmayın. Süresi dolarsa yöneticinizden yeni bir davet isteyin.'
            )
        );

        return ['sent' => $sent, 'link' => $link, 'error' => Mailer::lastError()];
    }

    /**
     * Görüşmeciye gönderilen davet, yönetici davetinden başka bir şey vaat eder:
     * kişi panele "çalışmaya" değil, kendi randevu ve ödeme durumuna bakmaya
     * geliyor. Metin bunu söylemezse gelen e-posta yanlış yere davet ediyor gibi
     * okunur.
     *
     * @return array{sent:bool,link:string,error:?string}
     */
    public static function sendToClient(int $userId, string $name, string $email, string $centerName = 'Mağusa Psikoloji'): array
    {
        $token = Auth::createToken($userId, 'invite', self::TTL_HOURS);
        $link  = rtrim((string) Config::get('app.url'), '/') . '/sifre-belirle?token=' . $token;

        $sent = Mailer::send(
            $email,
            "{$centerName} — randevu ve ödeme sayfanız hazır",
            Mailer::template(
                'Sayfanız hazır',
                "Merhaba {$name},\n\n{$centerName} sizin için kişisel bir sayfa oluşturdu. Buradan yaklaşan randevularınızı, geçmiş seanslarınızı ve ödeme durumunuzu görebilirsiniz. Aşağıdaki düğmeyle şifrenizi belirleyip giriş yapabilirsiniz. Bağlantı " . self::TTL_HOURS . " saat geçerlidir.",
                'Şifremi belirle',
                $link,
                'Bu bağlantıyı kimseyle paylaşmayın. Süresi dolarsa merkezden yeni bir bağlantı isteyebilirsiniz.'
            )
        );

        return ['sent' => $sent, 'link' => $link, 'error' => Mailer::lastError()];
    }

    /**
     * Davet bağlantısını bir sonraki sayfada gösterilmek üzere saklar — gönderim
     * başarılı olsa bile.
     *
     * mail() başarı döndürmesi yalnızca iletinin sunucunun posta servisine teslim
     * edildiği anlamına gelir; teslim edilip edilmediğini garanti etmez (spam filtresi,
     * SPF/DKIM, alıcı reddi devrede). Bağlantıyı her hâlükârda göstermek, yöneticiyi
     * e-postanın gerçekten ulaşmasına bağımlı olmaktan çıkarır.
     */
    public static function share(string $name, string $email, array $invite, string $context = 'user'): void
    {
        $_SESSION['_invite_link'] = [
            'name'    => $name,
            'url'     => $invite['link'],
            'sent'    => $invite['sent'],
            'context' => $context,
        ];

        if ($invite['sent']) {
            flash('success', "Davet e-postası {$email} adresine gönderildi.");
            return;
        }

        flash('warning', 'Davet e-postası gönderilemedi'
            . ($invite['error'] !== null ? ' (' . $invite['error'] . ')' : '')
            . '.');
    }

    /**
     * Saklanan bağlantıyı bir kez okur ve siler.
     *
     * Bağlam eşleşmezse dokunulmaz: kullanıcı ekranından açılan bir davetin
     * bağlantısı, yönetici araya bir görüşmeci sayfası açtı diye orada gösterilip
     * tükenmemeli.
     */
    public static function pending(string $context = 'user'): ?array
    {
        $invite = $_SESSION['_invite_link'] ?? null;
        if ($invite === null || ($invite['context'] ?? 'user') !== $context) {
            return null;
        }
        unset($_SESSION['_invite_link']);

        return $invite;
    }
}
