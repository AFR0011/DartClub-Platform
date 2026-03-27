<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/shared/tournament_helpers.php';

app_start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method.';
    app_redirect('../pages/admin/manage_tournaments.php');
}

$playerIds = tournament_unique_player_ids($_POST['selectedPlayers'] ?? []);

try {
    $db = app_db_connect();
    $db->begin_transaction();

    tournament_create_with_players($db, $_POST, $playerIds);

    $db->commit();
    $_SESSION['success'] = 'Tournament created successfully.';
} catch (Throwable $exception) {
    if (isset($db) && $db instanceof mysqli) {
        $db->rollback();
    }

    $_SESSION['error'] = 'Error creating tournament: ' . $exception->getMessage();
}

app_redirect('../pages/admin/manage_tournaments.php');
