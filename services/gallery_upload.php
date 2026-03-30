<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/auth.php';

app_start_session();

if (!is_manager_or_admin()) {
    app_json_response(['success' => false, 'message' => 'Unauthorized'], 403);
}

if (!isset($_FILES['image'])) {
    app_json_response(['success' => false, 'message' => 'No file uploaded.'], 422);
}

$file = $_FILES['image'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    app_json_response(['success' => false, 'message' => 'Upload error.'], 422);
}

if ($file['size'] > 5 * 1024 * 1024) {
    app_json_response(['success' => false, 'message' => 'File too large. Max 5MB.'], 422);
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
if (!isset($allowed[$mime])) {
    app_json_response(['success' => false, 'message' => 'Unsupported file type.'], 422);
}

$destinationDirectory = __DIR__ . '/../files/media/images/gallery';
if (!is_dir($destinationDirectory)) {
    mkdir($destinationDirectory, 0775, true);
}

$extension = $allowed[$mime];
$filename = bin2hex(random_bytes(8)) . '.' . $extension;
$destination = $destinationDirectory . DIRECTORY_SEPARATOR . $filename;
if (!move_uploaded_file($file['tmp_name'], $destination)) {
    app_json_response(['success' => false, 'message' => 'Failed to store the image.'], 500);
}

$webPath = '../files/media/images/gallery/' . $filename;
$title = trim((string) ($_POST['title'] ?? ''));
$title = $title !== '' ? $title : pathinfo($file['name'], PATHINFO_FILENAME);
$userId = get_current_user_id();

$stmt = $conn->prepare(
    'INSERT INTO gallery_images (file_path, title, uploaded_by_user_id)
     VALUES (?, ?, ?)'
);
$stmt->bind_param('ssi', $webPath, $title, $userId);
$stmt->execute();
$stmt->close();

app_json_response(['success' => true, 'file_path' => $webPath]);

