<?php

namespace App\Http\Controllers;

use App\Actions\Users\CreateUser;
use App\Actions\Users\DeleteUser;
use App\Actions\Users\UpdateUser;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();

        // Manual scoping kept (trait skips users table for auth safety)
        $users = $this->safePaginatedQuery(function () use ($user) {
            $query = User::with('roles', 'organization', 'participant');

            if (!$user->hasRole('super-admin')) {
                $query->where('organization_id', $user->organization_id);
            }

            return $query->orderBy('name')
                ->paginate(15)
                ->withQueryString();
        });

        $roles = $this->safeCollectionQuery(function () {
            return Role::orderBy('name')->get();
        });

        $organizations = $this->safeCollectionQuery(function () use ($user) {
            return $user->hasRole('super-admin')
                ? \App\Models\Organization::orderBy('name')->get()
                : collect([$user->organization]);
        });

        $participants = $this->safeCollectionQuery(function () use ($user) {
            return $user->hasRole('super-admin')
                ? Participant::orderBy('name')->get(['id', 'name'])
                : Participant::where('organization_id', $user->organization_id)->orderBy('name')->get(['id', 'name']);
        });

        return Inertia::render('Users/Index', [
            'users' => $users,
            'roles' => $roles,
            'organizations' => $organizations,
            'participants' => $participants,
        ]);
    }

    public function store(StoreUserRequest $request, CreateUser $action): RedirectResponse
    {
        Gate::authorize('create', User::class);

        $data = $request->validated();
        $currentUser = Auth::user();

        if (!$currentUser->hasRole('super-admin') && empty($data['organization_id'])) {
            $data['organization_id'] = $currentUser->organization_id;
        }

        $action->handle($data);

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUser $action): RedirectResponse
    {
        Gate::authorize('update', $user);

        $data = $request->validated();
        $currentUser = Auth::user();

        if (!$currentUser->hasRole('super-admin') && empty($data['organization_id'])) {
            $data['organization_id'] = $user->organization_id; // keep original if not super
        }

        $action->handle($user, $data);

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update(['password' => Hash::make($validated['password'])]);

        return redirect()->route('users.index')
            ->with('success', "Password reset for {$user->name}.");
    }

    public function destroy(User $user, DeleteUser $action): RedirectResponse
    {
        Gate::authorize('delete', $user);

        $action->handle($user);

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }
}
