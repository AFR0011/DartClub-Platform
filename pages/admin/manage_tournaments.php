<?php

require_once '../../services/app_bootstrap.php';
require_once '../../services/dbConnection.php';
require_once '../../services/auth.php';
require_once '../../services/shared/tournament_helpers.php';
require_once '../../services/shared/tournament_view_helpers.php';

app_start_session();
require_any_role(['admin', 'manager']);

$tournamentStmt = $conn->prepare('SELECT tour_id, tour_title, tour_creationDate, tour_endDate, tour_type FROM tournaments ORDER BY tour_creationDate DESC');
$tournamentStmt->execute();
$tournaments = $tournamentStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$tournamentStmt->close();

foreach ($tournaments as $index => $row) {
    $tournaments[$index] = tournament_refresh_lifecycle($conn, (int) $row['tour_id']);
}

$statusCounts = [
    'total' => count($tournaments),
    'registration_open' => 0,
    'in_progress' => 0,
    'completed' => 0,
];
foreach ($tournaments as $tournamentRow) {
    $statusCounts['registration_open'] += (($tournamentRow['status'] ?? '') === 'registration_open') ? 1 : 0;
    $statusCounts['in_progress'] += (($tournamentRow['status'] ?? '') === 'in_progress') ? 1 : 0;
    $statusCounts['completed'] += in_array(($tournamentRow['status'] ?? ''), ['completed', 'archived'], true) ? 1 : 0;
}

