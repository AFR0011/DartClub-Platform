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
$sourceMatchId = isset($input['source_match_id']) ? (int) $input['source_match_id'] : 0;
$targetMatchId = isset($input['target_match_id']) ? (int) $input['target_match_id'] : 0;
$sourceSlotKey = trim((string) ($input['source_slot'] ?? ''));
$targetSlotKey = trim((string) ($input['target_slot'] ?? ''));

$slotMap = [
    'player1' => 'player1_id',
    'player2' => 'player2_id',
];

if ($sourceMatchId <= 0 || $targetMatchId <= 0 || !isset($slotMap[$sourceSlotKey]) || !isset($slotMap[$targetSlotKey])) {
    app_json_response(['success' => false, 'message' => 'Invalid drag-and-drop payload.'], 422);
}

function match_swap_update_slot(mysqli $db, int $matchId, string $field, ?int $playerId): void
{
    if ($playerId === null) {
        $sql = sprintf(
            "UPDATE matches
             SET %s = NULL,
                 winner_id = NULL,
                 player1_score = NULL,
                 player2_score = NULL,
                 match_status = 'Scheduled'
             WHERE match_id = ?",
            $field
        );
        $stmt = $db->prepare($sql);
        $stmt->bind_param('i', $matchId);
        $stmt->execute();
        $stmt->close();
        return;
    }

    $sql = sprintf(
        "UPDATE matches
         SET %s = ?,
             winner_id = NULL,
             player1_score = NULL,
             player2_score = NULL,
             match_status = 'Scheduled'
         WHERE match_id = ?",
        $field
    );
    $stmt = $db->prepare($sql);
    $stmt->bind_param('ii', $playerId, $matchId);
    $stmt->execute();
    $stmt->close();
}

try {
    $conn->begin_transaction();

    $sourceMatch = tournament_fetch_match($conn, $sourceMatchId);
    $targetMatch = $sourceMatchId === $targetMatchId ? $sourceMatch : tournament_fetch_match($conn, $targetMatchId);

    if ((int) $sourceMatch['tour_id'] !== (int) $targetMatch['tour_id']) {
        throw new RuntimeException('Players can only be swapped inside the same tournament.');
    }

    tournament_assert_mutable($conn, (int) $sourceMatch['tour_id']);

    if (($sourceMatch['match_status'] ?? '') === 'Completed' || ($targetMatch['match_status'] ?? '') === 'Completed') {
        throw new RuntimeException('Completed matches cannot be rearranged with drag and drop.');
    }

    $sourceField = $slotMap[$sourceSlotKey];
    $targetField = $slotMap[$targetSlotKey];
    $sourcePlayerId = !empty($sourceMatch[$sourceField]) ? (int) $sourceMatch[$sourceField] : null;
    $targetPlayerId = !empty($targetMatch[$targetField]) ? (int) $targetMatch[$targetField] : null;

    if ($sourcePlayerId === null && $targetPlayerId === null) {
        throw new RuntimeException('There is no player to move.');
    }

    match_swap_update_slot($conn, $sourceMatchId, $sourceField, $targetPlayerId);
    match_swap_update_slot($conn, $targetMatchId, $targetField, $sourcePlayerId);

    tournament_refresh_lifecycle($conn, (int) $sourceMatch['tour_id']);
    $conn->commit();

    app_json_response(['success' => true]);
} catch (Throwable $exception) {
    try {
        $conn->rollback();
    } catch (Throwable $ignored) {
    }

    app_json_response(['success' => false, 'message' => app_safe_error_message($exception)], 422);
}
