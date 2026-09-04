<?php

require_once dirname(__DIR__) . '/services/app_bootstrap.php';

$db = app_db_connect();
$emails = [
    'portfolio-ci-admin@local.test',
    'portfolio-ci-role-target@local.test',
    'portfolio-ci-member@local.test',
];

$delete = $db->prepare('DELETE FROM users WHERE email = ?');
foreach ($emails as $email) {
    $delete->bind_param('s', $email);
    $delete->execute();
}
$delete->close();

$password = password_hash('Portfolio-CI-Password-42', PASSWORD_BCRYPT);
$insert = $db->prepare(
    'INSERT INTO users (user_name, email, password, user_role, membership_status)
     VALUES (?, ?, ?, ?, ?)'
);

$fixtures = [
    ['portfolio-ci-admin', $emails[0], 'admin', 'approved'],
    ['portfolio-ci-role-target', $emails[1], 'player', 'not_submitted'],
    ['portfolio-ci-member', $emails[2], 'player', 'pending'],
];
$userIds = [];
foreach ($fixtures as [$username, $email, $role, $membership]) {
    $insert->bind_param('sssss', $username, $email, $password, $role, $membership);
    $insert->execute();
    $userIds[$email] = (int) $db->insert_id;
}
$insert->close();

$firstName = 'Portfolio';
$surname = 'Member';
$username = 'portfolio-ci-member';
$memberId = $userIds[$emails[2]];
$player = $db->prepare(
    'INSERT INTO players (plr_name, plr_surname, plr_username, user_id)
     VALUES (?, ?, ?, ?)'
);
$player->bind_param('sssi', $firstName, $surname, $username, $memberId);
$player->execute();
$player->close();

$path = '/files/applications/membership/portfolio-ci-synthetic.pdf';
$original = 'portfolio-ci-synthetic.pdf';
$status = 'Pending';
$application = $db->prepare(
    'INSERT INTO membership_applications (
        user_id, application_file_path, original_filename, status
     ) VALUES (?, ?, ?, ?)'
);
$application->bind_param('isss', $memberId, $path, $original, $status);
$application->execute();
$application->close();

echo "integration fixture ready\n";
