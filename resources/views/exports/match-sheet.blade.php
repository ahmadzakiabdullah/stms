<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ app()->getLocale() === 'ms' ? 'Helaian Perlawanan' : 'Match Sheet' }} - {{ $fixture->match_number ?? 'N/A' }}</title>
    <style nonce="{{ request()->attributes->get('csp_nonce') }}">
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        h1 { font-size: 16px; margin-bottom: 5px; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .info-row-spaced { margin-top: 10px; }
        .label { font-weight: bold; }
        .match-box { border: 2px solid #333; padding: 15px; margin: 15px 0; text-align: center; }
        .teams { display: flex; justify-content: space-around; align-items: center; }
        .team { width: 40%; }
        .team-name { font-size: 14px; font-weight: bold; }
        .vs { font-size: 20px; font-weight: bold; color: #666; }
        .score-area { margin: 15px 0; text-align: center; }
        .score-box { display: inline-block; width: 60px; height: 40px; border: 2px solid #333; text-align: center; font-size: 18px; line-height: 40px; margin: 0 10px; }
        .section { margin-top: 20px; }
        .section-title { font-weight: bold; border-bottom: 1px solid #333; padding-bottom: 5px; margin-bottom: 10px; }
        .signatures { display: flex; justify-content: space-between; margin-top: 30px; }
        .signature-box { width: 45%; text-align: center; }
        .signature-line { border-top: 1px solid #333; margin-top: 40px; padding-top: 5px; }
        .notes-area { border: 1px solid #ccc; min-height: 80px; padding: 10px; margin-top: 10px; }
        .footer { margin-top: 20px; font-size: 10px; color: #999; text-align: center; }
    </style>
</head>
<body>
    @php($isMalay = app()->getLocale() === 'ms')
    <h1>{{ $isMalay ? 'HELAIAN PERLAWANAN' : 'MATCH SHEET' }}</h1>

    <div class="info-row">
        <span><span class="label">{{ $isMalay ? 'Kejohanan:' : 'Tournament:' }}</span> {{ $fixture->event?->tournament?->name ?? '-' }}</span>
        <span><span class="label">{{ $isMalay ? 'Acara:' : 'Event:' }}</span> {{ $fixture->event?->name ?? '-' }}</span>
    </div>
    <div class="info-row">
        <span><span class="label">{{ $isMalay ? 'Perlawanan #:' : 'Match #:' }}</span> {{ $fixture->match_number ?? '-' }}</span>
        <span><span class="label">{{ $isMalay ? 'Tarikh:' : 'Date:' }}</span> {{ $fixture->scheduled_at?->format('d M Y') ?? '-' }}</span>
        <span><span class="label">{{ $isMalay ? 'Masa:' : 'Time:' }}</span> {{ $fixture->scheduled_at?->format('H:i') ?? '-' }}</span>
    </div>
    <div class="info-row">
        <span><span class="label">{{ $isMalay ? 'Tempat:' : 'Venue:' }}</span> {{ $fixture->venue ?? '-' }}</span>
    </div>

    <div class="match-box">
        <div class="teams">
            <div class="team">
                <div class="team-name">{{ $fixture->homeParticipant?->name ?? 'TBD' }}</div>
                <div>({{ $isMalay ? 'Tuan Rumah' : 'Home' }})</div>
            </div>
            <div class="vs">{{ $isMalay ? 'Lwn.' : 'VS' }}</div>
            <div class="team">
                <div class="team-name">{{ $fixture->awayParticipant?->name ?? 'TBD' }}</div>
                <div>({{ $isMalay ? 'Pelawat' : 'Away' }})</div>
            </div>
        </div>
    </div>

    <div class="score-area">
        <span class="label">{{ $isMalay ? 'Skor Akhir:' : 'Final Score:' }}</span>
        <span class="score-box">{{ $result?->score_home ?? '' }}</span>
        <span>-</span>
        <span class="score-box">{{ $result?->score_away ?? '' }}</span>
    </div>

    <div class="section">
        <div class="section-title">{{ $isMalay ? 'Pegawai Perlawanan' : 'Match Officials' }}</div>
        <div class="info-row">
            <span><span class="label">{{ $isMalay ? 'Pengadil:' : 'Referee:' }}</span> _________________________</span>
            <span><span class="label">{{ $isMalay ? 'Pengadil Kedua:' : '2nd Referee:' }}</span> _________________________</span>
        </div>
        <div class="info-row info-row-spaced">
            <span><span class="label">{{ $isMalay ? 'Penjaga Masa:' : 'Timekeeper:' }}</span> _________________________</span>
            <span><span class="label">{{ $isMalay ? 'Pencatat Skor:' : 'Scorekeeper:' }}</span> _________________________</span>
        </div>
    </div>

    <div class="section">
        <div class="section-title">{{ $isMalay ? 'Catatan Perlawanan' : 'Match Notes' }}</div>
        <div class="notes-area">{{ $result?->notes ?? '' }}</div>
    </div>

    <div class="signatures">
        <div class="signature-box">
            <div class="signature-line">{{ $isMalay ? 'Wakil Pasukan Tuan Rumah' : 'Home Team Representative' }}</div>
        </div>
        <div class="signature-box">
            <div class="signature-line">{{ $isMalay ? 'Wakil Pasukan Pelawat' : 'Away Team Representative' }}</div>
        </div>
    </div>

    <div class="footer">
        {{ $isMalay ? 'Dijana pada' : 'Generated on' }} {{ now()->format('d M Y H:i:s') }} • {{ config('app.name') }}
    </div>
</body>
</html>
