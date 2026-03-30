<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/shared/player_helpers.php';

app_start_session();

if (!is_logged_in()) {
    app_json_response(['success' => false, 'message' => 'Not logged in'], 401);
}

$userId = get_current_user_id();
$player = player_fetch_for_user($conn, $userId);

$userStmt = $conn->prepare(
    'SELECT user_id, user_name, email, user_role, membership_status, member_since, membership_approved_at
     FROM users
     WHERE user_id = ?'
);
$userStmt->bind_param('i', $userId);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

$membershipApplication = null;
$membershipStmt = $conn->prepare(
    'SELECT application_id, status, application_file_path, submitted_at, reviewed_at, reviewer_notes
     FROM membership_applications
     WHERE user_id = ?
     ORDER BY submitted_at DESC
     LIMIT 1'
);
$membershipStmt->bind_param('i', $userId);
$membershipStmt->execute();
$membershipApplication = $membershipStmt->get_result()->fetch_assoc();
$membershipStmt->close();

$stats = [
    'registered_tournaments' => 0,
    'active_tournaments' => 0,
    'completed_tournaments' => 0,
    'individual_matches_played' => 0,
    'individual_matches_won' => 0,
    'team_matches_played' => 0,
    'team_matches_won' => 0,
];

$tournaments = [];
$upcomingMatches = [];

if ($player && !empty($player['plr_idNum'])) {
    $playerId = (int) $player['plr_idNum'];

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

    $upcomingIndividualStmt = $conn->prepare(
        "SELECT
            m.match_id AS item_id,
            'individual' AS item_type,
            t.tour_id,
            t.tour_title,
            m.match_date,
            m.match_time,
            p1.plr_name AS player1_name,
            p1.plr_surname AS player1_surname,
            p2.plr_name AS player2_name,
            p2.plr_surname AS player2_surname
         FROM matches m
         JOIN tournaments t ON t.tour_id = m.tour_id
         LEFT JOIN players p1 ON p1.plr_idNum = m.player1_id
         LEFT JOIN players p2 ON p2.plr_idNum = m.player2_id
         WHERE (m.player1_id = ? OR m.player2_id = ?)
           AND m.match_status = 'Scheduled'
         ORDER BY m.match_date, m.match_time
         LIMIT 10"
    );
    $upcomingIndividualStmt->bind_param('ii', $playerId, $playerId);
    $upcomingIndividualStmt->execute();
    $upcomingMatches = $upcomingIndividualStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $upcomingIndividualStmt->close();

    $upcomingTeamStmt = $conn->prepare(
        "SELECT
            tm.team_match_id AS item_id,
            'team' AS item_type,
            t.tour_id,
            t.tour_title,
            tm.match_date,
            tm.match_time,
            t1.team_name AS player1_name,
            '' AS player1_surname,
            t2.team_name AS player2_name,
            '' AS player2_surname
         FROM tournament_team_players ttp
         JOIN tournaments t ON t.tour_id = ttp.tour_id
         JOIN team_matches tm ON tm.tour_id = ttp.tour_id AND (tm.team1_id = ttp.team_id OR tm.team2_id = ttp.team_id)
         LEFT JOIN tournament_teams t1 ON t1.team_id = tm.team1_id
         LEFT JOIN tournament_teams t2 ON t2.team_id = tm.team2_id
         WHERE ttp.player_id = ?
           AND tm.match_status = 'Scheduled'
         ORDER BY tm.match_date, tm.match_time
         LIMIT 10"
    );
    $upcomingTeamStmt->bind_param('i', $playerId);
    $upcomingTeamStmt->execute();
    $teamUpcoming = $upcomingTeamStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $upcomingTeamStmt->close();

    $upcomingMatches = array_merge($upcomingMatches, $teamUpcoming);
    usort($upcomingMatches, static function (array $left, array $right): int {
        $leftKey = ($left['match_date'] ?? '') . ' ' . ($left['match_time'] ?? '');
        $rightKey = ($right['match_date'] ?? '') . ' ' . ($right['match_time'] ?? '');

        return strcmp($leftKey, $rightKey);
    });
}

app_json_response([
    'success' => true,
    'user' => $user,
    'player' => $player,
    'membership_application' => $membershipApplication,
    'stats' => $stats,
    'tournaments' => $tournaments,
    'upcoming_matches' => array_slice($upcomingMatches, 0, 10),
]);

