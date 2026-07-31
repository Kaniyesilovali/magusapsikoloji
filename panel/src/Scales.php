<?php
declare(strict_types=1);

namespace Panel;

/**
 * Check-in'de sorulan ölçekler — kaç tane, hangi cümleyle, hangi yönde.
 *
 * Üç soru (ruh hali, uyku, kaygı) kodda sabitti ve metinleri düzenlenebilir
 * olduktan sonra bile sayısı öyle kaldı: dördüncüsünü eklemek göç istiyordu.
 * Merkezin "bu dönem iştahı da soralım" demesi bir yazılım sürümüne bağlı
 * kalmamalı; liste artık veri (bkz. 010_checkin_olcekleri.sql).
 *
 * İki kural yapıyı belirliyor:
 *
 *  1. YÖN veridir, süs değil. `direction` +1 ise yüksek değer iyidir (ruh hali,
 *     uyku), −1 ise kötüdür (kaygı). Uçların ADI serbestçe yazılır ama yön
 *     eğrinin nasıl okunacağını söylüyor; bu yüzden ayrı bir alan ve düzenleme
 *     ekranında açıkça soruluyor.
 *  2. Ölçek listeden kalkınca CEVAPLARI KALIR. `checkin_scores.scale_key`
 *     yabancı anahtar değil: sekiz hafta "iştah" sorulduysa o sekiz sayı
 *     silinmez. Ölçmeyi bırakmak, ölçtüğünü unutmak değildir.
 *
 * Göç uygulanmadan da çalışır: tablo yoksa liste koddaki üç ölçekten kurulur
 * ve panelde "önce güncellemeyi uygulayın" denir.
 */
final class Scales
{
    /**
     * Aynı anda açık olabilecek en fazla ölçek.
     *
     * Altı: form her ölçekte bir kaydırıcı daha uzuyor ve bu döngünün ölçtüğü
     * tek şey doldurma oranı. Üçten altıya çıkmak yarım dakikayı bir dakikaya
     * çıkarır; altıdan sonrası telefonda kaydırılan bir ankettir.
     */
    public const MAX = 6;

    /**
     * Koddan gelen üç ölçek. Silinemezler — yalnız kapatılabilirler.
     *
     * Sebep geçmiş: bu üçünün cevapları `checkins` tablosunun kendi
     * sütunlarında da duruyor ve eğrinin on iki haftalık penceresi onlardan
     * besleniyor. Tanımı silmek veriyi silmezdi ama okunamaz hâle getirirdi.
     */
    public const BUILTIN = ['mood', 'sleep_quality', 'anxiety'];

    /** Yüksek değer iyi / kötü. */
    public const UP_GOOD = 1;
    public const UP_BAD  = -1;

    /**
     * Bütün ölçekler, sırayla.
     *
     * @return list<array{key:string,label:string,question:string,low:string,high:string,direction:int,enabled:bool,builtin:bool}>
     */
    public static function all(bool $onlyEnabled = false): array
    {
        $scales = Schema::checkinScalesReady() ? self::fromTable() : self::fromCode();

        if ($onlyEnabled) {
            $scales = array_values(array_filter($scales, static fn (array $s): bool => $s['enabled']));
        }

        return $scales;
    }

    /** @return list<string> */
    public static function keys(bool $onlyEnabled = true): array
    {
        return array_map(static fn (array $s): string => $s['key'], self::all($onlyEnabled));
    }

    /** Anahtarı verilen ölçek — yoksa null. */
    public static function find(string $key): ?array
    {
        foreach (self::all() as $scale) {
            if ($scale['key'] === $key) {
                return $scale;
            }
        }

        return null;
    }

    /**
     * Örüntülerin karşılaştırdığı ölçek.
     *
     * "Okul zorladığı haftalarda ruh hali düşüyor" cümlesinin ilk yarısı
     * ekolojik şeritten, ikinci yarısı bu ölçekten geliyor. Ruh hali varsa o;
     * yoksa yüksek değeri iyi olan ilk açık ölçek — kaygıyla kurulan aynı cümle
     * ters okunurdu.
     */
    public static function primaryKey(): string
    {
        $open = self::all(true);

        foreach ($open as $scale) {
            if ($scale['key'] === 'mood') {
                return 'mood';
            }
        }
        foreach ($open as $scale) {
            if ($scale['direction'] === self::UP_GOOD) {
                return $scale['key'];
            }
        }

        return $open === [] ? 'mood' : $open[0]['key'];
    }

