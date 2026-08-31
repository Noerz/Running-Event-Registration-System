<?php

namespace Tests\Concerns;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Participant;
use App\Models\RaceCategory;
use App\Models\User;
use Illuminate\Http\UploadedFile;

trait InteractsWithRegistration
{
    protected function createAdmin(): User
    {
        return User::factory()->create([
            'name' => 'Panitia Test',
            'email' => 'admin@test.com',
            'password' => 'password',
            'is_admin' => true,
        ]);
    }

    protected function createOpenEvent(array $categories = []): Event
    {
        $event = Event::create([
            'name' => 'Test Run 2026',
            'slug' => 'test-run-'.uniqid(),
            'description' => 'Deskripsi event untuk keperluan pengujian.',
            'event_date' => now()->addDays(30),
            'location' => 'Jakarta',
            'registration_start' => today(),
            'registration_end' => today()->addDays(14),
            'status' => EventStatus::Open,
        ]);

        foreach ($categories ?: [
            ['name' => '5K Fun Run', 'code' => '5K', 'distance' => 5, 'price' => 100000, 'quota' => 2],
            ['name' => '10K Run', 'code' => '10K', 'distance' => 10, 'price' => 200000, 'quota' => 10],
            ['name' => 'Half Marathon', 'code' => '21K', 'distance' => 21.0975, 'price' => 300000, 'quota' => 10],
        ] as $category) {
            $event->raceCategories()->create([...$category, 'status' => 'active']);
        }

        return $event->refresh();
    }

    protected function registrationData(int $categoryId): array
    {
        return [
            'full_name' => 'Budi Santoso',
            'phone' => '0812-3456-7890',
            'email' => 'Budi@Example.com',
            'gender' => 'male',
            'birth_place' => 'Bandung',
            'birth_date' => '1995-05-17',
            'identity_number' => null,
            'address' => 'Jl. Merdeka No. 10',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'race_category_id' => $categoryId,
            'jersey_size' => 'L',
            'running_club' => null,
            'emergency_contact_name' => 'Siti Santoso',
            'emergency_contact_phone' => '081298765432',
            'payment_method' => 'transfer_bank',
            'amount' => 100000,
            'payment_proof' => UploadedFile::fake()->image('proof.jpg'),
            'agree_terms' => '1',
        ];
    }

    protected function createPendingParticipant(Event $event, RaceCategory $category, array $overrides = []): Participant
    {
        $participant = Participant::create([
            ...$this->registrationData($category->getKey()),
            'race_category_id' => $category->getKey(),
            'event_id' => $event->getKey(),
            'full_name' => $overrides['full_name'] ?? 'Peserta Uji '.uniqid(),
            'email' => $overrides['email'] ?? 'peserta'.uniqid().'@example.com',
            'phone' => $overrides['phone'] ?? '62811'.substr((string) (microtime(true) * 10000), -8),
            'amount' => $overrides['amount'] ?? $category->price,
            'payment_proof' => 'payment-proofs/dummy.jpg',
            'age' => \Carbon\Carbon::parse('1995-05-17')->age,
        ]);

        $participant->forceFill([
            'registration_code' => sprintf('REG-%d-%05d', now()->year, $participant->getKey()),
        ])->save();

        $participant->payment()->create([
            'payment_method' => 'transfer_bank',
            'amount' => $overrides['amount'] ?? $category->price,
            'payment_proof' => 'payment-proofs/dummy.jpg',
            'payment_status' => 'pending',
        ]);

        return $participant->refresh();
    }
}

