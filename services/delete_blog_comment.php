<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/auth.php';

app_start_session();

if (!is_logged_in()) {
    app_json_response(['success' => false, 'message' => 'Login required.'], 401);
}

$input = app_read_json_input();
$commentId = isset($input['comment_id']) ? (int) $input['comment_id'] : 0;
if ($commentId <= 0) {
    app_json_response(['success' => false, 'message' => 'Comment id is required.'], 422);
}

$stmt = $conn->prepare('SELECT user_id FROM blog_comments WHERE comment_id = ?');
$stmt->bind_param('i', $commentId);
$stmt->execute();
$comment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$comment) {
    app_json_response(['success' => false, 'message' => 'Comment not found.'], 404);
}

$currentUserId = get_current_user_id();
if ((int) $comment['user_id'] !== $currentUserId && !can_publish_blog_posts()) {
    app_json_response(['success' => false, 'message' => 'You do not have permission to delete this comment.'], 403);
}

$delete = $conn->prepare('UPDATE blog_comments SET is_deleted = 1 WHERE comment_id = ?');
$delete->bind_param('i', $commentId);
$delete->execute();
$delete->close();

app_json_response(['success' => true]);

