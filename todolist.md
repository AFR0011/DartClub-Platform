## Dart Club Project – To-Do List

### High-level
- [x] Role-based access and flexible galleries/blogs
- [ ] Build out tournaments fully; centralize bracket UI/UX as primary interaction
- [ ] Close security gaps and data-model inconsistencies
- [ ] Test end-to-end and prepare for deployment

### Core data model and auth
- [ ] Users ↔ Players mapping: add `players.user_id` FK (or mapping table) and migrate; stop assuming `players.plr_idNum === users.user_id`
- [x] Sessions/guards: centralized guards via `services/auth.php`
- [x] Passwords: ensure signup uses `password_hash` everywhere and migrate legacy rows

### Guest → Player onboarding
- [x] Application review in `manage_players.php`; Approve/Reject → auto-create player and notify via email
- [ ] Note: currently manual by manager/admin; will be streamlined later (batch, inline, templates)

### Tournaments (player-facing)
- [x] Registration fixes to resolve actual `plr_idNum`
- [x] UX: show per-tournament registration state

### Tournaments (admin/manager)
- [x] Standings init for League/Group on creation
- [x] Flexible manual match create/update/delete endpoints
- [x] Basic inline controls in tournament details to add/edit/delete matches
- [x] Central bracket UI/UX: clickable bracket/match list opens an in-page modal for scores, monitoring, linkage
- [x] Improve bracket rendering/labels (rounds, W/L brackets for double-elim, group labels)
- [x] Group mode (ad-hoc players):
  - [x] At creation, accept group_count and advancers per group
  - [x] Schedule round-robin within groups; tag matches with `group_number`
  - [x] After group stage, promote top-N per group; create knockout bracket (semi/finals) with manual override
- [ ] Double-elimination:
  - [ ] Classic format: winners/losers brackets, single final; allow manual link edits
  - [x] Visual separation of brackets and final
- [x] League mode:
  - [x] Option to space rounds by dates; manual override remains available

### Blogs (public + admin/manager)
- [x] Author and created_at metadata
- [x] Pagination API and UI
- [x] Input validation/XSS hardening pass

### Gallery (public + admin/manager)
- [x] API: list/upload/delete + initial import from disk
- [x] UI wired to APIs; admin controls gated
- [x] Thumbnails/lazy-load for performance

### Profile
- [ ] Use FK mapping; show tournaments and recent results; editable basic info

### Navigation and pages
- [x] Role-gated nav consistent across pages
- [ ] Retire old admin blog/image pages definitively (optional)
- [ ] Standardize asset paths

### Emails (PHPMailer)
- [ ] SMTP env config and templates for events (application, approval, tour reg)

### Security and validation
- [ ] Server-side validation for all inputs; CSRF or same-site strategy
- [x] File upload hardening; rate-limiting for sensitive endpoints

### Ops/Deployment
- [ ] Config/secrets outside webroot; logging; backups; README

### Testing
- [ ] Postman collection; E2E manual checklist; seed data