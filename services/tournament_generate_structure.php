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
$startTournament = !empty($input['start_tournament']);
if ($tourId <= 0) {
    app_json_response(['success' => false, 'message' => 'Invalid tournament id.'], 422);
}

$transactionStarted = false;

try {
    $conn->begin_transaction();
    $transactionStarted = true;
    if ($startTournament) {
        tournament_force_close_registration($conn, $tourId);
    }
    $entrantCount = tournament_generate_structure($conn, $tourId);
    $conn->commit();

    app_json_response([
        'success' => true,
        'entrants' => $entrantCount,
        'started' => $startTournament,
    ]);
} catch (Throwable $exception) {
    if ($transactionStarted) {
        try {
            $conn->rollback();
        } catch (Throwable $ignored) {
        }
    }

    app_json_response(['success' => false, 'message' => app_safe_error_message($exception)], 422);
}
