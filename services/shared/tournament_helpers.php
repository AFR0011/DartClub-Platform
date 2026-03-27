<?php

require_once __DIR__ . '/../app_bootstrap.php';
require_once __DIR__ . '/player_helpers.php';

function tournament_supported_types(): array
{
    return ['League', 'Group', 'Elimination'];
}

function tournament_normalize_settings(array $input, int $playerCount = 0): array
{
    $type = trim((string) ($input['tour_type'] ?? ''));
    if (!in_array($type, tournament_supported_types(), true)) {
        throw new InvalidArgumentException('Unsupported tournament type.');
    }

    $title = trim((string) ($input['tour_title'] ?? ''));
    if ($title === '') {
        throw new InvalidArgumentException('Tournament title is required.');
    }

    $startDate = app_parse_date((string) ($input['tour_startDate'] ?? ''), 'Start date');
    $endDate = app_parse_date((string) ($input['tour_endDate'] ?? ''), 'End date');

    if ($endDate < $startDate) {
        throw new InvalidArgumentException('End date cannot be before start date.');
    }

    $groupCountRaw = $input['group_count'] ?? $input['num_groups'] ?? null;
    $advancersRaw = $input['advancers_per_group'] ?? $input['players_advancing'] ?? null;

    $groupCount = app_value_or_null($groupCountRaw);
    $advancersPerGroup = app_value_or_null($advancersRaw);

    if ($type === 'Group') {
        $groupCount = max(2, (int) $groupCount);
        $advancersPerGroup = max(1, (int) $advancersPerGroup);

        if ($playerCount > 0 && $groupCount > $playerCount) {
            throw new InvalidArgumentException('Group count cannot exceed the player count.');
        }

        if ($playerCount > 0 && ($groupCount * $advancersPerGroup) >= $playerCount) {
            throw new InvalidArgumentException('Advancing players must leave enough room for a knockout stage.');
        }
    } else {
        $groupCount = null;
        $advancersPerGroup = null;
    }

    return [
        'tour_type' => $type,
        'tour_title' => $title,
        'tour_startDate' => $startDate,
        'tour_endDate' => $endDate,
        'group_count' => $groupCount,
        'advancers_per_group' => $advancersPerGroup,
    ];
}

function tournament_unique_player_ids(array $playerIds): array
{
    $unique = [];

    foreach ($playerIds as $playerId) {
        $id = (int) $playerId;
        if ($id > 0 && !in_array($id, $unique, true)) {
            $unique[] = $id;
        }
    }

    return $unique;
}

function tournament_insert(mysqli $db, array $settings): int
{
    $sql = 'INSERT INTO tournaments (
                tour_title,
                tour_creationDate,
                tour_endDate,
                tour_type,
                group_count,
                advancers_per_group
            ) VALUES (?, ?, ?, ?, ?, ?)';

    $stmt = $db->prepare($sql);
    $startDate = $settings['tour_startDate']->format('Y-m-d H:i:s');
    $endDate = $settings['tour_endDate']->format('Y-m-d H:i:s');
    $groupCount = $settings['group_count'];
    $advancersPerGroup = $settings['advancers_per_group'];
    $stmt->bind_param(
        'ssssii',
        $settings['tour_title'],
        $startDate,
        $endDate,
        $settings['tour_type'],
        $groupCount,
        $advancersPerGroup
    );
    $stmt->execute();
    $tournamentId = (int) $db->insert_id;
    $stmt->close();

    return $tournamentId;
}

function tournament_attach_players(mysqli $db, int $tourId, array $playerIds, ?array $groupAssignments = null, string $defaultStatus = 'Active'): void
{
    $sql = 'INSERT INTO tournament_players (tour_id, plr_id, player_status, group_number)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                player_status = VALUES(player_status),
                group_number = VALUES(group_number)';

    $stmt = $db->prepare($sql);

    foreach ($playerIds as $playerId) {
        $groupNumber = $groupAssignments[$playerId] ?? null;
        $stmt->bind_param('iisi', $tourId, $playerId, $defaultStatus, $groupNumber);
        $stmt->execute();
    }

    $stmt->close();
}

