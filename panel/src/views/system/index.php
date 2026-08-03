<?php
use Panel\Csrf;
/** @var array $pending @var array $applied @var array $checks @var array $mail @var array $actor */
/** @var list<array<string,mixed>> $jobs @var array $backup @var string $phpBinary */

$cronDir = dirname(PANEL_ROOT) . '/panel/cron/';

// Başlık üstü satırı menü öbeğini ("Yönetim") tekrarlıyordu. Yerine sayfanın
// tek cümlelik özeti: bekleyen bir göç ya da durmuş bir iş varsa bu satır
// söyler, sayfayı açan kişi aşağı inmeden bilir.
$troubled = array_values(array_filter(
    $jobs,
    static fn (array $job): bool => $job['stateChip'] === 'chip-stop'
));
?>

<?php // Tur adımları 20'den başlar; 10–19 kenar çubuğunun (bkz. views/layout.php). ?>
<header class="mb-6"
        data-tour="20" data-tour-title="Bir şey aksadıysa burası"
        data-tour-text="Üstteki satır sayfanın özeti: bekleyen bir güncelleme ya da durmuş bir iş varsa aşağı inmeden söyler. Sayfa gündelik iş için değil — bir şeyin neden çalışmadığını anlamak için açılır.">
  <p class="eyebrow">
    <?php if ($pending !== []): ?>
      <span class="num"><?= count($pending) ?></span> güncelleme bekliyor
    <?php elseif ($troubled !== []): ?>
      <span class="num"><?= count($troubled) ?></span> iş dikkat istiyor
    <?php else: ?>
      Her şey yerinde
    <?php endif; ?>
  </p>
  <h1 class="page-title mt-2">Sistem</h1>
  <p class="page-sub">Sunucu durumu, zamanlanmış işler ve veritabanı güncellemeleri.</p>
</header>

<?php if ($pending !== []): ?>
  <?php // Bekleyen göç bir uyarı değil, bir karar: rozet clay, yaprağın kendisi sakin. ?>
  <section class="sheet mb-6"
           data-tour="21" data-tour-title="Bekleyen güncelleme"
           data-tour-text="Panelin yeni bir sürümü veritabanında bir değişiklik istiyorsa burada belirir ve düğmeye basılana kadar bekler. Uygulamadan önce cPanel'den veritabanı yedeği almak iyi bir alışkanlık. Bu bölüm, bekleyen bir şey yoksa hiç çizilmez.">
    <div class="sheet-head">
      <h2 class="sheet-title">Bekleyen veritabanı güncellemesi</h2>
      <span class="chip chip-stop">uygulanmadı</span>
    </div>
    <div class="sheet-body">
      <p class="text-sm text-ink-muted mb-4">
        Panelin yeni sürümü, veritabanında henüz bulunmayan tablo veya alanlar kullanıyor.
        Uygulanmadan ilgili ekranlar hata verir.
      </p>

      <ul class="text-sm text-ink font-mono bg-warm rounded-md px-4 py-3 mb-4 space-y-1">
        <?php foreach ($pending as $file): ?>
          <li><?= e($file) ?></li>
        <?php endforeach; ?>
      </ul>

      <form method="post" action="<?= e(url('/sistem/guncelle')) ?>"
            data-confirm="Veritabanı güncellemesi uygulanacak. Bu işlem geri alınamaz. Önce yedek aldığınızdan emin olun. Devam edilsin mi?">
        <?= Csrf::field() ?>
        <button class="btn btn-primary">Bekleyen güncellemeleri uygula</button>
      </form>

      <p class="field-hint">
        Uygulamadan önce cPanel → yedekleme ekranından veritabanının yedeğini almanız önerilir.
      </p>
    </div>
  </section>
<?php else: ?>
  <div class="note note-info mb-6">Veritabanı güncel — bekleyen güncelleme yok.</div>
<?php endif; ?>

<section class="sheet mb-6">
  <div class="sheet-head">
    <h2 class="sheet-title">Sunucu durumu</h2>
  </div>
  <ul class="divide-y divide-warm-secondary">
    <?php foreach ($checks as $check): ?>
      <li class="px-5 py-3 flex flex-wrap items-center gap-x-4 gap-y-1">
        <span class="text-sm text-ink w-56 shrink-0"><?= e($check['label']) ?></span>
        <span class="chip <?= $check['ok'] ? 'chip-go' : 'chip-stop' ?>">
          <?= $check['ok'] ? 'tamam' : 'dikkat' ?>
        </span>
        <span class="text-sm text-ink-muted flex-1 min-w-48"><?= e($check['detail']) ?></span>
      </li>
    <?php endforeach; ?>
  </ul>
