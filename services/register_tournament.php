<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/shared/mail_helpers.php';
require_once __DIR__ . '/shared/player_helpers.php';
require_once __DIR__ . '/shared/tournament_helpers.php';

app_start_session();

$input = app_read_json_input();
$tournamentId = isset($input['tournament_id']) ? (int) $input['tournament_id'] : 0;
if ($tournamentId <= 0) {
    app_json_response(['success' => false, 'message' => 'Tournament ID is required.'], 422);
}

try {
    $tournament = tournament_refresh_lifecycle($conn, $tournamentId);
    if (!$tournament) {
        app_json_response(['success' => false, 'message' => 'Tournament not found.'], 404);
    }

    if (($tournament['status'] ?? '') !== 'registration_open') {
        app_json_response(['success' => false, 'message' => 'Tournament registration is currently closed.'], 422);
    }

    if ((int) ($tournament['is_public'] ?? 0) !== 1) {
        app_json_response(['success' => false, 'message' => 'Tournament registration is unavailable.'], 422);
    }

    $conn->begin_transaction();
    $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
    $playerId = $userId > 0 ? player_id_for_user($conn, $userId) : null;
    $registrationEmail = null;
    $registrationName = null;

    if ($userId > 0) {
        $userStmt = $conn->prepare('SELECT user_name, email FROM users WHERE user_id = ?');
        $userStmt->bind_param('i', $userId);
        $userStmt->execute();
        $userRow = $userStmt->get_result()->fetch_assoc();
        $userStmt->close();

        if ($userRow) {
            $registrationEmail = trim((string) ($userRow['email'] ?? ''));
            $registrationName = trim((string) ($userRow['user_name'] ?? ''));
        }
    }

    if (!$playerId) {
        if ($userId > 0) {
            $seedPayload = player_seed_payload_for_user($conn, $userId);
            if ($seedPayload) {
                $playerId = player_create_or_update_for_user($conn, $userId, $seedPayload);
            }
        }

        $firstName = trim((string) ($input['plr_name'] ?? ''));
        $surname = trim((string) ($input['plr_surname'] ?? ''));
        if (!$playerId && ($firstName === '' || $surname === '')) {
            app_json_response([
                'success' => false,
                'requires_name' => true,
                'message' => 'Enter your first name and surname to complete this tournament registration.',
            ], 422);
        }

        if (!$playerId && $userId > 0) {
            $playerId = player_create_or_update_for_user($conn, $userId, [
                'plr_name' => $firstName,
                'plr_surname' => $surname,
            ]);
        } elseif (!$playerId) {
            $playerId = player_create_guest($conn, [
                'plr_name' => $firstName,
                'plr_surname' => $surname,
            ]);
        }
    }

    $existingStmt = $conn->prepare(
        'SELECT player_status
         FROM tournament_players
         WHERE tour_id = ? AND plr_id = ?'
    );
    $existingStmt->bind_param('ii', $tournamentId, $playerId);
    $existingStmt->execute();
    $existing = $existingStmt->get_result()->fetch_assoc();
    $existingStmt->close();

    if ($existing) {
        $conn->rollback();
        app_json_response(['success' => false, 'message' => 'You are already registered for this tournament.']);
    }

    $insert = $conn->prepare(
        'INSERT INTO tournament_players (tour_id, plr_id, player_status, group_number)
         VALUES (?, ?, ?, NULL)'
    );
    $status = 'Registered';
    $insert->bind_param('iis', $tournamentId, $playerId, $status);
    $insert->execute();
    $insert->close();

    $conn->commit();
    if ($registrationEmail !== null && $registrationEmail !== '') {
        app_send_best_effort_email(
            $registrationEmail,
            $registrationName ?: 'Club Member',
            'Tournament registration confirmed',
            'Your registration for <strong>' . htmlspecialchars((string) $tournament['tour_title'], ENT_QUOTES, 'UTF-8') . '</strong> is recorded.<br>Your entry is currently marked as <strong>Registered</strong> and will appear in the live competition roster once the tournament structure is generated.',
            'Your registration for ' . (string) $tournament['tour_title'] . ' is recorded. Your entry is currently marked as Registered and will appear in the live competition roster once the tournament structure is generated.'
        );
    }

    app_json_response([
        'success' => true,
        'message' => 'Successfully registered for the tournament.',
    ]);
} catch (Throwable $exception) {
    try {
        $conn->rollback();
    } catch (Throwable $ignored) {
    }
    app_json_response(['success' => false, 'message' => 'Database error: ' . app_safe_error_message($exception)], 500);
}
