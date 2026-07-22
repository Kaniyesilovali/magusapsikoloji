<?php
use Panel\Csrf;
/** @var string $text @var string $version @var bool $isDraft @var int $signed @var array $actor */
?>

<header class="mb-6">
  <h1 class="text-2xl font-semibold text-ink">KVKK metni</h1>
  <p class="text-sm text-ink-light mt-1">
    Danışanlara sunulan aydınlatma metni ve açık rıza beyanı. Şu an <?= $signed ?> danışan kaydında rıza işaretli.
  </p>
</header>

<?php if ($isDraft): ?>
  <div class="mb-6 px-4 py-3 rounded-xl border border-amber-200 bg-amber-50 text-amber-900 text-sm">
    <strong>Bu metin bir taslaktır ve henüz kaydedilmemiştir.</strong>
    Köşeli parantezli alanları kurumun bilgileriyle doldurun ve metni
    <strong>bir hukukçuya onaylatın</strong>. Panel hukuki tavsiye vermez; buradaki
    taslak yalnız başlangıç noktasıdır.
  </div>
<?php endif; ?>

<form method="post" action="<?= e(url('/kvkk')) ?>" class="bg-white border border-warm-tertiary rounded-2xl p-6 space-y-5">
  <?= Csrf::field() ?>

  <div>
    <label for="consent_text" class="block text-sm font-medium text-ink mb-1.5">Metin</label>
    <textarea id="consent_text" name="consent_text" rows="24" required
              class="w-full px-3 py-2.5 rounded-xl border border-warm-tertiary bg-warm focus:bg-white focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 text-sm leading-relaxed font-mono"><?= e($text) ?></textarea>
  </div>

  <div class="grid sm:grid-cols-2 gap-4 items-start">
    <div>
      <label for="consent_version" class="block text-sm font-medium text-ink mb-1.5">Sürüm</label>
      <input type="text" id="consent_version" name="consent_version" required maxlength="20"
             class="w-full px-3 py-2.5 rounded-xl border border-warm-tertiary bg-warm focus:bg-white focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 text-sm"
             value="<?= e($version) ?>">
      <p class="text-xs text-ink-light mt-1.5">
        Metni değiştirdiyseniz sürümü de yükseltin. Her danışan kaydı, rıza verdiği
        sürümü ayrıca saklar.
      </p>
    </div>
    <div class="bg-warm rounded-xl p-4 text-xs text-ink-muted leading-relaxed">
      Sürüm değişikliği geçmişe dönük çalışmaz: eski rızalar eski sürüme bağlı kalır.
      Metinde esaslı bir değişiklik yaptıysanız mevcut danışanlardan yeniden rıza
      alınması gerekip gerekmediğini değerlendirin.
    </div>
  </div>

  <div class="flex gap-3 pt-1">
    <button type="submit" class="bg-primary hover:bg-primary-hover text-white text-sm font-medium px-5 py-2.5 rounded-xl transition-colors">
      Kaydet
    </button>
  </div>
</form>

<p class="text-xs text-ink-light mt-6">
  Danışana imzalatılacak çıktıyı, danışanın kayıt sayfasındaki
  <strong>"Rıza formunu yazdır"</strong> bağlantısından alabilirsiniz.
</p>
