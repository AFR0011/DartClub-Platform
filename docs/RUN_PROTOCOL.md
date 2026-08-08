# RUN_PROTOCOL

## Purpose

Define the validation ladder for Dart Club Website. CI covers dependency/security/static checks, while database-backed browser behavior remains a manual local regression responsibility.

Use `docs/TESTING_CHECKLIST.md` for the detailed click path and this document for validation levels and reporting rules.

## Level 0 — Automated Publication Checks

Run for every maintained change:

```bash
composer validate --strict
composer install --no-interaction --prefer-dist
composer audit
python3 scripts/publication_guard.py
php scripts/security_smoke.php
```

Then lint PHP syntax across the maintained tree:

```bash
find . -path './vendor' -prune -o -name '*.php' -print0 | xargs -0 -n1 php -l
```

GitHub Actions runs the equivalent checks on PHP 8.3 with `mysqli`, `mbstring`, `fileinfo`, `zip`, and `dom` enabled.

## Level 1 — Schema and Bootstrap Validation

Use for database/config/service refactors.

Confirm active code uses:

- `services/config.php`
- `services/app_bootstrap.php`
- `services/dbConnection.php`

Confirm the schema contains the fields/tables expected by the maintained application, including:

- `membership_applications`
- `tournament_teams`
- `tournament_team_players`
- `team_matches`
- `blog_images`
- `blog_comments`
- `blog_reactions`
- `gallery_images`

Check that request paths do not mutate schema and that `user_role` remains distinct from `membership_status`.

## Level 2 — Local Browser / Database Smoke

Recommended environment:

- Apache/XAMPP or equivalent PHP-capable web server
- MySQL/MariaDB
- database imported from `dart_club.sql`
- local configuration copied from `services/config.local.example.php`

Install dependencies:

```bash
composer install
```

Import the database:

```bash
mysql -u root -p dart_club < dart_club.sql
```

For a lightweight non-Apache check:

```bash
php -S 127.0.0.1:8090 -t .
```

Use Apache/XAMPP for the complete membership-document `.htaccess` boundary.

Minimum smoke path:

- signup
- login/logout
- profile save
- membership submission and authorized admin review/download
- public tournament hub/detail
- blog and gallery pages
- player approval/creation
- admin tournament list/detail

## Level 3 — Tournament Regression

For tournament engine changes, exercise every supported format:

### Round Robin

- generate fixtures
- record several results
- verify standings update

### League

- create groups
- complete group stage
- verify group standings and promoted knockout structure

### Group

- verify two-team roster assignment
- generate cross-team player fixtures
- record results and verify team standings

### Elimination

- verify bracket links, byes, winner advancement, and placement/third-place behavior

### Double Elimination

- verify opening round
- winners and losers paths
- loser propagation
- third-place playoff
- grand final
- public/admin path filters

Also archive a completed tournament and verify public reads remain available while mutation services reject edits.

## Level 4 — Membership and Community Regression

- submit membership application as a signed-in player
- verify unsupported document types are rejected
- verify direct `/files/applications/membership/...` access is denied under Apache
- approve/reject as manager/admin using the authorized download route
- confirm membership changes do not silently change auth role
- create a blog draft as an approved member
- test rich HTML sanitization with safe formatting and hostile event/script payloads
- publish as manager/admin
- add/delete comments and reactions
- verify blog images appear in gallery as intended

## Reporting Rule

When reporting verification, separate:

- automated commands that passed;
- browser/database workflows that were manually exercised;
- behaviors only inspected in source;
- deployment assumptions not tested locally.

Do not report the project as runtime-verified merely because syntax/CI is green. A PHP file can be syntactically flawless while still making terrible decisions with a database, a tradition the ecosystem has maintained with impressive consistency.
