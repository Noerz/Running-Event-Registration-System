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
}
