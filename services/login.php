<?php
if (!session_status()) session_start();
include 'dbConnection.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$email = isset($input['email']) ? trim($input['email']) : '';
$password = isset($input['password']) ? $input['password'] : '';

if ($email === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Please fill in both fields.']);
    exit();
}

$sql = "SELECT user_id, password, user_role FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $stored = (string)$user['password'];
    $isHashed = preg_match('/^\$2y\$|^\$2a\$|^\$argon2/i', $stored) === 1;
    $ok = false;

    if ($isHashed) {
        $ok = password_verify($password, $stored);
    } else {
        // Legacy: support plaintext or md5
        if (hash_equals($stored, $password)) {
            $ok = true;
        } elseif (preg_match('/^[a-f0-9]{32}$/i', $stored) && hash_equals($stored, md5($password))) {
            $ok = true;
        }
        // If legacy verified, migrate to bcrypt
        if ($ok) {
            $newHash = password_hash($password, PASSWORD_BCRYPT);
            $up = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $up->bind_param('si', $newHash, $user['user_id']);
            $up->execute();
            $up->close();
        }
    }

    if ($ok) {
        $_SESSION['user_id'] = (int)$user['user_id'];
        $_SESSION['user_role'] = $user['user_role'];
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Incorrect email or password.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Incorrect email or password.']);
}

$stmt->close();
$conn->close();
?>