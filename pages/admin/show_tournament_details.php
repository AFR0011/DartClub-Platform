<?php

require_once '../../services/app_bootstrap.php';
require_once '../../services/dbConnection.php';
require_once '../../services/auth.php';
require_once '../../services/shared/player_helpers.php';
require_once '../../services/shared/tournament_helpers.php';
require_once '../../services/shared/tournament_view_helpers.php';

app_start_session();
require_any_role(['admin', 'manager']);

$tourId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($tourId <= 0) {
    app_redirect('manage_tournaments.php');
}

try {
    $pageData = tournament_fetch_page_data($conn, $tourId);
} catch (Throwable $exception) {
    echo 'Tournament not found';
    exit;
}

$tournament = $pageData['tournament'];
$players = $pageData['players'];
$matches = $pageData['matches'];
$teamMatches = $pageData['team_matches'];
$teams = $pageData['teams'];
$standings = $pageData['standings'];
$groupStandings = $pageData['group_standings'];
$teamStandings = $pageData['team_standings'];
$availablePlayers = $pageData['available_players'];
$status = tournament_status_data($tournament);

$matchNumbersById = [];
foreach ($matches as $index => $match) {
    $matchNumbersById[(int) $match['match_id']] = $index + 1;
}

$groupMatches = [];
$knockoutRounds = [];
foreach ($matches as $match) {
    if ($match['group_number'] !== null) {
        $groupNumber = (int) $match['group_number'];
        if (!isset($groupMatches[$groupNumber])) {
            $groupMatches[$groupNumber] = [];
        }
        $groupMatches[$groupNumber][] = $match;
        continue;
    }

    $roundNumber = (int) $match['round_number'];
    if (!isset($knockoutRounds[$roundNumber])) {
        $knockoutRounds[$roundNumber] = [];
    }
    $knockoutRounds[$roundNumber][] = $match;
}

ksort($groupMatches);
ksort($knockoutRounds);

