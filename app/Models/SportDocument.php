<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SportDocument extends Model
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = ['organization_id','sport_id','session_id','title','file_path','file_name','mime_type','file_size','is_published','sort_order','created_by'];
    protected $casts = ['is_published' => 'boolean'];
    protected $appends = ['url'];
    public function sport() { return $this->belongsTo(Sport::class); }
    public function session() { return $this->belongsTo(Session::class); }
    public function getUrlAttribute(): string { return Storage::disk('public')->url($this->file_path); }
}
