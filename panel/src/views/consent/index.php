<?php
use Panel\Csrf;
/** @var string $text @var string $version @var bool $isDraft @var int $signed @var array $actor */
/** @var int $online */
/** @var string $textEn @var bool $isDraftEn @var bool $staleEn @var string $versionEn */

// Geri çevrilmiş bir gönderim varsa ekranda kayıttaki metin değil, kişinin
// yazdığı metin durur (bkz. ConsentController::update).
$text    = old('consent_text', $text);
$textEn  = old('consent_text_en', $textEn);
$version = old('consent_version', $version);

// Çeviri alanı kapalı geliyor: İngilizce çıktı her kurumda istenmiyor ve 24
// satırlık ikinci bir metin, asıl metnin altındaki sürüm alanını görünmez
// yapıyordu. Eskimiş ya da geri çevrilmiş bir çeviride açık başlıyor —
// düzeltilecek şeyin bir tık arkasında durması onu unutturur.
$openEn = $staleEn || error_for('consent_text_en') !== null;
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

<form method="post" action="<?= e(url('/onam-formu')) ?>" class="sheet">
  <?= Csrf::field() ?>

  <div class="sheet-body space-y-5">
    <div data-tour="21" data-tour-title="Köşeli parantezler sizin"
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
         data-tour="22" data-tour-title="Sürüm geçmişe dönmez"
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
