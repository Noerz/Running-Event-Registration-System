<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEventTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return [
            'name' => 'Event Baru Panitia',
            'description' => 'Deskripsi event baru.',
            'location' => 'Surabaya',
            'event_date' => '2030-01-01',
            'registration_start' => today()->toDateString(),
            'registration_end' => today()->addDays(10)->toDateString(),
            'status' => 'open',
            ...$overrides,
        ];
    }

    public function test_admin_can_create_event(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.events.store'), $this->validPayload())
            ->assertRedirect(route('admin.events.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('events', ['name' => 'Event Baru Panitia', 'status' => 'open']);
    }

    public function test_edited_event_appears_on_landing_page_immediately(): void
    {
        $admin = User::factory()->admin()->create();
        $event = Event::create([
            ...$this->validPayload(),
            'slug' => 'event-lama',
            'name' => 'Nama Lama',
        ]);

        // Halaman utama menampilkan nama lama.
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Nama Lama');

        // Admin mengubah nama dan tanggal event.
        $this->actingAs($admin)
            ->put(route('admin.events.update', $event), $this->validPayload([
                'name' => 'Nama Baru Hasil Edit',
                'event_date' => '2031-06-15',
            ]))
            ->assertRedirect(route('admin.events.index'))
            ->assertSessionHas('success');

        // Perubahan langsung terlihat di halaman utama, termasuk target countdown.
        $response = $this->get(route('home'))->assertOk()->assertSee('Nama Baru Hasil Edit');

        $content = $response->getContent();
        $this->assertMatchesRegularExpression(
            "/countdown\('2031-06-15T00:00:00/",
            $content,
            'Countdown harus memakai tanggal event terbaru dari database.'
        );
    }

    public function test_guest_cannot_manage_events(): void
    {
        $event = Event::create([
            ...$this->validPayload(),
            'slug' => 'guest-test',
        ]);

        $this->post(route('admin.events.store'), $this->validPayload())
            ->assertRedirect(route('admin.login'));

        $this->put(route('admin.events.update', $event), $this->validPayload())
            ->assertRedirect(route('admin.login'));
    }
}
