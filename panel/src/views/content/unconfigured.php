<?php
/** @var array $actor */
?>

<div class="max-w-2xl">

<header class="mb-6">
  <p class="eyebrow">Site içeriği</p>
  <h1 class="page-title mt-2">İçerik yönetimi</h1>
  <p class="page-sub">Site metinlerini panelden düzenlemek için bir kerelik kurulum gerekiyor.</p>
</header>

<div class="sheet">
  <div class="sheet-body space-y-5">
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
        <pre class="mt-2 bg-warm rounded-md p-3 text-xs overflow-x-auto">'github' =&gt; [
  'token'  =&gt; 'github_pat_...',
  'repo'   =&gt; 'Kaniyesilovali/magusapsikoloji',
  'branch' =&gt; 'main',
],</pre>
      </li>
    </ol>

    <div class="bg-warm rounded-md p-4 text-xs text-ink-muted leading-relaxed">
      Anahtar <code>public_html</code> dışındaki config dosyasında durur, panelde hiçbir ekranda
      görünmez. Süresi dolduğunda bu ekran geri gelir — o zaman yeni bir anahtar üretip
      değeri değiştirmek yeterlidir.
    </div>
  </div>
</div>

</div>
