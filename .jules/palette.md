## 2026-09-09 - Icon-Only Buttons Translation and ARIA
**Learning:** React components in this codebase rely on a `t()` function for translations. When adding `aria-label` or `title` attributes to icon-only buttons for accessibility, these strings must also be wrapped in `t()` to ensure internationalization consistency.
**Action:** Always wrap newly added user-facing strings (like `aria-label="Approve"`) in the translation function (e.g., `aria-label={t('Approve')}`) if `t` is available in the component.
