<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Role::class);

        $roles = Role::with('permissions')
            ->orderBy('name')
            ->get()
            ->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'permissions' => $role->permissions->pluck('name'),
                'created_at' => $role->created_at,
            ]);

        $permissions = Permission::orderBy('name')
            ->get()
            ->groupBy(fn ($p) => explode(' ', $p->name)[1] ?? 'other')
            ->map(fn ($group) => $group->pluck('name'));

        return Inertia::render('Roles/Index', [
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Role::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions'] ?? []);

        activity()
            ->performedOn($role)
            ->causedBy(Auth::user())
            ->event('created')
            ->withProperties(['permissions' => $validated['permissions'] ?? []])
            ->log("Role '{$role->name}' created");

        return redirect()->route('roles.index')
            ->with('success', "Role '{$role->name}' created.");
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        Gate::authorize('update', $role);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', "unique:roles,name,{$role->id}"],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->update(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions'] ?? []);

        activity()
            ->performedOn($role)
            ->causedBy(Auth::user())
            ->event('updated')
            ->withProperties(['permissions' => $validated['permissions'] ?? []])
            ->log("Role '{$role->name}' updated");

        return redirect()->route('roles.index')
            ->with('success', "Role '{$role->name}' updated.");
    }

    public function destroy(Role $role): RedirectResponse
    {
        Gate::authorize('delete', $role);

        if ($role->name === 'super-admin') {
            return redirect()->route('roles.index')
                ->with('error', 'Cannot delete super-admin role.');
        }

        $roleName = $role->name;
        $role->delete();

        activity()
            ->causedBy(Auth::user())
            ->event('deleted')
            ->log("Role '{$roleName}' deleted");

        return redirect()->route('roles.index')
            ->with('success', "Role '{$roleName}' deleted.");
    }
}
