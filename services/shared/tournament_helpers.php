<?php

require_once __DIR__ . '/../app_bootstrap.php';
require_once __DIR__ . '/player_helpers.php';
require_once __DIR__ . '/tournament_view_helpers.php';

function tournament_supported_types(): array
{
    return ['Round Robin', 'League', 'Group', 'Elimination', 'Double Elimination'];
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

function tournament_compute_initial_status(DateTime $registrationOpenAt, DateTime $registrationCloseAt): string
{
    $now = new DateTime();
    if ($now < $registrationOpenAt) {
        return 'draft';
    }

    if ($now <= $registrationCloseAt) {
        return 'registration_open';
    }

    return 'registration_closed';
}

function tournament_normalize_settings(array $input, int $playerCount = 0): array
{
    $type = trim((string) ($input['tour_type'] ?? $input['format_code'] ?? ''));
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

    $registrationOpenAt = trim((string) ($input['registration_open_at'] ?? ''));
    $registrationCloseAt = trim((string) ($input['registration_close_at'] ?? ''));

    $registrationOpen = $registrationOpenAt !== ''
        ? app_parse_date($registrationOpenAt, 'Registration open date')
        : new DateTime();
    $registrationClose = $registrationCloseAt !== ''
        ? app_parse_date($registrationCloseAt, 'Registration close date')
        : clone $startDate;

    if ($registrationClose > $startDate) {
        $registrationClose = clone $startDate;
    }

    if ($registrationClose < $registrationOpen) {
        throw new InvalidArgumentException('Registration close date cannot be before the registration open date.');
    }

    $groupCount = app_value_or_null($input['group_count'] ?? null);
    $advancersPerGroup = app_value_or_null($input['advancers_per_group'] ?? null);
    $teamCount = app_value_or_null($input['team_count'] ?? null);

    if ($type === 'League') {
        $groupCount = max(2, (int) $groupCount);
        $advancersPerGroup = max(1, (int) $advancersPerGroup);
        $teamCount = null;
    } elseif ($type === 'Group') {
        $teamCount = 2;
        $groupCount = null;
        $advancersPerGroup = null;
    } else {
        $groupCount = null;
        $advancersPerGroup = null;
        $teamCount = null;
    }

    return [
        'tour_type' => $type,
        'format_code' => $type,
        'tour_title' => $title,
        'tour_startDate' => $startDate,
        'tour_endDate' => $endDate,
        'registration_open_at' => $registrationOpen,
        'registration_close_at' => $registrationClose,
        'group_count' => $groupCount,
        'advancers_per_group' => $advancersPerGroup,
        'team_count' => $teamCount,
        'status' => tournament_compute_initial_status($registrationOpen, $registrationClose),
        'is_public' => isset($input['is_public']) ? ((int) $input['is_public'] === 1 ? 1 : 0) : 1,
    ];
}

function tournament_validate_structure_settings(array $settings, int $playerCount): void
{
    if ($playerCount < 2) {
        throw new RuntimeException('At least two entrants are required before a tournament structure can be generated.');
    }

    if ($settings['tour_type'] === 'League') {
        $groupCount = max(2, (int) ($settings['group_count'] ?? 0));
        $advancersPerGroup = max(1, (int) ($settings['advancers_per_group'] ?? 0));

        if ($groupCount > $playerCount) {
            throw new InvalidArgumentException('Group count cannot exceed the number of tournament entrants.');
        }

        if (($groupCount * $advancersPerGroup) >= $playerCount) {
            throw new InvalidArgumentException('League settings must leave enough players for a knockout stage.');
        }
    }

    if ($settings['tour_type'] === 'Group') {
        $teamCount = max(2, (int) ($settings['team_count'] ?? 0));
        if ($teamCount !== 2) {
            throw new InvalidArgumentException('Group tournaments currently support exactly two teams.');
        }
        if ($teamCount > $playerCount) {
            throw new InvalidArgumentException('Team count cannot exceed the number of tournament entrants.');
        }
    }

    if ($settings['tour_type'] === 'Double Elimination' && $playerCount < 4) {
        throw new InvalidArgumentException('Double-elimination tournaments need at least four entrants.');
    }
}

function tournament_sanitize_player_status(string $status, string $fallback = 'Registered'): string
{
    $safeStatus = trim($status);
    $allowed = ['Active', 'Registered', 'Withdrawn'];

    if (in_array($safeStatus, $allowed, true)) {
        return $safeStatus;
    }

    return in_array($fallback, $allowed, true) ? $fallback : 'Registered';
}

function tournament_structure_is_locked(mysqli $db, int $tourId): bool
{
    return tournament_has_structure($db, $tourId) && tournament_has_completed_matches($db, $tourId);
}

function tournament_insert(mysqli $db, array $settings): int
{
    $sql = 'INSERT INTO tournaments (
                tour_title,
                tour_creationDate,
                tour_endDate,
                tour_type,
                format_code,
                status,
                registration_open_at,
                registration_close_at,
                group_count,
                advancers_per_group,
                team_count,
                is_public
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

    $stmt = $db->prepare($sql);
    $startDate = $settings['tour_startDate']->format('Y-m-d H:i:s');
    $endDate = $settings['tour_endDate']->format('Y-m-d H:i:s');
    $registrationOpen = $settings['registration_open_at']->format('Y-m-d H:i:s');
    $registrationClose = $settings['registration_close_at']->format('Y-m-d H:i:s');
    $groupCount = $settings['group_count'];
    $advancersPerGroup = $settings['advancers_per_group'];
    $teamCount = $settings['team_count'];
    $isPublic = (int) $settings['is_public'];

    $stmt->bind_param(
        'ssssssssiiii',
        $settings['tour_title'],
        $startDate,
        $endDate,
        $settings['tour_type'],
        $settings['format_code'],
        $settings['status'],
        $registrationOpen,
        $registrationClose,
        $groupCount,
        $advancersPerGroup,
        $teamCount,
        $isPublic
    );
    $stmt->execute();
    $tournamentId = (int) $db->insert_id;
    $stmt->close();

    return $tournamentId;
}

function tournament_attach_players(
    mysqli $db,
    int $tourId,
    array $playerIds,
    ?array $groupAssignments = null,
    string $defaultStatus = 'Active'
): void {
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

function tournament_insert_team_match(mysqli $db, array $matchData): int
{
    $sql = 'INSERT INTO team_matches (
                tour_id,
                round_number,
                match_date,
                match_time,
                team1_id,
                team2_id,
                match_status
            ) VALUES (?, ?, ?, ?, ?, ?, ?)';

    $stmt = $db->prepare($sql);
    $tourId = (int) $matchData['tour_id'];
    $roundNumber = (int) ($matchData['round_number'] ?? 1);
    $matchDate = $matchData['match_date'];
    $matchTime = $matchData['match_time'];
    $team1Id = $matchData['team1_id'] ?? null;
    $team2Id = $matchData['team2_id'] ?? null;
    $matchStatus = $matchData['match_status'] ?? 'Scheduled';

    $stmt->bind_param('iissiis', $tourId, $roundNumber, $matchDate, $matchTime, $team1Id, $team2Id, $matchStatus);
    $stmt->execute();
    $matchId = (int) $db->insert_id;
    $stmt->close();

    return $matchId;
}

function tournament_schedule_round_robin(
    mysqli $db,
    int $tourId,
    array $playerIds,
    string $startDate,
    ?int $groupNumber = null,
    int $roundNumber = 1,
    ?string $bracket = null
): void {
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
                'bracket' => $bracket,
                'match_status' => 'Scheduled',
            ]);
            $matchClock->modify('+30 minutes');
        }
    }
}

function tournament_schedule_team_round_robin(mysqli $db, int $tourId, array $teamIds, string $startDate): void
{
    $teams = array_values($teamIds);
    $matchClock = new DateTime($startDate . ' 18:00:00');

    for ($i = 0; $i < count($teams); $i++) {
        for ($j = $i + 1; $j < count($teams); $j++) {
            tournament_insert_team_match($db, [
                'tour_id' => $tourId,
                'round_number' => 1,
                'match_date' => $matchClock->format('Y-m-d'),
                'match_time' => $matchClock->format('H:i:s'),
                'team1_id' => $teams[$i],
                'team2_id' => $teams[$j],
                'match_status' => 'Scheduled',
            ]);
            $matchClock->modify('+45 minutes');
        }
    }
}

