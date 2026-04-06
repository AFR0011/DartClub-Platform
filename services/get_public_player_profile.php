<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/shared/player_helpers.php';

$playerId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($playerId <= 0) {
    app_json_response(['success' => false, 'message' => 'Invalid player id.'], 422);
}

$player = player_fetch_public($conn, $playerId);
if (!$player) {
    app_json_response(['success' => false, 'message' => 'Player not found.'], 404);
}

$stats = [
    'registered_tournaments' => 0,
    'active_tournaments' => 0,
    'completed_tournaments' => 0,
    'individual_matches_played' => 0,
    'individual_matches_won' => 0,
    'team_matches_played' => 0,
    'team_matches_won' => 0,
];

$tournamentsStmt = $conn->prepare(
    "SELECT
        t.tour_id,
        t.tour_title,
        t.tour_type,
        t.status,
        t.tour_creationDate,
        t.tour_endDate,
        t.winner_label,
        tp.player_status,
        tp.group_number,
        tp.final_rank,
        tp.placement_label,
        tp.registration_date
     FROM tournament_players tp
     JOIN tournaments t ON t.tour_id = tp.tour_id
     WHERE tp.plr_id = ?
     ORDER BY tp.registration_date DESC"
);
$tournamentsStmt->bind_param('i', $playerId);
$tournamentsStmt->execute();
$tournaments = $tournamentsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$tournamentsStmt->close();

$stats['registered_tournaments'] = count($tournaments);
foreach ($tournaments as $tournament) {
    if (in_array($tournament['status'], ['registration_open', 'registration_closed', 'in_progress'], true)) {
        $stats['active_tournaments']++;
    }
    if (in_array($tournament['status'], ['completed', 'archived'], true)) {
        $stats['completed_tournaments']++;
    }
}

$individualStatsStmt = $conn->prepare(
    "SELECT
        COUNT(*) AS played,
        SUM(CASE WHEN winner_id = ? THEN 1 ELSE 0 END) AS won
     FROM matches
     WHERE (player1_id = ? OR player2_id = ?)
       AND match_status = 'Completed'"
);
$individualStatsStmt->bind_param('iii', $playerId, $playerId, $playerId);
$individualStatsStmt->execute();
$individualStats = $individualStatsStmt->get_result()->fetch_assoc();
$individualStatsStmt->close();

$stats['individual_matches_played'] = (int) ($individualStats['played'] ?? 0);
$stats['individual_matches_won'] = (int) ($individualStats['won'] ?? 0);

$teamStatsStmt = $conn->prepare(
    "SELECT
        COUNT(*) AS played,
        SUM(
            CASE
                WHEN tm.winner_team_id = ttp.team_id THEN 1
                ELSE 0
            END
        ) AS won
     FROM tournament_team_players ttp
     JOIN team_matches tm ON tm.tour_id = ttp.tour_id AND (tm.team1_id = ttp.team_id OR tm.team2_id = ttp.team_id)
     WHERE ttp.player_id = ?
       AND tm.match_status = 'Completed'"
);
$teamStatsStmt->bind_param('i', $playerId);
$teamStatsStmt->execute();
$teamStats = $teamStatsStmt->get_result()->fetch_assoc();
$teamStatsStmt->close();

$stats['team_matches_played'] = (int) ($teamStats['played'] ?? 0);
$stats['team_matches_won'] = (int) ($teamStats['won'] ?? 0);

$recentResultsStmt = $conn->prepare(
    "SELECT
        m.match_id AS result_id,
        'individual' AS result_type,
        t.tour_id,
        t.tour_title,
        m.match_date,
        m.match_time,
        p1.plr_name AS player1_name,
        p1.plr_surname AS player1_surname,
        p2.plr_name AS player2_name,
        p2.plr_surname AS player2_surname,
        m.player1_score,
        m.player2_score
     FROM matches m
     JOIN tournaments t ON t.tour_id = m.tour_id
     LEFT JOIN players p1 ON p1.plr_idNum = m.player1_id
     LEFT JOIN players p2 ON p2.plr_idNum = m.player2_id
     WHERE (m.player1_id = ? OR m.player2_id = ?)
       AND m.match_status = 'Completed'
     ORDER BY m.match_date DESC, m.match_time DESC, m.match_id DESC
     LIMIT 8"
);
$recentResultsStmt->bind_param('ii', $playerId, $playerId);
$recentResultsStmt->execute();
$recentResults = $recentResultsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$recentResultsStmt->close();

app_json_response([
    'success' => true,
    'player' => $player,
    'stats' => $stats,
    'tournaments' => $tournaments,
    'recent_results' => $recentResults,
]);
