<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardMetricSnapshot extends Model
{
    protected $fillable = ['snapshot_date', 'scope_key', 'organizations', 'users', 'events', 'matches'];

    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'organizations' => 'integer',
            'users' => 'integer',
            'events' => 'integer',
            'matches' => 'integer',
        ];
    }
}
