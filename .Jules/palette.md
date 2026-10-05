## 2025-02-28 - Missing ARIA Labels on Navigation Icons
**Learning:** Icon-only navigation buttons in AuthenticatedLayout (Mobile Menu, Notifications, User Menu) lacked ARIA labels, creating accessibility gaps for screen readers.
**Action:** Always ensure any icon-only button uses an `aria-label` describing its specific functionality.
## 2026-55-05 - Add ARIA Labels to Event Participant Action Buttons
**Learning:** Interactive icon-only buttons in complex data tables (like the Event Participants table) often lack accessible names, making critical actions (approve, reject, unregister) invisible to screen readers.
**Action:** Always ensure that icon-only  and  elements within data tables and management views include descriptive s, utilizing the translation function  for localization when available.
## 2025-02-28 - Add ARIA Labels to Event Participant Action Buttons
**Learning:** Interactive icon-only buttons in complex data tables (like the Event Participants table) often lack accessible names, making critical actions (approve, reject, unregister) invisible to screen readers.
**Action:** Always ensure that icon-only `button` and `Link` elements within data tables and management views include descriptive `aria-label`s, utilizing the translation function `t()` for localization when available.
