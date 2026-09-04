# Project state

Last updated: 2026-09-04
State: portfolio finalization in verification

## Product boundary

DartClub-Platform is a maintained plain-PHP/MySQL portfolio application for
local demonstration and controlled deployment. It models public club pages plus
role-aware membership, community, player, and tournament administration. It is
not presented as a production Internet service or a modern framework project.

## Maintained application surface

- signup, login/logout, profile onboarding, and public player profiles;
- club membership application, review, approval/rejection, and protected download;
- blogs, comments, reactions, moderation, multi-image posts, and gallery views;
- player/user administration with auth role separate from membership state;
- tournament creation, registration, rosters, fixtures, results, standings,
  placements, lifecycle, archival, and public/admin bracket views;
- Round Robin, League, Group, Elimination, and Double Elimination formats;
- EN/TR support across major public and administration surfaces.

Detached blog/gallery bridge pages and standalone match-result/detail fallbacks
were removed from the maintained tree. Their evolution remains in Git history.

## Current engineering baseline

- shared configuration/bootstrap/session/JSON/error helpers;
- prepared statements across maintained data paths;
- service-level role enforcement;
- bcrypt password storage with bounded legacy-row migration;
- production same-origin checks for mutation requests;
- private membership-file authorization, confinement, MIME checks, and rollback;
- rich-HTML allowlist sanitization on write/read;
- randomized, MIME-validated image uploads;
- Composer lockfile, dependency audit, publication guard, and syntax CI;
- MariaDB-backed HTTP/session/membership and tournament contract tests.

## Ownership and provenance

Ali Farrokhnejad authored and maintains the application code. Morteza
Farrokhnejad and Nazife Dimililer provided non-code project support.

Checked-in fixture identities/data are synthetic or publication-consented.
Current photographs/logos are owner-created or cleared for public
redistribution. Blank DOCX membership forms are intentional public templates.
The historical short database value was disposable local-only and never reused.

## Verification state

Required release checks are defined in `docs/RUN_PROTOCOL.md`. Local PHP 8.4
syntax and sanitizer smoke checks pass. The MariaDB-backed contracts require the
public GitHub Actions environment before release closure.

Do not claim comprehensive browser/device, Apache, SMTP, or real-host validation.

## Known limitations

- manual administrator/support account recovery;
- no application-level rate limiter under the controlled-demo boundary;
- local uploads and Apache-specific direct-access denial;
- best-effort deployment-specific email;
- several large mixed-concern tournament files;
- no exhaustive browser or device suite;
- roughly 443 MiB historical repository footprint retained intentionally.

## Release gate

The repository remains active. Publish `v1.0.0-portfolio`, update GitHub
metadata/security controls, and delete merged branches only after exact-SHA
public CI and fresh-clone verification pass.
