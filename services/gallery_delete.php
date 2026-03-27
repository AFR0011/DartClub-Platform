<?php
if (!session_status()) session_start();
require_once 'dbConnection.php';
require_once 'auth.php';

header('Content-Type: application/json');

if (!in_array(get_current_role(), ['admin','manager'], true)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? null;
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Missing id']);
    exit();
}

try {
    // Fetch file path
    $find = $conn->prepare("SELECT file_path FROM gallery_images WHERE id = ?");
    $find->bind_param('i', $id);
    $find->execute();
    $res = $find->get_result();
    if ($res->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Not found']);
        $find->close();
        exit();
    }
    $row = $res->fetch_assoc();
    $find->close();

    // Delete DB record
    $del = $conn->prepare("DELETE FROM gallery_images WHERE id = ?");
    $del->bind_param('i', $id);
    $ok = $del->execute();
    $del->close();

    // Try to delete file from disk (best effort)
    $webPath = $row['file_path']; // '../files/media/images/gallery/filename.ext'
    $absPath = realpath(__DIR__ . '/../pages/' . $webPath);
    if ($absPath === false) {
        // Fallback: compute from services dir
        $absPath = realpath(__DIR__ . '/../' . ltrim($webPath, './'));
    }
    if ($absPath && is_file($absPath)) {
        @unlink($absPath);
    }

    echo json_encode(['success' => $ok]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

$conn->close();