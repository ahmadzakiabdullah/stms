# Contributing to STMS

Thank you for your interest in contributing to the Sports Tournament Management System (STMS). This document outlines the guidelines for contributing to the project.

## Code of Conduct

All contributors are expected to follow the project's code of conduct. Be respectful, professional, and collaborative.

## Getting Started

Before contributing, please:

1. Read `CLAUDE.md` thoroughly.
2. Read `AGENTS.md`.
3. Review the current `TODOS.md` to understand the active development focus.
4. Review relevant Architecture Decision Records (ADRs) in `docs/adr/`.

## Development Workflow

### 1. Planning
- Do not start coding without understanding the requirements.
- For significant changes, create a discussion or issue first.
- Follow the current milestone defined in `TODOS.md`.

### 2. Branching Strategy

- `main` — Production-ready code
- `develop` — Integration branch for ongoing development
- Feature branches: `feature/xxx` (e.g., `feature/organization-module`)
- Bug fixes: `fix/xxx`

### 3. Commit Messages

Use clear and descriptive commit messages:


feat: add organization creation endpoint
fix: resolve multi-tenant scoping issue in Session model
docs: update ADR-002 with implementation notes
refactor: extract ranking logic into RankingService

### 4. Pull Request Process

- Ensure all tests pass before submitting a PR.
- Update documentation (`docs/`) when making architectural or functional changes.
- Request review from at least one maintainer.
- Keep PRs focused and reasonably sized.

## Coding Standards

- Follow PSR-12 and Laravel best practices.
- Use Service Layer + Action Classes pattern.
- Write tests for every new feature (Feature + Unit tests).
- Use Form Requests for validation.
- Use Policies for authorization.
- Never hardcode sport names, rules, or ranking formulas.

## Documentation

- When architecture changes, update `docs/adr/` and `docs/architecture/`.
- When functionality changes, update `TODOS.md` and `CHANGELOG.md`.
- Keep documentation up to date.

## Questions?

If you are unsure about anything, open an issue or discussion first before implementing.

---

Thank you for contributing to STMS!