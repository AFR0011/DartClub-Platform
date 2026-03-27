<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!session_status()) session_start();
include 'dbConnection.php'; // Adjust the path as needed

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$username = isset($data['username']) ? trim($data['username']) : '';
$email = isset($data['email']) ? trim($data['email']) : '';
$password = isset($data['password']) ? (string)$data['password'] : '';

if ($username === '' || $email === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Unique email
$stmt = $conn->prepare('SELECT user_id FROM users WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already exists']);
    exit;
}
$stmt->close();

// Unique username
$st2 = $conn->prepare('SELECT user_id FROM users WHERE user_name = ?');
$st2->bind_param('s', $username);
$st2->execute();
$st2->store_result();
if ($st2->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Username already exists']);
    exit;
}
$st2->close();

$hashedPassword = password_hash($password, PASSWORD_BCRYPT);
$role = 'player';

$ins = $conn->prepare('INSERT INTO users (user_name, email, password, user_role) VALUES (?, ?, ?, ?)');
$ins->bind_param('ssss', $username, $email, $hashedPassword, $role);
if ($ins->execute()) {
    echo json_encode(['success' => true, 'message' => 'User registered successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'User registration failed']);
}
$ins->close();
$conn->close();
?>


