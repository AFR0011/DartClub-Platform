# Publication Readiness

Dart Club Website is being prepared as a public engineering portfolio project showcasing the maintained legacy PHP/MySQL application, its tournament/community feature set, and the security-hardening work applied to the codebase.

## Required before public visibility

- [x] Remove tracked default database passwords and local machine-specific configuration from the maintained runtime path.
- [x] Harden PHP sessions and rotate the session identifier after login.
- [x] Stop emailing temporary player passwords.
- [x] Protect membership documents behind manager/admin authorization and block direct Apache access.
- [x] Validate membership document MIME/content and clean up failed uploads.
- [x] Replace plaintext local seed passwords with bcrypt hashes.
- [x] Route service exception details through the production-safe error boundary.
- [x] Add same-origin protection for state-changing production service requests.
- [x] Replace regex-only rich HTML filtering with allowlist DOM sanitization on blog write and read.
- [x] Upgrade/audit the maintained PHPMailer dependency and remove committed `vendor/` files.
- [x] Remove generated Cursor transcript material from the maintained tree.
- [x] Add publication/security CI and security smoke coverage.
- [x] Run a full-history object/path audit to decide whether in-place public visibility is safe.
- [x] License the source code under the MIT License.
- [x] Confirm redistribution rights for retained gallery/demo photography and project assets.
- [ ] Complete an authenticated manual browser/database walkthrough after the final branch is installed in a disposable local environment.
- [ ] Use only synthetic/demo membership documents, user data, and credentials in public screenshots.

## Repository-history decision

The full-history audit makes the release-path decision technical rather than stylistic.

Observed Git history:

- packed Git objects: approximately **432.69 MiB**;
- current maintained working tree: approximately **47 MiB**;
- historical membership uploads include two DOCX files, including one approximately **5.01 MiB** document;
- historical configuration contains the removed local database defaults `dartadmin` / `1234`;
- historical objects also contain a large batch of imported photography under `files/media/Other Images/...`, committed Composer `vendor/` files, and the removed `progress.md` development transcript.

The targeted credential-filename scan did not surface private key/credential files; it only matched the existing reset-password page. Because private membership documents definitely exist in history, **do not make this original repository public in place**.

The portfolio release path is:

1. keep this original repository private as the development archive;
2. use `publication/dart-club-release` as the canonical maintained source snapshot;
3. create a separate public repository from the cleaned current tree with fresh history;
4. copy the maintained project assets into that clean public tree;
5. retain the documentation that this is a maintained legacy PHP/MySQL application;
6. preserve the MIT license in the public repository.

A clean public history is a privacy boundary here, not merely a nicer commit graph.

## Engineering scope

The repository demonstrates:

- a substantial role-aware PHP/MySQL application;
- signup/login/profile onboarding and public player profiles;
- membership application submission, review, approval, and account creation;
- user/role/membership administration;
- tournament registration and several tournament formats;
- standings, fixtures, connected bracket progression, byes, group promotion, and team competition workflows;
- blog drafting/publishing, comments, reactions, rich content, and gallery integration;
- image/document upload boundaries;
- multilingual EN/TR user-interface work;
- legacy-code security hardening, dependency hygiene, CI, and manual regression discipline.

## Current boundaries

The maintained release uses local server uploads, Apache/PHP sessions, and MySQL/MariaDB rather than object storage or horizontally scaled infrastructure. Self-service password reset is not implemented, and full authenticated browser/database regression remains a manual verification step rather than a CI job.

## Demo data and screenshots

Public screenshots should use local demonstration accounts and synthetic membership/player information. Do not use real membership documents, phone numbers, addresses, private email addresses, or live SMTP/database configuration.

The retained gallery and demo assets are cleared for publication.

## License

Dart Club Website source code is released under the MIT License. See `LICENSE`.
