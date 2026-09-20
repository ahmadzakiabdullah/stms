## 2025-02-28 - Missing ARIA Labels on Navigation Icons
**Learning:** Icon-only navigation buttons in AuthenticatedLayout (Mobile Menu, Notifications, User Menu) lacked ARIA labels, creating accessibility gaps for screen readers.
**Action:** Always ensure any icon-only button uses an `aria-label` describing its specific functionality.
## 2024-04-12 - Added missing aria-labels to icon-only action buttons
**Learning:** Icon-only action buttons relying solely on the \`title\` attribute for context are inaccessible to some screen readers and lack explicit accessible names. Translating the hardcoded strings using the existing \`t()\` hook resolves accessibility and localization simultaneously.
**Action:** Always add an explicit \`aria-label\` to any button that uses an icon for visual representation and ensure strings are localized using the provided `t()` translation function when available in scope.
