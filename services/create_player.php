<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/auth.php';

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

$applicationId = $input['appId'] !== '' ? (int) $input['appId'] : null;
$rawPassword = bin2hex(random_bytes(10));
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
    app_json_response(['success' => false, 'message' => $exception->getMessage()], 422);
}

$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoloadPath)) {
    require_once $autoloadPath;
}

try {
    if (class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = getenv('SMTP_HOST') ?: 'localhost';
        $mail->SMTPAuth = filter_var(getenv('SMTP_AUTH') ?: false, FILTER_VALIDATE_BOOLEAN);
        if ($mail->SMTPAuth) {
            $mail->Username = getenv('SMTP_USER') ?: '';
            $mail->Password = getenv('SMTP_PASS') ?: '';
        }
        $secure = getenv('SMTP_SECURE') ?: '';
        if ($secure !== '') {
            $mail->SMTPSecure = $secure;
        }
        $mail->Port = getenv('SMTP_PORT') ? (int) getenv('SMTP_PORT') : 25;
        $mail->setFrom(getenv('SMTP_FROM') ?: 'noreply@example.com', 'Famagusta Dart Club');
        $mail->addAddress($input['email']);
        $mail->isHTML(true);
        $mail->Subject = 'Your player account credentials';
        $mail->Body = "Hello {$input['fName']} {$input['lName']},<br>Your account has been created.<br>Username: {$input['username']}<br>Temporary password: {$rawPassword}";
        $mail->AltBody = "Hello {$input['fName']} {$input['lName']},\nYour account has been created.\nUsername: {$input['username']}\nTemporary password: {$rawPassword}";
        $mail->send();
    }
} catch (Throwable $exception) {
    // Best-effort email only.
}

app_json_response([
    'success' => true,
    'message' => 'Player and user created successfully.',
    'user_id' => $userId,
    'player_id' => $playerId,
]);
