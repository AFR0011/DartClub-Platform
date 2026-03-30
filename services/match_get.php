<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'dbConnection.php';
require_once 'auth.php';

header('Content-Type: application/json');

if (!in_array(get_current_role(), ['admin','manager'], true)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$match_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($match_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid id']);
    exit();
}

$stmt = $conn->prepare("SELECT match_id, tour_id, match_date, match_time, round_number, match_status, bracket, group_number, player1_id, player2_id, player1_score, player2_score, next_match_id, position_in_next, loser_next_match_id, loser_position_in_next FROM matches WHERE match_id = ?");
$stmt->bind_param('i', $match_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Not found']);
} else {
    echo json_encode(['success' => true, 'match' => $res->fetch_assoc()]);
}
$stmt->close();
$conn->close();
