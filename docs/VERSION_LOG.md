# VERSION_LOG

## Pre-Mapped Legacy Snapshot
- Date: before 2026-03-27 mapping pass
- Status: imported legacy website snapshot with no usable repo history in the current checkout
- Characteristics:
  - mixed DB credentials across pages/services
  - request-path schema mutation via `ALTER TABLE`
  - partial group/double-elimination tournament logic
  - oversized tournament admin page
  - mixed player identity assumptions
- Evidence:
  - `README.md`
  - `progress.md`
  - `todolist.md`
  - `tournament-errors.md`

## Mapping And Tournament Stabilization Pass
- Date: 2026-03-27
- Status: active cleanup milestone
- Main changes:
  - initialized local git metadata for the working tree
  - added repo docs pack and repo-local Codex config
  - added shared PHP bootstrap/config path
  - added shared player/tournament helper layers
  - removed request-path schema mutation from active handlers
  - removed active `DoubleElimination` creation support
  - aligned group tournament contracts on `group_count` and `advancers_per_group`
  - rewrote the main tournament admin pages around the consolidated helpers
  - updated `dart_club.sql` to match expected schema
- Verification in this pass:
  - code/structure inspection
  - credential/bootstrap consolidation checks
  - pattern scans for `ALTER TABLE` and scattered `new mysqli(...)`
- Still pending:
  - PHP linting
  - XAMPP/MariaDB smoke tests
  - full tournament flow regression run
