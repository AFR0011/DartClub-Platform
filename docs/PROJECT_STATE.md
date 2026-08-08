# PROJECT_STATE

Last updated: 2026-08-08

## Current Status

Dart Club Website is a maintained legacy PHP/MySQL application with substantial public, player, membership, community, tournament, and admin workflows. The current publication branch focuses on preserving that functionality while tightening configuration, session handling, private-document access, content sanitization, dependency hygiene, and release documentation.

This document records the current implementation rather than serving as an exhaustive chronological changelog.

## Application Surface

### Public / player

- home and shared navigation
- signup and login/logout
- player profile onboarding and public profiles
- tournament hub and tournament details
- tournament registration
- fixtures, standings, placements, and connected brackets
- membership application submission/status
- blog reader/composer workflow
- comments and reactions
- gallery and lightbox behavior
- EN/TR interface support across major public/admin surfaces

### Administration

- user/role management
- player registry
- membership review/approval/rejection
- player-account creation
- tournament creation, roster management, lifecycle controls, and archival
- match scheduling/results
- connected bracket and bracket-board controls
- blog moderation/publishing
- gallery upload/removal

## Identity and Permission Model

- `user_role` controls authorization.
- `membership_status` tracks club-membership state separately.
- approved members may author blog drafts.
- managers/admins may publish/moderate and review membership applications.
- admin-only behavior remains distinct from manager/player behavior where services enforce it.

## Tournament Engine

Supported formats:

- `Round Robin`
- `League`
- `Group`
- `Elimination`
- `Double Elimination`

The maintained helpers cover registration/rosters, fixture generation, standings, result propagation, bracket linkage, byes, group promotion, team behavior, loser-path propagation, third-place matches, and grand-final workflows as appropriate to each format.

## Persistence and File Model

- MySQL/MariaDB stores users, players, membership state, tournaments, matches, teams, blogs, comments, reactions, and gallery metadata.
- Local server storage holds gallery/blog images and membership documents.
- Membership documents are private application data and must not be served directly.
- Composer installs PHPMailer from the committed lockfile; `vendor/` is not part of maintained source control.

## Security Baseline

The publication branch now includes:

- environment/ignored-local database configuration with no tracked default password;
- production refusal of empty DB passwords;
- strict cookie-only PHP sessions;
- HttpOnly + SameSite=Lax cookies and Secure cookies in production/HTTPS;
- session-ID regeneration after login and cookie clearing on logout;
- production-safe service exception responses;
- production same-origin checks for unsafe HTTP methods;
- bcrypt password storage and bcrypt-hashed local seed credentials;
- no temporary-password delivery by email;
- manager/admin-authorized membership downloads;
- MIME/content validation and rollback cleanup for membership uploads;
- Apache denial for direct membership-document access;
- allowlist DOM sanitization for blog rich HTML on both write and read;
- MIME-validated randomized gallery/blog image uploads;
- audited Composer dependencies and PHPMailer 6.12.x;
- publication guard, sanitizer smoke tests, and PHP syntax CI.

See `SECURITY.md` for deployment boundaries.

## Local Seed Data

`dart_club.sql` contains demonstration users for the main authorization/membership states. Known local QA passwords may be documented for operators, but the SQL fixture stores password hashes rather than plaintext credentials.

Seed accounts are for local testing only and must be replaced or removed in any real deployment.

## Automated Verification

The maintained branch is checked with:

```text
composer validate --strict
composer install
composer audit
python3 scripts/publication_guard.py
php scripts/security_smoke.php
PHP syntax scan across the non-vendor tree
```

CI currently targets PHP 8.3 with `mysqli`, `mbstring`, `fileinfo`, `zip`, and `dom`.

## Manual Verification

Automated CI does not currently create a MariaDB/Apache browser environment. Before a portfolio/demo release, run the manual database/browser matrix in:

- `docs/RUN_PROTOCOL.md`
- `docs/TESTING_CHECKLIST.md`

Particular attention should go to:

- login/session behavior;
- membership upload/download privacy;
- every tournament format;
- bracket progression and responsive rendering;
- blog rich content/comments/reactions;
- gallery workflows;
- manager/admin permission boundaries;
- EN/TR admin/public navigation.

## Repository Hygiene

CI inventory measured the maintained tracked tree at roughly 47 MiB. Most bytes are intentional gallery/demo images. Generated Composer `vendor/` files and an exported Cursor conversation transcript have been removed from maintained source control.

The GitHub repository remains much larger historically because deleted/old objects still exist in Git history. A clean-history public portfolio copy is therefore preferred over making the original development archive public in place.

## Current Limitations

- No self-service password-reset backend.
- No object storage for uploads.
- No automated full database/browser E2E in CI.
- No claim of horizontal scalability or cloud-native deployment.
- Membership privacy depends on equivalent web-server denial rules when Apache `.htaccess` is not used.
- Email delivery is best-effort and deployment-specific.
- Gallery/image publication rights must be confirmed independently from source-code licensing.

## Publication Blockers

Before public portfolio visibility:

1. choose and add an explicit source-code license;
2. confirm rights to redistribute the retained gallery/demo imagery;
3. complete a final authenticated local browser/database walkthrough using synthetic data;
4. preferably publish the maintained tree into a fresh-history public repository while preserving this original repository privately as development history.
