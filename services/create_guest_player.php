<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/auth.php';

app_start_session();

if (!is_manager_or_admin()) {
    app_json_response(['success' => false, 'message' => 'Unauthorized access'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    app_json_response(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$firstName = trim((string) ($_POST['first_name'] ?? ''));
$lastName = trim((string) ($_POST['last_name'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));

if ($firstName === '' || $lastName === '') {
    app_json_response(['success' => false, 'message' => 'First name and surname are required.'], 422);
}

try {
    $db = app_db_connect();
    $stmt = $db->prepare(
        'INSERT INTO players (
            plr_name,
            plr_surname,
            plr_phone,
            user_id
        ) VALUES (?, ?, ?, NULL)'
    );
    $stmt->bind_param('sss', $firstName, $lastName, $phone);
    $stmt->execute();
    $playerId = (int) $db->insert_id;
    $stmt->close();
} catch (Throwable $exception) {
    app_json_response(['success' => false, 'message' => app_safe_error_message($exception)], 422);
}

app_json_response([
    'success' => true,
    'message' => 'Guest player created successfully.',
    'player_id' => $playerId,
]);
