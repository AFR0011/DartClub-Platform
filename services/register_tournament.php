<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/shared/player_helpers.php';
require_once __DIR__ . '/shared/tournament_helpers.php';

app_start_session();

if (!isset($_SESSION['user_id'])) {
    app_json_response(['success' => false, 'message' => 'You must be logged in to register for tournaments.'], 401);
}

$input = app_read_json_input();
$tournamentId = isset($input['tournament_id']) ? (int) $input['tournament_id'] : 0;
if ($tournamentId <= 0) {
    app_json_response(['success' => false, 'message' => 'Tournament ID is required.'], 422);
}

try {
    $playerId = player_id_for_user($conn, (int) $_SESSION['user_id']);
    if (!$playerId) {
        app_json_response(['success' => false, 'message' => 'Please complete your player profile before registering for tournaments.'], 422);
    }

    $tournament = tournament_refresh_lifecycle($conn, $tournamentId);
    if (!$tournament) {
        app_json_response(['success' => false, 'message' => 'Tournament not found.'], 404);
    }

    if (($tournament['status'] ?? '') !== 'registration_open') {
        app_json_response(['success' => false, 'message' => 'Tournament registration is currently closed.'], 422);
    }

    if ((int) ($tournament['is_public'] ?? 0) !== 1) {
        app_json_response(['success' => false, 'message' => 'Tournament registration is unavailable.'], 422);
    }

    $existingStmt = $conn->prepare(
        'SELECT player_status
         FROM tournament_players
         WHERE tour_id = ? AND plr_id = ?'
    );
    $existingStmt->bind_param('ii', $tournamentId, $playerId);
    $existingStmt->execute();
    $existing = $existingStmt->get_result()->fetch_assoc();
    $existingStmt->close();

    if ($existing) {
        app_json_response(['success' => false, 'message' => 'You are already registered for this tournament.']);
    }

    $insert = $conn->prepare(
        'INSERT INTO tournament_players (tour_id, plr_id, player_status, group_number)
         VALUES (?, ?, ?, NULL)'
    );
    $status = 'Registered';
    $insert->bind_param('iis', $tournamentId, $playerId, $status);
    $insert->execute();
    $insert->close();

    app_json_response([
        'success' => true,
        'message' => 'Successfully registered for tournament. A manager can promote your registration into the active competition roster from the admin screen.',
    ]);
} catch (Throwable $exception) {
    app_json_response(['success' => false, 'message' => 'Database error: ' . $exception->getMessage()], 500);
}

