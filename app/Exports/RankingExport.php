<?php

namespace App\Exports;

use App\Models\Organization;
use App\Models\Tournament;
use App\Services\RankingService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RankingExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    private Collection $rankings;
    private string $strategy;
    private int $row = 0;

    public function __construct(Organization $organization, string $tournamentId)
    {
        $tournament = Tournament::where('organization_id', $organization->id)
            ->with(['session'])
            ->findOrFail($tournamentId);

        $this->strategy = $tournament->ranking_strategy ?? 'points';

        $service = new RankingService();
        $this->rankings = $service->calculateForTournament($tournament);
    }

    public function collection(): Collection
    {
        return $this->rankings;
    }

    public function headings(): array
    {
        $headings = ['#', 'Participant', 'Type', 'Played', 'Wins', 'Draws', 'Losses'];

        if ($this->strategy === 'points') {
            array_push($headings, 'GF', 'GA', 'GD', 'Points');
        } elseif ($this->strategy === 'win_rate') {
            array_push($headings, 'Win Rate (%)');
        } else {
            array_push($headings, 'GF', 'GA', 'Points', 'Gold', 'Silver', 'Bronze');
        }

        return $headings;
    }

    public function map($row): array
    {
        $this->row++;

        $data = [
            $row['rank'],
            $row['participant_name'],
            $row['participant_type'],
            $row['matches_played'],
            $row['wins'],
            $row['draws'],
            $row['losses'],
        ];

        if ($this->strategy === 'points') {
            $data[] = $row['score_for'];
            $data[] = $row['score_against'];
            $data[] = $row['goal_difference'];
            $data[] = $row['points'];
        } elseif ($this->strategy === 'win_rate') {
            $data[] = $row['win_rate'];
        } else {
            $data[] = $row['score_for'];
            $data[] = $row['score_against'];
            $data[] = $row['points'];
            $data[] = $row['gold'];
            $data[] = $row['silver'];
            $data[] = $row['bronze'];
        }

        return $data;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
