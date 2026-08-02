## 2024-05-18 - Added aria labels to icon buttons

**Learning:** It is easy to miss adding screen reader descriptions on interactive components that only contain an icon, particularly when relying on `title` tooltips, which aren't always announced.

**Action:** Always verify icon-only buttons include an `aria-label` attribute if they lack descriptive, visible inner text.
