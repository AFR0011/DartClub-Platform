# RUN_PROTOCOL

## Purpose
Define how to verify work in this repo when there is no single automated test command and PHP is typically available locally through XAMPP rather than PATH.

## Companion Doc
- Use `docs/TESTING_CHECKLIST.md` for the concrete click path, seed data, and step-by-step operator checklist.
- Use this file for the validation ladder and reporting rules.

## Verification Ladder
### Level 0 - Static Repo Validation
Use for:
- docs/config updates
- path cleanup
- low-risk HTML/JS changes
- service wiring changes that can be checked by inspection

Checks:
- inspect changed files
- scan for banned patterns:
  - direct `new mysqli(...)` outside shared bootstrap
  - request-path `ALTER TABLE`
  - old username-based player resolution in steady-state public/tournament reads
  - membership logic that conflates `user_role` with `membership_status`

### Level 1 - Schema And Bootstrap Validation
Use for:
- DB config changes
- schema changes
- service-layer refactors

Checks:
- confirm `dart_club.sql` contains the columns the code expects
- confirm active code uses:
  - `services/config.php`
  - `services/app_bootstrap.php`
  - `services/dbConnection.php`
- confirm PHP handlers no longer mutate schema at request time
- confirm active tables exist for:
  - `membership_applications`
  - `tournament_teams`
  - `tournament_team_players`
  - `team_matches`
  - `blog_images`
  - `blog_comments`
  - `blog_reactions`
  - `gallery_images`

### Level 2 - Local Browser/XAMPP Smoke Validation
Use for:
- page/service integration work
- auth/profile/tournament flow work
- membership/blog/gallery flow work

Recommended local environment:
- XAMPP or equivalent Apache + MariaDB stack
- database imported from `dart_club.sql`
- env/config aligned with `services/config.php`

Smoke checks:
- signup
- login/logout
- profile save
- membership submission
- public tournaments hub load
- public tournament detail load
- blog page load
- gallery page load
- player approval/creation
- admin tournament list/detail page load
- admin membership/player page load

### Level 3 - Tournament Regression Validation
Use for:
- tournament creation/management changes
- bracket/group logic changes
- match result propagation changes

Checks:
- create a `Round Robin` tournament and confirm:
  - round-robin matches are generated
  - standings update after results
- create a `League` tournament and confirm:
  - `group_number` persists on roster rows
  - group-stage matches only happen within groups
  - group standings rank correctly
  - knockout creation happens automatically after the group stage finishes
- create a `Group` tournament and confirm:
  - teams are created inside the tournament
  - players are assigned to team rosters
  - team fixtures generate correctly
  - team standings update correctly after team results
- create an `Elimination` tournament and confirm:
  - bracket rounds are linked through `next_match_id`
  - byes carry forward correctly
  - results advance winners into the next round
- archive a completed tournament and confirm:
  - public reads still work
  - mutation services reject edits with a read-only message

### Level 4 - Membership And Community Validation
Use for:
- membership workflow changes
- blog/gallery/community changes

Checks:
- submit a membership application as a signed-in player
- approve or reject it as a manager/admin
- confirm `membership_status` changes without changing `user_role`
- create a blog draft as an approved member
- publish it as a manager/admin
- add a comment and like as an authenticated user
- confirm blog images are inserted into `gallery_images`

## Local Commands
- Preferred local PHP CLI path:
  - `C:\Users\Ali\xampp\php\php.exe`
- Preferred local MariaDB client path:
  - `C:\Users\Ali\xampp\mysql\bin\mysql.exe`
- Syntax lint command:

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { & 'C:\Users\Ali\xampp\php\php.exe' -l $_.FullName }
```

- Fresh DB import command:

```powershell
Get-Content -Raw 'dart_club.sql' | & 'C:\Users\Ali\xampp\mysql\bin\mysql.exe' -u root dart_club
```

- Built-in local PHP server:

```powershell
& 'C:\Users\Ali\xampp\php\php.exe' -S 127.0.0.1:8090 -t .
```

## Reporting Rule
If full validation was not run, explicitly report:
- what commands were run
- what was only inspected
- what local/XAMPP or Apache verification still needs to happen
- any remaining risks tied to missing runtime validation
