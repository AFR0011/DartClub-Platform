# VERSION_LOG

## Pre-Mapped Legacy Snapshot
- Date: before 2026-03-27 mapping pass
- Status: imported legacy website snapshot with no usable repo history in the current checkout
- Characteristics:
  - mixed DB credentials across pages/services
  - request-path schema mutation via `ALTER TABLE`
  - partial group/double-elimination tournament logic
  - oversized tournament admin page
  - mixed player identity assumptions
- Evidence:
  - `README.md`
  - `progress.md`
  - `todolist.md`
  - `tournament-errors.md`

## Mapping And Tournament Stabilization Pass
- Date: 2026-03-27
- Status: active cleanup milestone
- Main changes:
  - initialized local git metadata for the working tree
  - added repo docs pack and repo-local Codex config
  - added a next-session manual testing checklist
  - added shared PHP bootstrap/config path
  - added shared player/tournament helper layers
  - removed request-path schema mutation from active handlers
  - removed active `DoubleElimination` creation support
  - aligned group tournament contracts on `group_count` and `advancers_per_group`
  - rewrote the main tournament admin pages around the consolidated helpers
  - updated `dart_club.sql` to match expected schema
- Verification in this pass:
  - code/structure inspection
  - credential/bootstrap consolidation checks
  - pattern scans for `ALTER TABLE` and scattered `new mysqli(...)`
- Still pending:
  - PHP linting
  - XAMPP/MariaDB smoke tests
  - full tournament flow regression run

## Runtime Verification And Auth Hardening Pass
- Date: 2026-03-30
- Status: runtime-tested against local XAMPP MariaDB + PHP
- Main changes:
  - fixed `dart_club.sql` import failure in the `players` table definition
  - fixed broken session bootstrap across multiple services
  - added service-level auth checks to tournament create/update handlers
  - ran live HTTP validation for auth, profile, tournament registration, admin pages, and tournament service endpoints
  - ran tournament integration checks for league standings, group promotion, elimination progression, odd-player byes, and 128-player bracket shape
- Verification in this pass:
  - fresh DB import
  - full PHP lint via XAMPP PHP CLI
  - public/admin HTTP smoke checks
  - tournament helper and service-layer checks
- Still pending:
  - Apache visual QA
  - player creation/email flow
  - gallery/blog write flows
  - full 128-player round-by-round completion

## Productization And Public Platform Pass
- Date: 2026-03-30
- Status: core product flows implemented and runtime-tested
- Main changes:
  - added explicit `Round Robin` support and corrected tournament-type semantics
  - implemented tournament lifecycle fields, public visibility, and archive read-only behavior
  - added public tournament hub and public tournament detail page
  - added player dashboard and profile-save flow
  - added membership submission/review workflow with `membership_status` separate from auth role
  - rebuilt blog/community flows around drafts, moderation, comments, likes, and blog-image gallery insertion
  - added team-mode `Group` tournaments with team rosters and team fixtures
  - fixed a team-standings aggregation bug discovered during runtime verification
- Verification in this pass:
  - fresh DB import from the rebuilt SQL dump
  - PHP lint through XAMPP PHP CLI
  - HTTP smoke checks for public, auth, membership, blog, gallery, and admin surfaces
  - helper and service verification for `Round Robin`, `League`, `Group`, and `Elimination`
  - membership submit/approve flow
  - blog draft/publish/comment/like flow
  - gallery auto-insert from blog images
  - archive read-only enforcement
- Still pending:
  - Apache/responsive visual QA
  - manual gallery upload/delete pass
  - manual blog delete/moderation pass
  - `create_player.php` plus SMTP/email verification
  - uneven team/group-distribution runtime coverage
  - full 128-player end-to-end completion

## Full Testing And Debugging Pass
- Date: 2026-03-30
- Status: core product flows re-tested and debug-hardened
- Main changes:
  - fixed `pages/admin/manage_tournaments.php` so it loads the tournament lifecycle helper
  - rebuilt the legacy `create_player.php` endpoint onto the shared bootstrap/auth stack
  - fixed blog/gallery shared-media deletion so removing a gallery row does not break blog posts
  - added small UI polish on the gallery/blog/profile pages based on runtime findings
- Verification in this pass:
  - repo-wide PHP lint
  - no-inline-warning sweep across the main public/admin entrypoints
  - admin gallery upload/delete flow
  - blog publish/comment/delete flow
  - admin `create_player.php` flow and unauthenticated blocking
  - uneven player-group and uneven team-allocation tournaments
  - full 128-player elimination completion
  - admin user-management role-change and self-delete guard checks
- Still pending:
  - Apache/responsive visual QA
  - SMTP/email delivery verification
  - final browser click-through polish
