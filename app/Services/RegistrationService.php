<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Participant;
use App\Models\RaceCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RegistrationService
{
    /**
     * Menyimpan pendaftaran peserta beserta data pembayaran dalam satu transaksi.
     */
    public function register(array $data): Participant
    {
        /** @var Participant $participant */
        $participant = DB::transaction(function () use ($data) {
            // Lock kategori membuat cek kuota dan insert peserta serial per kategori.
            [$event, $category] = $this->resolveOpenCategory($data['race_category_id'], lock: true);

            if ($category->remainingQuota() < 1) {
                throw new RuntimeException('Kuota kategori '.$category->name.' sudah penuh.');
            }

            $participant = Participant::create([
                ...$this->participantAttributes($data),
                'event_id' => $event->getKey(),
                'race_category_id' => $category->getKey(),
            ]);

            // Format REG-{tahun}-{id}: unik dan bebas race condition karena
            // diturunkan dari primary key auto increment.
            $participant->forceFill([
                'registration_code' => sprintf('REG-%d-%05d', now()->year, $participant->getKey()),
            ])->save();

            $participant->payment()->create($this->paymentAttributes($data));

            return $participant;
        });

        $participant->load('raceCategory');

        ActivityLog::record(
            action: 'registration_created',
            subject: $participant,
            description: "Pendaftaran baru {$participant->registration_code} pada kategori {$participant->raceCategory->name}.",
        );

        return $participant->refresh();
    }

    private function resolveOpenCategory(int|string $categoryId, bool $lock = false): array
    {
        $query = RaceCategory::query()
            ->whereKey($categoryId)
            ->where('status', 'active')
            ->with('event');

        if ($lock) {
            $query->lockForUpdate();
        }

        $category = $query->firstOrFail();

        $event = $category->event;

        if (! $event instanceof Event || ! $event->isRegistrationOpen()) {
            throw new RuntimeException('Pendaftaran untuk event ini sedang tidak dibuka.');
        }

        return [$event, $category];
    }

    private function participantAttributes(array $data): array
    {
        return [
            'full_name' => $data['full_name'],
            'phone' => $data['phone'],
            'email' => strtolower($data['email']),
            'gender' => $data['gender'],
            'birth_place' => $data['birth_place'],
            'birth_date' => $data['birth_date'],
            'age' => Carbon::parse($data['birth_date'])->age,
            'identity_number' => $data['identity_number'] ?? null,
            'address' => $data['address'],
            'city' => $data['city'],
            'province' => $data['province'],
            'jersey_size' => strtoupper($data['jersey_size']),
            'running_club' => $data['running_club'] ?? null,
            'emergency_contact_name' => $data['emergency_contact_name'],
            'emergency_contact_phone' => $data['emergency_contact_phone'],
            'registration_status' => RegistrationStatus::Pending,
        ];
    }

    private function paymentAttributes(array $data): array
    {
        return [
            'payment_method' => $data['payment_method'],
            'amount' => $data['amount'],
            'payment_proof' => $data['payment_proof']->store('payment-proofs', config('registration.payment_proof_disk')),
            'payment_status' => PaymentStatus::Pending,
        ];
    }
}