</section>

<?php // Davet e-postası ulaşmadığında bakılacak ilk yer burası: hangi sürücü,
      // hangi adresten, ve tek tıkla gerçek bir gönderim denemesi. ?>
<section class="sheet mb-6">
  <div class="sheet-head">
    <div>
      <h2 class="sheet-title">E-posta gönderimi</h2>
      <p class="text-sm text-ink-muted mt-1">
        Davet, şifre sıfırlama ve randevu hatırlatma e-postalarının tümü bu ayarlardan çıkar.
      </p>
    </div>
    <span class="chip <?= $mail['live'] ? 'chip-go' : 'chip-stop' ?>">
      <?= $mail['live'] ? 'açık' : 'kapalı' ?>
    </span>
  </div>

  <ul class="divide-y divide-warm-secondary">
    <li class="px-5 py-3 flex flex-wrap items-center gap-x-4 gap-y-1">
      <span class="text-sm text-ink w-56 shrink-0">Sürücü</span>
      <span class="text-sm text-ink-muted flex-1 min-w-48">
        <code><?= e($mail['driver']) ?></code> — <?= e($mail['detail']) ?>
      </span>
    </li>
    <li class="px-5 py-3 flex flex-wrap items-center gap-x-4 gap-y-1">
      <span class="text-sm text-ink w-56 shrink-0">Gönderen adres</span>
      <span class="text-sm text-ink-muted flex-1 min-w-48">
        <?= $mail['from'] !== '' ? e($mail['from']) : 'tanımlı değil — gönderim büyük olasılıkla reddedilir' ?>
      </span>
    </li>
  </ul>

  <div class="sheet-foot">
    <?php if (!$mail['live']): ?>
      <p class="note note-stop mb-3">
        Sürücü <code>log</code> — e-postalar kimseye gitmiyor, yalnız dosyaya yazılıyor.
        Davet bağlantılarını elle iletmeniz gerekir. Yapılandırma dosyasındaki
        <code>mail.driver</code> değerini <code>mail</code> ya da <code>smtp</code> yapın.
      </p>
    <?php endif; ?>

    <form method="post" action="<?= e(url('/sistem/test-eposta')) ?>">
      <?= Csrf::field() ?>
      <button class="btn btn-quiet">Test e-postası gönder</button>
    </form>
    <p class="field-hint">
      İleti kendi adresinize (<?= e((string) $actor['email']) ?>) gider; sonuç ve hata varsa sebebi burada yazar.
    </p>
  </div>
</section>

