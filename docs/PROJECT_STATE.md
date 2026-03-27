# PROJECT_STATE

## Metadata
- Project: `Dart Club`
- Last updated: 2026-03-27
- Repo type: legacy PHP/MySQL website
- Current repo status: mapped, documented, and partially stabilized

## Current Objective
- Finish the migration from an ad-hoc legacy codebase to a maintainable documented workspace.
- Keep the existing plain PHP/MySQL stack.
- Stabilize tournament creation, group handling, elimination flow, and player identity mapping before any broader polish work.

## Current Technical Status
- The repo now has:
  - root `AGENTS.md`
  - repo-local `.codex/config.toml`
  - `docs/` operating docs
  - a shared PHP bootstrap/config path
  - shared tournament helper code
- The imported tree originally behaved like an untracked project snapshot. Git was initialized locally during this mapping pass, but there is no prior commit history in the current checkout.
- Frontend/public pages are largely present:
  - home/main
  - tournaments
  - blog
  - gallery
  - profile
- Backend maturity is mixed:
  - auth/signup/login exist
  - blog/gallery/user management exist
  - tournament flows were the main instability area and are now partially consolidated

## Active Backend Baseline
- Canonical DB/bootstrap path:
  - `services/config.php`
  - `services/app_bootstrap.php`
  - `services/dbConnection.php`
- Canonical tournament helper layer:
  - `services/shared/tournament_helpers.php`
  - `services/shared/tournament_view_helpers.php`
  - `services/shared/player_helpers.php`
- Active supported tournament types:
  - `League`
  - `Group`
  - `Elimination`
- Deferred tournament type:
  - `DoubleElimination`

## What Changed In This Pass
- Removed request-path `ALTER TABLE` calls from active PHP handlers.
- Standardized DB access away from mixed `root` vs `dartadmin/1234` usage.
- Moved tournament scheduling and result propagation into shared helpers.
- Updated public/profile/tournament identity resolution to use `players.user_id`.
- Reworked tournament admin pages around canonical `group_count` and `advancers_per_group` inputs.
- Replaced the oversized tournament details hotspot with a smaller shared-helper-backed page plus external JS.
- Updated `dart_club.sql` to describe the schema the current code expects.

## Verified Repo Facts
- There is no framework scaffolding such as Laravel, Symfony, React build tooling, or Node package management in this repo.
- Composer is present only for PHPMailer.
- PHP CLI is not currently available on PATH in this Codex environment.
- Historical migration notes still exist in:
  - `README.md`
  - `progress.md`
  - `todolist.md`
  - `tournament-errors.md`

## Open Risks
- Browser/XAMPP validation has not yet been run from this Codex session.
- PHP syntax linting could not be run because `php` is not available in the current environment.
- Legacy admin pages still exist and may not fully match the consolidated flows.
- Public tournament registration now stores roster registration only; admins still need to manage activation into the live schedule.
- The SQL dump has been updated to reflect expected schema, but an actual fresh DB import was not performed in this session.

## Remaining Priorities
- Run a real XAMPP/MariaDB smoke test against the updated schema.
- Use `docs/TESTING_CHECKLIST.md` as the next-session manual verification order.
- Verify:
  - signup/login
  - player approval/creation
  - profile and my-tournaments
  - league creation/edit/result flow
  - group creation/promotion flow
  - elimination bracket progression
- Clean up or retire remaining legacy admin pages once replacement coverage is confirmed.
- Standardize asset/path cleanup and document SMTP expectations clearly for deployment.

## Verification State
- Verified in this pass:
  - repo structure inspection
  - DB credential/source-path consolidation
  - removal of request-path `ALTER TABLE` usage
  - removal of scattered `new mysqli(...)` usage outside the shared bootstrap
  - creation of repo docs and repo-local config
  - creation of the personal-global `repo-map` skill scaffold
- Not verified in this pass:
  - PHP syntax linting
  - database import
  - browser/XAMPP flows
  - end-to-end tournament UI interactions
- Next-session operator guide:
  - `docs/TESTING_CHECKLIST.md`
