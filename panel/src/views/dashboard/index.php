<?php
use Panel\Rbac;
/** @var array $user @var array $stats @var array $appointments */

$statusLabels = [
    'scheduled' => 'Planlandı',
    'confirmed' => 'Onaylandı',
    'completed' => 'Tamamlandı',
    'cancelled' => 'İptal',
    'no_show'   => 'Gelmedi',
];
$isStaff = Rbac::can($user, 'appointment.view.all') || $user['role'] === Rbac::THERAPIST;
?>

<header class="mb-8">
  <h1 class="text-2xl font-semibold text-ink">Merhaba, <?= e(explode(' ', (string) $user['full_name'])[0]) ?></h1>
  <p class="text-sm text-ink-light mt-1"><?= e(Rbac::label($user['role'])) ?> olarak giriş yaptınız.</p>
</header>

<?php if ($stats !== []): ?>
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-8">
    <?php foreach ($stats as $stat): ?>
      <div class="bg-white border border-warm-tertiary rounded-2xl p-4">
        <p class="text-2xl font-semibold text-ink"><?= (int) $stat['value'] ?></p>
        <p class="text-xs text-ink-light mt-1"><?= e($stat['label']) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<section class="bg-white border border-warm-tertiary rounded-2xl overflow-hidden">
  <div class="px-5 py-4 border-b border-warm-tertiary">
    <h2 class="font-medium text-ink"><?= $isStaff ? 'Yaklaşan randevular' : 'Randevularım' ?></h2>
  </div>

  <?php if ($appointments === []): ?>
    <p class="px-5 py-8 text-sm text-ink-light text-center">
      Yaklaşan randevu yok.
      <?php if (!$isStaff): ?><br>Randevu bilgileriniz merkez tarafından girildiğinde burada görünür.<?php endif; ?>
    </p>
  <?php else: ?>
    <ul class="divide-y divide-warm-secondary">
      <?php foreach ($appointments as $appointment): ?>
        <li class="px-5 py-3.5 flex flex-wrap items-center gap-x-4 gap-y-1">
          <span class="text-sm font-medium text-ink tabular-nums w-36 shrink-0"><?= e(dt($appointment['starts_at'])) ?></span>
          <span class="text-sm text-ink flex-1 min-w-40">
            <?= e($isStaff ? $appointment['client_name'] : $appointment['therapist_name']) ?>
          </span>
          <span class="text-xs text-ink-light"><?= $appointment['location'] === 'online' ? 'Online' : 'Merkezde' ?></span>
          <span class="text-xs px-2 py-0.5 rounded-full bg-warm-secondary text-ink-muted">
            <?= e($statusLabels[$appointment['status']] ?? $appointment['status']) ?>
          </span>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>

<p class="text-xs text-ink-light mt-6">
  Randevu ve danışan yönetimi ekranları bir sonraki sürümde açılacak. Bu sürümde kullanıcı hesapları ve profil yönetimi kullanılabilir.
</p>
