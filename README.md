# Dart Club Website

Legacy plain-PHP/MySQL club website for:
- tournament registration and bracket viewing
- player dashboards and public player profiles
- membership application review
- blog publishing and moderation
- gallery management

Use the docs pack for current repo truth:
- `docs/PROJECT_STATE.md`
- `docs/REPO_MAP.md`
- `docs/RUN_PROTOCOL.md`
- `docs/TESTING_CHECKLIST.md`
- `docs/FREE_DEPLOYMENT_GUIDE.md`

## Local Regression Helpers
- `scripts/seed_large_tournaments.php`
- `scripts/lint_php.ps1`
- `scripts/run_smoke_checks.ps1`

## Email (PHPMailer) Configuration

Backend services use PHPMailer for best-effort notifications when SMTP is configured.

Set the following environment variables to enable SMTP:
- `SMTP_HOST`
- `SMTP_PORT` (for example `587` or `465`)
- `SMTP_AUTH` (`1` to enable auth)
- `SMTP_USER`
- `SMTP_PASS`
- `SMTP_SECURE` (`tls` or `ssl` when required)
- `SMTP_FROM` (sender email, for example `noreply@yourdomain.com`)

If these variables are not set, the code falls back to localhost SMTP without authentication where possible.

### Active email use
- `services/create_player.php`
  - sends player account credentials as a best-effort message
- `services/register_tournament.php`
  - sends a best-effort registration confirmation only for signed-in users that already have an email address in `users`

Guest tournament registrations do not currently send email because the public registration flow does not collect an email address.

## Password Reset

Self-service password reset is not live in this release.
`pages/reset_password.html` is now an account-help page that directs users back to sign-in and club contact channels instead of pretending a backend reset flow exists.
