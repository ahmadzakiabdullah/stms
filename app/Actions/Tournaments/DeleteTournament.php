<?php

namespace App\Actions\Tournaments;

use App\Models\Tournament;

class DeleteTournament
{
    public function handle(Tournament $tournament): void
    {
        $tournament->delete();
    }
}
