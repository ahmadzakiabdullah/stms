<?php

namespace App\Exports;

use App\Models\Fixture;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FixtureExport implements FromQuery, ShouldAutoSize, WithCustomChunkSize, WithHeadings, WithMapping, WithStyles
{
    private Builder $fixtures;

    private int $row = 0;

    public function __construct(Organization $organization, ?string $eventId = null)
    {
        $query = Fixture::where('organization_id', $organization->id)
            ->with(['event.tournament.session', 'homeParticipant', 'awayParticipant']);

        if ($eventId) {
            $query->where('event_id', $eventId);
        }

        $this->fixtures = $query->orderBy('scheduled_at');
    }

    public function query(): Builder
    {
        return $this->fixtures;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function headings(): array
    {
        return [
            '#',
            'Tournament',
            'Session',
            'Event',
            'Match No',
            'Home Team',
            'Away Team',
            'Venue',
            'Scheduled At',
            'Status',
        ];
    }

    public function map($fixture): array
    {
        $this->row++;

        return [
            $this->row,
            $fixture->event?->tournament?->name ?? '-',
            $fixture->event?->tournament?->session?->name ?? '-',
            $fixture->event?->name ?? '-',
            $fixture->match_number ?? '-',
            $fixture->homeParticipant?->name ?? 'TBD',
            $fixture->awayParticipant?->name ?? 'TBD',
            $fixture->venue ?? '-',
            $fixture->scheduled_at?->format('d M Y H:i') ?? '-',
            $fixture->status ?? 'pending',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
