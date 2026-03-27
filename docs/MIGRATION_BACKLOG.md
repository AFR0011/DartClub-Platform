# MIGRATION_BACKLOG

## Completed In This Pass
- Added repo mapping docs and repo-local Codex config.
- Added the global `repo-map` skill scaffold.
- Centralized DB/bootstrap usage.
- Removed request-path schema mutation.
- Stabilized active tournament support to:
  - `League`
  - `Group`
  - `Elimination`
- Removed active `DoubleElimination` creation support.
- Moved tournament admin JS into `js/admin_tournament_details.js`.
- Moved public/profile/tournament identity reads to `players.user_id`.

## Next High-Priority Validation
- Follow `docs/TESTING_CHECKLIST.md` in order.
- Run a real DB import from `dart_club.sql`.
- Smoke-test:
  - signup/login
  - player approval
  - profile load
  - public tournament registration state
  - tournament create/edit/result flows
- Confirm group promotion and elimination bracket propagation end to end in browser/XAMPP.

## Remaining Backend Cleanup
- Decide whether legacy standalone admin pages should be retired or kept as read-only fallbacks:
  - `pages/admin/record_match_result.php`
  - `pages/admin/view_match_details.php`
  - `pages/admin/manage_blogs.php`
  - image upload/admin legacy pages
- Standardize any remaining asset/path inconsistencies across public pages.
- Document SMTP deployment settings in one place and confirm production-safe defaults.

## Product/UX Follow-Up
- Replace placeholder logos/photos where still pending.
- Review broken or placeholder anchor targets across public pages.
- Improve error/success messaging for public profile and tournament registration flows.
- Decide whether public registration should stay roster-only or gain an explicit admin approval step.

## Deferred
- Full `DoubleElimination` implementation with a real losers bracket engine.
- Framework migration or frontend/backend split.
- Broad visual redesign beyond cleanup required by backend stabilization.
