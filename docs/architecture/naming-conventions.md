# Naming Conventions

- PHP: PSR-12; class `PascalCase`, method/property `camelCase`.
- TypeScript/React: component/type `PascalCase`, variable/function `camelCase`, page files `.tsx`.
- Database: table/column `snake_case`; table plural; FK `{entity}_id`.
- Route name: domain/action seperti `tournaments.index` atau `events.draw.generate`.
- Service untuk orkestrasi domain; Action untuk satu perubahan fokus; Form Request untuk validation; Policy/Gate untuk authorization.

Model `Fixture` memetakan jadual sejarah `matches`; elakkan memperkenalkan model `Match` kerana `match` ialah keyword PHP. Pivot tournament-sport yang sebenar ialah `tournament_sport`, bukan `sport_tournament`.

Primary key baharu hendaklah UUID. Jadual `settings` ialah pengecualian legacy dengan integer `id`; jangan jadikan pengecualian itu precedent. Soft delete digunakan apabila retention domain diperlukan, bukan secara automatik pada setiap jadual atau snapshot immutable.

Istilah domain kanonik: Organization → Session → Tournament → Event → Fixture → Result, dengan Sport/SportCategory sebagai katalog organisasi dan `tournament_sport` sebagai pemetaan many-to-many.
