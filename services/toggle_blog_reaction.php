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
if ($blogId <= 0) {
    app_json_response(['success' => false, 'message' => 'Blog id is required.'], 422);
}

$blogStmt = $conn->prepare('SELECT status FROM blogs WHERE blog_id = ?');
$blogStmt->bind_param('i', $blogId);
$blogStmt->execute();
$blog = $blogStmt->get_result()->fetch_assoc();
$blogStmt->close();

if (!$blog || $blog['status'] !== 'published') {
    app_json_response(['success' => false, 'message' => 'Likes are only available on published posts.'], 422);
}

$reactionType = 'like';
$userId = get_current_user_id();

$existingStmt = $conn->prepare(
    'SELECT reaction_id
     FROM blog_reactions
     WHERE blog_id = ? AND user_id = ? AND reaction_type = ?'
);
$existingStmt->bind_param('iis', $blogId, $userId, $reactionType);
$existingStmt->execute();
$existing = $existingStmt->get_result()->fetch_assoc();
$existingStmt->close();

if ($existing) {
    $deleteStmt = $conn->prepare('DELETE FROM blog_reactions WHERE reaction_id = ?');
    $reactionId = (int) $existing['reaction_id'];
    $deleteStmt->bind_param('i', $reactionId);
    $deleteStmt->execute();
    $deleteStmt->close();

    app_json_response(['success' => true, 'liked' => false]);
}

$insertStmt = $conn->prepare(
    'INSERT INTO blog_reactions (blog_id, user_id, reaction_type)
     VALUES (?, ?, ?)'
);
$insertStmt->bind_param('iis', $blogId, $userId, $reactionType);
$insertStmt->execute();
$insertStmt->close();

app_json_response(['success' => true, 'liked' => true]);

