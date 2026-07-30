<?php

namespace App\Services;

use App\Events\MatchScoreUpdated;
use App\Models\Organization;
use App\Models\Result;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResultService
{
    public function getAllByOrganization(Organization $organization, array $filters = []): LengthAwarePaginator
    {
        return $this->baseQuery($organization)
            ->with(['match.event', 'winner'])
            ->when($filters['event_id'] ?? null, fn ($q, $eventId) => $q->whereHas('match', fn ($m) => $m->where('event_id', $eventId)))
            ->orderByDesc('created_at')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function getAllByOrganizationForSelect(Organization $organization): Collection
    {
        return $this->baseQuery($organization)
            ->with(['match'])
            ->get(['id', 'match_id', 'score_home', 'score_away']);
    }

    public function getById(Organization $organization, string $id): Result
    {
        return $this->baseQuery($organization)
            ->with(['match.event', 'match.homeParticipant', 'match.awayParticipant', 'winner'])
            ->where('results.id', $id)
            ->firstOrFail();
    }

    public function getByMatchId(Organization $organization, string $matchId): ?Result
    {
        return $this->baseQuery($organization)
            ->with(['winner'])
            ->where('match_id', $matchId)
            ->first();
    }

    public function create(Organization $organization, array $data): Result
    {
        return DB::transaction(function () use ($organization, $data) {
            $data['organization_id'] = $organization->id;
            $result = Result::create($data);

            // Broadcast the real-time score update
            if ($result->match) {
                event(new MatchScoreUpdated($result->match));
            }

            Log::info('Result created', ['id' => $result->id, 'match_id' => $result->match_id, 'org_id' => $organization->id]);

            return $result;
        });
    }

    public function update(Organization $organization, string $id, array $data): Result
    {
        return DB::transaction(function () use ($organization, $id, $data) {
            $result = $this->getById($organization, $id);
            $result->update($data);

            // Broadcast the real-time score update
            if ($result->match) {
                event(new MatchScoreUpdated($result->match));
            }

            Log::info('Result updated', ['id' => $id, 'org_id' => $organization->id]);

            return $result->fresh();
        });
    }

    public function delete(Organization $organization, string $id): void
    {
        DB::transaction(function () use ($organization, $id) {
            $result = $this->getById($organization, $id);
            $result->delete();
            Log::info('Result deleted', ['id' => $id, 'org_id' => $organization->id]);
        });
    }

    public function countByOrganization(Organization $organization): int
    {
        return $this->baseQuery($organization)->count();
    }

    protected function baseQuery(Organization $organization)
    {
        return Result::where('organization_id', $organization->id);
    }
}
