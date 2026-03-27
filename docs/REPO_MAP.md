# REPO_MAP

## Top-Level Layout
- `pages/`: public and admin PHP/HTML pages
- `services/`: backend request handlers plus shared helpers
- `css/`: site/admin styles
- `js/`: public/admin client-side logic
- `files/`: media assets and uploaded/static image content
- `other/`: document forms
- `vendor/`: Composer dependencies
- `dart_club.sql`: expected database schema dump
- `README.md`, `progress.md`, `todolist.md`, `tournament-errors.md`: historical migration notes and backlog inputs

## Public Surfaces
- `pages/main.php`
  - public landing page
- `pages/tournaments.html`
  - calls `services/get_tournaments.php`
  - calls `services/register_tournament.php`
- `pages/profile.html`
  - calls `services/get_profile.php`
  - calls `services/get_my_tournaments.php`
- `pages/blog.html`
  - uses blog APIs
- `pages/gallery.html`
  - uses gallery APIs
- `pages/login.html`, `pages/sign_up.html`, `pages/register.html`, `pages/reset_password.html`
  - auth/onboarding surfaces

## Admin Surfaces
- `pages/admin/manage_tournaments.php`
  - canonical tournament creation/list page
  - posts to `services/create_tournament.php`
- `pages/admin/show_tournament_details.php`
  - canonical tournament management screen
  - posts to `services/update_tournament.php`
  - uses:
    - `services/match_create.php`
    - `services/match_get.php`
    - `services/match_update.php`
    - `services/match_delete.php`
    - `services/match_result.php`
    - `services/group_promote.php`
    - `services/league_tools.php`
  - client behavior now lives in `js/admin_tournament_details.js`
- `pages/admin/manage_players.php`
  - application review and player creation flow
  - posts to `services/create_player.php`
- `pages/admin/manage_users.php`
  - user role management
- `pages/admin/manage_blogs.php`
  - legacy blog admin page
- `pages/admin/record_match_result.php`, `pages/admin/view_match_details.php`
  - legacy match detail/result pages

## Shared Backend Layers
- `services/config.php`
  - canonical DB env/default config
- `services/app_bootstrap.php`
  - session, DB connection, JSON helpers
- `services/dbConnection.php`
  - compatibility include that exposes `$conn`
- `services/shared/player_helpers.php`
  - `users.user_id -> players.user_id` lookups
- `services/shared/tournament_helpers.php`
  - tournament creation
  - roster attachment
  - group assignment
  - round-robin scheduling
  - elimination bracket generation
  - standings updates
  - group promotion
  - tournament page data loading
- `services/shared/tournament_view_helpers.php`
  - status and display-label helpers for admin rendering

## Active Data Model
- `users`
  - auth identity and roles
- `players`
  - player profile data
  - now expected to use `user_id` as the canonical link to `users`
- `applications`
  - player application uploads
- `tournaments`
  - tournament metadata
  - now expected to include `group_count` and `advancers_per_group`
- `tournament_players`
  - tournament roster and player status
  - now expected to include `group_number`
- `tournament_standings`
  - league/group-stage standings
- `matches`
  - scheduled/completed matches
  - now expected to include:
    - `bracket`
    - `group_number`
    - `loser_next_match_id`
    - `loser_position_in_next`
- `match_legs`
  - per-leg detail
- `blogs`
  - blog content plus author metadata
- `media`
  - gallery/media records

## Current Flow Ownership
- Auth:
  - `services/signup.php`
  - `services/login.php`
  - `services/logout.php`
  - `services/auth.php`
- Tournament public reads:
  - `services/get_tournaments.php`
  - `services/get_my_tournaments.php`
- Tournament admin write path:
  - `services/create_tournament.php`
  - `services/update_tournament.php`
  - shared tournament helper layer
- Match operations:
  - `services/match_create.php`
  - `services/match_get.php`
  - `services/match_update.php`
  - `services/match_delete.php`
  - `services/match_result.php`

## Static And Media Assets
- Main styles:
  - `css/style.css`
  - `css/admin_style.css`
- Active tournament admin JS:
  - `js/admin_tournament_details.js`
- Shared site/admin JS:
  - `js/behaviour.js`
  - `js/admin_nav.js`
  - `js/blog.js`
  - `js/upload.js`
- Media:
  - `files/media/images/`
  - `files/media/images/gallery/`
  - `files/media/images/profile/`

## Drift Fixed In This Pass
- Removed request-path schema mutation from active handlers.
- Removed mixed direct DB credentials from pages/services.
- Removed steady-state username-based identity resolution from public/profile/tournament reads.
- Removed `DoubleElimination` from the active create flow.
- Aligned admin UI input names with backend contract:
  - `group_count`
  - `advancers_per_group`

## Known Remaining Drift
- Some legacy admin pages still duplicate newer behavior.
- Public registration is roster-level, not automatic bracket inclusion.
- Historical notes in `progress.md` still describe superseded code paths.
