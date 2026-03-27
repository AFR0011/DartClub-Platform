<?php
if (!session_status()) session_start();
include 'dbConnection.php';

header('Content-Type: application/json');

// Check if user is logged in and has admin/manager role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'manager'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get the POST data
$input = json_decode(file_get_contents('php://input'), true);
$blog_id = $input['blog_id'] ?? null;

if (!$blog_id) {
    echo json_encode(['success' => false, 'message' => 'Blog ID is required']);
    exit();
}

try {
    $sql = "DELETE FROM blogs WHERE blog_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $blog_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Blog post deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete blog post']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?> 