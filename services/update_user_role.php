<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'dbConnection.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get the POST data
$input = json_decode(file_get_contents('php://input'), true);
$user_id = $input['user_id'] ?? null;
$new_role = $input['new_role'] ?? null;

if (!$user_id || !$new_role) {
    echo json_encode(['success' => false, 'message' => 'User ID and new role are required']);
    exit();
}

// Validate role
if (!in_array($new_role, ['player', 'manager', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
    exit();
}

try {
    $sql = "UPDATE users SET user_role = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $new_role, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User role updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update user role']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?> 
