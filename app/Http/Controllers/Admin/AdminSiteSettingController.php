<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminSiteSettingController extends Controller
{
    public function index(): View
    {
        return view('admin.site-settings.index', [
            'heroUrl' => Setting::getHeroUrl(),
            'heroIsCustom' => Setting::isCustomHero(),
        ]);
    }

    public function updateHeroImage(Request $request): RedirectResponse
    {
        $request->validate([
            'hero_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [], ['hero_image' => 'gambar halaman utama']);

        $old = Setting::get('home_hero_image');
        if ($old && Storage::disk('public')->exists($old)) {
            Storage::disk('public')->delete($old);
        }

        $path = $request->file('hero_image')->store('site', 'public');
        Setting::put('home_hero_image', $path);

        ActivityLog::record('setting.hero_updated', null, auth()->id(), 'Perbarui gambar halaman utama');

        return back()->with('success', 'Gambar halaman utama berhasil diperbarui.');
    }

    public function destroyHeroImage(): RedirectResponse
    {
        $old = Setting::get('home_hero_image');
        if ($old && Storage::disk('public')->exists($old)) {
            Storage::disk('public')->delete($old);
        }

        Setting::forget('home_hero_image');
        ActivityLog::record('setting.hero_deleted', null, auth()->id(), 'Hapus gambar halaman utama kustom');

        return back()->with('success', 'Gambar halaman utama dikembalikan ke gambar default.');
    }
}
