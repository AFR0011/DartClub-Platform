<?php

require_once dirname(__DIR__) . '/services/app_bootstrap.php';
require_once dirname(__DIR__) . '/services/shared/tournament_helpers.php';

function contract_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function scalar_count(mysqli $db, string $sql, int $tourId): int
{
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $tourId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_row();
    $stmt->close();

    return (int) ($row[0] ?? 0);
}

function create_contract_tournament(mysqli $db, string $type, array $playerIds): int
{
    $settings = [
        'tour_title' => 'Portfolio contract - ' . $type,
        'tour_type' => $type,
        'tour_startDate' => date('Y-m-d', strtotime('+2 days')),
        'tour_endDate' => date('Y-m-d', strtotime('+12 days')),
        'registration_open_at' => date('Y-m-d', strtotime('-10 days')),
        'registration_close_at' => date('Y-m-d', strtotime('-2 days')),
        'selectedPlayersStatus' => 'Active',
        'is_public' => 1,
    ];
    if ($type === 'League') {
        $settings['group_count'] = 2;
        $settings['advancers_per_group'] = 2;
    }
    if ($type === 'Group') {
        $settings['team_count'] = 2;
    }

    return tournament_create_with_players($db, $settings, $playerIds);
}

$db = app_db_connect();
$db->begin_transaction();

try {
    $playerIds = [];
    $insert = $db->prepare(
        'INSERT INTO players (plr_name, plr_surname, plr_username)
         VALUES (?, ?, ?)'
    );
    for ($index = 1; $index <= 8; $index++) {
        $firstName = 'Contract';
        $surname = 'Player ' . $index;
        $username = 'portfolio-contract-player-' . $index;
        $insert->bind_param('sss', $firstName, $surname, $username);
        $insert->execute();
        $playerIds[] = (int) $db->insert_id;
    }
    $insert->close();

    contract_assert(tournament_supported_types() === [
        'Round Robin', 'League', 'Group', 'Elimination', 'Double Elimination',
    ], 'The maintained tournament type registry changed unexpectedly.');

    $roundRobinId = create_contract_tournament($db, 'Round Robin', $playerIds);
    contract_assert(
        scalar_count($db, 'SELECT COUNT(*) FROM matches WHERE tour_id = ?', $roundRobinId) === 28,
        'Eight-player Round Robin must create 28 fixtures.'
    );
    $roundRobinMatch = $db->query(
        'SELECT match_id FROM matches WHERE tour_id = ' . $roundRobinId . ' ORDER BY match_id LIMIT 1'
    )->fetch_assoc();
    tournament_record_match_result($db, (int) $roundRobinMatch['match_id'], 2, 1);
    contract_assert(
        scalar_count($db, 'SELECT COUNT(*) FROM tournament_standings WHERE tour_id = ? AND matches_played = 1', $roundRobinId) === 2,
        'Round Robin result must update both player standings.'
    );

    $leagueId = create_contract_tournament($db, 'League', $playerIds);
    contract_assert(
        scalar_count($db, 'SELECT COUNT(*) FROM matches WHERE tour_id = ? AND group_number IS NOT NULL', $leagueId) === 12,
        'Two four-player league groups must create 12 group-stage fixtures.'
    );
    $leagueMatches = $db->query(
        'SELECT match_id FROM matches WHERE tour_id = ' . $leagueId . ' AND group_number IS NOT NULL ORDER BY match_id'
    )->fetch_all(MYSQLI_ASSOC);
    foreach ($leagueMatches as $row) {
        tournament_record_match_result($db, (int) $row['match_id'], 2, 1);
    }
    contract_assert(
        scalar_count($db, 'SELECT COUNT(*) FROM matches WHERE tour_id = ? AND group_number IS NULL', $leagueId) > 0,
        'Completing a league group stage must create its knockout stage.'
    );

    $groupId = create_contract_tournament($db, 'Group', $playerIds);
    contract_assert(
        scalar_count($db, 'SELECT COUNT(*) FROM tournament_teams WHERE tour_id = ?', $groupId) === 2,
        'Group tournaments must create exactly two teams.'
    );
    contract_assert(
        scalar_count($db, 'SELECT COUNT(*) FROM matches WHERE tour_id = ?', $groupId) === 16,
        'Two four-player teams must create 16 cross-team fixtures.'
    );
    contract_assert(
        scalar_count($db, 'SELECT COUNT(*) FROM team_matches WHERE tour_id = ?', $groupId) === 0,
        'New Group tournaments must use player fixtures rather than legacy team_matches.'
    );

    $eliminationId = create_contract_tournament($db, 'Elimination', $playerIds);
    contract_assert(
        scalar_count($db, "SELECT COUNT(*) FROM matches WHERE tour_id = ? AND bracket = 'Elimination'", $eliminationId) === 7,
        'Eight-player single elimination must create seven bracket matches.'
    );
    contract_assert(
        scalar_count($db, "SELECT COUNT(*) FROM matches WHERE tour_id = ? AND bracket = 'Third Place Playoff'", $eliminationId) === 1,
        'Single elimination must include one third-place playoff.'
    );

    $doubleId = create_contract_tournament($db, 'Double Elimination', $playerIds);
    foreach (['Opening Round', 'Winners Bracket', 'Losers Bracket', 'Third Place Playoff', 'Grand Final'] as $bracket) {
        $stmt = $db->prepare('SELECT COUNT(*) FROM matches WHERE tour_id = ? AND bracket = ?');
        $stmt->bind_param('is', $doubleId, $bracket);
        $stmt->execute();
        $count = (int) $stmt->get_result()->fetch_row()[0];
        $stmt->close();
        contract_assert($count > 0, 'Double Elimination is missing bracket segment: ' . $bracket);
    }

    $opening = $db->query(
        "SELECT * FROM matches
         WHERE tour_id = {$doubleId}
           AND bracket = 'Opening Round'
           AND player1_id IS NOT NULL
           AND player2_id IS NOT NULL
           AND next_match_id IS NOT NULL
           AND loser_next_match_id IS NOT NULL
         ORDER BY match_id LIMIT 1"
    )->fetch_assoc();
    contract_assert((bool) $opening, 'Double Elimination needs a linked opening match.');
    $winnerId = (int) $opening['player1_id'];
    $loserId = (int) $opening['player2_id'];
    tournament_record_match_result($db, (int) $opening['match_id'], 3, 1);

    $next = tournament_fetch_match($db, (int) $opening['next_match_id']);
    $loserNext = tournament_fetch_match($db, (int) $opening['loser_next_match_id']);
    contract_assert(
        in_array($winnerId, [(int) $next['player1_id'], (int) $next['player2_id']], true),
        'Opening-round winner did not propagate to the winners path.'
    );
    contract_assert(
        in_array($loserId, [(int) $loserNext['player1_id'], (int) $loserNext['player2_id']], true),
        'Opening-round loser did not propagate to the losers path.'
    );

    $db->rollback();
    echo "tournament contracts ok\n";
} catch (Throwable $exception) {
    $db->rollback();
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
