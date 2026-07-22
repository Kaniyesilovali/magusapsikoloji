<?php
declare(strict_types=1);

namespace Panel\Controllers;

use DateTimeImmutable;
use Exception;
use Panel\Audit;
use Panel\Auth;
use Panel\Db;
use Panel\Money;
use Panel\Rbac;
use Panel\Scheduling;
use Panel\Schema;
use Panel\View;

/**
 * Seans ücretleri ve tahsilat.
 *
 * Ödeme durumu saklanmaz, hesaplanır: `fee` ile o randevuya girilmiş
 * tahsilatların toplamı karşılaştırılır. Ayrı bir "ödendi" bayrağı tutulsaydı
 * tahsilat silindiğinde ya da düzeltildiğinde bayrak geride kalır, kayıtlar
 * birbirini tutmazdı.
 *
 * Terapist kendi randevularının ücret ve ödeme durumunu görür, ücreti de
 * girebilir; ama **tahsilat kaydedemez** — para alma işi merkez yönetiminde.
 */
final class PaymentController
{
    public const METHODS = [
        'cash'     => 'Nakit',
        'card'     => 'Kart',
        'transfer' => 'Havale/EFT',
        'other'    => 'Diğer',
    ];

    private const STATUS_LABELS = [
        'belirsiz'  => 'Ücret girilmedi',
        'odenmedi'  => 'Ödenmedi',
        'kismi'     => 'Kısmi',
        'odendi'    => 'Ödendi',
    ];

