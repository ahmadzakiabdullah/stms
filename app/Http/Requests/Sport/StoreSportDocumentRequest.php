<?php
namespace App\Http\Requests\Sport;
use Illuminate\Foundation\Http\FormRequest;
class StoreSportDocumentRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('update', $this->route('sport')) ?? false; }
    public function rules(): array { return ['title' => ['required','string','max:255'], 'session_id' => ['required','uuid','exists:event_sessions,id'], 'document' => ['required','file','mimes:pdf,md,markdown','max:10240'], 'is_published' => ['sometimes','boolean'], 'sort_order' => ['sometimes','integer','min:0']]; }
}
