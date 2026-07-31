<?php
use Panel\Csrf;
use Panel\Ecosystem;
/** @var array $client @var ?int $age */
/** @var list<array<string,mixed>> $fields @var string $prompt @var string $promptDefault */
/** @var bool $editable */

// Hangi alanların sorulacağı klinik bir karar, ayar değil: kardeşi olmayan bir
// çocuğa her hafta "Kardeş" sormak formu kendi gözünde gereksiz kılar ve
// doldurma oranını düşürür. Bu yüzden ekran "aç/kapat" değil, "bu çocuğun
// hayatında ne var" sorusu gibi kuruluyor.
//
// Metinler de aynı sorunun devamı: alan açık ama adı ailenin kullandığı ad
// değilse ("Büyükler" yerine "Babaanne"), ebeveyn o alanı boş geçiyor. Sözlük
// ortak dili tutuyor, buradaki kutular o dili tek bir eve çeviriyor.
$core   = [];
$rest   = [];
$custom = [];
foreach ($fields as $field) {
    if ($field['custom']) {
        $custom[] = $field;
    } elseif ($field['core']) {
        $core[] = $field;
    } else {
        $rest[] = $field;
    }
}

$openCount = count(array_filter($fields, static fn (array $f): bool => (bool) $f['open']));

/** Tek satır: kutucuk + ad + ipucu. Üç bölüm de aynı satırı çiziyor. */
$row = static function (array $field) use ($editable): void {
    $id     = 'alan_' . $field['key'];
    $name   = 'alan[' . $field['key'] . ']';
    $custom = (bool) $field['custom'];
    ?>
    <div class="px-5 py-3 border-t border-warm-secondary">
      <label class="pick flex items-start gap-3 cursor-pointer -m-1 p-1" for="<?= e($id) ?>">
        <input type="checkbox" id="<?= e($id) ?>" name="<?= e($name) ?>[acik]" value="1"
               <?= $field['open'] ? 'checked' : '' ?> class="mt-1 shrink-0">
        <span class="min-w-0">
          <span class="text-sm text-ink">
            <?= $field['label'] !== '' ? e((string) $field['label']) : 'Boş yuva' ?>
          </span>
          <?php if (!$custom && $field['label'] !== $field['default_label']): ?>
            <span class="chip chip-neutral ml-1">uyarlandı</span>
          <?php endif; ?>
          <?php if ($custom): ?>
            <span class="chip chip-neutral ml-1">elle eklendi</span>
          <?php endif; ?>
        </span>
      </label>

      <?php if ($editable): ?>
        <div class="mt-2 pl-7 grid gap-2 sm:grid-cols-2">
          <span>
            <label class="field-label" for="<?= e($id) ?>_ad">Ebeveynin gördüğü ad</label>
            <input class="field" type="text" id="<?= e($id) ?>_ad" name="<?= e($name) ?>[ad]"
                   value="<?= e((string) $field['label']) ?>" maxlength="60"
                   placeholder="<?= e($custom ? 'Örn. Dans kursu' : (string) $field['default_label']) ?>">
          </span>
          <span>
            <label class="field-label" for="<?= e($id) ?>_ipucu">Altındaki açıklama</label>
            <input class="field" type="text" id="<?= e($id) ?>_ipucu" name="<?= e($name) ?>[ipucu]"
                   value="<?= e((string) $field['hint']) ?>" maxlength="120"
                   placeholder="<?= e($custom ? 'İsteğe bağlı — neyi kapsadığı' : (string) $field['default_hint']) ?>">
          </span>
        </div>
      <?php else: ?>
        <p class="field-hint pl-7"><?= e((string) $field['hint']) ?></p>
      <?php endif; ?>
    </div>
    <?php
};
?>

<header class="mb-6">
  <p class="eyebrow">
    <a href="<?= e(url('/danisanlar/' . (int) $client['id'])) ?>" class="btn-text btn-text-quiet">← <?= e($client['full_name']) ?></a>
  </p>
  <h1 class="page-title mt-2">Sorulan alanlar</h1>
  <p class="page-sub">
    Haftalık check-in'in ikinci sayfasında bu dosyada hangi alanların
    görüneceği ve hangi kelimelerle sorulacağı. Değişiklik bundan sonra üretilen
    bağlantılarda geçerli olur; geçmiş kayıtlar olduğu gibi kalır.
  </p>
