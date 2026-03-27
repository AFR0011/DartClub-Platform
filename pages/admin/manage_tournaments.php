<?php

require_once '../../services/app_bootstrap.php';
require_once '../../services/dbConnection.php';
require_once '../../services/auth.php';
require_once '../../services/shared/tournament_view_helpers.php';

app_start_session();
require_any_role(['admin', 'manager']);

$tournamentStmt = $conn->prepare('SELECT tour_id, tour_title, tour_creationDate, tour_endDate, tour_type FROM tournaments ORDER BY tour_creationDate DESC');
$tournamentStmt->execute();
$tournaments = $tournamentStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$tournamentStmt->close();

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
        .inline-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .callout {
            margin: 16px 0;
            padding: 12px 16px;
            border-left: 4px solid #1f6feb;
            background: #eef5ff;
        }

        .player-picker {
            max-height: 320px;
            overflow: auto;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 8px;
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

        <div class="callout">
            Supported tournament modes in this cleanup cycle are <strong>League</strong>, <strong>Group</strong>, and <strong>Elimination</strong>.
            Double-elimination has been intentionally deferred until it has a full bracket engine behind it.
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

    <div class="container">
        <h1>Create Tournament</h1>
        <form action="../../services/create_tournament.php" method="post" id="tournamentForm" class="tournament-form">
            <div class="inline-grid">
                <div class="form-group">
                    <label for="tournamentType">Tournament Type</label>
                    <select name="tour_type" id="tournamentType" required>
                        <option value="">Select type</option>
                        <option value="League">League</option>
                        <option value="Group">Group</option>
                        <option value="Elimination">Elimination</option>
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
            </div>

            <div id="groupSettings" class="inline-grid" style="display:none;">
                <div class="form-group">
                    <label for="group_count">Number of Groups</label>
                    <input type="number" name="group_count" id="group_count" min="2" value="2">
                </div>

                <div class="form-group">
                    <label for="advancers_per_group">Advancers per Group</label>
                    <input type="number" name="advancers_per_group" id="advancers_per_group" min="1" value="1">
                </div>
            </div>

            <div class="callout" id="typeHint">
                Select a tournament type to see mode-specific requirements.
            </div>

            <h3>Select Active Players</h3>
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
                            <tr>
                                <td><input type="checkbox" name="selectedPlayers[]" value="<?php echo (int) $player['plr_idNum']; ?>"></td>
                                <td><?php echo htmlspecialchars(trim($player['plr_name'] . ' ' . $player['plr_surname'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="form-buttons" style="margin-top:16px;">
                <input type="submit" value="Create Tournament" class="submit-btn">
                <input type="reset" value="Reset Form" class="reset-btn">
            </div>
        </form>
    </div>

    <script>
        const typeSelect = document.getElementById('tournamentType');
        const groupSettings = document.getElementById('groupSettings');
        const typeHint = document.getElementById('typeHint');

        function syncTournamentMode() {
            const selectedType = typeSelect.value;
            groupSettings.style.display = selectedType === 'Group' ? 'grid' : 'none';

            if (selectedType === 'League') {
                typeHint.textContent = 'League tournaments generate round-robin matches and standings immediately.';
            } else if (selectedType === 'Group') {
                typeHint.textContent = 'Group tournaments persist group assignments, create group-stage round robins, and promote top finishers into a knockout bracket later.';
            } else if (selectedType === 'Elimination') {
                typeHint.textContent = 'Elimination tournaments generate a single-elimination bracket with deterministic bye carry-forward.';
            } else {
                typeHint.textContent = 'Select a tournament type to see mode-specific requirements.';
            }
        }

        typeSelect.addEventListener('change', syncTournamentMode);
        syncTournamentMode();

        document.getElementById('tournamentForm').addEventListener('submit', function (event) {
            const selectedPlayers = document.querySelectorAll('input[name="selectedPlayers[]"]:checked');
            const startDate = new Date(document.getElementById('tour_startDate').value);
            const endDate = new Date(document.getElementById('tour_endDate').value);

            if (selectedPlayers.length < 2) {
                alert('Please select at least two players for the tournament.');
                event.preventDefault();
                return;
            }

            if (endDate < startDate) {
                alert('End date cannot be before start date.');
                event.preventDefault();
                return;
            }
        });
    </script>
</body>
</html>
