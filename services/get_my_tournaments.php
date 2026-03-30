<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/shared/player_helpers.php';

app_start_session();

if (!isset($_SESSION['user_id'])) {
    app_json_response(['success' => false, 'message' => 'Not logged in'], 401);
}

try {
    $playerId = player_id_for_user($conn, (int) $_SESSION['user_id']);
    if (!$playerId) {
        app_json_response([]);
    }

    $sql = "SELECT
                t.tour_id,
                t.tour_title,
                t.tour_type,
                t.status,
                t.tour_creationDate,
                t.tour_endDate,
                t.winner_label,
                tp.player_status,
                tp.registration_date,
                tp.final_rank,
                tp.placement_label
            FROM tournament_players tp
            JOIN tournaments t ON tp.tour_id = t.tour_id
            WHERE tp.plr_id = ?
            ORDER BY tp.registration_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $playerId);
    $stmt->execute();
    $tournaments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    app_json_response($tournaments);
} catch (Throwable $exception) {
    app_json_response(['error' => 'Failed to fetch tournaments: ' . $exception->getMessage()], 500);
}

