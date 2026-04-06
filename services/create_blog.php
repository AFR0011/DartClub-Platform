<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/auth.php';

app_start_session();

if (!can_author_blog_posts()) {
    app_json_response(['success' => false, 'message' => 'Only approved club members, managers, and admins can create blog posts.'], 403);
}

function blog_extract_input(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $payload = app_read_json_input();
        return [
            'title' => trim((string) ($payload['title'] ?? '')),
            'content' => trim((string) ($payload['content'] ?? '')),
            'status' => trim((string) ($payload['status'] ?? 'draft')),
        ];
    }

    return [
        'title' => trim((string) ($_POST['title'] ?? '')),
        'content' => trim((string) ($_POST['content'] ?? '')),
        'status' => trim((string) ($_POST['status'] ?? 'draft')),
    ];
}

function blog_extract_uploaded_images(): array
{
    $files = [];

    if (isset($_FILES['images']) && is_array($_FILES['images']['name'] ?? null)) {
        $count = count($_FILES['images']['name']);
        for ($index = 0; $index < $count; $index++) {
            $error = $_FILES['images']['error'][$index] ?? UPLOAD_ERR_NO_FILE;
            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $files[] = [
                'name' => $_FILES['images']['name'][$index] ?? '',
                'type' => $_FILES['images']['type'][$index] ?? '',
                'tmp_name' => $_FILES['images']['tmp_name'][$index] ?? '',
                'error' => $error,
                'size' => $_FILES['images']['size'][$index] ?? 0,
            ];
        }
    }

    if (empty($files) && isset($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $files[] = $_FILES['image'];
    }

    return $files;
}

$input = blog_extract_input();
$blogTitle = $input['title'];
$blogContent = $input['content'];
$requestedStatus = $input['status'] !== '' ? $input['status'] : 'draft';

if ($blogTitle === '' || $blogContent === '') {
    app_json_response(['success' => false, 'message' => 'Title and content are required.'], 422);
}

if (mb_strlen($blogTitle) > 200) {
    app_json_response(['success' => false, 'message' => 'Title is too long.'], 422);
}

if (mb_strlen($blogContent) > 20000) {
    app_json_response(['success' => false, 'message' => 'Content is too long.'], 422);
}

$blogTitle = strip_tags($blogTitle);
$allowedTags = '<p><br><strong><em><ul><ol><li><a><blockquote><code><pre><h1><h2><h3><h4><h5><h6><img>';
$blogContent = strip_tags($blogContent, $allowedTags);
$blogContent = preg_replace('/on[a-z]+\s*=\s*"[^"]*"/i', '', $blogContent);
$blogContent = preg_replace("/on[a-z]+\s*=\s*'[^']*'/i", '', $blogContent);
$blogContent = preg_replace('/javascript:/i', '', $blogContent);

$status = can_publish_blog_posts() && $requestedStatus === 'published' ? 'published' : 'draft';
$userId = get_current_user_id();
$uploadedImages = blog_extract_uploaded_images();

$conn->begin_transaction();
$storedFiles = [];

try {
    $publishedAt = $status === 'published' ? (new DateTime())->format('Y-m-d H:i:s') : null;
    $moderatedBy = $status === 'published' && can_publish_blog_posts() ? $userId : null;

    $stmt = $conn->prepare(
        'INSERT INTO blogs (blog_title, blog_content, author_user_id, status, published_at, moderated_by_user_id)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('ssissi', $blogTitle, $blogContent, $userId, $status, $publishedAt, $moderatedBy);
    $stmt->execute();
    $blogId = (int) $conn->insert_id;
    $stmt->close();

    if (!empty($uploadedImages)) {
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
        $directory = __DIR__ . '/../files/media/images/blog';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        foreach ($uploadedImages as $index => $uploadedImage) {
            if (($uploadedImage['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                throw new RuntimeException('One of the blog images failed to upload.');
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $uploadedImage['tmp_name']);
            finfo_close($finfo);

            if (!isset($allowed[$mime])) {
                throw new RuntimeException('Unsupported blog image type.');
            }

            $filename = bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
            $destination = $directory . DIRECTORY_SEPARATOR . $filename;
            if (!move_uploaded_file($uploadedImage['tmp_name'], $destination)) {
                throw new RuntimeException('Failed to store one of the blog images.');
            }

            $storedFiles[] = $destination;
            $webPath = '../files/media/images/blog/' . $filename;
            $imageTitle = trim((string) ($_POST['image_title'] ?? $blogTitle));
            if (count($uploadedImages) > 1) {
                $imageTitle .= ' #' . ($index + 1);
            }

            $blogImageStmt = $conn->prepare(
                'INSERT INTO blog_images (blog_id, file_path, title)
                 VALUES (?, ?, ?)'
            );
            $blogImageStmt->bind_param('iss', $blogId, $webPath, $imageTitle);
            $blogImageStmt->execute();
            $blogImageStmt->close();

            $galleryStmt = $conn->prepare(
                'INSERT INTO gallery_images (file_path, title, source_blog_id, uploaded_by_user_id)
                 VALUES (?, ?, ?, ?)'
            );
            $galleryStmt->bind_param('ssii', $webPath, $imageTitle, $blogId, $userId);
            $galleryStmt->execute();
            $galleryStmt->close();
        }
    }

    $conn->commit();
    app_json_response([
        'success' => true,
        'message' => $status === 'published' ? 'Blog post published successfully.' : 'Draft saved successfully.',
        'blog_id' => $blogId,
        'status' => $status,
    ]);
} catch (Throwable $exception) {
    $conn->rollback();
    foreach ($storedFiles as $storedFile) {
        if (is_file($storedFile)) {
            @unlink($storedFile);
        }
    }
    app_json_response(['success' => false, 'message' => $exception->getMessage()], 500);
}
