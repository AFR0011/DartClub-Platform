<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/shared/player_helpers.php';

app_start_session();

try {
    $playerId = null;
    if (isset($_SESSION['user_id'])) {
        $playerId = player_id_for_user($conn, (int) $_SESSION['user_id']);
    }

    $stmt = $conn->prepare('SELECT tour_id, tour_title, tour_creationDate, tour_endDate, tour_type FROM tournaments ORDER BY tour_creationDate DESC');
    $stmt->execute();
    $result = $stmt->get_result();

    $tournaments = [];
    while ($row = $result->fetch_assoc()) {
        $row['registered'] = false;
        $row['player_status'] = null;

        if ($playerId) {
            $registrationStmt = $conn->prepare('SELECT player_status FROM tournament_players WHERE tour_id = ? AND plr_id = ?');
            $registrationStmt->bind_param('ii', $row['tour_id'], $playerId);
            $registrationStmt->execute();
            $registration = $registrationStmt->get_result();
            if ($registration->num_rows > 0) {
                $registrationRow = $registration->fetch_assoc();
                $row['registered'] = true;
                $row['player_status'] = $registrationRow['player_status'];
            }
            $registrationStmt->close();
        }

        $tournaments[] = $row;
    }
    $stmt->close();

    app_json_response($tournaments);
} catch (Throwable $exception) {
    app_json_response(['error' => 'Failed to fetch tournaments: ' . $exception->getMessage()], 500);
}
