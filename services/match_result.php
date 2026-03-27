<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/shared/tournament_helpers.php';

app_start_session();

if (!in_array(get_current_role(), ['admin', 'manager'], true)) {
    app_json_response(['success' => false, 'message' => 'Unauthorized'], 403);
}

$input = app_read_json_input();
$matchId = isset($input['match_id']) ? (int) $input['match_id'] : 0;
$player1Score = isset($input['player1_score']) ? (int) $input['player1_score'] : 0;
$player2Score = isset($input['player2_score']) ? (int) $input['player2_score'] : 0;

if ($matchId <= 0) {
    app_json_response(['success' => false, 'message' => 'Invalid match id'], 422);
}

try {
    $conn->begin_transaction();
    tournament_record_match_result($conn, $matchId, $player1Score, $player2Score);
    $conn->commit();

    app_json_response(['success' => true]);
} catch (Throwable $exception) {
    $conn->rollback();
    app_json_response(['success' => false, 'message' => $exception->getMessage()], 422);
}
