<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Match Sheet - {{ $fixture->match_number ?? 'N/A' }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        h1 { font-size: 16px; margin-bottom: 5px; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 5px; }
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
    <h1>MATCH SHEET</h1>

    <div class="info-row">
        <span><span class="label">Tournament:</span> {{ $fixture->event?->tournament?->name ?? '-' }}</span>
        <span><span class="label">Event:</span> {{ $fixture->event?->name ?? '-' }}</span>
    </div>
    <div class="info-row">
        <span><span class="label">Match #:</span> {{ $fixture->match_number ?? '-' }}</span>
        <span><span class="label">Date:</span> {{ $fixture->scheduled_at?->format('d M Y') ?? '-' }}</span>
        <span><span class="label">Time:</span> {{ $fixture->scheduled_at?->format('H:i') ?? '-' }}</span>
    </div>
    <div class="info-row">
        <span><span class="label">Venue:</span> {{ $fixture->venue ?? '-' }}</span>
    </div>

    <div class="match-box">
        <div class="teams">
            <div class="team">
                <div class="team-name">{{ $fixture->homeParticipant?->name ?? 'TBD' }}</div>
                <div>(Home)</div>
            </div>
            <div class="vs">VS</div>
            <div class="team">
                <div class="team-name">{{ $fixture->awayParticipant?->name ?? 'TBD' }}</div>
                <div>(Away)</div>
            </div>
        </div>
    </div>

    <div class="score-area">
        <span class="label">Final Score:</span>
        <span class="score-box">{{ $result?->score_home ?? '' }}</span>
        <span>-</span>
        <span class="score-box">{{ $result?->score_away ?? '' }}</span>
    </div>

    <div class="section">
        <div class="section-title">Match Officials</div>
        <div class="info-row">
            <span><span class="label">Referee:</span> _________________________</span>
            <span><span class="label">2nd Referee:</span> _________________________</span>
        </div>
        <div class="info-row" style="margin-top: 10px;">
            <span><span class="label">Timekeeper:</span> _________________________</span>
            <span><span class="label">Scorekeeper:</span> _________________________</span>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Match Notes</div>
        <div class="notes-area">{{ $result?->notes ?? '' }}</div>
    </div>

    <div class="signatures">
        <div class="signature-box">
            <div class="signature-line">Home Team Representative</div>
        </div>
        <div class="signature-box">
            <div class="signature-line">Away Team Representative</div>
        </div>
    </div>

    <div class="footer">
        Generated on {{ now()->format('d M Y H:i:s') }} • {{ config('app.name') }}
    </div>
</body>
</html>
