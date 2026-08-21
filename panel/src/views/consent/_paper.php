<?php
/**
 * Onam formunun KÂĞIDI — başlık, metin, aile yakını kutusu ve imza satırları.
 *
 * Üç yerden çağrılıyor ve üçünde de aynı kâğıt olmak zorunda:
 *   consent/print.php   panel içi çıktı (kayıtlı birey ya da boş form)
 *   consent/public.php  herkese açık adres (/onam-formu)
 * İmzalanan kâğıdın panelde görülenden farklı olması, sürüm numarasının
 * verdiği sözü boşa çıkarırdı: aynı numara, aynı kâğıt. Bu yüzden ortak
 * parça — iki şablon olsaydı biri birkaç sürüm sonra sessizce ötekinden
 * ayrılırdı (aynı gerekçe: views/checkin_layout.php).
 *
 * Çağıran şunları hazırlar:
 *   @var string      $text     basılacak metin (o dilin, o sürümün metni)
 *   @var string      $version  kâğıda basılan sürüm numarası
 *   @var string      $lang     'tr' | 'en'
 *   @var string      $name     birey adı; '' ise ad kâğıdın üstünde doldurulur
 *   @var string      $signedOn imza tarihinin başlangıç değeri
 *   @var array|null  $online   çevrimiçi onay künyesi (yoksa null)
 */
$en = $lang === 'en';

// Kâğıdın sözlüğü. Dil YALNIZ kâğıdı değiştiriyor; çağıranın ekranda
// gösterdiği uyarılar Türkçe kalır — onlar bireyin değil, formu basan
// psikoloğun okuduğu yer.
$paper = $en
    ? [
        'subtitle'    => 'Informed consent form',
        'version'     => 'Text version',
        'origin'      => 'This is an English translation of the Turkish informed consent form, version '
                       . $version . '. In the event of any discrepancy, the Turkish text prevails.',
        'kin'         => 'Family Contact',
        'kinName'     => 'Name-Surname:',
        'kinRelation' => 'Relationship:',
        'kinPhone'    => 'Phone number:',
        'kinAddress'  => 'Address:',
        'clientName'  => 'Name-Surname',
        'date'        => 'Date',
        'clientSign'  => 'Signature',
        'psySign'     => 'Psychologist signature',
    ]
    : [
        'subtitle'    => 'Bilgilendirilmiş onam formu',
        'version'     => 'Metin sürümü',
        'origin'      => '',
        'kin'         => 'Aile Yakını Bilgisi',
        'kinName'     => 'İsim-Soyisim:',
        'kinRelation' => 'Yakınlık:',
        'kinPhone'    => 'Telefon Numarası:',
        'kinAddress'  => 'Adres:',
        'clientName'  => 'İsim-Soyisim',
        'date'        => 'Tarih',
        'clientSign'  => 'İmza',
        'psySign'     => 'Psikolog imzası',
    ];
