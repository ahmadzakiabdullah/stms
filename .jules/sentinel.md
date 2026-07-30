2024-05-24 - [Faculty Dashboard Squad Members IDOR], Vulnerability, Learning, and Prevention.
- Vulnerability: IDOR missing authorization on adding and deleting squad members via event participant UUID.
- Learning: Always verify that the resources being accessed or modified belong to the authenticated user using strict comparison checks (e.g., $ep->participant_id !== Auth::user()->participant_id) or Gate authorization checks before taking action.
- Prevention: Apply proper access control mechanisms and validate authorization for all endpoints accessing resources by reference IDs.
