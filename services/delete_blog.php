<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/auth.php';

app_start_session();

if (!is_logged_in()) {
    app_json_response(['success' => false, 'message' => 'Unauthorized access'], 401);
}

function delete_blog_resolve_absolute_path(string $webPath): ?string
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

function delete_blog_has_file_references(mysqli $db, string $webPath): bool
{
    $blogStmt = $db->prepare('SELECT COUNT(*) AS ref_count FROM blog_images WHERE file_path = ?');
    $blogStmt->bind_param('s', $webPath);
    $blogStmt->execute();
    $blogRefs = (int) ($blogStmt->get_result()->fetch_assoc()['ref_count'] ?? 0);
    $blogStmt->close();

    if ($blogRefs > 0) {
        return true;
    }

    $galleryStmt = $db->prepare('SELECT COUNT(*) AS ref_count FROM gallery_images WHERE file_path = ?');
    $galleryStmt->bind_param('s', $webPath);
    $galleryStmt->execute();
    $galleryRefs = (int) ($galleryStmt->get_result()->fetch_assoc()['ref_count'] ?? 0);
    $galleryStmt->close();

    return $galleryRefs > 0;
}

$input = app_read_json_input();
$blogId = isset($input['blog_id']) ? (int) $input['blog_id'] : 0;
if ($blogId <= 0) {
    app_json_response(['success' => false, 'message' => 'Blog ID is required'], 422);
}

$stmt = $conn->prepare('SELECT blog_id, author_user_id, status FROM blogs WHERE blog_id = ?');
$stmt->bind_param('i', $blogId);
$stmt->execute();
$blog = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$blog) {
    app_json_response(['success' => false, 'message' => 'Blog not found'], 404);
}

$currentUserId = get_current_user_id();
$canDelete = can_publish_blog_posts()
    || ((int) $blog['author_user_id'] === $currentUserId && $blog['status'] !== 'published');

if (!$canDelete) {
    app_json_response(['success' => false, 'message' => 'You do not have permission to delete this post.'], 403);
}

$pathStmt = $conn->prepare('SELECT DISTINCT file_path FROM blog_images WHERE blog_id = ?');
$pathStmt->bind_param('i', $blogId);
$pathStmt->execute();
$pathResult = $pathStmt->get_result();
$paths = [];
while ($row = $pathResult->fetch_assoc()) {
    if (!empty($row['file_path'])) {
        $paths[] = (string) $row['file_path'];
    }
}
$pathStmt->close();

$delete = $conn->prepare('DELETE FROM blogs WHERE blog_id = ?');
$delete->bind_param('i', $blogId);
$delete->execute();
$delete->close();

$filesToDelete = [];
foreach (array_unique($paths) as $path) {
    if (!delete_blog_has_file_references($conn, $path)) {
        $absolutePath = delete_blog_resolve_absolute_path($path);
        if ($absolutePath) {
            $filesToDelete[] = $absolutePath;
        }
    }
}

foreach ($filesToDelete as $filePath) {
    @unlink($filePath);
}

app_json_response(['success' => true, 'message' => 'Blog post deleted successfully']);
