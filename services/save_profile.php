<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/shared/player_helpers.php';

app_start_session();

if (!is_logged_in()) {
    app_json_response(['success' => false, 'message' => 'You must be logged in.'], 401);
}

$payload = app_read_json_input();

try {
    $playerId = player_create_or_update_for_user($conn, get_current_user_id(), $payload);
    $player = player_fetch_for_user($conn, get_current_user_id());

    app_json_response([
        'success' => true,
        'player_id' => $playerId,
        'player' => $player,
    ]);
} catch (Throwable $exception) {
    app_json_response(['success' => false, 'message' => $exception->getMessage()], 422);
}

