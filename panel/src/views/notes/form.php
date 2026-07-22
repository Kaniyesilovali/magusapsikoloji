<?php
use Panel\Csrf;
use Panel\Scheduling;
/** @var array $appointment @var array|null $note @var string $body */
/** @var bool $foreign @var bool $available @var array $actor */

$week = substr((string) $appointment['starts_at'], 0, 10);
?>

<?php // Not metni okunabilir genişlikte kalsın: 6xl sütunda satırlar çok uzuyor. ?>
<div class="max-w-2xl">

<header class="mb-6">
  <a href="<?= e(url('/randevular?hafta=' . $week)) ?>" class="btn-text btn-text-quiet">← Randevular</a>
  <p class="eyebrow mt-3">Seans notu</p>
  <h1 class="page-title mt-2"><?= e($appointment['client_name']) ?></h1>
  <p class="page-sub">
    <?= e(tr_date_label($week)) ?> saat <span class="num"><?= e(dt($appointment['starts_at'], 'H:i')) ?></span> ·
    <?= e(Scheduling::locationLabel($appointment['location'])) ?>
  </p>
</header>

<?php if (!$available): ?>
  <div class="sheet">
    <div class="sheet-body">
      <?php // Başlık clay: bu ekranda bir insanın (yöneticinin) müdahalesi gerekiyor. ?>
      <h2 class="sheet-title text-accent-dark mb-2">Şifreleme kullanılamıyor</h2>
      <p class="text-sm text-ink-muted">
        Seans notları yalnız şifreli saklanabilir. Sunucuda PHP <code>sodium</code> eklentisi kapalı
        ya da <code>note_key</code> ayarı geçersiz olduğu için not yazılamıyor.
      </p>
      <p class="text-sm text-ink-muted mt-3">
        cPanel → <strong>Select PHP Version</strong> ekranından <code>sodium</code> kutusunu işaretleyin.
        Sorun sürerse yöneticinizle görüşün — bu ekran, şifreleme çalışmadan bilerek not kabul etmez.
      </p>
    </div>
  </div>

<?php elseif ($foreign): ?>
  <div class="sheet">
    <div class="sheet-body">
      <h2 class="sheet-title mb-2">Bu seansın notu size ait değil</h2>
      <p class="text-sm text-ink-muted">
        Notu başka bir terapist yazmış. Seans notlarını yalnız yazan terapist okuyabilir;
        randevu size devredilmiş olsa da eski not açılamaz ve üzerine yazılamaz.
      </p>
      <p class="text-sm text-ink-muted mt-3">
        Bu seans için kendi değerlendirmenizi tutmanız gerekiyorsa yönetimden yeni bir randevu
        kaydı açılmasını isteyin.
      </p>
    </div>
  </div>

<?php else: ?>
  <form method="post" action="<?= e(url("/randevular/{$appointment['id']}/not")) ?>" class="sheet">
    <?= Csrf::field() ?>

    <div class="sheet-body space-y-5">
      <div>
        <label for="body" class="field-label">Not</label>
        <textarea id="body" name="body" rows="16" required class="field leading-relaxed"><?= e($body) ?></textarea>
      </div>

      <div class="bg-warm rounded-md p-4 text-xs text-ink-muted leading-relaxed">
        Bu not veritabanına <strong>şifreli</strong> yazılır. Yalnız siz okuyabilirsiniz —
        yöneticiler ve süper admin dahil kimse göremez, sistem kayıtlarında yalnız
        "not yazıldı" bilgisi tutulur, içerik tutulmaz.
        <br>
        Şifreleme anahtarı kaybolursa notlar kalıcı olarak kurtarılamaz.
      </div>
    </div>

    <div class="sheet-foot flex flex-wrap items-center gap-3">
      <button type="submit" class="btn btn-primary">
        <?= $note === null ? 'Notu kaydet' : 'Notu güncelle' ?>
      </button>
      <a href="<?= e(url('/randevular?hafta=' . $week)) ?>" class="btn btn-quiet">Vazgeç</a>
      <?php if ($note !== null): ?>
        <span class="text-xs text-ink-light">
          Son güncelleme: <span class="num"><?= e(dt($note['updated_at'] ?? $note['created_at'])) ?></span>
        </span>
      <?php endif; ?>
    </div>
  </form>
<?php endif; ?>

</div>