?>
<main class="max-w-3xl mx-auto bg-white my-6 px-10 py-10 print:my-0 print:px-0 print:py-0">
  <header class="border-b border-warm-tertiary pb-4 mb-6">
    <h1 class="font-serif text-lg font-medium text-ink">Mağusa Psikoloji</h1>
    <p class="text-sm text-ink-muted mt-0.5"><?= e($paper['subtitle']) ?></p>
    <p class="eyebrow mt-2"><?= e($paper['version']) ?> <span class="num"><?= e($version) ?></span></p>
  </header>

  <?php
  // Çevirinin künyesi — kâğıda BASILIYOR. İngilizce kâğıdı imzalayan kişi,
  // imzaladığı şeyin hangi metnin çevirisi olduğunu ve bir anlaşmazlıkta
  // hangisinin geçerli sayılacağını kâğıdın kendisinden okuyabilmeli.
  ?>
  <?php if ($paper['origin'] !== ''): ?>
    <p class="text-xs text-ink-muted italic mb-6"><?= e($paper['origin']) ?></p>
  <?php endif; ?>

  <?php
  // Çevrimiçi onay künyesi — kâğıda BASILAN bir satır.
  //
  // Bu kutu, seansta okumaya harcanan zamanı ortadan kaldıran şeyin kendisi:
  // kâğıt, metnin ne zaman ve hangi sürümüyle okunduğunu söylüyor. Altındaki
  // imza artık bir okuma değil, bir doğrulama. Söylenmeseydi psikolog her
  // seansta yeniden "okudunuz mu?" diye sormak ya da hiç sormamak arasında
  // kalırdı ve ikisi de kötüydü.
  ?>
  <?php if (!empty($online)): ?>
    <section class="mb-6 border border-ink/25 rounded-sm px-6 py-4 text-sm text-ink">
      <?php if ($en): ?>
        <p>
          You read and approved version <span class="num"><?= e((string) $online['version']) ?></span>
          <?php // Tarih kâğıdın dilinde: imza satırındaki tarihle aynı biçim. ?>
          of this text online on <span class="num"><?= e(dt((string) $online['approved_at'], 'j F Y')) ?></span>.
          The signature below confirms that approval in session.
        </p>
      <?php else: ?>
        <p>
          Bu metnin <span class="num"><?= e((string) $online['version']) ?></span> sürümünü
          <span class="num"><?= e(dt((string) $online['approved_at'])) ?></span> tarihinde
          çevrimiçi olarak okuyup onayladınız. Aşağıdaki imza, o onayın seansta
          doğrulanmasıdır.
        </p>
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <article class="text-sm text-ink leading-relaxed whitespace-pre-wrap"><?= e($text) ?></article>

  <?php
  // Aile yakını, metnin içinde söz verilen şeydir: "olağandışı bir durumda
  // ulaşabileceğimiz biri". Boş kutu olarak basılıyor, panelden doldurulmuyor —
  // bu bilgiyi birey masada kendi eliyle yazar, kurum ondan önce bilmez.
  ?>
  <section class="mt-8 border border-ink/25 rounded-sm px-6 py-5 text-sm text-ink">
    <p class="font-medium underline underline-offset-4 mb-5"><?= e($paper['kin']) ?></p>
    <div class="grid grid-cols-2 gap-x-8 gap-y-5">
      <p><?= e($paper['kinName']) ?></p>
      <p><?= e($paper['kinRelation']) ?></p>
      <p><?= e($paper['kinPhone']) ?></p>
      <p><?= e($paper['kinAddress']) ?></p>
    </div>
  </section>

  <section class="mt-10 pt-6 border-t border-warm-tertiary text-sm text-ink">
    <dl class="grid grid-cols-2 gap-x-8 gap-y-3 mb-10">
      <dt class="text-ink-muted self-center"><?= e($paper['clientName']) ?></dt>
      <dd>
        <?php if ($name !== ''): ?>
          <span class="person"><?= e($name) ?></span>
        <?php else: ?>
          <?php // Kaydı yoksa ad da kâğıdın üstünde yazılır — elle ya da klavyeyle. ?>
          <label for="clientName" class="sr-only"><?= e($paper['clientName']) ?></label>
          <input id="clientName" type="text" class="fill person" value="" autocomplete="off">
        <?php endif; ?>
      </dd>
      <dt class="text-ink-muted self-center"><?= e($paper['date']) ?></dt>
      <dd>
        <label for="signedOn" class="sr-only"><?= e($paper['date']) ?></label>
        <input id="signedOn" type="text" class="fill num" value="<?= e($signedOn) ?>" autocomplete="off">
      </dd>
    </dl>

    <?php // Karşılıklı bir anlaşma: metin öyle diyor, kâğıt da öyle imzalanıyor. ?>
    <div class="grid grid-cols-2 gap-8">
      <div>
        <p class="eyebrow mb-10"><?= e($paper['clientSign']) ?></p>
        <div class="border-t border-ink/40"></div>
      </div>
      <div>
        <p class="eyebrow mb-10"><?= e($paper['psySign']) ?></p>
        <div class="border-t border-ink/40"></div>
      </div>
    </div>
  </section>
</main>
