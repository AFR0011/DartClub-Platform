<?php
if (!session_status()) session_start();
require_once 'dbConnection.php';
header('Content-Type: application/json');

// Basic rate limit: 1 upload per 5 seconds per session
if (!isset($_SESSION['last_app_upload'])) { $_SESSION['last_app_upload'] = 0; }
if (time() - (int)$_SESSION['last_app_upload'] < 5) {
    echo json_encode(['success' => false, 'message' => 'Please wait before uploading again.']);
    exit();
}

if (!isset($_FILES['forms'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit();
}
$file = $_FILES['forms'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Upload error']);
    exit();
}
// Max 10MB
if ($file['size'] > 10 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File too large (max 10MB)']);
    exit();
}
// Extension check
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowedExt = ['pdf','doc','docx'];
if (!in_array($ext, $allowedExt, true)) {
    echo json_encode(['success' => false, 'message' => 'Unsupported file type']);
    exit();
}
// Mime check (best-effort)
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);
$allowedMime = ['application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
if (!in_array($mime, $allowedMime, true)) {
    // Some docx uploads can be octet-stream; allow if extension ok
    if (!($ext === 'docx' && $mime === 'application/octet-stream')) {
        echo json_encode(['success' => false, 'message' => 'Unsupported file content']);
        exit();
    }
}

$uploadDirectory = realpath(__DIR__ . '/../files/applications');
if ($uploadDirectory === false) { $uploadDirectory = __DIR__ . '/../files/applications'; }
if (!is_dir($uploadDirectory)) { mkdir($uploadDirectory, 0775, true); }

$safeName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
$destPath = rtrim($uploadDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;
if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
    exit();
}

$webPath = '../files/applications/' . $safeName;

try {
    $stmt = $conn->prepare("INSERT INTO applications(app_path) VALUES(?)");
    $stmt->bind_param('s', $webPath);
    $ok = $stmt->execute();
    $stmt->close();
    $_SESSION['last_app_upload'] = time();
    echo json_encode(['success' => $ok, 'path' => $webPath]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

?>
