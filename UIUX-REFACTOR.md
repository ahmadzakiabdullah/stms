# PUBLIC WEBSITE UI/UX REFACTOR — MASTER PROMPT

You are acting as a Principal UI/UX Designer, Frontend Architect,
Laravel Engineer, Accessibility Engineer, Performance Engineer,
and Design Systems Engineer.

Your task is to redesign and refactor ONLY the PUBLIC-FACING pages
of this Laravel application.

IMPORTANT SCOPE:

This task applies ONLY to pages accessible to public/guest users.

DO NOT modify:

- Admin dashboard
- Staff dashboard
- User dashboard
- Authentication system
- Backend business logic
- Database schema
- API contracts
- Authorization logic
- Existing application workflows
- Admin components
- Internal management interfaces

The objective is to transform the public website into a:

- modern
- professional
- trustworthy
- accessible
- responsive
- visually consistent
- fast
- maintainable
- production-ready

public-facing experience.

==================================================
1. FIRST — AUDIT BEFORE MODIFYING
==================================================

Do NOT immediately modify the code.

First inspect the existing public website.

Identify:

- Public routes
- Public controllers
- Public Blade/Inertia/React/Vue pages
- Main layout
- Header
- Navigation
- Footer
- Hero sections
- Content sections
- Cards
- Forms
- Tables
- Search
- Filters
- Pagination
- CTA sections
- News/announcement sections
- Event sections
- Gallery
- Quick links
- Breadcrumbs
- Contact sections
- Error pages

Determine which pages are public-facing.

Create a PUBLIC PAGE INVENTORY:

Page:
Route:
Template:
Purpose:
Primary user:
Primary CTA:
Secondary CTA:
Current UX problems:
Priority:

Do not modify files during this phase.

==================================================
2. PUBLIC USER EXPERIENCE
==================================================

Evaluate the website from the perspective of a first-time visitor.

The user should immediately understand:

1. What this website/system is.
2. Who it is for.
3. What they can do here.
4. What information is available.
5. What action they should take next.

Improve:

- Information hierarchy
- Content discoverability
- Navigation
- Page scanning
- CTA visibility
- Search experience
- Mobile usability
- Content grouping
- Visual hierarchy
- Trust signals

Avoid unnecessary cognitive load.

The interface should feel simple even when the underlying
system is technically complex.

==================================================
3. INFORMATION ARCHITECTURE
==================================================

Review the public navigation.

Design a clear structure:

HEADER
├── Brand / Logo
├── Primary Navigation
├── Important CTA
└── Mobile Navigation

PAGE
├── Breadcrumb
├── Page Header
├── Main Content
└── Contextual CTA

FOOTER
├── Important Links
├── Contact
├── Resources
├── Social / External Links
└── Copyright

Navigation must clearly communicate:

- Where the user is
- Where they can go
- What is important
- What action they can take

Do not create unnecessary menu items.

==================================================
4. VISUAL DESIGN DIRECTION
==================================================

The public website should look:

- modern
- institutional
- professional
- technical
- trustworthy
- clean
- contemporary
- approachable

Avoid generic SaaS styling unless appropriate.

Avoid:

- excessive gradients
- excessive glassmorphism
- excessive shadows
- excessive rounded cards
- excessive animations
- decorative elements without purpose
- oversized typography
- visual clutter
- inconsistent icon styles
- excessive borders
- random colours

Use:

- strong visual hierarchy
- controlled whitespace
- subtle elevation
- consistent spacing
- clear typography
- restrained colour palette
- purposeful accent colours
- consistent iconography

==================================================
5. DESIGN SYSTEM
==================================================

Create a lightweight public website design system.

Define:

COLORS

- Primary
- Secondary
- Accent
- Background
- Surface
- Muted Surface
- Border
- Text
- Muted Text
- Success
- Warning
- Error
- Information

TYPOGRAPHY

Define:

- Display
- H1
- H2
- H3
- H4
- Body
- Small
- Caption
- Navigation
- Button

SPACING

Use a consistent spacing scale.

COMPONENTS

Create consistent patterns for:

- Header
- Navigation
- Mobile menu
- Breadcrumb
- Hero
- Section header
- Button
- Link
- Card
- Feature card
- Content card
- Badge
- Alert
- Search
- Filter
- Pagination
- Form
- Input
- Select
- Textarea
- Modal
- CTA
- Footer
- Empty state
- Loading state
- Error state

