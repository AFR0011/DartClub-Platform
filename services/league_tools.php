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
    tournament_assert_mutable($conn, $tourId);
    if (!in_array($tournament['tour_type'], ['Round Robin', 'League'], true)) {
        throw new RuntimeException('Rescheduling is only available for round robin and league tournaments.');
    }

    if (tournament_has_completed_matches($conn, $tourId)) {
        throw new RuntimeException('Cannot reschedule a league after completed matches exist.');
    }

    $conn->begin_transaction();
    $transactionStarted = true;
    $playerIds = tournament_fetch_player_ids($conn, $tourId);
    tournament_rebuild_structure(
        $conn,
        $tourId,
        $tournament['tour_type'],
        $playerIds,
        $startDate,
        $tournament['group_count'] !== null ? (int) $tournament['group_count'] : null,
        $tournament['team_count'] !== null ? (int) $tournament['team_count'] : null
    );
    $conn->commit();

    app_json_response(['success' => true, 'created' => count($playerIds)]);
} catch (Throwable $exception) {
    if ($transactionStarted) {
        try {
            $conn->rollback();
        } catch (Throwable $ignored) {
        }
    }
    app_json_response(['success' => false, 'message' => app_safe_error_message($exception)], 422);
}
