<?php
use Panel\Csrf;
use Panel\Scheduling;
/** @var array $appointment @var array|null $note @var string $body */
/** @var bool $foreign @var bool $available @var array $actor */

$week = substr((string) $appointment['starts_at'], 0, 10);
?>

<header class="mb-6">
  <a href="<?= e(url('/randevular?hafta=' . $week)) ?>" class="text-sm text-ink-muted hover:text-ink">← Randevular</a>
  <h1 class="text-2xl font-semibold text-ink mt-2">Seans notu</h1>
  <p class="text-sm text-ink-light mt-1">
    <?= e($appointment['client_name']) ?> ·
    <?= e(tr_date_label($week)) ?> saat <?= e(dt($appointment['starts_at'], 'H:i')) ?> ·
    <?= e(Scheduling::locationLabel($appointment['location'])) ?>
  </p>
</header>

<?php if (!$available): ?>
  <div class="bg-white border border-accent/30 rounded-2xl p-6 max-w-2xl">
    <h2 class="font-medium text-accent-dark mb-2">Şifreleme kullanılamıyor</h2>
    <p class="text-sm text-ink-muted">
      Seans notları yalnız şifreli saklanabilir. Sunucuda PHP <code>sodium</code> eklentisi kapalı
      ya da <code>note_key</code> ayarı geçersiz olduğu için not yazılamıyor.
    </p>
    <p class="text-sm text-ink-muted mt-3">
      cPanel → <strong>Select PHP Version</strong> ekranından <code>sodium</code> kutusunu işaretleyin.
      Sorun sürerse yöneticinizle görüşün — bu ekran, şifreleme çalışmadan bilerek not kabul etmez.
    </p>
  </div>

<?php elseif ($foreign): ?>
  <div class="bg-white border border-warm-tertiary rounded-2xl p-6 max-w-2xl">
    <h2 class="font-medium text-ink mb-2">Bu seansın notu size ait değil</h2>
    <p class="text-sm text-ink-muted">
      Notu başka bir terapist yazmış. Seans notlarını yalnız yazan terapist okuyabilir;
      randevu size devredilmiş olsa da eski not açılamaz ve üzerine yazılamaz.
    </p>
    <p class="text-sm text-ink-muted mt-3">
      Bu seans için kendi değerlendirmenizi tutmanız gerekiyorsa yönetimden yeni bir randevu
      kaydı açılmasını isteyin.
    </p>
  </div>

<?php else: ?>
  <form method="post" action="<?= e(url("/randevular/{$appointment['id']}/not")) ?>"
        class="bg-white border border-warm-tertiary rounded-2xl p-6 space-y-5 max-w-2xl">
    <?= Csrf::field() ?>

    <div>
      <label for="body" class="block text-sm font-medium text-ink mb-1.5">Not</label>
      <textarea id="body" name="body" rows="16" required
                class="w-full px-3 py-2.5 rounded-xl border border-warm-tertiary bg-warm focus:bg-white focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 text-sm leading-relaxed"><?= e($body) ?></textarea>
    </div>

    <div class="bg-warm rounded-xl p-4 text-xs text-ink-muted leading-relaxed">
      Bu not veritabanına <strong>şifreli</strong> yazılır. Yalnız siz okuyabilirsiniz —
      yöneticiler ve süper admin dahil kimse göremez, sistem kayıtlarında yalnız
      "not yazıldı" bilgisi tutulur, içerik tutulmaz.
      <br>
      Şifreleme anahtarı kaybolursa notlar kalıcı olarak kurtarılamaz.
    </div>

    <div class="flex gap-3 pt-1">
      <button type="submit" class="bg-primary hover:bg-primary-hover text-white text-sm font-medium px-5 py-2.5 rounded-xl transition-colors">
        <?= $note === null ? 'Notu kaydet' : 'Notu güncelle' ?>
      </button>
      <a href="<?= e(url('/randevular?hafta=' . $week)) ?>" class="text-sm text-ink-muted hover:text-ink px-4 py-2.5">Vazgeç</a>
      <?php if ($note !== null): ?>
        <span class="text-xs text-ink-light self-center">
          Son güncelleme: <?= e(dt($note['updated_at'] ?? $note['created_at'])) ?>
        </span>
      <?php endif; ?>
    </div>
  </form>
<?php endif; ?>
