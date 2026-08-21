<?php
use Panel\Csrf;
/** @var string $text @var string $version @var bool $isDraft @var int $signed @var array $actor */
/** @var int $online */
/** @var string $textEn @var bool $isDraftEn @var bool $staleEn @var string $versionEn */
/** @var string $publicUrl @var string $publicUrlEn */

// Geri çevrilmiş bir gönderim varsa ekranda kayıttaki metin değil, kişinin
// yazdığı metin durur (bkz. ConsentController::update).
$text    = old('consent_text', $text);
$textEn  = old('consent_text_en', $textEn);
$version = old('consent_version', $version);

// Çeviri alanı kapalı geliyor: İngilizce çıktı her kurumda istenmiyor ve 24
// satırlık ikinci bir metin, asıl metnin altındaki sürüm alanını görünmez
// yapıyordu. Eskimiş ya da geri çevrilmiş bir çeviride açık başlıyor —
// düzeltilecek şeyin bir tık arkasında durması onu unutturur.
//
// Hiç kaydedilmemiş çeviri de açık başlıyor: alan zaten paneldeki taslakla
// dolu geliyor ve tek yapılacak şey onu okuyup Kaydet'e basmak. Formun sitedeki
// İngilizce adresi de buna bağlı (bkz. ConsentController::publicSheet); kapalı
// bir <details> arkasında "çeviriyi kaydedin" demek, kaydedilmeyecek demekti.
$openEn = $staleEn || $isDraftEn || error_for('consent_text_en') !== null;

// ── Doldurulmamış köşeli parantezler ────────────────────────────────────
// Taslak metin kurumun doldurması gereken iki alanla geliyor: saklama süresi
// ve başvuru adresi. Kaydedilmiş olmaları onları doldurulmuş yapmıyor ve
// panel bugüne kadar yalnız "hiç kaydedilmemiş" hâli uyarıyordu — oysa
// yarım bırakılmış bir metin de imzalanıyor, artık sitede de yayımlanıyor.
// Metin alanının kendisinde arıyoruz: ekranda ne yazıyorsa o basılacak.
$blanks   = preg_match_all('/\[[^\]\n]{2,40}\]/u', $text, $found) ? array_values(array_unique($found[0])) : [];
$blanksEn = preg_match_all('/\[[^\]\n]{2,40}\]/u', $textEn, $foundEn) ? array_values(array_unique($foundEn[0])) : [];
?>

<?php // Metin alanı geniş olmalı ama sayfa sütununun tamamı kadar değil. ?>
<div class="max-w-3xl">

<?php // Tur adımları 20'den başlar; 10–19 kenar çubuğunun (bkz. views/layout.php). ?>
<header class="mb-6"
        data-tour="20" data-tour-title="İmzalatılan metin"
        data-tour-text="Bireye imzalatılan bilgilendirilmiş onam formunun kendisi: süreç, gizlilik, kişisel bilgilerin kaydı ve gönüllülük tek metinde. Çıktısı ya birey kaydından ya da buradaki “Boş form yazdır” düğmesinden alınır — ikincisi, kaydı henüz açılmamış kişi için.">
  <p class="eyebrow">Merkez</p>
  <div class="flex flex-wrap items-start justify-between gap-3 mt-2">
    <div>
      <h1 class="page-title">Onam formu</h1>
      <p class="page-sub">
        Bireye imzalatılan bilgilendirilmiş onam formu: süreç, gizlilik, kişisel
        bilgilerin kaydı ve gönüllülük tek metinde. Şu an
        <span class="num"><?= $signed ?></span> birey kaydında onam tamamlanmış.
        <?php // Metin evde okunup tiklendiği hâlde seansta henüz kapanmamış
              // kayıtlar. Bu aralığı gösteren başka bir ekran yok; burada
              // durmasaydı kimse bakmazdı ve kâğıt dosyaya hiç girmezdi. ?>
        <?php if ($online > 0): ?>
          <span class="num"><?= $online ?></span> kayıtta ise metin çevrimiçi
          onaylanmış, ıslak imza ya da sözlü onam bekleniyor.
        <?php endif; ?>
      </p>
    </div>
    <?php // Kapıdan gelen kişinin kaydı çoğu zaman görüşmeden sonra açılıyor;
          // kâğıt ise önce imzalanıyor. Bu düğme o sırayı bozmuyor. ?>
    <a href="<?= e(url('/onam-formu/yazdir')) ?>" target="_blank" rel="noopener"
       class="btn btn-primary shrink-0">Boş form yazdır</a>
  </div>
