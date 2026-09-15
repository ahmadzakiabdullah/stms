<?php

namespace App\Exports;

use App\Models\Organization;
use App\Models\Result;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ResultExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    private Collection $results;
    private int $row = 0;

    public function __construct(Organization $organization, ?string $eventId = null)
    {
        $query = Result::where('organization_id', $organization->id)
            ->with(['match.event.tournament', 'match.homeParticipant', 'match.awayParticipant', 'winner']);

        if ($eventId) {
            $query->whereHas('match', fn ($q) => $q->where('event_id', $eventId));
        }

        $this->results = $query->orderByDesc('created_at')->get();
    }

    public function collection(): Collection
    {
        return $this->results;
    }

    public function headings(): array
    {
        return [
            '#',
            'Tournament',
            'Event',
            'Match No',
            'Home Team',
            'Score',
            'Away Team',
            'Winner',
        ];
    }

    public function map($result): array
    {
        $this->row++;

        return [
            $this->row,
            $result->match?->event?->tournament?->name ?? '-',
            $result->match?->event?->name ?? '-',
            $result->match?->match_number ?? '-',
            $result->match?->homeParticipant?->name ?? '-',
            ($result->score_home ?? 0) . ' - ' . ($result->score_away ?? 0),
            $result->match?->awayParticipant?->name ?? '-',
            $result->winner?->name ?? 'Draw',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