function tournament_initialize_standings(mysqli $db, int $tourId, array $playerIds): void
{
    $sql = 'INSERT INTO tournament_standings (
                tour_id,
                player_id,
                matches_played,
                matches_won,
                matches_lost,
                matches_drawn,
                points,
                leg_difference
            ) VALUES (?, ?, 0, 0, 0, 0, 0, 0)
            ON DUPLICATE KEY UPDATE
                matches_played = VALUES(matches_played),
                matches_won = VALUES(matches_won),
                matches_lost = VALUES(matches_lost),
                matches_drawn = VALUES(matches_drawn),
                points = VALUES(points),
                leg_difference = VALUES(leg_difference)';

    $stmt = $db->prepare($sql);

    foreach ($playerIds as $playerId) {
        $stmt->bind_param('ii', $tourId, $playerId);
        $stmt->execute();
    }

    $stmt->close();
}

function tournament_insert_match(mysqli $db, array $matchData): int
{
    $sql = 'INSERT INTO matches (
                tour_id,
                round_number,
                match_date,
                match_time,
                player1_id,
                player2_id,
                match_status,
                next_match_id,
                position_in_next,
                bracket,
                group_number,
                loser_next_match_id,
                loser_position_in_next
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

    $stmt = $db->prepare($sql);

    $tourId = (int) $matchData['tour_id'];
    $roundNumber = (int) ($matchData['round_number'] ?? 1);
    $matchDate = $matchData['match_date'];
    $matchTime = $matchData['match_time'];
    $player1Id = $matchData['player1_id'] ?? null;
    $player2Id = $matchData['player2_id'] ?? null;
    $matchStatus = $matchData['match_status'] ?? 'Scheduled';
    $nextMatchId = $matchData['next_match_id'] ?? null;
    $positionInNext = (int) ($matchData['position_in_next'] ?? 1);
    $bracket = $matchData['bracket'] ?? null;
    $groupNumber = $matchData['group_number'] ?? null;
    $loserNextMatchId = $matchData['loser_next_match_id'] ?? null;
    $loserPositionInNext = $matchData['loser_position_in_next'] ?? null;

    $stmt->bind_param(
        'iissiisiisiii',
        $tourId,
        $roundNumber,
        $matchDate,
        $matchTime,
        $player1Id,
        $player2Id,
        $matchStatus,
        $nextMatchId,
        $positionInNext,
        $bracket,
        $groupNumber,
        $loserNextMatchId,
        $loserPositionInNext
    );
    $stmt->execute();
    $matchId = (int) $db->insert_id;
    $stmt->close();

    return $matchId;
}

function tournament_schedule_round_robin(mysqli $db, int $tourId, array $playerIds, string $startDate, ?int $groupNumber = null, int $roundNumber = 1): void
{
    $players = array_values($playerIds);
    $matchClock = new DateTime($startDate . ' 18:00:00');

    for ($i = 0; $i < count($players); $i++) {
        for ($j = $i + 1; $j < count($players); $j++) {
            tournament_insert_match($db, [
                'tour_id' => $tourId,
                'round_number' => $roundNumber,
                'match_date' => $matchClock->format('Y-m-d'),
                'match_time' => $matchClock->format('H:i:s'),
                'player1_id' => $players[$i],
                'player2_id' => $players[$j],
                'group_number' => $groupNumber,
                'match_status' => 'Scheduled',
            ]);

            $matchClock->modify('+15 minutes');
        }
    }
}

function tournament_assign_groups(array $playerIds, int $groupCount): array
{
    $shuffled = array_values($playerIds);
    shuffle($shuffled);

    $assignments = [];
    $groupNumber = 1;

    foreach ($shuffled as $playerId) {
        $assignments[$playerId] = $groupNumber;
        $groupNumber++;

        if ($groupNumber > $groupCount) {
            $groupNumber = 1;
        }
    }

    return $assignments;
}

function tournament_grouped_player_ids(array $groupAssignments): array
{
    $grouped = [];

    foreach ($groupAssignments as $playerId => $groupNumber) {
        if (!isset($grouped[$groupNumber])) {
            $grouped[$groupNumber] = [];
        }

        $grouped[$groupNumber][] = (int) $playerId;
    }

    ksort($grouped);

    return $grouped;
}

function tournament_link_entry_to_next_match(mysqli $db, array $entry, int $nextMatchId, int $position): void
{
    if (isset($entry['match_id'])) {
        $sql = 'UPDATE matches SET next_match_id = ?, position_in_next = ? WHERE match_id = ?';
        $stmt = $db->prepare($sql);
        $matchId = (int) $entry['match_id'];
        $stmt->bind_param('iii', $nextMatchId, $position, $matchId);
        $stmt->execute();
        $stmt->close();
        return;
    }

    $field = $position === 1 ? 'player1_id' : 'player2_id';
    $sql = 'UPDATE matches SET ' . $field . ' = ? WHERE match_id = ?';
    $stmt = $db->prepare($sql);
    $playerId = (int) $entry['player_id'];
    $stmt->bind_param('ii', $playerId, $nextMatchId);
    $stmt->execute();
    $stmt->close();
}

