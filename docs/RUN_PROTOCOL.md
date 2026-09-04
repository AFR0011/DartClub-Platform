# Run protocol

## Level 0 — static and dependency checks

```bash
composer validate --strict
composer install --no-interaction --prefer-dist
composer audit --no-interaction
python3 scripts/publication_guard.py
php scripts/security_smoke.php
```

Lint every non-vendor PHP file and run `node --check` for maintained JavaScript.
Also parse Actions YAML, run `git diff --check`, and confirm no tracked generated
dependency, upload, log, cache, agent/editor state, credential, or private data.

## Level 1 — fresh database integration

Use PHP 8.3+ with `mysqli`, `mbstring`, `fileinfo`, `zip`, and `dom`, plus a
fresh MySQL/MariaDB database imported from `dart_club.sql`.

Export `APP_DB_HOST`, `APP_DB_PORT`, `APP_DB_NAME`, `APP_DB_USER`, and
`APP_DB_PASS`, then run:

```bash
php tests/prepare_integration_fixture.php
php tests/tournament_contracts.php
python3 tests/http_integration.py
```

The HTTP suite starts temporary loopback PHP servers. It proves:

- login sets and reuses only `dart_club_session`;
- guest/player calls cannot list users or change roles;
- an administrator can list users and change an auth role;
- a role change leaves membership state unchanged;
- membership review leaves auth role unchanged;
- guest membership-document download is denied;
- production database failures return generic JSON rather than internals.

The tournament suite runs inside a rolled-back transaction and proves:

- the five-format registry;
- eight-player Round Robin fixture count and standings update;
- League group fixture count and knockout promotion;
- two-team Group assignment and cross-team player fixtures;
- single-elimination bracket/third-place structure;
- double-elimination segment creation and winner/loser propagation.

GitHub Actions is the canonical reproducible database environment.

## Level 2 — focused browser review

With the fresh fixture and a local server, inspect at desktop and mobile width:

- homepage/navigation and the README screenshot viewport;
- login and admin user table;
- tournament list/detail and bracket filters;
- membership review controls;
- blog/gallery loading and missing-media handling.

Record the browser, viewport, commit, and exact paths. Browser review does not
replace Level 1 contracts.

## Level 3 — deployment-specific checks

Only when preparing a real controlled deployment:

- verify TLS, host/origin behavior, production errors, and seed-account removal;
- verify direct membership-file denial under the chosen web server;
- verify writable upload paths, quotas, backups, and retention;
- verify SMTP credentials and delivery separately;
- repeat critical role/membership/tournament flows with synthetic data.

## Reporting rule

Separate automated, browser-manual, source-inspected, and unavailable evidence.
Do not infer runtime correctness from syntax, or production readiness from a
loopback test.
