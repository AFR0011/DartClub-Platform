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
if (!$input || !isset($input['tour_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

$tour_id = (int)$input['tour_id'];
$match_date = $input['match_date'] ?? date('Y-m-d');
$match_time = $input['match_time'] ?? date('H:i:s');
$player1_id = $input['player1_id'] ?? null;
$player2_id = $input['player2_id'] ?? null;
$round_number = isset($input['round_number']) ? (int) $input['round_number'] : 1;
$next_match_id = $input['next_match_id'] ?? null;
$position_in_next = $input['position_in_next'] ?? 1;
$match_status = $input['match_status'] ?? 'Scheduled';
$bracket = $input['bracket'] ?? null;
$group_number = $input['group_number'] ?? null;
$loser_next_match_id = $input['loser_next_match_id'] ?? null;
$loser_position_in_next = $input['loser_position_in_next'] ?? null;

$sql = "INSERT INTO matches (tour_id, match_date, match_time, player1_id, player2_id, round_number, next_match_id, position_in_next, match_status, bracket, group_number, loser_next_match_id, loser_position_in_next)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('issiiiiissiii', $tour_id, $match_date, $match_time, $player1_id, $player2_id, $round_number, $next_match_id, $position_in_next, $match_status, $bracket, $group_number, $loser_next_match_id, $loser_position_in_next);
$ok = $stmt->execute();
$match_id = $ok ? $conn->insert_id : null;
$stmt->close();

echo json_encode(['success' => $ok, 'match_id' => $match_id]);