    public function index(): void
    {
        $actor = $this->requireView();

        [$from, $to] = $this->range();
        [$scope, $params] = $this->visibilityFilter($actor);

        $therapistFilter = (int) query('terapist');
        if ($therapistFilter > 0 && Rbac::can($actor, 'payment.view.all')) {
            $scope   .= ' AND a.therapist_id = ?';
            $params[] = $therapistFilter;
        } else {
            $therapistFilter = 0;
        }

        $rows = Db::all(
            "SELECT a.id, a.starts_at, a.status, a.fee, a.client_id,
                    c.full_name AS client_name,
                    t.full_name AS therapist_name,
                    (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.appointment_id = a.id) AS paid
               FROM appointments a
               JOIN clients c ON c.id = a.client_id
               JOIN users   t ON t.id = a.therapist_id
              WHERE a.starts_at >= ? AND a.starts_at < ? AND {$scope}
           ORDER BY a.starts_at DESC",
            array_merge([$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')], $params)
        );

        foreach ($rows as $index => $row) {
            $rows[$index]['payment_status'] = $this->statusOf($row);
        }

        $status = query('durum', 'hepsi');
        if (isset(self::STATUS_LABELS[$status])) {
            $rows = array_values(array_filter($rows, static fn (array $r): bool => $r['payment_status'] === $status));
        } else {
            $status = 'hepsi';
        }

        View::render('payments/index', [
            'title'           => 'Ödemeler',
            'rows'            => $rows,
            'totals'          => $this->totals($rows),
            'from'            => $from,
            'to'              => $to->modify('-1 day'),
            'nav'             => $this->nav($from, $to),
            'status'          => $status,
            'statusLabels'    => self::STATUS_LABELS,
            'therapists'      => Rbac::can($actor, 'payment.view.all') ? $this->therapistOptions() : [],
            'therapistFilter' => $therapistFilter,
            'actor'           => $actor,
        ]);
    }

    public function show(int $appointmentId): void
    {
        $actor       = $this->requireView();
        $appointment = $this->find($appointmentId, $actor);

        $payments = Db::all(
            'SELECT p.*, u.full_name AS recorded_by
               FROM payments p
          LEFT JOIN users u ON u.id = p.created_by
              WHERE p.appointment_id = ?
           ORDER BY p.paid_at DESC, p.id DESC',
            [$appointmentId]
        );

        View::render('payments/show', [
            'title'       => 'Seans ödemesi',
            'appointment' => $appointment,
            'payments'    => $payments,
            'status'      => $this->statusOf($appointment),
            'statusLabels' => self::STATUS_LABELS,
            'methods'     => self::METHODS,
            'canManage'   => Rbac::can($actor, 'payment.manage'),
            'canSetFee'   => $this->canSetFee($actor, $appointment),
            'actor'       => $actor,
        ]);
    }

    public function setFee(int $appointmentId): void
    {
        $actor       = $this->requireView();
        $appointment = $this->find($appointmentId, $actor);

        if (!$this->canSetFee($actor, $appointment)) {
            Audit::log('access.denied', 'appointment', $appointmentId, ['reason' => 'ücret belirleme']);
            View::error(403, 'Yetkiniz yok', 'Bu randevunun ücretini değiştiremezsiniz.');
            exit;
        }

        $raw = post('fee');
        // Boş bırakmak ücreti "belirlenmedi"ye döndürür — 0,00 ile aynı şey değil.
        $fee = $raw === '' ? null : Money::parse($raw);

        if ($raw !== '' && $fee === null) {
            flash('error', 'Ücreti sayı olarak girin (ör. 1500 ya da 1.500,00).');
            redirect("/odemeler/{$appointmentId}");
        }

        Db::run('UPDATE appointments SET fee = ?, updated_at = NOW() WHERE id = ?', [$fee, $appointmentId]);
        Audit::log('payment.fee_set', 'appointment', $appointmentId, ['ucret' => $fee]);

        flash('success', $fee === null ? 'Ücret kaldırıldı.' : 'Ücret güncellendi: ' . Money::format($fee) . '.');
        redirect("/odemeler/{$appointmentId}");
    }

    public function storePayment(int $appointmentId): void
    {
        $actor       = Auth::requirePermission('payment.manage');
        $appointment = $this->find($appointmentId, $actor);

        $amount = Money::parse(post('amount'));
        $method = post('method');
        $paidAt = Scheduling::parseStart(post('paid_date'), post('paid_time') !== '' ? post('paid_time') : '12:00');

        $problem = match (true) {
            $amount === null || !Money::isPositive($amount) => 'Tahsilat tutarını sıfırdan büyük bir sayı olarak girin.',
            !array_key_exists($method, self::METHODS)       => 'Ödeme yöntemini seçin.',
            $paidAt === null                                => 'Geçerli bir ödeme tarihi girin.',
            default                                         => null,
        };

        if ($problem !== null) {
            flash('error', $problem);
            redirect("/odemeler/{$appointmentId}");
        }

        Db::run(
            'INSERT INTO payments (appointment_id, amount, method, paid_at, note, created_by, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())',
            [
                $appointmentId,
                $amount,
                $method,
                $paidAt->format('Y-m-d H:i:s'),
                post('note') !== '' ? mb_substr(post('note'), 0, 255) : null,
                $actor['id'],
            ]
        );

        Audit::log('payment.recorded', 'appointment', $appointmentId, ['tutar' => $amount, 'yontem' => $method]);

        flash('success', Money::format($amount) . ' tahsilat kaydedildi.');
        redirect("/odemeler/{$appointmentId}");
    }

    public function destroyPayment(int $paymentId): void
    {
        $actor = Auth::requirePermission('payment.manage');

        $payment = Db::one('SELECT * FROM payments WHERE id = ? LIMIT 1', [$paymentId]);
        if ($payment === null) {
            View::error(404, 'Tahsilat kaydı bulunamadı');
            exit;
        }

        $appointmentId = (int) $payment['appointment_id'];
        $this->find($appointmentId, $actor);

        Db::run('DELETE FROM payments WHERE id = ?', [$paymentId]);
        Audit::log('payment.deleted', 'appointment', $appointmentId, ['tutar' => $payment['amount']]);

        flash('success', 'Tahsilat kaydı silindi.');
        redirect("/odemeler/{$appointmentId}");
    }

    // ── Yardımcılar ─────────────────────────────────────────────

    private function requireView(): array
    {
        $user = Auth::requireLogin();
        if (!Rbac::canAny($user, ['payment.view.all', 'payment.view.own'])) {
            Audit::log('access.denied', 'permission', null, ['permission' => 'payment.view']);
            View::error(403, 'Yetkiniz yok', 'Ödeme kayıtlarını görüntüleme yetkiniz bulunmuyor.');
            exit;
        }

        // Deploy ile veritabanı güncellemesi arasındaki boşlukta SQL hatası yerine
        // ne yapılması gerektiğini söyleyen bir sayfa gösterilir.
        if (!Schema::paymentsReady()) {
            View::error(
                503,
                'Ödeme takibi henüz kurulmadı',
                Rbac::can($user, 'settings.manage')
                    ? 'Sistem ekranından bekleyen veritabanı güncellemesini uygulayın.'
                    : 'Veritabanı güncellemesi bekleniyor. Süper admin ile görüşün.'
            );
            exit;
        }

        return $user;
    }

    /** @return array{0:string,1:array} */
    private function visibilityFilter(array $actor): array
    {
        if (Rbac::can($actor, 'payment.view.all')) {
            return ['1 = 1', []];
        }
        return ['a.therapist_id = ?', [$actor['id']]];
    }

    private function find(int $appointmentId, array $actor): array
    {
        [$scope, $params] = $this->visibilityFilter($actor);
        array_unshift($params, $appointmentId);

        $appointment = Db::one(
            "SELECT a.*, c.full_name AS client_name, t.full_name AS therapist_name,
                    (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.appointment_id = a.id) AS paid
               FROM appointments a
               JOIN clients c ON c.id = a.client_id
               JOIN users   t ON t.id = a.therapist_id
              WHERE a.id = ? AND ({$scope})
              LIMIT 1",
            $params
        );

        if ($appointment === null) {
            View::error(404, 'Randevu bulunamadı', 'Kayıt silinmiş olabilir ya da görüntüleme yetkiniz yok.');
            exit;
        }
        return $appointment;
    }

    /** Ücreti yönetim ya da randevunun kendi terapisti belirleyebilir. */
    private function canSetFee(array $actor, array $appointment): bool
    {
        return Rbac::can($actor, 'payment.manage')
            || (Rbac::can($actor, 'payment.view.own') && (int) $appointment['therapist_id'] === (int) $actor['id']);
    }

    private function statusOf(array $row): string
    {
        if ($row['fee'] === null) {
            return 'belirsiz';
        }
        if (!Money::isPositive($row['paid'])) {
            return 'odenmedi';
        }
        return Money::gte($row['paid'], $row['fee']) ? 'odendi' : 'kismi';
    }

    /**
     * Toplamlar kuruş cinsinden tam sayı olarak toplanır; float toplama
     * uzun listelerde kuruş kaydırır ve kasa tutmaz.
     *
     * @return array{fee:string,paid:string,outstanding:string,count:int}
     */
    private function totals(array $rows): array
    {
        $fee = $paid = $outstanding = 0;

        foreach ($rows as $row) {
            $rowFee  = (int) round(((float) ($row['fee'] ?? 0)) * 100);
            $rowPaid = (int) round(((float) $row['paid']) * 100);

            $fee  += $rowFee;
            $paid += $rowPaid;

            // İptal edilmiş seans borç sayılmaz; tahsil edilmişse tahsilatta kalır.
            if ($row['status'] !== 'cancelled' && $rowFee > $rowPaid) {
                $outstanding += $rowFee - $rowPaid;
            }
        }

        return [
            'fee'         => number_format($fee / 100, 2, '.', ''),
            'paid'        => number_format($paid / 100, 2, '.', ''),
            'outstanding' => number_format($outstanding / 100, 2, '.', ''),
            'count'       => count($rows),
        ];
    }

    /**
     * Görüntülenen aralık. Varsayılan: içinde bulunulan ay.
     *
     * İki giriş var ve ikisi aynı formdan gelmiyor: `?ay=YYYY-MM` bütün bir ayı
     * seçen kısayol (dönem çubuğu), `?baslangic`/`?bitis` ise serbest aralık
     * (detay filtresi). Ay seçicisi tarihleri, tarih formu da ayı taşımadığı
     * için ikisi hiçbir zaman birlikte gönderilmez; buradaki öncelik yalnız
     * elle kurcalanmış bir adres için geçerli.
     *
     * @return array{0:DateTimeImmutable,1:DateTimeImmutable}  [başlangıç, bitişin ertesi günü]
     */
    private function range(): array
    {
        $month = $this->month(query('ay'));
        if ($month !== null) {
            return [$month, $month->modify('+1 month')];
        }

        $parse = static function (string $value, string $fallback): DateTimeImmutable {
            try {
                return new DateTimeImmutable($value !== '' ? $value : $fallback);
            } catch (Exception) {
                return new DateTimeImmutable($fallback);
            }
        };

        $from = $parse(query('baslangic'), 'first day of this month')->setTime(0, 0);
        $to   = $parse(query('bitis'), 'last day of this month')->setTime(0, 0);

        if ($to < $from) {
            $to = $from;
        }
        // Bitiş günü aralığa dahil olsun diye ertesi günün başına çekilir.
        return [$from, $to->modify('+1 day')];
    }

    /** '2026-07' → o ayın ilk günü; geçersizse null. */
    private function month(string $value): ?DateTimeImmutable
    {
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value) !== 1) {
            return null;
        }
        return new DateTimeImmutable($value . '-01 00:00:00');
    }

