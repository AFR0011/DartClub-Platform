<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/shared/mail_helpers.php';

app_start_session();
header('Content-Type: application/json');

$input = app_read_json_input();
$username = trim((string) ($input['username'] ?? ''));
$email = trim((string) ($input['email'] ?? ''));
$password = (string) ($input['password'] ?? '');

if ($username === '' || $email === '' || $password === '') {
    app_json_response(['success' => false, 'message' => 'All fields are required.'], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    app_json_response(['success' => false, 'message' => 'Enter a valid email address.'], 422);
}

if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
    app_json_response([
        'success' => false,
        'message' => 'Password must be at least 8 characters and include at least one letter and one number.',
    ], 422);
}

$emailCheck = $conn->prepare('SELECT user_id FROM users WHERE email = ?');
$emailCheck->bind_param('s', $email);
$emailCheck->execute();
$emailCheck->store_result();
if ($emailCheck->num_rows > 0) {
    $emailCheck->close();
    app_json_response(['success' => false, 'message' => 'Email already exists.'], 409);
}
$emailCheck->close();

$nameCheck = $conn->prepare('SELECT user_id FROM users WHERE user_name = ?');
$nameCheck->bind_param('s', $username);
$nameCheck->execute();
$nameCheck->store_result();
if ($nameCheck->num_rows > 0) {
    $nameCheck->close();
    app_json_response(['success' => false, 'message' => 'Username already exists.'], 409);
}
$nameCheck->close();

$passwordHash = password_hash($password, PASSWORD_BCRYPT);
$role = 'player';
$membershipStatus = 'not_submitted';

$insert = $conn->prepare(
    'INSERT INTO users (user_name, email, password, user_role, membership_status)
     VALUES (?, ?, ?, ?, ?)'
);
$insert->bind_param('sssss', $username, $email, $passwordHash, $role, $membershipStatus);
$insert->execute();
$insert->close();

app_send_best_effort_email(
    $email,
    $username,
    'Welcome to Famagusta Dart Club',
    '<p>Your account is now active on Famagusta Dart Club.</p><p>You can sign in, build your player profile, and register for tournaments whenever registration opens.</p>',
    "Your account is now active on Famagusta Dart Club.\n\nYou can sign in, build your player profile, and register for tournaments whenever registration opens."
);

app_json_response(['success' => true, 'message' => 'User registered successfully.']);
