# LESSONS

## Request-Path Schema Mutation Is A Trap
- Do not hide schema drift behind live `ALTER TABLE ... IF NOT EXISTS` calls.
- Keep schema expectations in `dart_club.sql` and docs instead.
- If code needs a new column, update the SQL dump and document the verification step.

## Tournament Identity Must Flow Through `players.user_id`
- Public/profile/tournament reads should not depend on username matching between `users` and `players`.
- Use `users.user_id -> players.user_id` as the canonical identity link.
- Treat `plr_username` as legacy profile data, not the steady-state join key.

## `session_status()` Truthiness Is A Trap
- `if (!session_status()) session_start();` does not start a session in normal PHP request flow.
- Use `session_status() === PHP_SESSION_NONE` or `app_start_session()` instead.
- Treat login/signup/auth-adjacent services as high-risk whenever session bootstrap is edited.

## Service Auth Must Not Rely On The Page Layer
- Protecting `pages/admin/*.php` is not enough if the underlying `services/*.php` handler can still be posted to directly.
- Tournament create/update endpoints must enforce role checks themselves.
- When adding or refactoring admin actions, verify both:
  - the page guard
  - the service guard

## PowerShell SQL Import Needs A Pipe, Not `<`
- In this Windows workspace, PowerShell does not support shell-style `<` redirection for `mysql.exe`.
- Use `Get-Content -Raw 'dart_club.sql' | & 'mysql' -u root dart_club` for reproducible imports.

## Team Standings Cannot Aggregate Roster Rows And Match Rows Together
- In team-mode tournaments, joining `tournament_team_players` and `team_matches` in one aggregate query multiplies completed-match stats by roster size.
- Compute roster size separately from match-result aggregation, or aggregate team match stats in PHP/from a dedicated subquery.
- Treat team standings as high-risk whenever roster joins or team-fixture queries are edited.

## Blog And Gallery Images Share File Paths
- A gallery entry created from a blog image does not own that file exclusively.
- Removing the gallery row must not unlink the shared file while `blog_images` still references it.
- Treat blog/gallery media deletion as shared-resource cleanup, not single-table cleanup.
