# Sports Engine

The Sports Engine is the core domain model that defines what sports the system can manage. It is built around two primary models: `Sport` and `SportCategory`.

`Sport` represents a distinct athletic discipline (e.g., Soccer, Basketball, Swimming). Each sport can have multiple `SportCategory` records, which represent divisions or variations within that sport (e.g., Under-14 Boys, Women's Doubles). Categories are used for grouping participants and scheduling events within a tournament.

The design is **generic by design** — no sport-specific rules, scoring logic, or ranking formulas are hardcoded into the model layer. All sports are defined as data rows seeded into the `sports` table. This ensures the platform can support any sport without code changes. Sport-specific behavior (scoring, rules, field dimensions, uniform requirements) should be implemented as configurable metadata stored in JSON columns or through a plugin system, not through conditional logic in the application code.

The `Sport` model exposes a `categories()` relationship (hasMany). The Sports Engine service provides methods for querying available sports, filtering by organization, and associating sports with tournaments and events.
