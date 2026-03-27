<?php
if (!session_status()) session_start();
include 'dbConnection.php';
require_once 'auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'manager'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$blog_title = isset($input['title']) ? trim($input['title']) : '';
$blog_content = isset($input['content']) ? trim($input['content']) : '';

if ($blog_title === '' || $blog_content === '') {
    echo json_encode(['success' => false, 'message' => 'Title and content are required']);
    exit();
}

// Basic length and content validation
if (mb_strlen($blog_title) > 200) {
    echo json_encode(['success' => false, 'message' => 'Title is too long (max 200 characters)']);
    exit();
}
if (mb_strlen($blog_content) > 10000) {
    echo json_encode(['success' => false, 'message' => 'Content is too long (max 10000 characters)']);
    exit();
}

// Sanitize inputs
$blog_title = strip_tags($blog_title);
// Allow a safe subset of tags in content
$allowed_tags = '<p><br><strong><em><ul><ol><li><a><blockquote><code><pre><h1><h2><h3><h4><h5><h6>'; 
$blog_content = strip_tags($blog_content, $allowed_tags);
// Optionally, strip on-event attributes
$blog_content = preg_replace('/on[a-z]+\s*=\s*"[^"]*"/i', '', $blog_content);
$blog_content = preg_replace("/on[a-z]+\s*=\s*'[^']*'/i", '', $blog_content);
$blog_content = preg_replace('/javascript:/i', '', $blog_content);

try {
    $sql = "INSERT INTO blogs (blog_title, blog_content, author_user_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $uid = get_current_user_id();
    $stmt->bind_param("ssi", $blog_title, $blog_content, $uid);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Blog post created successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create blog post']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?> 
