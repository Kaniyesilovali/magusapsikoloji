<?php
use Panel\Csrf;
/** @var array $client @var bool $checkinAuto @var bool $checkinSwitchable */

// Check-in'in yöneticiye açık yüzü — eğrinin yerine geçmiyor, kapısını
// gösteriyor. Burada bilinçli olarak YOK: puan, cümle, şerit, örüntü. Bunlar
// sağlık verisi ve yalnız terapiste ait (bkz. Rbac → checkin.view.own).
//
// Kalan iki şey idari: bu kişiye haftalık e-posta çıkıyor mu, ve halkada hangi
// alanlar hangi kelimelerle soruluyor. İkincisi klinik bir karar ama metni
// yazan çoğu zaman merkezi yöneten kişi; ekranı ona kapatmak, uyarlamayı hiç
// yapılmaz kılıyordu.
$isActive = $client['status'] === 'active';
?>

<section class="sheet mb-6">
  <div class="sheet-head">
    <div>
      <h2 class="sheet-title">Seanslar arası check-in</h2>
      <p class="text-xs text-ink-light mt-1">
        Haftada bir, üç soru, giriş gerektirmeyen bir bağlantıyla.
        Cevaplar ve eğri yalnız terapistin ekranında görünür.
      </p>
    </div>
  </div>

  <div class="px-5 py-4 border-t border-warm-secondary">
    <p class="text-sm text-ink-muted">
      Buradan yönetebileceğiniz iki şey var: bu kişiye haftalık e-postanın çıkıp
      çıkmayacağı ve halkada hangi alanların hangi kelimelerle sorulacağı.
    </p>
    <p class="mt-3 flex flex-wrap gap-4">
      <a href="<?= e(url('/danisanlar/' . (int) $client['id'] . '/alanlar')) ?>" class="btn-text">
        Sorulan alanlar ve metinler →
      </a>
      <a href="<?= e(url('/check-in-sorulari')) ?>" class="btn-text btn-text-quiet">
        Sorular ve gönderim listesi →
      </a>
    </p>
  </div>

  <?php if ($isActive): ?>
    <div class="sheet-foot">
      <?php if ($checkinSwitchable): ?>
        <form method="post" action="<?= e(url("/danisanlar/{$client['id']}/check-in-gonderim")) ?>"
              class="flex items-baseline gap-2 flex-wrap">
          <?= Csrf::field() ?>
          <input type="hidden" name="acik" value="<?= $checkinAuto ? '0' : '1' ?>">
          <span class="text-xs text-ink-muted">
            Haftalık e-posta:
            <strong class="text-ink"><?= $checkinAuto ? 'açık' : 'kapalı' ?></strong>
          </span>
          <button class="btn btn-quiet btn-sm">
            <?= $checkinAuto ? 'Haftalık gönderimi durdur' : 'Haftalık gönderimi aç' ?>
          </button>
        </form>
        <p class="field-hint">
          <?php if ($client['email'] === null): ?>
            Kayıtta e-posta adresi yok; anahtar açık olsa da haftalık ileti çıkmaz.
          <?php else: ?>
            Anahtarın kapattığı tek şey cron. Terapist her zaman elle bağlantı gönderebilir.
          <?php endif; ?>
        </p>
      <?php else: ?>
        <p class="field-hint">
          Haftalık gönderim anahtarı için bekleyen bir veritabanı güncellemesi
          var; şu an gönderim açık sayılıyor.
        </p>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="sheet-foot">
      <p class="text-xs text-ink-light">Arşivlenmiş kayıt — check-in gönderilmez.</p>
    </div>
  <?php endif; ?>
</section>
