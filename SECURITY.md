# Security policy

## Supported boundary

DartClub-Platform is maintained for local demonstration and controlled
deployment. It handles authentication, roles, membership documents, community
content, and file uploads, so it must not be exposed directly to the public
Internet without a separate deployment/security review.

An Internet-facing operator must address TLS and trusted origins, rate limiting,
tokenized account recovery, database/network credentials, web-server rules,
filesystem permissions and quotas, SMTP policy, demo accounts, backups, and
document retention.

## Configuration and secrets

Database and SMTP credentials belong in environment variables or the ignored
`services/config.local.php`. Production refuses an empty database password.
Never commit real credentials or reuse the synthetic CI/local fixture values.

A short database value exists only in historical development commits. The owner
confirmed it was disposable local-only data and was never reused for a real
system; authentic history is therefore retained.

## Sessions and authorization

The canonical bootstrap configures strict cookie-only sessions named
`dart_club_session`, HttpOnly cookies, SameSite=Lax, Secure cookies for
production/HTTPS, and session-ID regeneration after login.

Sensitive service handlers enforce authorization themselves. `user_role`
controls authorization; `membership_status` is a separate club workflow state.
Database-backed HTTP tests verify guest/player denial and administrator role
changes through the named session.

## Passwords and recovery

New and fixture passwords use PHP password hashes. The login service retains a
bounded migration path for historical plaintext/MD5 development rows and
rehashes them after a successful verified login. New legacy-format rows are not
supported practice.

Self-service password reset is intentionally not implemented. Controlled-demo
operators use an administrator/support recovery process. Temporary credentials
must be delivered through a separate trusted channel, never email body text.

## State-changing requests

Production mutation requests require an `Origin` host matching the application
host; SameSite=Lax cookies provide another browser boundary. This is a legacy-
application same-origin defense, not a claim of synchronizer-token CSRF.

## Membership documents

Submitted forms may contain private information even though the blank templates
are public. The maintained path validates size, extension, detected MIME, and
DOCX structure; stores randomized names; rolls back newly moved files on DB
failure; and exposes them only through a manager/admin endpoint with private,
no-store responses and path confinement.

Apache `.htaccess` denies direct access to the membership upload directory.
Nginx, Caddy, IIS, and other servers require an equivalent rule.

## Rich content and media

Blog HTML is sanitized on write and read with an allowlist. Unsafe tags,
attributes, event handlers, styles, and protocols are removed; `_blank` links
receive `noopener noreferrer`. Security smoke tests cover hostile examples and
UTF-8 preservation.

Gallery/blog uploads use detected MIME types and randomized filenames. Shared
blog/gallery files must not be removed while another record references them.

## Error disclosure and dependencies

Service exceptions pass through the shared JSON/error boundary. Production
returns generic failures; database/runtime details remain development-only.
Composer installs the committed lockfile and CI runs `composer audit`.

## Reporting

Report security issues privately to the repository owner. Do not include
credentials, membership documents, personal data, database dumps, or exploit
payloads in public issues.
