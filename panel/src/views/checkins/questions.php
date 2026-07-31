<?php
use Panel\Checkins;
use Panel\Csrf;
/** @var array<string,string> $questions @var array<string,string> $defaults */
/** @var array<string,array{label:string,low:string,high:string}> $measures */
/** @var array<string,array{label:string,low:string,high:string}> $measureDefaults */
/** @var array<string,string> $texts @var array<string,array<string,mixed>> $textFields */
/** @var list<array<string,mixed>> $roster @var bool $ready @var bool $switchable */

// İki soru, tek ekran: haftalık bağlantıda NE soruluyor ve KİME gidiyor.
// Ayrı sayfalara bölünseydi ikisi de tek başına yarım kalırdı — metni
// değiştiren kişi çoğu zaman "bu ay şuna göndermeyelim" diyen kişiyle aynı.
//
// Metin düzenlenebilir, ölçek değil. Her sorunun altında yönü yazılı duruyor:
// terapist cümleyi değiştirirken 1'in ve 10'un ne anlama geldiğini görmezse
// "kaygın ne kadar azdı?" gibi ters bir soru yazar ve eğri sessizce yalan söyler.
?>

<header class="mb-6">
  <p class="eyebrow">Seanslar arası check-in</p>
  <h1 class="page-title mt-2">Haftalık check-in</h1>
  <p class="page-sub">
    Görüşmecinin gördüğü bütün metinler ve e-postanın kimlere gittiği. Ölçek her
    soruda 1–10 arası bir kaydırıcı; değişen cümleler ve uçların adları.
  </p>
</header>

