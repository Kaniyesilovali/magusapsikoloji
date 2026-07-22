<?php
declare(strict_types=1);

namespace Panel\Controllers;

use Panel\Audit;
use Panel\Auth;
use Panel\ClientScope;
use Panel\Crypto;
use Panel\Db;
use Panel\Rbac;
use Panel\Schema;
use Panel\View;
use RuntimeException;

/**
 * Danışan dosyası — başvuru nedeni, öykü, formülasyon, plan.
 *
 * Seans notundan ayrı tutulur çünkü ayrı sorulara cevap veriyorlar: seans notu
 * "o gün ne oldu", dosya "bu kişiyle ne üzerinde çalışıyoruz". Terapist ikincisini
 * her seans öncesi okumak ister ve bunun için on iki seans notunu açmak zorunda
 * kalmamalıdır.
 *
 * Saklama kuralı seans notuyla aynı: yalnız şifreli, yalnız yazarına açık.
 * Kayıt (danışan, terapist) çifti başına tektir — devir hâlinde devralan terapist
 * öncekinin dosyasını okuyamaz, kendi formülasyonunu yazar.
 */
final class CaseFileController
{
    public function form(int $clientId): void
    {
        $actor  = Auth::requirePermission('note.read.own');
        $client = $this->findClient($clientId, $actor);
        $file   = $this->findFile($clientId, (int) $actor['id']);

        $body = '';
        if ($file !== null) {
            try {
                $body = Crypto::decrypt((string) $file['ciphertext'], (string) $file['nonce']);
                Audit::log('case.read', 'client', $clientId);
            } catch (RuntimeException $e) {
                flash('error', 'Dosya çözülemedi: ' . $e->getMessage());
            }
        }

        View::render('cases/form', [
            'title'     => 'Danışan dosyası',
            'client'    => $client,
            'file'      => $file,
            'body'      => $body,
            'available' => Crypto::available(),
            'actor'     => $actor,
        ]);
    }

    public function save(int $clientId): void
    {
        $actor  = Auth::requirePermission('note.write');
        $client = $this->findClient($clientId, $actor);
        $file   = $this->findFile($clientId, (int) $actor['id']);

        $body = post('body');
        if ($body === '') {
            flash('error', 'Dosya boş kaydedilemez. Silmek yerine içeriği düzeltin.');
            redirect("/danisanlar/{$clientId}/dosya");
        }

        try {
            [$cipher, $nonce] = Crypto::encrypt($body);
        } catch (RuntimeException $e) {
            flash('error', 'Dosya şifrelenemedi: ' . $e->getMessage());
            redirect("/danisanlar/{$clientId}/dosya");
        }

        if ($file === null) {
            Db::run(
                'INSERT INTO case_files (client_id, therapist_id, ciphertext, nonce, created_at)
                 VALUES (?, ?, ?, ?, NOW())',
                [$clientId, $actor['id'], $cipher, $nonce]
            );
        } else {
            Db::run(
                'UPDATE case_files SET ciphertext = ?, nonce = ?, updated_at = NOW() WHERE id = ?',
                [$cipher, $nonce, $file['id']]
            );
        }

        // İçerik audit kaydına ASLA yazılmaz — yalnız yazıldığı bilgisi.
        Audit::log('case.written', 'client', $clientId, ['yeni' => $file === null]);

        flash('success', $client['full_name'] . ' dosyası şifrelenerek kaydedildi.');
        redirect("/danisanlar/{$clientId}");
    }

    // ── Yardımcılar ─────────────────────────────────────────────

    private function findClient(int $clientId, array $actor): array
    {
        if (!Schema::caseFilesReady()) {
            View::error(
                503,
                'Danışan dosyası henüz kurulmadı',
                Rbac::can($actor, 'settings.manage')
                    ? 'Sistem ekranından bekleyen veritabanı güncellemesini uygulayın.'
                    : 'Veritabanı güncellemesi bekleniyor. Süper admin ile görüşün.'
            );
            exit;
        }

        [$scope, $params] = ClientScope::filter($actor);
        array_unshift($params, $clientId);

        $client = Db::one("SELECT c.* FROM clients c WHERE c.id = ? AND ({$scope}) LIMIT 1", $params);
        if ($client === null) {
            View::error(404, 'Danışan bulunamadı', 'Dosya yalnız kendi danışanlarınız için açılabilir.');
            exit;
        }
        return $client;
    }

    private function findFile(int $clientId, int $therapistId): ?array
    {
        return Db::one(
            'SELECT * FROM case_files WHERE client_id = ? AND therapist_id = ? LIMIT 1',
            [$clientId, $therapistId]
        );
    }
}
