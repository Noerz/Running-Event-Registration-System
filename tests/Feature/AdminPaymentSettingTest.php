<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithRegistration;
use Tests\TestCase;

class AdminPaymentSettingTest extends TestCase
{
    use RefreshDatabase, InteractsWithRegistration;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_guest_cannot_access_payment_settings(): void
    {
        $this->get(route('admin.payment-settings.index'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_payment_settings(): void
    {
        BankAccount::create(['bank' => 'BCA', 'number' => '123', 'holder' => 'Panitia', 'is_active' => true, 'order' => 1]);

        $this->actingAs($this->createAdmin())
            ->get(route('admin.payment-settings.index'))
            ->assertOk()
            ->assertSee('BCA')
            ->assertSee('123');
    }

    public function test_admin_can_create_bank_account(): void
    {
        $this->actingAs($this->createAdmin())
            ->post(route('admin.payment-settings.bank.store'), [
                'bank' => 'BRI',
                'number' => '555-1234',
                'holder' => 'Panitia Lari',
                'order' => 2,
                'is_active' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('bank_accounts', [
            'bank' => 'BRI',
            'number' => '555-1234',
            'holder' => 'Panitia Lari',
            'is_active' => true,
            'order' => 2,
        ]);
    }

    public function test_bank_number_must_be_numeric(): void
    {
        $this->actingAs($this->createAdmin())
            ->from(route('admin.payment-settings.index'))
            ->post(route('admin.payment-settings.bank.store'), [
                'bank' => 'BRI',
                'number' => 'abc<script>',
                'holder' => 'Panitia',
            ])
            ->assertSessionHasErrors('number');

        $this->assertDatabaseMissing('bank_accounts', ['bank' => 'BRI']);
    }

    public function test_admin_can_update_bank_account(): void
    {
        $account = BankAccount::create(['bank' => 'BCA', 'number' => '111', 'holder' => 'Lama', 'is_active' => true, 'order' => 1]);

        $this->actingAs($this->createAdmin())
            ->put(route('admin.payment-settings.bank.update', $account), [
                'bank' => 'BCA',
                'number' => '222',
                'holder' => 'Baru',
                'order' => 5,
            ])
            ->assertRedirect();

        $account->refresh();
        $this->assertSame('222', $account->number);
        $this->assertSame('Baru', $account->holder);
        $this->assertFalse($account->is_active);
    }

    public function test_admin_can_delete_bank_account(): void
    {
        $account = BankAccount::create(['bank' => 'BCA', 'number' => '111', 'holder' => 'Lama', 'is_active' => true, 'order' => 1]);

        $this->actingAs($this->createAdmin())
            ->delete(route('admin.payment-settings.bank.destroy', $account))
            ->assertRedirect();

        $this->assertDatabaseMissing('bank_accounts', ['id' => $account->id]);
    }

    public function test_admin_can_upload_qris_image(): void
    {
        $this->actingAs($this->createAdmin())
            ->post(route('admin.payment-settings.qris.update'), [
                'qris_image' => UploadedFile::fake()->create('qris.png', 20, 'image/png'),
            ])
            ->assertRedirect();

        $path = Setting::get('qris_image');
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
        $this->assertTrue(Setting::isCustomQris());
    }

    public function test_admin_can_reset_qris_image(): void
    {
        Storage::disk('public')->put('qris/old.png', 'x');
        Setting::put('qris_image', 'qris/old.png');

        $this->actingAs($this->createAdmin())
            ->delete(route('admin.payment-settings.qris.destroy'))
            ->assertRedirect();

        Storage::disk('public')->assertMissing('qris/old.png');
        $this->assertNull(Setting::get('qris_image'));
        $this->assertFalse(Setting::isCustomQris());
    }

    public function test_register_page_shows_active_bank_accounts(): void
    {
        $this->createOpenEvent();
        BankAccount::create(['bank' => 'BNI', 'number' => '777888', 'holder' => 'Panitia BNI', 'is_active' => true, 'order' => 1]);
        BankAccount::create(['bank' => 'NONAKTIF', 'number' => '999', 'holder' => 'Sembunyi', 'is_active' => false, 'order' => 2]);

        $this->get(route('register.create'))
            ->assertOk()
            ->assertSee('BNI')
            ->assertSee('777888')
            ->assertDontSee('NONAKTIF');
    }
}
