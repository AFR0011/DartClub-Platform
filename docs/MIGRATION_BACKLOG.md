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
- Clarified the public tournament-detail page so the main-website bracket is a dedicated section instead of being buried under a mixed fixtures heading.
- Tightened the public tournament-detail bracket into compact clickable matchup nodes with a single selected-match detail card.
- Polished the public blog/gallery controls so their buttons and text/file inputs match the newer site styling more closely.
- Added a safe public player-profile route and linked it from tournament participant rows and selected bracket-detail cards.
- Compressed the admin connected bracket into compact matchup selectors with a single selected detail card below the bracket.
- Added editable registration-close handling plus a one-click admin `Start Tournament` flow from the tournament-detail page.
- Fixed membership document URLs so admin review screens stop requesting broken `/pages/files/...` paths, and improved DOC/DOCX handling there.
- Aligned manage-users and membership-review action styling so role/membership state and destructive actions read more consistently.
- Added filter/sort controls to the remaining admin player/tournament roster lists instead of leaving them as raw static tables.
- Changed membership-review actions to collapse into a compact dropdown on smaller screens while preserving full inline controls on larger screens.
- Switched the admin knockout bracket from below-the-bracket detail cards to a focused modal workflow for score entry and schedule edits.
- Increased connected-bracket spacing and tightened matchup-card sizing to reduce overlap in the admin bracket layout.
- Restyled tournament section toggles plus match-dialog schedule/time inputs so they align better with the shared admin UI language.
- Rebuilt the blog page into a clearer preview-rail plus full-article workflow with a dedicated composer dialog.
- Added multi-image blog post support instead of keeping blog authoring limited to a single image.
- Restored the gallery to a stable four-column desktop card layout and added linked-post navigation for blog-originated images.
- Softened tournament admin updates so common match/bracket mutations preserve the current section instead of hard-resetting the page.
- Expanded `docs/TESTING_CHECKLIST.md` into a fuller page-by-page QA runbook.
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
    - admin start-tournament flow
    - bracket drag-and-drop under a real browser pointer/touch session
    - compact match-score entry flow under real browser interaction
    - connected bracket spacing/readability under real browser rendering
    - modal-first bracket detail flow under real browser rendering
    - compact public bracket click-to-expand readability under real browser rendering
    - responsive membership-review action dropdown behavior
    - tournament roster filter/sort behavior on create/edit pages
    - public player-profile navigation from tournament pages
    - preview-rail blog reading flow and multi-image post creation
    - gallery linked-post lightbox flow

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
- Keep tightening the admin bracket/modal workflow if manual QA still finds the new dialog flow or spacing unclear.
- Continue polishing public media pages if manual QA still shows any raw/default-looking controls after the latest blog/gallery styling pass.
- Continue refining the new blog/news workflow if manual QA still shows confusion between preview browsing and full-post reading.
- Decide whether public player profiles should remain tournament-history-only or expose any additional fields.
- Redesign the public pages so `tournaments`, `profile`, `blog`, `gallery`, `login`, `signup`, and `register` visually match the homepage language.
- Redesign the admin area into a more coherent operations console once the remaining runtime issues are closed.

## Deferred
- Full `DoubleElimination` implementation with a real losers bracket engine.
- Framework migration or frontend/backend split.
- Realtime sockets/push for live scoring.
