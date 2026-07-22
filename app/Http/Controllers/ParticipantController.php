<?php

namespace App\Http\Controllers;

use App\Actions\Participants\CreateParticipant;
use App\Actions\Participants\DeleteParticipant;
use App\Actions\Participants\UpdateParticipant;
use App\Http\Requests\Participant\StoreParticipantRequest;
use App\Http\Requests\Participant\UpdateParticipantRequest;
use App\Models\Participant;
use App\Models\Session;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ParticipantController extends Controller
{
    public function index(): Response
    {
        $dataLoadFailed = false;

        $participants = $this->safePaginatedQuery(function () {
            return Participant::with(['organization', 'users.roles'])
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString();
        }, function () use (&$dataLoadFailed) {
            $dataLoadFailed = true;
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15, 1, [
                'path' => request()->url(),
            ]);
        });

        $response = Inertia::render('Participants/Index', [
            'participants' => $participants,
            'sessions' => Session::query()->orderBy('name')->get(['id', 'name', 'slug']),
        ]);

        if ($dataLoadFailed) {
            $response->with('error', 'Failed to load some data. Please run "php artisan migrate" on the server (database may be out of date).');
        }

        return $response;
    }

    public function store(StoreParticipantRequest $request, CreateParticipant $action): RedirectResponse
    {
        Gate::authorize('create', Participant::class);

        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('logos', 'public');
        }

        $action->handle($data);

        return redirect()->route('participants.index')
            ->with('success', 'Participant created successfully.');
    }

    public function update(UpdateParticipantRequest $request, Participant $participant, UpdateParticipant $action): RedirectResponse
    {
        Gate::authorize('update', $participant);

        $data = $request->validated();

        if ($request->hasFile('logo')) {
            if ($participant->logo_path) {
                Storage::disk('public')->delete($participant->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('logos', 'public');
        }

        $action->handle($participant, $data);

        return redirect()->route('participants.index')
            ->with('success', 'Participant updated successfully.');
    }

    public function destroy(Participant $participant, DeleteParticipant $action): RedirectResponse
    {
        Gate::authorize('delete', $participant);

        $action->handle($participant);

        return redirect()->route('participants.index')
            ->with('success', 'Participant deleted successfully.');
    }
}
