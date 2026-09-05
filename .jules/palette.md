## 2025-02-28 - Missing ARIA Labels on Inline Action Buttons
**Learning:** Icon-only action buttons inside tables and grid views in `EventParticipants/Index.tsx` (like Grid view, Table view, Approve, Reject, Unregister) lacked ARIA labels, rendering them inaccessible to screen readers.
**Action:** Always verify that any interactive element, especially icon-only buttons representing actions, include `aria-label` attributes describing their function, utilizing localization functions like `t()` where applicable.
