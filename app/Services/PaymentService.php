<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Mail\RegistrationConfirmedMail;
use App\Models\ActivityLog;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PaymentService
{
    /**
     * Memverifikasi (approve) pembayaran peserta secara idempotent:
     * - mengunci baris participant untuk mencegah race condition;
     * - membuat BIB number dengan counter terkunci per kategori;
     * - mencatat admin dan waktu verifikasi;
     * - mengirim email notifikasi ke peserta via queue.
     *
     * Mengembalikan true jika proses approve dijalankan,
     * false jika sebelumnya sudah approved (idempotent).
     */
    public function approve(Participant $participant, User $admin): bool
    {
        $bibIssued = false;

        DB::transaction(function () use ($participant, $admin, &$bibIssued) {
            /** @var Participant $locked */
            $locked = Participant::query()
                ->whereKey($participant->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $payment = $locked->payment()->lockForUpdate()->firstOrFail();

            // Hanya pembayaran pending yang boleh di-approve. Ini membuat proses
            // idempotent sekaligus memblokir approve atas pembayaran yang ditolak.
            if ($payment->payment_status !== PaymentStatus::Pending) {
                return false;
            }

            $bibNumber = $locked->raceCategory->reserveBibNumber()['bib_number'];

            $payment->update([
                'payment_status' => PaymentStatus::Approved,
                'verified_by' => $admin->getKey(),
                'verified_at' => now(),
                'rejection_reason' => null,
            ]);

            $locked->forceFill([
                'bib_number' => $bibNumber,
                'registration_status' => RegistrationStatus::Confirmed,
            ])->save();

            $bibIssued = true;

            return true;
        });

        if (! $bibIssued) {
            return false;
        }

        $participant->refresh();

        ActivityLog::record(
            action: 'payment_approved',
            subject: $participant,
            adminId: $admin->getKey(),
            description: "Pembayaran {$participant->registration_code} disetujui oleh {$admin->name}.",
        );
        ActivityLog::record(
            action: 'bib_issued',
            subject: $participant,
            adminId: $admin->getKey(),
            description: "BIB {$participant->bib_number} diterbitkan untuk {$participant->registration_code}.",
        );

        try {
            Mail::to($participant->email)->queue(new RegistrationConfirmedMail($participant));

            ActivityLog::record(
                action: 'email_queued',
                subject: $participant,
                adminId: $admin->getKey(),
                description: "Email konfirmasi BIB {$participant->bib_number} masuk antrean ke {$participant->email}.",
            );
        } catch (Throwable $e) {
            Log::error('Gagal memasukkan email konfirmasi ke queue.', [
                'registration_code' => $participant->registration_code,
                'error' => $e->getMessage(),
            ]);

            ActivityLog::record(
                action: 'email_failed',
                subject: $participant,
                adminId: $admin->getKey(),
                description: 'Gagal mengantre email konfirmasi: '.$e->getMessage(),
            );
        }

        return true;
    }

    /**
     * Menolak pembayaran beserta alasan. Idempotent: penolakan ulang
     * tidak menimpa status yang sudah final.
     */
    public function reject(Participant $participant, string $reason, User $admin): bool
    {
        $rejected = DB::transaction(function () use ($participant, $reason, $admin) {
            /** @var Participant $locked */
            $locked = Participant::query()
                ->whereKey($participant->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $payment = $locked->payment()->lockForUpdate()->firstOrFail();

            if ($payment->payment_status !== PaymentStatus::Pending) {
                return false;
            }

            $payment->update([
                'payment_status' => PaymentStatus::Rejected,
                'rejection_reason' => $reason,
                'verified_by' => $admin->getKey(),
                'verified_at' => now(),
            ]);

            $locked->forceFill([
                'registration_status' => RegistrationStatus::Rejected,
                'bib_number' => null,
            ])->save();

            return true;
        });

        if (! $rejected) {
            return false;
        }

        ActivityLog::record(
            action: 'payment_rejected',
            subject: $participant,
            adminId: $admin->getKey(),
            description: "Pembayaran {$participant->registration_code} ditolak oleh {$admin->name}: {$reason}",
        );

        return true;
    }
}
