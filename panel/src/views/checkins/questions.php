<?php
use Panel\Checkins;
use Panel\Csrf;
/** @var array<string,string> $questions @var array<string,string> $defaults */
/** @var array<string,array{label:string,low:string,high:string}> $measures */
/** @var array<string,array{label:string,low:string,high:string}> $measureDefaults */
/** @var array<string,string> $texts @var array<string,array<string,mixed>> $textFields */
/** @var list<array<string,mixed>> $roster @var bool $ready @var bool $switchable */
/** @var bool $scalesReady */
/** @var array{enabled:bool,lastRun:?string,lastResult:?string,stale:bool} $cron */

// İki soru, tek ekran: haftalık bağlantıda NE soruluyor ve KİME gidiyor.
// Ayrı sayfalara bölünseydi ikisi de tek başına yarım kalırdı — metni
// değiştiren kişi çoğu zaman "bu ay şuna göndermeyelim" diyen kişiyle aynı.
//
// Aynı gerekçe formun kendisi için de geçerli ve bugüne kadar tutulmuyordu:
// sayfa tek ekrandı ama İKİ form ve iki "Kaydet" taşıyordu. Yanlış düğmeye
// basmak öteki formdaki kaydedilmemiş her şeyi sessizce siliyor, araya konan
// uyarı bunu yalnız haber veriyordu. Artık tek form, tek düğme: silinecek bir
// şey yok, uyarıya da gerek yok.
//
// Metin düzenlenebilir, ölçek değil. Her sorunun altında yönü yazılı duruyor:
// terapist cümleyi değiştirirken 1'in ve 10'un ne anlama geldiğini görmezse
// "kaygın ne kadar azdı?" gibi ters bir soru yazar ve eğri sessizce yalan söyler.

$tones = [
    'go'      => 'chip chip-go',
    'stop'    => 'chip chip-stop',
    'done'    => 'chip chip-done',
    'neutral' => 'chip chip-neutral',
];
?>

