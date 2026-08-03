<?php
use Panel\Patterns;
/** @var array{rows:list<array{text:string,detail:string}>,anchors:list<array{label:string,before:list<int>,after:list<int>}>} $patterns */

// Seans öncesi 40 saniye. Bu blok birey sayfasının en üstünde, idari
// alanların üstünde duruyor — plandaki tek entegrasyon kuralı: terapistin
// zaten açtığı sayfada, zaten açtığı anda. Ayrı bir "ekosistem paneli" hiç
// açılmazdı.
//
// Eşiği geçen bir şey yoksa bu dosya hiçbir şey çizmez. Boş bir kutu bile
// çizmiyor: "şu an bakılacak bir şey yok" demek de bir iddia ve her hafta
// tekrarlandığında terapisti buraya bakmamayı öğretir.
$rows    = $patterns['rows'] ?? [];
$anchors = $patterns['anchors'] ?? [];
?>

<?php if ($rows !== [] || $anchors !== []): ?>
  <?php // Aradaki boşluğu sütunun kendi gap'i veriyor (bkz. clients/show.php). ?>
  <section class="sheet">
    <div class="sheet-head">
      <div>
        <h2 class="sheet-title">Bakılacak yerler</h2>
        <p class="text-xs text-ink-light mt-1">
          Check-in işaretleriyle ruh hali aynı haftalara denk geliyor mu — sayılmış,
          hesaplanmamış. En fazla üç satır.
        </p>
      </div>
    </div>

    <?php if ($rows !== []): ?>
      <ul class="divide-y divide-warm-secondary">
        <?php foreach ($rows as $row): ?>
          <li class="px-5 py-4">
            <p class="text-sm text-ink"><?= e($row['text']) ?></p>
            <p class="text-xs text-ink-light mt-1 num"><?= e($row['detail']) ?></p>
            <p class="text-xs text-ink-muted mt-2"><?= e(Patterns::DISCLAIMER) ?></p>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <?php if ($anchors !== []): ?>
      <?php // Olay çapası bir kural değil, bir görüntü: öncesi ve sonrası yan
            // yana konuyor, cümle kurulmuyor. Yorumu terapist yapar. ?>
      <div class="px-5 py-4 <?= $rows !== [] ? 'border-t border-warm-secondary' : '' ?>">
        <p class="eyebrow">✦ işaretinin çevresi</p>
        <?php foreach ($anchors as $anchor): ?>
          <p class="text-sm text-ink mt-2">
            <?php if ($anchor['label'] !== ''): ?>
              <span class="text-ink-muted">“<?= e($anchor['label']) ?>”</span> —
            <?php endif; ?>
            <span class="num">
              öncesi <?= e($anchor['before'] === [] ? '—' : implode(' · ', $anchor['before'])) ?>
              <span class="text-ink-light">→</span>
              sonrası <?= e($anchor['after'] === [] ? '—' : implode(' · ', $anchor['after'])) ?>
            </span>
          </p>
        <?php endforeach; ?>
        <p class="text-xs text-ink-light mt-2">Ruh hali puanları, haftalar sırayla.</p>
      </div>
    <?php endif; ?>
  </section>
<?php endif; ?>
