<?php
namespace App\Http\Controllers;
use App\Models\Session;
use App\Models\SportDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;
class SessionDocumentController extends Controller
{
    public function available(Session $session): JsonResponse
    {
        Gate::authorize('view', $session);
        $year = $session->start_date?->format('Y') ?? now()->format('Y');
        $root = storage_path('app/public/documents/'.$year.'/general');
        $files = File::isDirectory($root) ? collect(File::files($root))->filter(fn ($file) => in_array(strtolower($file->getExtension()), ['pdf', 'md', 'markdown'], true))->map(fn ($file) => ['name' => $file->getFilename(), 'path' => 'documents/'.$year.'/general/'.$file->getFilename()])->values() : collect();
        return response()->json(['files' => $files]);
    }

    public function store(Request $request, Session $session): RedirectResponse
    {
        Gate::authorize('update', $session);
        $data = $request->validate(['title'=>['required','string','max:255'],'document'=>['required','file','mimes:pdf,md,markdown','max:10240'],'is_published'=>['sometimes','boolean']]);
        $file = $request->file('document');
        if (! $file || ! $file->isValid() || blank($file->getRealPath())) {
            return back()->withErrors(['document' => 'Upload gagal. Sila pilih fail PDF atau Markdown yang sah dan cuba lagi.']);
        }
        $path = $file->store('documents/'.($session->organization?->slug ?? $session->organization_id).'/'.$session->id.'/general', 'public');
        SportDocument::create(['organization_id'=>$session->organization_id,'session_id'=>$session->id,'title'=>$data['title'],'file_path'=>$path,'file_name'=>$file->getClientOriginalName(),'mime_type'=>$file->getMimeType(),'file_size'=>$file->getSize(),'is_published'=>$request->boolean('is_published', true),'created_by'=>$request->user()->uuid]);
        return back()->with('success', 'Session document uploaded successfully.');
    }

    public function select(Request $request, Session $session): RedirectResponse
    {
        Gate::authorize('update', $session);
        $data = $request->validate(['title'=>['required','string','max:255'],'file_path'=>['required','string']]);
        $year = $session->start_date?->format('Y') ?? now()->format('Y');
        $prefix = 'documents/'.$year.'/general/';

        // Prevent path traversal attacks
        abort_if(str_contains($data['file_path'], '..'), 422);

        abort_unless(str_starts_with($data['file_path'], $prefix) && Storage::disk('public')->exists($data['file_path']), 422);
        $absolute = Storage::disk('public')->path($data['file_path']);
        $file = new \SplFileInfo($absolute);
        SportDocument::create(['organization_id'=>$session->organization_id,'session_id'=>$session->id,'title'=>$data['title'],'file_path'=>$data['file_path'],'file_name'=>$file->getFilename(),'mime_type'=>File::mimeType($absolute),'file_size'=>$file->getSize(),'is_published'=>true,'created_by'=>$request->user()->uuid]);
        return back()->with('success', 'Session document linked successfully.');
    }
    public function destroy(SportDocument $sportDocument): RedirectResponse
    {
        abort_unless($sportDocument->sport_id === null, 404);
        Gate::authorize('update', $sportDocument->session);
        Storage::disk('public')->delete($sportDocument->file_path); $sportDocument->delete();
        return back()->with('success', 'Session document deleted successfully.');
    }
}
