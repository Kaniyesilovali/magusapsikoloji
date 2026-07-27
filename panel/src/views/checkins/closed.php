<?php
/** @var string $heading @var string $message */

// Geçersiz, kullanılmış, süresi dolmuş ve kapanmış bağlantıların tek ekranı.
// Hangi durumda olduğu metinden anlaşılıyor ama sayfa aynı: kişi bir şeyi
// yanlış yapmadı, bağlantının ömrü doldu.
?>

<div class="sheet-body">
  <h1 class="page-title"><?= e($heading) ?></h1>
  <p class="page-sub"><?= e($message) ?></p>
</div>