function tournament_schedule_group_cross_team_matches(mysqli $db, int $tourId, array $teamAssignments, string $startDate): void
{
    $teams = array_values($teamAssignments);
    if (count($teams) !== 2) {
        throw new InvalidArgumentException('Group tournaments currently require exactly two teams.');
    }

    $teamA = $teams[0];
    $teamB = $teams[1];
    $teamAPlayers = array_values(array_map('intval', $teamA['player_ids'] ?? []));
    $teamBPlayers = array_values(array_map('intval', $teamB['player_ids'] ?? []));
    $matchClock = new DateTime($startDate . ' 18:00:00');

    foreach ($teamAPlayers as $playerAId) {
        foreach ($teamBPlayers as $playerBId) {
            tournament_insert_match($db, [
                'tour_id' => $tourId,
                'round_number' => 1,
                'match_date' => $matchClock->format('Y-m-d'),
                'match_time' => $matchClock->format('H:i:s'),
                'player1_id' => $playerAId,
                'player2_id' => $playerBId,
                'bracket' => 'Team Stage',
                'match_status' => 'Scheduled',
            ]);
            $matchClock->modify('+30 minutes');
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

function tournament_entry_player(int $playerId): array
{
    return ['entry_type' => 'player', 'player_id' => $playerId];
}

function tournament_entry_match_winner(int $matchId): array
{
    return ['entry_type' => 'winner_match', 'match_id' => $matchId];
}

function tournament_entry_match_loser(int $matchId): array
{
    return ['entry_type' => 'loser_match', 'match_id' => $matchId];
}

function tournament_round_clock(string $startDate, int $dayOffset = 0): DateTime
{
    $clock = new DateTime($startDate . ' 18:00:00');
    if ($dayOffset > 0) {
        $clock->modify('+' . $dayOffset . ' day');
    }

    return $clock;
}

function tournament_link_entry_to_next_match(mysqli $db, array $entry, int $nextMatchId, int $position): void
{
    if (($entry['entry_type'] ?? '') === 'loser_match' && isset($entry['match_id'])) {
        $stmt = $db->prepare('UPDATE matches SET loser_next_match_id = ?, loser_position_in_next = ? WHERE match_id = ?');
        $matchId = (int) $entry['match_id'];
        $stmt->bind_param('iii', $nextMatchId, $position, $matchId);
        $stmt->execute();
        $stmt->close();
        return;
    }

    if (isset($entry['match_id'])) {
        $stmt = $db->prepare('UPDATE matches SET next_match_id = ?, position_in_next = ? WHERE match_id = ?');
        $matchId = (int) $entry['match_id'];
        $stmt->bind_param('iii', $nextMatchId, $position, $matchId);
        $stmt->execute();
        $stmt->close();
        return;
    }

    $field = $position === 2 ? 'player2_id' : 'player1_id';
    $stmt = $db->prepare('UPDATE matches SET ' . $field . ' = ? WHERE match_id = ?');
    $playerId = (int) $entry['player_id'];
    $stmt->bind_param('ii', $playerId, $nextMatchId);
    $stmt->execute();
    $stmt->close();
}

function tournament_build_entry_round(
    mysqli $db,
    int $tourId,
    array $entries,
    int $roundNumber,
    string $startDate,
    string $bracket,
    int $dayOffset = 0
): array {
    $nextEntries = [];
    $matchIds = [];
    $clock = tournament_round_clock($startDate, $dayOffset);

    for ($index = 0; $index < count($entries); $index += 2) {
        if (!isset($entries[$index + 1])) {
            $nextEntries[] = $entries[$index];
            continue;
        }

        $matchId = tournament_insert_match($db, [
            'tour_id' => $tourId,
            'round_number' => $roundNumber,
            'match_date' => $clock->format('Y-m-d'),
            'match_time' => $clock->format('H:i:s'),
            'match_status' => 'Scheduled',
            'bracket' => $bracket,
        ]);

        tournament_link_entry_to_next_match($db, $entries[$index], $matchId, 1);
        tournament_link_entry_to_next_match($db, $entries[$index + 1], $matchId, 2);

        $matchIds[] = $matchId;
        $nextEntries[] = tournament_entry_match_winner($matchId);
        $clock->modify('+30 minutes');
    }

    return [
        'matches' => $matchIds,
        'entries' => $nextEntries,
    ];
}

function tournament_build_zipped_entry_round(
    mysqli $db,
    int $tourId,
    array $leftEntries,
    array $rightEntries,
    int $roundNumber,
    string $startDate,
    string $bracket,
    int $dayOffset = 0
): array {
    $nextEntries = [];
    $matchIds = [];
    $clock = tournament_round_clock($startDate, $dayOffset);
    $total = max(count($leftEntries), count($rightEntries));

    for ($index = 0; $index < $total; $index++) {
        $leftEntry = $leftEntries[$index] ?? null;
        $rightEntry = $rightEntries[$index] ?? null;

        if ($leftEntry === null && $rightEntry === null) {
            continue;
        }

        if ($leftEntry === null) {
            $nextEntries[] = $rightEntry;
            continue;
        }

        if ($rightEntry === null) {
            $nextEntries[] = $leftEntry;
            continue;
        }

        $matchId = tournament_insert_match($db, [
            'tour_id' => $tourId,
            'round_number' => $roundNumber,
            'match_date' => $clock->format('Y-m-d'),
            'match_time' => $clock->format('H:i:s'),
            'match_status' => 'Scheduled',
            'bracket' => $bracket,
        ]);

        tournament_link_entry_to_next_match($db, $leftEntry, $matchId, 1);
        tournament_link_entry_to_next_match($db, $rightEntry, $matchId, 2);

        $matchIds[] = $matchId;
        $nextEntries[] = tournament_entry_match_winner($matchId);
        $clock->modify('+30 minutes');
    }

    return [
        'matches' => $matchIds,
        'entries' => $nextEntries,
    ];
}

function tournament_create_winners_bracket(
    mysqli $db,
    int $tourId,
    array $playerIds,
    string $startDate,
    string $bracket = 'Elimination'
): array {
    $players = array_values($playerIds);
    shuffle($players);
    $entries = array_map(static fn (int $playerId): array => tournament_entry_player($playerId), $players);
    $rounds = [];
    $roundNumber = 1;

    while (count($entries) > 1) {
        $result = tournament_build_entry_round($db, $tourId, $entries, $roundNumber, $startDate, $bracket, $roundNumber - 1);
        $rounds[$roundNumber] = $result['matches'];
        $entries = $result['entries'];
        $roundNumber++;
    }

    return [
        'rounds' => $rounds,
        'champion_entry' => $entries[0] ?? null,
    ];
}

function tournament_create_entry_bracket(
    mysqli $db,
    int $tourId,
    array $entries,
    string $startDate,
    string $bracket,
    int $startingDayOffset = 0,
    int $startingRoundNumber = 1
): array {
    $entries = array_values($entries);
    $rounds = [];
    $roundNumber = $startingRoundNumber;
    $dayOffset = $startingDayOffset;

    while (count($entries) > 1) {
        $result = tournament_build_entry_round($db, $tourId, $entries, $roundNumber, $startDate, $bracket, $dayOffset);
        $rounds[$roundNumber] = $result['matches'];
        $entries = $result['entries'];
        $roundNumber++;
        $dayOffset++;
    }

    $finalMatchId = null;
    if (!empty($rounds)) {
        $lastRound = end($rounds);
        if (!empty($lastRound)) {
            $finalMatchId = (int) end($lastRound);
        }
    }

    return [
        'rounds' => $rounds,
        'champion_entry' => $entries[0] ?? null,
        'final_match_id' => $finalMatchId,
        'day_offset' => $dayOffset,
    ];
}

function tournament_create_third_place_match(
    mysqli $db,
    int $tourId,
    array $winnerRounds,
    string $startDate
): ?int {
    if (count($winnerRounds) < 2) {
        return null;
    }

    ksort($winnerRounds);
    $roundNumbers = array_keys($winnerRounds);
    $semifinalRoundNumber = (int) $roundNumbers[count($roundNumbers) - 2];
    $semifinalMatchIds = array_values(array_map('intval', $winnerRounds[$semifinalRoundNumber] ?? []));
    if (count($semifinalMatchIds) < 2) {
        return null;
    }

    $clock = tournament_round_clock($startDate, max(0, count($winnerRounds) - 1));
    $matchId = tournament_insert_match($db, [
        'tour_id' => $tourId,
        'round_number' => 1,
        'match_date' => $clock->format('Y-m-d'),
        'match_time' => $clock->format('H:i:s'),
        'match_status' => 'Scheduled',
        'bracket' => 'Third Place Playoff',
    ]);

    tournament_link_entry_to_next_match($db, tournament_entry_match_loser($semifinalMatchIds[0]), $matchId, 1);
    tournament_link_entry_to_next_match($db, tournament_entry_match_loser($semifinalMatchIds[1]), $matchId, 2);

    return $matchId;
}

function tournament_create_elimination_bracket(mysqli $db, int $tourId, array $playerIds, string $startDate, ?string $bracket = 'Elimination'): void
{
    $winnersBracket = tournament_create_winners_bracket($db, $tourId, $playerIds, $startDate, (string) $bracket);
    tournament_create_third_place_match($db, $tourId, $winnersBracket['rounds'] ?? [], $startDate);
}

function tournament_create_double_elimination_bracket(mysqli $db, int $tourId, array $playerIds, string $startDate): void
{
    $players = array_values($playerIds);
    shuffle($players);
    $openingEntries = array_map(static fn (int $playerId): array => tournament_entry_player($playerId), $players);
    $openingRound = tournament_build_entry_round($db, $tourId, $openingEntries, 1, $startDate, 'Opening Round', 0);
    $openingMatchIds = array_values(array_map('intval', $openingRound['matches'] ?? []));
    $winnerEntries = array_values($openingRound['entries'] ?? []);
    $loserEntries = array_map(
        static fn (int $matchId): array => tournament_entry_match_loser($matchId),
        $openingMatchIds
    );

    if (empty($winnerEntries) || empty($loserEntries)) {
        return;
    }

    $winnersBracket = tournament_create_entry_bracket($db, $tourId, $winnerEntries, $startDate, 'Winners Bracket', 1);
    $losersBracket = tournament_create_entry_bracket($db, $tourId, $loserEntries, $startDate, 'Losers Bracket', 1);

    if (empty($winnersBracket['champion_entry']) || empty($losersBracket['champion_entry'])) {
        return;
    }

    $finalDayOffset = max(
        (int) ($winnersBracket['day_offset'] ?? 1),
        (int) ($losersBracket['day_offset'] ?? 1)
    );
    $finalClock = tournament_round_clock($startDate, $finalDayOffset);

    if (!empty($winnersBracket['final_match_id']) && !empty($losersBracket['final_match_id'])) {
        $thirdPlaceId = tournament_insert_match($db, [
            'tour_id' => $tourId,
            'round_number' => 1,
            'match_date' => $finalClock->format('Y-m-d'),
            'match_time' => $finalClock->format('H:i:s'),
            'match_status' => 'Scheduled',
            'bracket' => 'Third Place Playoff',
        ]);

        tournament_link_entry_to_next_match($db, tournament_entry_match_loser((int) $winnersBracket['final_match_id']), $thirdPlaceId, 1);
        tournament_link_entry_to_next_match($db, tournament_entry_match_loser((int) $losersBracket['final_match_id']), $thirdPlaceId, 2);
        $finalClock->modify('+45 minutes');
    }

    $grandFinalId = tournament_insert_match($db, [
        'tour_id' => $tourId,
        'round_number' => 1,
        'match_date' => $finalClock->format('Y-m-d'),
        'match_time' => $finalClock->format('H:i:s'),
        'match_status' => 'Scheduled',
        'bracket' => 'Grand Final',
    ]);

    tournament_link_entry_to_next_match($db, $winnersBracket['champion_entry'], $grandFinalId, 1);
    tournament_link_entry_to_next_match($db, $losersBracket['champion_entry'], $grandFinalId, 2);
}

function tournament_delete_structure(mysqli $db, int $tourId): void
{
    $deleteTeamMatches = $db->prepare('DELETE FROM team_matches WHERE tour_id = ?');
    $deleteTeamMatches->bind_param('i', $tourId);
    $deleteTeamMatches->execute();
    $deleteTeamMatches->close();

    $deleteMatches = $db->prepare('DELETE FROM matches WHERE tour_id = ?');
    $deleteMatches->bind_param('i', $tourId);
    $deleteMatches->execute();
    $deleteMatches->close();

    $deleteTeamPlayers = $db->prepare('DELETE FROM tournament_team_players WHERE tour_id = ?');
    $deleteTeamPlayers->bind_param('i', $tourId);
    $deleteTeamPlayers->execute();
    $deleteTeamPlayers->close();

    $deleteTeams = $db->prepare('DELETE FROM tournament_teams WHERE tour_id = ?');
    $deleteTeams->bind_param('i', $tourId);
    $deleteTeams->execute();
    $deleteTeams->close();

    $deleteStandings = $db->prepare('DELETE FROM tournament_standings WHERE tour_id = ?');
    $deleteStandings->bind_param('i', $tourId);
    $deleteStandings->execute();
    $deleteStandings->close();

    $clearMeta = $db->prepare(
        'UPDATE tournament_players
         SET group_number = NULL, final_rank = NULL, placement_label = NULL, eliminated_at = NULL
         WHERE tour_id = ?'
    );
    $clearMeta->bind_param('i', $tourId);
    $clearMeta->execute();
    $clearMeta->close();
}

function tournament_generate_team_assignments(array $playerIds, int $teamCount): array
{
    $shuffled = array_values($playerIds);
    shuffle($shuffled);

    $teams = [];
    for ($index = 0; $index < $teamCount; $index++) {
        $teams[$index + 1] = [];
    }

    $cursor = 1;
    foreach ($shuffled as $playerId) {
        $teams[$cursor][] = $playerId;
        $cursor++;
        if ($cursor > $teamCount) {
            $cursor = 1;
        }
    }

    return $teams;
}

function tournament_create_team_structure(
    mysqli $db,
    int $tourId,
    array $playerIds,
    string $startDate,
    int $teamCount,
    ?array $teamNames = null
): void {
    $teamAssignments = tournament_generate_team_assignments($playerIds, $teamCount);
    $createdTeams = [];

    $insertTeam = $db->prepare('INSERT INTO tournament_teams (tour_id, team_name, team_seed) VALUES (?, ?, ?)');
    $insertTeamPlayer = $db->prepare('INSERT INTO tournament_team_players (tour_id, team_id, player_id) VALUES (?, ?, ?)');

    $seed = 1;
    foreach ($teamAssignments as $slot => $teamPlayerIds) {
        $teamName = trim((string) ($teamNames[$slot - 1] ?? ''));
        if ($teamName === '') {
            $teamName = 'Team ' . $slot;
        }

        $insertTeam->bind_param('isi', $tourId, $teamName, $seed);
        $insertTeam->execute();
        $teamId = (int) $db->insert_id;
        $createdTeams[] = [
            'team_id' => $teamId,
            'team_name' => $teamName,
            'player_ids' => array_values(array_map('intval', $teamPlayerIds)),
        ];

        foreach ($teamPlayerIds as $playerId) {
            $insertTeamPlayer->bind_param('iii', $tourId, $teamId, $playerId);
            $insertTeamPlayer->execute();
        }
        $seed++;
    }

    $insertTeam->close();
    $insertTeamPlayer->close();

    tournament_schedule_group_cross_team_matches($db, $tourId, $createdTeams, $startDate);
}

function tournament_rebuild_structure(
    mysqli $db,
    int $tourId,
    string $tourType,
    array $playerIds,
    string $startDate,
    ?int $groupCount = null,
    ?int $teamCount = null,
    ?array $teamNames = null
): void {
    tournament_delete_structure($db, $tourId);

    if ($tourType === 'Round Robin') {
        tournament_attach_players($db, $tourId, $playerIds, null, 'Active');
        tournament_initialize_standings($db, $tourId, $playerIds);
        tournament_schedule_round_robin($db, $tourId, $playerIds, $startDate, null, 1, 'Round Robin');
        return;
    }

    if ($tourType === 'League') {
        $groupAssignments = tournament_assign_groups($playerIds, (int) $groupCount);
        tournament_attach_players($db, $tourId, $playerIds, $groupAssignments, 'Active');
        tournament_initialize_standings($db, $tourId, $playerIds);
        foreach (tournament_grouped_player_ids($groupAssignments) as $groupNumber => $groupPlayerIds) {
            tournament_schedule_round_robin($db, $tourId, $groupPlayerIds, $startDate, (int) $groupNumber, 1, 'Group Stage');
        }
        return;
    }

    if ($tourType === 'Group') {
        tournament_attach_players($db, $tourId, $playerIds, null, 'Active');
        tournament_create_team_structure($db, $tourId, $playerIds, $startDate, (int) $teamCount, $teamNames);
        return;
    }

    tournament_attach_players($db, $tourId, $playerIds, null, 'Active');
    if ($tourType === 'Double Elimination') {
        tournament_create_double_elimination_bracket($db, $tourId, $playerIds, $startDate);
        return;
    }

    tournament_create_elimination_bracket($db, $tourId, $playerIds, $startDate, 'Elimination');
}

function tournament_create_with_players(mysqli $db, array $settings, array $playerIds): int
{
    $playerIds = tournament_unique_player_ids($playerIds);
    $normalized = tournament_normalize_settings($settings, count($playerIds));
    $tourId = tournament_insert($db, $normalized);
    $selectedPlayersStatus = tournament_sanitize_player_status(
        (string) ($settings['selectedPlayersStatus'] ?? $settings['selected_players_status'] ?? 'Registered'),
        'Registered'
    );

    if (!empty($playerIds)) {
        tournament_attach_players($db, $tourId, $playerIds, null, $selectedPlayersStatus);
    }

    $shouldGenerateImmediately = !in_array($normalized['status'], ['draft', 'registration_open'], true)
        && $selectedPlayersStatus === 'Active'
        && count($playerIds) >= 2;

    if ($shouldGenerateImmediately) {
        tournament_validate_structure_settings($normalized, count($playerIds));
        tournament_rebuild_structure(
            $db,
            $tourId,
            $normalized['tour_type'],
            $playerIds,
            $normalized['tour_startDate']->format('Y-m-d'),
            $normalized['group_count'],
            $normalized['team_count'],
            isset($settings['team_names']) && is_array($settings['team_names']) ? $settings['team_names'] : null
        );
    }

    tournament_refresh_lifecycle($db, $tourId);

    return $tourId;
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

function tournament_fetch_player_ids(mysqli $db, int $tourId): array
{
    $stmt = $db->prepare('SELECT plr_id FROM tournament_players WHERE tour_id = ? AND player_status = ? ORDER BY plr_id');
    $active = 'Active';
    $stmt->bind_param('is', $tourId, $active);
    $stmt->execute();
    $result = $stmt->get_result();
    $playerIds = [];
    while ($row = $result->fetch_assoc()) {
        $playerIds[] = (int) $row['plr_id'];
    }
    $stmt->close();

    return $playerIds;
}

function tournament_has_structure(mysqli $db, int $tourId): bool
{
    $stmt = $db->prepare(
        "SELECT (
            (SELECT COUNT(*) FROM matches WHERE tour_id = ?) +
            (SELECT COUNT(*) FROM team_matches WHERE tour_id = ?) +
            (SELECT COUNT(*) FROM tournament_teams WHERE tour_id = ?)
        ) AS structure_count"
    );
    $stmt->bind_param('iii', $tourId, $tourId, $tourId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return ((int) ($row['structure_count'] ?? 0)) > 0;
}

function tournament_has_completed_matches(mysqli $db, int $tourId): bool
{
    $stmt = $db->prepare(
        "SELECT (
            (SELECT COUNT(*) FROM matches WHERE tour_id = ? AND match_status = 'Completed') +
            (SELECT COUNT(*) FROM team_matches WHERE tour_id = ? AND match_status = 'Completed')
        ) AS completed_count"
    );
    $stmt->bind_param('ii', $tourId, $tourId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return ((int) ($row['completed_count'] ?? 0)) > 0;
}

function tournament_assert_mutable(mysqli $db, int $tourId): void
{
    $tournament = tournament_fetch_settings($db, $tourId);
    if (!empty($tournament['archived_at']) || $tournament['status'] === 'archived') {
        throw new RuntimeException('Archived tournaments are read-only.');
    }
}

function tournament_update(mysqli $db, int $tourId, array $payload): void
{
    tournament_assert_mutable($db, $tourId);
    $tournament = tournament_fetch_settings($db, $tourId);

    $playerRowsStmt = $db->prepare('SELECT plr_id, player_status FROM tournament_players WHERE tour_id = ?');
    $playerRowsStmt->bind_param('i', $tourId);
    $playerRowsStmt->execute();
    $playerRows = $playerRowsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $playerRowsStmt->close();

    $currentStatuses = [];
    foreach ($playerRows as $row) {
        $currentStatuses[(int) $row['plr_id']] = $row['player_status'];
    }

    $newPlayerIds = tournament_unique_player_ids($payload['new_players'] ?? []);
    $removePlayerIds = tournament_unique_player_ids($payload['remove_players'] ?? []);

    $finalStatuses = $currentStatuses;
    if (isset($payload['player_status']) && is_array($payload['player_status'])) {
        foreach ($payload['player_status'] as $playerId => $status) {
            $playerId = (int) $playerId;
            if (!array_key_exists($playerId, $finalStatuses)) {
                continue;
            }
            $finalStatuses[$playerId] = tournament_sanitize_player_status((string) $status, $finalStatuses[$playerId]);
        }
    }

    foreach ($removePlayerIds as $playerId) {
        unset($finalStatuses[$playerId]);
    }

    $newPlayersStatus = tournament_sanitize_player_status((string) ($payload['new_players_status'] ?? 'Registered'), 'Registered');
    foreach ($newPlayerIds as $playerId) {
        if (!isset($finalStatuses[$playerId])) {
            $finalStatuses[$playerId] = $newPlayersStatus;
        }
    }

    $settingsInput = [
        'tour_type' => $tournament['tour_type'],
        'tour_title' => $payload['tour_title'] ?? $tournament['tour_title'],
        'tour_startDate' => $payload['tour_startDate'] ?? date('Y-m-d', strtotime($tournament['tour_creationDate'])),
        'tour_endDate' => $payload['tour_endDate'] ?? date('Y-m-d', strtotime($tournament['tour_endDate'])),
        'registration_open_at' => $payload['registration_open_at'] ?? date('Y-m-d', strtotime($tournament['registration_open_at'] ?: $tournament['tour_creationDate'])),
        'registration_close_at' => $payload['registration_close_at'] ?? date('Y-m-d', strtotime($tournament['registration_close_at'] ?: $tournament['tour_creationDate'])),
        'group_count' => $payload['group_count'] ?? $tournament['group_count'],
        'advancers_per_group' => $payload['advancers_per_group'] ?? $tournament['advancers_per_group'],
        'team_count' => $payload['team_count'] ?? $tournament['team_count'],
        'is_public' => $payload['is_public'] ?? $tournament['is_public'],
    ];
    $normalized = tournament_normalize_settings($settingsInput, count($finalStatuses));

    $updateTournament = $db->prepare(
        'UPDATE tournaments
         SET tour_title = ?,
             tour_creationDate = ?,
             tour_endDate = ?,
             status = ?,
             registration_open_at = ?,
             registration_close_at = ?,
             group_count = ?,
             advancers_per_group = ?,
             team_count = ?,
             is_public = ?,
             archived_at = CASE WHEN ? = 1 THEN COALESCE(archived_at, NOW()) ELSE NULL END
         WHERE tour_id = ?'
    );
    $startDate = $normalized['tour_startDate']->format('Y-m-d H:i:s');
    $endDate = $normalized['tour_endDate']->format('Y-m-d H:i:s');
    $registrationOpen = $normalized['registration_open_at']->format('Y-m-d H:i:s');
    $registrationClose = $normalized['registration_close_at']->format('Y-m-d H:i:s');
    $archiveFlag = isset($payload['archive']) && (int) $payload['archive'] === 1 ? 1 : 0;
    $isPublic = (int) $normalized['is_public'];

    $updateTournament->bind_param(
        'ssssssiiiiii',
        $normalized['tour_title'],
        $startDate,
        $endDate,
        $normalized['status'],
        $registrationOpen,
        $registrationClose,
        $normalized['group_count'],
        $normalized['advancers_per_group'],
        $normalized['team_count'],
        $isPublic,
        $archiveFlag,
        $tourId
    );
    $updateTournament->execute();
    $updateTournament->close();

    foreach ($removePlayerIds as $playerId) {
        $deleteTp = $db->prepare('DELETE FROM tournament_players WHERE tour_id = ? AND plr_id = ?');
        $deleteTp->bind_param('ii', $tourId, $playerId);
        $deleteTp->execute();
        $deleteTp->close();
    }

    if (!empty($newPlayerIds)) {
        tournament_attach_players($db, $tourId, $newPlayerIds, null, $newPlayersStatus);
    }

    $statusStmt = $db->prepare('UPDATE tournament_players SET player_status = ? WHERE tour_id = ? AND plr_id = ?');
    foreach ($finalStatuses as $playerId => $status) {
        $statusStmt->bind_param('sii', $status, $tourId, $playerId);
        $statusStmt->execute();
    }
    $statusStmt->close();

    tournament_refresh_lifecycle($db, $tourId);
}

function tournament_generate_structure(mysqli $db, int $tourId, ?string $startDate = null): int
{
    tournament_assert_mutable($db, $tourId);
    $tournament = tournament_refresh_lifecycle($db, $tourId);

    if (in_array($tournament['status'], ['draft', 'registration_open'], true)) {
        throw new RuntimeException('Tournament registration must be closed before generating the structure.');
    }

    if (tournament_structure_is_locked($db, $tourId)) {
        throw new RuntimeException('Cannot rebuild the tournament structure after results have been recorded.');
    }

    $playerRowsStmt = $db->prepare('SELECT plr_id, player_status FROM tournament_players WHERE tour_id = ? ORDER BY plr_id');
    $playerRowsStmt->bind_param('i', $tourId);
    $playerRowsStmt->execute();
    $playerRows = $playerRowsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $playerRowsStmt->close();

    $competitionPlayerIds = [];
    foreach ($playerRows as $row) {
        if (($row['player_status'] ?? '') === 'Withdrawn') {
            continue;
        }
        $competitionPlayerIds[] = (int) $row['plr_id'];
    }

    $competitionPlayerIds = tournament_unique_player_ids($competitionPlayerIds);
    $settingsInput = [
        'tour_type' => $tournament['tour_type'],
        'tour_title' => $tournament['tour_title'],
        'tour_startDate' => $startDate ?: date('Y-m-d', strtotime($tournament['tour_creationDate'])),
        'tour_endDate' => date('Y-m-d', strtotime($tournament['tour_endDate'])),
        'registration_open_at' => date('Y-m-d', strtotime($tournament['registration_open_at'] ?: $tournament['tour_creationDate'])),
        'registration_close_at' => date('Y-m-d', strtotime($tournament['registration_close_at'] ?: $tournament['tour_creationDate'])),
        'group_count' => $tournament['group_count'],
        'advancers_per_group' => $tournament['advancers_per_group'],
        'team_count' => $tournament['team_count'],
        'is_public' => $tournament['is_public'],
    ];
    $normalized = tournament_normalize_settings($settingsInput, count($competitionPlayerIds));
    tournament_validate_structure_settings($normalized, count($competitionPlayerIds));

    $activate = $db->prepare(
        "UPDATE tournament_players
         SET player_status = 'Active'
         WHERE tour_id = ? AND player_status <> 'Withdrawn'"
    );
    $activate->bind_param('i', $tourId);
    $activate->execute();
    $activate->close();

    tournament_rebuild_structure(
        $db,
        $tourId,
        $tournament['tour_type'],
        $competitionPlayerIds,
        $normalized['tour_startDate']->format('Y-m-d'),
        $normalized['group_count'],
        $normalized['team_count']
    );

    tournament_refresh_lifecycle($db, $tourId);

    return count($competitionPlayerIds);
}

function tournament_force_close_registration(mysqli $db, int $tourId, ?DateTime $closedAt = null): array
{
    tournament_assert_mutable($db, $tourId);
    $tournament = tournament_fetch_settings($db, $tourId);

    if (in_array($tournament['status'], ['completed', 'archived'], true)) {
        throw new RuntimeException('Completed tournaments cannot be restarted from this action.');
    }

    $closedAt = $closedAt ?? new DateTime();
    $registrationOpenAt = new DateTime($tournament['registration_open_at'] ?: $tournament['tour_creationDate']);
    if ($registrationOpenAt > $closedAt) {
        $registrationOpenAt = clone $closedAt;
    }

    $update = $db->prepare(
        'UPDATE tournaments
         SET registration_open_at = ?, registration_close_at = ?
         WHERE tour_id = ?'
    );
    $registrationOpen = $registrationOpenAt->format('Y-m-d H:i:s');
    $registrationClose = $closedAt->format('Y-m-d H:i:s');
    $update->bind_param('ssi', $registrationOpen, $registrationClose, $tourId);
    $update->execute();
    $update->close();

    return tournament_refresh_lifecycle($db, $tourId);
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

function tournament_fetch_team_match(mysqli $db, int $teamMatchId): array
{
    $stmt = $db->prepare(
        'SELECT tm.*, t.tour_type
         FROM team_matches tm
         JOIN tournaments t ON t.tour_id = tm.tour_id
         WHERE tm.team_match_id = ?'
    );
    $stmt->bind_param('i', $teamMatchId);
    $stmt->execute();
    $match = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$match) {
        throw new RuntimeException('Team match not found.');
    }

    return $match;
}

function tournament_rebuild_standings(mysqli $db, int $tourId): void
{
    $tournament = tournament_fetch_settings($db, $tourId);
    if (!in_array($tournament['tour_type'], ['Round Robin', 'League'], true)) {
        return;
    }

    $playerIdsStmt = $db->prepare('SELECT plr_id FROM tournament_players WHERE tour_id = ? AND player_status = ?');
    $active = 'Active';
    $playerIdsStmt->bind_param('is', $tourId, $active);
    $playerIdsStmt->execute();
    $playerRows = $playerIdsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $playerIdsStmt->close();

    $players = [];
    foreach ($playerRows as $row) {
        $players[(int) $row['plr_id']] = [
            'matches_played' => 0,
            'matches_won' => 0,
            'matches_lost' => 0,
            'matches_drawn' => 0,
            'points' => 0,
            'leg_difference' => 0,
        ];
    }

    if ($tournament['tour_type'] === 'Round Robin') {
        $sql = "SELECT player1_id, player2_id, player1_score, player2_score, winner_id
                FROM matches
                WHERE tour_id = ? AND match_status = 'Completed'";
    } else {
        $sql = "SELECT player1_id, player2_id, player1_score, player2_score, winner_id
                FROM matches
                WHERE tour_id = ? AND group_number IS NOT NULL AND match_status = 'Completed'";
    }

    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $tourId);
    $stmt->execute();
    $matches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($matches as $match) {
        $player1Id = (int) $match['player1_id'];
        $player2Id = (int) $match['player2_id'];
        if (!isset($players[$player1Id], $players[$player2Id])) {
            continue;
        }

        $player1Score = (int) $match['player1_score'];
        $player2Score = (int) $match['player2_score'];
        $winnerId = $match['winner_id'] !== null ? (int) $match['winner_id'] : null;
        $isDraw = $winnerId === null;

        $players[$player1Id]['matches_played']++;
        $players[$player2Id]['matches_played']++;
        $players[$player1Id]['leg_difference'] += ($player1Score - $player2Score);
        $players[$player2Id]['leg_difference'] += ($player2Score - $player1Score);

        if ($isDraw) {
            $players[$player1Id]['matches_drawn']++;
            $players[$player2Id]['matches_drawn']++;
            $players[$player1Id]['points']++;
            $players[$player2Id]['points']++;
            continue;
        }

        if ($winnerId === $player1Id) {
            $players[$player1Id]['matches_won']++;
            $players[$player2Id]['matches_lost']++;
            $players[$player1Id]['points'] += 3;
        } else {
            $players[$player2Id]['matches_won']++;
            $players[$player1Id]['matches_lost']++;
            $players[$player2Id]['points'] += 3;
        }
    }

    $deleteStmt = $db->prepare('DELETE FROM tournament_standings WHERE tour_id = ?');
    $deleteStmt->bind_param('i', $tourId);
    $deleteStmt->execute();
    $deleteStmt->close();

    $insertStmt = $db->prepare(
        'INSERT INTO tournament_standings (
            tour_id,
            player_id,
            matches_played,
            matches_won,
            matches_lost,
            matches_drawn,
            points,
            leg_difference
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );

    foreach ($players as $playerId => $standing) {
        $insertStmt->bind_param(
            'iiiiiiii',
            $tourId,
            $playerId,
            $standing['matches_played'],
            $standing['matches_won'],
            $standing['matches_lost'],
            $standing['matches_drawn'],
            $standing['points'],
            $standing['leg_difference']
        );
        $insertStmt->execute();
    }

    $insertStmt->close();
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

function tournament_group_has_individual_matches(mysqli $db, int $tourId): bool
{
    $stmt = $db->prepare('SELECT COUNT(*) AS match_count FROM matches WHERE tour_id = ?');
    $stmt->bind_param('i', $tourId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return ((int) ($row['match_count'] ?? 0)) > 0;
}

function tournament_group_player_team_map(mysqli $db, int $tourId): array
{
    $stmt = $db->prepare(
        'SELECT ttp.player_id, ttp.team_id, tt.team_name
         FROM tournament_team_players ttp
         JOIN tournament_teams tt ON tt.team_id = ttp.team_id
         WHERE ttp.tour_id = ?'
    );
    $stmt->bind_param('i', $tourId);
    $stmt->execute();
    $result = $stmt->get_result();

    $map = [];
    while ($row = $result->fetch_assoc()) {
        $map[(int) $row['player_id']] = [
            'team_id' => (int) $row['team_id'],
            'team_name' => (string) $row['team_name'],
        ];
    }
    $stmt->close();

    return $map;
}

function tournament_team_standings(mysqli $db, int $tourId): array
{
    $teamsStmt = $db->prepare(
        "SELECT
            tt.team_id,
            tt.team_name,
            COUNT(ttp.player_id) AS roster_size
         FROM tournament_teams tt
         LEFT JOIN tournament_team_players ttp ON ttp.team_id = tt.team_id
         WHERE tt.tour_id = ?
         GROUP BY tt.team_id, tt.team_name
         ORDER BY tt.team_seed, tt.team_name"
    );
    $teamsStmt->bind_param('i', $tourId);
    $teamsStmt->execute();
    $teamsResult = $teamsStmt->get_result();

    $standingsByTeam = [];
    while ($row = $teamsResult->fetch_assoc()) {
        $teamId = (int) $row['team_id'];
        $standingsByTeam[$teamId] = [
            'team_id' => $teamId,
            'team_name' => $row['team_name'],
            'roster_size' => (int) $row['roster_size'],
            'matches_played' => 0,
            'matches_won' => 0,
            'matches_lost' => 0,
            'matches_drawn' => 0,
            'points' => 0,
            'leg_difference' => 0,
        ];
    }
    $teamsStmt->close();

    if (tournament_group_has_individual_matches($db, $tourId)) {
        $playerTeamMap = tournament_group_player_team_map($db, $tourId);
        $matchesStmt = $db->prepare(
            "SELECT
                player1_id,
                player2_id,
                player1_score,
                player2_score,
                winner_id,
                match_status
             FROM matches
             WHERE tour_id = ?"
        );
        $matchesStmt->bind_param('i', $tourId);
        $matchesStmt->execute();
        $matchesResult = $matchesStmt->get_result();

        while ($match = $matchesResult->fetch_assoc()) {
            if (($match['match_status'] ?? '') !== 'Completed') {
                continue;
            }

            $player1Id = (int) ($match['player1_id'] ?? 0);
            $player2Id = (int) ($match['player2_id'] ?? 0);
            if (!isset($playerTeamMap[$player1Id], $playerTeamMap[$player2Id])) {
                continue;
            }

            $team1Id = (int) $playerTeamMap[$player1Id]['team_id'];
            $team2Id = (int) $playerTeamMap[$player2Id]['team_id'];
            if ($team1Id === $team2Id || !isset($standingsByTeam[$team1Id], $standingsByTeam[$team2Id])) {
                continue;
            }

            $team1Score = (int) ($match['player1_score'] ?? 0);
            $team2Score = (int) ($match['player2_score'] ?? 0);
            $winnerPlayerId = isset($match['winner_id']) ? (int) $match['winner_id'] : null;
            $winnerTeamId = $winnerPlayerId !== null && isset($playerTeamMap[$winnerPlayerId])
                ? (int) $playerTeamMap[$winnerPlayerId]['team_id']
                : null;

            $standingsByTeam[$team1Id]['matches_played']++;
            $standingsByTeam[$team2Id]['matches_played']++;
            $standingsByTeam[$team1Id]['leg_difference'] += $team1Score - $team2Score;
            $standingsByTeam[$team2Id]['leg_difference'] += $team2Score - $team1Score;

            if ($winnerTeamId === null) {
                $standingsByTeam[$team1Id]['matches_drawn']++;
                $standingsByTeam[$team2Id]['matches_drawn']++;
                $standingsByTeam[$team1Id]['points']++;
                $standingsByTeam[$team2Id]['points']++;
                continue;
            }

            $loserTeamId = $winnerTeamId === $team1Id ? $team2Id : $team1Id;
            $standingsByTeam[$winnerTeamId]['matches_won']++;
            $standingsByTeam[$winnerTeamId]['points'] += 3;
            $standingsByTeam[$loserTeamId]['matches_lost']++;
        }
        $matchesStmt->close();
    } else {
        $matchesStmt = $db->prepare(
            "SELECT
                team1_id,
                team2_id,
                team1_score,
                team2_score,
                winner_team_id,
                match_status
             FROM team_matches
             WHERE tour_id = ?"
        );
        $matchesStmt->bind_param('i', $tourId);
        $matchesStmt->execute();
        $matchesResult = $matchesStmt->get_result();

        while ($match = $matchesResult->fetch_assoc()) {
            if (($match['match_status'] ?? '') !== 'Completed') {
                continue;
            }

            $team1Id = (int) $match['team1_id'];
            $team2Id = (int) $match['team2_id'];
            $team1Score = (int) ($match['team1_score'] ?? 0);
            $team2Score = (int) ($match['team2_score'] ?? 0);
            $winnerTeamId = isset($match['winner_team_id']) ? (int) $match['winner_team_id'] : null;

            if (!isset($standingsByTeam[$team1Id]) || !isset($standingsByTeam[$team2Id])) {
                continue;
            }

            $standingsByTeam[$team1Id]['matches_played']++;
            $standingsByTeam[$team2Id]['matches_played']++;
            $standingsByTeam[$team1Id]['leg_difference'] += $team1Score - $team2Score;
            $standingsByTeam[$team2Id]['leg_difference'] += $team2Score - $team1Score;

            if ($winnerTeamId === null) {
                $standingsByTeam[$team1Id]['matches_drawn']++;
                $standingsByTeam[$team2Id]['matches_drawn']++;
                $standingsByTeam[$team1Id]['points']++;
                $standingsByTeam[$team2Id]['points']++;
                continue;
            }

            $loserTeamId = $winnerTeamId === $team1Id ? $team2Id : $team1Id;
            $standingsByTeam[$winnerTeamId]['matches_won']++;
            $standingsByTeam[$winnerTeamId]['points'] += 3;
            $standingsByTeam[$loserTeamId]['matches_lost']++;
        }
        $matchesStmt->close();
    }

    $standings = array_values($standingsByTeam);
    usort($standings, static function (array $left, array $right): int {
        $pointOrder = $right['points'] <=> $left['points'];
        if ($pointOrder !== 0) {
            return $pointOrder;
        }

        $legOrder = $right['leg_difference'] <=> $left['leg_difference'];
        if ($legOrder !== 0) {
            return $legOrder;
        }

        $winOrder = $right['matches_won'] <=> $left['matches_won'];
        if ($winOrder !== 0) {
            return $winOrder;
        }

        return strcmp((string) $left['team_name'], (string) $right['team_name']);
    });

    return $standings;
}

function tournament_fetch_team_rosters(mysqli $db, int $tourId): array
{
    $sql = "SELECT
                tt.team_id,
                tt.team_name,
                p.plr_idNum,
                p.plr_name,
                p.plr_surname
            FROM tournament_teams tt
            LEFT JOIN tournament_team_players ttp ON ttp.team_id = tt.team_id
            LEFT JOIN players p ON p.plr_idNum = ttp.player_id
            WHERE tt.tour_id = ?
            ORDER BY tt.team_seed, p.plr_surname, p.plr_name";

    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $tourId);
    $stmt->execute();
    $result = $stmt->get_result();

    $teams = [];
    while ($row = $result->fetch_assoc()) {
        $teamId = (int) $row['team_id'];
        if (!isset($teams[$teamId])) {
            $teams[$teamId] = [
                'team_id' => $teamId,
                'team_name' => $row['team_name'],
                'players' => [],
            ];
        }
        if (!empty($row['plr_idNum'])) {
            $teams[$teamId]['players'][] = [
                'plr_idNum' => (int) $row['plr_idNum'],
                'plr_name' => $row['plr_name'],
                'plr_surname' => $row['plr_surname'],
            ];
        }
    }

    $stmt->close();

    return array_values($teams);
}

function tournament_fetch_team_matches(mysqli $db, int $tourId): array
{
    $sql = "SELECT
                tm.*,
                t1.team_name AS team1_name,
                t2.team_name AS team2_name
            FROM team_matches tm
            LEFT JOIN tournament_teams t1 ON t1.team_id = tm.team1_id
            LEFT JOIN tournament_teams t2 ON t2.team_id = tm.team2_id
            WHERE tm.tour_id = ?
            ORDER BY tm.round_number, tm.match_date, tm.match_time, tm.team_match_id";

    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $tourId);
    $stmt->execute();
    $matches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $matches;
}

function tournament_promote_league_groups(mysqli $db, int $tourId): int
{
    $tournament = tournament_fetch_settings($db, $tourId);
    if ($tournament['tour_type'] !== 'League') {
        throw new RuntimeException('Tournament is not a league tournament.');
    }

    $unfinished = $db->prepare(
        "SELECT COUNT(*) AS pending_matches
         FROM matches
         WHERE tour_id = ? AND group_number IS NOT NULL AND match_status <> 'Completed'"
    );
    $unfinished->bind_param('i', $tourId);
    $unfinished->execute();
    $pending = $unfinished->get_result()->fetch_assoc();
    $unfinished->close();

    if ((int) ($pending['pending_matches'] ?? 0) > 0) {
        throw new RuntimeException('Finish all group-stage matches before creating the knockout stage.');
    }

    $existingKnockout = $db->prepare(
        "SELECT COUNT(*) AS knockout_count
         FROM matches
         WHERE tour_id = ? AND group_number IS NULL"
    );
    $existingKnockout->bind_param('i', $tourId);
    $existingKnockout->execute();
    $knockoutCount = $existingKnockout->get_result()->fetch_assoc();
    $existingKnockout->close();

    if ((int) ($knockoutCount['knockout_count'] ?? 0) > 0) {
        return 0;
    }

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
        throw new RuntimeException('Not enough players qualified from the league groups.');
    }

    $startDate = new DateTime($tournament['tour_endDate']);
    $startDate->modify('-1 day');
    tournament_create_elimination_bracket($db, $tourId, $advancers, $startDate->format('Y-m-d'), 'Knockout');

    return count($advancers);
}

function tournament_promote_groups(mysqli $db, int $tourId): int
{
    return tournament_promote_league_groups($db, $tourId);
}

function tournament_propagate_winner(mysqli $db, array $match, int $winnerId): void
{
    if (!empty($match['next_match_id'])) {
        $field = ((int) $match['position_in_next'] === 2) ? 'player2_id' : 'player1_id';
        $stmt = $db->prepare('UPDATE matches SET ' . $field . ' = ? WHERE match_id = ?');
        $nextMatchId = (int) $match['next_match_id'];
        $stmt->bind_param('ii', $winnerId, $nextMatchId);
        $stmt->execute();
        $stmt->close();
    }

    if (!empty($match['loser_next_match_id']) && !empty($match['player1_id']) && !empty($match['player2_id'])) {
        $loserId = $winnerId === (int) $match['player1_id'] ? (int) $match['player2_id'] : (int) $match['player1_id'];
        $field = ((int) $match['loser_position_in_next'] === 2) ? 'player2_id' : 'player1_id';
        $stmt = $db->prepare('UPDATE matches SET ' . $field . ' = ? WHERE match_id = ?');
        $loserNextMatchId = (int) $match['loser_next_match_id'];
        $stmt->bind_param('ii', $loserId, $loserNextMatchId);
        $stmt->execute();
        $stmt->close();
    }
}

function tournament_match_loser_id(array $match): ?int
{
    $winnerId = isset($match['winner_id']) ? (int) $match['winner_id'] : 0;
    $player1Id = isset($match['player1_id']) ? (int) $match['player1_id'] : 0;
    $player2Id = isset($match['player2_id']) ? (int) $match['player2_id'] : 0;

    if ($winnerId <= 0 || $player1Id <= 0 || $player2Id <= 0) {
        return null;
    }

    return $winnerId === $player1Id ? $player2Id : $player1Id;
}

function tournament_apply_player_placement(
    mysqli $db,
    int $tourId,
    int $playerId,
    ?int $rank,
    ?string $label,
    ?string $eliminatedAt = null
): void {
    $stmt = $db->prepare(
        'UPDATE tournament_players
         SET final_rank = ?, placement_label = ?, eliminated_at = ?
         WHERE tour_id = ? AND plr_id = ?'
    );
    $stmt->bind_param('issii', $rank, $label, $eliminatedAt, $tourId, $playerId);
    $stmt->execute();
    $stmt->close();
}

function tournament_elimination_round_placement_label(int $roundPosition, int $roundCount): string
{
    $roundLabel = tournament_round_title($roundPosition, $roundCount);

    if ($roundLabel === 'Semifinal') {
        return 'Semifinalist';
    }

    if ($roundLabel === 'Quarterfinal') {
        return 'Quarterfinalist';
    }

    return 'Eliminated in ' . $roundLabel;
}

function tournament_sync_single_elimination_placements(mysqli $db, int $tourId): void
{
    $stmt = $db->prepare(
        "SELECT
            match_id,
            round_number,
            bracket,
            match_date,
            match_time,
            player1_id,
            player2_id,
            winner_id,
            match_status
         FROM matches
         WHERE tour_id = ? AND group_number IS NULL
         ORDER BY round_number, match_id"
    );
    $stmt->bind_param('i', $tourId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $mainBracketMatches = [];
    $thirdPlaceMatch = null;
    foreach ($rows as $row) {
        $bracketLabel = trim((string) ($row['bracket'] ?? ''));
        if ($bracketLabel === 'Third Place Playoff') {
            $thirdPlaceMatch = $row;
            continue;
        }

        if ($bracketLabel === 'Grand Final' || $bracketLabel === 'Losers Bracket') {
            continue;
        }

        $mainBracketMatches[] = $row;
    }

    if (empty($mainBracketMatches)) {
        return;
    }

    $roundCount = 0;
    foreach ($mainBracketMatches as $match) {
        $roundCount = max($roundCount, (int) ($match['round_number'] ?? 0));
    }

    $assigned = [];
    $assign = static function (int $playerId, ?int $rank, string $label, ?string $eliminatedAt = null) use ($db, $tourId, &$assigned): void {
        if ($playerId <= 0 || isset($assigned[$playerId])) {
            return;
        }

        $assigned[$playerId] = true;
        tournament_apply_player_placement($db, $tourId, $playerId, $rank, $label, $eliminatedAt);
    };

    $finalMatch = null;
    foreach ($mainBracketMatches as $match) {
        if ((int) ($match['round_number'] ?? 0) === $roundCount) {
            $finalMatch = $match;
        }
    }

    if ($finalMatch && ($finalMatch['match_status'] ?? '') === 'Completed') {
        $winnerId = (int) ($finalMatch['winner_id'] ?? 0);
        $loserId = tournament_match_loser_id($finalMatch) ?? 0;
        $eliminatedAt = !empty($finalMatch['match_date'])
            ? trim((string) $finalMatch['match_date'] . ' ' . ($finalMatch['match_time'] ?? '00:00:00'))
            : null;

        $assign($winnerId, 1, 'Champion');
        $assign($loserId, 2, 'Runner-up', $eliminatedAt);
    }

    if ($thirdPlaceMatch && ($thirdPlaceMatch['match_status'] ?? '') === 'Completed') {
        $winnerId = (int) ($thirdPlaceMatch['winner_id'] ?? 0);
        $loserId = tournament_match_loser_id($thirdPlaceMatch) ?? 0;
        $eliminatedAt = !empty($thirdPlaceMatch['match_date'])
            ? trim((string) $thirdPlaceMatch['match_date'] . ' ' . ($thirdPlaceMatch['match_time'] ?? '00:00:00'))
            : null;

        $assign($winnerId, 3, '3rd Place');
        $assign($loserId, 4, '4th Place', $eliminatedAt);
    }

    foreach ($mainBracketMatches as $match) {
        if (($match['match_status'] ?? '') !== 'Completed') {
            continue;
        }

        $loserId = tournament_match_loser_id($match);
        if ($loserId === null || isset($assigned[$loserId])) {
            continue;
        }

        $roundPosition = (int) ($match['round_number'] ?? 0);
        if ($roundPosition <= 0 || $roundPosition >= $roundCount) {
            continue;
        }

        $eliminatedAt = !empty($match['match_date'])
            ? trim((string) $match['match_date'] . ' ' . ($match['match_time'] ?? '00:00:00'))
            : null;
        $assign(
            $loserId,
            null,
            tournament_elimination_round_placement_label($roundPosition, $roundCount),
            $eliminatedAt
        );
    }
}

function tournament_sync_double_elimination_placements(mysqli $db, int $tourId): void
{
    $stmt = $db->prepare(
        "SELECT
            match_id,
            round_number,
            bracket,
            match_date,
            match_time,
            player1_id,
            player2_id,
            winner_id,
            next_match_id,
            match_status
         FROM matches
         WHERE tour_id = ?
         ORDER BY
            CASE
                WHEN bracket = 'Opening Round' THEN 1
                WHEN bracket = 'Winners Bracket' THEN 1
                WHEN bracket = 'Losers Bracket' THEN 2
                WHEN bracket = 'Grand Final' THEN 3
                WHEN bracket = 'Third Place Playoff' THEN 4
                ELSE 4
            END,
            round_number,
            match_id"
    );
    $stmt->bind_param('i', $tourId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $assigned = [];
    $assign = static function (int $playerId, ?int $rank, string $label, ?string $eliminatedAt = null) use ($db, $tourId, &$assigned): void {
        if ($playerId <= 0 || isset($assigned[$playerId])) {
            return;
        }

        $assigned[$playerId] = true;
        tournament_apply_player_placement($db, $tourId, $playerId, $rank, $label, $eliminatedAt);
    };

    $grandFinal = null;
    $thirdPlaceMatch = null;
    $openingMatches = [];
    $winnersMatches = [];
    $losersMatches = [];
    foreach ($rows as $row) {
        $bracketLabel = trim((string) ($row['bracket'] ?? ''));
        if ($bracketLabel === 'Opening Round') {
            $openingMatches[] = $row;
            continue;
        }

        if ($bracketLabel === 'Third Place Playoff') {
            $thirdPlaceMatch = $row;
            continue;
        }

        if ($bracketLabel === 'Grand Final') {
            $grandFinal = $row;
            continue;
        }

        if ($bracketLabel === 'Losers Bracket') {
            $losersMatches[] = $row;
            continue;
        }

        if ($bracketLabel === 'Winners Bracket') {
            $winnersMatches[] = $row;
        }
    }

    if ($grandFinal && ($grandFinal['match_status'] ?? '') === 'Completed') {
        $winnerId = (int) ($grandFinal['winner_id'] ?? 0);
        $loserId = tournament_match_loser_id($grandFinal) ?? 0;
        $eliminatedAt = !empty($grandFinal['match_date'])
            ? trim((string) $grandFinal['match_date'] . ' ' . ($grandFinal['match_time'] ?? '00:00:00'))
            : null;

        $assign($winnerId, 1, 'Champion');
        $assign($loserId, 2, 'Runner-up', $eliminatedAt);
    }

    if ($thirdPlaceMatch && ($thirdPlaceMatch['match_status'] ?? '') === 'Completed') {
        $winnerId = (int) ($thirdPlaceMatch['winner_id'] ?? 0);
        $loserId = tournament_match_loser_id($thirdPlaceMatch) ?? 0;
        $eliminatedAt = !empty($thirdPlaceMatch['match_date'])
            ? trim((string) $thirdPlaceMatch['match_date'] . ' ' . ($thirdPlaceMatch['match_time'] ?? '00:00:00'))
            : null;

        $assign($winnerId, 3, '3rd Place');
        $assign($loserId, 4, '4th Place', $eliminatedAt);
    }

    $assignBranchLosers = static function (array $matches, string $bracketLabel) use ($assign): void {
        $roundCount = 0;
        foreach ($matches as $match) {
            $roundCount = max($roundCount, (int) ($match['round_number'] ?? 0));
        }

        foreach ($matches as $match) {
            if (($match['match_status'] ?? '') !== 'Completed') {
                continue;
            }

            if (!empty($match['loser_next_match_id'])) {
                continue;
            }

            $loserId = tournament_match_loser_id($match);
            if ($loserId === null) {
                continue;
            }

            $eliminatedAt = !empty($match['match_date'])
                ? trim((string) $match['match_date'] . ' ' . ($match['match_time'] ?? '00:00:00'))
                : null;
            $roundLabel = tournament_bracket_round_title(
                $bracketLabel,
                (int) ($match['round_number'] ?? 1),
                max(1, $roundCount)
            );
            $assign($loserId, null, 'Eliminated in ' . $roundLabel, $eliminatedAt);
        }
    };

    foreach ($openingMatches as $match) {
        if (($match['match_status'] ?? '') !== 'Completed' || !empty($match['loser_next_match_id'])) {
            continue;
        }

        $loserId = tournament_match_loser_id($match);
        if ($loserId === null) {
            continue;
        }

        $eliminatedAt = !empty($match['match_date'])
            ? trim((string) $match['match_date'] . ' ' . ($match['match_time'] ?? '00:00:00'))
            : null;
        $assign($loserId, null, 'Eliminated in Opening Round', $eliminatedAt);
    }

    $assignBranchLosers($winnersMatches, 'Winners Bracket');
    $assignBranchLosers($losersMatches, 'Losers Bracket');
}

function tournament_sync_player_placements(mysqli $db, array $tournament): void
{
    $tourId = (int) ($tournament['tour_id'] ?? 0);
    if ($tourId <= 0) {
        return;
    }

    $clearStmt = $db->prepare(
        'UPDATE tournament_players
         SET final_rank = NULL, placement_label = NULL, eliminated_at = NULL
         WHERE tour_id = ?'
    );
    $clearStmt->bind_param('i', $tourId);
    $clearStmt->execute();
    $clearStmt->close();

    if ($tournament['tour_type'] === 'Double Elimination') {
        tournament_sync_double_elimination_placements($db, $tourId);
        return;
    }

    if (in_array($tournament['tour_type'], ['Elimination', 'League'], true)) {
        tournament_sync_single_elimination_placements($db, $tourId);
    }
}

function tournament_determine_winner_label(mysqli $db, array $tournament): array
{
    if ($tournament['tour_type'] === 'Group') {
        $standings = tournament_team_standings($db, (int) $tournament['tour_id']);
        if (!empty($standings)) {
            return [
                'winner_player_id' => null,
                'winner_team_id' => (int) $standings[0]['team_id'],
                'winner_label' => $standings[0]['team_name'],
            ];
        }

        return ['winner_player_id' => null, 'winner_team_id' => null, 'winner_label' => null];
    }

    if ($tournament['tour_type'] === 'Round Robin') {
        tournament_rebuild_standings($db, (int) $tournament['tour_id']);
        $stmt = $db->prepare(
            "SELECT p.plr_idNum, p.plr_name, p.plr_surname
             FROM tournament_standings s
             JOIN players p ON p.plr_idNum = s.player_id
             WHERE s.tour_id = ?
             ORDER BY s.points DESC, s.leg_difference DESC, s.matches_won DESC, p.plr_surname, p.plr_name
             LIMIT 1"
        );
        $tourId = (int) $tournament['tour_id'];
        $stmt->bind_param('i', $tourId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return [
            'winner_player_id' => $row ? (int) $row['plr_idNum'] : null,
            'winner_team_id' => null,
            'winner_label' => $row ? trim($row['plr_name'] . ' ' . $row['plr_surname']) : null,
        ];
    }

    if ($tournament['tour_type'] === 'Double Elimination') {
        $stmt = $db->prepare(
            "SELECT m.winner_id, p.plr_name, p.plr_surname
             FROM matches m
             LEFT JOIN players p ON p.plr_idNum = m.winner_id
             WHERE m.tour_id = ? AND m.match_status = 'Completed'
             ORDER BY
                CASE
                    WHEN m.bracket = 'Grand Final' THEN 3
                    WHEN m.bracket = 'Losers Bracket' THEN 2
                    ELSE 1
                END DESC,
                m.round_number DESC,
                m.match_id DESC
             LIMIT 1"
        );
    } else {
        $stmt = $db->prepare(
            "SELECT m.winner_id, p.plr_name, p.plr_surname
             FROM matches m
             LEFT JOIN players p ON p.plr_idNum = m.winner_id
             WHERE m.tour_id = ? AND m.match_status = 'Completed'
             ORDER BY m.round_number DESC, m.match_id DESC
             LIMIT 1"
        );
    }
    $tourId = (int) $tournament['tour_id'];
    $stmt->bind_param('i', $tourId);
    $stmt->execute();
    $winner = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return [
        'winner_player_id' => $winner && $winner['winner_id'] !== null ? (int) $winner['winner_id'] : null,
        'winner_team_id' => null,
        'winner_label' => $winner && $winner['winner_id'] !== null ? trim(($winner['plr_name'] ?? '') . ' ' . ($winner['plr_surname'] ?? '')) : null,
    ];
}

function tournament_all_competition_completed(mysqli $db, array $tournament): bool
{
    if ($tournament['tour_type'] === 'Group') {
        if (tournament_group_has_individual_matches($db, (int) $tournament['tour_id'])) {
            $stmt = $db->prepare(
                "SELECT
                    COUNT(*) AS total_matches,
                    SUM(CASE WHEN match_status = 'Completed' THEN 1 ELSE 0 END) AS completed_matches
                 FROM matches
                 WHERE tour_id = ?"
            );
        } else {
            $stmt = $db->prepare(
                "SELECT
                    COUNT(*) AS total_matches,
                    SUM(CASE WHEN match_status = 'Completed' THEN 1 ELSE 0 END) AS completed_matches
                 FROM team_matches
                 WHERE tour_id = ?"
            );
        }
    } else {
        $stmt = $db->prepare(
            "SELECT
                COUNT(*) AS total_matches,
                SUM(CASE WHEN match_status = 'Completed' THEN 1 ELSE 0 END) AS completed_matches
             FROM matches
             WHERE tour_id = ?"
        );
    }

    $tourId = (int) $tournament['tour_id'];
    $stmt->bind_param('i', $tourId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $total = (int) ($row['total_matches'] ?? 0);
    $completed = (int) ($row['completed_matches'] ?? 0);

    return $total > 0 && $total === $completed;
}

function tournament_has_started(mysqli $db, array $tournament): bool
{
    if ($tournament['tour_type'] === 'Group') {
        if (tournament_group_has_individual_matches($db, (int) $tournament['tour_id'])) {
            $stmt = $db->prepare(
                "SELECT COUNT(*) AS started_matches
                 FROM matches
                 WHERE tour_id = ? AND match_status IN ('Completed', 'In Progress')"
            );
        } else {
            $stmt = $db->prepare(
                "SELECT COUNT(*) AS started_matches
                 FROM team_matches
                 WHERE tour_id = ? AND match_status IN ('Completed', 'In Progress')"
            );
        }
    } else {
        $stmt = $db->prepare(
            "SELECT COUNT(*) AS started_matches
             FROM matches
             WHERE tour_id = ? AND match_status IN ('Completed', 'In Progress')"
        );
    }

    $tourId = (int) $tournament['tour_id'];
    $stmt->bind_param('i', $tourId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return ((int) ($row['started_matches'] ?? 0)) > 0;
}

function tournament_refresh_lifecycle(mysqli $db, int $tourId): array
{
    $tournament = tournament_fetch_settings($db, $tourId);
    tournament_sync_player_placements($db, $tournament);
    $tournament = tournament_fetch_settings($db, $tourId);

    if (!empty($tournament['archived_at'])) {
        $status = 'archived';
    } elseif (tournament_all_competition_completed($db, $tournament)) {
        $status = 'completed';
    } elseif (tournament_has_started($db, $tournament)) {
        $status = 'in_progress';
    } else {
        $status = tournament_compute_initial_status(
            new DateTime($tournament['registration_open_at'] ?: $tournament['tour_creationDate']),
            new DateTime($tournament['registration_close_at'] ?: $tournament['tour_creationDate'])
        );
    }

    $winner = ['winner_player_id' => null, 'winner_team_id' => null, 'winner_label' => null];
    if ($status === 'completed' || $status === 'archived') {
        $winner = tournament_determine_winner_label($db, $tournament);
    }

    $startedAt = $tournament['started_at'];
    if ($status === 'in_progress' && empty($startedAt)) {
        $startedAt = (new DateTime())->format('Y-m-d H:i:s');
    }

    $completedAt = $tournament['completed_at'];
    if ($status === 'completed' && empty($completedAt)) {
        $completedAt = (new DateTime())->format('Y-m-d H:i:s');
    }
    if ($status !== 'completed' && $status !== 'archived') {
        $completedAt = null;
    }

    $update = $db->prepare(
        'UPDATE tournaments
         SET status = ?,
             started_at = ?,
             completed_at = ?,
             winner_player_id = ?,
             winner_team_id = ?,
             winner_label = ?
         WHERE tour_id = ?'
    );

    $winnerPlayerId = $winner['winner_player_id'];
    $winnerTeamId = $winner['winner_team_id'];
    $winnerLabel = $winner['winner_label'];
    $update->bind_param('sssiisi', $status, $startedAt, $completedAt, $winnerPlayerId, $winnerTeamId, $winnerLabel, $tourId);
    $update->execute();
    $update->close();

    return tournament_fetch_settings($db, $tourId);
}

function tournament_record_match_result(mysqli $db, int $matchId, int $player1Score, int $player2Score): array
{
    if ($player1Score < 0 || $player2Score < 0) {
        throw new InvalidArgumentException('Scores cannot be negative.');
    }

    $match = tournament_fetch_match($db, $matchId);
    tournament_assert_mutable($db, (int) $match['tour_id']);

    $isLeagueGroupStage = $match['tour_type'] === 'League' && $match['group_number'] !== null;
    $isKnockout = $match['tour_type'] === 'Elimination'
        || $match['tour_type'] === 'Double Elimination'
        || ($match['tour_type'] === 'League' && $match['group_number'] === null);

    if ($player1Score === 0 && $player2Score === 0) {
        throw new InvalidArgumentException('At least one side must score.');
    }

    if ($isKnockout && $player1Score === $player2Score) {
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

    if ($match['tour_type'] === 'Round Robin' || $isLeagueGroupStage) {
        tournament_rebuild_standings($db, (int) $match['tour_id']);
    }

    if ($winnerId !== null) {
        tournament_propagate_winner($db, $match, $winnerId);
    }

    if ($isLeagueGroupStage) {
        try {
            tournament_promote_league_groups($db, (int) $match['tour_id']);
        } catch (Throwable $ignored) {
        }
    }

    tournament_refresh_lifecycle($db, (int) $match['tour_id']);

    return [
        'winner_id' => $winnerId,
        'is_draw' => $winnerId === null,
    ];
}

function tournament_record_team_match_result(mysqli $db, int $teamMatchId, int $team1Score, int $team2Score): array
{
    if ($team1Score < 0 || $team2Score < 0) {
        throw new InvalidArgumentException('Scores cannot be negative.');
    }

    $match = tournament_fetch_team_match($db, $teamMatchId);
    tournament_assert_mutable($db, (int) $match['tour_id']);

    if ($team1Score === 0 && $team2Score === 0) {
        throw new InvalidArgumentException('At least one side must score.');
    }

    $winnerTeamId = null;
    if ($team1Score > $team2Score) {
        $winnerTeamId = (int) $match['team1_id'];
    } elseif ($team2Score > $team1Score) {
        $winnerTeamId = (int) $match['team2_id'];
    }

    $update = $db->prepare(
        "UPDATE team_matches
         SET team1_score = ?, team2_score = ?, winner_team_id = ?, match_status = 'Completed'
         WHERE team_match_id = ?"
    );
    $update->bind_param('iiii', $team1Score, $team2Score, $winnerTeamId, $teamMatchId);
    $update->execute();
    $update->close();

    tournament_refresh_lifecycle($db, (int) $match['tour_id']);

    return [
        'winner_team_id' => $winnerTeamId,
        'is_draw' => $winnerTeamId === null,
    ];
}

function tournament_fetch_page_data(mysqli $db, int $tourId): array
{
    $detailsSql = "SELECT
                        t.*,
                        COUNT(DISTINCT tp.plr_id) AS player_count,
                        COUNT(DISTINCT m.match_id) AS match_count,
                        COUNT(DISTINCT tm.team_match_id) AS team_match_count,
                        COUNT(DISTINCT tt.team_id) AS team_count_actual
                   FROM tournaments t
                   LEFT JOIN tournament_players tp ON tp.tour_id = t.tour_id
                   LEFT JOIN matches m ON m.tour_id = t.tour_id
                   LEFT JOIN team_matches tm ON tm.tour_id = t.tour_id
                   LEFT JOIN tournament_teams tt ON tt.tour_id = t.tour_id
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

    $summary = [
        'player_count' => (int) ($tournament['player_count'] ?? 0),
        'match_count' => (int) ($tournament['match_count'] ?? 0),
        'team_match_count' => (int) ($tournament['team_match_count'] ?? 0),
        'team_count_actual' => (int) ($tournament['team_count_actual'] ?? 0),
    ];

    $tournament = array_merge(tournament_refresh_lifecycle($db, $tourId), $summary);
    $tournament['structure_generated'] = (
        $tournament['match_count'] > 0
        || $tournament['team_match_count'] > 0
        || $tournament['team_count_actual'] > 0
    ) ? 1 : 0;

    $playersSql = "SELECT
                        p.plr_idNum,
                        p.plr_name,
                        p.plr_surname,
                        tp.player_status,
                        tp.group_number,
                        tp.final_rank,
                        tp.placement_label
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
                        tt1.team_id AS player1_team_id,
                        tt1.team_name AS player1_team_name,
                        tt2.team_id AS player2_team_id,
                        tt2.team_name AS player2_team_name,
                        COALESCE(pmw1.match_id, pml1.match_id) AS prev_match1_id,
                        CASE
                            WHEN pmw1.match_id IS NOT NULL THEN 'winner'
                            WHEN pml1.match_id IS NOT NULL THEN 'loser'
                            ELSE NULL
                        END AS prev_match1_source,
                        COALESCE(pmw2.match_id, pml2.match_id) AS prev_match2_id,
                        CASE
                            WHEN pmw2.match_id IS NOT NULL THEN 'winner'
                            WHEN pml2.match_id IS NOT NULL THEN 'loser'
                            ELSE NULL
                        END AS prev_match2_source
                   FROM matches m
                   LEFT JOIN players p1 ON p1.plr_idNum = m.player1_id
                   LEFT JOIN players p2 ON p2.plr_idNum = m.player2_id
                   LEFT JOIN tournament_team_players ttp1 ON ttp1.tour_id = m.tour_id AND ttp1.player_id = m.player1_id
                   LEFT JOIN tournament_teams tt1 ON tt1.team_id = ttp1.team_id
                   LEFT JOIN tournament_team_players ttp2 ON ttp2.tour_id = m.tour_id AND ttp2.player_id = m.player2_id
                   LEFT JOIN tournament_teams tt2 ON tt2.team_id = ttp2.team_id
                   LEFT JOIN matches pmw1 ON pmw1.next_match_id = m.match_id AND pmw1.position_in_next = 1
                   LEFT JOIN matches pml1 ON pml1.loser_next_match_id = m.match_id AND pml1.loser_position_in_next = 1
                   LEFT JOIN matches pmw2 ON pmw2.next_match_id = m.match_id AND pmw2.position_in_next = 2
                   LEFT JOIN matches pml2 ON pml2.loser_next_match_id = m.match_id AND pml2.loser_position_in_next = 2
                   WHERE m.tour_id = ?
                   ORDER BY m.group_number IS NULL, m.group_number, m.round_number, m.match_date, m.match_time, m.match_id";
    $matchesStmt = $db->prepare($matchesSql);
    $matchesStmt->bind_param('i', $tourId);
    $matchesStmt->execute();
    $matches = $matchesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $matchesStmt->close();

    $teamMatches = tournament_fetch_team_matches($db, $tourId);
    $teams = tournament_fetch_team_rosters($db, $tourId);

    $standings = [];
    if ($tournament['tour_type'] === 'Round Robin') {
        tournament_rebuild_standings($db, $tourId);
        $standingsStmt = $db->prepare(
            "SELECT
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
             ORDER BY s.points DESC, s.leg_difference DESC, s.matches_won DESC, p.plr_surname, p.plr_name"
        );
        $standingsStmt->bind_param('i', $tourId);
        $standingsStmt->execute();
        $standings = $standingsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $standingsStmt->close();
    }

    return [
        'tournament' => $tournament,
        'players' => $players,
        'matches' => $matches,
        'team_matches' => $teamMatches,
        'teams' => $teams,
        'standings' => $standings,
        'group_standings' => $tournament['tour_type'] === 'League' ? tournament_group_standings($db, $tourId) : [],
        'team_standings' => $tournament['tour_type'] === 'Group' ? tournament_team_standings($db, $tourId) : [],
        'available_players' => players_not_in_tournament($db, $tourId),
    ];
}
