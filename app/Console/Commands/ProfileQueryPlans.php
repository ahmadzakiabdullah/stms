<?php

namespace App\Console\Commands;

use App\Models\Organization;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ProfileQueryPlans extends Command
{
    protected $signature = 'stms:query-profile
        {--organization= : Organization UUID or slug to scope representative tenant queries}
        {--json : Output machine-readable JSON}';

    protected $description = 'Collect non-destructive MySQL EXPLAIN plans for representative STMS query-budget paths';

    public function handle(): int
    {
        $driver = DB::connection()->getDriverName();
        $organization = $this->resolveOrganization();

        $result = [
            'status' => $driver === 'mysql' ? 'ok' : 'skipped',
            'driver' => $driver,
            'organization_id' => $organization?->id,
            'message' => $driver === 'mysql'
                ? 'Representative query plans collected. Review rows, type, possible_keys, key and Extra before adding indexes.'
                : 'Query-plan profiling is skipped because this connection is not MySQL.',
            'plans' => [],
        ];

        if ($driver === 'mysql' && $organization) {
            foreach ($this->queries($organization->id) as $name => $query) {
                $result['plans'][$name] = $this->explain($query);
            }
        } elseif ($driver === 'mysql') {
            $result['status'] = 'error';
            $result['message'] = 'No organization was found. Seed or select an organization before profiling tenant queries.';
        }

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->line($result['message']);
            $this->newLine();

            foreach ($result['plans'] as $name => $plan) {
                $this->line("<info>{$name}</info>");
                $this->table(array_keys($plan[0] ?? []), $plan);
                $this->newLine();
            }
        }

        return $result['status'] === 'error' ? self::FAILURE : self::SUCCESS;
    }

    private function resolveOrganization(): ?Organization
    {
        $value = $this->option('organization');

        return Organization::query()
            ->when($value, fn ($query) => $query->where(fn ($query) => $query
                ->where('id', $value)
                ->orWhere('slug', $value)))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->first();
    }

    /** @return array<string, Builder> */
    private function queries(string $organizationId): array
    {
        return [
            'public_schedule_upcoming_fixtures' => DB::table('matches')
                ->join('events', 'events.id', '=', 'matches.event_id')
                ->where('matches.organization_id', $organizationId)
                ->where('events.organization_id', $organizationId)
                ->whereNull('matches.deleted_at')
                ->whereNull('events.deleted_at')
                ->whereIn('matches.status', ['scheduled', 'pending', 'in_progress'])
                ->orderBy('matches.scheduled_at')
                ->limit(50),
            'results_index_recent_results' => DB::table('results')
                ->join('matches', 'matches.id', '=', 'results.match_id')
                ->join('events', 'events.id', '=', 'matches.event_id')
                ->where('results.organization_id', $organizationId)
                ->where('matches.organization_id', $organizationId)
                ->where('events.organization_id', $organizationId)
                ->whereNull('results.deleted_at')
                ->whereNull('matches.deleted_at')
                ->whereNull('events.deleted_at')
                ->orderByDesc('results.created_at')
                ->limit(25),
            'event_participants_index' => DB::table('event_participants')
                ->join('events', 'events.id', '=', 'event_participants.event_id')
                ->join('participants', 'participants.id', '=', 'event_participants.participant_id')
                ->where('event_participants.organization_id', $organizationId)
                ->where('events.organization_id', $organizationId)
                ->where('participants.organization_id', $organizationId)
                ->whereNull('event_participants.deleted_at')
                ->whereNull('events.deleted_at')
                ->whereNull('participants.deleted_at')
                ->orderByDesc('event_participants.created_at')
                ->limit(25),
            'reports_export_governance' => DB::table('data_transfers')
                ->where('organization_id', $organizationId)
                ->whereIn('type', ['export_fixtures', 'export_results', 'export_rankings', 'export_medals'])
                ->where('created_at', '>=', now()->subDays(30))
                ->orderByDesc('created_at')
                ->limit(25),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function explain(Builder $query): array
    {
        return collect(DB::select('EXPLAIN '.$query->toSql(), $query->getBindings()))
            ->map(fn (object $row): array => (array) $row)
            ->all();
    }
}
