<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/auth.php';

app_start_session();

if (!can_publish_blog_posts()) {
    app_json_response(['success' => false, 'message' => 'Unauthorized'], 403);
}

$input = app_read_json_input();
$blogId = isset($input['blog_id']) ? (int) $input['blog_id'] : 0;
$status = trim((string) ($input['status'] ?? ''));

if ($blogId <= 0 || !in_array($status, ['draft', 'published'], true)) {
    app_json_response(['success' => false, 'message' => 'Invalid request.'], 422);
}

$publishedAt = $status === 'published' ? (new DateTime())->format('Y-m-d H:i:s') : null;
$moderatedBy = get_current_user_id();

$stmt = $conn->prepare(
    'UPDATE blogs
     SET status = ?, published_at = ?, moderated_by_user_id = ?
     WHERE blog_id = ?'
);
$stmt->bind_param('ssii', $status, $publishedAt, $moderatedBy, $blogId);
$stmt->execute();
$stmt->close();

app_json_response(['success' => true, 'status' => $status]);

