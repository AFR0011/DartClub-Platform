# RUN_PROTOCOL

## Purpose
Define how to verify work in this repo when there is no single automated test command and the current Codex environment does not expose PHP CLI on PATH.

## Companion Doc
- Use `docs/TESTING_CHECKLIST.md` for the concrete next-session click path and data-entry sequence.
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

### Level 2 - Local Browser/XAMPP Smoke Validation
Use for:
- page/service integration work
- auth/profile/tournament flow work

Recommended local environment:
- XAMPP or equivalent Apache + MariaDB stack
- database imported from `dart_club.sql`
- env/config aligned with `services/config.php`

Smoke checks:
- signup
- login/logout
- player approval/creation
- public tournaments page load
- profile page load
- admin tournament list/detail page load

### Level 3 - Tournament Regression Validation
Use for:
- tournament creation/management changes
- bracket/group logic changes
- match result propagation changes

Checks:
- create a `League` tournament and confirm:
  - round-robin matches are generated
  - standings update after results
- create a `Group` tournament and confirm:
  - `group_number` persists on roster rows
  - group-stage matches only happen within groups
  - group standings rank correctly
  - promotion creates knockout matches
- create an `Elimination` tournament and confirm:
  - bracket rounds are linked through `next_match_id`
  - byes carry forward correctly
  - results advance winners into the next round

## PHP CLI Note
- `php -l` was not available in this Codex environment during the current mapping/cleanup pass.
- If PHP CLI becomes available locally, run:

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

## Reporting Rule
If full validation was not run, explicitly report:
- what commands were run
- what was only inspected
- what local/XAMPP verification still needs to happen
- any remaining risks tied to missing runtime validation