</header>

<?php if ($isDraft): ?>
  <div class="note note-stop mb-6">
    <strong>Bu metin bir taslaktır ve henüz kaydedilmemiştir.</strong>
    Köşeli parantezli alanları kurumun bilgileriyle doldurun ve metni
    <strong>bir hukukçuya onaylatın</strong>. Panel hukuki tavsiye vermez; buradaki
    taslak yalnız başlangıç noktasıdır.
  </div>
<?php endif; ?>

<?php
// Kaydedilmiş ama yarım bırakılmış metin. Taslak uyarısından ayrı duruyor:
// orada "henüz kaydetmediniz" deniyor, burada kaydedilmiş bir metinde
// doldurulmamış alan olduğu. Metin masaya konuyor ve artık sitede de
// okunuyor — "[saklama süresi]" diye.
?>
<?php if ($blanks !== [] || $blanksEn !== []): ?>
  <div class="note note-stop mb-6">
    <strong>Metinde doldurulmamış alan var.</strong>
    <?php if ($blanks !== []): ?>
      Türkçe metinde <?= e(implode(', ', $blanks)) ?> olduğu gibi duruyor.
    <?php endif; ?>
    <?php if ($blanksEn !== []): ?>
      İngilizce metinde <?= e(implode(', ', $blanksEn)) ?> olduğu gibi duruyor.
    <?php endif; ?>
    Bu ifadeler imzalanan kâğıda ve
    <a href="<?= e($publicUrl) ?>" target="_blank" rel="noopener" class="underline">sitedeki sayfaya</a>
    yazıldıkları gibi basılır. Kurumun saklama süresini ve KVKK başvuru adresini
    yazıp kaydedin; metin değiştiği için sürüm de yükselecek.
  </div>
<?php endif; ?>

<?php
// ── Herkese açık adres ──────────────────────────────────────────────────
// Metnin üçüncü çıkış kapısı. Ötekiler kişiye ait: kayıtlı bireyin çıktısı ve
// bireye özel onam bağlantısı. Bu adres herkese aynı ve HİÇBİR KAYIT TUTMAZ —
// randevu almadan önce "neyi imzalayacağım" diye soran kişiye verilecek cevap.
//
// Ayrı bir yayımlama adımı yok: sayfa metni her istekte veritabanından okuyor,
// aşağıdaki Kaydet'e basıldığı an adres de değişiyor. Bu yüzden kutu formun
// ÜSTÜNDE duruyor: metni düzenleyen kişi, düzenlediği şeyin herkese açık
// olduğunu düzenlemeden önce görsün.
?>
<section class="sheet mb-6"
         data-tour="21" data-tour-title="Herkese açık adres"
         data-tour-text="Formun metni sitede de yayımlanıyor: /onam-formu. Adres herkese aynı, kayıt tutmaz ve onam almaz — randevu öncesi metni okumak isteyen kişiye verilir. Aşağıdaki Kaydet'e bastığınız an sayfa da güncellenir; ayrı bir yayımlama adımı yok.">
  <div class="sheet-body">
    <p class="text-sm text-ink">Formun sitedeki adresi</p>
    <p class="text-sm text-ink-muted mt-1 mb-3">
      <?php if ($isDraft): ?>
        Metin henüz kaydedilmediği için sayfa <strong>yayımlanmıyor</strong>: adres
        şu an "yayımlanmıyor" diyen kısa bir sayfa gösteriyor. Aşağıdaki metni
        kaydettiğinizde kendiliğinden açılır.
      <?php else: ?>
        Aşağıdaki metni <strong>Kaydet</strong>'e bastığınız an bu sayfa da güncellenir;
        ayrı bir yayımlama adımı yok.
      <?php endif; ?>
      Adres kayıt tutmaz ve onam almaz: kim okudu bilinmez, sayfada tik de yoktur.
      Onamın kaydı için birey sayfasındaki <strong>"Onam bağlantısı gönder"</strong>
      kullanılır.
    </p>

    <div class="flex gap-2">
      <label for="publicConsentUrl" class="sr-only">Onam formunun herkese açık adresi</label>
      <input id="publicConsentUrl" type="text" readonly value="<?= e($publicUrl) ?>"
             class="field flex-1 min-w-0 text-xs font-mono text-ink-muted">
      <button type="button" data-copy="#publicConsentUrl" class="btn btn-quiet shrink-0">Kopyala</button>
      <a href="<?= e($publicUrl) ?>" target="_blank" rel="noopener"
         class="btn btn-quiet shrink-0">Aç</a>
    </div>

    <?php
    // İngilizce adres yalnız çeviri KAYDEDİLMİŞSE var: paneldeki taslak çeviri
    // yayımlanmıyor (bkz. ConsentController::publicSheet). Kaydedilmemişken
    // adresi vermek, açılmayan bir bağlantı paylaştırırdı.
    ?>
    <p class="field-hint mt-2">
      <?php if ($isDraftEn): ?>
        İngilizce sayfa henüz yayımlanmıyor: çeviri panelde kaydedilmemiş.
        Aşağıdaki <strong>İngilizce çeviri</strong> alanı paneldeki taslakla dolu
        gelir — okuyup <strong>Kaydet</strong>'e basmanız yeterli;
        <span class="font-mono"><?= e($publicUrlEn) ?></span> o an açılır.
      <?php else: ?>
        İngilizcesi: <a href="<?= e($publicUrlEn) ?>" target="_blank" rel="noopener"
        class="underline font-mono"><?= e($publicUrlEn) ?></a>
      <?php endif; ?>
    </p>
  </div>
