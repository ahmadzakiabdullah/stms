<?php

namespace App\Http\Controllers;

use App\Imports\SquadMembersImport;
use App\Models\EventParticipant;
use App\Models\SquadMember;
use App\Services\SquadQuotaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class FacultyDashboardController extends Controller
{
    public function storeSquad(Request $request, SquadQuotaService $quotaService): RedirectResponse
    {
        $validated = $request->validate([
            'event_participant_id' => ['required', 'uuid', 'exists:event_participants,id'],
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'in:athlete_male,athlete_female,assistant_manager,manager,coach,physio'],
            'matrix_no' => ['required', 'string', 'max:20'],
            'identification_no' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $ep = EventParticipant::with('event.sportCategory')->findOrFail($validated['event_participant_id']);

        if ($ep->participant_id !== Auth::user()->participant_id) {
            abort(403, 'Unauthorized action.');
        }

        if ($ep->status !== 'confirmed') {
            return redirect()->route('dashboard')
                ->with('error', 'Only confirmed registrations can add squad members.');
        }

        $isOfficial = in_array($validated['role'], ['assistant_manager', 'manager', 'coach', 'physio'], true);
        if ($isOfficial && blank($validated['phone'])) {
            return redirect()->route('dashboard')
                ->with('error', 'Officials must provide a phone number.');
        }

        if (! $isOfficial && ! $ep->squadMembers()->whereIn('role', ['assistant_manager', 'manager', 'coach', 'physio'])->exists()) {
            return redirect()->route('dashboard')
                ->with('error', 'Add officials before athletes.');
        }

        $quotaError = $quotaService->validateAddition($ep, $validated['role']);
        if ($quotaError) {
            return redirect()->route('dashboard')
                ->with('error', $quotaError);
        }

        SquadMember::create([
            'event_participant_id' => $ep->id,
            'organization_id' => $ep->event?->organization_id ?? Auth::user()->organization_id,
            'name' => $validated['name'],
            'matrix_no' => $validated['matrix_no'],
            'role' => $validated['role'],
            'identification_no' => $validated['identification_no'],
            'phone' => $validated['phone'],
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Squad member added.');
    }

    public function destroySquad(SquadMember $squadMember): RedirectResponse
    {
        $squadMember->load('eventParticipant');
        if ($squadMember->eventParticipant->participant_id !== Auth::user()->participant_id) {
            abort(403, 'Unauthorized action.');
        }

        $squadMember->delete();

        return redirect()->route('dashboard')
            ->with('success', 'Squad member removed.');
    }

    public function importSquad(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'event_participant_id' => ['required', 'uuid', 'exists:event_participants,id'],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:2048'],
        ]);

        $ep = EventParticipant::with('event.sportCategory')->findOrFail($validated['event_participant_id']);

        if ($ep->participant_id !== Auth::user()->participant_id) {
            abort(403, 'Unauthorized action.');
        }

        if ($ep->status !== 'confirmed') {
            return redirect()->route('dashboard')
                ->with('error', 'Only confirmed registrations can add squad members.');
        }

        try {
            Excel::import(
                new SquadMembersImport($ep, $ep->event?->organization_id ?? Auth::user()->organization_id),
                $request->file('file')
            );

            return redirect()->route('dashboard')
                ->with('success', 'Squad members imported successfully.');
        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors());
        } catch (\Throwable $e) {
            Log::error('Squad import failed', ['error' => $e->getMessage()]);

            return redirect()->route('dashboard')
                ->with('error', 'Failed to import file: '.$e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        $headers = ['name', 'role', 'matrix_no', 'ic_passport', 'phone'];
        $example = [
            ['Ahmad bin Jamal', 'manager', 'B062310003', '', '019-8765432'],
            ['Ali bin Ahmad', 'athlete_male', 'B062310001', '010203-10-1234', '012-3456789'],
            ['Siti binti Ali', 'athlete_female', 'B062310002', '020304-10-5678', '012-9876543'],
        ];

        return response()->streamDownload(function () use ($headers, $example) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($example as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 'squad-template.csv', ['Content-Type' => 'text/csv']);
    }
}