    /**
     * Düzenleme ekranından gelen listeyi yazar.
     *
     * Girdi sıralıdır: satırların ekrandaki sırası ölçeklerin sırasıdır.
     * Anahtarı olan satır güncellenir, olmayan satır yeni ölçektir, `sil`
     * işaretli satır (yalnız kodda tanımlı olmayanlar) tanımdan kalkar.
     *
     * @param list<array<string,string>> $rows
     */
    public static function save(array $rows): void
    {
        if (!Schema::checkinScalesReady()) {
            return;
        }

        $existing = array_column(self::fromTable(), null, 'key');
        $seen     = [];
        $open     = 0;
        $sort     = 0;

        foreach ($rows as $row) {
            $key      = trim((string) ($row['anahtar'] ?? ''));
            $builtin  = in_array($key, self::BUILTIN, true);
            $label    = self::clean($row['ad'] ?? '', 60);
            $question = self::clean($row['soru'] ?? '', 200);

            // Adsız yuva yok sayılır: ekranda boş satırlar duruyor ve
            // doldurulmayanı kaydetmek, olmayan bir soruyu sormaktır.
            if (!isset($existing[$key]) && $label === '' && $question === '') {
                continue;
            }

            // Var olan bir ölçeğin alanı boşaltıldıysa eskisi korunur: adsız ya
            // da sorusuz bir ölçek forma çizilemez ve silme işlemi ayrı bir
            // kutu — boş bırakmak silmek anlamına gelmemeli.
            if (isset($existing[$key])) {
                $label    = $label === '' ? $existing[$key]['label'] : $label;
                $question = $question === '' ? $existing[$key]['question'] : $question;
            }

            if (($row['sil'] ?? '') !== '' && !$builtin) {
                if ($key !== '') {
                    Db::run('DELETE FROM checkin_scales WHERE scale_key = ?', [$key]);
                }
                continue;
            }

            $enabled = ($row['acik'] ?? '') !== '' && $open < self::MAX;
            if ($enabled) {
                $open++;
            }

            $direction = (int) ($row['yon'] ?? self::UP_GOOD) === self::UP_BAD ? self::UP_BAD : self::UP_GOOD;
            $low       = self::clean($row['alt'] ?? '', 60);
            $high      = self::clean($row['ust'] ?? '', 60);

            // Yönü kodda sabit olan üç ölçek: kaygı ters, diğer ikisi düz.
            // Ekran bunu zaten kilitli gösteriyor; kural burada da duruyor ki
            // elle gönderilen bir form geçmişi baş aşağı çeviremesin.
            if ($builtin) {
                $direction = $key === 'anxiety' ? self::UP_BAD : self::UP_GOOD;
            }

            if ($key !== '' && isset($existing[$key])) {
                Db::run(
                    'UPDATE checkin_scales
                        SET label = ?, question = ?, low_label = ?, high_label = ?,
                            direction = ?, sort = ?, enabled = ?
                      WHERE scale_key = ?',
                    [$label, $question, $low, $high, $direction, $sort++, $enabled ? 1 : 0, $key]
                );
            } else {
                // Bilinmeyen anahtar gönderilmişse yok sayılır ve yenisi
                // üretilir: anahtar geçmiş cevapların adresi, dışarıdan
                // yazdırılmaz.
                $key = self::newKey($label, $seen);
                Db::run(
                    'INSERT INTO checkin_scales
                        (scale_key, label, question, low_label, high_label, direction, sort, enabled, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())',
                    [$key, $label, $question, $low, $high, $direction, $sort++, $enabled ? 1 : 0]
                );
            }

            $seen[] = $key;
        }
    }

    // ── Kaynaklar ───────────────────────────────────────────────

    /** @return list<array<string,mixed>> */
    private static function fromTable(): array
    {
        $scales = [];
        foreach (Db::all('SELECT * FROM checkin_scales ORDER BY sort, id') as $row) {
            $key      = (string) $row['scale_key'];
            $scales[] = [
                'key'       => $key,
                'label'     => (string) $row['label'],
                'question'  => (string) $row['question'],
                'low'       => (string) $row['low_label'],
                'high'      => (string) $row['high_label'],
                'direction' => (int) $row['direction'] === self::UP_BAD ? self::UP_BAD : self::UP_GOOD,
                'enabled'   => (int) $row['enabled'] === 1,
                'builtin'   => in_array($key, self::BUILTIN, true),
            ];
        }

        return $scales;
    }

    /**
     * Göç uygulanmadan: koddaki üç ölçek, settings'teki düzenlemeleriyle.
     *
     * @return list<array<string,mixed>>
     */
    private static function fromCode(): array
    {
        $questions = Checkins::questions();
        $scales    = [];

        foreach (Checkins::measures() as $key => $measure) {
            $scales[] = [
                'key'       => $key,
                'label'     => $measure['label'],
                'question'  => $questions[$key] ?? '',
                'low'       => $measure['low'],
                'high'      => $measure['high'],
                'direction' => $key === 'anxiety' ? self::UP_BAD : self::UP_GOOD,
                'enabled'   => true,
                'builtin'   => true,
            ];
        }

        return $scales;
    }

    /**
     * Yeni ölçeğin anahtarı — adından türetilir.
     *
     * Anahtar veritabanında ve geçmiş cevaplarda kalıcıdır, bu yüzden ad
     * sonradan değişse bile değişmez. Okunur olması yedeği elden inceleyen biri
     * için değerli: `istah` , `olcek_4`'ten fazlasını söyler.
     *
     * @param list<string> $seen
     */
    private static function newKey(string $label, array $seen): string
    {
        $slug = strtr(mb_strtolower($label, 'UTF-8'), [
            'ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u', 'â' => 'a', 'î' => 'i', 'û' => 'u',
        ]);
        $slug = trim(preg_replace('/[^a-z0-9]+/', '_', $slug) ?? '', '_');
        $slug = $slug === '' ? 'olcek' : mb_substr($slug, 0, 24, 'UTF-8');

        $taken = array_merge($seen, array_column(self::fromTable(), 'key'), self::BUILTIN);
        $key   = $slug;
        $n     = 2;
        while (in_array($key, $taken, true)) {
            $key = $slug . '_' . $n++;
        }

        return $key;
    }

    private static function clean(string $raw, int $max): string
    {
        return trim(mb_substr(preg_replace('/\s+/u', ' ', $raw) ?? '', 0, $max));
    }
}
