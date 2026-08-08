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
$matchId = isset($input['match_id']) ? (int) $input['match_id'] : 0;
if ($matchId <= 0) {
    app_json_response(['success' => false, 'message' => 'Invalid match id'], 422);
}

try {
    $match = tournament_fetch_match($conn, $matchId);
    tournament_assert_mutable($conn, (int) $match['tour_id']);

    $fields = [];
    $params = [];
    $types = '';
    $allowed = [
        'match_date' => 's',
        'match_time' => 's',
        'player1_id' => 'i',
        'player2_id' => 'i',
        'round_number' => 'i',
        'next_match_id' => 'i',
        'position_in_next' => 'i',
        'match_status' => 's',
        'bracket' => 's',
        'group_number' => 'i',
        'loser_next_match_id' => 'i',
        'loser_position_in_next' => 'i'
    ];

    foreach ($allowed as $key => $type) {
        if (array_key_exists($key, $input)) {
            $fields[] = $key . ' = ?';
            $params[] = $input[$key];
            $types .= $type;
        }
    }

    if (empty($fields)) {
        app_json_response(['success' => false, 'message' => 'No fields to update'], 422);
    }

    $sql = 'UPDATE matches SET ' . implode(', ', $fields) . ' WHERE match_id = ?';
    $params[] = $matchId;
    $types .= 'i';

    $stmt = $conn->prepare($sql);
    $bindParams = [$types];
    foreach ($params as $index => $value) {
        $bindParams[] = &$params[$index];
    }
    call_user_func_array([$stmt, 'bind_param'], $bindParams);
    $stmt->execute();
    $stmt->close();

    tournament_refresh_lifecycle($conn, (int) $match['tour_id']);
    app_json_response(['success' => true]);
} catch (Throwable $exception) {
    app_json_response(['success' => false, 'message' => app_safe_error_message($exception)], 422);
}

