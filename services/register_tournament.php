<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/shared/player_helpers.php';

app_start_session();

if (!isset($_SESSION['user_id'])) {
    app_json_response(['success' => false, 'message' => 'You must be logged in to register for tournaments'], 401);
}

$input = app_read_json_input();
$tournamentId = isset($input['tournament_id']) ? (int) $input['tournament_id'] : 0;
if ($tournamentId <= 0) {
    app_json_response(['success' => false, 'message' => 'Tournament ID is required'], 422);
}

try {
    $playerId = player_id_for_user($conn, (int) $_SESSION['user_id']);
    if (!$playerId) {
        app_json_response(['success' => false, 'message' => 'Player profile not found. Please contact an administrator.'], 404);
    }

    $tournamentStmt = $conn->prepare('SELECT tour_title, tour_endDate FROM tournaments WHERE tour_id = ?');
    $tournamentStmt->bind_param('i', $tournamentId);
    $tournamentStmt->execute();
    $tournamentResult = $tournamentStmt->get_result();
    $tournament = $tournamentResult->fetch_assoc();
    $tournamentStmt->close();

    if (!$tournament) {
        app_json_response(['success' => false, 'message' => 'Tournament not found'], 404);
    }

    if (new DateTime() > new DateTime($tournament['tour_endDate'])) {
        app_json_response(['success' => false, 'message' => 'Tournament registration is closed'], 422);
    }

    $existingStmt = $conn->prepare('SELECT player_status FROM tournament_players WHERE tour_id = ? AND plr_id = ?');
    $existingStmt->bind_param('ii', $tournamentId, $playerId);
    $existingStmt->execute();
    $existing = $existingStmt->get_result()->fetch_assoc();
    $existingStmt->close();

    if ($existing) {
        app_json_response(['success' => false, 'message' => 'You are already registered for this tournament']);
    }

    $insert = $conn->prepare('INSERT INTO tournament_players (tour_id, plr_id, player_status, group_number) VALUES (?, ?, ?, NULL)');
    $status = 'Registered';
    $insert->bind_param('iis', $tournamentId, $playerId, $status);
    $insert->execute();
    $insert->close();

    app_json_response([
        'success' => true,
        'message' => 'Successfully registered for tournament. An admin must include you in the active tournament bracket before matches are generated.',
    ]);
} catch (Throwable $exception) {
    app_json_response(['success' => false, 'message' => 'Database error: ' . $exception->getMessage()], 500);
}
