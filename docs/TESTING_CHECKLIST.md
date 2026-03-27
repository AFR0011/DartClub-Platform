# TESTING_CHECKLIST

## Purpose
Use this checklist at the start of the next session to determine whether the current app actually works in a real local runtime.

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
3. Open the blog page and gallery page and confirm they load.
4. Open signup and create a fresh non-admin user.
5. Log out, then log back in with the same account.
6. Open the profile page and confirm it loads without relying on username-based joins.
7. Open the "my tournaments" area and confirm it loads even if empty.

## Player/Admin Setup
1. Sign in as an admin or manager account.
2. Open player management and create or approve at least 8 players with valid names.
3. Confirm the created players appear in the admin player list.
4. Confirm a player tied to a user account still resolves correctly in profile-related flows.

## League Tournament Check
1. Create a new `League` tournament from the admin tournament page.
2. Add at least 4 active players during creation.
3. Save the tournament and open its details page.
4. Confirm round-robin matches were generated automatically.
5. Record results for at least 3 matches.
6. Confirm standings update after each saved result.
7. Use the league reschedule tool and confirm match dates update as expected.
8. Confirm there are no duplicate or orphaned matches after rescheduling.

## Group Tournament Check
1. Create a new `Group` tournament.
2. Use at least 8 active players.
3. Set `group_count` to `2`.
4. Set `advancers_per_group` to `2`.
5. Save the tournament and open its details page.
6. Confirm each player has a persisted `group_number`.
7. Confirm group-stage matches only pair players from the same group.
8. Record enough results to produce clear group standings.
9. Confirm points and leg difference sort the standings sensibly.
10. Run group promotion.
11. Confirm knockout matches are created from the promoted players only.
12. Confirm the bracket view appears and the promoted players advance correctly after saving results.

## Elimination Tournament Check
1. Create a new `Elimination` tournament.
2. Use 4 or 8 active players.
3. Save the tournament and open its details page.
4. Confirm the initial bracket is created automatically.
5. If the player count is uneven, confirm byes advance deterministically.
6. Record results round by round.
7. Confirm winners propagate through `next_match_id` and `position_in_next`.
8. Confirm the final winner path is correct.

## Registration And Identity Check
1. Log in as a normal user linked to a player.
2. Open the public tournaments page.
3. Register for an open tournament.
4. Confirm duplicate registration is blocked cleanly.
5. Confirm closed or ended tournaments do not allow new registration.
6. Return to admin tournament details and confirm the user appears as roster-registered, not silently inserted into active matches.

## Legacy/Admin Page Regression Check
1. Open `pages/admin/manage_blogs.php` through the app and confirm blog rows still load.
2. Open `pages/admin/record_match_result.php` and `pages/admin/view_match_details.php` only as fallback admin screens and confirm they still load without fatal errors.
3. Click the main admin navigation items and confirm there are no broken links.
4. Spot-check image and file paths on public pages for missing assets.

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
- Signup, login, profile, and my-tournaments all work.
- `League`, `Group`, and `Elimination` tournaments can be created and managed end to end.
- Group promotion produces a knockout stage from persisted group data.
- Match results update standings or advance winners correctly.
- Public registration behavior is clear and does not corrupt the active bracket.