function tournament_create_elimination_bracket(mysqli $db, int $tourId, array $playerIds, string $startDate, ?string $bracket = null): void
{
    $entries = [];
    $players = array_values($playerIds);
    shuffle($players);
    $clock = new DateTime($startDate . ' 18:00:00');

    for ($i = 0; $i < count($players); $i += 2) {
        if (!isset($players[$i + 1])) {
            $entries[] = ['player_id' => $players[$i]];
            continue;
        }

        $matchId = tournament_insert_match($db, [
            'tour_id' => $tourId,
            'round_number' => 1,
            'match_date' => $clock->format('Y-m-d'),
            'match_time' => $clock->format('H:i:s'),
            'player1_id' => $players[$i],
            'player2_id' => $players[$i + 1],
            'match_status' => 'Scheduled',
            'bracket' => $bracket,
        ]);
        $entries[] = ['match_id' => $matchId];
        $clock->modify('+30 minutes');
    }

    $roundNumber = 2;
    while (count($entries) > 1) {
        $nextEntries = [];
        $roundClock = new DateTime($startDate . ' 18:00:00');
        $roundClock->modify('+' . ($roundNumber - 1) . ' day');

        for ($i = 0; $i < count($entries); $i += 2) {
            if (!isset($entries[$i + 1])) {
                $nextEntries[] = $entries[$i];
                continue;
            }

            $matchId = tournament_insert_match($db, [
                'tour_id' => $tourId,
                'round_number' => $roundNumber,
                'match_date' => $roundClock->format('Y-m-d'),
                'match_time' => $roundClock->format('H:i:s'),
                'match_status' => 'Scheduled',
                'bracket' => $bracket,
            ]);

            tournament_link_entry_to_next_match($db, $entries[$i], $matchId, 1);
            tournament_link_entry_to_next_match($db, $entries[$i + 1], $matchId, 2);

            $nextEntries[] = ['match_id' => $matchId];
            $roundClock->modify('+30 minutes');
        }

        $entries = $nextEntries;
        $roundNumber++;
    }
}

function tournament_rebuild_structure(mysqli $db, int $tourId, string $tourType, array $playerIds, string $startDate, ?int $groupCount = null): void
{
    $deleteMatches = $db->prepare('DELETE FROM matches WHERE tour_id = ?');
    $deleteMatches->bind_param('i', $tourId);
    $deleteMatches->execute();
    $deleteMatches->close();

    $deleteStandings = $db->prepare('DELETE FROM tournament_standings WHERE tour_id = ?');
    $deleteStandings->bind_param('i', $tourId);
    $deleteStandings->execute();
    $deleteStandings->close();

    $clearGroups = $db->prepare('UPDATE tournament_players SET group_number = NULL WHERE tour_id = ?');
    $clearGroups->bind_param('i', $tourId);
    $clearGroups->execute();
    $clearGroups->close();

    if ($tourType === 'League') {
        tournament_initialize_standings($db, $tourId, $playerIds);
        tournament_schedule_round_robin($db, $tourId, $playerIds, $startDate);
        return;
    }

    if ($tourType === 'Group') {
        $groupAssignments = tournament_assign_groups($playerIds, (int) $groupCount);
        $groupedPlayers = tournament_grouped_player_ids($groupAssignments);
        tournament_attach_players($db, $tourId, $playerIds, $groupAssignments, 'Active');
        tournament_initialize_standings($db, $tourId, $playerIds);

        foreach ($groupedPlayers as $groupNumber => $groupPlayerIds) {
            tournament_schedule_round_robin($db, $tourId, $groupPlayerIds, $startDate, (int) $groupNumber, 1);
        }
        return;
    }

    tournament_create_elimination_bracket($db, $tourId, $playerIds, $startDate);
}

