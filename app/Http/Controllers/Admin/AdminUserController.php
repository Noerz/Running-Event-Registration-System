<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminRequest;
use App\Http\Requests\UpdateAdminRequest;
use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use App\Services\RoleService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;

class AdminUserController extends Controller
{
    public function __construct(
        private readonly RoleService $roleService,
    ) {}

    public function index(): View
    {
        return view('admin.users.index', [
            'admins' => User::query()
                ->where('is_admin', true)
                ->with('roles')
                ->orderBy('name')
                ->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'roles' => $this->assignableRoles(),
        ]);
    }

    public function store(StoreAdminRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $roleId = (int) $data['role_id'];
        unset($data['role_id']);

        $admin = User::create([
            ...$data,
            'is_admin' => true,
        ]);

        $this->roleService->assignRoleToAdmin($admin->id, $roleId, $request->user()?->id);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Admin \"{$admin->name}\" berhasil ditambahkan.");
    }

    public function edit(User $adminUser): View
    {
        return view('admin.users.edit', [
            'admin' => $adminUser,
            'roles' => $this->assignableRoles(),
            'currentRoleId' => (int) $adminUser->roles()->value('roles.id'),
        ]);
    }

    public function update(UpdateAdminRequest $request, User $adminUser): RedirectResponse
    {
        $data = $request->validated();
        $roleId = (int) $data['role_id'];
        unset($data['role_id']);

        if (! $request->filled('password')) {
            unset($data['password']);
        }

        $isSelf = $adminUser->is($request->user());
        $currentRoleId = (int) $adminUser->roles()->value('roles.id');

        // Cegah admin mengubah role akunnya sendiri (privilege escalation).
        if ($isSelf && $roleId !== $currentRoleId) {
            return back()
                ->withInput()
                ->withErrors(['role_id' => 'Anda tidak dapat mengubah role akun Anda sendiri.']);
        }

        if ($currentRoleId !== $roleId && $this->isLastSuperAdmin($adminUser, $currentRoleId)) {
            return back()
                ->withInput()
                ->withErrors(['role_id' => 'Minimal harus ada satu Super Admin aktif.']);
        }

        $adminUser->update($data);

        if ($roleId !== $currentRoleId) {
            $this->roleService->assignRoleToAdmin($adminUser->id, $roleId, $request->user()?->id);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Admin \"{$adminUser->name}\" berhasil diperbarui.");
    }

    public function destroy(User $adminUser): RedirectResponse
    {
        if ($adminUser->is(auth()->user())) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if ($adminUser->isSuperAdmin() && $this->countSuperAdmins() <= 1) {
            return back()->with('error', 'Minimal harus ada satu Super Admin aktif.');
        }

        $name = $adminUser->name;
        $adminUser->delete();

        ActivityLog::record('admin_deleted', null, auth()->id(), "Hapus admin {$name}");

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Admin \"{$name}\" berhasil dihapus.");
    }

    /**
     * @return Collection<int, Role>
     */
    private function assignableRoles(): Collection
    {
        return Role::query()->active()->orderByDesc('is_system')->orderBy('name')->get();
    }

    private function isLastSuperAdmin(User $admin, int $currentRoleId): bool
    {
        $superRoleId = (int) Role::query()->where('code', Role::SUPER_ADMIN)->value('id');

        return $currentRoleId === $superRoleId && $this->countSuperAdmins() <= 1;
    }

    private function countSuperAdmins(): int
    {
        return User::query()
            ->where('is_admin', true)
            ->whereHas('roles', fn ($q) => $q->where('code', Role::SUPER_ADMIN)->where('is_active', true))
            ->count();
    }
}
