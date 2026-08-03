<?php
use Panel\Csrf;
/** @var string $token @var string $firstName @var string $text @var string $version */

// Danışanın seanstan ÖNCE, evinde okuduğu onam formu.
//
// Bu sayfanın varlık sebebi tek bir gözlem: form seansın içinde okutulduğunda
// okunmuyor. "Süreden gidiyor" diye gerilen kişi sayfayı çeviriyor, imza
// atıyor ve neyi kabul ettiğini bilmiyor. Oysa onamın değeri imzada değil,
// anlaşılmış olmasında. Burada acele ettiren hiçbir şey yok: sayfada tek bir
// düğme var ve o düğmeye basmadan önce kişi metnin sonuna kadar iniyor.
//
// Metin BİR PARÇA hâlinde duruyor — bölümlere ayrılmış, "devam" düğmeli bir
// sihirbaz denenmedi bilerek: bölünmüş metin, okunmuş metin gibi hissettirir
// ama kaydırma çubuğunun uzunluğunu gizler. Kişi neyi kabul ettiğini görmeli.
?>

<div class="sheet-body">
  <h1 class="page-title">
    <?php if ($firstName !== ''): ?>Merhaba <?= e($firstName) ?>,<?php else: ?>Onam formu<?php endif; ?>
  </h1>
  <p class="page-sub">
    Aşağıdaki metin, birlikte çalışacağımız sürecin çerçevesini anlatıyor:
    seansların işleyişi, gizlilik, bilgilerinizin nasıl saklandığı ve her
    aşamada sahip olduğunuz haklar. Acele etmeyin — sonunda bir onay kutusu var.
  </p>
  <p class="text-xs text-ink-light mt-3 num">Metin sürümü <?= e($version) ?></p>
</div>

<?php // Metnin kendisi. Çıktıdaki (consent/print.php) ile aynı biçimde
      // basılıyor: aynı metin, aynı boşluklar — seansta masaya konan kâğıt
      // burada okunanın aynısı olmalı. ?>
<div class="px-5 py-5 border-t border-warm-secondary sm:px-6">
  <article class="text-sm text-ink leading-relaxed whitespace-pre-wrap"><?= e($text) ?></article>
</div>

<form method="post" action="<?= e(url('/onam/' . $token)) ?>" class="border-t border-warm-secondary">
  <?= Csrf::field() ?>
  <?php // Okunan metnin sürümü gönderimle birlikte geri geliyor. Kişi okurken
        // metin panelden düzenlenmiş olabilir; denetleyici bu alanı güncel
        // sürümle karşılaştırıp uyuşmazsa onayı reddediyor. Okumadığı bir
        // metne verilmiş onay, onay değildir. ?>
  <input type="hidden" name="surum" value="<?= e($version) ?>">

  <div class="px-5 py-5 sm:px-6">
    <label class="flex items-start gap-3 text-sm text-ink cursor-pointer">
      <input type="checkbox" name="onay" value="1" class="mt-0.5 shrink-0" required>
      <span>
        Yukarıdaki metnin tamamını <strong>okudum</strong>, anladım ve
        <strong>kabul ediyorum</strong>. Seans notlarım dahil kişisel
        bilgilerimin metinde anlatılan biçimde işlenmesine açık rızam ile onay
        veriyorum.
      </span>
    </label>

    <?php // Tikin ne olduğunu ve ne OLMADIĞINI söylüyor. Bu satır olmasaydı
          // kişi seansta yeniden imza istendiğinde "ama ben onaylamıştım"
          // diye şaşırırdı — ve haklı olurdu. ?>
    <p class="field-hint mt-3">
      Onayınız kaydedilecek. İlk seansta bu formun çıktısı üzerinden kısaca
      doğrulanacak: merkeze geliyorsanız imzanız alınacak, online görüşüyorsanız
      onayınızı sözlü olarak beyan etmeniz istenecek. Yeniden okumanız
      gerekmeyecek.
    </p>
    <p class="field-hint mt-2">
      Anlamadığınız ya da sormak istediğiniz bir yer varsa onaylamadan
      merkezimize yazabilirsiniz; seansta da konuşabiliriz.
    </p>
  </div>

  <div class="sheet-foot">
    <button class="btn btn-primary w-full justify-center">Okudum, onaylıyorum</button>
  </div>
</form>