function tournament_create_with_players(mysqli $db, array $settings, array $playerIds): int
{
    $playerIds = tournament_unique_player_ids($playerIds);
    if (count($playerIds) < 2) {
        throw new InvalidArgumentException('At least two players must be selected.');
    }

    $settings = tournament_normalize_settings($settings, count($playerIds));
    $tourId = tournament_insert($db, $settings);

    if ($settings['tour_type'] === 'Group') {
        $groupAssignments = tournament_assign_groups($playerIds, (int) $settings['group_count']);
        tournament_attach_players($db, $tourId, $playerIds, $groupAssignments, 'Active');
    } else {
        tournament_attach_players($db, $tourId, $playerIds, null, 'Active');
    }

    tournament_rebuild_structure(
        $db,
        $tourId,
        $settings['tour_type'],
        $playerIds,
        $settings['tour_startDate']->format('Y-m-d'),
        $settings['group_count']
    );

    return $tourId;
}

function tournament_has_completed_matches(mysqli $db, int $tourId): bool
{
    $stmt = $db->prepare("SELECT COUNT(*) AS count_matches FROM matches WHERE tour_id = ? AND match_status = 'Completed'");
    $stmt->bind_param('i', $tourId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return ((int) ($result['count_matches'] ?? 0)) > 0;
}

function tournament_fetch_player_ids(mysqli $db, int $tourId): array
{
    $stmt = $db->prepare('SELECT plr_id FROM tournament_players WHERE tour_id = ? ORDER BY plr_id');
    $stmt->bind_param('i', $tourId);
    $stmt->execute();
    $result = $stmt->get_result();
    $playerIds = [];

    while ($row = $result->fetch_assoc()) {
        $playerIds[] = (int) $row['plr_id'];
    }

    $stmt->close();

    return $playerIds;
}

function tournament_fetch_settings(mysqli $db, int $tourId): array
{
    $stmt = $db->prepare('SELECT * FROM tournaments WHERE tour_id = ?');
    $stmt->bind_param('i', $tourId);
    $stmt->execute();
    $tournament = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$tournament) {
        throw new RuntimeException('Tournament not found.');
    }

    return $tournament;
}

