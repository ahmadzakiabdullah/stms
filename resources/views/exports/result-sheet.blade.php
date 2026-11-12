<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ app()->getLocale() === 'ms' ? 'Helaian Keputusan' : 'Result Sheet' }} - {{ $fixture->match_number ?? 'N/A' }}</title>
    <style nonce="{{ request()->attributes->get('csp_nonce') }}">
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        h1 { font-size: 16px; margin-bottom: 5px; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .label { font-weight: bold; }
        .result-box { border: 2px solid #333; padding: 15px; margin: 15px 0; text-align: center; }
        .teams { display: flex; justify-content: space-around; align-items: center; }
        .team { width: 40%; }
        .team-name { font-size: 14px; font-weight: bold; }
        .vs { font-size: 20px; font-weight: bold; color: #666; }
        .score-area { margin: 15px 0; text-align: center; }
        .score-box { display: inline-block; min-width: 60px; padding: 6px 10px; border: 2px solid #333; text-align: center; font-size: 18px; line-height: 32px; margin: 0 10px; font-weight: bold; }
        .section { margin-top: 20px; }
        .section-title { font-weight: bold; border-bottom: 1px solid #333; padding-bottom: 5px; margin-bottom: 10px; }
        .status-badge { display: inline-block; padding: 4px 12px; border: 1px solid #333; border-radius: 3px; font-weight: bold; text-transform: uppercase; font-size: 11px; margin-top: 8px; }
        .meta { margin-top: 12px; font-size: 11px; color: #555; }
        .notes-area { border: 1px solid #ccc; min-height: 60px; padding: 10px; margin-top: 10px; }
        .signatures { display: flex; justify-content: space-between; margin-top: 30px; }
        .signature-box { width: 45%; text-align: center; }
        .signature-line { border-top: 1px solid #333; margin-top: 40px; padding-top: 5px; }
        .footer { margin-top: 20px; font-size: 10px; color: #999; text-align: center; }
    </style>
</head>
<body>
    @php($isMalay = app()->getLocale() === 'ms')
    <h1>{{ $isMalay ? 'HELAIAN KEPUTUSAN RASMI' : 'OFFICIAL RESULT SHEET' }}</h1>

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
        <span><span class="label">Status:</span> {{ $result ? ucfirst($result->status) : ($isMalay ? 'Tiada keputusan direkodkan' : 'No result recorded') }}</span>
    </div>

    <div class="result-box">
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
        <div class="score-area">
            <span class="label">{{ $isMalay ? 'Skor Akhir:' : 'Final Score:' }}</span>
            <span class="score-box">{{ $result?->score_home ?? '-' }}</span>
            <span>-</span>
            <span class="score-box">{{ $result?->score_away ?? '-' }}</span>
        </div>
        @if ($result && $result->winner)
            <div><span class="label">{{ $isMalay ? 'Pemenang:' : 'Winner:' }}</span> {{ $result->winner->name }}</div>
        @endif
        @if (in_array($result?->status, ['approved', 'locked'], true))
            <div class="status-badge">{{ $isMalay ? 'Diluluskan' : 'Approved' }}</div>
        @endif
    </div>

    @if ($result)
        <div class="meta">
            <span class="label">{{ $isMalay ? 'Dihantar oleh:' : 'Submitted by:' }}</span> {{ $result->submittedBy?->name ?? '-' }} {{ $isMalay ? 'pada' : 'on' }} {{ optional($result->submitted_at)->format('d M Y H:i') ?? '-' }}<br>
            @if ($result->approved_at)
                <span class="label">{{ $isMalay ? 'Diluluskan oleh:' : 'Approved by:' }}</span> {{ $result->approvedBy?->name ?? '-' }} {{ $isMalay ? 'pada' : 'on' }} {{ $result->approved_at->format('d M Y H:i') }}
            @else
                <span>{{ $isMalay ? 'Belum diluluskan.' : 'Not yet approved.' }}</span>
            @endif
        </div>
    @endif

    <div class="section">
        <div class="section-title">{{ $isMalay ? 'Catatan Keputusan' : 'Result Notes' }}</div>
        <div class="notes-area">{{ $result?->notes ?? '' }}</div>
    </div>

    <div class="signatures">
        <div class="signature-box">
            <div class="signature-line">{{ $isMalay ? 'Pegawai Perlawanan' : 'Match Official' }}</div>
        </div>
        <div class="signature-box">
            <div class="signature-line">{{ $isMalay ? 'Penganjur Kejohanan' : 'Tournament Organizer' }}</div>
        </div>
    </div>

    <div class="footer">
        {{ $isMalay ? 'Dijana pada' : 'Generated on' }} {{ now()->format('d M Y H:i:s') }} • {{ config('app.name') }}
    </div>
</body>
</html>
