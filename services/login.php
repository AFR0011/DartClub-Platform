<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/dbConnection.php';

app_start_session();
header('Content-Type: application/json');

$input = app_read_json_input();
$email = trim((string) ($input['email'] ?? ''));
$password = (string) ($input['password'] ?? '');

if ($email === '' || $password === '') {
    app_json_response(['success' => false, 'message' => 'Please fill in both fields.'], 422);
}

$sql = "SELECT user_id, user_name, password, user_role, membership_status
        FROM users
        WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    app_json_response(['success' => false, 'message' => 'Incorrect email or password.'], 401);
}

$user = $result->fetch_assoc();
$stmt->close();

$stored = (string) $user['password'];
$verified = false;
$isHashed = preg_match('/^\$2y\$|^\$2a\$|^\$argon2/i', $stored) === 1;

if ($isHashed) {
    $verified = password_verify($password, $stored);
} else {
    if (hash_equals($stored, $password)) {
        $verified = true;
    } elseif (preg_match('/^[a-f0-9]{32}$/i', $stored) && hash_equals($stored, md5($password))) {
        $verified = true;
    }

    if ($verified) {
        $newHash = password_hash($password, PASSWORD_BCRYPT);
        $update = $conn->prepare('UPDATE users SET password = ? WHERE user_id = ?');
        $userId = (int) $user['user_id'];
        $update->bind_param('si', $newHash, $userId);
        $update->execute();
        $update->close();
    }
}

if (!$verified) {
    app_json_response(['success' => false, 'message' => 'Incorrect email or password.'], 401);
}

$_SESSION['user_id'] = (int) $user['user_id'];
$_SESSION['user_name'] = $user['user_name'];
$_SESSION['user_role'] = $user['user_role'];
$_SESSION['membership_status'] = $user['membership_status'] ?? 'not_submitted';

app_json_response(['success' => true]);

