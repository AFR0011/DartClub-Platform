# Migration backlog

This is a deliberately bounded maintenance backlog. Completed historical work
is preserved in `docs/VERSION_LOG.md` and Git history rather than repeated here.

## Release gate

- [ ] Public PHP 8.3/MariaDB CI passes on the final commit.
- [ ] Fresh public clone repeats Composer, schema import, database/HTTP,
  tournament, sanitizer, syntax, and publication checks.
- [x] Final homepage screenshot is reviewed for accuracy and privacy at desktop
  and mobile widths.
- [ ] GitHub description/topics and security controls are read back.
- [ ] `v1.0.0-portfolio` points to the verified default-branch commit.
- [ ] Merged branches are deleted only after remote ancestry proof.

## Future maintenance, not release blockers

- Characterize more bracket edge cases before splitting the large tournament
  helper/admin/public files.
- Add broader browser automation only where repeated regressions justify it.
- Define a retirement date for legacy plaintext/MD5 password-row migration.
- Review PHPMailer 7 compatibility with a real mail test before upgrading.
- Add rate limiting and tokenized account recovery only if the deployment
  boundary changes from controlled demo to an Internet-facing service.
- Document exact storage, backup, retention, and non-Apache private-file rules
  for any chosen host.

## Intentionally out of scope

- framework migration or frontend/backend split;
- microservices, Kubernetes, or cloud architecture added for presentation;
- realtime sockets/live scoring;
- object storage without a real deployment requirement;
- unsupported performance, scale, reliability, or production claims.
