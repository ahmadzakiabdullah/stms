## 2026-08-10 - Missing ARIA Labels on Icon-only Event Participant Buttons
**Learning:** The Event Participants grid/table toggle buttons and row action buttons (Approve, Reject, Unregister) were missing accessible `aria-label`s, rendering them ambiguous to screen readers.
**Action:** Consistently ensure that all icon-only action buttons (especially within DataTables or complex lists) include an `aria-label` attribute, leveraging the `t()` translation function for internationalization where appropriate.
