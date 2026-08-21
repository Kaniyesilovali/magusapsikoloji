<?php
/**
 * Herkese açık onam formu — https://magusapsikoloji.com/onam-formu
 *
 * Sitenin kökünde duran tek PHP dosyası. `.htaccess` uzantısız adresi buraya
 * yönlendirir (bkz. static/.htaccess); İngilizce karşılığı /en/consent-form.
 *
 * Neden burada, siteyle birlikte derlenen bir sayfa DEĞİL: metin panelin
 * veritabanında sürümlü duruyor ve sürüm numarası, imzalanmış her formun
 * hangi metne bağlı olduğunun tek kanıtı. Depoya ikinci bir kopya çıkarsaydı
 * kopya, panelde Kaydet'e basıldıktan sonra derleme + FTP yayını bitene kadar
 * eski metni gösterirdi — üstünde yeni sürüm numarasıyla. Burası metni her
 * istekte doğrudan panelden okuyor: Kaydet'e basıldığı an sayfa da değişir,
 * ikinci bir yayımlama adımı yok.
 *
 * Eleventy bu dosyayı `onam-formu.php` adıyla site köküne kopyalar
 * (bkz. eleventy.config.js) — yani panel/ dizini yanı başında durur.
 * Depodaki yeri static/ olduğu için yerelde doğrudan çalıştırılamaz;
 * denemek için: npm run build && php -S localhost:8080 -t _site
 */

// Panelin kendi giriş noktası bu değeri SCRIPT_NAME'den türetiyor; burada
// dosya panelin dışında olduğu için elle söyleniyor. Panel adresi değişirse
// (taşınırsa) bu satır da değişmeli — panel varlıklarının (panel.css/js) ve
// url() ile üretilen bağlantıların tek dayanağı burası.
define('PANEL_BASE', '/panel');

// Oturum açılmaz: sayfa kimseyi tanımıyor, tanımamalı (bkz. bootstrap.php).
define('PANEL_STATELESS', true);

require __DIR__ . '/panel/src/bootstrap.php';

(new Panel\Controllers\ConsentController())->publicSheet();
