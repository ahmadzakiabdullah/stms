# Accreditation System

The accreditation system is **deferred to a future milestone** and is not yet implemented. This documentation serves as a placeholder and high-level design intent.

The accreditation system will manage credentialing for event participants, staff, media, and VIPs. Planned features include:
- Accreditation types and tiers per event/venue
- Photo upload and identity verification workflows
- QR-code-based badge generation
- Access control mapping (which venues/zones each badge permits)
- Accreditation request, approval, and check-in workflows

When implemented, accreditation will live in its own domain module under `app/Modules/Accreditation/` with dedicated controllers, services, and database migrations. It will integrate with the existing Participant and Organization models through polymorphic relationships.

No accreditation code, migrations, or UI exists in the current codebase. This feature is scoped for post-MVP delivery.