function tournament_update(mysqli $db, int $tourId, array $payload): void
{
    $tournament = tournament_fetch_settings($db, $tourId);

    $playerRowsStmt = $db->prepare('SELECT plr_id, player_status FROM tournament_players WHERE tour_id = ?');
    $playerRowsStmt->bind_param('i', $tourId);
    $playerRowsStmt->execute();
    $playerRowsResult = $playerRowsStmt->get_result();

    $currentStatuses = [];
    while ($row = $playerRowsResult->fetch_assoc()) {
        $currentStatuses[(int) $row['plr_id']] = $row['player_status'];
    }
    $playerRowsStmt->close();

    $currentActivePlayerIds = array_values(array_keys(array_filter(
        $currentStatuses,
        static function (string $status): bool {
            return $status === 'Active';
        }
    )));

    $newPlayerIds = tournament_unique_player_ids($payload['new_players'] ?? []);
    $removePlayerIds = tournament_unique_player_ids($payload['remove_players'] ?? []);

    $finalStatuses = $currentStatuses;
    if (isset($payload['player_status']) && is_array($payload['player_status'])) {
        foreach ($payload['player_status'] as $playerId => $status) {
            $playerId = (int) $playerId;
            if (!array_key_exists($playerId, $finalStatuses)) {
                continue;
            }

            $safeStatus = trim((string) $status);
            if ($safeStatus !== '') {
                $finalStatuses[$playerId] = $safeStatus;
            }
        }
    }

    foreach ($removePlayerIds as $playerId) {
        unset($finalStatuses[$playerId]);
    }

    foreach ($newPlayerIds as $playerId) {
        if (!array_key_exists($playerId, $finalStatuses)) {
            $finalStatuses[$playerId] = 'Active';
        }
    }

    $targetActivePlayerIds = [];
    foreach ($finalStatuses as $playerId => $status) {
        if ($status === 'Active') {
            $targetActivePlayerIds[] = (int) $playerId;
        }
    }

    $settingsInput = [
        'tour_type' => $tournament['tour_type'],
        'tour_title' => $payload['tour_title'] ?? $tournament['tour_title'],
        'tour_startDate' => $payload['tour_startDate'] ?? date('Y-m-d', strtotime($tournament['tour_creationDate'])),
        'tour_endDate' => $payload['tour_endDate'] ?? $tournament['tour_endDate'],
        'group_count' => $payload['group_count'] ?? $tournament['group_count'],
        'advancers_per_group' => $payload['advancers_per_group'] ?? $tournament['advancers_per_group'],
    ];

    $settings = tournament_normalize_settings($settingsInput, count($targetActivePlayerIds));

    $updateTournament = $db->prepare(
        'UPDATE tournaments
         SET tour_title = ?, tour_creationDate = ?, tour_endDate = ?, group_count = ?, advancers_per_group = ?
         WHERE tour_id = ?'
    );
    $startDate = $settings['tour_startDate']->format('Y-m-d H:i:s');
    $endDate = $settings['tour_endDate']->format('Y-m-d H:i:s');
    $groupCount = $settings['group_count'];
    $advancersPerGroup = $settings['advancers_per_group'];
    $updateTournament->bind_param('sssiii', $settings['tour_title'], $startDate, $endDate, $groupCount, $advancersPerGroup, $tourId);
    $updateTournament->execute();
    $updateTournament->close();

    $statusStmt = $db->prepare('UPDATE tournament_players SET player_status = ? WHERE tour_id = ? AND plr_id = ?');
    foreach ($finalStatuses as $playerId => $status) {
        $statusStmt->bind_param('sii', $status, $tourId, $playerId);
        $statusStmt->execute();
    }
    $statusStmt->close();

    sort($currentActivePlayerIds);
    sort($targetActivePlayerIds);
    $structuralChange = $currentActivePlayerIds !== $targetActivePlayerIds;
    $groupSettingsChanged = $tournament['tour_type'] === 'Group'
        && ((int) $tournament['group_count'] !== (int) $settings['group_count']
            || (int) $tournament['advancers_per_group'] !== (int) $settings['advancers_per_group']);

    if (!$structuralChange && !$groupSettingsChanged) {
        return;
    }

    if (tournament_has_completed_matches($db, $tourId)) {
        $activations = array_values(array_diff($targetActivePlayerIds, $currentActivePlayerIds));
        $requiresHardRebuild = !empty($newPlayerIds) || !empty($removePlayerIds) || $groupSettingsChanged || !empty($activations);
        if ($requiresHardRebuild) {
            throw new RuntimeException('Cannot rebuild the tournament structure after completed matches exist.');
        }
        return;
    }

    if (count($targetActivePlayerIds) < 2) {
        throw new RuntimeException('A tournament needs at least two players.');
    }

    if (!empty($removePlayerIds)) {
        $deleteTournamentPlayers = $db->prepare('DELETE FROM tournament_players WHERE tour_id = ? AND plr_id = ?');
        $deleteStandings = $db->prepare('DELETE FROM tournament_standings WHERE tour_id = ? AND player_id = ?');

        foreach ($removePlayerIds as $playerId) {
            $deleteTournamentPlayers->bind_param('ii', $tourId, $playerId);
            $deleteTournamentPlayers->execute();
            $deleteStandings->bind_param('ii', $tourId, $playerId);
            $deleteStandings->execute();
        }

        $deleteTournamentPlayers->close();
        $deleteStandings->close();
    }

    if (!empty($newPlayerIds)) {
        tournament_attach_players($db, $tourId, $newPlayerIds, null, 'Active');
    }

    $statusStmt = $db->prepare('UPDATE tournament_players SET player_status = ? WHERE tour_id = ? AND plr_id = ?');
    foreach ($finalStatuses as $playerId => $status) {
        $statusStmt->bind_param('sii', $status, $tourId, $playerId);
        $statusStmt->execute();
    }
    $statusStmt->close();

    tournament_rebuild_structure(
        $db,
        $tourId,
        $tournament['tour_type'],
        $targetActivePlayerIds,
        $settings['tour_startDate']->format('Y-m-d'),
        $settings['group_count']
    );
}

function tournament_fetch_match(mysqli $db, int $matchId): array
{
    $sql = 'SELECT m.*, t.tour_type
            FROM matches m
            JOIN tournaments t ON t.tour_id = m.tour_id
            WHERE m.match_id = ?';

    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $matchId);
    $stmt->execute();
    $match = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$match) {
        throw new RuntimeException('Match not found.');
    }

    return $match;
}

