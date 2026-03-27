<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!session_status()) session_start();
require 'dbConnection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['user_role' => 'guest']);
    exit;
}

$user_id = $_SESSION['user_id'];
$query = $conn->prepare("SELECT user_role FROM users WHERE user_id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo json_encode(['user_role' => $user['user_role']]);
} else {
    echo json_encode(['user_role' => 'guest']);
}

$query->close();
$conn->close();

