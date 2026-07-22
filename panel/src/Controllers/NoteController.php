<?php
declare(strict_types=1);

namespace Panel\Controllers;

use Panel\Audit;
use Panel\Auth;
use Panel\Crypto;
use Panel\Db;
use Panel\View;
use RuntimeException;

/**
 * Seans notları — özel nitelikli sağlık verisi.
 *
 * Üç kural kodun tamamını belirliyor:
 *  1. Not yalnız şifreli saklanır; veritabanı yedeği tek başına okunamaz.
 *  2. Notu yalnız **yazan terapist** okuyabilir. Süper admin dahil kimse okuyamaz —
 *     yetki matrisinde `note.*` yalnız terapistte olduğu için bu kural burada
 *     ayrıca kontrol edilmez, `requirePermission` zaten kapıyı kapatır.
 *  3. Randevu başka bir terapiste devredilirse, devralan terapist eski notu ne
 *     okuyabilir ne de üzerine yazabilir.
 */
final class NoteController
{
    public function form(int $appointmentId): void
    {
        $actor       = Auth::requirePermission('note.write');
        $appointment = $this->findAppointment($appointmentId, $actor);
        $note        = $this->findNote($appointmentId);

        $body    = '';
        $foreign = $note !== null && (int) $note['therapist_id'] !== (int) $actor['id'];

        if ($note !== null && !$foreign) {
            try {
                $body = Crypto::decrypt((string) $note['ciphertext'], (string) $note['nonce']);
                Audit::log('note.read', 'appointment', $appointmentId);
            } catch (RuntimeException $e) {
                flash('error', 'Not çözülemedi: ' . $e->getMessage());
                $body = '';
            }
        }

        View::render('notes/form', [
            'title'       => 'Seans notu',
            'appointment' => $appointment,
            'note'        => $note,
            'body'        => $body,
            'foreign'     => $foreign,
            'available'   => Crypto::available(),
            'actor'       => $actor,
        ]);
    }

    public function save(int $appointmentId): void
    {
        $actor       = Auth::requirePermission('note.write');
        $appointment = $this->findAppointment($appointmentId, $actor);
        $note        = $this->findNote($appointmentId);

        if ($note !== null && (int) $note['therapist_id'] !== (int) $actor['id']) {
            Audit::log('access.denied', 'appointment', $appointmentId, ['reason' => 'başka terapistin seans notu']);
            View::error(403, 'Yetkiniz yok', 'Bu seansın notunu başka bir terapist yazmış; üzerine yazılamaz.');
            exit;
        }

        $body = post('body');
        if ($body === '') {
            flash('error', 'Not boş olamaz. Kaydı silmek yerine içeriği düzeltin.');
            redirect("/randevular/{$appointmentId}/not");
        }

        try {
            [$cipher, $nonce] = Crypto::encrypt($body);
        } catch (RuntimeException $e) {
            flash('error', 'Not şifrelenemedi: ' . $e->getMessage());
            redirect("/randevular/{$appointmentId}/not");
        }

        if ($note === null) {
            Db::run(
                'INSERT INTO session_notes (appointment_id, therapist_id, ciphertext, nonce, created_at)
                 VALUES (?, ?, ?, ?, NOW())',
                [$appointmentId, $actor['id'], $cipher, $nonce]
            );
        } else {
            Db::run(
                'UPDATE session_notes SET ciphertext = ?, nonce = ?, updated_at = NOW() WHERE id = ?',
                [$cipher, $nonce, $note['id']]
            );
        }

        // Not içeriği audit kaydına ASLA yazılmaz — yalnız yazıldığı bilgisi.
        Audit::log('note.written', 'appointment', $appointmentId, ['yeni' => $note === null]);

        flash('success', 'Seans notu şifrelenerek kaydedildi.');
        redirect('/randevular?hafta=' . substr((string) $appointment['starts_at'], 0, 10));
    }

    // ── Yardımcılar ─────────────────────────────────────────────

    /** Terapistin kendi randevusu değilse 404 — varlığı bile sızmasın. */
    private function findAppointment(int $id, array $actor): array
    {
        $appointment = Db::one(
            'SELECT a.*, c.full_name AS client_name
               FROM appointments a
               JOIN clients c ON c.id = a.client_id
              WHERE a.id = ? AND a.therapist_id = ?
              LIMIT 1',
            [$id, $actor['id']]
        );

        if ($appointment === null) {
            View::error(404, 'Randevu bulunamadı', 'Seans notu yalnız kendi randevularınıza yazılabilir.');
            exit;
        }
        return $appointment;
    }

    private function findNote(int $appointmentId): ?array
    {
        return Db::one('SELECT * FROM session_notes WHERE appointment_id = ? LIMIT 1', [$appointmentId]);
    }
}
