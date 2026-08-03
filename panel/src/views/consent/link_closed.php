<?php
/** @var string $heading @var string $message */

// Geçersiz, onaylanmış, süresi dolmuş ve kapanmış bağlantıların tek ekranı —
// check-in'deki karşılığıyla aynı (bkz. checkins/closed.php).
//
// Sayfa dört durumda da aynı: kişi bir şeyi yanlış yapmadı, bağlantının ömrü
// doldu. Hangi durumda olduğu yalnız metinden anlaşılıyor ve metin, bağlantının
// kime ait olduğunu ya da neden geçersiz olduğunu ele vermiyor.
?>

<div class="sheet-body">
  <h1 class="page-title"><?= e($heading) ?></h1>
  <p class="page-sub"><?= e($message) ?></p>
</div>
