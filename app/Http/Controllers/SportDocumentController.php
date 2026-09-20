<?php

namespace App\Http\Controllers;

use App\Http\Requests\Sport\StoreSportDocumentRequest;
use App\Models\Sport;
use App\Models\SportDocument;
use App\Services\DocumentStorageService;
use App\Services\PublicPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

class SportDocumentController extends Controller
{
    public function available(Sport $sport, Request $request, DocumentStorageService $documents): JsonResponse
    {
        Gate::authorize('view', $sport);
        $data = $request->validate([
            'session_id' => [
                'required',
                'uuid',
                Rule::exists('event_sessions', 'id')->where('organization_id', $sport->organization_id),
            ],
        ]);

        return response()->json(['files' => $documents->availableFiles($documents->sportDirectories($sport, $data['session_id']))]);
    }

    public function store(StoreSportDocumentRequest $request, Sport $sport, PublicPortalService $publicPortal, DocumentStorageService $documents): RedirectResponse
    {
        Gate::authorize('update', $sport);
        $file = $request->file('document');
        if (! $file || ! $file->isValid() || blank($file->getRealPath())) {
            return back()->withErrors(['document' => 'Upload gagal. Sila pilih fail PDF atau Markdown yang sah dan cuba lagi.']);
        }
        $path = $file->store($documents->sportDirectory($sport, $request->validated('session_id')), 'public');
        SportDocument::create([...$request->safe()->except('document'), 'organization_id' => $sport->organization_id, 'sport_id' => $sport->id, 'file_path' => $path, 'file_name' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType(), 'file_size' => $file->getSize(), 'created_by' => $request->user()->uuid]);
        $publicPortal->forget($request->session_id);

        return back()->with('success', 'Sport document uploaded successfully.');
    }

    public function destroy(SportDocument $sportDocument, PublicPortalService $publicPortal, DocumentStorageService $documents): RedirectResponse
    {
        Gate::authorize('update', $sportDocument->sport);
        if ($sportDocument->sport && $documents->isSelectable($sportDocument->file_path, $documents->sportDirectories($sportDocument->sport, $sportDocument->session_id))) {
            Storage::disk('public')->delete($sportDocument->file_path);
        }
        $sportDocument->delete();
        $publicPortal->forget($sportDocument->session_id);

        return back()->with('success', 'Sport document deleted successfully.');
    }

    public function select(Request $request, Sport $sport, DocumentStorageService $documents): RedirectResponse
    {
        Gate::authorize('update', $sport);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'session_id' => [
                'required',
                'uuid',
                Rule::exists('event_sessions', 'id')->where('organization_id', $sport->organization_id),
            ],
            'file_path' => ['required', 'string'],
        ]);
        abort_unless($documents->isSelectable($data['file_path'], $documents->sportDirectories($sport, $data['session_id'])), 422);
        $absolute = Storage::disk('public')->path($data['file_path']);
        $file = new \SplFileInfo($absolute);
        SportDocument::create(['organization_id' => $sport->organization_id, 'sport_id' => $sport->id, 'session_id' => $data['session_id'], 'title' => $data['title'], 'file_path' => $data['file_path'], 'file_name' => $file->getFilename(), 'mime_type' => File::mimeType($absolute), 'file_size' => $file->getSize(), 'is_published' => true, 'created_by' => $request->user()->uuid]);
        return back()->with('success', 'Sport document linked successfully.');
    }
}
