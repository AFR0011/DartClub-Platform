# MIGRATION_BACKLOG

## Completed In This Pass
- Fixed guest session-context loading so the public shell no longer needs a DB connection before checking login state.
- Added service-level JSON hardening so uncaught service errors stop leaking HTML into frontend `.json()` callers.
- Fixed config env parsing for blank DB passwords.
- Removed the duplicate legacy navbar/bootstrap logic from `pages/main.php`.
- Fixed shared public-shell JS guards for missing section anchors, dynamic nav links, and `href="#"` interactions.
- Fixed admin user deletion to follow the canonical `users.user_id -> players.user_id` mapping.
- Fixed tournament detail summary warnings by preserving aggregate counts across lifecycle refreshes.
- Added deferred tournament generation so admins/managers can create empty tournaments, collect entrants, and explicitly generate fixtures later.
- Added public guest/name-based tournament registration fallback for visitors and signed-in users without a player profile.
- Added tournament-admin quick scoring and bracket slot swapping so more match management can happen on one page.
- Added a connected admin bracket view plus row-click roster selection to make tournament management less checkbox-heavy.
- Added a public “My tournaments” hub filter and signed-in no-profile quick registration from account-name seeding.
- Extended the newer tournament/public visual language onto the blog page, public tournament detail page, and older admin console surfaces.
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
  - deferred tournament QA:
    - guest registration modal
    - signed-in no-profile registration path
    - admin generate/rebuild structure flow
    - bracket drag-and-drop under a real browser pointer/touch session
    - compact match-score entry flow under real browser interaction
    - connected bracket spacing/readability under real browser rendering

## Remaining Backend Cleanup
- Document or script the one-time MariaDB grant setup expected for the canonical `dartadmin` local user.
- Decide whether legacy standalone admin pages should be retired or kept as read-only fallbacks:
  - `pages/admin/record_match_result.php`
  - `pages/admin/view_match_details.php`
  - `pages/admin/manage_blogs.php`
  - image upload/admin legacy pages
- Decide how guest-only tournament entrants should be reconciled if they later create a full site account/profile.
- Document SMTP deployment settings in one place and confirm production-safe defaults.

## Product/UX Follow-Up
- Replace placeholder logos/photos where still pending.
- Review broken or placeholder anchor targets across public pages.
- Improve error/success messaging for profile, membership, tournament registration, and community flows.
- Improve the tournament hub registration UX further if manual QA still shows confusion around guest vs signed-in registration behavior.
- Continue polishing tournament admin density and readability if manual QA still shows cramped layouts on smaller screens.
- Redesign the public pages so `tournaments`, `profile`, `blog`, `gallery`, `login`, `signup`, and `register` visually match the homepage language.
- Redesign the admin area into a more coherent operations console once the remaining runtime issues are closed.

## Deferred
- Full `DoubleElimination` implementation with a real losers bracket engine.
- Framework migration or frontend/backend split.
- Realtime sockets/push for live scoring.