$playerStmt = $conn->prepare('SELECT plr_idNum, plr_name, plr_surname FROM players ORDER BY plr_surname, plr_name');
$playerStmt->execute();
$players = $playerStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$playerStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tournaments</title>
    <link href="../../css/admin_style.css" rel="stylesheet">
    <script src="../../js/admin_nav.js"></script>
    <style>
        .hero-band {
            display: grid;
            gap: 18px;
            grid-template-columns: 1.4fr 1fr;
            margin-bottom: 22px;
        }

        .hero-card,
        .stats-card,
        .surface-panel {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(245, 248, 255, 0.92));
            border: 1px solid rgba(37, 99, 235, 0.1);
            border-radius: 22px;
            padding: 20px;
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.06);
        }

        .hero-card {
            background:
                radial-gradient(circle at top right, rgba(245, 158, 11, 0.14), transparent 32%),
                radial-gradient(circle at bottom left, rgba(37, 99, 235, 0.12), transparent 32%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(244, 248, 255, 0.95));
        }

        .hero-card p,
        .stats-card p,
        .mini-note {
            color: #5b6678;
        }

        .stats-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .stat-pill {
            padding: 14px;
            border-radius: 18px;
            background: rgba(37, 99, 235, 0.06);
            border: 1px solid rgba(37, 99, 235, 0.08);
        }

        .stat-pill strong {
            display: block;
            font-size: 1.45rem;
            color: #18212f;
            line-height: 1.1;
        }

        .inline-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .callout {
            margin: 16px 0;
            padding: 16px 18px;
            border-left: 4px solid #1f6feb;
            background: linear-gradient(180deg, #eef5ff, #f7fbff);
            border-radius: 18px;
        }

        .player-picker {
            max-height: 360px;
            overflow: auto;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 20px;
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.8);
        }

        .player-picker table {
            margin: 10px 0 0;
        }

        .player-picker .players-table tbody tr {
            cursor: pointer;
            transition: background-color 0.18s ease, transform 0.18s ease;
        }

        .player-picker .players-table tbody tr:hover {
            transform: translateY(-1px);
        }

        .player-picker .players-table tbody tr.is-selected {
            background: rgba(37, 99, 235, 0.1);
        }

        .row-toggle {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid rgba(15, 23, 42, 0.12);
            background: rgba(255, 255, 255, 0.88);
            color: #334155;
            font-size: 0.84rem;
            font-weight: 700;
        }

        .row-toggle input {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            margin: 0;
        }

        .row-toggle-indicator {
            width: 18px;
            height: 18px;
            border-radius: 999px;
            border: 2px solid rgba(37, 99, 235, 0.32);
            background: transparent;
            transition: background-color 0.18s ease, border-color 0.18s ease, transform 0.18s ease;
        }

        .row-toggle.is-selected {
            border-color: rgba(37, 99, 235, 0.3);
            background: rgba(37, 99, 235, 0.1);
            color: #1d4ed8;
        }

        .row-toggle.is-selected .row-toggle-indicator {
            background: linear-gradient(180deg, #60a5fa, #2563eb);
            border-color: rgba(37, 99, 235, 0.92);
            transform: scale(1.02);
        }

        .selection-meta {
            color: #5b6678;
            font-size: 0.92rem;
            margin-top: 10px;
        }

        .player-picker thead th {
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .table-container {
            overflow: auto;
            border-radius: 22px;
        }

        .filter-bar {
            display: grid;
            gap: 12px;
            grid-template-columns: minmax(220px, 1fr) minmax(180px, 220px) minmax(180px, 220px);
            align-items: end;
            margin-bottom: 12px;
        }

        .compact-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.08);
            color: #1d4ed8;
            font-weight: 600;
            font-size: 0.88rem;
        }

        .toolbar-line {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        @media (max-width: 900px) {
            .hero-band {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .stats-grid,
            .filter-bar {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="sidenav" id="sidenav">
        <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
        <a href="manage_players.php">Manage Players</a>
        <a href="manage_tournaments.php">Manage Tournaments</a>
        <a href="manage_users.php">Manage Users</a>
        <a href="../main.php">Back to Website</a>
    </div>

    <div class="container">
        <div class="header-actions">
            <span style="font-size:30px;cursor:pointer" onclick="openNav()">&#9776;</span>
            <h1>Manage Tournaments</h1>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message">
                <?php
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <?php
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="hero-band">
            <div class="hero-card">
                <div class="compact-chip">Tournament workspace</div>
                <h2 style="margin-top:14px;">Create smoother event flows without overpacking the page</h2>
                <p style="margin-top:10px;">
                    Start with an empty tournament or a registration-first roster, then generate fixtures once the field is ready.
                    The details view now supports same-page structure management, bracket work, and lighter-weight score handling.
                </p>
                <div class="callout" style="margin-bottom:0;">
                    Supported tournament formats are <strong>Round Robin</strong>, <strong>League</strong>, <strong>Group</strong>, <strong>Elimination</strong>, and <strong>Double Elimination</strong>.
                    <strong>League</strong> means pool-stage plus knockout, while <strong>Group</strong> currently creates exactly two teams and schedules every player from one team against every player from the other team.
                    Double-elimination now uses dedicated winners-bracket, losers-bracket, and grand-final paths.
                </div>
            </div>
            <div class="stats-card">
                <h3 style="margin-top:0;">Tournament Snapshot</h3>
                <div class="stats-grid" style="margin-top:14px;">
                    <div class="stat-pill">
                        <span class="mini-note">Total tournaments</span>
                        <strong><?php echo (int) $statusCounts['total']; ?></strong>
                    </div>
                    <div class="stat-pill">
                        <span class="mini-note">Registration open</span>
                        <strong><?php echo (int) $statusCounts['registration_open']; ?></strong>
                    </div>
                    <div class="stat-pill">
                        <span class="mini-note">Live now</span>
                        <strong><?php echo (int) $statusCounts['in_progress']; ?></strong>
                    </div>
                    <div class="stat-pill">
                        <span class="mini-note">Finished</span>
                        <strong><?php echo (int) $statusCounts['completed']; ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-toggle">
            <button type="button" class="active" data-section-button="current_tournaments">Current Tournaments</button>
            <button type="button" data-section-button="create_tournament">Create Tournament</button>
        </div>

        <section class="page-section active" data-section="current_tournaments">
        <div class="surface-panel">
            <div class="toolbar-line">
                <div>
                    <h2 style="margin:0;">Current Tournaments</h2>
                    <p class="mini-note">Open any tournament to edit its roster, fixtures, groups, or bracket structure.</p>
                </div>
            </div>
            <div class="table-container">
            <table class="tournaments-table">
                <thead>
                    <tr>
                        <th>Tournament Name</th>
                        <th>Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tournaments as $tournament): ?>
                        <?php $status = tournament_status_data($tournament); ?>
                        <tr>
                            <td><?php echo htmlspecialchars($tournament['tour_title']); ?></td>
                            <td><?php echo htmlspecialchars($tournament['tour_type']); ?></td>
                            <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($tournament['tour_creationDate']))); ?></td>
                            <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($tournament['tour_endDate']))); ?></td>
                            <td class="<?php echo $status['class']; ?>"><?php echo $status['label']; ?></td>
                            <td><a class="details-btn" href="show_tournament_details.php?id=<?php echo (int) $tournament['tour_id']; ?>">Show Details</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
        </section>

        <section class="page-section" data-section="create_tournament">
        <div class="toolbar-line">
            <div>
                <h1>Create Tournament</h1>
                <p class="mini-note">Keep the setup compact now, then do structure work after registration closes.</p>
            </div>
        </div>
        <form action="../../services/create_tournament.php" method="post" id="tournamentForm" class="tournament-form surface-panel" style="padding:24px;">
            <div class="inline-grid">
                <div class="form-group">
                    <label for="tournamentType">Tournament Type</label>
                    <select name="tour_type" id="tournamentType" required>
                        <option value="">Select type</option>
                        <option value="Round Robin">Round Robin</option>
                        <option value="League">League</option>
                        <option value="Group">Group</option>
                        <option value="Elimination">Elimination</option>
                        <option value="Double Elimination">Double Elimination</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="tour_title">Tournament Title</label>
                    <input type="text" name="tour_title" id="tour_title" required>
                </div>

                <div class="form-group">
                    <label for="tour_startDate">Start Date</label>
                    <input type="date" name="tour_startDate" id="tour_startDate" required>
                </div>

                <div class="form-group">
                    <label for="tour_endDate">End Date</label>
                    <input type="date" name="tour_endDate" id="tour_endDate" required>
                </div>

                <div class="form-group">
                    <label for="registration_open_at">Registration Opens</label>
                    <input type="date" name="registration_open_at" id="registration_open_at">
                </div>

                <div class="form-group">
                    <label for="registration_close_at">Registration Closes</label>
                    <input type="date" name="registration_close_at" id="registration_close_at">
                </div>
            </div>

            <div id="leagueSettings" class="inline-grid" style="display:none;">
                <div class="form-group">
                    <label for="group_count">Number of Groups</label>
                    <input type="number" name="group_count" id="group_count" min="2" value="2">
                </div>

                <div class="form-group">
                    <label for="advancers_per_group">Advancers per Group</label>
                    <input type="number" name="advancers_per_group" id="advancers_per_group" min="1" value="1">
                </div>
            </div>

            <div id="teamSettings" class="inline-grid" style="display:none;">
                <div class="form-group">
                    <label for="team_count">Number of Teams</label>
                    <input type="number" name="team_count" id="team_count" min="2" max="2" value="2" readonly>
                </div>
            </div>

            <div class="callout" id="typeHint">
                Select a tournament type to see mode-specific requirements.
            </div>

            <h3>Select Players (Optional)</h3>
            <div class="filter-bar">
                <div class="form-group" style="margin-bottom:0;">
                    <label for="playerFilter">Filter player list</label>
                    <input type="text" id="playerFilter" placeholder="Type a player name to narrow the list">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label for="playerSort">Sort player list</label>
                    <select id="playerSort">
                        <option value="name_asc">Player A-Z</option>
                        <option value="name_desc">Player Z-A</option>
                        <option value="id_asc">Oldest first</option>
                        <option value="id_desc">Newest first</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label for="selectedPlayersStatus">Add selected players as</label>
                    <select name="selectedPlayersStatus" id="selectedPlayersStatus">
                        <option value="Registered" selected>Registered entrants</option>
                        <option value="Active">Active competition roster</option>
                    </select>
                </div>
            </div>
            <div class="inline-grid" style="margin-bottom:16px;">
                <div class="form-group">
                    <div class="compact-chip">Select existing players only if you want to seed the roster immediately.</div>
                </div>
            </div>
            <div class="player-picker">
                <table class="players-table">
                    <thead>
                        <tr>
                            <th>Select</th>
                            <th>Player Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($players as $player): ?>
                            <tr
                                data-player-row
                                data-player-id="<?php echo (int) $player['plr_idNum']; ?>"
                                data-player-name="<?php echo htmlspecialchars(strtolower(trim($player['plr_name'] . ' ' . $player['plr_surname']))); ?>"
                            >
                                <td>
                                    <label class="row-toggle" data-row-toggle>
                                        <input type="checkbox" name="selectedPlayers[]" value="<?php echo (int) $player['plr_idNum']; ?>">
                                        <span class="row-toggle-indicator" aria-hidden="true"></span>
                                        <span>Select</span>
                                    </label>
                                </td>
                                <td><?php echo htmlspecialchars(trim($player['plr_name'] . ' ' . $player['plr_surname'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="selection-meta" id="playerSelectionCount">No players selected yet.</div>

            <div class="form-buttons" style="margin-top:16px;">
                <input type="submit" value="Create Tournament" class="submit-btn">
                <input type="reset" value="Reset Form" class="reset-btn">
            </div>
        </form>
        </section>
    </div>

    <script src="../../js/ui_feedback.js?v=20260406-1"></script>
    <script>
        const typeSelect = document.getElementById('tournamentType');
        const leagueSettings = document.getElementById('leagueSettings');
        const teamSettings = document.getElementById('teamSettings');
        const typeHint = document.getElementById('typeHint');
        const playerFilter = document.getElementById('playerFilter');
        const playerSort = document.getElementById('playerSort');
        const playerSelectionCount = document.getElementById('playerSelectionCount');

        function showSection(sectionName) {
            document.querySelectorAll('[data-section]').forEach((section) => {
                section.classList.toggle('active', section.dataset.section === sectionName);
            });

            document.querySelectorAll('[data-section-button]').forEach((button) => {
                button.classList.toggle('active', button.dataset.sectionButton === sectionName);
            });
        }

        function syncSelectableRow(row) {
            const checkbox = row.querySelector('input[type="checkbox"]');
            const toggle = row.querySelector('[data-row-toggle]');
            const checked = !!checkbox?.checked;
            row.classList.toggle('is-selected', checked);
            toggle?.classList.toggle('is-selected', checked);
        }

        function refreshSelectionCount() {
            const selectedCount = document.querySelectorAll('input[name="selectedPlayers[]"]:checked').length;
            playerSelectionCount.textContent = selectedCount > 0
                ? `${selectedCount} player${selectedCount === 1 ? '' : 's'} selected for the initial roster.`
                : 'No players selected yet.';
        }

        function bindSelectableRows() {
            document.querySelectorAll('[data-player-row]').forEach((row) => {
                const checkbox = row.querySelector('input[type="checkbox"]');
                if (!checkbox) {
                    return;
                }

                syncSelectableRow(row);

                row.addEventListener('click', (event) => {
                    if (event.target.closest('input, button, a, select, textarea')) {
                        return;
                    }

                    checkbox.checked = !checkbox.checked;
                    syncSelectableRow(row);
                    refreshSelectionCount();
                });

                checkbox.addEventListener('change', () => {
                    syncSelectableRow(row);
                    refreshSelectionCount();
                });
            });
        }

        function syncTournamentMode() {
            const selectedType = typeSelect.value;
            leagueSettings.style.display = selectedType === 'League' ? 'grid' : 'none';
            teamSettings.style.display = selectedType === 'Group' ? 'grid' : 'none';

            if (selectedType === 'Round Robin') {
                typeHint.textContent = 'Round Robin tournaments place all competition entrants into one shared standings table once you generate the fixture list.';
            } else if (selectedType === 'League') {
                typeHint.textContent = 'League tournaments assign players to groups, complete group-stage round robins, and automatically build a knockout bracket from top finishers after the structure exists.';
            } else if (selectedType === 'Group') {
                typeHint.textContent = 'Group tournaments currently use exactly two teams and schedule every player from Team 1 against every player from Team 2 once the structure is generated.';
            } else if (selectedType === 'Elimination') {
                typeHint.textContent = 'Elimination tournaments generate a single-elimination bracket with deterministic bye carry-forward once you are ready to seed the final entrant list.';
            } else if (selectedType === 'Double Elimination') {
                typeHint.textContent = 'Double Elimination tournaments build winners-bracket and losers-bracket paths, then finish with one grand final. Use at least four entrants for a stable bracket.';
            } else {
                typeHint.textContent = 'Select a tournament type to see mode-specific requirements.';
            }
        }

        typeSelect.addEventListener('change', syncTournamentMode);
        syncTournamentMode();

        function applyPlayerListControls() {
            const tbody = document.querySelector('.player-picker tbody');
            if (!tbody) {
                return;
            }

            const query = playerFilter?.value.trim().toLowerCase() || '';
            const sortMode = playerSort?.value || 'name_asc';
            const rows = Array.from(tbody.querySelectorAll('[data-player-row]'));

            rows.sort((left, right) => {
                const leftName = left.dataset.playerName || '';
                const rightName = right.dataset.playerName || '';
                if (sortMode === 'name_desc') {
                    return rightName.localeCompare(leftName, undefined, { sensitivity: 'base' });
                }
                if (sortMode === 'id_asc') {
                    return Number(left.dataset.playerId || 0) - Number(right.dataset.playerId || 0);
                }
                if (sortMode === 'id_desc') {
                    return Number(right.dataset.playerId || 0) - Number(left.dataset.playerId || 0);
                }

                return leftName.localeCompare(rightName, undefined, { sensitivity: 'base' });
            });

            rows.forEach((row) => {
                row.style.display = (row.dataset.playerName || '').includes(query) ? '' : 'none';
                tbody.appendChild(row);
            });
        }

        playerFilter?.addEventListener('input', applyPlayerListControls);
        playerSort?.addEventListener('change', applyPlayerListControls);
        document.querySelectorAll('[data-section-button]').forEach((button) => {
            button.addEventListener('click', () => showSection(button.dataset.sectionButton));
        });

        bindSelectableRows();
        refreshSelectionCount();
        applyPlayerListControls();
        showSection('current_tournaments');

        document.getElementById('tournamentForm').addEventListener('submit', function (event) {
            const startDate = new Date(document.getElementById('tour_startDate').value);
            const endDate = new Date(document.getElementById('tour_endDate').value);

            if (endDate < startDate) {
                AppUI?.toast('End date cannot be before start date.', 'warning');
                event.preventDefault();
                return;
            }

            const registrationOpen = document.getElementById('registration_open_at').value;
            const registrationClose = document.getElementById('registration_close_at').value;
            if (registrationOpen && registrationClose && new Date(registrationClose) < new Date(registrationOpen)) {
                AppUI?.toast('Registration close date cannot be before the registration open date.', 'warning');
                event.preventDefault();
            }
        });

        document.getElementById('tournamentForm').addEventListener('reset', function () {
            window.setTimeout(() => {
                document.querySelectorAll('[data-player-row]').forEach(syncSelectableRow);
                refreshSelectionCount();
            }, 0);
        });
    </script>
</body>
</html>
