# CLAUDE.md

# Sports Tournament Management System (STMS)

## Project Vision

STMS is an open-source, enterprise-grade Sports Tournament Management System designed to support:

* Olympic Games
* Asian Games
* Commonwealth Games
* SUKMA
* SUKIPT
* MASUM
* MSSM
* Universities
* Schools
* Sports Associations
* Private Tournament Organizers

The platform must support:

* Multi-Organization
* Multi-Session
* Multi-Tournament
* Multi-Sport
* Multi-Tenant
* Multi-Language

The same codebase must be capable of running a school sports day, university tournament, national games, or international sporting event.

---

# Core Architecture

## Hierarchy

Organization
→ Session
→ Tournament
→ Sport
→ Event
→ Match
→ Result

Examples:

Organization:

* Olympics
* SUKMA
* SUKIPT

Session:

* Paris 2024
* Los Angeles 2028
* SUKMA XXI

Tournament:

* Football
* Athletics
* Swimming

---

# Critical Rules

## Session-Based Design

Never assume tournaments occur yearly.

Examples:

* Olympics = every 4 years
* SUKMA = every 2 years
* MSSM = yearly

All records must belong to a Session.

---

## Configurable Business Rules

Never hardcode:

* Sport names
* Medal systems
* Ranking formulas
* Tournament formats
* Point calculations

All business rules must be configurable.

---

## Multi-Tenant Design

The system must support:

* Multiple organizations
* Multiple sessions
* Multiple tournaments

Data isolation must be maintained.

---

# Backend Standards

Framework:

* Laravel 13 (minimum 12+)

Requirements:

* Service Layer Pattern
* Repository Pattern
* Action Classes
* Form Requests
* API Resources
* Policies

Avoid:

* Fat Controllers
* Business Logic in Controllers
* Direct DB Queries in Controllers

---

# Database Standards

Requirements:

* UUID Primary Keys
* Foreign Key Constraints
* Soft Deletes
* Audit Logging

Naming:

Tables:
snake_case plural

Examples:

organizations
sessions
sports
events

Models:
PascalCase

Examples:

Organization
Session
Sport

---

# API Standards

Versioning:

/api/v1

Requirements:

* RESTful
* API Resources
* Consistent Responses
* OpenAPI Ready

---

# Frontend Standards

Stack:

* React
* Inertia.js
* Tailwind CSS
* TypeScript

---

# UI/UX Standards

## Design System

The entire application must follow shadcn/ui.

Reference:

https://ui.shadcn.com

---

## Approved UI Technologies

Allowed:

* shadcn/ui
* Radix UI
* Tailwind CSS
* Lucide React
* class-variance-authority
* TanStack Table

---

## Forbidden UI Libraries

Do NOT use:

* Material UI
* Ant Design
* Chakra UI
* Mantine
* Flowbite
* DaisyUI
* PrimeReact
* Bootstrap Components

unless explicitly approved.

---

## Component Policy

Before creating a component:

1. Check components/ui/
2. Reuse shadcn component
3. Extend existing component
4. Create new component only if necessary

Avoid duplicate components.

---

## Forms

Use shadcn patterns:

* Form
* Input
* Select
* Combobox
* Checkbox
* Radio Group
* Date Picker
* Textarea

---

## Tables

Use:

* shadcn/ui Table
* TanStack Table

---

## Dialogs

Use:

* Dialog
* Sheet
* Alert Dialog

Never use browser alert().

---

## Icons

Use Lucide React only.

No FontAwesome.
No HeroIcons.
No Bootstrap Icons.

---

## Styling Rules

Prefer:

* Tailwind utilities
* CVA variants
* CSS Variables

Avoid:

* Inline styles
* Large custom CSS files
* !important

---

# Testing Standards

Every feature must include:

* Feature Tests
* Unit Tests

Bug fixes should include:

* Regression Tests

Target:

80%+ coverage

---

# Documentation

Always keep updated:

* ROADMAP.md
* TODOS.md
* CHANGELOG.md

Architecture changes require ADR updates.

Location:

docs/adr/

---

# Long-Term Goal

Become the leading open-source sports tournament management platform for educational institutions, sports councils, and international sporting organizations.
