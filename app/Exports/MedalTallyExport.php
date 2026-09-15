<?php

namespace App\Exports;

use App\Models\Organization;
use App\Models\Session;
use App\Services\RankingService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MedalTallyExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    private Collection $rankings;

    private int $row = 0;

    public function __construct(Organization $organization, string $sessionId)
    {
        $session = Session::where('organization_id', $organization->id)
            ->findOrFail($sessionId);

        $service = app(RankingService::class);
        $this->rankings = $service->calculateMedalTallyForSession($session);
    }

    public function collection(): Collection
    {
        return $this->rankings;
    }

    public function headings(): array
    {
        return ['#', 'Participant', 'Type', 'Played', 'Gold', 'Silver', 'Bronze', 'Total Medals'];
    }

    public function map($row): array
    {
        $this->row++;

        return [
            $row['rank'],
            $row['participant_name'],
            $row['participant_type'],
            $row['matches_played'],
            $row['gold'],
            $row['silver'],
            $row['bronze'],
            $row['total_medals'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
