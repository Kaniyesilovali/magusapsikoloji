<?php
declare(strict_types=1);

namespace Panel;

use RuntimeException;

/**
 * Seans notlarının şifrelenmesi (libsodium secretbox: XSalsa20-Poly1305).
 *
 * Anahtar webroot dışındaki config dosyasında durur. Anahtar kaybolursa notlar
 * KALICI olarak kurtarılamaz — yedeği ayrı bir ortamda tutulmalıdır.
 */
final class Crypto
{
    private static ?string $key = null;

    private static function key(): string
    {
        if (self::$key !== null) {
            return self::$key;
        }
        if (!function_exists('sodium_crypto_secretbox')) {
            throw new RuntimeException('PHP sodium eklentisi yüklü değil; seans notu şifrelemesi kullanılamaz.');
        }
        $raw = base64_decode((string) Config::get('security.note_key', ''), true);
        if ($raw === false || strlen($raw) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new RuntimeException('security.note_key geçersiz: 32 baytlık base64 anahtar bekleniyor.');
        }
        return self::$key = $raw;
    }

    /** @return array{0:string,1:string} [ciphertext, nonce] — ikisi de ham bayt. */
    public static function encrypt(string $plaintext): array
    {
        $nonce  = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = sodium_crypto_secretbox($plaintext, $nonce, self::key());
        return [$cipher, $nonce];
    }

    public static function decrypt(string $ciphertext, string $nonce): string
    {
        $plain = sodium_crypto_secretbox_open($ciphertext, $nonce, self::key());
        if ($plain === false) {
            throw new RuntimeException('Not çözülemedi: anahtar değişmiş veya kayıt bozulmuş olabilir.');
        }
        return $plain;
    }

    public static function available(): bool
    {
        try {
            self::key();
            return true;
        } catch (RuntimeException) {
            return false;
        }
    }
}
