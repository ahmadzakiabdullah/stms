        $fixtures = Fixture::query()
            ->where('organization_id', $session->organization_id)
            ->where('event_id', $entry->event_id)
            ->where(function ($query) use ($participant) {
                $query->where('home_participant_id', $participant->id)->orWhere('away_participant_id', $participant->id);
            })
            ->with(['event:id,name,venues', 'result', 'homeParticipant:id,name', 'awayParticipant:id,name'])
            ->orderByDesc('scheduled_at')->orderByDesc('match_number')->get();

        $matches = $fixtures->map(function (Fixture $fixture) use ($participant) {
            $isHome = $fixture->home_participant_id === $participant->id;
            $result = $fixture->result;
            $outcome = null;
            if ($fixture->status === 'completed' && $result) {
                $outcome = $result->winner_participant_id === null
                    ? 'draw'
                    : ($result->winner_participant_id === $participant->id ? 'win' : 'loss');
            }

            return [
                'id' => $fixture->id,
                'event' => $fixture->event?->name,
                'opponent' => $isHome ? $fixture->awayParticipant?->name : $fixture->homeParticipant?->name,
                'score_for' => $isHome ? $result?->score_home : $result?->score_away,
                'score_against' => $isHome ? $result?->score_away : $result?->score_home,
                'scheduled_at' => $fixture->scheduled_at?->toIso8601String(),
                'venue' => $fixture->venue ?: ($fixture->event?->venues[0] ?? null),
                'status' => $fixture->status,
                'outcome' => $outcome,
            ];
        })->values();

        return [
            'athlete' => [
                'id' => $member->id,
                'name' => $member->name,
                'role' => $member->role,
                'faculty' => $participant->name,
                'logo_url' => $participant->logo_url,
                'inverse_logo_url' => $participant->inverse_logo_url,
                'sport' => $entry->event?->sport?->name,
                'category' => $entry->event?->sportCategory?->name,
                'event' => $entry->event?->name,
            ],
            'stats' => [
                'matches' => $matches->where('status', 'completed')->count(),
                'wins' => $matches->where('outcome', 'win')->count(),
                'draws' => $matches->where('outcome', 'draw')->count(),
                'losses' => $matches->where('outcome', 'loss')->count(),
            ],
            'matches' => $matches->all(),
