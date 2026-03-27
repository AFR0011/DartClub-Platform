# Dart Club Repo Guide

## Repo Type
This repository is a legacy plain-PHP/MySQL website workspace.
It is not a framework app, package, or service mesh.

The main units of work are:
- page templates under `pages/`
- request handlers under `services/`
- SQL/schema state in `dart_club.sql`
- static assets under `css/`, `js/`, and `files/`
- repo-state and migration docs under `docs/`

## Primary Source Of Truth
- `pages/` for routed UI surfaces
- `services/` for backend behavior and shared helpers
- `dart_club.sql` for expected schema
- `docs/PROJECT_STATE.md` for current repo status
- `docs/REPO_MAP.md` for structure and ownership
- `docs/MIGRATION_BACKLOG.md` for remaining cleanup/finalization work

Do not treat `progress.md` as the current technical source of truth.
Treat it as historical migration context only.

## Operating Rules
- Prefer minimal, local diffs over broad rewrites.
- Keep the current plain PHP/MySQL architecture unless explicitly asked to migrate away from it.
- Use `services/config.php`, `services/app_bootstrap.php`, and `services/dbConnection.php` as the canonical DB/bootstrap path.
- Do not reintroduce request-path schema mutations such as `ALTER TABLE ... IF NOT EXISTS` inside live handlers.
- Keep tournament support scoped to:
  - `League`
  - `Group`
  - single `Elimination`
- Treat `DoubleElimination` as deferred unless the user explicitly asks to design and implement a full bracket engine.
- Prefer `users.user_id -> players.user_id` for identity mapping.
- Do not bring back steady-state username-based player resolution in public/profile/tournament services.
- Keep `tour_creationDate` as the effective tournament start-date column unless a deliberate migration is requested.

## Required Local Docs
Before substantial work, read:
- `docs/PROJECT_STATE.md`
- `docs/REPO_MAP.md`
- `docs/RUN_PROTOCOL.md`
- `docs/VERSION_LOG.md`
- `docs/MIGRATION_BACKLOG.md`
- `docs/LESSONS.md` if it exists

## Verification Rules
- Do not claim completion without verification.
- This environment currently does not expose `php` on PATH, so PHP linting may be unavailable from Codex.
- Use the verification ladder in `docs/RUN_PROTOCOL.md`.
- Prefer the least expensive valid check for the change:
  - static repo inspection
  - SQL/schema drift check
  - targeted browser/XAMPP smoke test
  - tournament flow regression check

If full browser/DB validation was not run, explicitly say:
- what was not run
- why
- what remains unverified

## Documentation Sync Rules
- Update `docs/PROJECT_STATE.md` when repo status, active cleanup lane, blockers, or verification state changes.
- Update `docs/REPO_MAP.md` when structure, service ownership, or major data flow changes.
- Update `docs/VERSION_LOG.md` for meaningful cleanup milestones.
- Update `docs/MIGRATION_BACKLOG.md` when backlog items are completed, deferred, or reprioritized.
- Update `docs/LESSONS.md` only for concrete recurring traps.

## Done Means
A task is not done here unless all relevant items are handled:
1. changed files are identified
2. commands run are listed
3. outputs/docs created or updated are named
4. verification performed is stated clearly
5. remaining risks or assumptions are stated clearly
6. repo docs are updated when the task materially changes state or workflow

## Legacy Notes
- Old admin pages such as the standalone match detail/result pages and the blog/image admin pages may still exist as legacy surfaces.
- Prefer the consolidated tournament admin flow in `pages/admin/show_tournament_details.php`.
