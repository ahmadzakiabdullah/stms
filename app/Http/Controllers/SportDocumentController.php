<?php

namespace App\Http\Controllers;

use App\Http\Requests\Sport\StoreSportDocumentRequest;
use App\Models\Sport;
use App\Models\SportDocument;
use App\Services\PublicPortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class SportDocumentController extends Controller
{
    public function available(Sport $sport, Request $request): JsonResponse
    {
        Gate::authorize('view', $sport);
        $year = $request->query('year', now()->format('Y'));
        abort_unless(preg_match('/^\d{4}$/', $year) === 1, 422);
        $root = storage_path('app/public/documents/'.$year.'/sports');
        $files = File::isDirectory($root) ? collect(File::files($root))->filter(fn ($file) => in_array(strtolower($file->getExtension()), ['pdf', 'md', 'markdown'], true))->map(fn ($file) => ['name' => $file->getFilename(), 'path' => 'documents/'.$year.'/sports/'.$file->getFilename()])->values() : collect();

        return response()->json(['files' => $files]);
    }

    public function store(StoreSportDocumentRequest $request, Sport $sport, PublicPortalService $publicPortal): RedirectResponse
    {
        Gate::authorize('update', $sport);
        $file = $request->file('document');
        if (! $file || ! $file->isValid() || blank($file->getRealPath())) {
            return back()->withErrors(['document' => 'Upload gagal. Sila pilih fail PDF atau Markdown yang sah dan cuba lagi.']);
        }
        $path = $file->store('documents/'.($sport->organization?->slug ?? $sport->organization_id).'/'.$request->session_id.'/sports/'.$sport->slug, 'public');
        SportDocument::create([...$request->safe()->except('document'), 'organization_id' => $sport->organization_id, 'sport_id' => $sport->id, 'file_path' => $path, 'file_name' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType(), 'file_size' => $file->getSize(), 'created_by' => $request->user()->uuid]);
        $publicPortal->forget($request->session_id);

        return back()->with('success', 'Sport document uploaded successfully.');
    }

    public function destroy(SportDocument $sportDocument, PublicPortalService $publicPortal): RedirectResponse
    {
        Gate::authorize('update', $sportDocument->sport);
        if (preg_match('#^documents/\d{4}/sports/[^/]+$#', $sportDocument->file_path) !== 1) {
            Storage::disk('public')->delete($sportDocument->file_path);
        }
        $sportDocument->delete();
        $publicPortal->forget($sportDocument->session_id);

        return back()->with('success', 'Sport document deleted successfully.');
    }

    public function select(Request $request, Sport $sport): RedirectResponse
    {
        Gate::authorize('update', $sport);
        $data = $request->validate(['title' => ['required', 'string', 'max:255'], 'session_id' => ['required', 'uuid', 'exists:event_sessions,id'], 'file_path' => ['required', 'string'], 'year' => ['required', 'regex:/^\d{4}$/']]);
        abort_if(str_contains($data['file_path'], '..'), 422);
        $prefix = 'documents/'.$data['year'].'/sports/';
        abort_unless(str_starts_with($data['file_path'], $prefix) && Storage::disk('public')->exists($data['file_path']), 422);
        $absolute = Storage::disk('public')->path($data['file_path']);
        $file = new \SplFileInfo($absolute);
        SportDocument::create(['organization_id' => $sport->organization_id, 'sport_id' => $sport->id, 'session_id' => $data['session_id'], 'title' => $data['title'], 'file_path' => $data['file_path'], 'file_name' => $file->getFilename(), 'mime_type' => File::mimeType($absolute), 'file_size' => $file->getSize(), 'is_published' => true, 'created_by' => $request->user()->uuid]);

        return back()->with('success', 'Sport document linked successfully.');
    }
}
