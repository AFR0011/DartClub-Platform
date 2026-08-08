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
    app_json_response(['success' => false, 'message' => 'Invalid input'], 422);
}

try {
    $conn->begin_transaction();
    $match = tournament_fetch_match($conn, $matchId);
    tournament_assert_mutable($conn, (int) $match['tour_id']);

    if (!empty($match['next_match_id'])) {
        $field = ((int) $match['position_in_next'] === 2) ? 'player2_id' : 'player1_id';
        $stmt = $conn->prepare("UPDATE matches SET {$field} = NULL WHERE match_id = ?");
        $nextMatchId = (int) $match['next_match_id'];
        $stmt->bind_param('i', $nextMatchId);
        $stmt->execute();
        $stmt->close();
    }

    if (!empty($match['loser_next_match_id'])) {
        $field = ((int) $match['loser_position_in_next'] === 2) ? 'player2_id' : 'player1_id';
        $stmt = $conn->prepare("UPDATE matches SET {$field} = NULL WHERE match_id = ?");
        $loserNextMatchId = (int) $match['loser_next_match_id'];
        $stmt->bind_param('i', $loserNextMatchId);
        $stmt->execute();
        $stmt->close();
    }

    $detachWinners = $conn->prepare('UPDATE matches SET next_match_id = NULL, position_in_next = 1 WHERE next_match_id = ?');
    $detachWinners->bind_param('i', $matchId);
    $detachWinners->execute();
    $detachWinners->close();

    $detachLosers = $conn->prepare('UPDATE matches SET loser_next_match_id = NULL, loser_position_in_next = NULL WHERE loser_next_match_id = ?');
    $detachLosers->bind_param('i', $matchId);
    $detachLosers->execute();
    $detachLosers->close();

    $delete = $conn->prepare('DELETE FROM matches WHERE match_id = ?');
    $delete->bind_param('i', $matchId);
    $delete->execute();
    $delete->close();

    tournament_refresh_lifecycle($conn, (int) $match['tour_id']);
    $conn->commit();
    app_json_response(['success' => true]);
} catch (Throwable $exception) {
    $conn->rollback();
    app_json_response(['success' => false, 'message' => app_safe_error_message($exception)], 422);
}

