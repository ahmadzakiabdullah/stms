<?php

namespace Database\Seeders;

use App\Models\Participant;
use App\Models\Session;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/** Restores only the eight SAF 2026 faculty participants. Does not create registrations, matches or results. */
class RestoreSAF2026ParticipantsSeeder extends Seeder
{
    public function run(): void
    {
        $session = Session::withTrashed()
            ->where(function ($query) {
                $query->whereIn('slug', ['saf-2026', 'saf-20-2026'])
                    ->orWhere('name', 'SAF 2026')
                    ->orWhere('name', 'Sukan Antara Fakulti 2026');
            })
            ->latest('start_date')
            ->firstOrFail();
        if ($session->trashed()) {
            $session->restore();
        }
        $faculties = [
            ['FKEKK', 'Fakulti Kejuruteraan Elektronik dan Kejuruteraan Komputer'],
            ['FKM', 'Fakulti Kejuruteraan Mekanikal'],
            ['FKEE', 'Fakulti Kejuruteraan Elektrik'],
            ['FKP', 'Fakulti Kejuruteraan Pembuatan'],
            ['FTMK', 'Fakulti Teknologi Maklumat dan Komunikasi'],
            ['FPTT', 'Fakulti Pengurusan Teknologi dan Teknousahawanan'],
            ['FTKEE', 'Fakulti Teknologi Kejuruteraan Elektrik dan Elektronik'],
            ['PBPI', 'Pusat Bahasa dan Pengajian Islam'],
        ];

        foreach ($faculties as [$code, $fullName]) {
            Participant::withTrashed()->updateOrCreate(
                ['organization_id' => $session->organization_id, 'slug' => Str::slug($code)],
                ['session_id' => $session->id, 'name' => $code, 'slug' => Str::slug($code), 'participant_type' => 'team', 'team_name' => $fullName, 'status' => 'confirmed', 'is_active' => true, 'deleted_at' => null],
            );
        }
    }
}
