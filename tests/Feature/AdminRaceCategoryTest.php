<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\RaceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRaceCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function createCategory(Event $event, array $overrides = []): RaceCategory
    {
        return RaceCategory::create([
            'event_id' => $event->getKey(),
            'name' => '5K Fun Run',
            'code' => '5K',
            'distance' => 5,
            'price' => 100000,
            'quota' => 10,
            'status' => 'active',
            ...$overrides,
        ]);
    }

    public function test_admin_can_update_only_quota(): void
    {
        $admin = User::factory()->admin()->create();
        $event = Event::create([
            'name' => 'Test Run 2026',
            'slug' => 'test-run-'.uniqid(),
            'description' => 'Deskripsi event.',
            'event_date' => now()->addDays(30),
            'location' => 'Jakarta',
            'registration_start' => today(),
            'registration_end' => today()->addDays(14),
            'status' => 'open',
        ]);
        $category = $this->createCategory($event);

        // Admin hanya mengubah kuota — field lain dikirim apa adanya.
        $this->actingAs($admin)
            ->put(route('admin.race-categories.update', $category), [
                'event_id' => $event->getKey(),
                'name' => $category->name,
                'code' => $category->code,
                'distance' => '5.00',
                'price' => $category->price,
                'quota' => 25,
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.race-categories.index'))
            ->assertSessionHas('success');

        $this->assertSame(25, $category->fresh()->quota);
    }

    public function test_code_unique_rule_ignores_own_record_on_update(): void
    {
        $admin = User::factory()->admin()->create();
        $event = Event::create([
            'name' => 'Test Run 2026',
            'slug' => 'test-run-'.uniqid(),
            'description' => 'Deskripsi event.',
            'event_date' => now()->addDays(30),
            'location' => 'Jakarta',
            'registration_start' => today(),
            'registration_end' => today()->addDays(14),
            'status' => 'open',
        ]);
        $category = $this->createCategory($event);

        $response = $this->actingAs($admin)
            ->from(route('admin.race-categories.edit', $category))
            ->put(route('admin.race-categories.update', $category), [
                'event_id' => $event->getKey(),
                'name' => $category->name,
                'code' => $category->code, // kode sama dengan dirinya sendiri: harus lolos.
                'distance' => '5.00',
                'price' => $category->price,
                'quota' => 15,
                'status' => 'active',
            ]);

        $this->assertFalse(
            $response->exceptions->filter(fn ($e) => $e instanceof \Illuminate\Validation\ValidationException)->isNotEmpty(),
            'Update kategori tidak boleh gagal validasi unique pada kode miliknya sendiri.'
        );

        $response->assertSessionHasNoErrors();
    }

    public function test_cannot_change_event_or_lower_quota_below_registered_participants(): void
    {
        $admin = User::factory()->admin()->create();
        $event1 = Event::create([
            'name' => 'Event 1',
            'slug' => 'event-1-'.uniqid(),
            'description' => 'Deskripsi.',
            'event_date' => now()->addDays(30),
            'location' => 'Jakarta',
            'registration_start' => today(),
            'registration_end' => today()->addDays(14),
            'status' => 'open',
        ]);
        $event2 = Event::create([
            'name' => 'Event 2',
            'slug' => 'event-2-'.uniqid(),
            'description' => 'Deskripsi.',
            'event_date' => now()->addDays(60),
            'location' => 'Bandung',
            'registration_start' => today(),
            'registration_end' => today()->addDays(14),
            'status' => 'open',
        ]);
        $category = $this->createCategory($event1, ['quota' => 10]);

        foreach ([1, 2] as $i) {
            \App\Models\Participant::create([
                'event_id' => $event1->getKey(),
                'race_category_id' => $category->getKey(),
                'registration_code' => "REG-2026-0000{$i}",
                'full_name' => "Runner {$i}",
                'email' => "runner{$i}@example.com",
                'phone' => "08123456789{$i}",
                'gender' => 'male',
                'birth_place' => 'Jakarta',
                'birth_date' => '1995-01-01',
                'age' => 30,
                'address' => 'Jl. Test',
                'city' => 'Jakarta',
                'province' => 'DKI Jakarta',
                'jersey_size' => 'M',
                'emergency_contact_name' => 'Kontak',
                'emergency_contact_phone' => '08129999999',
                'registration_status' => 'confirmed',
            ]);
        }

        // Gagal jika pindah event atau kuota < 2
        $this->actingAs($admin)
            ->put(route('admin.race-categories.update', $category), [
                'event_id' => $event2->getKey(),
                'name' => $category->name,
                'code' => $category->code,
                'distance' => '5.00',
                'price' => $category->price,
                'quota' => 1,
                'status' => 'active',
            ])
            ->assertSessionHasErrors(['event_id', 'quota']);
    }

    public function test_category_bib_format_and_digits_are_used_when_reserving_bib(): void
    {
        $admin = User::factory()->admin()->create();
        $event = Event::create([
            'name' => 'Test Run 2026',
            'slug' => 'test-run-'.uniqid(),
            'description' => 'Deskripsi.',
            'event_date' => '2026-11-11',
            'location' => 'Jakarta',
            'registration_start' => today(),
            'registration_end' => today()->addDays(14),
            'status' => 'open',
        ]);
        $category = $this->createCategory($event);

        $this->actingAs($admin)
            ->put(route('admin.race-categories.update', $category), [
                'event_id' => $event->getKey(),
                'name' => $category->name,
                'code' => '5K',
                'bib_format' => '{year}-{code}{number}',
                'bib_digits' => 3,
                'distance' => '5.00',
                'price' => $category->price,
                'quota' => 10,
                'status' => 'active',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('26-5K001', $category->fresh()->reserveBibNumber()['bib_number']);
    }
}
