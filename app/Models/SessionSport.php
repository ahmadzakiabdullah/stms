<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SessionSport extends Model
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = ['organization_id', 'session_id', 'sport_id', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function session() { return $this->belongsTo(Session::class); }
    public function sport() { return $this->belongsTo(Sport::class); }
    public function categories() { return $this->hasMany(SessionSportCategory::class); }
}
