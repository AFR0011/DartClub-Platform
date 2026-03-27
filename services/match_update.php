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

foreach ($allowed as $key => $t) {
    if (array_key_exists($key, $input)) {
        $fields[] = "$key = ?";
        $params[] = $input[$key];
        $types .= $t;
    }
}

if (empty($fields)) {
    echo json_encode(['success' => false, 'message' => 'No fields to update']);
    exit();
}

$sql = "UPDATE matches SET " . implode(', ', $fields) . " WHERE match_id = ?";
$types .= 'i';
$params[] = $match_id;

$stmt = $conn->prepare($sql);
$bindParams = [$types];
foreach ($params as $index => $value) {
    $bindParams[] = &$params[$index];
}
call_user_func_array([$stmt, 'bind_param'], $bindParams);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(['success' => $ok]);
