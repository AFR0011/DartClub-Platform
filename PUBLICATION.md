# Publication readiness

DartClub-Platform is published in place as a controlled-demo legacy PHP/MySQL
portfolio application. The same repository name and authentic development
history are intentional.

## Release gates

- [x] Source licensed under MIT.
- [x] Current photographs/logos confirmed owner-created or cleared for public
  redistribution.
- [x] Checked-in identities and personal-looking fields confirmed synthetic or
  publication-consented.
- [x] Blank membership forms confirmed intentional public download/submission
  templates.
- [x] Historical short database value confirmed disposable local-only and never
  reused for a real system.
- [x] Current runtime configuration contains no tracked usable database password.
- [x] Named sessions, login rotation, same-origin production mutation checks,
  password hashing, private document access, MIME validation, and rich-HTML
  sanitization are present.
- [x] Admin user listing/role mutation uses the canonical auth/session/error path.
- [x] Detached fallback pages and public identity/logo placeholders are removed.
- [x] Tool/editor state and generated dependencies are excluded from the
  maintained tree.
- [x] Current 6000×4000 photographs are replaced by web-sized equivalents.
- [ ] Public MariaDB-backed CI passes on the final release commit.
- [ ] A fresh public clone repeats the documented integration checks.
- [x] The final privacy-safe screenshot is captured from the reviewed tree and
  checked at desktop and mobile widths.
- [ ] Final repository metadata, security settings, tag, release, and merged-
  branch cleanup are read back successfully.

## History decision

The repository previously recommended a clean-history successor based on an
incorrect classification of two historical DOCX objects. Inspection established
that one is the public blank membership form and the other is an unrelated
document, not a completed private membership application. The owner also
confirmed the historical short database value was disposable and never reused.

Therefore the approved release preserves:

- the name `DartClub-Platform`;
- every pre-finalization commit;
- the normal Git graph and public URL.

No rebase, squash, force-push, replacement repository, or manufactured history
is part of this release. The approximately 443 MiB historical footprint remains
a known tradeoff.

## Verified scope

The release gate covers Composer/dependency health, PHP/JavaScript syntax,
sanitizer behavior, named-session HTTP authentication, admin role management,
membership authorization/status separation, fresh schema import, and synthetic
tournament contracts for all five maintained formats.

This evidence does not turn the project into a production Internet service. It
does not establish exhaustive browser/device behavior, SMTP delivery, non-Apache
private-file rules, rate limiting, backups, retention policy, or real-host
operations.

## Attribution and assets

Ali Farrokhnejad authored and maintains the application code. Morteza
Farrokhnejad and Nazife Dimililer provided non-code project support.

Current photographs/logos are owner-created or cleared for public
redistribution. Source-code licensing does not relicense third-party marks or
media. Public screenshots must use only the cleared fixture/media set and must
not show submitted membership documents, real credentials, or live service data.

## Release policy

Create `v1.0.0-portfolio` only after public CI and fresh-clone verification pass.
Attach no database dump, user upload, secret, binary dependency tree, or
unverified benchmark. Delete a branch only after its tip is proved reachable
from the final default branch.
