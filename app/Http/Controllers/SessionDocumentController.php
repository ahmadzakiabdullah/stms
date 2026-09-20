<?php

namespace App\Http\Controllers;

use App\Models\Session;
use App\Models\SportDocument;
use App\Services\DocumentStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class SessionDocumentController extends Controller
{
    public function available(Session $session, DocumentStorageService $documents): JsonResponse
    {
        Gate::authorize('view', $session);

        return response()->json([
            'files' => $documents->availableFiles($documents->sessionDirectories($session)),
        ]);
    }

    public function store(Request $request, Session $session, DocumentStorageService $documents): RedirectResponse
    {
        Gate::authorize('update', $session);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'document' => ['required', 'file', 'mimes:pdf,md,markdown', 'max:10240'],
            'is_published' => ['sometimes', 'boolean'],
        ]);
        $file = $request->file('document');

        if (! $file || ! $file->isValid() || blank($file->getRealPath())) {
            return back()->withErrors(['document' => 'Upload gagal. Sila pilih fail PDF atau Markdown yang sah dan cuba lagi.']);
        }

        $path = $file->store($documents->sessionDirectory($session), 'public');
        SportDocument::create([
            'organization_id' => $session->organization_id,
            'session_id' => $session->id,
            'title' => $data['title'],
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'is_published' => $request->boolean('is_published', true),
            'created_by' => $request->user()->uuid,
        ]);

        return back()->with('success', 'Session document uploaded successfully.');
    }

    public function select(Request $request, Session $session, DocumentStorageService $documents): RedirectResponse
    {
        Gate::authorize('update', $session);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'file_path' => ['required', 'string'],
        ]);
        abort_unless($documents->isSelectable($data['file_path'], $documents->sessionDirectories($session)), 422);

        $absolute = Storage::disk('public')->path($data['file_path']);
        $file = new \SplFileInfo($absolute);
        SportDocument::create([
            'organization_id' => $session->organization_id,
            'session_id' => $session->id,
            'title' => $data['title'],
            'file_path' => $data['file_path'],
            'file_name' => $file->getFilename(),
            'mime_type' => File::mimeType($absolute),
            'file_size' => $file->getSize(),
            'is_published' => true,
            'created_by' => $request->user()->uuid,
        ]);

        return back()->with('success', 'Session document linked successfully.');
    }

    public function destroy(SportDocument $sportDocument, DocumentStorageService $documents): RedirectResponse
    {
        abort_unless($sportDocument->sport_id === null, 404);
        Gate::authorize('update', $sportDocument->session);

        if ($sportDocument->session && $documents->isSelectable($sportDocument->file_path, $documents->sessionDirectories($sportDocument->session))) {
            Storage::disk('public')->delete($sportDocument->file_path);
        }
        $sportDocument->delete();

        return back()->with('success', 'Session document deleted successfully.');
    }
}
