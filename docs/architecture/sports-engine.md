# Sports Engine

“Sports engine” semasa ialah model domain generik, bukannya plugin engine. `Sport` ialah katalog tenant; `SportCategory` menyimpan kategori/quota; `Tournament` memilih sports melalui `tournament_sport`; `Event` mengikat tournament, sport dan category sebelum participant, draw, fixture dan result.

Sistem tidak hardcode nama sukan tertentu dalam core workflow. Ranking MVP kini data-driven:

- ranking hanya menyediakan `points`, `win_rate` dan `medal_tally`;
- strategy menggunakan contract + registry;
- win/draw/loss points dan ordered tiebreakers boleh disimpan pada session/tournament;
- format fixture/draw bergantung pada service yang tersedia, bukan ruleset plugin lengkap.

Jangan mendakwa sokongan semua jenis sukan atau ruleset plugin lengkap. Extension baharu mesti menyimpan rule/version secara data-driven, tenant-scoped, diuji dan tidak menambah conditional nama sukan dalam controller.

## Individual scoring events

Sport boleh menetapkan `scoring_mode=individual` untuk mengaktifkan `match_scoring_events`. Setiap event jaringan memautkan result, pasukan dan `SquadMember` atlet yang aktif serta disahkan dalam roster match. Service result memvalidasi jumlah event terhadap score home/away dan controller tidak mengandungi senarai sukan hardcoded. Paparan awam hanya menunjukkan event scorer apabila data rasmi direkodkan.
