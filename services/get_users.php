<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/dbConnection.php';

if (get_current_role() !== 'admin') {
    app_json_response(['success' => false, 'message' => 'Unauthorized access'], 403);
}

$stmt = $conn->prepare(
    'SELECT user_id, user_name, email, user_role, membership_status
     FROM users
     ORDER BY user_id'
);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();

app_json_response($users);
