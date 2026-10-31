<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminRoleController extends Controller
{
    public function __construct(
        private readonly RoleService $roleService,
    ) {}

    public function index(): View
    {
        $roles = Role::query()
            ->withCount(['admins', 'permissions'])
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get();

        return view('admin.roles.index', [
            'roles' => $roles,
        ]);
    }

    public function create(): View
    {
        $permissions = Permission::query()->orderBy('module')->orderBy('id')->get()->groupBy('module');

        return view('admin.roles.create', [
            'role' => new Role(['is_active' => true]),
            'groupedPermissions' => $permissions,
            'moduleLabels' => Permission::MODULE_LABELS,
            'assignedPermissionCodes' => [],
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = $this->roleService->create(
            $request->validated(),
            $request->user()?->id,
        );

        return redirect()
            ->route('admin.roles.index')
            ->with('success', "Role \"{$role->name}\" berhasil ditambahkan.");
    }

    public function edit(Role $role): View
    {
        $permissions = Permission::query()->orderBy('module')->orderBy('id')->get()->groupBy('module');
        $assigned = $role->permissions()->pluck('code')->all();

        return view('admin.roles.edit', [
            'role' => $role,
            'groupedPermissions' => $permissions,
            'moduleLabels' => Permission::MODULE_LABELS,
            'assignedPermissionCodes' => $assigned,
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $role = $this->roleService->update(
            $role,
            $request->validated(),
            $request->user()?->id,
        );

        return redirect()
            ->route('admin.roles.index')
            ->with('success', "Role \"{$role->name}\" berhasil diperbarui.");
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        try {
            $this->roleService->delete($role, $request->user()?->id);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()
            ->route('admin.roles.index')
            ->with('success', "Role \"{$role->name}\" berhasil dihapus.");
    }

    public function toggle(Request $request, Role $role): RedirectResponse
    {
        try {
            $this->roleService->toggleActive($role, $request->user()?->id);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $status = $role->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()
            ->route('admin.roles.index')
            ->with('success', "Role \"{$role->name}\" berhasil {$status}.");
    }
}
