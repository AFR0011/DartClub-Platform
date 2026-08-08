<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/auth.php';

app_start_session();

if (!is_logged_in() || get_current_role() !== 'admin') {
    app_json_response(['success' => false, 'message' => 'Unauthorized access'], 403);
}

$input = app_read_json_input();
$userId = isset($input['user_id']) ? (int) $input['user_id'] : 0;

if ($userId <= 0) {
    app_json_response(['success' => false, 'message' => 'User ID is required'], 422);
}

if ($userId === get_current_user_id()) {
    app_json_response(['success' => false, 'message' => 'Cannot delete your own account'], 422);
}

$conn->begin_transaction();

try {
    $playerLookup = $conn->prepare('SELECT plr_idNum FROM players WHERE user_id = ? LIMIT 1');
    $playerLookup->bind_param('i', $userId);
    $playerLookup->execute();
    $playerRow = $playerLookup->get_result()->fetch_assoc();
    $playerLookup->close();

    if ($playerRow && !empty($playerRow['plr_idNum'])) {
        $playerId = (int) $playerRow['plr_idNum'];
        $deletePlayer = $conn->prepare('DELETE FROM players WHERE plr_idNum = ?');
        $deletePlayer->bind_param('i', $playerId);
        $deletePlayer->execute();
        $deletePlayer->close();
    }

    $deleteUser = $conn->prepare('DELETE FROM users WHERE user_id = ?');
    $deleteUser->bind_param('i', $userId);
    $deleteUser->execute();

    if ($deleteUser->affected_rows !== 1) {
        $deleteUser->close();
        throw new RuntimeException('User not found.');
    }

    $deleteUser->close();
    $conn->commit();

    app_json_response(['success' => true, 'message' => 'User deleted successfully']);
} catch (Throwable $exception) {
    $conn->rollback();
    app_json_response(['success' => false, 'message' => app_safe_error_message($exception)], 500);
}
