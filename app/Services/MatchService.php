<?php

namespace App\Services;

use App\Models\Fixture;
use App\Models\Organization;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MatchService
{
    public function getAllByOrganization(Organization $organization, array $filters = []): LengthAwarePaginator
    {
        return $this->baseQuery($organization)
            ->when($filters['event_id'] ?? null, fn ($q, $eventId) => $q->where('event_id', $eventId))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where('venue', 'like', "%{$search}%"))
            ->orderByDesc('scheduled_at')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function getAllByOrganizationForSelect(Organization $organization): Collection
    {
        return $this->baseQuery($organization)
            ->orderBy('match_number')
            ->get(['id', 'match_number', 'status', 'scheduled_at', 'event_id']);
    }

    public function getById(Organization $organization, string $id): Fixture
    {
        return $this->baseQuery($organization)
            ->with(['event', 'homeParticipant', 'awayParticipant', 'result'])
            ->where('matches.id', $id)
            ->firstOrFail();
    }

    public function create(Organization $organization, array $data): Fixture
    {
        return DB::transaction(function () use ($organization, $data) {
            $data['organization_id'] = $organization->id;
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['match_number'] ?? Str::random(8));
            }
            if (empty($data['status'])) {
                $data['status'] = 'scheduled';
            }
            $match = Fixture::create($data);
            Log::info('Match created', ['id' => $match->id, 'org_id' => $organization->id]);
            return $match;
        });
    }

    public function update(Organization $organization, string $id, array $data): Fixture
    {
        return DB::transaction(function () use ($organization, $id, $data) {
            $match = $this->getById($organization, $id);
            if (isset($data['slug']) && empty($data['slug'])) {
                $data['slug'] = Str::slug($data['match_number'] ?? Str::random(8));
            }
            $match->update($data);
            Log::info('Match updated', ['id' => $id, 'org_id' => $organization->id]);
            return $match->fresh();
        });
    }

    public function delete(Organization $organization, string $id): void
    {
        DB::transaction(function () use ($organization, $id) {
            $match = $this->getById($organization, $id);
            $match->delete();
            Log::info('Match deleted', ['id' => $id, 'org_id' => $organization->id]);
        });
    }

    public function countByOrganization(Organization $organization): int
    {
        return $this->baseQuery($organization)->count();
    }

    public function getStatusCounts(Organization $organization): array
    {
        return $this->baseQuery($organization)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    protected function baseQuery(Organization $organization)
    {
        return Fixture::where('organization_id', $organization->id);
    }
}
