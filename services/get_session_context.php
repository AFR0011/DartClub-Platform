<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/auth.php';

app_start_session();

$userId = get_current_user_id();
if (!$userId) {
    app_json_response([
        'logged_in' => false,
        'user_role' => 'guest',
        'membership_status' => 'not_submitted',
        'can_author_blog' => false,
        'can_publish_blog' => false,
        'can_manage_club' => false,
        'has_player_profile' => false,
    ]);
}

require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/shared/player_helpers.php';

$stmt = $conn->prepare(
    'SELECT user_id, user_name, email, user_role, membership_status
     FROM users
     WHERE user_id = ?'
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    $_SESSION = [];

    app_json_response([
        'logged_in' => false,
        'user_role' => 'guest',
        'membership_status' => 'not_submitted',
        'can_author_blog' => false,
        'can_publish_blog' => false,
        'can_manage_club' => false,
        'has_player_profile' => false,
    ]);
}

$_SESSION['user_name'] = $user['user_name'];
$_SESSION['user_role'] = $user['user_role'];
$_SESSION['membership_status'] = $user['membership_status'];

$player = player_fetch_for_user($conn, $userId);

app_json_response([
    'logged_in' => true,
    'user_id' => (int) $user['user_id'],
    'user_name' => $user['user_name'],
    'email' => $user['email'],
    'user_role' => $user['user_role'],
    'membership_status' => $user['membership_status'],
    'can_author_blog' => can_author_blog_posts(),
    'can_publish_blog' => can_publish_blog_posts(),
    'can_manage_club' => is_manager_or_admin(),
    'has_player_profile' => $player !== null && !empty($player['plr_idNum']),
]);
