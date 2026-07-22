# Design System Overview

## Stack

The STMS frontend is built with **React 18 + Inertia.js + TypeScript** and styled using **Tailwind CSS v3** with the **shadcn/ui** component library. shadcn/ui is not a published package — it provides copy-paste-ready, accessible React components built on Radix UI primitives that are installed directly into the project's `Components/ui/` directory.

## Dark Mode

Dark mode is class-based (`darkMode: ['class']` in `tailwind.config.js`). A theme toggle switches between `light` and `dark` modes by toggling the `.dark` class on the root `<html>` element. All components use CSS variables defined in `app.css` to adapt to the active theme seamlessly.

## Component Library Approach

Rather than pulling in opinionated UI frameworks (MUI, Ant Design, Chakra), STMS follows the shadcn/ui philosophy: own your components. Each component in `resources/js/components/ui/` is a standalone, customizable, accessible React component. This gives full control over styling, behavior, and theming while avoiding version lock-in and bundle bloat.

## File Structure

```
resources/js/components/ui/
├── button.tsx
├── card.tsx
├── input.tsx
├── label.tsx
├── dialog.tsx
├── select.tsx
├── table.tsx
├── badge.tsx
└── ...
```

Pages in `resources/js/Pages/` compose these components into feature-specific views using Inertia's page system.
