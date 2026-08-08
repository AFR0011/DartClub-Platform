# Security Policy

Dart Club Website is a legacy PHP/MySQL club-management application maintained as an engineering portfolio project. It includes authentication, role-based administration, membership documents, tournament data, community content, and local file uploads. Those features make deployment security materially more important than the age or simplicity of the stack might suggest.

## Supported deployment boundary

The repository is intended for local development, demonstration, and controlled deployment behind a properly configured PHP-capable web server and database.

Do not expose the application directly to the public Internet without reviewing:

- TLS termination and host/origin configuration;
- database credentials and network access;
- Apache or equivalent private-upload denial rules;
- filesystem permissions and upload quotas;
- SMTP credentials and sender policy;
- production PHP error/display settings;
- seed/demo accounts;
- backups and retention for membership/application documents.

## Database secrets

Database credentials are supplied through environment variables or the ignored `services/config.local.php` file. The committed configuration does not contain a usable production password, and production refuses an empty database password.

Never commit real values for database or SMTP credentials.

## Sessions and authentication

The shared bootstrap enables strict cookie-only PHP sessions and configures:

- HttpOnly session cookies;
- SameSite=Lax;
- Secure cookies in production/HTTPS;
- session identifier regeneration after successful login;
- explicit cookie clearing on logout.

Passwords are stored using PHP password hashes. The local SQL fixture keeps known QA passwords only as bcrypt hashes.

The login service retains migration compatibility for historical plaintext/MD5 rows so an old development database can be upgraded to bcrypt after a successful verified login. New passwords must not be stored in legacy formats.

## State-changing requests

In production, PHP service requests using `POST`, `PUT`, `PATCH`, or `DELETE` must carry a same-origin `Origin` header matching the application host. SameSite=Lax cookies provide an additional browser boundary.

This is a centralized legacy-app CSRF defense, not a claim that the application implements per-form synchronizer tokens. Deployments that add cross-origin clients or APIs must redesign this boundary rather than disabling it casually.

## Membership documents

Membership applications may contain private personal information.

The maintained release:

- stores uploaded membership documents under `files/applications/membership/`;
- blocks direct Apache access to that directory with `.htaccess`;
- validates file size, extension, server-detected MIME type, and DOCX structure when available;
- exposes documents only through a manager/admin-authorized PHP download endpoint;
- validates stored paths remain inside the membership directory;
- returns `Cache-Control: private, no-store`;
- removes newly moved files if the database transaction fails.

If the application is deployed behind Nginx, Caddy, IIS, or another web server, equivalent direct-access denial is required. Do not assume Apache `.htaccess` semantics magically follow the files to another server. Computers remain disappointingly literal.

## Blog and gallery content

Blog content is rich HTML, so stored XSS is treated as a security boundary. The maintained release sanitizes blog HTML through a DOM-based tag/attribute allowlist on both write and read. Legacy stored rows therefore pass through the sanitizer before being returned.

Link/image protocols are restricted, event/style attributes are removed, and `_blank` links receive `noopener noreferrer`.

Blog/gallery image uploads use server-detected MIME types and randomized filenames.

## Error disclosure

Service exceptions pass through a shared safe-error helper. Development can expose useful diagnostic text, while production returns generic failures instead of database/runtime exception details.

## Email credentials and temporary passwords

PHPMailer configuration comes from environment variables. Player-account emails do not contain temporary passwords. Temporary credentials must be delivered through a separate trusted channel.

## Dependency security

Composer dependencies are installed from `composer.lock`; generated `vendor/` files are not committed. CI validates Composer metadata, installs the lockfile, and runs `composer audit`.

The maintained dependency set currently uses PHPMailer 6.12.x.

## Historical repository boundary

The current working tree has been hardened and cleaned, but historical Git objects remain immutable unless history is deliberately rewritten. Before public visibility, old commits should be treated as a separate review surface for removed config files, credentials, application uploads, generated vendor code, and development transcripts.

For that reason the recommended recruiter-facing publication path is a clean-history public repository created from the maintained source tree, while preserving the original private repository as development history.

## Reporting

Report security issues privately to the repository owner. Do not publish membership documents, credentials, personal data, exploit payloads, or database dumps in a public issue.
