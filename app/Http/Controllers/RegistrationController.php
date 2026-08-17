<?php

namespace App\Http\Controllers;

use App\Actions\Registrations\CreateRegistration;
use App\Actions\Registrations\DeleteRegistration;
use App\Actions\Registrations\UpdateRegistration;
use App\Http\Requests\Registration\StoreRegistrationRequest;
use App\Http\Requests\Registration\UpdateRegistrationRequest;
use App\Models\Participant;
use App\Models\Registration;
use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RegistrationController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Registration::class);

        $dataLoadFailed = false;

        $registrations = $this->safePaginatedQuery(function () {
            return Registration::with(['tournament', 'participant', 'organization'])
                ->orderBy('created_at', 'desc')
                ->paginate(15)
                ->withQueryString();
        }, function () use (&$dataLoadFailed) {
            $dataLoadFailed = true;

            return new LengthAwarePaginator([], 0, 15, 1, [
                'path' => request()->url(),
            ]);
        });

        $tournaments = $this->safeCollectionQuery(function () {
            return Tournament::query()
                ->orderBy('name')
                ->get(['id', 'name', 'slug']);
        }, function () use (&$dataLoadFailed) {
            $dataLoadFailed = true;

            return collect();
        });

        $participants = $this->safeCollectionQuery(function () {
            return Participant::query()
                ->orderBy('name')
                ->get(['id', 'name', 'slug']);
        }, function () use (&$dataLoadFailed) {
            $dataLoadFailed = true;

            return collect();
        });

        $response = Inertia::render('Registrations/Index', [
            'registrations' => $registrations,
            'tournaments' => $tournaments,
            'participants' => $participants,
        ]);

        if ($dataLoadFailed) {
            $response->with('error', 'Failed to load some data. Please run "php artisan migrate" on the server (database may be out of date).');
        }

        return $response;
    }

    public function store(StoreRegistrationRequest $request, CreateRegistration $action): RedirectResponse
    {
        Gate::authorize('create', Registration::class);

        $action->handle($request->validated());

        return redirect()->route('registrations.index')
            ->with('success', 'Registration created successfully.');
    }

    public function update(UpdateRegistrationRequest $request, Registration $registration, UpdateRegistration $action): RedirectResponse
    {
        Gate::authorize('update', $registration);

        $action->handle($registration, $request->validated());

        return redirect()->route('registrations.index')
            ->with('success', 'Registration updated successfully.');
    }

    public function destroy(Registration $registration, DeleteRegistration $action): RedirectResponse
    {
        Gate::authorize('delete', $registration);

        $action->handle($registration);

        return redirect()->route('registrations.index')
            ->with('success', 'Registration deleted successfully.');
    }
}