Do not create unnecessary abstractions.

Reuse existing components wherever practical.

==================================================
6. HEADER / NAVIGATION
==================================================

Redesign the public header.

Requirements:

- Clear brand identity
- Clear navigation hierarchy
- Obvious active state
- Strong contrast
- Responsive navigation
- Mobile-friendly menu
- Keyboard accessible
- Proper focus states

Desktop navigation should not become overcrowded.

Mobile navigation should be intentionally designed,
not simply a collapsed desktop menu.

==================================================
7. HERO SECTION
==================================================

For important landing pages, redesign the hero section.

Hero should communicate:

- What the page/system is
- Main value proposition
- Primary action
- Secondary action if necessary

Use visual elements only when they reinforce the message.

Avoid hero sections that consume excessive vertical space.

==================================================
8. PAGE SECTIONS
==================================================

Every major section should have:

- clear purpose
- appropriate heading
- optional description
- consistent spacing
- logical content grouping

Use visual rhythm between sections.

Avoid making every section look like a card.

Use different layout patterns when appropriate:

- Full-width sections
- Split layouts
- Grid layouts
- Feature sections
- Content sections
- Statistics
- Timeline
- CTA sections
- Editorial layouts

The page should feel like a coherent visual journey.

==================================================
9. CARDS
==================================================

Do not turn every piece of content into a card.

Cards should only be used when content benefits from grouping.

Use consistent:

- padding
- border
- radius
- shadow
- hover behaviour
- typography
- icon placement

Avoid excessive card nesting.

==================================================
10. FORMS
==================================================

For public-facing forms:

Improve:

- labels
- descriptions
- required indicators
- validation
- error messages
- success messages
- field grouping
- button hierarchy
- mobile layout

Validation must be understandable.

Do not rely exclusively on colour.

Preserve existing backend validation.

==================================================
11. SEARCH / FILTER / DATA DISPLAY
==================================================

For public search and listing pages:

Improve:

- search input
- filters
- sorting
- pagination
- result count
- empty state
- loading state
- error state

Make it immediately clear:

"What am I looking at?"

"How many results are there?"

"How can I narrow the results?"

==================================================
12. RESPONSIVE DESIGN
==================================================

Use mobile-first design.

Consider:

320px
375px
390px
430px
768px
1024px
1280px
1440px
1920px

Verify:

- no horizontal overflow
- readable typography
- usable navigation
- usable forms
- usable cards
- usable tables
- appropriate image scaling
- appropriate spacing
- touch-friendly controls

Do not simply shrink the desktop design.

Design mobile layouts intentionally.

==================================================
13. ACCESSIBILITY
==================================================

Target WCAG 2.1 AA.

Verify:

- semantic HTML
- heading hierarchy
- landmarks
- keyboard navigation
- focus states
- focus visibility
- accessible forms
- accessible navigation
- sufficient colour contrast
- alt text
- accessible links
- accessible buttons
- screen reader considerations

Do not use colour alone to communicate information.

==================================================
14. ANIMATION / MOTION
==================================================

Use subtle motion to improve UX.

Good examples:

- hover transitions
- navigation transitions
- subtle section reveal
- button feedback
- card interaction

Avoid:

- excessive parallax
- continuous animations
- distracting effects
- animation on every element

Respect:

prefers-reduced-motion

Animations must not reduce usability or performance.

==================================================
15. IMAGES / ICONS / VISUAL ASSETS
==================================================

Use consistent iconography.

Do not mix unrelated icon styles.

Images should:

- have appropriate aspect ratios
- avoid layout shifts
- use lazy loading where appropriate
- use responsive sizing
- include meaningful alt text

Decorative images should not unnecessarily burden accessibility.

==================================================
16. PERFORMANCE
==================================================

The public website must prioritize performance.

Optimize:

- CSS
- JavaScript
- images
- fonts
- DOM complexity
- lazy loading
- asset loading
- animations

Avoid unnecessary JavaScript.

Prefer CSS for simple visual effects.

Do not introduce heavy libraries for simple interactions.

Target:

- fast initial render
- minimal layout shift
- minimal blocking resources
- efficient asset delivery

==================================================
17. SEO
==================================================

Review public pages for:

