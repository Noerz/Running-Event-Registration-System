<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithRegistration;
use Tests\TestCase;

class AdminSiteSettingTest extends TestCase
{
    use InteractsWithRegistration, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_guest_cannot_access_site_settings(): void
    {
        $this->get(route('admin.site-settings.index'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_site_settings(): void
    {
        $this->actingAs($this->createAdmin())
            ->get(route('admin.site-settings.index'))
            ->assertOk()
            ->assertSee('Pengaturan Web')
            ->assertSee('Gambar Halaman Utama');
    }

    public function test_admin_can_upload_and_reset_hero_image(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->post(route('admin.site-settings.hero-image.update'), [
                'hero_image' => UploadedFile::fake()->create('hero.jpg', 100, 'image/jpeg'),
            ])
            ->assertRedirect();

        $path = Setting::get('home_hero_image');
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
        $this->assertTrue(Setting::isCustomHero());

        $this->actingAs($admin)
            ->delete(route('admin.site-settings.hero-image.destroy'))
            ->assertRedirect();

        Storage::disk('public')->assertMissing($path);
        $this->assertNull(Setting::get('home_hero_image'));
        $this->assertFalse(Setting::isCustomHero());
    }
}
