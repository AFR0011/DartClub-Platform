<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/shared/tournament_helpers.php';

app_start_session();
require_any_role(['admin', 'manager']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    app_redirect('../pages/admin/manage_tournaments.php');
}

$tourId = isset($_POST['tour_id']) ? (int) $_POST['tour_id'] : 0;
if ($tourId <= 0) {
    $_SESSION['error'] = 'Tournament ID is required.';
    app_redirect('../pages/admin/manage_tournaments.php');
}

try {
    $db = app_db_connect();
    $db->begin_transaction();

    tournament_update($db, $tourId, $_POST);

    $db->commit();
    $_SESSION['success'] = 'Tournament updated successfully.';
} catch (Throwable $exception) {
    if (isset($db) && $db instanceof mysqli) {
        $db->rollback();
    }

    $_SESSION['error'] = 'Error updating tournament: ' . $exception->getMessage();
}

app_redirect('../pages/admin/show_tournament_details.php?id=' . $tourId);