$playersForJs = array_map(function (array $player): array {
    return [
        'id' => (int) $player['plr_idNum'],
        'name' => player_name($player),
    ];
}, $players);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tournament Details</title>
    <link href="../../css/admin_style.css" rel="stylesheet">
    <script src="../../js/admin_nav.js"></script>
    <style>
        .info-grid,
        .inline-grid,
        .section-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .info-card,
        .section-card,
        .match-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 16px;
            background: #fff;
        }

        .section-card h3,
        .match-card h4 {
            margin-top: 0;
        }

        .section-toggle {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 16px 0;
        }

        .section-toggle button.active {
            background: #1f6feb;
            color: #fff;
        }

        .page-section {
            display: none;
        }

        .page-section.active {
            display: block;
        }

        .callout {
            margin: 16px 0;
            padding: 12px 16px;
            border-left: 4px solid #1f6feb;
            background: #eef5ff;
        }

        .bracket-grid {
            display: flex;
            gap: 16px;
            overflow-x: auto;
            padding-bottom: 8px;
        }

        .bracket-round {
            min-width: 260px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal-card {
            width: min(760px, 95vw);
            max-height: 90vh;
            overflow: auto;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
        }

        .tag {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            background: #f1f3f5;
            font-size: 12px;
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
            <h1><?php echo htmlspecialchars($tournament['tour_title']); ?></h1>
            <a href="manage_tournaments.php" class="back-btn">Back to Tournaments</a>
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
            Tournament structures rebuild automatically from the current active roster. Once scores have been recorded, structural changes are blocked.
            League promotion now happens automatically after all group-stage matches are complete, and archived tournaments are read-only.
        </div>

        <div class="info-grid">
            <div class="info-card"><strong>Type</strong><br><?php echo htmlspecialchars($tournament['tour_type']); ?></div>
            <div class="info-card"><strong>Start Date</strong><br><?php echo htmlspecialchars(date('Y-m-d', strtotime($tournament['tour_creationDate']))); ?></div>
            <div class="info-card"><strong>End Date</strong><br><?php echo htmlspecialchars(date('Y-m-d', strtotime($tournament['tour_endDate']))); ?></div>
            <div class="info-card"><strong>Status</strong><br><span class="<?php echo $status['class']; ?>"><?php echo $status['label']; ?></span></div>
            <div class="info-card"><strong>Players</strong><br><?php echo (int) $tournament['player_count']; ?></div>
            <div class="info-card"><strong>Matches</strong><br><?php echo (int) $tournament['match_count']; ?></div>
        </div>

        <div class="section-grid" style="margin-top:24px;">
            <div class="section-card">
                <h3>Edit Tournament</h3>
                <form action="../../services/update_tournament.php" method="post" id="editTournamentForm">
                    <input type="hidden" name="tour_id" value="<?php echo (int) $tourId; ?>">
                    <div class="inline-grid">
                        <div>
                            <label for="tour_title">Tournament Title</label>
                            <input type="text" name="tour_title" id="tour_title" value="<?php echo htmlspecialchars($tournament['tour_title']); ?>" required>
                        </div>
                        <div>
                            <label for="tour_startDate">Start Date</label>
                            <input type="date" name="tour_startDate" id="tour_startDate" value="<?php echo htmlspecialchars(date('Y-m-d', strtotime($tournament['tour_creationDate']))); ?>" required>
                        </div>
                        <div>
                            <label for="tour_endDate">End Date</label>
                            <input type="date" name="tour_endDate" id="tour_endDate" value="<?php echo htmlspecialchars(date('Y-m-d', strtotime($tournament['tour_endDate']))); ?>" required>
                        </div>
                        <?php if ($tournament['tour_type'] === 'League'): ?>
                            <div>
                                <label for="group_count">Group Count</label>
                                <input type="number" name="group_count" id="group_count" min="2" value="<?php echo (int) $tournament['group_count']; ?>">
                            </div>
                            <div>
                                <label for="advancers_per_group">Advancers per Group</label>
                                <input type="number" name="advancers_per_group" id="advancers_per_group" min="1" value="<?php echo (int) $tournament['advancers_per_group']; ?>">
                            </div>
                        <?php elseif ($tournament['tour_type'] === 'Group'): ?>
                            <div>
                                <label for="team_count">Team Count</label>
                                <input type="number" name="team_count" id="team_count" min="2" value="<?php echo (int) $tournament['team_count']; ?>">
                            </div>
                        <?php endif; ?>
                    </div>

                    <h4 style="margin-top:16px;">Current Players</h4>
                    <table class="players-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Status</th>
                                <?php if ($tournament['tour_type'] === 'League'): ?><th>Group</th><?php endif; ?>
                                <th>Remove</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($players as $player): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(player_name($player)); ?></td>
                                    <td>
                                        <select name="player_status[<?php echo (int) $player['plr_idNum']; ?>]">
                                            <?php foreach (['Active', 'Registered', 'Withdrawn'] as $playerStatus): ?>
                                                <option value="<?php echo $playerStatus; ?>" <?php echo $player['player_status'] === $playerStatus ? 'selected' : ''; ?>><?php echo $playerStatus; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <?php if ($tournament['tour_type'] === 'League'): ?><td><?php echo $player['group_number'] !== null ? (int) $player['group_number'] : '-'; ?></td><?php endif; ?>
                                    <td><input type="checkbox" name="remove_players[]" value="<?php echo (int) $player['plr_idNum']; ?>"></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php if (!empty($availablePlayers)): ?>
                        <h4 style="margin-top:16px;">Add Players</h4>
                        <select name="new_players[]" multiple size="6" style="width:100%;">
                            <?php foreach ($availablePlayers as $availablePlayer): ?>
                                <option value="<?php echo (int) $availablePlayer['plr_idNum']; ?>">
                                    <?php echo htmlspecialchars(player_name($availablePlayer)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>

                    <div class="form-buttons" style="margin-top:16px;">
                        <button type="submit" class="submit-btn">Save Tournament</button>
                    </div>
                </form>
            </div>

            <div class="section-card">
                <h3>Players Snapshot</h3>
                <table class="players-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Status</th>
                            <?php if ($tournament['tour_type'] === 'League'): ?><th>Group</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($players as $player): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(player_name($player)); ?></td>
                                <td><?php echo htmlspecialchars($player['player_status']); ?></td>
                                <?php if ($tournament['tour_type'] === 'League'): ?><td><?php echo $player['group_number'] !== null ? (int) $player['group_number'] : '-'; ?></td><?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="section-toggle">
            <button type="button" class="active" data-section-button="matches">Matches</button>
            <?php if ($tournament['tour_type'] === 'Round Robin'): ?>
                <button type="button" data-section-button="standings">Standings</button>
            <?php endif; ?>
            <?php if ($tournament['tour_type'] === 'League'): ?>
                <button type="button" data-section-button="groups">Groups</button>
            <?php endif; ?>
            <?php if ($tournament['tour_type'] === 'Group'): ?>
                <button type="button" data-section-button="teams">Teams</button>
            <?php endif; ?>
            <?php if (!empty($knockoutRounds)): ?>
                <button type="button" data-section-button="bracket">Bracket</button>
            <?php endif; ?>
        </div>

        <section class="page-section active" data-section="matches">
            <div class="header-actions">
                <h2><?php echo $tournament['tour_type'] === 'Group' ? 'Team Fixtures' : 'Matches'; ?></h2>
                <div>
                    <?php if (in_array($tournament['tour_type'], ['Round Robin', 'League'], true)): ?>
                        <button type="button" class="action-btn" id="openLeagueToolsBtn">Reschedule Structure</button>
                    <?php endif; ?>
                    <?php if ($tournament['tour_type'] !== 'Group'): ?>
                        <button type="button" class="action-btn" id="addMatchBtn">Add Match</button>
                    <?php endif; ?>
                    <button type="button" class="action-btn" id="refreshMatchesBtn">Refresh</button>
                </div>
            </div>

            <?php if ($tournament['tour_type'] === 'Group'): ?>
                <table class="matches-table">
                    <thead>
                        <tr>
                            <th>Round</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Team 1</th>
                            <th>Score</th>
                            <th>Team 2</th>
                            <th>Status</th>
                            <th>Record Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teamMatches as $teamMatch): ?>
                            <tr>
                                <td><?php echo (int) $teamMatch['round_number']; ?></td>
                                <td><?php echo htmlspecialchars($teamMatch['match_date']); ?></td>
                                <td><?php echo htmlspecialchars($teamMatch['match_time']); ?></td>
                                <td><?php echo htmlspecialchars((string) ($teamMatch['team1_name'] ?? 'TBD')); ?></td>
                                <td>
                                    <?php if ($teamMatch['match_status'] === 'Completed'): ?>
                                        <?php echo htmlspecialchars((string) $teamMatch['team1_score']); ?> - <?php echo htmlspecialchars((string) $teamMatch['team2_score']); ?>
                                    <?php else: ?>
                                        vs
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars((string) ($teamMatch['team2_name'] ?? 'TBD')); ?></td>
                                <td><?php echo htmlspecialchars($teamMatch['match_status']); ?></td>
                                <td>
                                    <form onsubmit="return saveTeamMatchResult(event, <?php echo (int) $teamMatch['team_match_id']; ?>)" style="display:flex;gap:6px;align-items:center;">
                                        <input type="number" min="0" name="team1_score" style="width:72px;">
                                        <input type="number" min="0" name="team2_score" style="width:72px;">
                                        <button type="submit" class="action-btn">Save</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <table class="matches-table">
                    <thead>
                        <tr>
                            <th>Match #</th>
                            <th>Round</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Bracket</th>
                            <th>Group</th>
                            <th>Player 1</th>
                            <th>Score</th>
                            <th>Player 2</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matches as $match): ?>
                            <tr>
                                <td><?php echo (int) $matchNumbersById[(int) $match['match_id']]; ?></td>
                                <td><?php echo (int) $match['round_number']; ?></td>
                                <td><?php echo htmlspecialchars($match['match_date']); ?></td>
                                <td><?php echo htmlspecialchars($match['match_time']); ?></td>
                                <td><?php echo htmlspecialchars((string) ($match['bracket'] ?? '-')); ?></td>
                                <td><?php echo $match['group_number'] !== null ? (int) $match['group_number'] : '-'; ?></td>
                                <td><?php echo htmlspecialchars(tournament_match_label($match, 'player1', $matchNumbersById)); ?></td>
                                <td>
                                    <?php if ($match['match_status'] === 'Completed'): ?>
                                        <?php echo htmlspecialchars((string) $match['player1_score']); ?> - <?php echo htmlspecialchars((string) $match['player2_score']); ?>
                                    <?php else: ?>
                                        vs
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars(tournament_match_label($match, 'player2', $matchNumbersById)); ?></td>
                                <td><?php echo htmlspecialchars($match['match_status']); ?></td>
                                <td>
                                    <button type="button" class="action-btn" onclick="openMatchModal(<?php echo (int) $match['match_id']; ?>)">Open</button>
                                    <button type="button" class="cancel-btn" onclick="deleteMatch(<?php echo (int) $match['match_id']; ?>)">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <?php if ($tournament['tour_type'] === 'Round Robin'): ?>
            <section class="page-section" data-section="standings">
                <h2>Round Robin Standings</h2>
                <table class="standings-table">
                    <thead>
                        <tr>
                            <th>Position</th>
                            <th>Player</th>
                            <th>Played</th>
                            <th>Won</th>
                            <th>Lost</th>
                            <th>Drawn</th>
                            <th>Points</th>
                            <th>Leg Diff</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($standings as $index => $standing): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars(player_name($standing)); ?></td>
                                <td><?php echo (int) $standing['matches_played']; ?></td>
                                <td><?php echo (int) $standing['matches_won']; ?></td>
                                <td><?php echo (int) $standing['matches_lost']; ?></td>
                                <td><?php echo (int) $standing['matches_drawn']; ?></td>
                                <td><?php echo (int) $standing['points']; ?></td>
                                <td><?php echo (int) $standing['leg_difference']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        <?php endif; ?>

        <?php if ($tournament['tour_type'] === 'League'): ?>
            <section class="page-section" data-section="groups">
                <h2>Group Stage</h2>
                <div class="section-grid">
                    <?php foreach ($groupStandings as $groupNumber => $rows): ?>
                        <div class="section-card">
                            <h3>Group <?php echo (int) $groupNumber; ?></h3>
                            <table class="standings-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Player</th>
                                        <th>Pts</th>
                                        <th>Leg Diff</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rows as $index => $row): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars(player_name($row)); ?></td>
                                            <td><?php echo (int) $row['points']; ?></td>
                                            <td><?php echo (int) $row['leg_difference']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                            <?php if (!empty($groupMatches[$groupNumber])): ?>
                                <h4 style="margin-top:12px;">Matches</h4>
                                <?php foreach ($groupMatches[$groupNumber] as $groupMatch): ?>
                                    <div class="match-card">
                                        <strong><?php echo htmlspecialchars(tournament_match_label($groupMatch, 'player1', $matchNumbersById)); ?></strong>
                                        vs
                                        <strong><?php echo htmlspecialchars(tournament_match_label($groupMatch, 'player2', $matchNumbersById)); ?></strong>
                                        <div class="tag" style="margin-top:8px;">
                                            <?php echo htmlspecialchars($groupMatch['match_status']); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($tournament['tour_type'] === 'Group'): ?>
            <section class="page-section" data-section="teams">
                <h2>Teams & Standings</h2>
                <div class="section-grid">
                    <div class="section-card">
                        <h3>Team Standings</h3>
                        <table class="standings-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Team</th>
                                    <th>Played</th>
                                    <th>Won</th>
                                    <th>Lost</th>
                                    <th>Drawn</th>
                                    <th>Points</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teamStandings as $index => $standing): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($standing['team_name']); ?></td>
                                        <td><?php echo (int) $standing['matches_played']; ?></td>
                                        <td><?php echo (int) $standing['matches_won']; ?></td>
                                        <td><?php echo (int) $standing['matches_lost']; ?></td>
                                        <td><?php echo (int) $standing['matches_drawn']; ?></td>
                                        <td><?php echo (int) $standing['points']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php foreach ($teams as $team): ?>
                        <div class="section-card">
                            <h3><?php echo htmlspecialchars($team['team_name']); ?></h3>
                            <ul>
                                <?php foreach ($team['players'] as $teamPlayer): ?>
                                    <li><?php echo htmlspecialchars(trim($teamPlayer['plr_name'] . ' ' . $teamPlayer['plr_surname'])); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if (!empty($knockoutRounds)): ?>
            <section class="page-section" data-section="bracket">
                <h2>Knockout Bracket</h2>
                <div class="bracket-grid">
                    <?php foreach ($knockoutRounds as $roundNumber => $roundMatches): ?>
                        <div class="bracket-round">
                            <h3><?php echo htmlspecialchars(tournament_round_title($roundNumber)); ?></h3>
                            <?php foreach ($roundMatches as $roundMatch): ?>
                                <div class="match-card">
                                    <div><span class="tag">Match <?php echo (int) $matchNumbersById[(int) $roundMatch['match_id']]; ?></span></div>
                                    <p><?php echo htmlspecialchars(tournament_match_label($roundMatch, 'player1', $matchNumbersById)); ?></p>
                                    <p><?php echo htmlspecialchars(tournament_match_label($roundMatch, 'player2', $matchNumbersById)); ?></p>
                                    <p><?php echo htmlspecialchars($roundMatch['match_status']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <div class="modal-overlay" id="matchModal">
        <div class="modal-card">
            <div class="header-actions">
                <h3>Match Details</h3>
                <button type="button" class="cancel-btn" onclick="closeMatchModal()">Close</button>
            </div>
            <input type="hidden" id="mf_match_id">
            <div class="inline-grid">
                <div><label for="mf_date">Date</label><input type="date" id="mf_date"></div>
                <div><label for="mf_time">Time</label><input type="time" id="mf_time"></div>
                <div><label for="mf_round">Round</label><input type="number" id="mf_round" min="1"></div>
                <div><label for="mf_bracket">Bracket</label><input type="text" id="mf_bracket" maxlength="1"></div>
                <div><label for="mf_group">Group</label><input type="number" id="mf_group" min="1"></div>
                <div><label for="mf_next">Next Match ID</label><input type="number" id="mf_next" min="1"></div>
                <div><label for="mf_pos">Position In Next</label><input type="number" id="mf_pos" min="1" max="2"></div>
                <div><label for="mf_loser_next">Loser Next Match ID</label><input type="number" id="mf_loser_next" min="1"></div>
                <div><label for="mf_loser_pos">Loser Position</label><input type="number" id="mf_loser_pos" min="1" max="2"></div>
                <div>
                    <label for="mf_p1">Player 1</label>
                    <select id="mf_p1"></select>
                </div>
                <div>
                    <label for="mf_p2">Player 2</label>
                    <select id="mf_p2"></select>
                </div>
                <div><label for="mf_p1s">Player 1 Score</label><input type="number" id="mf_p1s" min="0"></div>
                <div><label for="mf_p2s">Player 2 Score</label><input type="number" id="mf_p2s" min="0"></div>
            </div>
            <div class="form-buttons" style="margin-top:16px;">
                <button type="button" class="action-btn" onclick="saveMatchFields()">Save Match</button>
                <button type="button" class="submit-btn" onclick="saveMatchResult()">Record Result</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="leagueToolsModal">
        <div class="modal-card">
            <div class="header-actions">
                <h3>Structure Rescheduling</h3>
                <button type="button" class="cancel-btn" onclick="closeLeagueToolsModal()">Close</button>
            </div>
            <div class="inline-grid">
                <div>
                    <label for="lt_startdate">New Start Date</label>
                    <input type="date" id="lt_startdate" value="<?php echo htmlspecialchars(date('Y-m-d', strtotime($tournament['tour_creationDate']))); ?>">
                </div>
            </div>
            <div class="form-buttons" style="margin-top:16px;">
                <button type="button" class="submit-btn" onclick="submitLeagueTools()">Apply</button>
            </div>
        </div>
    </div>

    <script>
        window.TOURNAMENT_PAGE = {
            tourId: <?php echo (int) $tourId; ?>,
            type: <?php echo json_encode($tournament['tour_type']); ?>,
            players: <?php echo json_encode($playersForJs); ?>
        };
    </script>
    <script src="../../js/admin_tournament_details.js"></script>
</body>
</html>
