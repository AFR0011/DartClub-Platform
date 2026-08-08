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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    app_json_response(['success' => false, 'message' => 'Invalid request method.'], 405);
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

if ($file['size'] <= 0 || $file['size'] > 10 * 1024 * 1024) {
    app_json_response(['success' => false, 'message' => 'File must be between 1 byte and 10MB.'], 422);
}

$extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
$allowedMimes = [
    'pdf' => ['application/pdf'],
    'doc' => ['application/msword', 'application/CDFV2', 'application/x-ole-storage', 'application/octet-stream'],
    'docx' => [
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/zip',
        'application/octet-stream',
    ],
];

if (!isset($allowedMimes[$extension])) {
    app_json_response(['success' => false, 'message' => 'Only PDF, DOC, and DOCX files are allowed.'], 422);
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : false;
if ($finfo) {
    finfo_close($finfo);
}

if ($mime === false || !in_array($mime, $allowedMimes[$extension], true)) {
    app_json_response(['success' => false, 'message' => 'The uploaded file content does not match an allowed document type.'], 422);
}

if ($extension === 'docx' && $mime !== 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' && class_exists('ZipArchive')) {
    $zip = new ZipArchive();
    if ($zip->open($file['tmp_name']) !== true) {
        app_json_response(['success' => false, 'message' => 'The DOCX file is invalid.'], 422);
    }

    $hasContentTypes = $zip->locateName('[Content_Types].xml') !== false;
    $hasDocument = $zip->locateName('word/document.xml') !== false;
    $zip->close();

    if (!$hasContentTypes || !$hasDocument) {
        app_json_response(['success' => false, 'message' => 'The DOCX file is invalid.'], 422);
    }
}

$uploadDirectory = __DIR__ . '/../files/applications/membership';
if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0775, true) && !is_dir($uploadDirectory)) {
    app_json_response(['success' => false, 'message' => 'Upload storage is unavailable.'], 500);
}

$filename = time() . '_' . bin2hex(random_bytes(12)) . '.' . $extension;
$destination = $uploadDirectory . DIRECTORY_SEPARATOR . $filename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    app_json_response(['success' => false, 'message' => 'Failed to store the uploaded file.'], 500);
}

$webPath = '/files/applications/membership/' . $filename;
$originalFilename = mb_substr(basename((string) $file['name']), 0, 255);

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
    $applicationStmt->bind_param('isss', $userId, $webPath, $originalFilename, $status);
    $applicationStmt->execute();
    $applicationId = (int) $conn->insert_id;
    $applicationStmt->close();

    $legacyStmt = $conn->prepare(
        'INSERT INTO applications (user_id, app_path, original_filename, isApproved)
         VALUES (?, ?, ?, NULL)'
    );
    $legacyStmt->bind_param('iss', $userId, $webPath, $originalFilename);
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
    if (is_file($destination)) {
        @unlink($destination);
    }
    app_json_response(['success' => false, 'message' => app_safe_error_message($exception, 'Membership application could not be saved.')], 500);
}
