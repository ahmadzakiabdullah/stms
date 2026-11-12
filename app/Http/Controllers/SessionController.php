<?php

namespace App\Http\Controllers;

use App\Actions\Sessions\CreateSession;
use App\Actions\Sessions\DeleteSession;
use App\Actions\Sessions\UpdateSession;
use App\Http\Requests\Session\StoreSessionRequest;
use App\Http\Requests\Session\UpdateSessionRequest;
use App\Models\Organization;
use App\Models\Session;
use App\Services\ParticipantLogoService;
use App\Services\PublicPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SessionController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Session::class);

        $user = Auth::user();

        // Defensive queries
        $sessions = $this->safePaginatedQuery(function () use ($request) {
            return Session::with('organization', 'documents')
                ->when($request->filled('search'), fn ($query) => $query->where(function ($q) use ($request) {
                    $search = trim($request->string('search')->toString());
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                }))
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

    public function store(StoreSessionRequest $request, CreateSession $action, ParticipantLogoService $logoService, PublicPortalService $publicPortal): RedirectResponse
    {
        Gate::authorize('create', Session::class);

        $data = $request->validated();
        $storedPaths = [];

        try {
            foreach (['logo' => 'logo_path', 'inverse_logo' => 'inverse_logo_path'] as $upload => $pathKey) {
                if ($request->hasFile($upload)) {
                    $data[$pathKey] = $logoService->store($request->file($upload), $upload);
                    $storedPaths[] = $data[$pathKey];
                }
            }
            unset($data['logo'], $data['inverse_logo']);
            $action->handle($data);
            $publicPortal->forget();
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($storedPaths);
            throw $exception;
        }

        return redirect()->route('sessions.index')
            ->with('success', 'Session created successfully.');
    }

    public function update(UpdateSessionRequest $request, Session $session, UpdateSession $action, ParticipantLogoService $logoService, PublicPortalService $publicPortal): RedirectResponse
    {
        Gate::authorize('update', $session);

        $data = $request->validated();
        $storedPaths = [];
        $pathsToDelete = [];

        try {
            foreach ([
                'logo' => ['path' => 'logo_path', 'remove' => 'remove_logo'],
                'inverse_logo' => ['path' => 'inverse_logo_path', 'remove' => 'remove_inverse_logo'],
            ] as $upload => $fields) {
                $currentPath = $session->getAttribute($fields['path']);

                if ($request->hasFile($upload)) {
                    $data[$fields['path']] = $logoService->store($request->file($upload), $upload);
                    $storedPaths[] = $data[$fields['path']];
                    if ($currentPath) {
                        $pathsToDelete[] = $currentPath;
                    }
                } elseif ($request->boolean($fields['remove'])) {
                    $data[$fields['path']] = null;
                    if ($currentPath) {
                        $pathsToDelete[] = $currentPath;
                    }
                }
            }

            unset($data['logo'], $data['inverse_logo'], $data['remove_logo'], $data['remove_inverse_logo']);
            if ($data !== []) {
                $action->handle($session, $data);
            }
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($storedPaths);
            throw $exception;
        }

        Storage::disk('public')->delete(array_values(array_unique($pathsToDelete)));
        $publicPortal->forget($session->id);

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
