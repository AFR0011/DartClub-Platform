<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/auth.php';

app_start_session();

if (!is_manager_or_admin()) {
    app_json_response(['success' => false, 'message' => 'Unauthorized'], 403);
}

function gallery_delete_resolve_absolute_path(string $webPath): ?string
{
    $relativePath = str_replace('../', '', $webPath);
    $fullPath = __DIR__ . '/../' . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    $directory = realpath(dirname($fullPath));
    if ($directory === false) {
        return null;
    }

    $absolutePath = $directory . DIRECTORY_SEPARATOR . basename($fullPath);
    return is_file($absolutePath) ? $absolutePath : null;
}

function gallery_delete_has_file_references(mysqli $db, string $webPath): bool
{
    $galleryStmt = $db->prepare('SELECT COUNT(*) AS ref_count FROM gallery_images WHERE file_path = ?');
    $galleryStmt->bind_param('s', $webPath);
    $galleryStmt->execute();
    $galleryRefs = (int) ($galleryStmt->get_result()->fetch_assoc()['ref_count'] ?? 0);
    $galleryStmt->close();

    if ($galleryRefs > 0) {
        return true;
    }

    $blogStmt = $db->prepare('SELECT COUNT(*) AS ref_count FROM blog_images WHERE file_path = ?');
    $blogStmt->bind_param('s', $webPath);
    $blogStmt->execute();
    $blogRefs = (int) ($blogStmt->get_result()->fetch_assoc()['ref_count'] ?? 0);
    $blogStmt->close();

    return $blogRefs > 0;
}

$input = app_read_json_input();
$id = isset($input['id']) ? (int) $input['id'] : 0;
if ($id <= 0) {
    app_json_response(['success' => false, 'message' => 'Missing id.'], 422);
}

$find = $conn->prepare('SELECT file_path, source_blog_id FROM gallery_images WHERE id = ?');
$find->bind_param('i', $id);
$find->execute();
$row = $find->get_result()->fetch_assoc();
$find->close();

if (!$row) {
    app_json_response(['success' => false, 'message' => 'Image not found.'], 404);
}

$delete = $conn->prepare('DELETE FROM gallery_images WHERE id = ?');
$delete->bind_param('i', $id);
$delete->execute();
$delete->close();

if (!gallery_delete_has_file_references($conn, (string) $row['file_path'])) {
    $absolutePath = gallery_delete_resolve_absolute_path((string) $row['file_path']);
    if ($absolutePath) {
    @unlink($absolutePath);
    }
}

app_json_response([
    'success' => true,
    'removed_from_gallery_only' => !empty($row['source_blog_id']),
]);
