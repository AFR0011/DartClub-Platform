<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/auth.php';

app_start_session();

if (!is_manager_or_admin()) {
    app_json_response(['success' => false, 'message' => 'Unauthorized'], 403);
}

$sql = "SELECT
            ma.application_id,
            ma.status,
            ma.original_filename,
            ma.submitted_at,
            ma.reviewed_at,
            ma.reviewer_notes,
            u.user_id,
            u.user_name,
            u.email,
            u.user_role,
            u.membership_status,
            p.plr_idNum,
            p.plr_name,
            p.plr_surname,
            reviewer.user_name AS reviewed_by_name
        FROM membership_applications ma
        JOIN users u ON u.user_id = ma.user_id
        LEFT JOIN players p ON p.user_id = u.user_id
        LEFT JOIN users reviewer ON reviewer.user_id = ma.reviewed_by_user_id
        ORDER BY
            CASE ma.status
                WHEN 'Pending' THEN 0
                WHEN 'Rejected' THEN 1
                ELSE 2
            END,
            ma.submitted_at DESC";

$result = $conn->query($sql);
$items = [];
while ($row = $result->fetch_assoc()) {
    $applicationId = (int) $row['application_id'];
    $row['application_file_path'] = '/services/download_membership_application.php?application_id=' . $applicationId;
    $items[] = $row;
}

app_json_response(['success' => true, 'items' => $items]);
