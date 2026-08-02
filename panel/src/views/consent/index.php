<?php
use Panel\Csrf;
/** @var string $text @var string $version @var bool $isDraft @var int $signed @var array $actor */

// Geri çevrilmiş bir gönderim varsa ekranda kayıttaki metin değil, kişinin
// yazdığı metin durur (bkz. ConsentController::update).
$text    = old('consent_text', $text);
$version = old('consent_version', $version);
?>

<?php // Metin alanı geniş olmalı ama sayfa sütununun tamamı kadar değil. ?>
<div class="max-w-3xl">

<?php // Tur adımları 20'den başlar; 10–19 kenar çubuğunun (bkz. views/layout.php). ?>
<header class="mb-6"
        data-tour="20" data-tour-title="İmzalatılan metin"
        data-tour-text="Danışana imzalatılan bilgilendirilmiş onam formunun kendisi: süreç, gizlilik, kişisel bilgilerin kaydı ve gönüllülük tek metinde. Çıktısı ya görüşmeci kaydından ya da buradaki “Boş form yazdır” düğmesinden alınır — ikincisi, kaydı henüz açılmamış kişi için.">
  <p class="eyebrow">Merkez</p>
  <div class="flex flex-wrap items-start justify-between gap-3 mt-2">
    <div>
      <h1 class="page-title">Onam formu</h1>
      <p class="page-sub">
        Danışana imzalatılan bilgilendirilmiş onam formu: süreç, gizlilik, kişisel
        bilgilerin kaydı ve gönüllülük tek metinde. Şu an
        <span class="num"><?= $signed ?></span> danışan kaydında onam işaretli.
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

    <div class="grid sm:grid-cols-2 gap-4 items-start"
         data-tour="22" data-tour-title="Sürüm geçmişe dönmez"
         data-tour-text="Her danışan kaydı, onay verdiği sürümü ayrıca saklar. Metni değiştirdiyseniz sürümü de yükseltin; imzalanmış formlar eski sürüme bağlı kalır. Esaslı bir değişiklikte mevcut danışanlardan yeniden onam gerekip gerekmediğini değerlendirin.">
      <div>
        <label for="consent_version" class="field-label">Sürüm</label>
        <input type="text" id="consent_version" name="consent_version" required maxlength="20"
               class="field num" value="<?= e($version) ?>">
        <?php if (error_for('consent_version') !== null): ?>
          <p class="field-error"><?= e(error_for('consent_version')) ?></p>
        <?php endif; ?>
        <p class="field-hint">
          Metni değiştirdiyseniz sürümü de yükseltin. Her danışan kaydı, onay verdiği
          sürümü ayrıca saklar.
        </p>
      </div>
      <div class="bg-warm rounded-md p-4 text-xs text-ink-muted leading-relaxed">
        Sürüm değişikliği geçmişe dönük çalışmaz: imzalanmış formlar eski sürüme bağlı
        kalır. Metinde esaslı bir değişiklik yaptıysanız mevcut danışanlardan yeniden
        onam alınması gerekip gerekmediğini değerlendirin.
      </div>
    </div>
  </div>

  <div class="sheet-foot">
    <button type="submit" class="btn btn-primary">Kaydet</button>
  </div>
</form>

<p class="text-xs text-ink-light mt-6">
  Kayıtlı bir görüşmecinin çıktısı, kayıt sayfasının üstündeki
  <strong>"Onam formu"</strong> düğmesinden alınır; adı basılı gelir. Kaydı
  henüz açılmamış kişi için yukarıdaki <strong>"Boş form yazdır"</strong>
  kullanılır. İki çıktıda da metnin sonuna seans koşulları, aile yakını bilgisi
  kutusu ve imza satırları eklenir; seans ücreti, süresi ve sıklığı yazdırmadan
  önce çıktının üstünde düzeltilebilir — bunun için metne dokunmak gerekmez.
</p>

</div>
