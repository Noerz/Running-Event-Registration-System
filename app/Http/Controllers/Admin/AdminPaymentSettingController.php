<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BankAccount;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminPaymentSettingController extends Controller
{
    public function index(): View
    {
        return view('admin.payment-settings.index', [
            'accounts' => BankAccount::query()->orderBy('order')->orderBy('id')->get(),
            'qrisUrl' => Setting::getQrisUrl(),
            'qrisIsCustom' => Setting::isCustomQris(),
            'qrisCustomPath' => Setting::get('qris_image'),
        ]);
    }

    public function storeBank(Request $request): RedirectResponse
    {
        $data = $this->validateBank($request);

        $account = BankAccount::create([
            'bank' => $data['bank'],
            'number' => $data['number'],
            'holder' => $data['holder'],
            'is_active' => $request->boolean('is_active', true),
            'order' => $data['order'] ?? ((int) BankAccount::query()->max('order') + 1),
        ]);

        ActivityLog::record('bank_account.created', $account, auth()->id(), "Tambah rekening {$account->bank} {$account->number}");

        return back()->with('success', "Rekening {$account->bank} berhasil ditambahkan.");
    }

    public function updateBank(Request $request, BankAccount $bankAccount): RedirectResponse
    {
        $data = $this->validateBank($request);

        $bankAccount->update([
            'bank' => $data['bank'],
            'number' => $data['number'],
            'holder' => $data['holder'],
            'is_active' => $request->boolean('is_active'),
            'order' => $data['order'] ?? $bankAccount->order,
        ]);

        ActivityLog::record('bank_account.updated', $bankAccount, auth()->id(), "Ubah rekening {$bankAccount->bank} {$bankAccount->number}");

        return back()->with('success', "Rekening {$bankAccount->bank} berhasil diperbarui.");
    }

    public function destroyBank(BankAccount $bankAccount): RedirectResponse
    {
        $label = "{$bankAccount->bank} {$bankAccount->number}";
        $bankAccount->delete();

        ActivityLog::record('bank_account.deleted', null, auth()->id(), "Hapus rekening {$label}");

        return back()->with('success', "Rekening {$label} berhasil dihapus.");
    }

    public function updateQris(Request $request): RedirectResponse
    {
        $request->validate([
            'qris_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [], ['qris_image' => 'gambar QRIS']);

        $old = Setting::get('qris_image');
        if ($old && Storage::disk('public')->exists($old)) {
            Storage::disk('public')->delete($old);
        }

        $path = $request->file('qris_image')->store('qris', 'public');
        Setting::put('qris_image', $path);

        ActivityLog::record('setting.qris_updated', null, auth()->id(), 'Perbarui gambar QRIS');

        return back()->with('success', 'Gambar QRIS berhasil diperbarui.');
    }

    public function destroyQris(): RedirectResponse
    {
        $old = Setting::get('qris_image');
        if ($old && Storage::disk('public')->exists($old)) {
            Storage::disk('public')->delete($old);
        }

        Setting::forget('qris_image');
        ActivityLog::record('setting.qris_deleted', null, auth()->id(), 'Hapus gambar QRIS kustom');

        return back()->with('success', 'Gambar QRIS kustom dihapus, memakai gambar default dari konfigurasi.');
    }

    /**
     * @return array{bank: string, number: string, holder: string, order?: int}
     */
    private function validateBank(Request $request): array
    {
        return $request->validate([
            'bank' => ['required', 'string', 'max:50'],
            'number' => ['required', 'string', 'max:50', 'regex:/^[0-9\-\s]+$/'],
            'holder' => ['required', 'string', 'max:255'],
            'order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', Rule::in(['0', '1', 0, 1, 'on'])],
        ], [
            'number.regex' => 'Nomor rekening hanya boleh berisi angka, spasi, dan tanda hubung.',
        ]);
    }
}
