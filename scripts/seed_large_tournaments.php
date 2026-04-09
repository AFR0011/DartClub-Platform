<?php

require_once dirname(__DIR__) . '/services/app_bootstrap.php';
require_once dirname(__DIR__) . '/services/shared/tournament_helpers.php';

function scale_seed_player_ids(mysqli $db, int $count): array
{
    $playerIds = [];
    $select = $db->prepare('SELECT plr_idNum FROM players WHERE plr_username = ? LIMIT 1');
    $insert = $db->prepare(
        'INSERT INTO players (plr_name, plr_surname, plr_username)
         VALUES (?, ?, ?)'
    );

    for ($index = 1; $index <= $count; $index++) {
        $suffix = str_pad((string) $index, 2, '0', STR_PAD_LEFT);
        $username = 'scale-player-' . $suffix;
        $select->bind_param('s', $username);
        $select->execute();
        $existing = $select->get_result()->fetch_assoc();

        if ($existing) {
            $playerIds[] = (int) $existing['plr_idNum'];
            continue;
        }

        $firstName = 'Scale';
        $surname = 'Player ' . $suffix;
        $insert->bind_param('sss', $firstName, $surname, $username);
        $insert->execute();
        $playerIds[] = (int) $db->insert_id;
    }

    $select->close();
    $insert->close();

    sort($playerIds);

    return $playerIds;
}

function delete_tournament_by_title(mysqli $db, string $title): void
{
    $find = $db->prepare('SELECT tour_id FROM tournaments WHERE tour_title = ?');
    $find->bind_param('s', $title);
    $find->execute();
    $rows = $find->get_result()->fetch_all(MYSQLI_ASSOC);
    $find->close();

    if (!$rows) {
        return;
    }

    $delete = $db->prepare('DELETE FROM tournaments WHERE tour_id = ?');
    foreach ($rows as $row) {
        $tourId = (int) $row['tour_id'];
        $delete->bind_param('i', $tourId);
        $delete->execute();
    }
    $delete->close();
}

function scale_seed_definition(string $title, string $type): array
{
    $settings = [
        'tour_title' => $title,
        'tour_type' => $type,
        'tour_startDate' => date('Y-m-d', strtotime('+2 day')),
        'tour_endDate' => date('Y-m-d', strtotime('+9 day')),
        'registration_open_at' => date('Y-m-d', strtotime('-10 day')),
        'registration_close_at' => date('Y-m-d', strtotime('-2 day')),
        'selectedPlayersStatus' => 'Active',
        'is_public' => 1,
    ];

    if ($type === 'League') {
        $settings['group_count'] = 8;
        $settings['advancers_per_group'] = 2;
    }

    if ($type === 'Group') {
        $settings['team_count'] = 2;
    }

    return $settings;
}

$db = app_db_connect();
$db->begin_transaction();

try {
    $playerIds = scale_seed_player_ids($db, 64);
    $definitions = [
        ['title' => 'Scale Test - League 64', 'type' => 'League'],
        ['title' => 'Scale Test - Group 64', 'type' => 'Group'],
        ['title' => 'Scale Test - Elimination 64', 'type' => 'Elimination'],
        ['title' => 'Scale Test - Double Elimination 64', 'type' => 'Double Elimination'],
    ];

    $result = [];

    foreach ($definitions as $definition) {
        delete_tournament_by_title($db, $definition['title']);
        $tourId = tournament_create_with_players(
            $db,
            scale_seed_definition($definition['title'], $definition['type']),
            $playerIds
        );
        $pageData = tournament_fetch_page_data($db, $tourId);

        $result[] = [
            'tour_id' => $tourId,
            'tour_title' => $definition['title'],
            'tour_type' => $definition['type'],
            'player_count' => (int) ($pageData['tournament']['player_count'] ?? 0),
            'match_count' => (int) ($pageData['tournament']['match_count'] ?? 0),
            'team_match_count' => (int) ($pageData['tournament']['team_match_count'] ?? 0),
            'team_count_actual' => (int) ($pageData['tournament']['team_count_actual'] ?? 0),
            'status' => $pageData['tournament']['status'] ?? null,
        ];
    }

    $db->commit();
    echo json_encode([
        'success' => true,
        'seeded_players' => count($playerIds),
        'tournaments' => $result,
    ], JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Throwable $exception) {
    $db->rollback();
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