</section>

<form method="post" action="<?= e(url('/onam-formu')) ?>" class="sheet">
  <?= Csrf::field() ?>

  <div class="sheet-body space-y-5">
    <div data-tour="22" data-tour-title="Köşeli parantezler sizin"
         data-tour-text="Paneldeki metin bir taslaktır, hukuki tavsiye değil. Köşeli parantezli yerleri (saklama süresi, iletişim adresi) kurumun bilgileriyle doldurun ve metni bir hukukçuya onaylatın.">
      <label for="consent_text" class="field-label">Metin</label>
      <textarea id="consent_text" name="consent_text" rows="24" required
                class="field leading-relaxed font-mono"><?= e($text) ?></textarea>
    </div>

    <?php
    // ── İngilizce çeviri ────────────────────────────────────────────────
    // Yalnız ÇIKTI için: Türkçe okumayan bireyin masasına konan kâğıt.
    // Çevrimiçi onam bağlantısı ve panelin kendisi Türkçe kalıyor — çeviri
    // ayrı bir onam metni değil, aynı sürümün İngilizcesi. Bu yüzden kendi
    // sürüm numarası da yok; hangi sürümün çevirisi olduğunu panel kendisi
    // takip ediyor (bkz. Consent::translatedVersion).
    ?>
    <details class="rounded-md border border-warm-tertiary" <?= $openEn ? 'open' : '' ?>>
      <summary class="cursor-pointer px-4 py-3 text-sm text-ink">
        İngilizce çeviri
        <span class="text-ink-muted">
          — <?php if ($staleEn): ?>
            <?= e($versionEn) ?> sürümüne ait, metin <?= e($version) ?> sürümünde
          <?php elseif ($isDraftEn): ?>
            kaydedilmemiş taslak
          <?php else: ?>
            <?= e($versionEn) ?> sürümünün çevirisi
          <?php endif; ?>
        </span>
      </summary>

      <div class="px-4 pb-4 space-y-3">
        <?php if ($staleEn): ?>
          <p class="note note-stop">
            <strong>Çeviri eskidi.</strong> Türkçe metin
            <span class="num"><?= e($version) ?></span> sürümünde, çeviri ise
            <span class="num"><?= e($versionEn) ?></span> sürümüne ait. İngilizce çıktı
            güncellenene kadar Türkçesinden farklı bir metin basar.
          </p>
        <?php elseif ($isDraftEn): ?>
          <p class="note note-info">
            <strong>Bu çeviri bir taslaktır ve henüz kaydedilmemiştir.</strong>
            Türkçesiyle aynı uyarı geçerli: köşeli parantezli alanları doldurun ve
            metni bir hukukçuya onaylatın. Kaydedilene kadar İngilizce çıktı bu
            taslağı basar.
          </p>
        <?php endif; ?>

        <div>
          <label for="consent_text_en" class="field-label">English text</label>
          <textarea id="consent_text_en" name="consent_text_en" rows="24"
                    class="field leading-relaxed font-mono"><?= e($textEn) ?></textarea>
          <?php if (error_for('consent_text_en') !== null): ?>
            <p class="field-error"><?= e(error_for('consent_text_en')) ?></p>
          <?php endif; ?>
          <p class="field-hint">
            Türkçe metni her değiştirdiğinizde bu çeviriyi de güncelleyin; panel
            aksi hâlde çıktının üstünde uyarır. Alanı tamamen boşaltırsanız
            İngilizce çıktı paneldeki taslağa döner. Onam bağlantısı ve bireyin
            gördüğü sayfa Türkçe kalır — imzalanan kâğıtta da hangi metnin
            çevirisi olduğu ve anlaşmazlıkta Türkçesinin geçerli olduğu yazılıdır.
          </p>
        </div>

        <p class="text-xs text-ink-light">
          <a href="<?= e(url('/onam-formu/yazdir?dil=en')) ?>" target="_blank" rel="noopener"
             class="underline">İngilizce boş form yazdır</a> — kayıtlı bir bireyin
          İngilizce çıktısı, o kaydın onam formunun üstündeki
          <strong>“English version”</strong> bağlantısından alınır.
        </p>
      </div>
    </details>

    <div class="grid sm:grid-cols-2 gap-4 items-start"
         data-tour="23" data-tour-title="Sürüm geçmişe dönmez"
         data-tour-text="Her birey kaydı, onay verdiği sürümü ayrıca saklar. Metni değiştirdiyseniz sürümü de yükseltin; imzalanmış formlar eski sürüme bağlı kalır. Esaslı bir değişiklikte mevcut bireylerden yeniden onam gerekip gerekmediğini değerlendirin.">
      <div>
        <label for="consent_version" class="field-label">Sürüm</label>
        <input type="text" id="consent_version" name="consent_version" required maxlength="20"
               class="field num" value="<?= e($version) ?>">
        <?php if (error_for('consent_version') !== null): ?>
          <p class="field-error"><?= e(error_for('consent_version')) ?></p>
        <?php endif; ?>
        <p class="field-hint">
          Metni değiştirdiyseniz sürümü de yükseltin. Her birey kaydı, onay verdiği
          sürümü ayrıca saklar.
        </p>
      </div>
      <div class="bg-warm rounded-md p-4 text-xs text-ink-muted leading-relaxed">
        Sürüm değişikliği geçmişe dönük çalışmaz: imzalanmış formlar eski sürüme bağlı
        kalır. Metinde esaslı bir değişiklik yaptıysanız mevcut bireylerden yeniden
        onam alınması gerekip gerekmediğini değerlendirin.
      </div>
    </div>
  </div>

  <div class="sheet-foot">
    <button type="submit" class="btn btn-primary">Kaydet</button>
  </div>
</form>

<p class="text-xs text-ink-light mt-6">
  Kayıtlı bir bireyin çıktısı, kayıt sayfasının üstündeki
  <strong>"Onam formu"</strong> düğmesinden alınır; adı basılı gelir. Kaydı
  henüz açılmamış kişi için yukarıdaki <strong>"Boş form yazdır"</strong>
  kullanılır. İki çıktıda da metnin sonuna aile yakını bilgisi kutusu ve imza
  satırları eklenir.
  İki çıktının da üstünde <strong>“English version”</strong> bağlantısı durur:
  aynı kâğıdın İngilizcesi, aynı sürüm numarasıyla.
</p>

</div>
