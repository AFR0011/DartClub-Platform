# Dart Club Website

Dart Club Website is a legacy plain-PHP/MySQL web application for club membership, community publishing, player profiles, tournament administration, registration, standings, and connected bracket workflows.

The project is maintained as an engineering portfolio artifact rather than presented as a modern framework rewrite. Its value is the breadth of application behavior implemented in a deliberately simple stack: role-aware authentication, membership review, multiple tournament formats, bracket progression, admin workflows, community content, file handling, and incremental security hardening around an older codebase.

## Implemented product surface

### Public and player experience

- Club home page and responsive navigation.
- Signup, login/logout, and player-profile onboarding.
- Public player profiles.
- Tournament discovery, details, registration, fixtures, standings, and bracket views.
- Membership application upload and status workflow.
- Blog feed, draft/publish workflow, comments, reactions, and linked gallery images.
- Standalone gallery browsing and lightbox behavior.
- English/Turkish interface support across major screens.

### Administration

- User, role, player, and membership management.
- Membership application approval/rejection with reviewer notes.
- Player-account creation after approval.
- Tournament creation and management.
- Match scheduling/results and bracket-board interaction.
- Gallery upload/removal and blog moderation.
- Tournament archival/read-only behavior.

### Tournament formats

The maintained application includes workflows for:

- Round Robin
- League / group-stage-to-knockout
- Group/team competition
- Single Elimination
- Double Elimination

Tournament services cover registration validation, roster management, standings, match result propagation, bracket advancement, byes, group promotion, and team/player fixture behavior depending on the format.

## Architecture

```text
Browser pages / admin console
        |
        v
Plain HTML/CSS/JavaScript + PHP pages
        |
        v
PHP service endpoints
  - auth / roles
  - membership
  - tournaments / matches
  - blogs / gallery
  - profiles
        |
        +--> shared bootstrap/config/security helpers
        +--> PHPMailer (optional best-effort SMTP)
        |
        v
MySQL / MariaDB
```

This is intentionally not described as a horizontally scalable or framework-managed application. Sessions, Apache/PHP behavior, database state, and local upload storage are part of the deployment boundary.

## Security hardening in the maintained release

The publication branch adds or verifies:

- environment/local-file database configuration with no tracked default DB password;
- strict cookie-only PHP sessions with HttpOnly, SameSite=Lax, and production/HTTPS Secure cookies;
- session-ID rotation after successful authentication;
- production-safe exception responses rather than raw database/runtime error disclosure;
- production same-origin enforcement for state-changing service requests;
- bcrypt password storage, including bcrypt-hashed local seed credentials;
- no temporary passwords sent by email;
- manager/admin-authorized membership document downloads rather than public file URLs;
- MIME/content validation and failed-transaction cleanup for membership uploads;
- direct Apache denial for the private membership-document directory;
- allowlist-based rich HTML sanitization on blog write **and read** to protect legacy rows from stored XSS;
- MIME validation and randomized filenames for gallery/blog image uploads;
- audited PHPMailer dependency installation through Composer;
- publication guards, sanitizer security smoke, and full PHP syntax verification in CI.

See [`SECURITY.md`](SECURITY.md) for the trust and deployment boundaries.

## Requirements

A typical local setup uses:

- PHP 8.3+ with `mysqli`, `mbstring`, `fileinfo`, `zip`, and `dom` extensions;
- Apache or another PHP-capable web server;
- MySQL/MariaDB;
- Composer 2.

CI verifies the maintained tree on PHP 8.3.

## Local setup

Install PHP dependencies from the committed lockfile:

```bash
composer install
```

Import the development schema/seed data:

```bash
mysql -u root -p dart_club < dart_club.sql
```

Copy the safe local configuration template:

```bash
cp services/config.local.example.php services/config.local.php
```

On PowerShell:

```powershell
Copy-Item services/config.local.example.php services/config.local.php
```

Edit the local file or set environment variables for your database. `services/config.local.php` is ignored by Git.

For a lightweight PHP development server:

```bash
php -S 127.0.0.1:8090 -t .
```

Apache/XAMPP or an equivalent local stack is still recommended for the complete upload/`.htaccess` behavior.

## Configuration

Database configuration can be supplied through the local override file or environment:

- `APP_ENV` (`development` or `production`)
- `APP_DB_HOST`
- `APP_DB_PORT`
- `APP_DB_NAME`
- `APP_DB_USER`
- `APP_DB_PASS`
- `APP_DB_CHARSET`

Production refuses an empty database password.

### Optional SMTP

Best-effort email notifications use PHPMailer when SMTP is configured:

- `SMTP_HOST`
- `SMTP_PORT`
- `SMTP_AUTH`
- `SMTP_USER`
- `SMTP_PASS`
- `SMTP_SECURE`
- `SMTP_FROM`

Player-account email does **not** contain the temporary password. Club management must deliver that credential through a separate trusted channel.

## Local seed accounts

`dart_club.sql` includes local-only demonstration accounts for the major roles. Their known demo passwords may be documented for local QA, but the SQL fixture stores those credentials as bcrypt hashes rather than plaintext values.

Do not reuse demo credentials in a real deployment. Replace or remove seeded accounts before deployment.

## Verification

GitHub Actions currently requires:

- `composer validate --strict`
- clean Composer security audit
- repository publication guard
- rich-HTML sanitizer security smoke
- PHP syntax validation across the maintained tree

Useful local helpers also live under `scripts/`; see [`docs/RUN_PROTOCOL.md`](docs/RUN_PROTOCOL.md) and [`docs/TESTING_CHECKLIST.md`](docs/TESTING_CHECKLIST.md) for the broader browser/database regression ladder.

## Repository footprint

The maintained working tree is roughly **47 MiB**, mostly intentional gallery/demo imagery. Generated Composer `vendor/` files and an exported Cursor development transcript were removed from the maintained branch.

The historical Git repository is much larger than the current tree because old objects remain in history. For a recruiter-facing public release, [`PUBLICATION.md`](PUBLICATION.md) recommends publishing the maintained tree with a clean modern history while preserving this private repository as the development archive.

## Known limitations

- Self-service password reset is not implemented; the reset page directs users to account-help/contact paths instead of pretending a backend reset flow exists.
- Email is best-effort and deployment-specific.
- Private membership documents currently rely on Apache `.htaccess` denial plus the authenticated PHP download endpoint; equivalent web-server rules are required when deploying outside Apache.
- Uploads live on local server storage rather than object storage.
- There is no automated full browser/database E2E environment in CI; the existing CI is static/security/dependency verification plus extensive manual regression documentation.
- The codebase remains a legacy server-rendered/plain-JavaScript application rather than a framework migration.
- The historical repository is substantially larger than the maintained source tree.

## Project documentation

- [`docs/PROJECT_STATE.md`](docs/PROJECT_STATE.md) — maintained feature/state snapshot.
- [`docs/REPO_MAP.md`](docs/REPO_MAP.md) — routes/services/data-flow map.
- [`docs/RUN_PROTOCOL.md`](docs/RUN_PROTOCOL.md) — verification ladder.
- [`docs/TESTING_CHECKLIST.md`](docs/TESTING_CHECKLIST.md) — detailed manual regression matrix.
- [`PUBLICATION.md`](PUBLICATION.md) — portfolio/release boundaries.
- [`SECURITY.md`](SECURITY.md) — security and disclosure policy.

## License

Dart Club Website is released under the MIT License. See [`LICENSE`](LICENSE).
