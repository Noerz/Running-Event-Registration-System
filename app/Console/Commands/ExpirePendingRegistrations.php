<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Participant;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class ExpirePendingRegistrations extends Command
{
    protected $signature = 'participants:expire-pending';

    protected $description = 'Membatalkan pendaftaran pending yang melewati batas waktu verifikasi';

    public function handle(): int
    {
        $cutoff = Carbon::now()->subDays((int) config('registration.pending_expiry_days', 7));

        $expired = Participant::query()
            ->where('registration_status', 'pending')
            ->where('created_at', '<', $cutoff)
            ->with('payment')
            ->get();

        foreach ($expired as $participant) {
            // Hapus bukti pembayaran untuk mengosongkan storage privat.
            if ($participant->payment?->payment_proof) {
                Storage::disk(config('registration.payment_proof_disk'))
                    ->delete($participant->payment->payment_proof);
            }

            $participant->payment?->update([
                'payment_status' => 'rejected',
                'rejection_reason' => 'Batas waktu verifikasi pembayaran telah lewat (otomatis).',
            ]);

            $participant->forceFill(['registration_status' => 'rejected'])->save();

            ActivityLog::record(
                action: 'registration_expired',
                subject: $participant,
                description: "Pendaftaran {$participant->registration_code} dibatalkan otomatis karena tidak diverifikasi dalam batas waktu.",
            );
        }

        $this->info("Dibatalkan: {$expired->count()} pendaftaran pending kadaluarsa.");

        return self::SUCCESS;
    }
}
