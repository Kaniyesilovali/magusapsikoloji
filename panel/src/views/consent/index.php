<?php
use Panel\Csrf;
/** @var string $text @var string $version @var bool $isDraft @var int $signed @var array $actor */
?>

<?php // Metin alanı geniş olmalı ama sayfa sütununun tamamı kadar değil. ?>
<div class="max-w-3xl">

<header class="mb-6">
  <p class="eyebrow">Site</p>
  <h1 class="page-title mt-2">KVKK metni</h1>
  <p class="page-sub">
    Danışanlara sunulan aydınlatma metni ve açık rıza beyanı. Şu an
    <span class="num"><?= $signed ?></span> danışan kaydında rıza işaretli.
  </p>
</header>

<?php if ($isDraft): ?>
  <div class="note note-stop mb-6">
    <strong>Bu metin bir taslaktır ve henüz kaydedilmemiştir.</strong>
    Köşeli parantezli alanları kurumun bilgileriyle doldurun ve metni
    <strong>bir hukukçuya onaylatın</strong>. Panel hukuki tavsiye vermez; buradaki
    taslak yalnız başlangıç noktasıdır.
  </div>
<?php endif; ?>

<form method="post" action="<?= e(url('/kvkk')) ?>" class="sheet">
  <?= Csrf::field() ?>

  <div class="sheet-body space-y-5">
    <div>
      <label for="consent_text" class="field-label">Metin</label>
      <textarea id="consent_text" name="consent_text" rows="24" required
                class="field leading-relaxed font-mono"><?= e($text) ?></textarea>
    </div>

    <div class="grid sm:grid-cols-2 gap-4 items-start">
      <div>
        <label for="consent_version" class="field-label">Sürüm</label>
        <input type="text" id="consent_version" name="consent_version" required maxlength="20"
               class="field num" value="<?= e($version) ?>">
        <p class="field-hint">
          Metni değiştirdiyseniz sürümü de yükseltin. Her danışan kaydı, rıza verdiği
          sürümü ayrıca saklar.
        </p>
      </div>
      <div class="bg-warm rounded-md p-4 text-xs text-ink-muted leading-relaxed">
        Sürüm değişikliği geçmişe dönük çalışmaz: eski rızalar eski sürüme bağlı kalır.
        Metinde esaslı bir değişiklik yaptıysanız mevcut danışanlardan yeniden rıza
        alınması gerekip gerekmediğini değerlendirin.
      </div>
    </div>
  </div>

  <div class="sheet-foot">
    <button type="submit" class="btn btn-primary">Kaydet</button>
  </div>
</form>

<p class="text-xs text-ink-light mt-6">
  Danışana imzalatılacak çıktıyı, danışanın kayıt sayfasındaki
  <strong>"Rıza formunu yazdır"</strong> bağlantısından alabilirsiniz.
</p>

</div>