<form method="post" action="<?= e(url('/check-in-sorulari')) ?>" class="sheet">
  <?= Csrf::field() ?>

  <?php if ($scalesReady): ?>
    <?php require __DIR__ . '/_scales.php'; ?>
  <?php else: ?>

  <?php foreach ($questions as $field => $text): ?>
    <?php
    $measure = $measures[$field];
    $default = $measureDefaults[$field];
    // Yön veri modelinin kendisinde: kaygıda yüksek değer kötü ve eğri bunu
    // böyle okuyor. Uçların ADI değişebilir, YÖNÜ değişemez — bu yüzden her
    // sorunun altında hangi ucun "iyi" olduğu ayrıca yazıyor.
    $goodEnd = $field === 'anxiety' ? '1' : '10';
    ?>
    <div class="px-5 py-4 <?= $field === array_key_first($questions) ? '' : 'border-t border-warm-secondary' ?>">
      <label for="<?= e($field) ?>" class="field-label">
        Soru — <?= e($measure['label']) ?>
      </label>
      <input type="text" id="<?= e($field) ?>" name="<?= e($field) ?>"
             value="<?= e($text) ?>" maxlength="200" class="field">
      <?php if ($text !== $defaults[$field]): ?>
        <p class="field-hint">
          Varsayılan: <span class="text-ink-muted">“<?= e($defaults[$field]) ?>”</span>
          — alanı boşaltıp kaydederseniz buna geri döner.
        </p>
      <?php endif; ?>

      <div class="mt-3 grid gap-2 sm:grid-cols-3">
        <span>
          <label class="field-label" for="<?= e($field) ?>_ad">Panelde görünen ad</label>
          <input class="field" type="text" id="<?= e($field) ?>_ad"
                 name="olcek[<?= e($field) ?>][label]" maxlength="60"
                 value="<?= e($measure['label']) ?>" placeholder="<?= e($default['label']) ?>">
        </span>
        <span>
          <label class="field-label" for="<?= e($field) ?>_alt">1 ucu</label>
          <input class="field" type="text" id="<?= e($field) ?>_alt"
                 name="olcek[<?= e($field) ?>][low]" maxlength="60"
                 value="<?= e($measure['low']) ?>" placeholder="<?= e($default['low']) ?>">
        </span>
        <span>
          <label class="field-label" for="<?= e($field) ?>_ust">10 ucu</label>
          <input class="field" type="text" id="<?= e($field) ?>_ust"
                 name="olcek[<?= e($field) ?>][high]" maxlength="60"
                 value="<?= e($measure['high']) ?>" placeholder="<?= e($default['high']) ?>">
        </span>
      </div>

      <p class="field-hint">
        Bu ölçekte <strong><?= $goodEnd ?></strong> iyi uçtur; eğri de böyle okunuyor.
        Uçların <em>adını</em> değiştirebilirsiniz, <em>yönünü</em> değil — ters
        yazılmış bir uç, geçmiş kayıtları da baş aşağı okutur.
      </p>
    </div>
  <?php endforeach; ?>

  <div class="px-5 pb-4">
    <p class="note note-info">
      Soru ekleyip çıkarabilmek için bekleyen bir veritabanı güncellemesi var;
      şu an koddaki üç sorunun metni düzenlenebiliyor. Sistem ekranından
      uygulandığında liste bu ekrandan yönetilebilir olacak.
    </p>
  </div>
  <?php endif; ?>

  <?php // ── Formun geri kalan metinleri ────────────────────────────────
        // Soruların cümlesi düzenlenebilirken teşekkür sayfası kodda kilitli
        // kalınca ekran yarım bir söz veriyordu: formu okuyan kişi metnin bir
        // kısmının merkeze, bir kısmının yazılıma ait olduğunu bilmez —
        // yalnız ikisi arasındaki dil farkını görür.
        $group = null; ?>

  <?php foreach ($textFields as $key => $meta): ?>
    <?php if ($meta['group'] !== $group): ?>
      <?php $group = $meta['group']; ?>
      <div class="sheet-head border-t border-warm-secondary">
        <h2 class="sheet-title"><?= e($group) ?></h2>
      </div>
    <?php endif; ?>

    <div class="px-5 py-3 border-t border-warm-secondary">
      <label class="field-label" for="metin_<?= e($key) ?>"><?= e($meta['label']) ?></label>
      <?php if (!empty($meta['area'])): ?>
        <textarea class="field" id="metin_<?= e($key) ?>" name="metin[<?= e($key) ?>]"
                  rows="2" maxlength="<?= (int) $meta['max'] ?>"><?= e($texts[$key]) ?></textarea>
      <?php else: ?>
        <input class="field" type="text" id="metin_<?= e($key) ?>" name="metin[<?= e($key) ?>]"
               maxlength="<?= (int) $meta['max'] ?>" value="<?= e($texts[$key]) ?>">
      <?php endif; ?>

      <?php if ($meta['hint'] !== '' || $texts[$key] !== $meta['default']): ?>
        <p class="field-hint">
          <?= e($meta['hint']) ?>
          <?php if ($texts[$key] !== $meta['default']): ?>
            Varsayılan: <span class="text-ink-muted">“<?= e($meta['default']) ?>”</span>
            — boşaltıp kaydederseniz buna döner.
          <?php endif; ?>
        </p>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>

  <div class="sheet-foot">
    <button class="btn btn-primary">Kaydet</button>
    <p class="field-hint">
      Değişiklik yalnız bundan sonra üretilen bağlantılarda görünür; şu an
      görüşmecinin elinde bekleyen bir bağlantı varsa o eski metni gösterir.
      Daha önce verilmiş cevaplar etkilenmez — sayılar aynı ölçekte kalır.
    </p>
  </div>
</form>

<?php
// ── Kime gidiyor ────────────────────────────────────────────────
//
// Bu listedeki işaret YALNIZ haftalık e-postayı yönetir. Takibi kapatmaz,
// kaydı arşivlemez, geçmişi gizlemez — kapalı bir görüşmeciye terapist elden
// bağlantı gönderdiğinde form eskisi gibi çalışır. Bugüne kadar gönderimi
// durdurmanın tek yolu kaydı arşivlemekti; "bu dönem doldurmayalım" diyen bir
// aile için bu fazla ağır bir işlemdi.
//
// Listede puan ya da cümle YOK: ekran yöneticiye de açık (bkz. Rbac →
// checkin.manage) ve cevaplar yalnız terapiste ait. Görünen tek şey adın
// yanında gönderimin durumu.
$tones = [
    'go'      => 'chip chip-go',
    'stop'    => 'chip chip-stop',
    'done'    => 'chip chip-done',
    'neutral' => 'chip chip-neutral',
];
?>

