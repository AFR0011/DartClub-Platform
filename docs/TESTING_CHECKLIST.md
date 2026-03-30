# TESTING_CHECKLIST

## Purpose
Use this checklist to determine whether the current app works in a real local runtime and whether the productized tournament, membership, and community flows are behaving correctly.

## Latest Run - 2026-03-30
- Environment used:
  - MariaDB from XAMPP at `C:\Users\Ali\xampp\mysql\bin\mysql.exe`
  - PHP CLI from XAMPP at `C:\Users\Ali\xampp\php\php.exe`
  - local PHP server at `http://127.0.0.1:8090`
  - fresh DB import from `dart_club.sql`
- Passed in this run:
  - `dart_club.sql` imported successfully after schema cleanup
  - full PHP syntax lint passed via XAMPP PHP CLI
  - public pages loaded:
    - `pages/main.php`
    - `pages/tournaments.html`
    - `pages/blog.html`
    - `pages/gallery.html`
    - `pages/profile.html`
    - `pages/register.html`
  - public/auth services worked:
    - signup
    - login
    - profile save and dashboard fetch through `players.user_id`
    - membership submission
    - `get_tournaments.php`
    - `get_tournament_details.php`
    - `get_my_tournaments.php`
    - open registration
    - duplicate-registration block
    - closed-registration block
    - blog draft creation
    - blog publish moderation
    - blog comment creation
    - blog like toggle
    - gallery auto-insertion from blog images
  - admin pages loaded:
    - `pages/admin/admin_panel.php`
    - `pages/admin/manage_tournaments.php`
    - `pages/admin/show_tournament_details.php`
    - `pages/admin/manage_players.php`
    - `pages/admin/manage_users.php`
    - `pages/admin/manage_blogs.php`
    - `pages/admin/image_upload.php`
    - `pages/admin/record_match_result.php`
    - `pages/admin/view_match_details.php`
  - tournament helper/runtime coverage passed:
    - `Round Robin` generation and standings payload
    - `League` group-stage generation, standings, and automatic knockout creation
    - `Group` team generation, team match save, and team standings update
    - `Elimination` progression for 5 players
    - odd-player bye handling for 5 players
    - uneven player-group distribution
    - uneven team allocation
    - full 128-player completion
    - invalid tournament-setting rejection
    - archive read-only enforcement
  - tournament HTTP service coverage passed:
    - `create_tournament.php` for authenticated admin
    - `update_tournament.php` for authenticated admin
    - `match_create.php`
    - `match_get.php`
    - `match_update.php`
    - `match_delete.php`
    - `match_result.php`
    - `team_match_result.php`
    - `league_tools.php`
  - auth boundary checks passed after fixes:
    - unauthenticated admin-page access redirects to `../main.php`
    - unauthenticated `create_tournament.php` is blocked
    - unauthenticated `update_tournament.php` is blocked
    - unauthenticated `create_player.php` is blocked
  - membership/community checks passed:
    - membership application submit/approve
    - approved-member draft posting
    - manager/admin publish flow
    - authenticated comments and likes
    - blog delete flow
    - gallery image creation from blog uploads
    - gallery upload/delete flow
    - removing a blog-linked image from the gallery no longer breaks the blog post image
  - admin user-management checks passed:
    - `get_users.php`
    - `update_user_role.php`
    - self-delete guard in `delete_user.php`
  - page sweep passed:
    - no inline PHP warnings/fatals across the audited public/admin entrypoints
- Failed first, then fixed in the same run:
  - `dart_club.sql` import failed because of a trailing comma in the `players` table definition
  - login/signup-related services used `if (!session_status()) session_start();`, which never started a session in normal PHP flow
  - `create_tournament.php` and `update_tournament.php` lacked service-level role checks even though the admin pages were protected
  - team standings were double-counting completed matches because the aggregate query joined roster rows and team-match rows together
  - `pages/admin/manage_tournaments.php` called `tournament_refresh_lifecycle()` without loading the helper file
  - `create_player.php` used a broken PHPMailer include path and allowed unauthenticated POSTs
  - deleting a gallery entry that came from a blog post deleted the shared image file and broke the blog post
- Still pending manual follow-up:
  - visual QA under Apache/XAMPP rather than only the PHP built-in server
  - `create_player.php` end to end, including generated credentials/email behavior
  - explicit manual click-through of all admin nav links and asset rendering
  - responsive/mobile review

## Before You Start
1. Confirm Apache and MariaDB are available in XAMPP or your equivalent local stack.
2. Confirm the site root points at this repo and the app loads from a browser.
3. Open `services/config.php` and confirm the DB settings or environment overrides match your local MariaDB setup.
4. Create a fresh database named `dart_club` unless you intentionally use a different name and matching overrides.
5. Import `dart_club.sql` into MariaDB.
6. If the import fails, stop and capture the exact SQL error before changing code.

## Baseline Smoke Check
1. Open the homepage and confirm CSS, images, and navigation render.
2. Open the public tournaments page and confirm the list loads without PHP warnings or fatal errors.
3. Open at least one public tournament detail page and confirm the view renders.
4. Open the blog page, gallery page, and membership page and confirm they load.
5. Open signup and create a fresh non-admin user.
6. Log out, then log back in with the same account.
7. Open the profile page and confirm it loads without relying on username-based joins.
8. Open the "my tournaments" area and confirm it loads even if empty.

