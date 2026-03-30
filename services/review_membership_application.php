<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/auth.php';

app_start_session();

if (!is_manager_or_admin()) {
    app_json_response(['success' => false, 'message' => 'Unauthorized'], 403);
}

$payload = app_read_json_input();
$applicationId = isset($payload['application_id']) ? (int) $payload['application_id'] : 0;
$decision = strtolower(trim((string) ($payload['decision'] ?? '')));
$notes = trim((string) ($payload['reviewer_notes'] ?? ''));

if ($applicationId <= 0 || !in_array($decision, ['approve', 'reject'], true)) {
    app_json_response(['success' => false, 'message' => 'Invalid review payload.'], 422);
}

$stmt = $conn->prepare('SELECT application_id, user_id, status FROM membership_applications WHERE application_id = ?');
$stmt->bind_param('i', $applicationId);
$stmt->execute();
$application = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$application) {
    app_json_response(['success' => false, 'message' => 'Application not found.'], 404);
}

$reviewStatus = $decision === 'approve' ? 'Approved' : 'Rejected';
$membershipStatus = $decision === 'approve' ? 'approved' : 'rejected';
$approverId = get_current_user_id();

$conn->begin_transaction();

try {
    $reviewedAt = (new DateTime())->format('Y-m-d H:i:s');
    $applicationUpdate = $conn->prepare(
        'UPDATE membership_applications
         SET status = ?, reviewed_at = ?, reviewed_by_user_id = ?, reviewer_notes = ?
         WHERE application_id = ?'
    );
    $applicationUpdate->bind_param('ssisi', $reviewStatus, $reviewedAt, $approverId, $notes, $applicationId);
    $applicationUpdate->execute();
    $applicationUpdate->close();

    $legacyValue = $decision === 'approve' ? 1 : 0;
    $legacyUpdate = $conn->prepare(
        'UPDATE applications
         SET isApproved = ?
         WHERE user_id = ?
         ORDER BY app_creationDate DESC
         LIMIT 1'
    );
    $legacyUpdate->bind_param('ii', $legacyValue, $application['user_id']);
    $legacyUpdate->execute();
    $legacyUpdate->close();

    if ($decision === 'approve') {
        $userUpdate = $conn->prepare(
            "UPDATE users
             SET membership_status = 'approved',
                 member_since = COALESCE(member_since, ?),
                 membership_approved_at = ?,
                 membership_approved_by_user_id = ?
             WHERE user_id = ?"
        );
        $userUpdate->bind_param('ssii', $reviewedAt, $reviewedAt, $approverId, $application['user_id']);
    } else {
        $userUpdate = $conn->prepare(
            "UPDATE users
             SET membership_status = 'rejected',
                 membership_approved_at = NULL,
                 membership_approved_by_user_id = ?
             WHERE user_id = ?"
        );
        $userUpdate->bind_param('ii', $approverId, $application['user_id']);
    }
    $userUpdate->execute();
    $userUpdate->close();

    $conn->commit();

    app_json_response([
        'success' => true,
        'status' => $reviewStatus,
        'membership_status' => $membershipStatus,
    ]);
} catch (Throwable $exception) {
    $conn->rollback();
    app_json_response(['success' => false, 'message' => $exception->getMessage()], 500);
}