<header class="flex flex-wrap items-end justify-between gap-4 mb-6">
  <div>
    <?php // Başlık üstü satırı gönderimin o anki hâlini söylüyor: "Sırada"
          // rozetleri bir söz veriyor ve sözü tutanın çalışıp çalışmadığı
          // sayfanın en üstünde görünmeli. ?>
    <p class="eyebrow">
      <?php if (!$cron['enabled']): ?>
        Haftalık gönderim kapalı
      <?php elseif ($cron['lastRun'] === null): ?>
        Cron hiç çalışmadı
      <?php elseif ($cron['stale']): ?>
        Cron durmuş olabilir
      <?php else: ?>
        Haftalık gönderim çalışıyor
      <?php endif; ?>
    </p>
    <h1 class="page-title mt-2">Haftalık check-in</h1>
    <p class="page-sub">
      Görüşmecinin gördüğü bütün metinler ve e-postanın kimlere gittiği. Ölçek her
      soruda 1–10 arası bir kaydırıcı; değişen cümleler ve uçların adları.
    </p>
  </div>
  <?php // Önizleme yeni sekmede açılıyor: buradaki yarım düzenleme kaybolmasın.
        // Gösterdiği şey KAYDEDİLMİŞ metin; bunu önizlemenin kendi şeridi
        // söylüyor, çünkü uyarının okunması gereken yer orası. ?>
  <a href="<?= e(url('/check-in-sorulari/onizleme')) ?>" target="_blank" rel="noopener"
     class="btn btn-quiet">Formu önizle</a>
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

  <?php
  // ── Kime gidiyor ────────────────────────────────────────────────
  //
  // Aynı formun devamı, ayrı bir gönderim değil. Buradaki işaret YALNIZ
  // haftalık e-postayı yönetir. Takibi kapatmaz, kaydı arşivlemez, geçmişi
  // gizlemez — kapalı bir görüşmeciye terapist elden bağlantı gönderdiğinde
  // form eskisi gibi çalışır. Bugüne kadar gönderimi durdurmanın tek yolu
  // kaydı arşivlemekti; "bu dönem doldurmayalım" diyen bir aile için bu fazla
  // ağır bir işlemdi.
  //
  // Listede puan ya da cümle YOK: ekran yöneticiye de açık (bkz. Rbac →
  // checkin.manage) ve cevaplar yalnız terapiste ait. Görünen tek şey adın
  // yanında gönderimin durumu.
  ?>
  <div class="sheet-head border-t border-warm-secondary">
    <div>
      <h2 class="sheet-title">Haftalık e-posta kimlere gidiyor</h2>
      <p class="text-sm text-ink-muted mt-1">
        İşaretli görüşmecilere cron haftada bir bağlantı yollar. İşareti
        kaldırmak takibi kapatmaz — terapist görüşmeci sayfasından elle bağlantı
        göndermeye devam edebilir.
      </p>
      <?php // Aşağıdaki "Sırada" rozeti bir söz: cron koşarsa bu kişiye gider.
            // Cron kurulmamışsa ya da anahtar kapalıysa sözü tutan yok ve liste
            // her hafta çalışıyormuş gibi görünür. Durum bu yüzden burada,
            // rozetlerin hemen üstünde duruyor. ?>
      <p class="text-sm text-ink-muted mt-2">
        <?php if (!$cron['enabled']): ?>
          <span class="chip chip-stop">gönderim kapalı</span>
          Haftalık gönderim <code>settings.checkins_enabled</code> ile kapatılmış:
          aşağıda “Sırada” yazsa da hiçbir e-posta çıkmıyor.
        <?php elseif ($cron['lastRun'] === null): ?>
          <span class="chip chip-stop">cron hiç çalışmadı</span>
          Gönderimi cPanel'deki cron yapar, panel kendi kendine tetiklemez —
          kurulmamış olabilir. Komut <strong>Sistem</strong> ekranında.
        <?php elseif ($cron['stale']): ?>
          <span class="chip chip-stop">cron durmuş olabilir</span>
          Haftalık bir iş ama son koşusu
          <span class="num"><?= e(dt($cron['lastRun'])) ?></span> —
          <?= e((string) $cron['lastResult']) ?>.
        <?php else: ?>
          <span class="chip chip-go">cron çalışıyor</span>
          Son koşu <span class="num"><?= e(dt($cron['lastRun'])) ?></span> —
          <?= e((string) $cron['lastResult']) ?>.
        <?php endif; ?>
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

    <?php foreach ($roster as $row): ?>
      <?php
      $state = $row['state'];
      $email = trim((string) ($row['email'] ?? ''));
      ?>
      <?php // Kayda giden bağlantı etiketin DIŞINDA: <label> içine konan bir
            // <a> hem geçersiz işaretleme hem de tıklandığında kutucuğu
            // çeviriyor — kaydı açmak isteyen kişi gönderimi kapatmış olurdu. ?>
      <div class="flex items-start gap-3 px-5 py-3 border-t border-warm-secondary">
        <label class="flex items-start gap-3 min-w-0 flex-1 -m-1 p-1 <?= $switchable ? 'pick cursor-pointer' : '' ?>">
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
  <?php endif; ?>

  <?php // Tek "Kaydet": metinler ve gönderim listesi aynı gönderime giriyor. ?>
  <div class="sheet-foot">
    <button class="btn btn-primary">Kaydet</button>
    <p class="field-hint">
      Metin değişikliği yalnız bundan sonra üretilen bağlantılarda görünür; şu an
      görüşmecinin elinde bekleyen bir bağlantı varsa o eski metni gösterir.
      Daha önce verilmiş cevaplar etkilenmez — sayılar aynı ölçekte kalır.
    </p>
    <p class="field-hint">
      Gönderim listesinde yalnız yukarıda görünen kayıtlar etkilenir. Aynı kişiye
      <span class="num"><?= Checkins::MIN_DAYS_BETWEEN ?></span> gün geçmeden
      ikinci bağlantı gitmez; üst üste cevapsız kalan
      <span class="num"><?= Checkins::MAX_UNANSWERED ?></span> bağlantıdan
      sonra gönderim kendiliğinden susar ve elle gönderilen bir bağlantı
      doldurulunca sürer.
    </p>
  </div>
</form>
