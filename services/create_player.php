<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/shared/mail_helpers.php';

app_start_session();

if (!is_manager_or_admin()) {
    app_json_response(['success' => false, 'message' => 'Unauthorized access'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    app_json_response(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$fields = [
    'fName',
    'lName',
    'fatherName',
    'motherName',
    'birthPlace',
    'birthDate',
    'phoneNo',
    'address',
    'username',
    'email',
    'temporaryPassword',
    'appId',
];
$input = [];
foreach ($fields as $field) {
    $input[$field] = isset($_POST[$field]) ? trim((string) $_POST[$field]) : '';
}

if ($input['fName'] === '' || $input['lName'] === '' || $input['username'] === '' || $input['email'] === '') {
    app_json_response(['success' => false, 'message' => 'First name, surname, username, and email are required.'], 422);
}

if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    app_json_response(['success' => false, 'message' => 'Email address is invalid.'], 422);
}

if ($input['temporaryPassword'] === '') {
    app_json_response([
        'success' => false,
        'message' => 'A temporary password is required so club management can deliver it through a separate trusted channel.',
    ], 422);
}

if (strlen($input['temporaryPassword']) < 10
    || !preg_match('/[A-Za-z]/', $input['temporaryPassword'])
    || !preg_match('/\d/', $input['temporaryPassword'])) {
    app_json_response([
        'success' => false,
        'message' => 'Temporary password must be at least 10 characters and include a letter and a number.',
    ], 422);
}

$applicationId = $input['appId'] !== '' ? (int) $input['appId'] : null;
$rawPassword = $input['temporaryPassword'];
$hashedPassword = password_hash($rawPassword, PASSWORD_BCRYPT);
$role = 'player';
$approvedByUserId = $applicationId ? get_current_user_id() : null;
$now = (new DateTime())->format('Y-m-d H:i:s');
$membershipStatus = $applicationId ? 'approved' : 'not_submitted';
$memberSince = $applicationId ? $now : null;
$approvedAt = $applicationId ? $now : null;
$birthDate = app_value_or_null($input['birthDate']);

$conn->begin_transaction();

try {
    $existingUserStmt = $conn->prepare('SELECT user_id FROM users WHERE user_name = ? OR email = ? LIMIT 1');
    $existingUserStmt->bind_param('ss', $input['username'], $input['email']);
    $existingUserStmt->execute();
    $existingUser = $existingUserStmt->get_result()->fetch_assoc();
    $existingUserStmt->close();

    if ($existingUser) {
        throw new RuntimeException('A user with that username or email already exists.');
    }

    $userInsert = $conn->prepare(
        'INSERT INTO users (
            user_name,
            email,
            password,
            user_role,
            membership_status,
            member_since,
            membership_approved_at,
            membership_approved_by_user_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $userInsert->bind_param(
        'sssssssi',
        $input['username'],
        $input['email'],
        $hashedPassword,
        $role,
        $membershipStatus,
        $memberSince,
        $approvedAt,
        $approvedByUserId
    );
    $userInsert->execute();
    $userId = (int) $conn->insert_id;
    $userInsert->close();

    $playerInsert = $conn->prepare(
        'INSERT INTO players (
            plr_name,
            plr_surname,
            plr_address,
            plr_dob,
            plr_mother,
            plr_father,
            plr_pob,
            plr_phone,
            plr_username,
            plr_app,
            user_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $playerInsert->bind_param(
        'sssssssssii',
        $input['fName'],
        $input['lName'],
        $input['address'],
        $birthDate,
        $input['motherName'],
        $input['fatherName'],
        $input['birthPlace'],
        $input['phoneNo'],
        $input['username'],
        $applicationId,
        $userId
    );
    $playerInsert->execute();
    $playerId = (int) $conn->insert_id;
    $playerInsert->close();

    if ($applicationId) {
        $legacyApplication = $conn->prepare('UPDATE applications SET isApproved = 1, user_id = COALESCE(user_id, ?) WHERE app_id = ?');
        $legacyApplication->bind_param('ii', $userId, $applicationId);
        $legacyApplication->execute();
        $legacyApplication->close();
    }

    $conn->commit();
} catch (Throwable $exception) {
    $conn->rollback();
    app_json_response([
        'success' => false,
        'message' => app_safe_error_message($exception, 'Player account could not be created.'),
    ], 422);
}

app_send_best_effort_email(
    $input['email'],
    trim($input['fName'] . ' ' . $input['lName']),
    'Your player account is ready',
    "Hello {$input['fName']} {$input['lName']},<br>Your account has been created.<br>Username: {$input['username']}<br>Please obtain your temporary password directly from club management through the agreed trusted channel.",
    "Hello {$input['fName']} {$input['lName']},\nYour account has been created.\nUsername: {$input['username']}\nPlease obtain your temporary password directly from club management through the agreed trusted channel."
);

app_json_response([
    'success' => true,
    'message' => 'Player and user created successfully. Deliver the temporary password separately; it was not sent by email.',
    'user_id' => $userId,
    'player_id' => $playerId,
]);
