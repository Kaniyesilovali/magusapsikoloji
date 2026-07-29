<?php
use Panel\Checkins;
/** @var array{rows:list<array{key:string,label:string,cells:list<int>}>,events:array<int,?string>} $ecosystem */
/** @var list<array> $checkins */

// Ekolojik şerit — terapistin asıl okuduğu şey.
//
// Eğrinin hemen altında ve AYNI haftaların üstünde duruyor: sütunlar eğrinin
// noktalarıyla birebir aynı check-in'ler. Şeridin tek işi şu cümleyi hesap
// yapmadan okutmak: "sınav haftalarında Okul satırı koyu, ruh hali bir hafta
// sonra düşüyor."
//
// Tablo, SVG değil. Buradaki veri sürekli bir eğri değil üç değerli bir
// ızgara; hücreye yazılan işaret ekran okuyucuya da, kopyalayana da aynı şeyi
// söylüyor. Dar ekranda kutu kendi içinde kayar (takvimdeki çözüm).
$glyph = [1 => '↑', 0 => '·', -1 => '↓'];
$tone  = [1 => 'is-up', 0 => 'is-calm', -1 => 'is-down'];
?>

<?php if ($ecosystem['rows'] !== []): ?>
  <div class="px-5 pt-5">
    <h3 class="sheet-title text-sm">Haftanın hâli</h3>
    <?php // Bu cümle sabit ve kalmalı: aşağıdaki işaretler bir ölçüm değil,
          // ebeveynin o haftaki izlenimi. Şeridi bir ölçek gibi okumak,
          // olmayan bir kesinlik üretir. ?>
    <p class="text-xs text-ink-light mt-1">
      Bunlar ölçüm değil, ebeveynin o haftaki işaretidir.
      <span class="text-ink-muted">↑ iyi geldi · ↓ zorladı · · öne çıkmadı</span>
    </p>
  </div>

  <div class="ci-scroll px-5 py-4">
    <table class="eco-strip">
      <thead>
        <tr>
          <th scope="col" class="eco-strip-head">Alan</th>
          <?php foreach ($checkins as $row): ?>
            <th scope="col" class="eco-strip-week"
                title="<?= e(Checkins::weekLabel((string) $row['created_at'])) ?>">
              <?= e(Checkins::weekShort((string) $row['created_at'])) ?>
            </th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($ecosystem['rows'] as $line): ?>
          <tr>
            <th scope="row" class="eco-strip-head"><?= e($line['label']) ?></th>
            <?php foreach ($line['cells'] as $value): ?>
              <td class="eco-cell <?= e($tone[$value]) ?>">
                <span aria-hidden="true"><?= $glyph[$value] ?></span>
                <span class="sr-only"><?= $value === 1 ? 'iyi geldi' : ($value === -1 ? 'zorladı' : 'öne çıkmadı') ?></span>
              </td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>

        <?php if ($ecosystem['events'] !== []): ?>
          <?php // Olay satırı en altta ve tek satır: dikey çapa. Ekolojik
                // okumanın en değerli tek verisi bu, ama her hafta dolmuyor. ?>
          <tr>
            <th scope="row" class="eco-strip-head">Olay</th>
            <?php foreach ($checkins as $row): ?>
              <?php $label = $ecosystem['events'][(int) $row['id']] ?? null; ?>
              <td class="eco-cell <?= array_key_exists((int) $row['id'], $ecosystem['events']) ? 'is-event' : '' ?>">
                <?php if (array_key_exists((int) $row['id'], $ecosystem['events'])): ?>
                  <span title="<?= e($label ?? '') ?>" aria-hidden="true">✦</span>
                  <span class="sr-only">bu hafta bir şey oldu<?= $label !== null ? ': ' . e($label) : '' ?></span>
                <?php endif; ?>
              </td>
            <?php endforeach; ?>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php // Etiketler tabloda yalnız ipucu olarak duruyor; okunur hâli burada,
        // çünkü dokunmatik ekranda `title` görünmez. ?>
  <?php $written = array_filter($ecosystem['events'], static fn (?string $l): bool => $l !== null && $l !== ''); ?>
  <?php if ($written !== []): ?>
    <div class="px-5 pb-4 space-y-1">
      <?php foreach ($checkins as $row): ?>
        <?php $label = $ecosystem['events'][(int) $row['id']] ?? null; ?>
        <?php if ($label !== null && $label !== ''): ?>
          <p class="text-xs text-ink-muted">
            <span class="num"><?= e(Checkins::weekLabel((string) $row['created_at'])) ?></span>
            — ✦ <?= e($label) ?>
          </p>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>
