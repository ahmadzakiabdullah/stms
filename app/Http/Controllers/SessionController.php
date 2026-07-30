<?php

namespace App\Http\Controllers;

use App\Actions\Sessions\CreateSession;
use App\Actions\Sessions\DeleteSession;
use App\Actions\Sessions\UpdateSession;
use App\Http\Requests\Session\StoreSessionRequest;
use App\Http\Requests\Session\UpdateSessionRequest;
use App\Models\Organization;
use App\Models\Session;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SessionController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();

        // Defensive queries
        $sessions = $this->safePaginatedQuery(function () {
            return Session::with('organization')
                ->orderBy('start_date', 'desc')
                ->paginate(15)
                ->withQueryString();
        });

        $organizations = $this->safeCollectionQuery(function () use ($user) {
            // Only super-admins need the full list of organizations for the create form
            return $user->hasRole('super-admin')
                ? Organization::orderBy('name')->get(['id', 'name'])
                : collect();
        });

        return Inertia::render('Sessions/Index', [
            'sessions' => $sessions,
            'organizations' => $organizations,
        ]);
    }

    public function store(StoreSessionRequest $request, CreateSession $action): RedirectResponse
    {
        Gate::authorize('create', Session::class);

        $action->handle($request->validated());

        return redirect()->route('sessions.index')
            ->with('success', 'Session created successfully.');
    }

    public function update(UpdateSessionRequest $request, Session $session, UpdateSession $action): RedirectResponse
    {
        Gate::authorize('update', $session);

        $action->handle($session, $request->validated());

        return redirect()->route('sessions.index')
            ->with('success', 'Session updated successfully.');
    }

    public function destroy(Session $session, DeleteSession $action): RedirectResponse
    {
        Gate::authorize('delete', $session);

        $action->handle($session);

        return redirect()->route('sessions.index')
            ->with('success', 'Session deleted successfully.');
    }
}
