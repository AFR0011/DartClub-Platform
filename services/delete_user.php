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

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit();
}

// Prevent admin from deleting themselves
if ($user_id == $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Delete from tournament_players
    $sql1 = "DELETE FROM tournament_players WHERE plr_id = ?";
    $stmt1 = $conn->prepare($sql1);
    $stmt1->bind_param("i", $user_id);
    $stmt1->execute();
    $stmt1->close();
    
    // Delete from tournament_standings
    $sql2 = "DELETE FROM tournament_standings WHERE player_id = ?";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    $stmt2->close();
    
    // Delete from players
    $sql3 = "DELETE FROM players WHERE plr_idNum = ?";
    $stmt3 = $conn->prepare($sql3);
    $stmt3->bind_param("i", $user_id);
    $stmt3->execute();
    $stmt3->close();
    
    // Delete from users
    $sql4 = "DELETE FROM users WHERE user_id = ?";
    $stmt4 = $conn->prepare($sql4);
    $stmt4->bind_param("i", $user_id);
    $stmt4->execute();
    $stmt4->close();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?> 