## Player/Admin Setup
1. Sign in as an admin or manager account.
2. Open player management and review at least one pending membership application.
3. Approve it and confirm the user becomes an approved member without changing `user_role`.
4. Create or approve at least 8 players with valid names.
5. Confirm the created players appear in the admin player list.
6. Confirm a player tied to a user account still resolves correctly in profile-related flows.

## Round Robin Tournament Check
1. Create a new `Round Robin` tournament from the admin tournament page.
2. Add at least 4 active players during creation.
3. Save the tournament and open its details page.
4. Confirm round-robin matches were generated automatically.
5. Record results for at least 3 matches.
6. Confirm standings update after each saved result.
7. Use the structure reschedule tool and confirm match dates update as expected.
8. Confirm there are no duplicate or orphaned matches after rescheduling.

## League Tournament Check
1. Create a new `League` tournament.
2. Use at least 8 active players.
3. Set `group_count` to `2`.
4. Set `advancers_per_group` to `2`.
5. Save the tournament and open its details page.
6. Confirm each player has a persisted `group_number`.
7. Confirm group-stage matches only pair players from the same group.
8. Record enough results to complete the group stage.
9. Confirm points and leg difference sort the group standings sensibly.
10. Confirm knockout matches are created automatically when the group stage finishes.
11. Confirm only the promoted players appear in the knockout stage.
12. Record a knockout result and confirm winners advance correctly.

## Group Tournament Check
1. Create a new `Group` tournament.
2. Use at least 8 active players.
3. Set `team_count` to `4`.
4. Save the tournament and open its details page.
5. Confirm the tournament creates teams inside the tournament automatically.
6. Confirm each team has a roster.
7. Confirm team fixtures are generated automatically.
8. Record at least 2 team match results.
9. Confirm team standings update with correct matches played, points, and leg difference.
10. Confirm the tournament status changes to `in_progress` after team results begin.

## Elimination Tournament Check
1. Create a new `Elimination` tournament.
2. Use 4, 5, or 8 active players.
3. Save the tournament and open its details page.
4. Confirm the initial bracket is created automatically.
5. If the player count is uneven, confirm byes advance deterministically.
6. Record results round by round.
7. Confirm winners propagate through `next_match_id` and `position_in_next`.
8. Confirm the final winner path is correct.

## Membership And Community Check
1. Log in as a normal signed-in player with no approved membership yet.
2. Open `pages/register.html`.
3. Submit a membership form.
4. Approve it from the admin membership/player screen.
5. Log back in as the approved member and open `pages/blog.html`.
6. Create a draft post with an image.
7. Confirm the post is saved as a draft when submitted by a non-manager member.
8. Publish it as a manager or admin.
9. Add a comment and a like from another authenticated account.
10. Confirm the blog image also appears in the gallery.

## Registration And Identity Check
1. Log in as a normal user linked to a player.
2. Open the public tournaments page.
3. Register for an open tournament.
4. Confirm duplicate registration is blocked cleanly.
5. Confirm closed, archived, or ended tournaments do not allow new registration.
6. Return to admin tournament details and confirm the user appears as roster-registered, not silently inserted into active matches.

## Archive Check
1. Complete a tournament or use a known completed seeded tournament.
2. Archive it through the admin flow or direct admin action used by the current session.
3. Confirm it still appears in public tournament history / old tournaments.
4. Confirm its public detail page still renders.
5. Attempt to edit a match or tournament setting as an admin.
6. Confirm the mutation is blocked with a read-only/archive message.

## Legacy/Admin Page Regression Check
1. Open `pages/admin/manage_blogs.php` through the app and confirm the bridge still works.
2. Open `pages/admin/record_match_result.php` and `pages/admin/view_match_details.php` only as fallback admin screens and confirm they still load without fatal errors.
3. Click the main admin navigation items and confirm there are no broken links.
4. Spot-check image and file paths on public pages for missing assets.
5. Open `pages/admin/manage_tournaments.php` and confirm it renders without inline PHP warnings.

## Failure Logging Rule
1. For each failure, capture:
   - page URL
   - user role used
   - exact action taken
   - exact error text
   - whether it is reproducible
2. Add confirmed failures to `docs/LESSONS.md` if they expose a repo-specific trap.
3. Add remaining fix work to `docs/MIGRATION_BACKLOG.md`.

## Exit Criteria
- The app loads without fatal PHP errors on public and admin entrypoints.
- Signup, login, profile, membership, and my-tournaments all work.
- `Round Robin`, `League`, `Group`, and `Elimination` tournaments can be created and managed end to end.
- League knockout creation happens automatically after the group stage completes.
- Team-mode standings update correctly after team match results.
- Match results update standings or advance winners correctly.
- Public registration behavior is clear and does not corrupt the active bracket.
- Archived tournaments remain public and read-only.
- The latest run summary above has no unresolved blocker in the core product stack.
