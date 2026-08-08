# Publication Readiness

Dart Club Website is being prepared as a public engineering portfolio project. The public version should demonstrate the maintained application and the hardening work around a legacy PHP/MySQL codebase without presenting it as a commercial club-management product or a modern framework rewrite.

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
- [ ] Choose and add an explicit public source-code license.
- [ ] Complete an authenticated manual browser/database walkthrough after the final branch is installed in a disposable local environment.
- [ ] Use only synthetic/demo membership documents, user data, and credentials in public screenshots.

## Repository-history decision

GitHub reports a repository footprint much larger than the maintained working tree. CI inventory shows the current tracked tree at roughly 47 MiB, with most bytes coming from intentional gallery/demo imagery. The discrepancy is therefore historical Git data rather than current application code.

The recommended portfolio release path is:

1. keep this original repository private as the development archive;
2. use `publication/dart-club-release` as the canonical maintained source snapshot;
3. create a new public repository from that cleaned tree with fresh history;
4. retain the documentation that this is a maintained legacy PHP/MySQL application rather than implying it was newly built from scratch;
5. add the selected source-code license before public visibility.

A clean public history is a presentation and privacy choice, not an attempt to conceal authorship or development provenance.

## Supported portfolio claims

The repository can truthfully demonstrate:

- a substantial role-aware PHP/MySQL application rather than a toy CRUD demo;
- signup/login/profile onboarding and public player profiles;
- membership application submission, review, approval, and account creation;
- user/role/membership administration;
- tournament registration and several tournament formats;
- standings, fixtures, connected bracket progression, byes, group promotion, and team competition workflows;
- blog drafting/publishing, comments, reactions, rich content, and gallery integration;
- image/document upload boundaries;
- multilingual EN/TR user-interface work;
- legacy-code security hardening, dependency hygiene, CI, and manual regression discipline.

## Claims to avoid

Do not describe Dart Club Website as:

- a commercial or client deployment unless there is separate evidence of that;
- an enterprise-grade club-management SaaS;
- horizontally scalable or cloud-native;
- fully covered by browser/database automation;
- a password-reset-capable product;
- object-storage-backed or designed for untrusted public file hosting;
- an application whose historical Git repository has been exhaustively proven free of every old credential or personal artifact.

## Demo data and screenshots

Public screenshots should use local demonstration accounts and synthetic membership/player information. Do not use real membership documents, phone numbers, addresses, private email addresses, or live SMTP/database configuration.

The included gallery images appear to be intentional demo/site content and are retained in the maintained tree. Confirm you have the right to publish those images before copying them into a new public repository.

## License blocker

Composer is currently marked `proprietary` only to describe the private application unambiguously to Composer. It is **not** the final public licensing decision. A source-code license must be deliberately chosen and committed before a public repository is created.