function tournament_update_standings(mysqli $db, int $tourId, int $playerId, int $playerScore, int $opponentScore, bool $isWinner, bool $isDraw): void
{
    $sql = 'SELECT matches_played, matches_won, matches_lost, matches_drawn, points, leg_difference
            FROM tournament_standings
            WHERE tour_id = ? AND player_id = ?';

    $stmt = $db->prepare($sql);
    $stmt->bind_param('ii', $tourId, $playerId);
    $stmt->execute();
    $standing = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();

    $matchesPlayed = ((int) ($standing['matches_played'] ?? 0)) + 1;
    $matchesWon = ((int) ($standing['matches_won'] ?? 0)) + ($isWinner ? 1 : 0);
    $matchesLost = ((int) ($standing['matches_lost'] ?? 0)) + (!$isWinner && !$isDraw ? 1 : 0);
    $matchesDrawn = ((int) ($standing['matches_drawn'] ?? 0)) + ($isDraw ? 1 : 0);
    $points = ((int) ($standing['points'] ?? 0)) + ($isWinner ? 3 : ($isDraw ? 1 : 0));
    $legDifference = ((int) ($standing['leg_difference'] ?? 0)) + ($playerScore - $opponentScore);

    $upsert = $db->prepare(
        'INSERT INTO tournament_standings (
            tour_id,
            player_id,
            matches_played,
            matches_won,
            matches_lost,
            matches_drawn,
            points,
            leg_difference
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            matches_played = VALUES(matches_played),
            matches_won = VALUES(matches_won),
            matches_lost = VALUES(matches_lost),
            matches_drawn = VALUES(matches_drawn),
            points = VALUES(points),
            leg_difference = VALUES(leg_difference)'
    );
    $upsert->bind_param(
        'iiiiiiii',
        $tourId,
        $playerId,
        $matchesPlayed,
        $matchesWon,
        $matchesLost,
        $matchesDrawn,
        $points,
        $legDifference
    );
    $upsert->execute();
    $upsert->close();
}

function tournament_propagate_winner(mysqli $db, array $match, int $winnerId): void
{
    if (!empty($match['next_match_id'])) {
        $field = ((int) $match['position_in_next'] === 2) ? 'player2_id' : 'player1_id';
        $sql = 'UPDATE matches SET ' . $field . ' = ? WHERE match_id = ?';
        $stmt = $db->prepare($sql);
        $nextMatchId = (int) $match['next_match_id'];
        $stmt->bind_param('ii', $winnerId, $nextMatchId);
        $stmt->execute();
        $stmt->close();
    }

    if (!empty($match['loser_next_match_id']) && !empty($match['player1_id']) && !empty($match['player2_id'])) {
        $loserId = $winnerId === (int) $match['player1_id'] ? (int) $match['player2_id'] : (int) $match['player1_id'];
        $field = ((int) $match['loser_position_in_next'] === 2) ? 'player2_id' : 'player1_id';
        $sql = 'UPDATE matches SET ' . $field . ' = ? WHERE match_id = ?';
        $stmt = $db->prepare($sql);
        $loserNextMatchId = (int) $match['loser_next_match_id'];
        $stmt->bind_param('ii', $loserId, $loserNextMatchId);
        $stmt->execute();
        $stmt->close();
    }
}

function tournament_record_match_result(mysqli $db, int $matchId, int $player1Score, int $player2Score): array
{
    if ($player1Score < 0 || $player2Score < 0) {
        throw new InvalidArgumentException('Scores cannot be negative.');
    }

    $match = tournament_fetch_match($db, $matchId);
    $isGroupStageMatch = $match['tour_type'] === 'Group' && $match['group_number'] !== null;
    $isBracketMatch = $match['tour_type'] === 'Elimination' || ($match['tour_type'] === 'Group' && !$isGroupStageMatch);

    if ($player1Score === 0 && $player2Score === 0) {
        throw new InvalidArgumentException('At least one player must score.');
    }

    if ($isBracketMatch && $player1Score === $player2Score) {
        throw new InvalidArgumentException('Knockout matches cannot end in a draw.');
    }

    $winnerId = null;
    if ($player1Score > $player2Score) {
        $winnerId = (int) $match['player1_id'];
    } elseif ($player2Score > $player1Score) {
        $winnerId = (int) $match['player2_id'];
    }

    $update = $db->prepare(
        "UPDATE matches
         SET player1_score = ?, player2_score = ?, winner_id = ?, match_status = 'Completed'
         WHERE match_id = ?"
    );
    $update->bind_param('iiii', $player1Score, $player2Score, $winnerId, $matchId);
    $update->execute();
    $update->close();

    $isDraw = $winnerId === null;
    if ($match['tour_type'] === 'League' || $isGroupStageMatch) {
        tournament_update_standings(
            $db,
            (int) $match['tour_id'],
            (int) $match['player1_id'],
            $player1Score,
            $player2Score,
            $winnerId === (int) $match['player1_id'],
            $isDraw
        );
        tournament_update_standings(
            $db,
            (int) $match['tour_id'],
            (int) $match['player2_id'],
            $player2Score,
            $player1Score,
            $winnerId === (int) $match['player2_id'],
            $isDraw
        );
    }

    if (!$isDraw) {
        tournament_propagate_winner($db, $match, $winnerId);
    }

    return [
        'winner_id' => $winnerId,
        'is_draw' => $isDraw,
    ];
}

