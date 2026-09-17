<?php

namespace App\Http\Controllers;

use App\Http\Requests\Sport\StoreSportDocumentRequest;
use App\Models\Sport;
use App\Models\SportDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class SportDocumentController extends Controller
{
    public function store(StoreSportDocumentRequest $request, Sport $sport): RedirectResponse
    {
        Gate::authorize('update', $sport);
        $file = $request->file('document');
        if (! $file || ! $file->isValid() || blank($file->getRealPath())) {
            return back()->withErrors(['document' => 'Upload gagal. Sila pilih fail PDF atau Markdown yang sah dan cuba lagi.']);
        }
        $path = $file->store('documents/'.($sport->organization?->slug ?? $sport->organization_id).'/'.$request->session_id.'/sports/'.$sport->slug, 'public');
        SportDocument::create([...$request->safe()->except('document'), 'organization_id' => $sport->organization_id, 'sport_id' => $sport->id, 'file_path' => $path, 'file_name' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType(), 'file_size' => $file->getSize(), 'created_by' => $request->user()->uuid]);

        return back()->with('success', 'Sport document uploaded successfully.');
    }

    public function destroy(SportDocument $sportDocument): RedirectResponse
    {
        Gate::authorize('update', $sportDocument->sport);
        Storage::disk('public')->delete($sportDocument->file_path);
        $sportDocument->delete();

        return back()->with('success', 'Sport document deleted successfully.');
    }
}
