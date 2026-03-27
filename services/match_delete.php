<?php
if (!session_status()) session_start();
require_once 'dbConnection.php';
require_once 'auth.php';

header('Content-Type: application/json');

if (!in_array(get_current_role(), ['admin','manager'], true)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['match_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

$match_id = (int)$input['match_id'];

try {
    $conn->begin_transaction();

    $matchStmt = $conn->prepare("SELECT next_match_id, position_in_next, loser_next_match_id, loser_position_in_next FROM matches WHERE match_id = ?");
    $matchStmt->bind_param('i', $match_id);
    $matchStmt->execute();
    $match = $matchStmt->get_result()->fetch_assoc();
    $matchStmt->close();

    if (!$match) {
        throw new RuntimeException('Match not found');
    }

    if (!empty($match['next_match_id'])) {
        $field = ((int)$match['position_in_next'] === 2) ? 'player2_id' : 'player1_id';
        $stmt = $conn->prepare("UPDATE matches SET {$field} = NULL WHERE match_id = ?");
        $nextMatchId = (int) $match['next_match_id'];
        $stmt->bind_param('i', $nextMatchId);
        $stmt->execute();
        $stmt->close();
    }

    if (!empty($match['loser_next_match_id'])) {
        $field = ((int)$match['loser_position_in_next'] === 2) ? 'player2_id' : 'player1_id';
        $stmt = $conn->prepare("UPDATE matches SET {$field} = NULL WHERE match_id = ?");
        $loserNextMatchId = (int) $match['loser_next_match_id'];
        $stmt->bind_param('i', $loserNextMatchId);
        $stmt->execute();
        $stmt->close();
    }

    $detachWinners = $conn->prepare("UPDATE matches SET next_match_id = NULL, position_in_next = 1 WHERE next_match_id = ?");
    $detachWinners->bind_param('i', $match_id);
    $detachWinners->execute();
    $detachWinners->close();

    $detachLosers = $conn->prepare("UPDATE matches SET loser_next_match_id = NULL, loser_position_in_next = NULL WHERE loser_next_match_id = ?");
    $detachLosers->bind_param('i', $match_id);
    $detachLosers->execute();
    $detachLosers->close();

    // Remove the match itself
    $del = $conn->prepare("DELETE FROM matches WHERE match_id = ?");
    $del->bind_param('i', $match_id);
    $ok = $del->execute();
    $del->close();

    $conn->commit();
    echo json_encode(['success' => $ok]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