<?php
// ── Zamanlanmış işler ──────────────────────────────────────────────────
//
// Üç iş bugüne kadar üç ayrı yaprakta duruyordu ve üçü de birebir aynı kalıbı
// tekrarlıyordu: durum satırı, son çalışma satırı, "cPanel → Cron Jobs"
// açıklaması, komut bloğu. Arıza anında sorulan soru ise hepsi için aynı ve
// tek: hangisi durmuş? Yan yana durunca bu tek bakışta görünüyor.
//
// Kurulum metni de bir kez yazılıyor. Komutların üçü aynı kalıp; değişen
// yalnız sıklık ile dosya adı, ki tablo zaten satır satır onu söylüyor.
?>
<section class="sheet mb-6"
         data-tour="22" data-tour-title="Zamanlanmış işler"
         data-tour-text="Randevu hatırlatmaları, haftalık check-in ve yedekleme. Üçünü de cPanel'deki cron çalıştırır; panel kendi kendine tetiklemez. Satırdaki durum “en son ne zaman koştu”ya bakar: bir iş sessizce durduğunda tek uyarı burasıdır.">
  <div class="sheet-head">
    <div>
      <h2 class="sheet-title">Zamanlanmış işler</h2>
      <p class="text-sm text-ink-muted mt-1">
        Üçünü de cPanel'deki cron çalıştırır; panel kendi kendine tetiklemez.
      </p>
    </div>
  </div>

  <div class="overflow-x-auto">
    <table class="tbl">
      <thead>
        <tr>
          <th>İş</th>
          <th>Durum</th>
          <th>Son çalışma</th>
          <th>Sıradaki</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($jobs as $job): ?>
          <tr>
            <td>
              <span class="text-ink"><?= e((string) $job['label']) ?></span>
              <br><span class="text-xs text-ink-light"><?= e((string) $job['schedule']) ?></span>
            </td>
            <td>
              <span class="chip <?= e((string) $job['stateChip']) ?>"><?= e((string) $job['stateLabel']) ?></span>
              <?php // Rozetin sebebi rozetin altında: "kurulmadı" tek başına
                    // neyin eksik olduğunu söylemez ve kullanıcıyı aramaya yollar. ?>
              <?php if ($job['blocked'] !== null): ?>
                <br><span class="text-xs text-accent-dark"><?= e((string) $job['blocked']) ?></span>
              <?php elseif ($job['settingKey'] !== null): ?>
                <br><span class="text-xs text-ink-light"><code><?= e((string) $job['settingKey']) ?></code></span>
              <?php endif; ?>
            </td>
            <td class="num">
              <?php if ($job['lastRun'] === null): ?>
                <span class="text-ink-light">—</span>
                <br><span class="text-xs text-ink-light">cron kurulmamış olabilir</span>
              <?php else: ?>
                <?= e(dt((string) $job['lastRun'])) ?>
                <?php if ($job['lastResult'] !== null): ?>
                  <br><span class="text-xs text-ink-light"><?= e((string) $job['lastResult']) ?></span>
                <?php endif; ?>
              <?php endif; ?>
            </td>
            <td class="text-ink-muted"><?= e((string) $job['measure']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="sheet-foot">
    <p class="text-sm text-ink-muted mb-2">
      cPanel → <strong>Cron Jobs</strong> → satırdaki sıklığı seçip ilgili komutu yapıştırın:
    </p>
    <pre class="text-xs bg-white border border-warm-tertiary rounded-md p-3 overflow-x-auto"><code><?php
      foreach ($jobs as $job):
        echo e(str_pad((string) $job['cron'], 12)) . e($phpBinary) . ' ' . e($cronDir . $job['script']) . "\n";
      endforeach;
    ?></code></pre>

    <?php if (!$backup['ready']): ?>
      <?php // Yedek anahtarı eksikse iş hiç kurulamaz; komutu vermeden önce
            // anahtarın nasıl üretileceği yazmalı. ?>
      <p class="note note-stop mt-3">
        <code>security.backup_key</code> tanımlı değil — yedek alınmıyor.
        Anahtarı üretmek için (SSH varsa):
      </p>
      <pre class="text-xs bg-white border border-warm-tertiary rounded-md p-3 overflow-x-auto mt-2"><code><?= e($phpBinary) ?> <?= e($cronDir) ?>backup.php --anahtar-uret</code></pre>
      <p class="field-hint">
        Çıkan satırı <code>config.php</code> içindeki <code>security</code> bölümüne ekleyin ve
        aynı değeri parola yöneticinize kaydedin — <strong><code>note_key</code>'den ayrı bir kayıt olarak.</strong>
        Bu anahtar kaybolursa yedekler açılamaz.
      </p>
    <?php else: ?>
      <p class="note note-info mt-3">
        Yedekler <strong>aynı sunucuda</strong> duruyor (<code><?= e((string) $backup['dir']) ?></code>);
        sunucu giderse onlar da gider. Ayda bir dosyayı indirmek hâlâ gerekli.
        En fazla <span class="num"><?= Panel\Backup::KEEP ?></span> yedek saklanır.
        Kurtarma: <code>backup.php --coz &lt;dosya&gt; &lt;hedef.sql&gt;</code> → phpMyAdmin → İçe Aktar.
      </p>
    <?php endif; ?>

    <p class="field-hint">
      Check-in doldurma oranı düşükse ilk şüpheli e-posta kanalıdır, kod değil:
      bağlantı birey sayfasından kopyalanıp başka bir kanaldan (ör. WhatsApp)
      denenebilir.
    </p>
  </div>
</section>

<section class="sheet">
  <div class="sheet-head">
    <h2 class="sheet-title">Uygulanmış güncellemeler</h2>
  </div>
  <?php if ($applied === []): ?>
    <p class="sheet-empty">Kayıt yok.</p>
  <?php else: ?>
    <ul class="divide-y divide-warm-secondary">
      <?php foreach ($applied as $row): ?>
        <li class="px-5 py-3 flex flex-wrap items-center gap-x-4 gap-y-1">
          <span class="text-sm text-ink font-mono flex-1 min-w-48"><?= e($row['filename']) ?></span>
          <span class="text-xs text-ink-light num"><?= e(dt($row['applied_at'])) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>
