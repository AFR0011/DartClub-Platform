# Repository map

## Entry points

- `index.php`: redirects the web root to the public homepage
- `pages/main.php`: homepage
- `pages/about.html`: project/club context
- `pages/login.html`, `pages/sign_up.html`, `pages/reset_password.html`: auth and
  intentional administrator/support recovery boundary
- `pages/profile.html`, `pages/player_profile.php`: private/public player views
- `pages/register.html`: membership submission
- `pages/tournaments.html`, `pages/tournament_details.php`: public tournament views
- `pages/blog.html`, `pages/gallery.html`: community content and cleared media
- `pages/admin/admin_panel.php`: administration entry
- `pages/admin/manage_users.php`, `manage_players.php`,
  `manage_tournaments.php`, `show_tournament_details.php`: canonical admin flows

## Backend layers

- `services/config.php`: environment and ignored-local configuration
- `services/app_bootstrap.php`: session, DB, JSON, error, origin, and path helpers
- `services/dbConnection.php`: compatibility connection include
- `services/auth.php`: current identity and role/membership capabilities
- `services/shared/player_helpers.php`: canonical user-to-player mapping
- `services/shared/tournament_helpers.php`: creation, structure, standings,
  propagation, lifecycle, placements, and read models
- `services/shared/tournament_view_helpers.php`: bracket/view labels
- `services/shared/html_sanitizer.php`: rich-HTML allowlist
- `services/shared/mail_helpers.php`: best-effort PHPMailer boundary
- other `services/*.php`: HTTP handlers for auth, profiles, membership,
  tournaments/matches, blogs, comments/reactions, and gallery operations

## Data ownership

- `users`: auth identity, `user_role`, and separate `membership_status`
- `players`: profiles linked by `players.user_id -> users.user_id`
- `applications`, `membership_applications`: legacy/current membership records
- `tournaments`, `tournament_players`, `tournament_standings`: competition core
- `tournament_teams`, `tournament_team_players`: two-team Group rosters
- `matches`: individual, Group cross-team, and linked bracket fixtures
- `team_matches`: readable legacy Group fixtures only
- `match_legs`: optional leg details
- `blogs`, `blog_images`, `blog_comments`, `blog_reactions`: community content
- `gallery_images`: gallery metadata, including blog-linked media

`dart_club.sql` is the fresh-install schema and synthetic development fixture.
Request handlers must not mutate schema.

## Files and generated boundaries

- `files/media/images/`: cleared maintained UI/media assets
- `files/media/images/blog/`, `gallery/`: runtime image destinations
- `files/applications/membership/`: private submitted-document destination
- `other/memberform.docx`, `other/athleteform.docx`: public blank templates
- `vendor/`: generated Composer dependencies, ignored
- local config, generated uploads, logs, caches, and agent/editor state: ignored

## Verification surfaces

- `.github/workflows/ci.yml`: canonical PHP 8.3 + MariaDB release checks
- `scripts/security_smoke.php`: rich-content sanitizer contracts
- `scripts/publication_guard.py`: claims and repository-hygiene guard
- `scripts/repository_inventory.py`: tracked-tree footprint report
- `tests/prepare_integration_fixture.php`: deterministic synthetic HTTP fixture
- `tests/http_integration.py`: named-session, role, membership, and error contracts
- `tests/tournament_contracts.php`: all five tournament-format invariants

## High-risk change areas

- session/auth bootstrap and role checks;
- `user_role` versus `membership_status` separation;
- membership-file path/MIME/direct-access controls;
- blog sanitization and shared image deletion;
- tournament result propagation, League promotion, Group semantics, placements,
  and archive guards;
- schema changes and fresh import behavior.
