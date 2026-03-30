<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/shared/tournament_helpers.php';

app_start_session();

if (!is_manager_or_admin()) {
    app_json_response(['success' => false, 'message' => 'Unauthorized'], 403);
}

$input = app_read_json_input();
$teamMatchId = isset($input['team_match_id']) ? (int) $input['team_match_id'] : 0;
$team1Score = isset($input['team1_score']) ? (int) $input['team1_score'] : 0;
$team2Score = isset($input['team2_score']) ? (int) $input['team2_score'] : 0;

if ($teamMatchId <= 0) {
    app_json_response(['success' => false, 'message' => 'Invalid team match id.'], 422);
}

try {
    $conn->begin_transaction();
    tournament_record_team_match_result($conn, $teamMatchId, $team1Score, $team2Score);
    $conn->commit();

    app_json_response(['success' => true]);
} catch (Throwable $exception) {
    $conn->rollback();
    app_json_response(['success' => false, 'message' => $exception->getMessage()], 422);
}

