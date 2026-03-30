<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/auth.php';

app_start_session();

if (!is_logged_in()) {
    app_json_response(['success' => false, 'message' => 'Login required.'], 401);
}

$input = app_read_json_input();
$blogId = isset($input['blog_id']) ? (int) $input['blog_id'] : 0;
$content = trim((string) ($input['content'] ?? ''));

if ($blogId <= 0 || $content === '') {
    app_json_response(['success' => false, 'message' => 'Blog and comment content are required.'], 422);
}

$blogStmt = $conn->prepare('SELECT status FROM blogs WHERE blog_id = ?');
$blogStmt->bind_param('i', $blogId);
$blogStmt->execute();
$blog = $blogStmt->get_result()->fetch_assoc();
$blogStmt->close();

if (!$blog || $blog['status'] !== 'published') {
    app_json_response(['success' => false, 'message' => 'Comments are only available on published posts.'], 422);
}

$stmt = $conn->prepare(
    'INSERT INTO blog_comments (blog_id, user_id, comment_content)
     VALUES (?, ?, ?)'
);
$userId = get_current_user_id();
$stmt->bind_param('iis', $blogId, $userId, $content);
$stmt->execute();
$stmt->close();

app_json_response(['success' => true]);