<section class="sheet mt-6">
  <div class="sheet-head">
    <div>
      <h2 class="sheet-title">Haftalık e-posta kimlere gidiyor</h2>
      <p class="text-xs text-ink-light mt-1">
        İşaretli görüşmecilere cron haftada bir bağlantı yollar. İşareti
        kaldırmak takibi kapatmaz — terapist görüşmeci sayfasından elle bağlantı
        göndermeye devam edebilir.
      </p>
    </div>
  </div>

  <?php if (!$ready): ?>
    <p class="sheet-empty">
      Check-in tabloları henüz kurulmamış. Sistem ekranından bekleyen veritabanı
      güncellemelerini uygulayın.
    </p>
  <?php elseif ($roster === []): ?>
    <p class="sheet-empty">Aktif görüşmeci yok.</p>
  <?php else: ?>

    <?php if (!$switchable): ?>
      <div class="px-5 pt-4">
        <p class="note note-info">
          Gönderim anahtarı için bekleyen bir veritabanı güncellemesi var; şu an
          herkes açık sayılıyor. Sistem ekranından uygulandığında bu liste
          kaydedilebilir olacak.
        </p>
      </div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('/check-in-sorulari/alicilar')) ?>">
      <?= Csrf::field() ?>

      <?php foreach ($roster as $row): ?>
        <?php
        $state = $row['state'];
        $email = trim((string) ($row['email'] ?? ''));
        ?>
        <?php // Kayda giden bağlantı etiketin DIŞINDA: <label> içine konan bir
              // <a> hem geçersiz işaretleme hem de tıklandığında kutucuğu
              // çeviriyor — kaydı açmak isteyen kişi gönderimi kapatmış olurdu. ?>
        <div class="flex items-start gap-3 px-5 py-3 border-t border-warm-secondary">
          <label class="flex items-start gap-3 min-w-0 flex-1 <?= $switchable ? 'cursor-pointer' : '' ?>">
            <?php // İsim değeri değil anahtarı taşıyor: kaydederken işaretli
                  // olanların id'si `alici` dizisinin anahtarlarından okunuyor. ?>
            <input type="checkbox" name="alici[<?= (int) $row['id'] ?>]" value="1"
                   <?= $row['auto'] ? 'checked' : '' ?>
                   <?= $switchable ? '' : 'disabled' ?>
                   class="mt-1 shrink-0">
            <span class="min-w-0">
              <span class="text-sm text-ink person"><?= e((string) $row['full_name']) ?></span>
              <span class="<?= e($tones[$state['tone']] ?? $tones['neutral']) ?> ml-1"><?= e($state['label']) ?></span>
              <span class="block text-xs text-ink-light mt-0.5"><?= e($state['detail']) ?></span>
              <span class="block text-xs text-ink-light mt-0.5">
                <?= $email !== '' ? e($email) : '<span class="text-accent-dark">e-posta yok</span>' ?>
                <?php if ($row['last_checkin_at'] !== null): ?>
                  · son check-in <span class="num"><?= e(dt((string) $row['last_checkin_at'], 'd.m.Y')) ?></span>
                <?php elseif ((int) $row['request_count'] > 0): ?>
                  · henüz hiç doldurulmadı
                <?php endif; ?>
              </span>
            </span>
          </label>
          <?php // Alan/metin uyarlaması tek tek dosyaya ait; listeden doğrudan
                // açılıyor ki bunun için görüşmeci sayfasını dolaşmak gerekmesin. ?>
          <span class="flex flex-col items-end gap-1 shrink-0">
            <a href="<?= e(url('/danisanlar/' . (int) $row['id'] . '/alanlar')) ?>" class="btn-text btn-text-quiet">Alanlar</a>
            <a href="<?= e(url('/danisanlar/' . (int) $row['id'])) ?>" class="btn-text btn-text-quiet">Kayıt</a>
          </span>
        </div>
      <?php endforeach; ?>

      <div class="sheet-foot">
        <button class="btn btn-primary" <?= $switchable ? '' : 'disabled' ?>>Gönderim listesini kaydet</button>
        <p class="field-hint">
          Yalnız bu listede görünen kayıtlar etkilenir. Aynı kişiye
          <span class="num"><?= Checkins::MIN_DAYS_BETWEEN ?></span> gün geçmeden
          ikinci bağlantı gitmez; üst üste cevapsız kalan
          <span class="num"><?= Checkins::MAX_UNANSWERED ?></span> bağlantıdan
          sonra gönderim kendiliğinden susar ve elle gönderilen bir bağlantı
          doldurulunca sürer.
        </p>
      </div>
    </form>
  <?php endif; ?>
</section>
