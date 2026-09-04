<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/dbConnection.php';

if (get_current_role() !== 'admin') {
    app_json_response(['success' => false, 'message' => 'Unauthorized access'], 403);
}

$input = app_read_json_input();
$userId = filter_var($input['user_id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
$newRole = trim((string) ($input['new_role'] ?? ''));

if ($userId === false || !in_array($newRole, ['player', 'manager', 'admin'], true)) {
    app_json_response(['success' => false, 'message' => 'A valid user ID and role are required.'], 422);
}

$stmt = $conn->prepare('UPDATE users SET user_role = ? WHERE user_id = ?');
$stmt->bind_param('si', $newRole, $userId);
$stmt->execute();
$stmt->close();

app_json_response(['success' => true, 'message' => 'User role updated successfully']);
