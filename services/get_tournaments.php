<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/shared/player_helpers.php';
require_once __DIR__ . '/shared/tournament_helpers.php';
require_once __DIR__ . '/shared/tournament_view_helpers.php';

app_start_session();

try {
    $playerId = null;
    if (isset($_SESSION['user_id'])) {
        $playerId = player_id_for_user($conn, (int) $_SESSION['user_id']);
    }

    $stmt = $conn->prepare(
        "SELECT
            t.tour_id,
            t.tour_title,
            t.tour_creationDate,
            t.tour_endDate,
            t.tour_type,
            t.status,
            t.registration_open_at,
            t.registration_close_at,
            t.group_count,
            t.advancers_per_group,
            t.team_count,
            t.winner_label,
            t.is_public
         FROM tournaments t
         WHERE t.is_public = 1
         ORDER BY
            CASE t.status
                WHEN 'in_progress' THEN 0
                WHEN 'registration_open' THEN 1
                WHEN 'registration_closed' THEN 2
                WHEN 'completed' THEN 3
                WHEN 'archived' THEN 4
                ELSE 5
            END,
            t.tour_creationDate DESC"
    );
    $stmt->execute();
    $result = $stmt->get_result();

    $tournaments = [];
    while ($row = $result->fetch_assoc()) {
        tournament_refresh_lifecycle($conn, (int) $row['tour_id']);
        $row = tournament_fetch_settings($conn, (int) $row['tour_id']);
        $row['registered'] = false;
        $row['player_status'] = null;
        $row['detail_url'] = 'tournament_details.php?id=' . (int) $row['tour_id'];

        if ($playerId) {
            $registrationStmt = $conn->prepare(
                'SELECT player_status
                 FROM tournament_players
                 WHERE tour_id = ? AND plr_id = ?'
            );
            $registrationStmt->bind_param('ii', $row['tour_id'], $playerId);
            $registrationStmt->execute();
            $registration = $registrationStmt->get_result()->fetch_assoc();
            $registrationStmt->close();

            if ($registration) {
                $row['registered'] = true;
                $row['player_status'] = $registration['player_status'];
            }
        }

        $tournaments[] = $row;
    }
    $stmt->close();

    app_json_response($tournaments);
} catch (Throwable $exception) {
    app_json_response(['error' => 'Failed to fetch tournaments: ' . $exception->getMessage()], 500);
}
