# Git Workflow

This repository follows a standard Git workflow with a clear branch strategy and Conventional Commits formatting.

## Branch strategy

- `main` — production-ready branch.
- `development` — staging and integration branch for reviewed feature work.
- `feature/*` — isolated feature branches for specific modules, enhancements, or bug fixes.

## Branch workflow

1. Branch from `development` using a descriptive name like `feature/user-auth`.
2. Develop and make commits locally.
3. Open a merge request into `development` for review and testing.
4. Once the work is stable and validated, merge `development` into `main`.

## Commit message format

We use Conventional Commits to keep history readable and support tooling.

- `feat: ` — a new feature
- `fix: ` — a bug fix
- `docs: ` — documentation changes
- `chore: ` — maintenance tasks or tooling updates
- `refactor: ` — code changes without new features or bug fixes

### Example

```text
feat: add import/export support for user profiles
fix: resolve missing foreign key in migrations
docs: update deployment checklist for PostgreSQL
```