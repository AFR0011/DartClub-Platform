# LESSONS

## Request-Path Schema Mutation Is A Trap
- Do not hide schema drift behind live `ALTER TABLE ... IF NOT EXISTS` calls.
- Keep schema expectations in `dart_club.sql` and docs instead.
- If code needs a new column, update the SQL dump and document the verification step.

## Tournament Identity Must Flow Through `players.user_id`
- Public/profile/tournament reads should not depend on username matching between `users` and `players`.
- Use `users.user_id -> players.user_id` as the canonical identity link.
- Treat `plr_username` as legacy profile data, not the steady-state join key.
