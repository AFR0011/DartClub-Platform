# PROJECT_STATE

## Metadata
- Project: `Dart Club`
- Last updated: 2026-03-30
- Repo type: legacy PHP/MySQL website
- Current repo status: mapped, documented, runtime-tested on XAMPP, and debug-hardened across the core tournament, membership, and community flows

## Current Objective
- Finish the migration from an ad-hoc legacy codebase to a maintainable public club platform.
- Keep the existing plain PHP/MySQL stack.
- Complete visual/manual QA and polish after the new tournament model, membership workflow, and public/community surfaces are in place.

## Current Technical Status
- The repo now has:
  - root `AGENTS.md`
  - repo-local `.codex/config.toml`
  - `docs/` operating docs
  - a shared PHP bootstrap/config path
  - shared tournament helper code
- The imported tree originally behaved like an untracked project snapshot. Git was initialized locally during the earlier mapping pass, but there is no prior upstream commit history in the current checkout.
- Frontend/public pages are largely present:
  - home/main
  - tournaments hub
  - tournament details
  - blog/community
  - gallery
  - profile dashboard
  - membership registration
- Backend maturity is now stronger in the core product lanes:
  - auth/signup/login now share one bootstrap/session path
  - tournament flows are consolidated behind shared helpers
  - membership, blog, gallery, and profile dashboard flows now have working service layers
  - visual polish and some legacy admin cleanup still remain

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
  - `Round Robin`
  - `League`
  - `Group`
  - `Elimination`
- Deferred tournament type:
  - `DoubleElimination`
- Current tournament semantics:
  - `Round Robin` = single-table round robin
  - `League` = group-stage plus knockout
  - `Group` = per-tournament team competition
  - `Elimination` = single-elimination bracket
- Current audience model:
  - guests
  - signed-in players
  - approved club members
  - managers/admins
- Current identity and permissions model:
  - `user_role` controls auth permissions
  - `membership_status` controls club-membership workflow
  - approved members can author blog drafts
  - managers/admins can publish and moderate

## What Changed In This Pass
- Rebuilt `dart_club.sql` around the productized data model:
  - membership state
  - membership applications
  - tournament lifecycle
  - team tournaments
  - blog/community tables
  - gallery records
- Removed request-path `ALTER TABLE` calls from active PHP handlers.
- Standardized DB access away from mixed `root` vs `dartadmin/1234` usage.
- Standardized session/bootstrap/auth handling through shared helpers.
- Added an explicit `Round Robin` format and corrected the overloaded tournament-type semantics.
- Moved tournament scheduling, progression, lifecycle refresh, and archive guards into shared helpers.
- Updated public/profile/tournament identity resolution to use `players.user_id`.
- Reworked tournament admin pages around canonical `group_count`, `advancers_per_group`, and `team_count` inputs.
- Added a public tournament hub and public tournament detail page.
- Added a real player dashboard and profile-save flow.
- Replaced the old application page with a membership submission/review flow.
- Rebuilt blog, comment, like, moderation, and gallery auto-insertion behavior.
- Fixed the SQL dump so a fresh import succeeds again.
- Fixed broken session bootstrap in multiple services by switching to `session_status() === PHP_SESSION_NONE`.
- Added service-level auth checks to admin mutation handlers.
- Fixed a live team-standings aggregation bug that double-counted completed matches.
- Fixed a missing helper include that caused `pages/admin/manage_tournaments.php` to fatal.
- Rebuilt the legacy `create_player.php` endpoint onto the current bootstrap/auth path and blocked unauthenticated access.
- Fixed shared-media cleanup so removing a gallery entry that originated from a blog post no longer breaks the blog image.

## Verified Repo Facts
- There is no framework scaffolding such as Laravel, Symfony, React build tooling, or Node package management in this repo.
- Composer is present only for PHPMailer.
- PHP CLI is not on PATH by default in this Codex environment, but it is available locally via `C:\Users\Ali\xampp\php\php.exe`.
- MariaDB is available locally via XAMPP and the repo can be exercised against a real imported database.
- The app ran successfully behind the PHP built-in server at `http://127.0.0.1:8090` against a fresh local import.
- Historical migration notes still exist in:
  - `README.md`
  - `progress.md`
  - `todolist.md`
  - `tournament-errors.md`

## Open Risks
- Visual/manual QA under Apache/XAMPP still needs a real click-through pass.
- `create_player.php` plus SMTP/email delivery has not been exercised end to end.
- Public registration still stores roster registration only; admins decide whether and when registrations become active competition entries.
- Final UI polish still needs a true browser/responsive pass on the public and admin shells.

## Remaining Priorities
- Use `docs/TESTING_CHECKLIST.md` as the next-session manual verification order.
- Finish the remaining manual/runtime items:
  - Apache visual QA
  - responsive/mobile review
  - SMTP-backed credential email behavior
  - visual/admin navigation click-through
- Clean up or retire remaining legacy admin pages once replacement coverage is confirmed.
- Standardize any remaining asset-path or presentation inconsistencies and document SMTP expectations clearly for deployment.

## Verification State
- Verified in this pass:
  - fresh DB import from `dart_club.sql`
  - PHP syntax lint via `C:\Users\Ali\xampp\php\php.exe -l`
  - public/auth/profile/membership/tournament/blog/gallery HTTP smoke checks
  - public tournament-detail payloads for `Round Robin`, `League`, `Group`, and `Elimination`
  - admin page loading for tournament, membership, user-management, and legacy fallback admin screens
  - tournament helper/runtime coverage for:
    - `Round Robin`
    - `League`
    - `Group`
    - `Elimination`
    - odd-player byes
    - uneven player-group distribution
    - uneven team allocation
    - full 128-player completion
  - tournament HTTP service coverage for:
    - create/update
    - match CRUD
    - match results
    - team match results
    - reschedule
    - archive read-only enforcement
  - membership submission and approval flow
  - member-authored blog draft creation
  - manager/admin blog publish flow
  - authenticated comments and likes
  - blog delete flow
  - automatic gallery insertion from blog images
  - gallery upload/delete, including blog-linked gallery removal without breaking the blog post
  - admin `create_player.php` flow and unauthenticated blocking
  - admin user role update and self-delete guard
  - broad HTML sweep for inline PHP warnings/fatals on key public/admin pages
  - unauthenticated access blocking for admin pages and admin mutation services
- Not verified in this pass:
  - Apache-backed visual QA
  - SMTP/email delivery
  - full manual click-through of all admin navigation/items under a real browser
- Next-session operator guide:
  - `docs/TESTING_CHECKLIST.md`
