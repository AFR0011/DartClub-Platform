# Dart-Club-Website



TODO:
  - ~Backend code for all html files.~ Only double-check.
  - Setting corresponding links for all remaining anchor tags
  - Finalization of the "tournament creation"
  - Finalization of design (the website design itself and swapping the photos and logos that must be swapped)
  - ~Configuration for different screen sizes~
~

## Email (PHPMailer) Configuration

Backend services use PHPMailer for best-effort email notifications (e.g., player account credentials, tournament registration confirmations).

Set the following environment variables (Apache/PHP-FPM env or via .htaccess / system env) to enable SMTP:

- SMTP_HOST
- SMTP_PORT (e.g., 587 or 465)
- SMTP_AUTH ("1" to enable auth)
- SMTP_USER
- SMTP_PASS
- SMTP_SECURE ("tls" or "ssl" if required)
- SMTP_FROM (sender email, e.g., noreply@yourdomain.com)

If variables are not set, the code falls back to localhost without authentication where possible.

### Templates
- Player creation: sends username and a temporary password to the provided email.
- Tournament registration confirmation: confirms the registration and includes the tournament title.

You can customize email subjects/bodies directly in:
- services/create_player.php
- services/register_tournament.php