    /**
     * Dönem çubuğunun "önceki / sonraki" bağlantıları.
     *
     * Aralık tam bir aysa aylar arasında gezilir; değilse pencere **kendi
     * uzunluğu kadar** kayar. Serbest bir on günlük aralığa bakarken "önceki"nin
     * bir ay geri gitmesi, kullanıcının kurduğu aralığı sessizce bozardı.
     *
     * @param  DateTimeImmutable $to  bitişin ertesi günü
     * @return array{isMonth:bool, prev:array<string,string>, next:array<string,string>}
     */
    private function nav(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $last = $to->modify('-1 day');

        $isMonth = $from->format('j') === '1'
            && $last->format('Y-m-d') === $from->modify('last day of this month')->format('Y-m-d');

        if ($isMonth) {
            return [
                'isMonth' => true,
                'prev'    => ['ay' => $from->modify('-1 month')->format('Y-m')],
                'next'    => ['ay' => $from->modify('+1 month')->format('Y-m')],
            ];
        }

        $days = max(1, (int) $from->diff($to)->days);

        return [
            'isMonth' => false,
            'prev'    => [
                'baslangic' => $from->modify("-{$days} days")->format('Y-m-d'),
                'bitis'     => $last->modify("-{$days} days")->format('Y-m-d'),
            ],
            'next'    => [
                'baslangic' => $from->modify("+{$days} days")->format('Y-m-d'),
                'bitis'     => $last->modify("+{$days} days")->format('Y-m-d'),
            ],
        ];
    }

    private function therapistOptions(): array
    {
        return Db::all(
            'SELECT id, full_name FROM users WHERE role = ? AND status = \'active\' ORDER BY full_name',
            [Rbac::THERAPIST]
        );
    }
}
