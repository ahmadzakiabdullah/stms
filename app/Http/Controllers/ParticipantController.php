<?php

namespace App\Http\Controllers;

use App\Actions\Participants\CreateParticipant;
use App\Actions\Participants\DeleteParticipant;
use App\Actions\Participants\UpdateParticipant;
use App\Http\Requests\Participant\StoreParticipantRequest;
use App\Http\Requests\Participant\UpdateParticipantRequest;
use App\Models\Participant;
use App\Models\Session;
use App\Services\ParticipantLogoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ParticipantController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Participant::class);

        $dataLoadFailed = false;

        $participants = $this->safePaginatedQuery(function () {
            return Participant::with(['organization', 'users.roles'])
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString();
        }, function () use (&$dataLoadFailed) {
            $dataLoadFailed = true;

            return new LengthAwarePaginator([], 0, 15, 1, [
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

    public function store(StoreParticipantRequest $request, CreateParticipant $action, ParticipantLogoService $logoService): RedirectResponse
    {
        Gate::authorize('create', Participant::class);

        $data = $request->validated();
        $storedPaths = [];

        try {
            if ($request->hasFile('logo')) {
                $data['logo_path'] = $logoService->store($request->file('logo'));
                $storedPaths[] = $data['logo_path'];
            }

            if ($request->hasFile('inverse_logo')) {
                $data['inverse_logo_path'] = $logoService->store($request->file('inverse_logo'), 'inverse_logo');
                $storedPaths[] = $data['inverse_logo_path'];
            }

            unset($data['logo'], $data['inverse_logo']);
            $action->handle($data);
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($storedPaths);

            throw $exception;
        }

        return redirect()->route('participants.index')
            ->with('success', 'Participant created successfully.');
    }

    public function update(UpdateParticipantRequest $request, Participant $participant, UpdateParticipant $action, ParticipantLogoService $logoService): RedirectResponse
    {
        Gate::authorize('update', $participant);

        $data = $request->validated();
        $storedPaths = [];
        $pathsToDelete = [];

        try {
            foreach ([
                'logo' => ['path' => 'logo_path', 'remove' => 'remove_logo'],
                'inverse_logo' => ['path' => 'inverse_logo_path', 'remove' => 'remove_inverse_logo'],
            ] as $uploadField => $fields) {
                $currentPath = $participant->getAttribute($fields['path']);

                if ($request->hasFile($uploadField)) {
                    $data[$fields['path']] = $logoService->store($request->file($uploadField), $uploadField);
                    $storedPaths[] = $data[$fields['path']];

                    if ($currentPath) {
                        $pathsToDelete[] = $currentPath;
                    }
                } elseif ($request->boolean($fields['remove']) && $currentPath) {
                    $data[$fields['path']] = null;
                    $pathsToDelete[] = $currentPath;
                }
            }

            unset($data['logo'], $data['inverse_logo'], $data['remove_logo'], $data['remove_inverse_logo']);
            $action->handle($participant, $data);
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($storedPaths);

            throw $exception;
        }

        Storage::disk('public')->delete(array_values(array_unique($pathsToDelete)));

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
