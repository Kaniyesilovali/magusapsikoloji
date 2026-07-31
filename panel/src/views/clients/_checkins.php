<?php
use Panel\Checkins;
use Panel\Csrf;
/** @var array $client @var list<array> $checkins @var int $checkinTotal */
/** @var ?array $checkinPending @var ?array $checkinLink @var array $ecosystem */
/** @var bool $checkinAuto @var bool $checkinSwitchable @var list<array> $scaleRows */

// Seanslar arası check-in — terapistin gördüğü yüz.
//
// Bu bölümün varlık sebebi tek bir soru: "iki seans arasında ne oldu?" Cevabı
// bugüne kadar yalnız görüşmecinin hatırladığı kadarıyla, seansın ilk on
// dakikasında alınabiliyordu. Üç sayı bunu bir eğriye çeviriyor.
//
// Eğri üç ayrı satır, tek kutu değil: kaygıda yukarı kötüdür, diğer ikisinde
// iyi. Üst üste çizilseydi yükselen çizgi ne anlama geldiği belirsiz olurdu.
$isActive = $client['status'] === 'active';
$latest   = $checkins === [] ? null : $checkins[count($checkins) - 1];
?>

<section class="sheet mb-6">
  <div class="sheet-head">
    <div>
      <h2 class="sheet-title">Seanslar arası check-in</h2>
      <p class="text-xs text-ink-light mt-1">
        Haftada bir, üç soru, giriş gerektirmeyen bir bağlantıyla.
        <a href="<?= e(url('/check-in-sorulari')) ?>" class="btn-text btn-text-quiet">Sorular ve gönderim</a>
        <a href="<?= e(url('/danisanlar/' . (int) $client['id'] . '/alanlar')) ?>" class="btn-text btn-text-quiet">Sorulan alanlar</a>
      </p>
    </div>
    <?php if ($checkinTotal > 0): ?>
      <span class="text-xs text-ink-light">
        <span class="num"><?= $checkinTotal ?></span> kayıt
        <?php if ($checkinTotal > count($checkins)): ?>
          <span class="text-ink-light">— son <span class="num"><?= count($checkins) ?></span> gösteriliyor</span>
        <?php endif; ?>
      </span>
    <?php endif; ?>
  </div>

  <?php if ($checkins === []): ?>
    <p class="sheet-empty">
      Henüz check-in yok.
      <?php if ($isActive): ?>
        Aşağıdaki düğme bağlantıyı üretir; sonrasını haftalık cron sürdürür.
      <?php endif; ?>
    </p>
  <?php else: ?>

    <?php
    // Satırlar ölçek listesinden değil, ELDEKİ VERİDEN çıkıyor: sonradan
    // eklenen bir ölçek ilk cevabıyla belirir, kapatılan bir ölçek geçmişi
    // bitene kadar kalır. Ölçmeyi bırakmak ölçtüğünü unutmak değil.
    $drawn = [];
    foreach ($scaleRows as $scale) {
        foreach ($checkins as $row) {
            if (($row['scores'][$scale['key']] ?? null) !== null) {
                $drawn[] = $scale;
                break;
            }
        }
    }
    ?>
    <?php foreach ($drawn as $measure): ?>
      <?php
      $field = $measure['key'];
      $curve = Checkins::curve($checkins, $field);
      $note  = trim('1 · ' . $measure['low'] . '  ·  10 · ' . $measure['high'], ' ·');
      ?>
      <?php if ($curve['last'] === null) { continue; } ?>
      <div class="px-5 py-4 border-t border-warm-secondary">
        <div class="flex items-baseline justify-between gap-3">
          <h3 class="text-sm font-medium text-ink"><?= e($measure['label']) ?></h3>
          <p class="text-xs text-ink-light"><?= e($note) ?></p>
        </div>

        <?php // Sabit ölçekli çizim; dar ekranda kutu kendi içinde kayar. ?>
        <div class="ci-scroll mt-1">
          <svg class="ci-chart" width="<?= Checkins::VIEW_W ?>" height="<?= Checkins::VIEW_H ?>"
               viewBox="0 0 <?= Checkins::VIEW_W ?> <?= Checkins::VIEW_H ?>" role="img"
               aria-label="<?= e($measure['label']) ?> eğrisi — <?= count($checkins) ?> check-in, en son değer <?= (int) $curve['last']['value'] ?>/10">

            <?php // Izgara üç çizgi: 10, orta ve 1. Fazlası veriyi bastırır. ?>
            <line class="ci-grid" x1="<?= $curve['left'] ?>" y1="<?= $curve['top'] ?>"    x2="<?= $curve['right'] ?>" y2="<?= $curve['top'] ?>"></line>
            <line class="ci-grid" x1="<?= $curve['left'] ?>" y1="<?= $curve['mid'] ?>"    x2="<?= $curve['right'] ?>" y2="<?= $curve['mid'] ?>"></line>
            <line class="ci-grid" x1="<?= $curve['left'] ?>" y1="<?= $curve['bottom'] ?>" x2="<?= $curve['right'] ?>" y2="<?= $curve['bottom'] ?>"></line>

            <text class="ci-tick" x="<?= $curve['left'] - 6 ?>" y="<?= $curve['top'] + 3 ?>"    text-anchor="end">10</text>
            <text class="ci-tick" x="<?= $curve['left'] - 6 ?>" y="<?= $curve['bottom'] + 3 ?>" text-anchor="end">1</text>

            <?php if (count($curve['dots']) > 1): ?>
              <polyline class="ci-line" points="<?= e($curve['points']) ?>"></polyline>
            <?php endif; ?>

            <?php // Her noktanın kendi ipucu var: JS'siz, ekran okuyucuya da açık. ?>
            <?php foreach ($curve['dots'] as $dot): ?>
              <circle class="ci-dot" cx="<?= $dot['x'] ?>" cy="<?= $dot['y'] ?>" r="4">
                <title><?= e($dot['label']) ?></title>
              </circle>
            <?php endforeach; ?>

            <?php // Yalnız son değer yazıyor — geri kalanı aşağıdaki tabloda. ?>
            <text class="ci-value" x="<?= $curve['last']['x'] + 10 ?>" y="<?= $curve['last']['y'] + 4 ?>">
              <?= (int) $curve['last']['value'] ?>
            </text>
          </svg>
        </div>
      </div>
    <?php endforeach; ?>

    <?php // Ekolojik şerit eğrinin hemen altında: aynı haftalar, aynı sıra.
          // "Bu hafta ne oldu" sorusunun cevabı eğride değil burada. ?>
    <?php if (($ecosystem['rows'] ?? []) !== []): ?>
      <div class="border-t border-warm-secondary">
        <?php require __DIR__ . '/_ecosystem.php'; ?>
      </div>
    <?php endif; ?>

    <?php // Tablo görünümü: eğrinin okunamadığı her durumda (yazdırma, ekran
          // okuyucu, iki noktanın üst üste düşmesi) sayılar burada duruyor.
          // Cümleler yalnız burada; şifreli saklanıyor, bu ekranda çözülüyor. ?>
    <div class="border-t border-warm-secondary overflow-x-auto">
      <table class="tbl">
        <thead>
          <tr>
            <th>Hafta</th>
            <?php foreach ($drawn as $measure): ?>
              <th><?= e($measure['label']) ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach (array_reverse($checkins) as $row): ?>
            <tr>
              <td>
                <span class="text-ink"><?= e(Checkins::weekLabel((string) $row['created_at'])) ?></span>
                <span class="block text-xs text-ink-light num"><?= e(dt($row['created_at'])) ?></span>
              </td>
              <?php foreach ($drawn as $measure): ?>
                <?php $value = $row['scores'][$measure['key']] ?? null; ?>
                <?php // O hafta sorulmamış ölçek boş kalır, sıfır yazılmaz. ?>
                <td class="num text-ink"><?= $value === null ? '—' : (int) $value ?></td>
              <?php endforeach; ?>
            </tr>
            <?php if ($row['note'] !== null || $row['note_error']): ?>
              <tr>
                <td colspan="<?= count($drawn) + 1 ?>" class="text-sm">
                  <?php if ($row['note_error']): ?>
                    <span class="text-accent-dark">
                      Bu check-in'in cümlesi çözülemedi — şifreleme anahtarı değişmiş olabilir.
                    </span>
                  <?php else: ?>
                    <span class="text-ink-muted">“<?= e((string) $row['note']) ?>”</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endif; ?>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <div class="sheet-foot">
    <?php if ($checkinLink !== null): ?>
      <?php // E-posta ulaşmazsa bağlantı elden iletilir. Pilotta doldurma oranı
            // düşükse ilk şüpheli kanal; bu kutu ikinci kanalı denemenin yolu. ?>
      <div class="bg-white border border-warm-tertiary rounded-md p-4 mb-3">
        <p class="text-sm text-ink"><?= e($checkinLink['name']) ?> için check-in bağlantısı</p>
        <p class="text-xs text-ink-light mt-1 mb-3">
          <?= !empty($checkinLink['sent'])
              ? 'E-posta gönderildi. Ulaşmazsa bu bağlantıyı doğrudan (ör. WhatsApp) iletebilirsiniz.'
              : 'Bu bağlantıyı görüşmeciye kendiniz iletin.' ?>
          <span class="num"><?= Checkins::TTL_DAYS ?></span> gün geçerli, tek kullanımlık.
        </p>
        <div class="flex gap-2">
          <label for="checkinLink" class="sr-only">Check-in bağlantısı</label>
          <input id="checkinLink" type="text" readonly value="<?= e($checkinLink['url']) ?>"
                 class="field flex-1 min-w-0 text-xs font-mono text-ink-muted">
          <button type="button" data-copy="#checkinLink" class="btn btn-primary shrink-0">Kopyala</button>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($checkinPending !== null): ?>
      <p class="text-xs text-ink-muted mb-3">
        Bekleyen bağlantı var:
        <?= $checkinPending['sent_at'] !== null
            ? 'e-posta ' . e(dt($checkinPending['sent_at'])) . ' tarihinde gönderildi'
            : 'panelden üretildi, e-posta çıkmadı' ?>,
        henüz doldurulmadı. <span class="num"><?= e(dt($checkinPending['expires_at'], 'd.m.Y')) ?></span> tarihine kadar geçerli.
      </p>
    <?php endif; ?>

    <?php if ($isActive): ?>
      <form method="post" action="<?= e(url("/danisanlar/{$client['id']}/check-in")) ?>"
            <?= $checkinPending !== null
                ? 'data-confirm="Bekleyen bağlantı iptal edilip yenisi üretilecek. Eski bağlantı çalışmayacak. Devam edilsin mi?"'
                : '' ?>>
        <?= Csrf::field() ?>
        <button class="btn <?= $checkins === [] ? 'btn-primary' : 'btn-quiet' ?> btn-sm">
          <?= $checkinPending !== null ? 'Bağlantıyı yenile ve gönder' : 'Check-in bağlantısı gönder' ?>
        </button>
      </form>
      <p class="field-hint">
        <?php if ($client['email'] === null): ?>
          Kayıtta e-posta yok — bağlantı üretilir, iletmek size kalır.
        <?php else: ?>
          Bağlantı <?= e((string) $client['email']) ?> adresine gider ve burada da gösterilir.
        <?php endif; ?>
      </p>

      <?php // ── Haftalık gönderim anahtarı ────────────────────────────────
            // Yukarıdaki düğme döngüyü BAŞLATIR, bu anahtar haftalık cron'u
            // durdurup yeniden açar. Kapalıyken düğme çalışmaya devam eder:
            // "bu dönem e-posta çıkmasın" demek, "artık check-in yapmıyoruz"
            // demek değil — ikisini tek düğmeye bindirmek, e-postayı durdurmak
            // isteyen terapiste kaydı arşivletirdi.
            //
            // Aynı anahtarın toplu hâli panel → Check-in ekranında. ?>
      <div class="mt-4 pt-3 border-t border-warm-secondary">
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
              Kayıtta e-posta adresi yok; anahtar açık olsa da haftalık ileti
              çıkmaz. Bağlantıyı yukarıdan üretip elden iletebilirsiniz.
            <?php elseif ($checkinAuto): ?>
              Cron her hafta bağlantı yolluyor. Durdurulursa yalnız e-posta
              kesilir; buradan elle bağlantı göndermeye devam edebilirsiniz.
            <?php else: ?>
              Cron bu görüşmeciye bağlantı yollamıyor. Yukarıdaki düğme yine
              çalışır — elle gönderilen bağlantı geçerlidir.
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
      <p class="text-xs text-ink-light">Arşivlenmiş kayıt — yeni check-in gönderilmez.</p>
    <?php endif; ?>
  </div>
</section>
