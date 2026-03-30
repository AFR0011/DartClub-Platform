<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/shared/player_helpers.php';

app_start_session();
header('Content-Type: application/json');

if (!is_logged_in()) {
    app_json_response(['success' => false, 'message' => 'You must be logged in to apply for club membership.'], 401);
}

$userId = get_current_user_id();
$player = player_fetch_for_user($conn, $userId);
if (!$player || empty($player['plr_idNum'])) {
    app_json_response(['success' => false, 'message' => 'Please complete your player profile before submitting a membership application.'], 422);
}

if (!isset($_FILES['form'])) {
    app_json_response(['success' => false, 'message' => 'Please attach the membership form.'], 422);
}

$file = $_FILES['form'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    app_json_response(['success' => false, 'message' => 'Upload failed.'], 422);
}

if ($file['size'] > 10 * 1024 * 1024) {
    app_json_response(['success' => false, 'message' => 'File too large. Max 10MB.'], 422);
}

$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowedExtensions = ['pdf', 'doc', 'docx'];
if (!in_array($extension, $allowedExtensions, true)) {
    app_json_response(['success' => false, 'message' => 'Only PDF, DOC, and DOCX files are allowed.'], 422);
}

$uploadDirectory = __DIR__ . '/../files/applications/membership';
if (!is_dir($uploadDirectory)) {
    mkdir($uploadDirectory, 0775, true);
}

$filename = time() . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
$destination = $uploadDirectory . DIRECTORY_SEPARATOR . $filename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    app_json_response(['success' => false, 'message' => 'Failed to store the uploaded file.'], 500);
}

$webPath = '../files/applications/membership/' . $filename;

$conn->begin_transaction();

try {
    $updateUser = $conn->prepare(
        "UPDATE users
         SET membership_status = 'pending'
         WHERE user_id = ?"
    );
    $updateUser->bind_param('i', $userId);
    $updateUser->execute();
    $updateUser->close();

    $applicationStmt = $conn->prepare(
        'INSERT INTO membership_applications (
            user_id,
            application_file_path,
            original_filename,
            status
        ) VALUES (?, ?, ?, ?)'
    );
    $status = 'Pending';
    $applicationStmt->bind_param('isss', $userId, $webPath, $file['name'], $status);
    $applicationStmt->execute();
    $applicationId = (int) $conn->insert_id;
    $applicationStmt->close();

    $legacyStmt = $conn->prepare(
        'INSERT INTO applications (user_id, app_path, original_filename, isApproved)
         VALUES (?, ?, ?, NULL)'
    );
    $legacyStmt->bind_param('iss', $userId, $webPath, $file['name']);
    $legacyStmt->execute();
    $legacyStmt->close();

    $conn->commit();
    $_SESSION['membership_status'] = 'pending';

    app_json_response([
        'success' => true,
        'application_id' => $applicationId,
        'message' => 'Membership application submitted successfully.',
    ]);
} catch (Throwable $exception) {
    $conn->rollback();
    app_json_response(['success' => false, 'message' => $exception->getMessage()], 500);
}

