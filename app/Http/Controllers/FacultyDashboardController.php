<?php

namespace App\Http\Controllers;

use App\Imports\SquadMembersImport;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\SportCategory;
use App\Models\SquadMember;
use App\Services\SquadQuotaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FacultyDashboardController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();

        $participant = $user->participant;

        $registrations = collect();
        $totalMale = 0;
        $totalFemale = 0;
        $totalOfficials = 0;

        if ($participant) {
            $registrations = EventParticipant::with([
                'event.sport',
                'event.sportCategory',
                'event.tournament.session',
                'squadMembers',
            ])
                ->where('participant_id', $participant->id)
                ->orderBy('created_at', 'desc')
                ->get();

            foreach ($registrations as $reg) {
                $totalMale += $reg->squadMembers->where('role', 'athlete_male')->count();
                $totalFemale += $reg->squadMembers->where('role', 'athlete_female')->count();
                $totalOfficials += $reg->squadMembers->whereIn('role', ['assistant_manager', 'manager', 'coach', 'physio'])->count();
            }
        }

        $availableEvents = Event::with(['sport', 'sportCategory', 'tournament'])
            ->where('is_active', true)
            ->orderBy('start_date')
            ->get(['id', 'name', 'sport_id', 'sport_category_id', 'tournament_id', 'start_date']);

        return Inertia::render('Faculty/Dashboard', [
            'participant' => $participant,
            'registrations' => $registrations,
            'totals' => [
                'male' => $totalMale,
                'female' => $totalFemale,
                'officials' => $totalOfficials,
            ],
            'availableEvents' => $availableEvents,
            'sportCategories' => SportCategory::with('sport')
                ->orderBy('name')
                ->get()
                ->map(fn ($sc) => array_merge(
                    $sc->only([
                        'id',
                        'name',
                        'sport_id',
                        'quota_mode',
                        'max_athletes_total',
                        'max_male_athletes',
                        'max_female_athletes',
                        'min_male_athletes',
                        'min_female_athletes',
                        'max_officials',
                    ]),
                    ['allowed_roles' => $sc->allowedAthleteRoles()]
                )),
        ]);
    }

    public function storeSquad(Request $request, SquadQuotaService $quotaService): RedirectResponse
    {
        $validated = $request->validate([
            'event_participant_id' => ['required', 'uuid', 'exists:event_participants,id'],
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'in:athlete_male,athlete_female,assistant_manager,manager,coach,physio'],
            'identification_no' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $ep = EventParticipant::with('event.sportCategory')->findOrFail($validated['event_participant_id']);

        if ($ep->organization_id !== Auth::user()->organization_id || $ep->participant_id !== Auth::user()->participant_id) {
            abort(403, 'Unauthorized action.');
        }

        $quotaError = $quotaService->validateAddition($ep, $validated['role']);
        if ($quotaError) {
            return redirect()->route('faculty.dashboard')
                ->with('error', $quotaError);
        }

        SquadMember::create([
            'event_participant_id' => $ep->id,
            'organization_id' => $ep->event?->organization_id ?? Auth::user()->organization_id,
            'name' => $validated['name'],
            'role' => $validated['role'],
            'identification_no' => $validated['identification_no'],
            'phone' => $validated['phone'],
        ]);

        return redirect()->route('faculty.dashboard')
            ->with('success', 'Squad member added.');
    }

    public function destroySquad(SquadMember $squadMember): RedirectResponse
    {
        $squadMember->load('eventParticipant');
        if (
            $squadMember->organization_id !== Auth::user()->organization_id ||
            $squadMember->eventParticipant->participant_id !== Auth::user()->participant_id
        ) {
            abort(403, 'Unauthorized action.');
        }

        $squadMember->delete();

        return redirect()->route('faculty.dashboard')
            ->with('success', 'Squad member removed.');
    }

    public function importSquad(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'event_participant_id' => ['required', 'uuid', 'exists:event_participants,id'],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:2048'],
        ]);

        $ep = EventParticipant::with('event.sportCategory')->findOrFail($validated['event_participant_id']);

        if ($ep->organization_id !== Auth::user()->organization_id || $ep->participant_id !== Auth::user()->participant_id) {
            abort(403, 'Unauthorized action.');
        }

        if ($ep->status !== 'confirmed') {
            return redirect()->route('faculty.dashboard')
                ->with('error', 'Only confirmed registrations can add squad members.');
        }

        try {
            Excel::import(
                new SquadMembersImport($ep, $ep->event?->organization_id ?? Auth::user()->organization_id),
                $request->file('file')
            );

            return redirect()->route('faculty.dashboard')
                ->with('success', 'Squad members imported successfully.');
        } catch (ValidationException $e) {
            $errors = implode(' ', array_merge(...array_values($e->errors())));

            return redirect()->route('faculty.dashboard')
                ->with('error', $errors);
        } catch (\Throwable $e) {
            Log::error('Squad import failed', ['error' => $e->getMessage()]);

            return redirect()->route('faculty.dashboard')
                ->with('error', 'Failed to import file: '.$e->getMessage());
        }
    }

    public function downloadTemplate(): BinaryFileResponse
    {
        $headers = ['name', 'role', 'ic_passport', 'phone'];
        $example = [
            ['Ali bin Ahmad', 'athlete_male', '010203-10-1234', '012-3456789'],
            ['Siti binti Ali', 'athlete_female', '020304-10-5678', '012-9876543'],
            ['Ahmad bin Jamal', 'manager', '', '019-8765432'],
        ];

        $filename = 'squad-template.csv';
        $path = storage_path('app/temp/'.$filename);

        if (! is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $handle = fopen($path, 'w');
        fputcsv($handle, $headers);
        foreach ($example as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);

        return response()->download($path, $filename, ['Content-Type' => 'text/csv'])->deleteFileAfterSend(true);
    }
}
