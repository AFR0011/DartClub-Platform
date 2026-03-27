<?php
if (!session_status()) session_start();
include 'dbConnection.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    $sql = "SELECT user_id, user_name, email, user_role FROM users ORDER BY user_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    echo json_encode($users);
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to fetch users: ' . $e->getMessage()]);
}

$stmt->close();
$conn->close();
?> 