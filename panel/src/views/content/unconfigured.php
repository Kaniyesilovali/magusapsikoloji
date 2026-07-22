<?php
/** @var array $actor */
?>

<header class="mb-6">
  <h1 class="text-2xl font-semibold text-ink">İçerik yönetimi</h1>
  <p class="text-sm text-ink-light mt-1">Site metinlerini panelden düzenlemek için bir kerelik kurulum gerekiyor.</p>
</header>

<div class="bg-white border border-warm-tertiary rounded-2xl p-6 max-w-2xl space-y-5">
  <p class="text-sm text-ink">
    Panel, site içeriğini doğrudan sunucuya değil <strong>depoya</strong> yazar; GitHub Actions
    siteyi oradan yeniden yayınlar. Sunucudaki dosyalara yazsaydı bir sonraki yayında silinirdi.
    Bunun için depoya yazma izni olan bir erişim anahtarı gerekiyor.
  </p>

  <ol class="text-sm text-ink-muted space-y-3 list-decimal pl-5">
    <li>
      GitHub → <strong>Settings → Developer settings → Personal access tokens →
      Fine-grained tokens</strong> → <em>Generate new token</em>
    </li>
    <li>
      <strong>Repository access:</strong> yalnız <code>magusapsikoloji</code> deposu seçilsin.
      Tüm depolara erişim vermeyin.
    </li>
    <li>
      <strong>Permissions → Repository permissions → Contents:</strong>
      <code>Read and write</code>. Başka izne gerek yok.
    </li>
    <li>
      Oluşan anahtarı, sunucudaki <code>config.php</code> dosyasına ekleyin:
      <pre class="mt-2 bg-warm rounded-xl p-3 text-xs overflow-x-auto">'github' =&gt; [
  'token'  =&gt; 'github_pat_...',
  'repo'   =&gt; 'Kaniyesilovali/magusapsikoloji',
  'branch' =&gt; 'main',
],</pre>
    </li>
  </ol>

  <div class="bg-warm rounded-xl p-4 text-xs text-ink-muted leading-relaxed">
    Anahtar <code>public_html</code> dışındaki config dosyasında durur, panelde hiçbir ekranda
    görünmez. Süresi dolduğunda bu ekran geri gelir — o zaman yeni bir anahtar üretip
    değeri değiştirmek yeterlidir.
  </div>
</div>