</header>

<form method="post" action="<?= e(url('/danisanlar/' . (int) $client['id'] . '/alanlar')) ?>" class="sheet">
  <?= Csrf::field() ?>

  <?php if (!$editable): ?>
    <div class="px-5 pt-4">
      <p class="note note-info">
        Metin uyarlaması için bekleyen bir veritabanı güncellemesi var; şu an
        yalnız alanlar açılıp kapatılabiliyor. Sistem ekranından uygulandığında
        adlar ve açıklamalar da bu ekrandan düzenlenebilir olacak.
      </p>
    </div>
  <?php endif; ?>

  <?php // ── Halkanın üstündeki tek soru ────────────────────────────────
        // Ergen ve yetişkin dosyalarında "çocuğunun sırtını" diye başlayan
        // cümle yanlış kişiye sesleniyordu; formu kendisi dolduran on yedi
        // yaşındaki biri için yazılmamıştı. ?>
  <div class="sheet-head">
    <div>
      <h2 class="sheet-title">Sorunun kendisi</h2>
      <p class="text-sm text-ink-muted mt-1">
        Halkanın üstünde duran tek cümle. Kimin doldurduğuna göre değişir:
        ebeveyn dosyasında “çocuğunun”, ergen dosyasında “senin”.
      </p>
    </div>
  </div>

  <div class="px-5 py-4 border-t border-warm-secondary">
    <?php if ($editable): ?>
      <label class="field-label" for="soru">Soru metni</label>
      <input class="field" type="text" id="soru" name="soru" maxlength="200"
             value="<?= e($prompt) ?>" placeholder="<?= e($promptDefault) ?>">
      <?php if ($prompt !== $promptDefault): ?>
        <p class="field-hint">
          Varsayılan: <span class="text-ink-muted">“<?= e($promptDefault) ?>”</span>
          — alanı boşaltıp kaydederseniz buna geri döner.
        </p>
      <?php endif; ?>
    <?php else: ?>
      <p class="text-sm text-ink">“<?= e($prompt) ?>”</p>
    <?php endif; ?>
  </div>

  <?php foreach ([
      ['Çekirdek', 'Her dosyada açık başlar.', $core],
      ['Koşullu', 'Yalnız o çocuğun hayatında karşılığı varsa açın. İnanç ve gelenek '
          . 'alanını ailenin kendisi istemedikçe açmayın.', $rest],
      ['Elle eklenen', 'Sözlükte karşılığı olmayan şeyler için: “Dans kursu”, '
          . '“Babanın nöbetleri”, “Yeni bebek”. Dosya başına en fazla '
          . Ecosystem::MAX_CUSTOM . ' tane. Ad verilmemiş yuva yok sayılır; adı '
          . 'silmek alanı kaldırır, geçmiş işaretleri şeritte kalır.',
          // Göç uygulanmadıysa yuvalar hiç çizilmez: adı saklanamayan bir alanı
          // doldurtmak, kaydedilmeyecek bir formu doldurtmak olurdu.
          $editable ? $custom : []],
  ] as [$title, $note, $group]): ?>
    <?php if ($group === []) { continue; } ?>
    <div class="sheet-head border-t border-warm-secondary">
      <div>
        <h2 class="sheet-title"><?= e($title) ?></h2>
        <p class="text-sm text-ink-muted mt-1"><?= e($note) ?></p>
      </div>
    </div>

    <?php foreach ($group as $field) { $row($field); } ?>
  <?php endforeach; ?>

  <div class="sheet-foot">
    <button class="btn btn-primary">Kaydet</button>
    <p class="field-hint">
      Şu an <span class="num"><?= (int) $openCount ?></span> alan açık; en fazla
      <span class="num"><?= Ecosystem::MAX_OPEN ?></span> açılabilir, fazlası
      kaydedilmez. Sekizden sonrası halkada sıkışmaya başlar —
      <strong>gerçekten sorulacak</strong> olanı açın. Her ek alan doldurma
      süresini uzatıyor ve ölçtüğümüz tek şey formun doldurulup doldurulmadığı.
    </p>
    <p class="field-hint">
      Metin kutuları boş bırakılırsa ya da varsayılanla aynı kalırsa dosya
      sözlüğe bağlı kalır: ilerideki bir düzeltme bu dosyada da görünür.
    </p>
  </div>
</form>
