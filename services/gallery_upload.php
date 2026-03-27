<?php
if (!session_status()) session_start();
require_once 'dbConnection.php';
require_once 'auth.php';

header('Content-Type: application/json');

// Only admin/manager may upload
if (!in_array(get_current_role(), ['admin','manager'], true)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    // Ensure table exists
    $conn->query("CREATE TABLE IF NOT EXISTS gallery_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        file_path VARCHAR(255) NOT NULL,
        title VARCHAR(255) DEFAULT NULL,
        uploaded_by_user_id INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    if (!isset($_FILES['image'])) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded']);
        exit();
    }

    $file = $_FILES['image'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Upload error']);
        exit();
    }

    // Validate size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File too large (max 5MB)']);
        exit();
    }

    // Validate mime type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) {
        echo json_encode(['success' => false, 'message' => 'Unsupported file type']);
        exit();
    }

    $ext = $allowed[$mime];
    $safeName = bin2hex(random_bytes(8)) . '.' . $ext;
    $destDir = realpath(__DIR__ . '/../files/media/images/gallery');
    if ($destDir === false) {
        $destDir = __DIR__ . '/../files/media/images/gallery';
    }
    if (!is_dir($destDir)) {
        mkdir($destDir, 0775, true);
    }

    $destPath = rtrim($destDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        echo json_encode(['success' => false, 'message' => 'Failed to save file']);
        exit();
    }

    // Use web path relative to pages
    $webPath = '../files/media/images/gallery/' . $safeName;

    $title = isset($_POST['title']) ? trim($_POST['title']) : null;

    $stmt = $conn->prepare("INSERT INTO gallery_images (file_path, title, uploaded_by_user_id) VALUES (?, ?, ?)");
    $uid = get_current_user_id();
    $stmt->bind_param('ssi', $webPath, $title, $uid);
    $ok = $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => $ok, 'file_path' => $webPath]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

$conn->close();