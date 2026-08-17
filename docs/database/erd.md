# Entity Relationships

## Rantaian Operasi

```text
Organization
├─ EventSession ─ Tournament ─ Event ─ Match/Fixture ─ Result
├─ Sport ─ SportCategory ───────────────┘
├─ Participant ─ EventParticipant ─ SquadMember
│              └─ Pool / draw allocation
├─ User ─ roles/permissions and sport_user
└─ Setting
```

Rantaian ini ialah gambaran operasi, bukan satu siri FK tunggal. Hubungan penting ialah:

- `event_sessions.organization_id` → `organizations.id`;
- `tournaments.session_id` dan `tournaments.organization_id`;
- `tournament_sport` menghubungkan tournament dan sport;
- `events.tournament_id`, `events.sport_id`, `events.sport_category_id` serta `events.organization_id`;
- `participants.session_id` dan `participants.organization_id`;
- `registrations` menghubungkan participant–tournament;
- `event_participants` menghubungkan participant–event dan boleh menunjuk pool;
- `squad_members.event_participant_id` serta `organization_id`;
- `matches.event_id`, home/away participant, optional pool, dan `organization_id`;
- `results.match_id` → `matches.id` dengan unique constraint; **`matches` tidak mempunyai `result_id`**;
- `draw_versions.event_id`, `actor_id` dan `organization_id`.

## Invariant Tenant

FK sahaja tidak membuktikan semua rekod dalam hubungan berasal daripada organisasi yang sama. Service/Action, scoped validation dan policy mesti memastikan organization bagi event, sport, category, participant, pool, fixture dan result sepadan. Ujian perlu mencuba ID sah daripada tenant lain.

## Nama Model

Jadual `matches` dipetakan kepada model `Fixture`, kerana `match` ialah keyword PHP. Dokumentasi dan API dalaman boleh menggunakan istilah fixture untuk mengelakkan kekeliruan, sambil mengekalkan nama jadual legacy.