- semantic HTML
- title
- meta description
- heading hierarchy
- canonical URL where appropriate
- Open Graph metadata where appropriate
- descriptive links
- image alt text
- structured content

Do not modify SEO-critical routing without understanding
the existing implementation.

==================================================
18. SECURITY
==================================================

Do not weaken security.

Preserve:

- CSRF
- escaping
- validation
- authorization
- secure forms
- safe file handling
- secure links

Never trust client-side validation alone.

==================================================
19. LARAVEL IMPLEMENTATION
==================================================

Respect the existing Laravel architecture.

Before creating components determine whether the application uses:

- Blade
- Livewire
- Inertia
- React
- Vue
- Alpine.js
- Tailwind
- Bootstrap
- another UI system

Use the existing stack whenever practical.

Do NOT introduce another frontend framework simply to redesign the UI.

Do NOT create duplicate component systems.

If Tailwind is already used:

Prefer Tailwind utilities and reusable components.

If shadcn/ui is already used:

Prefer existing shadcn/ui components.

If Blade components are used:

Prefer reusable Blade components.

==================================================
20. COMPONENT ARCHITECTURE
==================================================

Create reusable public components only when there is
a real reuse case.

Possible structure:

resources/
└── views/
    ├── layouts/
    │   └── public.blade.php
    │
    ├── components/
    │   └── public/
    │       ├── header.blade.php
    │       ├── navigation.blade.php
    │       ├── breadcrumb.blade.php
    │       ├── hero.blade.php
    │       ├── section-header.blade.php
    │       ├── card.blade.php
    │       ├── button.blade.php
    │       ├── alert.blade.php
    │       ├── empty-state.blade.php
    │       └── footer.blade.php
    │
    └── pages/
        └── public/

Adapt this structure to the actual project.

Do not force this exact structure if the existing architecture
already has a better organization.

==================================================
21. CSS ARCHITECTURE
==================================================

Reduce duplicated CSS.

Prefer:

- design tokens
- utility classes
- reusable components
- CSS variables
- existing framework utilities

Avoid:

- page-specific hacks
- !important unless genuinely necessary
- deeply nested selectors
- duplicated declarations
- arbitrary magic values

Do not rewrite all CSS unless necessary.

==================================================
22. IMPLEMENTATION STRATEGY
==================================================

Work incrementally.

PHASE 1
Public layout

PHASE 2
Header and navigation

PHASE 3
Design system

PHASE 4
Homepage / landing page

PHASE 5
Content/listing pages

PHASE 6
Forms

PHASE 7
Search/filter pages

PHASE 8
Detail pages

PHASE 9
Footer

PHASE 10
Accessibility and responsive QA

PHASE 11
Performance optimization

Do not refactor all pages in one operation.

==================================================
23. BEFORE EACH CHANGE
==================================================

Before modifying a page:

1. Inspect the existing implementation.
2. Identify reusable components.
3. Identify business logic.
4. Identify routes.
5. Identify data dependencies.
6. Identify responsive behaviour.

Then explain briefly:

WHY:
WHAT:
FILES:
RISK:

==================================================
24. AFTER EACH CHANGE
==================================================

Verify:

- Laravel application loads
- route works
- page renders
- no PHP errors
- no JavaScript errors
- no console errors
- responsive layout works
- existing functionality still works

Run appropriate:

- tests
- lint
- formatter
- build

==================================================
25. IMPORTANT CONSTRAINT
==================================================

This is a PUBLIC UI/UX REFACTOR.

Do not turn this into a backend refactor.

Do not modify unrelated application functionality.

Do not change business rules.

Do not redesign admin interfaces.

Do not introduce unnecessary dependencies.

Do not make large destructive changes.

Prioritize:

UX
Accessibility
Consistency
Performance
Maintainability
Responsive design
Visual hierarchy

over visual novelty.

==================================================
FINAL QUALITY BAR
==================================================

The public website should feel like a professionally designed
modern institutional/enterprise website rather than a collection
of individually designed pages.

Every page should share:

- the same visual language
- the same spacing system
- the same typography
- the same button hierarchy
- the same interaction patterns
- the same responsive principles
- the same accessibility standards

The final result must be:

CONSISTENT
ACCESSIBLE
RESPONSIVE
FAST
PROFESSIONAL
MAINTAINABLE
PRODUCTION-READY