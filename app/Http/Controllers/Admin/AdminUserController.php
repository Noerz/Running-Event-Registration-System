<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminRequest;
use App\Http\Requests\UpdateAdminRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AdminUserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'admins' => User::query()
                ->where('is_admin', true)
                ->orderBy('name')
                ->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(StoreAdminRequest $request): RedirectResponse
    {
        $admin = User::create([
            ...$request->validated(),
            'is_admin' => true,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Admin \"{$admin->name}\" berhasil ditambahkan.");
    }

    public function edit(User $adminUser): View
    {
        return view('admin.users.edit', ['admin' => $adminUser]);
    }

    public function update(UpdateAdminRequest $request, User $adminUser): RedirectResponse
    {
        $data = $request->validated();

        if (! $request->filled('password')) {
            unset($data['password']);
        }

        $adminUser->update($data);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Admin \"{$adminUser->name}\" berhasil diperbarui.");
    }

    public function destroy(User $adminUser): RedirectResponse
    {
        if ($adminUser->is(auth()->user())) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $name = $adminUser->name;
        $adminUser->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Admin \"{$name}\" berhasil dihapus.");
    }
}
