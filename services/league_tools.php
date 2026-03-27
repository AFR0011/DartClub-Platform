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
$tourId = isset($input['tour_id']) ? (int) $input['tour_id'] : 0;
$startDate = trim((string) ($input['start_date'] ?? ''));
if ($tourId <= 0 || $startDate === '') {
    app_json_response(['success' => false, 'message' => 'Invalid input'], 422);
}

$transactionStarted = false;

try {
    $tournament = tournament_fetch_settings($conn, $tourId);
    if ($tournament['tour_type'] !== 'League') {
        throw new RuntimeException('League tools only work for league tournaments.');
    }

    if (tournament_has_completed_matches($conn, $tourId)) {
        throw new RuntimeException('Cannot reschedule a league after completed matches exist.');
    }

    $conn->begin_transaction();
    $transactionStarted = true;
    $playerIds = tournament_fetch_player_ids($conn, $tourId);
    tournament_rebuild_structure($conn, $tourId, 'League', $playerIds, $startDate, null);
    $conn->commit();

    app_json_response(['success' => true, 'created' => count($playerIds)]);
} catch (Throwable $exception) {
    if ($transactionStarted) {
        try {
            $conn->rollback();
        } catch (Throwable $ignored) {
        }
    }
    app_json_response(['success' => false, 'message' => $exception->getMessage()], 422);
}