function tournament_group_standings(mysqli $db, int $tourId): array
{
    $sql = "SELECT
                tp.group_number,
                p.plr_idNum AS player_id,
                p.plr_name,
                p.plr_surname,
                SUM(CASE WHEN m.match_status = 'Completed' THEN 1 ELSE 0 END) AS matches_played,
                SUM(CASE WHEN m.match_status = 'Completed' AND m.winner_id = p.plr_idNum THEN 1 ELSE 0 END) AS matches_won,
                SUM(CASE WHEN m.match_status = 'Completed' AND m.winner_id IS NOT NULL AND m.winner_id <> p.plr_idNum THEN 1 ELSE 0 END) AS matches_lost,
                SUM(CASE WHEN m.match_status = 'Completed' AND m.winner_id IS NULL THEN 1 ELSE 0 END) AS matches_drawn,
                SUM(
                    CASE
                        WHEN m.match_status <> 'Completed' THEN 0
                        WHEN m.winner_id = p.plr_idNum THEN 3
                        WHEN m.winner_id IS NULL THEN 1
                        ELSE 0
                    END
                ) AS points,
                SUM(
                    CASE
                        WHEN m.match_status <> 'Completed' THEN 0
                        WHEN m.player1_id = p.plr_idNum THEN COALESCE(m.player1_score, 0) - COALESCE(m.player2_score, 0)
                        ELSE COALESCE(m.player2_score, 0) - COALESCE(m.player1_score, 0)
                    END
                ) AS leg_difference
            FROM tournament_players tp
            JOIN players p ON p.plr_idNum = tp.plr_id
            LEFT JOIN matches m
                ON m.tour_id = tp.tour_id
                AND m.group_number = tp.group_number
                AND (m.player1_id = tp.plr_id OR m.player2_id = tp.plr_id)
            WHERE tp.tour_id = ? AND tp.group_number IS NOT NULL
            GROUP BY tp.group_number, p.plr_idNum, p.plr_name, p.plr_surname
            ORDER BY tp.group_number, points DESC, leg_difference DESC, matches_won DESC, p.plr_surname, p.plr_name";

    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $tourId);
    $stmt->execute();
    $result = $stmt->get_result();

    $groupStandings = [];
    while ($row = $result->fetch_assoc()) {
        $groupNumber = (int) $row['group_number'];
        if (!isset($groupStandings[$groupNumber])) {
            $groupStandings[$groupNumber] = [];
        }

        $groupStandings[$groupNumber][] = $row;
    }

    $stmt->close();

    return $groupStandings;
}

function tournament_promote_groups(mysqli $db, int $tourId): int
{
    $tournament = tournament_fetch_settings($db, $tourId);
    if ($tournament['tour_type'] !== 'Group') {
        throw new RuntimeException('Tournament is not a group tournament.');
    }

    $unfinished = $db->prepare("SELECT COUNT(*) AS pending_matches FROM matches WHERE tour_id = ? AND group_number IS NOT NULL AND match_status <> 'Completed'");
    $unfinished->bind_param('i', $tourId);
    $unfinished->execute();
    $unfinishedResult = $unfinished->get_result()->fetch_assoc();
    $unfinished->close();

    if ((int) ($unfinishedResult['pending_matches'] ?? 0) > 0) {
        throw new RuntimeException('Finish all group-stage matches before promoting players.');
    }

    $existingKnockout = $db->prepare("SELECT COUNT(*) AS completed_knockout FROM matches WHERE tour_id = ? AND group_number IS NULL AND match_status = 'Completed'");
    $existingKnockout->bind_param('i', $tourId);
    $existingKnockout->execute();
    $existingKnockoutResult = $existingKnockout->get_result()->fetch_assoc();
    $existingKnockout->close();

    if ((int) ($existingKnockoutResult['completed_knockout'] ?? 0) > 0) {
        throw new RuntimeException('Knockout matches already started for this tournament.');
    }

    $deleteScheduledKnockout = $db->prepare('DELETE FROM matches WHERE tour_id = ? AND group_number IS NULL');
    $deleteScheduledKnockout->bind_param('i', $tourId);
    $deleteScheduledKnockout->execute();
    $deleteScheduledKnockout->close();

    $groupStandings = tournament_group_standings($db, $tourId);
    $advancers = [];
    foreach ($groupStandings as $rows) {
        $selected = array_slice($rows, 0, (int) $tournament['advancers_per_group']);
        foreach ($selected as $row) {
            $advancers[] = (int) $row['player_id'];
        }
    }

    $advancers = tournament_unique_player_ids($advancers);
    if (count($advancers) < 2) {
        throw new RuntimeException('Not enough players qualified from the groups.');
    }

    $startDate = new DateTime($tournament['tour_endDate']);
    $startDate->modify('+1 day');
    tournament_create_elimination_bracket($db, $tourId, $advancers, $startDate->format('Y-m-d'));

    return count($advancers);
}

