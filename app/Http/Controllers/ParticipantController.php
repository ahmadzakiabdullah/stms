<?php

namespace App\Http\Controllers;

use App\Actions\Participants\CreateParticipant;
use App\Actions\Participants\DeleteParticipant;
use App\Actions\Participants\UpdateParticipant;
use App\Http\Requests\Participant\ParticipantImportRequest;
use App\Http\Requests\Participant\StoreParticipantRequest;
use App\Http\Requests\Participant\UpdateParticipantRequest;
use App\Imports\ParticipantsImport;
use App\Models\Participant;
use App\Models\Session;
use App\Services\ParticipantLogoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ParticipantController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Participant::class);

        $dataLoadFailed = false;

        $search = trim($request->string('search')->toString());

        $participants = $this->safePaginatedQuery(function () use ($search) {
            return Participant::with(['organization', 'users.roles'])
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('team_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
                })
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

        $importPreview = session('participant_import_preview');
        if ($importPreview) {
            $response->with('importPreview', $importPreview);
            session()->forget('participant_import_preview');
        }

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

    public function previewImport(ParticipantImportRequest $request): RedirectResponse
    {
        Gate::authorize('create', Participant::class);

        $user = $request->user();
        $import = new ParticipantsImport($user->organization_id, $request->validated('session_id'));

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            Log::error('Participant import preview failed', ['org_id' => $user->organization_id, 'error' => $e->getMessage()]);

            return redirect()->back()->with('error', 'Failed to parse the import file.');
        }

        $token = Str::uuid()->toString();
        $key = "participants_import_{$user->id}_{$token}";
        $rows = $import->rows();
        $errors = $import->errors();

        Cache::put($key, [
            'organization_id' => $user->organization_id,
            'session_id' => $request->validated('session_id'),
            'rows' => $rows,
        ], now()->addMinutes(30));

        return redirect()->back()->with('participant_import_preview', [
            'token' => $token,
            'valid_count' => count($rows),
            'error_count' => count($errors),
            'rows' => $rows,
            'errors' => $errors,
        ]);
    }

    public function confirmImport(Request $request): RedirectResponse
    {
        Gate::authorize('create', Participant::class);

        $user = $request->user();
        $token = trim((string) $request->input('token'));
        $key = "participants_import_{$user->id}_{$token}";
        $payload = Cache::get($key);

        if (! $payload) {
            return redirect()->back()->with('error', 'Import preview expired or not found. Please re-upload the file.');
        }

        try {
            $created = DB::transaction(function () use ($payload) {
                $created = 0;

                foreach ($payload['rows'] as $row) {
                    Participant::create([
                        'organization_id' => $payload['organization_id'],
                        ...$row['data'],
                    ]);
                    $created++;
                }

                return $created;
            });
        } catch (\Throwable $e) {
            Cache::forget($key);
            Log::error('Participant import failed and was rolled back', ['org_id' => $payload['organization_id'], 'error' => $e->getMessage()]);

            return redirect()->back()->with('error', 'Import failed. No records were created.');
        }

        Cache::forget($key);

        Log::info('Participants imported', ['org_id' => $payload['organization_id'], 'count' => $created]);

        return redirect()->back()->with('success', "{$created} participant(s) imported.");
    }

    public function downloadImportTemplate(): StreamedResponse
    {
        Gate::authorize('create', Participant::class);

        return response()->streamDownload(function () {
            echo "name,participant_type,team_name,email,phone,status,is_active,slug\n";
            echo "Fakulti Kejuruteraan Elektronik,team,FKE,fke@example.com,0123456789,registered,true,\n";
            echo "Ahmad bin Ali,individual,,ahmad@example.com,,registered,true,\n";
        }, 'participants-template.csv', ['Content-Type' => 'text/csv']);
    }
}
