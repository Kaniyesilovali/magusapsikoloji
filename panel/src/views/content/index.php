<?php
/** @var bool $configured @var array $actor */

$sections = [
    [
        'path'  => '/icerik/iletisim',
        'title' => 'İletişim bilgileri',
        'desc'  => 'Telefon, WhatsApp, e-posta ve sosyal medya adresleri. Sitenin alt bilgisinde ve iletişim sayfasında görünür.',
    ],
    [
        'path'  => '/icerik/sss',
        'title' => 'SSS içeriği',
        'desc'  => 'Sayfalara gömülen soru-cevap blokları ve SSS sayfasının kategorileri. TR ve EN ayrı ayrı düzenlenir.',
    ],
];
?>

<header class="mb-6">
  <p class="eyebrow">Site</p>
  <h1 class="page-title mt-2">Site içeriği</h1>
  <p class="page-sub">
    Buradan yapılan değişiklikler depoya işlenir ve site otomatik olarak yeniden yayınlanır.
  </p>
</header>

<?php if (!$configured): ?>
  <div class="note note-stop mb-6">
    GitHub erişimi henüz yapılandırılmamış. Aşağıdaki bölümlerden birine girdiğinizde
    kurulum adımlarını göreceksiniz.
  </div>
<?php endif; ?>

<div class="grid sm:grid-cols-2 gap-4">
  <?php foreach ($sections as $section): ?>
    <a href="<?= e(url($section['path'])) ?>" class="sheet block hover:border-primary-light transition-colors">
      <div class="sheet-body">
        <h2 class="sheet-title"><?= e($section['title']) ?></h2>
        <p class="text-sm text-ink-muted mt-1.5 leading-relaxed"><?= e($section['desc']) ?></p>
      </div>
    </a>
  <?php endforeach; ?>
</div>

<p class="text-xs text-ink-light mt-6">
  Blog yazıları ve sayfa içeriği <a href="/admin/" class="btn-text">Sveltia CMS</a>
  üzerinden düzenlenir. Bu bilinçli bir ayrım: yazılar iç içe geçmiş yapısal bloklardan
  oluşuyor ve Sveltia bunun için tasarlanmış bir editör sunuyor.
</p>