function tournament_fetch_page_data(mysqli $db, int $tourId): array
{
    $detailsSql = "SELECT
                        t.*,
                        COUNT(DISTINCT tp.plr_id) AS player_count,
                        COUNT(DISTINCT m.match_id) AS match_count
                   FROM tournaments t
                   LEFT JOIN tournament_players tp ON tp.tour_id = t.tour_id
                   LEFT JOIN matches m ON m.tour_id = t.tour_id
                   WHERE t.tour_id = ?
                   GROUP BY t.tour_id";

    $detailsStmt = $db->prepare($detailsSql);
    $detailsStmt->bind_param('i', $tourId);
    $detailsStmt->execute();
    $tournament = $detailsStmt->get_result()->fetch_assoc();
    $detailsStmt->close();

    if (!$tournament) {
        throw new RuntimeException('Tournament not found.');
    }

    $playersSql = "SELECT
                        p.plr_idNum,
                        p.plr_name,
                        p.plr_surname,
                        tp.player_status,
                        tp.group_number
                   FROM players p
                   JOIN tournament_players tp ON tp.plr_id = p.plr_idNum
                   WHERE tp.tour_id = ?
                   ORDER BY tp.group_number IS NULL, tp.group_number, p.plr_surname, p.plr_name";
    $playersStmt = $db->prepare($playersSql);
    $playersStmt->bind_param('i', $tourId);
    $playersStmt->execute();
    $players = $playersStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $playersStmt->close();

    $matchesSql = "SELECT
                        m.*,
                        p1.plr_name AS player1_name,
                        p1.plr_surname AS player1_surname,
                        p2.plr_name AS player2_name,
                        p2.plr_surname AS player2_surname,
                        pm1.match_id AS prev_match1_id,
                        pm2.match_id AS prev_match2_id
                   FROM matches m
                   LEFT JOIN players p1 ON p1.plr_idNum = m.player1_id
                   LEFT JOIN players p2 ON p2.plr_idNum = m.player2_id
                   LEFT JOIN matches pm1 ON pm1.next_match_id = m.match_id AND pm1.position_in_next = 1
                   LEFT JOIN matches pm2 ON pm2.next_match_id = m.match_id AND pm2.position_in_next = 2
                   WHERE m.tour_id = ?
                   ORDER BY m.group_number IS NULL, m.group_number, m.round_number, m.match_date, m.match_time, m.match_id";
    $matchesStmt = $db->prepare($matchesSql);
    $matchesStmt->bind_param('i', $tourId);
    $matchesStmt->execute();
    $matches = $matchesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $matchesStmt->close();

    $standings = [];
    if ($tournament['tour_type'] === 'League') {
        $standingsSql = "SELECT
                            p.plr_idNum,
                            p.plr_name,
                            p.plr_surname,
                            s.matches_played,
                            s.matches_won,
                            s.matches_lost,
                            s.matches_drawn,
                            s.points,
                            s.leg_difference
                         FROM tournament_standings s
                         JOIN players p ON p.plr_idNum = s.player_id
                         WHERE s.tour_id = ?
                         ORDER BY s.points DESC, s.leg_difference DESC, p.plr_surname, p.plr_name";
        $standingsStmt = $db->prepare($standingsSql);
        $standingsStmt->bind_param('i', $tourId);
        $standingsStmt->execute();
        $standings = $standingsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $standingsStmt->close();
    }

    return [
        'tournament' => $tournament,
        'players' => $players,
        'matches' => $matches,
        'standings' => $standings,
        'group_standings' => $tournament['tour_type'] === 'Group' ? tournament_group_standings($db, $tourId) : [],
        'available_players' => players_not_in_tournament($db, $tourId),
    ];
}
