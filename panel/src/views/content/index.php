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
  <h1 class="text-2xl font-semibold text-ink">Site içeriği</h1>
  <p class="text-sm text-ink-light mt-1">
    Buradan yapılan değişiklikler depoya işlenir ve site otomatik olarak yeniden yayınlanır.
  </p>
</header>

<?php if (!$configured): ?>
  <div class="mb-6 px-4 py-3 rounded-xl border border-amber-200 bg-amber-50 text-amber-900 text-sm">
    GitHub erişimi henüz yapılandırılmamış. Aşağıdaki bölümlerden birine girdiğinizde
    kurulum adımlarını göreceksiniz.
  </div>
<?php endif; ?>

<div class="grid sm:grid-cols-2 gap-4">
  <?php foreach ($sections as $section): ?>
    <a href="<?= e(url($section['path'])) ?>"
       class="block bg-white border border-warm-tertiary rounded-2xl p-5 hover:border-primary/40 transition-colors">
      <h2 class="font-medium text-ink"><?= e($section['title']) ?></h2>
      <p class="text-sm text-ink-muted mt-1.5 leading-relaxed"><?= e($section['desc']) ?></p>
    </a>
  <?php endforeach; ?>
</div>

<p class="text-xs text-ink-light mt-6">
  Blog yazıları ve sayfa içeriği <a href="/admin/" class="text-primary hover:text-primary-dark">Sveltia CMS</a>
  üzerinden düzenlenir. Bu bilinçli bir ayrım: yazılar iç içe geçmiş yapısal bloklardan
  oluşuyor ve Sveltia bunun için tasarlanmış bir editör sunuyor.
</p>
