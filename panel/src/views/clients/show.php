<?php
use Panel\Csrf;
use Panel\Rbac;
use Panel\Scheduling;
/** @var array $client @var array $appointments @var array $actor */

$age      = age_from($client['birth_date']);
$isActive = $client['status'] === 'active';
?>

<header class="mb-6">
  <a href="<?= e(url('/danisanlar')) ?>" class="text-sm text-ink-muted hover:text-ink">← Danışanlar</a>
  <div class="flex flex-wrap items-start justify-between gap-3 mt-2">
    <div>
      <h1 class="text-2xl font-semibold text-ink">
        <?= e($client['full_name']) ?>
        <?php if (!$isActive): ?>
          <span class="text-xs align-middle px-2 py-0.5 rounded-full bg-warm-secondary text-ink-muted">Arşiv</span>
        <?php endif; ?>
      </h1>
      <p class="text-sm text-ink-light mt-1">
        Kayıt: <?= e(dt($client['created_at'], 'd.m.Y')) ?>
        <?php if ($client['therapist_name'] !== null): ?> — Terapist: <?= e($client['therapist_name']) ?><?php endif; ?>
      </p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <?php if (Rbac::can($actor, 'appointment.create') && $isActive): ?>
        <a href="<?= e(url('/randevular/yeni?danisan=' . (int) $client['id'])) ?>"
           class="bg-primary hover:bg-primary-hover text-white text-sm font-medium px-4 py-2.5 rounded-xl transition-colors">
          Randevu ver
        </a>
      <?php endif; ?>
      <?php if (Rbac::can($actor, 'client.update')): ?>
        <a href="<?= e(url("/danisanlar/{$client['id']}/duzenle")) ?>"
           class="text-sm text-ink-muted hover:text-ink border border-warm-tertiary bg-white px-4 py-2.5 rounded-xl">
          Düzenle
        </a>
      <?php endif; ?>
    </div>
  </div>
</header>

<?php if ($client['consent_at'] === null): ?>
  <div class="mb-6 px-4 py-3 rounded-xl border border-amber-200 bg-amber-50 text-amber-900 text-sm">
    Bu kayıt için <strong>açık rıza</strong> işaretlenmemiş. Seans verisi işlemeden önce
    aydınlatma metni sunulup rıza alınmalı ve kayıt düzenlenerek işaretlenmelidir.
  </div>
<?php endif; ?>

<div class="grid lg:grid-cols-3 gap-4 mb-6">
  <div class="bg-white border border-warm-tertiary rounded-2xl p-5 lg:col-span-2">
    <h2 class="font-medium text-ink mb-3">İletişim</h2>
    <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
      <div>
        <dt class="text-xs text-ink-light">Telefon</dt>
        <dd class="text-ink tabular-nums"><?= e($client['phone'] ?? '—') ?></dd>
      </div>
      <div>
        <dt class="text-xs text-ink-light">E-posta</dt>
        <dd class="text-ink break-all"><?= e($client['email'] ?? '—') ?></dd>
      </div>
      <div>
        <dt class="text-xs text-ink-light">Doğum tarihi</dt>
        <dd class="text-ink">
          <?= e($client['birth_date'] !== null ? tr_date_label($client['birth_date'], false) : '—') ?>
          <?php if ($age !== null): ?><span class="text-ink-light">(<?= $age ?>)</span><?php endif; ?>
        </dd>
      </div>
      <div>
        <dt class="text-xs text-ink-light">Panel hesabı</dt>
        <dd class="text-ink break-all"><?= e($client['account_email'] ?? 'bağlı değil') ?></dd>
      </div>
    </dl>
  </div>

  <div class="bg-white border border-warm-tertiary rounded-2xl p-5">
    <h2 class="font-medium text-ink mb-3">KVKK</h2>
    <?php if ($client['consent_at'] !== null): ?>
      <p class="text-sm text-ink">Açık rıza alındı.</p>
      <p class="text-xs text-ink-light mt-1">
        <?= e(dt($client['consent_at'])) ?><br>Metin sürümü: <?= e((string) $client['consent_version']) ?>
      </p>
    <?php else: ?>
      <p class="text-sm text-ink-muted">Rıza kaydı yok.</p>
    <?php endif; ?>
    <a href="<?= e(url("/danisanlar/{$client['id']}/riza")) ?>" target="_blank" rel="noopener"
       class="inline-block mt-3 text-xs text-primary hover:text-primary-dark font-medium">
      Rıza formunu yazdır →
    </a>
  </div>
</div>

<section class="bg-white border border-warm-tertiary rounded-2xl overflow-hidden">
  <div class="px-5 py-4 border-b border-warm-tertiary flex items-center justify-between">
    <h2 class="font-medium text-ink">Randevu geçmişi</h2>
    <span class="text-xs text-ink-light"><?= count($appointments) ?> kayıt</span>
  </div>

  <?php if ($appointments === []): ?>
    <p class="px-5 py-8 text-sm text-ink-light text-center">Randevu kaydı yok.</p>
  <?php else: ?>
    <ul class="divide-y divide-warm-secondary">
      <?php foreach ($appointments as $appointment): ?>
        <li class="px-5 py-3.5 flex flex-wrap items-center gap-x-4 gap-y-1">
          <span class="text-sm text-ink tabular-nums w-36 shrink-0"><?= e(dt($appointment['starts_at'])) ?></span>
          <span class="text-sm text-ink-muted flex-1 min-w-40"><?= e($appointment['therapist_name']) ?></span>
          <span class="text-xs text-ink-light"><?= e(Scheduling::locationLabel($appointment['location'])) ?></span>
          <span class="text-xs px-2 py-0.5 rounded-full bg-warm-secondary text-ink-muted">
            <?= e(Scheduling::statusLabel($appointment['status'])) ?>
          </span>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>

<?php if (Rbac::canAny($actor, ['client.update', 'client.delete'])): ?>
  <section class="mt-6 border border-warm-tertiary rounded-2xl p-5 bg-white">
    <h2 class="font-medium text-ink mb-1">Kayıt yönetimi</h2>
    <p class="text-xs text-ink-light mb-4">
      Arşivleme geri alınabilir ve randevu geçmişini korur. Kalıcı silme yalnız KVKK
      kapsamındaki silme talepleri içindir.
    </p>
    <div class="flex flex-wrap gap-3">
      <?php if (Rbac::can($actor, 'client.update')): ?>
        <form method="post" action="<?= e(url("/danisanlar/{$client['id']}/arsivle")) ?>">
          <?= Csrf::field() ?>
          <button class="text-sm text-ink-muted hover:text-ink border border-warm-tertiary px-4 py-2 rounded-xl">
            <?= $isActive ? 'Arşivle' : 'Arşivden çıkar' ?>
          </button>
        </form>
      <?php endif; ?>

      <?php if (Rbac::can($actor, 'client.delete')): ?>
        <form method="post" action="<?= e(url("/danisanlar/{$client['id']}/sil")) ?>"
              data-confirm="<?= e($client['full_name']) ?> kaydı, tüm randevuları ve seans notları kalıcı olarak silinecek. Bu işlem geri alınamaz. Devam edilsin mi?">
          <?= Csrf::field() ?>
          <button class="text-sm text-accent-dark hover:text-accent border border-accent/30 px-4 py-2 rounded-xl">
            Kalıcı olarak sil
          </button>
        </form>
      <?php endif; ?>
    </div>
  </section>
<?php endif; ?>
