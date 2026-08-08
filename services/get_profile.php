<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/shared/player_helpers.php';

app_start_session();

if (!isset($_SESSION['user_id'])) {
    app_json_response(['success' => false, 'message' => 'Not logged in'], 401);
}

try {
    $player = player_fetch_for_user($conn, (int) $_SESSION['user_id']);

    if (!$player) {
        app_json_response(['success' => false, 'message' => 'Player not found'], 404);
    }

    app_json_response(['success' => true, 'player' => $player]);
} catch (Throwable $exception) {
    app_json_response(['success' => false, 'message' => 'Database error: ' . app_safe_error_message($exception)], 500);
}
