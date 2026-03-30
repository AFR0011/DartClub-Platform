# MIGRATION_BACKLOG

## Completed In This Pass
- Added repo mapping docs and repo-local Codex config.
- Added the global `repo-map` skill scaffold.
- Centralized DB/bootstrap usage.
- Removed request-path schema mutation.
- Fixed the SQL dump so a fresh import works again.
- Fixed service session bootstrap so login-backed flows persist correctly.
- Locked admin mutation handlers behind service-level role checks.
- Ran real XAMPP/MariaDB runtime tests for the core product stack.
- Moved public/profile/tournament identity reads to `players.user_id`.
- Added explicit `Round Robin` support and corrected the tournament-type model.
- Added tournament lifecycle state, public visibility, and archive read-only guards.
- Added public tournament detail and dashboard surfaces.
- Added membership submission/review workflow with `membership_status` separate from auth role.
- Added blog drafts, moderation, comments, likes, and blog-image gallery insertion.
- Added team-mode `Group` tournaments with team rosters and team fixtures.
- Fixed team standings so completed team matches are no longer double-counted.
- Fixed `manage_tournaments.php` so it loads the lifecycle helper instead of fatalling at runtime.
- Repaired and hardened the legacy `create_player.php` endpoint.
- Fixed shared-media handling so gallery deletion no longer breaks blog-linked images.
- Verified uneven player/team distributions and full 128-player elimination completion.

## Next High-Priority Validation
- Follow `docs/TESTING_CHECKLIST.md` in order.
- Finish the remaining runtime/manual gaps:
  - SMTP-backed credential delivery
  - Apache visual and navigation QA
  - responsive/mobile QA
  - full manual click-through of admin navigation and fallback pages

## Remaining Backend Cleanup
- Decide whether legacy standalone admin pages should be retired or kept as read-only fallbacks:
  - `pages/admin/record_match_result.php`
  - `pages/admin/view_match_details.php`
  - `pages/admin/manage_blogs.php`
  - image upload/admin legacy pages
- Decide whether roster registration should remain a manager-promoted step or become auto-activation for some tournament states.
- Document SMTP deployment settings in one place and confirm production-safe defaults.

## Product/UX Follow-Up
- Replace placeholder logos/photos where still pending.
- Review broken or placeholder anchor targets across public pages.
- Improve error/success messaging for profile, membership, tournament registration, and community flows.
- Redesign the public pages so `tournaments`, `profile`, `blog`, `gallery`, `login`, `signup`, and `register` visually match the homepage language.
- Redesign the admin area into a more coherent operations console once the remaining runtime issues are closed.

## Deferred
- Full `DoubleElimination` implementation with a real losers bracket engine.
- Framework migration or frontend/backend split.
- Realtime sockets/push for live scoring.
