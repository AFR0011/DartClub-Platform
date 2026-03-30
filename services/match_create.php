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
$tourId = isset($input['tour_id']) ? (int) $input['tour_id'] : 0;
if ($tourId <= 0) {
    app_json_response(['success' => false, 'message' => 'Invalid tournament.'], 422);
}

try {
    tournament_assert_mutable($conn, $tourId);
    $matchId = tournament_insert_match($conn, [
        'tour_id' => $tourId,
        'match_date' => $input['match_date'] ?? date('Y-m-d'),
        'match_time' => $input['match_time'] ?? date('H:i:s'),
        'player1_id' => $input['player1_id'] ?? null,
        'player2_id' => $input['player2_id'] ?? null,
        'round_number' => isset($input['round_number']) ? (int) $input['round_number'] : 1,
        'next_match_id' => $input['next_match_id'] ?? null,
        'position_in_next' => $input['position_in_next'] ?? 1,
        'match_status' => $input['match_status'] ?? 'Scheduled',
        'bracket' => $input['bracket'] ?? null,
        'group_number' => $input['group_number'] ?? null,
        'loser_next_match_id' => $input['loser_next_match_id'] ?? null,
        'loser_position_in_next' => $input['loser_position_in_next'] ?? null,
    ]);
    tournament_refresh_lifecycle($conn, $tourId);

    app_json_response(['success' => true, 'match_id' => $matchId]);
} catch (Throwable $exception) {
    app_json_response(['success' => false, 'message' => $exception->getMessage()], 422);
}

