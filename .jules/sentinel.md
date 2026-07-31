## 2024-07-29 - Fixed IDOR in Faculty Dashboard
**Vulnerability:** Insecure Direct Object Reference (IDOR) found in `FacultyDashboardController` where authenticated users could manage (add, remove, import) squad members for an `EventParticipant` that did not belong to their own `participant_id`.
**Learning:** Participant-scoped endpoints (like a dashboard meant for a specific faculty or team) that accept related IDs (like `event_participant_id` or `squad_member_id`) must explicitly verify that the related model inherently belongs to the currently authenticated user's `participant_id`. `findOrFail()` is not enough.
**Prevention:** Always check ownership: `if (Auth::user()->participant_id !== $model->participant_id) { abort(403); }` when modifying participant resources.

2024-05-24 - [Faculty Dashboard Squad Members IDOR], Vulnerability, Learning, and Prevention.
- Vulnerability: IDOR missing authorization on adding and deleting squad members via event participant UUID.
- Learning: Always verify that the resources being accessed or modified belong to the authenticated user using strict comparison checks (e.g., $ep->participant_id !== Auth::user()->participant_id) or Gate authorization checks before taking action.
- Prevention: Apply proper access control mechanisms and validate authorization for all endpoints accessing resources by reference IDs.
## 2026-07-31 - Enforce Multi-tenant Check (Organization ID) on Nested Objects
**Vulnerability:** IDOR (Insecure Direct Object Reference) leading to unauthorized access.
**Learning:** Even if an object has a primary authorization check like a user's participant_id, nested resources belonging to an organization in a multi-tenant system should also explicitly have their `organization_id` matched against the `Auth::user()->organization_id` to prevent cross-tenant access.
**Prevention:** In multi-tenant application controllers, always validate both the specific contextual relationships (like participant_id) and the overarching tenant scope (organization_id) when performing state-modifying actions (create, delete, update).
