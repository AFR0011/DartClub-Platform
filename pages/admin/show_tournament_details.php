<?php

require_once '../../services/app_bootstrap.php';
require_once '../../services/dbConnection.php';
require_once '../../services/auth.php';
require_once '../../services/shared/admin_locale_helpers.php';
require_once '../../services/shared/player_helpers.php';
require_once '../../services/shared/tournament_helpers.php';
require_once '../../services/shared/tournament_view_helpers.php';

app_start_session();
require_any_role(['admin', 'manager']);

$tourId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($tourId <= 0) {
    app_redirect('manage_tournaments.php');
}

try {
    $pageData = tournament_fetch_page_data($conn, $tourId);
} catch (Throwable $exception) {
    echo admin_text('Tournament not found', 'Turnuva bulunamadı');
    exit;
}

$tournament = $pageData['tournament'];
$players = $pageData['players'];
$matches = $pageData['matches'];
$teamMatches = $pageData['team_matches'];
$teams = $pageData['teams'];
$standings = $pageData['standings'];
$groupStandings = $pageData['group_standings'];
$teamStandings = $pageData['team_standings'];
$availablePlayers = $pageData['available_players'];
$status = tournament_status_data($tournament);
$structureGenerated = ((int) ($tournament['structure_generated'] ?? 0)) === 1;
$groupUsesIndividualMatches = $tournament['tour_type'] === 'Group' && !empty($matches);
$displayMatchCount = $groupUsesIndividualMatches
    ? (int) ($tournament['match_count'] ?? 0)
    : ($tournament['tour_type'] === 'Group'
        ? (int) ($tournament['team_match_count'] ?? 0)
        : (int) ($tournament['match_count'] ?? 0));

$matchNumbersById = [];
foreach ($matches as $index => $match) {
    $matchNumbersById[(int) $match['match_id']] = $index + 1;
}

$groupMatches = [];
$knockoutBracketGroups = [];
foreach ($matches as $match) {
    if ($tournament['tour_type'] === 'Group') {
        continue;
    }

    if ($match['group_number'] !== null) {
        $groupNumber = (int) $match['group_number'];
        if (!isset($groupMatches[$groupNumber])) {
            $groupMatches[$groupNumber] = [];
        }
        $groupMatches[$groupNumber][] = $match;
        continue;
    }

    $rawBracketLabel = trim((string) ($match['bracket'] ?? ''));
    if ($rawBracketLabel === '') {
        $rawBracketLabel = 'Elimination';
    }
    $bracketKey = $tournament['tour_type'] === 'Double Elimination'
        ? $rawBracketLabel
        : ($rawBracketLabel === 'Third Place Playoff' ? 'Third Place Playoff' : 'primary');
    $displayLabel = $tournament['tour_type'] === 'Double Elimination'
        ? admin_translate_value($rawBracketLabel)
        : (
            $rawBracketLabel === 'Third Place Playoff'
                ? admin_translate_value('Third Place Playoff')
                : admin_translate_value($rawBracketLabel === 'Knockout' ? 'Knockout Bracket' : 'Tournament Bracket')
        );

    if (!isset($knockoutBracketGroups[$bracketKey])) {
        $knockoutBracketGroups[$bracketKey] = [
            'key' => $bracketKey,
            'source_label' => $rawBracketLabel,
            'display_label' => $displayLabel,
            'rounds' => [],
            'round_numbers' => [],
            'round_positions' => [],
            'round_count' => 0,
            'slot_count' => 0,
        ];
    }

    $roundNumber = (int) $match['round_number'];
    if (!isset($knockoutBracketGroups[$bracketKey]['rounds'][$roundNumber])) {
        $knockoutBracketGroups[$bracketKey]['rounds'][$roundNumber] = [];
    }
    $knockoutBracketGroups[$bracketKey]['rounds'][$roundNumber][] = $match;
}

ksort($groupMatches);
$knockoutBracketPriority = [
    'Opening Round' => 1,
    'Losers Bracket' => 2,
    'Winners Bracket' => 3,
    'Elimination' => 2,
    'Knockout' => 2,
    'Grand Final' => 4,
    'Third Place Playoff' => 5,
];
uasort($knockoutBracketGroups, static function (array $left, array $right) use ($knockoutBracketPriority): int {
    $leftPriority = $knockoutBracketPriority[$left['source_label']] ?? 99;
    $rightPriority = $knockoutBracketPriority[$right['source_label']] ?? 99;
    if ($leftPriority !== $rightPriority) {
        return $leftPriority <=> $rightPriority;
    }

    return strcmp((string) $left['display_label'], (string) $right['display_label']);
});

$bracketRoundCountByLabel = [];
foreach ($knockoutBracketGroups as $groupKey => $group) {
    ksort($group['rounds']);
    $roundNumbers = array_keys($group['rounds']);
    $roundPositions = [];
    foreach ($roundNumbers as $index => $roundNumber) {
        $roundPositions[(int) $roundNumber] = $index + 1;
    }
    $group['round_numbers'] = $roundNumbers;
    $group['round_positions'] = $roundPositions;
    $group['round_count'] = count($roundNumbers);
    $group['slot_count'] = $group['round_count'] > 0 ? (int) pow(2, $group['round_count']) : 0;
    $knockoutBracketGroups[$groupKey] = $group;
    $bracketRoundCountByLabel[$group['source_label']] = $group['round_count'];
}

$selectedBracketMatchId = 0;
if (!empty($knockoutBracketGroups)) {
    $firstBracketGroup = reset($knockoutBracketGroups);
    $firstRoundNumber = (int) ($firstBracketGroup['round_numbers'][0] ?? 0);
    if ($firstRoundNumber > 0 && !empty($firstBracketGroup['rounds'][$firstRoundNumber][0]['match_id'])) {
        $selectedBracketMatchId = (int) $firstBracketGroup['rounds'][$firstRoundNumber][0]['match_id'];
    }
}
$canStartTournament = in_array((string) ($tournament['status'] ?? ''), ['draft', 'registration_open', 'registration_closed'], true);
$rosterUsesWithdrawals = !empty($tournament['started_at'])
    || in_array((string) ($tournament['status'] ?? ''), ['in_progress', 'completed'], true)
    || tournament_structure_is_locked($conn, $tourId);
$removeActionLabel = $rosterUsesWithdrawals
    ? admin_text('Withdraw', 'Çekildi')
    : admin_text('Remove', 'Kaldır');
$removeSelectionEmptyText = $rosterUsesWithdrawals
    ? admin_text('No players marked for withdrawal.', 'Çekilmek için işaretlenen oyuncu yok.')
    : admin_text('No players marked for removal.', 'Kaldırmak için işaretlenen oyuncu yok.');
$newPlayersDefaultStatus = $rosterUsesWithdrawals ? 'Active' : 'Registered';
$isDoubleElimination = $tournament['tour_type'] === 'Double Elimination';
$hasSingleEliminationBracketViews = !$isDoubleElimination && count($knockoutBracketGroups) > 1;
$bracketPanels = [];
if ($isDoubleElimination) {
    $openingBracketGroup = $knockoutBracketGroups['Opening Round'] ?? null;
    $winnersBracketGroup = $knockoutBracketGroups['Winners Bracket'] ?? null;
    $losersBracketGroup = $knockoutBracketGroups['Losers Bracket'] ?? null;
    $grandFinalGroup = $knockoutBracketGroups['Grand Final'] ?? null;
    $thirdPlaceGroup = $knockoutBracketGroups['Third Place Playoff'] ?? null;

    if ($openingBracketGroup !== null || $winnersBracketGroup !== null || $losersBracketGroup !== null) {
        $bracketPanels['merged'] = [
            'label' => 'Merged Bracket',
            'groups' => array_values(array_filter([$losersBracketGroup, $openingBracketGroup, $winnersBracketGroup])),
            'description' => 'The opening round stays centered, then the field splits into a losers-side tree and a winners-side tree.',
        ];
    }

    if ($winnersBracketGroup !== null) {
        $bracketPanels['winners'] = [
            'label' => 'Winners Bracket',
            'groups' => array_values(array_filter([$openingBracketGroup, $winnersBracketGroup])),
            'description' => 'Show the shared opening round plus the winners-side branch.',
        ];
    }

    if ($losersBracketGroup !== null) {
        $bracketPanels['losers'] = [
            'label' => 'Losers Bracket',
            'groups' => array_values(array_filter([$openingBracketGroup, $losersBracketGroup])),
            'description' => 'Show the shared opening round plus the losers-side branch.',
        ];
    }

    if ($grandFinalGroup !== null || $thirdPlaceGroup !== null) {
        $bracketPanels['grand_final'] = [
            'label' => 'Grand Final',
            'groups' => array_values(array_filter([$grandFinalGroup, $thirdPlaceGroup])),
            'description' => 'Show the grand final and the separate 3rd-place playoff.',
        ];
    }
}

$placementPlayers = array_values(array_filter($players, static function (array $player): bool {
    return ($player['final_rank'] ?? null) !== null || trim((string) ($player['placement_label'] ?? '')) !== '';
}));
usort($placementPlayers, static function (array $left, array $right): int {
    $leftRank = isset($left['final_rank']) && $left['final_rank'] !== null ? (int) $left['final_rank'] : 9999;
    $rightRank = isset($right['final_rank']) && $right['final_rank'] !== null ? (int) $right['final_rank'] : 9999;
    if ($leftRank !== $rightRank) {
        return $leftRank <=> $rightRank;
    }

    return strcmp(player_name($left), player_name($right));
});

$teamStandingsById = [];
foreach ($teamStandings as $standing) {
    $teamStandingsById[(int) ($standing['team_id'] ?? 0)] = $standing;
}

$groupTeamPlayerStats = [];
if ($tournament['tour_type'] === 'Group') {
    foreach ($teams as $team) {
        foreach ($team['players'] as $teamPlayer) {
            $groupTeamPlayerStats[(int) $teamPlayer['plr_idNum']] = [
                'matches_played' => 0,
                'matches_won' => 0,
                'matches_lost' => 0,
                'matches_drawn' => 0,
                'points' => 0,
                'leg_difference' => 0,
            ];
        }
    }

    foreach ($matches as $match) {
        $player1Id = (int) ($match['player1_id'] ?? 0);
        $player2Id = (int) ($match['player2_id'] ?? 0);
        if (!isset($groupTeamPlayerStats[$player1Id], $groupTeamPlayerStats[$player2Id])) {
            continue;
        }

        if (($match['match_status'] ?? '') !== 'Completed' || $match['player1_score'] === null || $match['player2_score'] === null) {
            continue;
        }

        $player1Score = (int) $match['player1_score'];
        $player2Score = (int) $match['player2_score'];
        $groupTeamPlayerStats[$player1Id]['matches_played']++;
        $groupTeamPlayerStats[$player2Id]['matches_played']++;
        $groupTeamPlayerStats[$player1Id]['leg_difference'] += $player1Score - $player2Score;
        $groupTeamPlayerStats[$player2Id]['leg_difference'] += $player2Score - $player1Score;

        if ($player1Score > $player2Score) {
            $groupTeamPlayerStats[$player1Id]['matches_won']++;
            $groupTeamPlayerStats[$player1Id]['points'] += 3;
            $groupTeamPlayerStats[$player2Id]['matches_lost']++;
        } elseif ($player2Score > $player1Score) {
            $groupTeamPlayerStats[$player2Id]['matches_won']++;
            $groupTeamPlayerStats[$player2Id]['points'] += 3;
            $groupTeamPlayerStats[$player1Id]['matches_lost']++;
        } else {
            $groupTeamPlayerStats[$player1Id]['matches_drawn']++;
            $groupTeamPlayerStats[$player2Id]['matches_drawn']++;
            $groupTeamPlayerStats[$player1Id]['points']++;
            $groupTeamPlayerStats[$player2Id]['points']++;
        }
    }
}

function tournament_admin_round_title(string $bracketLabel, int $roundNumber, int $roundCount): string
{
    return admin_round_title_label(tournament_bracket_round_title($bracketLabel, $roundNumber, $roundCount));
}

function tournament_admin_match_label(array $match, string $slot, array $matchNumbersById): string
{
    return admin_translate_value(tournament_match_label($match, $slot, $matchNumbersById));
}

function tournament_admin_match_flow(array $match, array $matchNumbersById): string
{
    return admin_match_flow_label(tournament_match_advancement_label($match, $matchNumbersById));
}

function tournament_match_visual_state(array $match): array
{
    $status = strtolower(trim((string) ($match['match_status'] ?? 'scheduled')));
    $cardClass = 'state-scheduled';

    if ($status === 'completed') {
        $cardClass = 'state-completed';
    } elseif ($status === 'in progress' || $status === 'in_progress') {
        $cardClass = 'state-live';
    } elseif (!empty($match['player1_id']) && !empty($match['player2_id'])) {
        $cardClass = 'state-ready';
    } elseif (!empty($match['player1_id']) || !empty($match['player2_id'])) {
        $cardClass = 'state-waiting';
    }

    $player1Class = '';
    $player2Class = '';
    if ($status === 'completed' && $match['player1_score'] !== null && $match['player2_score'] !== null) {
        if ((int) $match['player1_score'] > (int) $match['player2_score']) {
            $player1Class = 'slot-winner';
            $player2Class = 'slot-loser';
        } elseif ((int) $match['player2_score'] > (int) $match['player1_score']) {
            $player1Class = 'slot-loser';
            $player2Class = 'slot-winner';
        }
    }

    return [
        'card' => $cardClass,
        'player1' => $player1Class,
        'player2' => $player2Class,
    ];
}

$playersForJs = array_map(function (array $player): array {
    return [
        'id' => (int) $player['plr_idNum'],
        'name' => player_name($player),
    ];
}, $players);

$matchesForJs = [];
foreach ($matches as $match) {
    $matchId = (int) $match['match_id'];
    $nextMatchNumber = null;
    if (!empty($match['next_match_id']) && isset($matchNumbersById[(int) $match['next_match_id']])) {
        $nextMatchNumber = (int) $matchNumbersById[(int) $match['next_match_id']];
    }
    $loserNextMatchNumber = null;
    if (!empty($match['loser_next_match_id']) && isset($matchNumbersById[(int) $match['loser_next_match_id']])) {
        $loserNextMatchNumber = (int) $matchNumbersById[(int) $match['loser_next_match_id']];
    }
    $bracketLabel = trim((string) ($match['bracket'] ?? ''));
    if ($bracketLabel === '') {
        $bracketLabel = 'Elimination';
    }

    $matchesForJs[$matchId] = [
        'id' => $matchId,
        'number' => (int) ($matchNumbersById[$matchId] ?? 0),
        'status' => admin_match_status_label((string) ($match['match_status'] ?? 'Scheduled')),
        'bracket' => $bracketLabel,
        'round_title' => tournament_admin_round_title(
            $bracketLabel,
            (int) ($match['round_number'] ?? 1),
            (int) ($bracketRoundCountByLabel[$bracketLabel] ?? 1)
        ),
        'player1_label' => tournament_admin_match_label($match, 'player1', $matchNumbersById),
        'player2_label' => tournament_admin_match_label($match, 'player2', $matchNumbersById),
        'player1_profile_url' => !empty($match['player1_id']) ? '../player_profile.php?id=' . (int) $match['player1_id'] : '',
        'player2_profile_url' => !empty($match['player2_id']) ? '../player_profile.php?id=' . (int) $match['player2_id'] : '',
        'next_match_number' => $nextMatchNumber,
        'loser_next_match_number' => $loserNextMatchNumber,
        'flow_label' => tournament_admin_match_flow($match, $matchNumbersById),
    ];
}
?>
<!DOCTYPE html>
<html lang="<?php echo admin_html_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(admin_text('Tournament Details', 'Turnuva Ayrıntıları')); ?></title>
    <link href="../../css/admin_style.css" rel="stylesheet">
    <script src="../../js/admin_nav.js"></script>
    <style>
        .info-grid,
        .inline-grid,
        .section-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .info-card,
        .section-card,
        .match-card {
            border: 1px solid rgba(37, 99, 235, 0.1);
            border-radius: 22px;
            padding: 18px;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(247, 250, 255, 0.94));
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.06);
        }

        .section-card h3,
        .match-card h4 {
            margin-top: 0;
        }

        .section-toggle {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 22px 0 16px;
        }

        .section-toggle button {
            padding: 10px 16px;
            border: 1px solid rgba(37, 99, 235, 0.12);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.94);
            color: #334155;
            font-size: 0.92rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease, background-color 0.18s ease;
            box-shadow: 0 10px 18px rgba(15, 23, 42, 0.05);
        }

        .section-toggle button:hover {
            transform: translateY(-1px);
            border-color: rgba(37, 99, 235, 0.22);
            box-shadow: 0 14px 24px rgba(15, 23, 42, 0.08);
        }

        .section-toggle button.active {
            background: linear-gradient(180deg, #60a5fa 0%, #2563eb 100%);
            color: #fff;
            border-color: rgba(37, 99, 235, 0.4);
        }

        .page-section {
            display: none;
        }

        .page-section.active {
            display: block;
        }

        .page-section > .header-actions:first-child {
            margin-top: 6px;
        }

        .subsection-toggle {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 16px 0 18px;
            padding: 8px;
            border-radius: 20px;
            border: 1px solid rgba(148, 163, 184, 0.2);
            background: linear-gradient(180deg, #eef4ff, #f8fbff);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.05);
        }

        .subsection-toggle button {
            border: 1px solid rgba(148, 163, 184, 0.22);
            background: rgba(255, 255, 255, 0.88);
            color: #334155;
            border-radius: 14px;
            padding: 10px 14px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease, background-color 0.18s ease;
            box-shadow: 0 10px 18px rgba(15, 23, 42, 0.05);
        }

        .subsection-toggle button:hover {
            transform: translateY(-1px);
            border-color: rgba(37, 99, 235, 0.22);
            box-shadow: 0 14px 24px rgba(15, 23, 42, 0.08);
        }

        .subsection-toggle button.active {
            background: linear-gradient(180deg, #60a5fa 0%, #2563eb 100%);
            color: #fff;
            border-color: rgba(37, 99, 235, 0.4);
        }

        .section-panel-stack {
            display: grid;
            gap: 18px;
        }

        .callout {
            margin: 16px 0;
            padding: 16px 18px;
            border-left: 4px solid #1f6feb;
            background: linear-gradient(180deg, #eef5ff, #f8fbff);
            border-radius: 18px;
        }

        .bracket-grid {
            display: flex;
            gap: 18px;
            overflow-x: auto;
            padding: 6px 0 12px;
            align-items: flex-start;
        }

        .connected-bracket-shell {
            overflow-x: auto;
            padding-bottom: 14px;
        }

        .connected-bracket {
            --bracket-track: 106px;
            display: grid;
            grid-auto-flow: column;
            grid-auto-columns: minmax(224px, 224px);
            gap: 24px;
            align-items: start;
            min-width: max-content;
            padding-right: 12px;
        }

        [data-bracket-group="Winners Bracket"] .connected-bracket,
        [data-bracket-group="Losers Bracket"] .connected-bracket {
            --bracket-track: 122px;
        }

        .merged-bracket-grid {
            display: grid;
            gap: 18px;
            align-items: start;
            grid-template-columns: minmax(0, 1fr) minmax(240px, 280px) minmax(0, 1fr);
            grid-template-areas: "losers opening winners";
            transition: grid-template-columns 0.32s ease, gap 0.32s ease;
        }

        .merged-bracket-grid.is-single-view {
            grid-template-columns: minmax(0, 1fr);
            grid-template-areas: none;
        }

        .merged-bracket-grid [data-bracket-group] {
            min-width: 0;
            overflow: hidden;
            transition:
                opacity 0.28s ease,
                transform 0.28s ease,
                padding 0.28s ease,
                border-color 0.28s ease,
                box-shadow 0.28s ease,
                max-width 0.28s ease,
                max-height 0.28s ease,
                margin 0.28s ease;
        }

        .merged-bracket-grid [data-bracket-group="Losers Bracket"] {
            grid-area: losers;
            justify-self: stretch;
        }

        .merged-bracket-grid [data-bracket-group="Opening Round"] {
            grid-area: opening;
            justify-self: center;
        }

        .merged-bracket-grid [data-bracket-group="Winners Bracket"] {
            grid-area: winners;
            justify-self: stretch;
        }

        .merged-bracket-grid [data-bracket-group="Grand Final"],
        .merged-bracket-grid [data-bracket-group="Third Place Playoff"] {
            justify-self: stretch;
        }

        .merged-bracket-grid[data-active-view="merged"] [data-bracket-group="Opening Round"] {
            width: min(100%, 280px);
        }

        .merged-bracket-grid[data-active-view="Winners Bracket"] {
            grid-template-columns: 0fr minmax(240px, 280px) minmax(0, 1.45fr);
        }

        .merged-bracket-grid[data-active-view="Losers Bracket"] {
            grid-template-columns: minmax(0, 1.45fr) minmax(240px, 280px) 0fr;
        }

        .merged-bracket-grid[data-active-view="Grand Final"] {
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            grid-template-areas: none;
        }

        .merged-bracket-grid[data-active-view="Winners Bracket"] [data-bracket-group="Opening Round"],
        .merged-bracket-grid[data-active-view="Losers Bracket"] [data-bracket-group="Opening Round"] {
            width: min(100%, 280px);
        }

        .merged-bracket-grid[data-active-view="merged"] [data-bracket-group="Losers Bracket"] .connected-bracket,
        .merged-bracket-grid[data-active-view="Losers Bracket"] [data-bracket-group="Losers Bracket"] .connected-bracket {
            display: flex;
            flex-direction: row-reverse;
            gap: 24px;
            min-width: max-content;
            align-items: start;
        }

        .merged-bracket-grid[data-active-view="merged"] [data-bracket-group="Losers Bracket"] .connected-bracket-round,
        .merged-bracket-grid[data-active-view="Losers Bracket"] [data-bracket-group="Losers Bracket"] .connected-bracket-round {
            flex: 0 0 224px;
        }

        .merged-bracket-grid[data-active-view="merged"] [data-bracket-group="Losers Bracket"] .connected-bracket-round h3,
        .merged-bracket-grid[data-active-view="Losers Bracket"] [data-bracket-group="Losers Bracket"] .connected-bracket-round h3 {
            text-align: right;
        }

        .merged-bracket-grid[data-active-view="merged"] [data-bracket-group="Losers Bracket"] .connected-bracket-node.has-incoming::before,
        .merged-bracket-grid[data-active-view="Losers Bracket"] [data-bracket-group="Losers Bracket"] .connected-bracket-node.has-incoming::before {
            left: auto;
            right: -18px;
        }

        .merged-bracket-grid[data-active-view="merged"] [data-bracket-group="Losers Bracket"] .connected-bracket-node.has-incoming::after,
        .merged-bracket-grid[data-active-view="Losers Bracket"] [data-bracket-group="Losers Bracket"] .connected-bracket-node.has-incoming::after {
            left: auto;
            right: -18px;
        }

        .merged-bracket-grid[data-active-view="merged"] [data-bracket-group="Losers Bracket"] .connected-bracket-matchup.has-outgoing::after,
        .merged-bracket-grid[data-active-view="Losers Bracket"] [data-bracket-group="Losers Bracket"] .connected-bracket-matchup.has-outgoing::after {
            left: -18px;
            right: auto;
        }

        .merged-bracket-grid[data-active-view="merged"] [data-bracket-group="Losers Bracket"] .bracket-grid,
        .merged-bracket-grid[data-active-view="Losers Bracket"] [data-bracket-group="Losers Bracket"] .bracket-grid {
            flex-direction: row-reverse;
        }

        .merged-bracket-grid[data-active-view="merged"] [data-bracket-group="Losers Bracket"],
        .merged-bracket-grid[data-active-view="merged"] [data-bracket-group="Opening Round"],
        .merged-bracket-grid[data-active-view="merged"] [data-bracket-group="Winners Bracket"],
        .merged-bracket-grid[data-active-view="Winners Bracket"] [data-bracket-group="Opening Round"],
        .merged-bracket-grid[data-active-view="Winners Bracket"] [data-bracket-group="Winners Bracket"],
        .merged-bracket-grid[data-active-view="Losers Bracket"] [data-bracket-group="Opening Round"],
        .merged-bracket-grid[data-active-view="Losers Bracket"] [data-bracket-group="Losers Bracket"],
        .merged-bracket-grid[data-active-view="Grand Final"] [data-bracket-group="Grand Final"],
        .merged-bracket-grid[data-active-view="Grand Final"] [data-bracket-group="Third Place Playoff"] {
            opacity: 1;
            pointer-events: auto;
            transform: none;
            max-width: none;
            max-height: none;
            margin: 0;
        }

        .merged-bracket-grid[data-active-view="merged"] [data-bracket-group="Grand Final"],
        .merged-bracket-grid[data-active-view="merged"] [data-bracket-group="Third Place Playoff"],
        .merged-bracket-grid[data-active-view="Winners Bracket"] [data-bracket-group="Losers Bracket"],
        .merged-bracket-grid[data-active-view="Winners Bracket"] [data-bracket-group="Grand Final"],
        .merged-bracket-grid[data-active-view="Winners Bracket"] [data-bracket-group="Third Place Playoff"],
        .merged-bracket-grid[data-active-view="Losers Bracket"] [data-bracket-group="Winners Bracket"],
        .merged-bracket-grid[data-active-view="Losers Bracket"] [data-bracket-group="Grand Final"],
        .merged-bracket-grid[data-active-view="Losers Bracket"] [data-bracket-group="Third Place Playoff"],
        .merged-bracket-grid[data-active-view="Grand Final"] [data-bracket-group="Losers Bracket"],
        .merged-bracket-grid[data-active-view="Grand Final"] [data-bracket-group="Opening Round"],
        .merged-bracket-grid[data-active-view="Grand Final"] [data-bracket-group="Winners Bracket"] {
            opacity: 0;
            pointer-events: none;
            border-color: transparent;
            box-shadow: none;
            padding: 0;
            max-width: 0;
            max-height: 0;
            margin: 0;
        }

        .merged-bracket-grid[data-active-view="Winners Bracket"] [data-bracket-group="Losers Bracket"] {
            transform: translateX(-44px);
        }

        .merged-bracket-grid[data-active-view="Losers Bracket"] [data-bracket-group="Winners Bracket"] {
            transform: translateX(44px);
        }

        .bracket-focus-toolbar,
        .bracket-jump-nav,
        .bracket-view-toggle {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .bracket-focus-toolbar {
            justify-content: space-between;
            margin-bottom: 14px;
        }

        .bracket-view-toggle .action-btn.active {
            background: linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%);
            box-shadow: 0 14px 24px rgba(37, 99, 235, 0.2);
        }

        .fadeable-completed-match {
            transition: opacity 0.2s ease, filter 0.2s ease;
        }

        body.completed-round-fade-enabled .fadeable-completed-match {
            opacity: 0.42;
            filter: saturate(0.72);
        }

        body.completed-round-fade-enabled .fadeable-completed-match:hover,
        body.completed-round-fade-enabled .fadeable-completed-match:focus-within {
            opacity: 0.8;
            filter: saturate(1);
        }

        .connected-bracket-round {
            display: grid;
            gap: 14px;
        }

        .connected-bracket-lane {
            position: relative;
            display: grid;
            grid-template-rows: repeat(var(--slot-count, 2), var(--bracket-track));
            min-height: calc(var(--slot-count, 2) * var(--bracket-track));
        }

        .connected-bracket-node {
            position: relative;
            display: flex;
            align-items: center;
            padding-block: 6px;
            min-width: 0;
        }

        .connected-bracket-node.has-incoming::before {
            content: '';
            position: absolute;
            left: -18px;
            top: 25%;
            bottom: 25%;
            width: 2px;
            border-radius: 999px;
            background: linear-gradient(180deg, rgba(37, 99, 235, 0.32), rgba(245, 158, 11, 0.28));
        }

        .connected-bracket-node.has-incoming::after {
            content: '';
            position: absolute;
            left: -18px;
            top: 50%;
            width: 18px;
            height: 2px;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.32);
        }

        .connected-bracket-matchup {
            position: relative;
            width: 100%;
            display: grid;
            gap: 10px;
            padding: 10px;
            border-radius: 20px;
            border: 1px solid rgba(37, 99, 235, 0.12);
            background:
                radial-gradient(circle at top right, rgba(245, 158, 11, 0.08), transparent 36%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.99), rgba(246, 249, 255, 0.95));
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.08);
            cursor: pointer;
            transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
        }

        .connected-bracket-matchup:hover {
            transform: translateY(-1px);
        }

        .connected-bracket-matchup.state-scheduled,
        .match-card.state-scheduled {
            background:
                radial-gradient(circle at top right, rgba(255, 255, 255, 0.22), transparent 36%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.99), rgba(246, 249, 255, 0.95));
        }

        .connected-bracket-matchup.state-waiting,
        .match-card.state-waiting {
            background:
                radial-gradient(circle at top right, rgba(59, 130, 246, 0.1), transparent 34%),
                linear-gradient(180deg, rgba(248, 251, 255, 0.99), rgba(238, 245, 255, 0.95));
            border-color: rgba(59, 130, 246, 0.18);
        }

        .connected-bracket-matchup.state-ready,
        .match-card.state-ready {
            background:
                radial-gradient(circle at top right, rgba(245, 158, 11, 0.16), transparent 34%),
                linear-gradient(180deg, rgba(255, 252, 244, 0.99), rgba(255, 247, 224, 0.96));
            border-color: rgba(245, 158, 11, 0.24);
        }

        .connected-bracket-matchup.state-live,
        .match-card.state-live {
            background:
                radial-gradient(circle at top right, rgba(245, 158, 11, 0.18), transparent 34%),
                linear-gradient(180deg, rgba(255, 248, 234, 0.99), rgba(255, 240, 204, 0.96));
            border-color: rgba(245, 158, 11, 0.3);
        }

        .connected-bracket-matchup.state-completed,
        .match-card.state-completed {
            background:
                radial-gradient(circle at top right, rgba(34, 197, 94, 0.14), transparent 34%),
                linear-gradient(180deg, rgba(245, 255, 248, 0.99), rgba(232, 250, 239, 0.96));
            border-color: rgba(34, 197, 94, 0.28);
        }

        .connected-bracket-matchup.has-outgoing::after {
            content: '';
            position: absolute;
            right: -18px;
            top: 50%;
            width: 18px;
            height: 2px;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.32);
        }

        .connected-bracket-matchup.is-placeholder {
            cursor: default;
            border-style: dashed;
            border-color: rgba(100, 116, 139, 0.28);
            background:
                repeating-linear-gradient(
                    135deg,
                    rgba(241, 245, 249, 0.96),
                    rgba(241, 245, 249, 0.96) 10px,
                    rgba(226, 232, 240, 0.96) 10px,
                    rgba(226, 232, 240, 0.96) 20px
                );
            box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.08);
        }

        .connected-bracket-matchup.is-placeholder:hover {
            transform: none;
            border-color: rgba(100, 116, 139, 0.28);
            box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.08);
        }

        .connected-bracket-matchup.is-placeholder .connected-bracket-summary,
        .connected-bracket-matchup.is-placeholder .bracket-slot-name,
        .connected-bracket-matchup.is-placeholder .bracket-slot-label,
        .connected-bracket-matchup.is-placeholder .bracket-slot-hint {
            color: #475569;
        }

        .bracket-view-panel.is-hidden {
            display: none;
        }

        [data-bracket-group].is-hidden {
            display: none !important;
        }

        .page-section[data-section="bracket"]:fullscreen {
            padding: 20px;
            overflow: auto;
            background: linear-gradient(180deg, #edf4ff, #f8fbff);
        }

        .page-section[data-section="bracket"]:fullscreen .connected-bracket-shell {
            max-height: calc(100vh - 15rem);
            overflow: auto;
            padding-right: 16px;
        }

        .page-section[data-section="bracket"]:fullscreen .section-grid {
            align-items: start;
        }

        .connected-bracket-summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            color: #64748b;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 10px;
        }

        .connected-bracket-slot-stack {
            display: grid;
            gap: 6px;
        }

        .bracket-slot--compact {
            padding: 7px 9px;
            margin-bottom: 0;
            border-radius: 14px;
            gap: 6px;
        }

        .bracket-slot--compact .bracket-slot-label {
            font-size: 0.68rem;
        }

        .bracket-slot--compact .bracket-slot-name {
            font-size: 0.88rem;
        }

        .bracket-slot--compact .bracket-slot-hint {
            font-size: 0.72rem;
        }

        .player-link {
            color: #1d4ed8;
            font-weight: 700;
            text-decoration: none;
        }

        .player-link:hover {
            text-decoration: underline;
        }

        .bracket-round {
            min-width: 320px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            position: relative;
        }

        .league-groups-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            align-items: start;
        }

        @media (max-width: 1100px) {
            .league-groups-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal-card {
            width: min(760px, 95vw);
            max-height: 90vh;
            overflow: auto;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
        }

        .tag {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.08);
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 600;
        }

        .surface-note {
            color: #5b6678;
            margin-top: 8px;
        }

        .team-roster-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            margin-top: 16px;
        }

        .team-roster-card {
            display: grid;
            gap: 14px;
        }

        .team-roster-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
        }

        .team-roster-head h3 {
            margin: 0;
        }

        .team-roster-note {
            color: #5b6678;
            margin-top: 6px;
            line-height: 1.55;
        }

        .team-roster-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .team-roster-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0.4rem 0.72rem;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.09);
            border: 1px solid rgba(37, 99, 235, 0.14);
            color: #1d4ed8;
            font-size: 0.84rem;
            font-weight: 600;
        }

        .team-roster-table-shell {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .team-roster-table {
            width: 100%;
            min-width: 520px;
            border-collapse: collapse;
        }

        .team-roster-table th,
        .team-roster-table td {
            padding: 0.72rem 0.6rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.18);
            text-align: left;
            white-space: nowrap;
        }

        .team-roster-table th {
            color: white;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .team-roster-table td.team-roster-name {
            white-space: normal;
            min-width: 170px;
        }

        .section-summary-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            margin-top: 16px;
        }

        .summary-line {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 16px;
            background: rgba(248, 250, 252, 0.88);
            border: 1px solid rgba(15, 23, 42, 0.06);
        }

        .table-shell {
            overflow: auto;
            border-radius: 20px;
        }

        .table-shell > table {
            margin: 0;
        }

        .player-picker {
            max-height: 340px;
            overflow: auto;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 20px;
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.8);
        }

        .player-picker table {
            margin: 10px 0 0;
        }

        .player-picker thead th {
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .player-picker tbody tr {
            cursor: pointer;
        }

        .player-picker tbody tr.is-selected {
            background: rgba(37, 99, 235, 0.1);
        }

        .filter-bar {
            display: grid;
            gap: 12px;
            grid-template-columns: minmax(220px, 1fr) minmax(180px, 220px) minmax(180px, 220px);
            align-items: end;
            margin-bottom: 12px;
        }

        .filter-bar--double {
            grid-template-columns: minmax(220px, 1fr) minmax(180px, 220px);
        }

        .row-toggle {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid rgba(15, 23, 42, 0.12);
            background: rgba(255, 255, 255, 0.88);
            color: #334155;
            font-size: 0.84rem;
            font-weight: 700;
        }

        .row-toggle input {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            margin: 0;
        }

        .row-toggle-indicator {
            width: 18px;
            height: 18px;
            border-radius: 999px;
            border: 2px solid rgba(37, 99, 235, 0.32);
            background: transparent;
            transition: background-color 0.18s ease, border-color 0.18s ease, transform 0.18s ease;
        }

        .row-toggle.is-selected {
            border-color: rgba(37, 99, 235, 0.3);
            background: rgba(37, 99, 235, 0.1);
            color: #1d4ed8;
        }

        .row-toggle.is-selected .row-toggle-indicator {
            background: linear-gradient(180deg, #60a5fa, #2563eb);
            border-color: rgba(37, 99, 235, 0.92);
            transform: scale(1.02);
        }

        .selection-meta {
            color: #5b6678;
            font-size: 0.92rem;
            margin-top: 10px;
        }

        .dense-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            justify-content: flex-end;
        }

        .quick-score-form,
        .bracket-score-form {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .quick-score-form input,
        .bracket-score-form input {
            width: 62px;
            min-width: 62px;
            margin-bottom: 0;
            text-align: center;
            padding-inline: 8px;
        }

        .quick-score-form button,
        .bracket-score-form button {
            margin-left: 2px;
        }

        .quick-score-form.is-disabled,
        .bracket-score-form.is-disabled {
            opacity: 0.55;
        }

        .bracket-round > h3 {
            margin: 0;
            font-size: 0.95rem;
            color: #334155;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .bracket-card {
            position: relative;
            overflow: hidden;
        }

        .bracket-card::before {
            content: '';
            position: absolute;
            inset: 0 auto 0 0;
            width: 4px;
            background: linear-gradient(180deg, rgba(37, 99, 235, 0.75), rgba(245, 158, 11, 0.65));
        }

        .bracket-card-head,
        .bracket-card-meta,
        .bracket-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .bracket-card-head {
            margin-bottom: 12px;
        }

        .bracket-card-meta {
            margin-bottom: 12px;
            color: #64748b;
            font-size: 0.9rem;
        }

        .bracket-link-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.05);
            color: #475569;
            font-size: 0.82rem;
            font-weight: 600;
        }

        .bracket-slot {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 14px;
            margin-bottom: 10px;
            border-radius: 16px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            background: rgba(255, 255, 255, 0.88);
            color: #18212f;
            transition: transform 0.18s ease, border-color 0.18s ease, background-color 0.18s ease, box-shadow 0.18s ease;
        }

        .bracket-slot[data-player-id]:not([data-player-id=""]) {
            cursor: grab;
        }

        .bracket-slot.slot-winner {
            border-color: rgba(22, 163, 74, 0.32);
            background: rgba(240, 253, 244, 0.92);
            box-shadow: inset 0 0 0 1px rgba(22, 163, 74, 0.08);
        }

        .bracket-slot.slot-loser {
            border-color: rgba(239, 68, 68, 0.22);
            background: rgba(254, 242, 242, 0.9);
            color: #7f1d1d;
        }

        .bracket-slot.is-dragging {
            opacity: 0.55;
            transform: scale(0.98);
        }

        .bracket-slot.is-drop-target {
            border-color: rgba(37, 99, 235, 0.5);
            background: rgba(37, 99, 235, 0.08);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
        }

        .bracket-slot-label {
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #64748b;
        }

        .bracket-slot-name {
            flex: 1;
            font-weight: 700;
        }

        .bracket-slot-hint {
            font-size: 0.8rem;
            color: #64748b;
        }

        .bracket-status {
            color: #475569;
            font-size: 0.88rem;
            font-weight: 600;
        }

        .bracket-board-note {
            margin-top: 6px;
            color: #64748b;
            font-size: 0.92rem;
        }

        .section-card h4 {
            margin-bottom: 6px;
        }

        .match-modal-card {
            width: min(780px, 95vw);
            padding: 24px;
            border-radius: 24px;
            background:
                radial-gradient(circle at top right, rgba(245, 158, 11, 0.12), transparent 28%),
                radial-gradient(circle at bottom left, rgba(37, 99, 235, 0.1), transparent 28%),
                #ffffff;
        }

        .match-modal-subtitle {
            margin: 6px 0 0;
            color: #64748b;
        }

        .match-modal-summary {
            display: grid;
            gap: 16px;
            margin: 18px 0 20px;
            padding: 18px;
            border-radius: 22px;
            border: 1px solid rgba(37, 99, 235, 0.12);
            background: rgba(248, 251, 255, 0.94);
        }

        .match-modal-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            justify-content: space-between;
        }

        .match-chip-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .match-chip {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.06);
            color: #475569;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .match-player-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .match-player-card {
            display: grid;
            gap: 6px;
            padding: 14px 16px;
            border-radius: 18px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            background: rgba(255, 255, 255, 0.9);
        }

        .match-player-card span {
            color: #64748b;
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .match-modal-body {
            display: grid;
            gap: 16px;
        }

        .match-modal-state {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            padding: 12px 14px;
            border-radius: 16px;
            background: rgba(37, 99, 235, 0.06);
            border: 1px solid rgba(37, 99, 235, 0.12);
        }

        .match-modal-state-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.08);
            color: #334155;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .match-modal-state-badge[data-tone="dirty"] {
            background: rgba(245, 158, 11, 0.16);
            color: #b45309;
        }

        .match-modal-state-badge[data-tone="saving"] {
            background: rgba(37, 99, 235, 0.16);
            color: #1d4ed8;
        }

        .match-modal-state-badge[data-tone="saved"] {
            background: rgba(22, 163, 74, 0.16);
            color: #15803d;
        }

        .match-modal-state-badge[data-tone="warning"] {
            background: rgba(220, 38, 38, 0.12);
            color: #b91c1c;
        }

        .match-modal-section {
            padding: 18px;
            border-radius: 22px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            background: rgba(255, 255, 255, 0.92);
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.05);
        }

        .match-modal-section h4 {
            margin: 0 0 6px;
        }

        .match-modal-section p {
            margin: 0 0 14px;
            color: #64748b;
        }

        .match-score-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .match-score-field,
        .schedule-field {
            display: grid;
            gap: 8px;
        }

        .match-score-field input,
        .schedule-field input {
            margin-bottom: 0;
        }

        .match-score-field input {
            font-size: 1.2rem;
            text-align: center;
            font-weight: 700;
        }

        .schedule-field--time input[type="time"] {
            min-height: 48px;
            font-weight: 600;
        }

        @media (max-width: 900px) {
            .dense-actions {
                justify-content: flex-start;
            }

            .bracket-focus-toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .team-roster-head {
                flex-direction: column;
                align-items: stretch;
            }

            .bracket-jump-nav,
            .bracket-view-toggle {
                flex-wrap: nowrap;
                overflow-x: auto;
                padding-bottom: 0.35rem;
            }

            .merged-bracket-grid,
            .merged-bracket-grid.is-single-view {
                display: flex;
                flex-direction: column;
                gap: 1rem;
            }

            .merged-bracket-grid > [data-bracket-group] {
                grid-area: auto;
                width: 100%;
                justify-self: stretch;
                margin: 0;
            }

            .merged-bracket-grid[data-active-view="merged"] [data-bracket-group="Opening Round"],
            .merged-bracket-grid[data-active-view="Winners Bracket"] [data-bracket-group="Opening Round"],
            .merged-bracket-grid[data-active-view="Losers Bracket"] [data-bracket-group="Opening Round"] {
                width: 100%;
            }

            .connected-bracket-shell {
                overflow: visible;
            }

            .bracket-round {
                min-width: 0;
                width: 100%;
                gap: 1rem;
            }

            .connected-bracket {
                display: flex;
                flex-direction: column;
                gap: 1rem;
                min-width: 0;
                width: 100%;
            }

            [data-bracket-group="Winners Bracket"] .connected-bracket,
            [data-bracket-group="Losers Bracket"] .connected-bracket {
                --bracket-track: auto;
            }

            .merged-bracket-grid[data-active-view="merged"] [data-bracket-group="Losers Bracket"] .connected-bracket,
            .merged-bracket-grid[data-active-view="Losers Bracket"] [data-bracket-group="Losers Bracket"] .connected-bracket {
                flex-direction: column;
                gap: 1rem;
                min-width: 0;
                width: 100%;
            }

            .connected-bracket-round,
            .merged-bracket-grid[data-active-view="merged"] [data-bracket-group="Losers Bracket"] .connected-bracket-round,
            .merged-bracket-grid[data-active-view="Losers Bracket"] [data-bracket-group="Losers Bracket"] .connected-bracket-round {
                display: grid;
                gap: 0.9rem;
                min-width: 0;
                width: 100%;
                flex: 0 0 auto;
            }

            .connected-bracket-round h3,
            .bracket-round h3 {
                font-size: 0.78rem;
                line-height: 1.3;
                letter-spacing: 0.03em;
                white-space: normal;
                overflow-wrap: anywhere;
                text-transform: none;
                text-align: left;
            }

            .merged-bracket-grid[data-active-view="merged"] [data-bracket-group="Losers Bracket"] .connected-bracket-round h3,
            .merged-bracket-grid[data-active-view="Losers Bracket"] [data-bracket-group="Losers Bracket"] .connected-bracket-round h3 {
                text-align: left;
            }

            .connected-bracket-lane {
                display: flex;
                flex-direction: column;
                gap: 0.85rem;
                grid-template-rows: none;
                min-height: 0;
            }

            .connected-bracket-node {
                display: block;
                padding-block: 0;
            }

            .connected-bracket-node.has-incoming::before,
            .connected-bracket-node.has-incoming::after,
            .connected-bracket-matchup.has-outgoing::after {
                display: none;
            }

            .connected-bracket-matchup {
                padding: 8px;
                border-radius: 16px;
            }

            .connected-bracket-summary {
                font-size: 0.72rem;
                flex-wrap: wrap;
            }

            .bracket-slot--compact {
                padding: 6px 8px;
            }

            .bracket-slot--compact .bracket-slot-name {
                font-size: 0.82rem;
                white-space: normal;
                overflow: visible;
                text-overflow: initial;
                overflow-wrap: anywhere;
                line-height: 1.25;
            }

            .bracket-slot--compact .bracket-slot-hint {
                font-size: 0.68rem;
            }

            .bracket-grid {
                display: grid;
                grid-template-columns: 1fr;
                gap: 1rem;
                overflow: visible;
                padding-bottom: 8px;
            }

            .bracket-grid .bracket-round {
                display: grid;
                gap: 1rem;
            }

            .bracket-grid .match-card {
                width: 100%;
            }
        }

        @media (max-width: 640px) {
            .quick-score-form,
            .bracket-score-form {
                align-items: stretch;
            }

            .filter-bar {
                grid-template-columns: 1fr;
            }

            .league-groups-grid {
                grid-template-columns: 1fr;
            }

            .team-roster-grid {
                grid-template-columns: 1fr;
            }

            .connected-bracket {
                gap: 0.85rem;
            }

            .connected-bracket-round h3,
            .bracket-round h3 {
                font-size: 0.74rem;
            }

            .bracket-grid .match-card,
            .connected-bracket-matchup {
                padding: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="sidenav" id="sidenav">
        <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
        <a href="manage_players.php">Manage Players</a>
        <a href="manage_tournaments.php">Manage Tournaments</a>
        <a href="manage_users.php">Manage Users</a>
        <a href="../main.php">Back to Website</a>
    </div>

    <div class="container">
        <div class="header-actions">
            <span style="font-size:30px;cursor:pointer" onclick="openNav()">&#9776;</span>
            <h1><?php echo htmlspecialchars($tournament['tour_title']); ?></h1>
            <a href="manage_tournaments.php" class="back-btn"><?php echo htmlspecialchars(admin_text('Back to Tournaments', 'Turnuvalara Dön')); ?></a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message">
                <?php
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <?php
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="section-toggle">
            <button type="button" class="active" data-section-button="details"><?php echo htmlspecialchars(admin_text('Tournament Details', 'Turnuva Ayrıntıları')); ?></button>
            <button type="button" data-section-button="players"><?php echo htmlspecialchars(admin_text('Players', 'Oyuncular')); ?></button>
            <button type="button" data-section-button="matches"><?php echo htmlspecialchars(admin_text('Matches', 'Maçlar')); ?></button>
            <?php if ($tournament['tour_type'] === 'Round Robin'): ?>
                <button type="button" data-section-button="standings"><?php echo htmlspecialchars(admin_text('Standings', 'Puan Durumu')); ?></button>
            <?php endif; ?>
            <?php if ($tournament['tour_type'] === 'League'): ?>
                <button type="button" data-section-button="groups"><?php echo htmlspecialchars(admin_text('Groups', 'Gruplar')); ?></button>
            <?php endif; ?>
            <?php if ($tournament['tour_type'] === 'Group'): ?>
                <button type="button" data-section-button="teams"><?php echo htmlspecialchars(admin_text('Teams', 'Takımlar')); ?></button>
            <?php endif; ?>
            <?php if (!empty($knockoutBracketGroups)): ?>
                <button type="button" data-section-button="bracket"><?php echo htmlspecialchars(admin_text('Tournament Bracket', 'Turnuva Braketi')); ?></button>
                <button type="button" data-section-button="bracket_board"><?php echo htmlspecialchars(admin_text('Bracket Board', 'Braket Panosu')); ?></button>
            <?php endif; ?>
        </div>

        <section class="page-section active" data-section="details">
        <div class="callout">
            <?php echo htmlspecialchars(admin_text('Registration can stay open while you collect entrants. Save roster/status changes here, then use', 'Katılımcıları toplarken kayıt açık kalabilir. Kadro/durum değişikliklerini burada kaydedin, ardından')); ?>
            <strong><?php echo htmlspecialchars($structureGenerated ? admin_text('Rebuild Structure', 'Yapıyı Yeniden Oluştur') : admin_text('Generate Structure', 'Yapıyı Oluştur')); ?></strong>
            <?php echo htmlspecialchars(admin_text('after registration closes to create fixtures from the current non-withdrawn entrants. Once scores have been recorded, automatic structure rebuilds are blocked, but managers and admins can still adjust individual matches manually from the tables below.', 'kayıt kapandıktan sonra mevcut, çekilmemiş katılımcılardan fikstür oluşturmak için kullanın. Skorlar girildikten sonra otomatik yapı yeniden oluşturma engellenir, ancak yönetici ve menajerler aşağıdaki tablolardan tekil maçları yine de manuel olarak düzenleyebilir.')); ?>
        </div>

        <div class="info-grid">
            <div class="info-card"><strong><?php echo htmlspecialchars(admin_text('Type', 'Tür')); ?></strong><br><?php echo htmlspecialchars(admin_tournament_type_label((string) $tournament['tour_type'])); ?></div>
            <div class="info-card"><strong><?php echo htmlspecialchars(admin_text('Start Date', 'Başlangıç Tarihi')); ?></strong><br><?php echo htmlspecialchars(date('Y-m-d', strtotime($tournament['tour_creationDate']))); ?></div>
            <div class="info-card"><strong><?php echo htmlspecialchars(admin_text('End Date', 'Bitiş Tarihi')); ?></strong><br><?php echo htmlspecialchars(date('Y-m-d', strtotime($tournament['tour_endDate']))); ?></div>
            <div class="info-card"><strong><?php echo htmlspecialchars(admin_text('Registration Closes', 'Kayıt Bitişi')); ?></strong><br><?php echo htmlspecialchars(date('Y-m-d', strtotime($tournament['registration_close_at'] ?: $tournament['tour_creationDate']))); ?></div>
            <div class="info-card"><strong><?php echo htmlspecialchars(admin_text('Status', 'Durum')); ?></strong><br><span class="<?php echo $status['class']; ?>"><?php echo htmlspecialchars(admin_tournament_status_label((string) ($tournament['status'] ?? ''))); ?></span></div>
            <div class="info-card"><strong><?php echo htmlspecialchars(admin_text('Players', 'Oyuncular')); ?></strong><br><?php echo (int) $tournament['player_count']; ?></div>
            <div class="info-card"><strong><?php echo htmlspecialchars($tournament['tour_type'] === 'Group' ? admin_text('Fixtures', 'Fikstür') : admin_text('Matches', 'Maçlar')); ?></strong><br><?php echo $displayMatchCount; ?></div>
        </div>

        <div class="section-grid" style="margin-top:24px;">
            <div class="section-card">
                <h3><?php echo htmlspecialchars(admin_text('Edit Tournament', 'Turnuvayı Düzenle')); ?></h3>
                <form action="../../services/update_tournament.php" method="post" id="editTournamentForm">
                    <input type="hidden" name="tour_id" value="<?php echo (int) $tourId; ?>">
                    <div class="inline-grid">
                        <div>
                            <label for="tour_title"><?php echo htmlspecialchars(admin_text('Tournament Title', 'Turnuva Başlığı')); ?></label>
                            <input type="text" name="tour_title" id="tour_title" value="<?php echo htmlspecialchars($tournament['tour_title']); ?>" required>
                        </div>
                        <div>
                            <label for="tour_startDate"><?php echo htmlspecialchars(admin_text('Start Date', 'Başlangıç Tarihi')); ?></label>
                            <input type="date" name="tour_startDate" id="tour_startDate" value="<?php echo htmlspecialchars(date('Y-m-d', strtotime($tournament['tour_creationDate']))); ?>" required>
                        </div>
                        <div>
                            <label for="tour_endDate"><?php echo htmlspecialchars(admin_text('End Date', 'Bitiş Tarihi')); ?></label>
                            <input type="date" name="tour_endDate" id="tour_endDate" value="<?php echo htmlspecialchars(date('Y-m-d', strtotime($tournament['tour_endDate']))); ?>" required>
                        </div>
                        <div>
                            <label for="registration_open_at"><?php echo htmlspecialchars(admin_text('Registration Opens', 'Kayıt Başlangıcı')); ?></label>
                            <input type="date" name="registration_open_at" id="registration_open_at" value="<?php echo htmlspecialchars(date('Y-m-d', strtotime($tournament['registration_open_at'] ?: $tournament['tour_creationDate']))); ?>" required>
                        </div>
                        <div>
                            <label for="registration_close_at"><?php echo htmlspecialchars(admin_text('Registration Closes', 'Kayıt Bitişi')); ?></label>
                            <input type="date" name="registration_close_at" id="registration_close_at" value="<?php echo htmlspecialchars(date('Y-m-d', strtotime($tournament['registration_close_at'] ?: $tournament['tour_creationDate']))); ?>" required>
                        </div>
                        <?php if ($tournament['tour_type'] === 'League'): ?>
                            <div>
                                <label for="group_count"><?php echo htmlspecialchars(admin_text('Group Count', 'Grup Sayısı')); ?></label>
                                <input type="number" name="group_count" id="group_count" min="2" value="<?php echo (int) $tournament['group_count']; ?>">
                            </div>
                            <div>
                                <label for="advancers_per_group"><?php echo htmlspecialchars(admin_text('Advancers per Group', 'Grup Başına Yükselen')); ?></label>
                                <input type="number" name="advancers_per_group" id="advancers_per_group" min="1" value="<?php echo (int) $tournament['advancers_per_group']; ?>">
                            </div>
                        <?php elseif ($tournament['tour_type'] === 'Group'): ?>
                            <div>
                                <label for="team_count"><?php echo htmlspecialchars(admin_text('Team Count', 'Takım Sayısı')); ?></label>
                                <input type="number" name="team_count" id="team_count" min="2" max="2" value="<?php echo max(2, (int) $tournament['team_count']); ?>" readonly>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-buttons" style="margin-top:16px;">
                        <button type="submit" class="submit-btn"><?php echo htmlspecialchars(admin_text('Save Tournament Details', 'Turnuva Ayrıntılarını Kaydet')); ?></button>
                    </div>
                </form>
            </div>

            <div class="section-card">
                <h3><?php echo htmlspecialchars(admin_text('Operational Snapshot', 'Operasyon Özeti')); ?></h3>
                <div class="section-summary-grid">
                    <div class="summary-line"><strong><?php echo htmlspecialchars(admin_text('Winner', 'Kazanan')); ?></strong><span><?php echo htmlspecialchars(admin_translate_value((string) ($tournament['winner_label'] ?? 'TBD'))); ?></span></div>
                    <div class="summary-line"><strong><?php echo htmlspecialchars(admin_text('Visibility', 'Görünürlük')); ?></strong><span><?php echo htmlspecialchars(admin_translate_value((int) ($tournament['is_public'] ?? 0) === 1 ? 'Public' : 'Private')); ?></span></div>
                    <div class="summary-line"><strong><?php echo htmlspecialchars(admin_text('Structure', 'Yapı')); ?></strong><span><?php echo htmlspecialchars(admin_translate_value($structureGenerated ? 'Generated' : 'Not generated')); ?></span></div>
                    <div class="summary-line"><strong><?php echo htmlspecialchars(admin_text('Bracket Style', 'Braket Stili')); ?></strong><span><?php echo htmlspecialchars($isDoubleElimination ? admin_translate_value('Double elimination') : (($tournament['tour_type'] === 'League' && !empty($knockoutBracketGroups)) ? admin_translate_value('League knockout') : admin_tournament_type_label((string) $tournament['tour_type']))); ?></span></div>
                </div>
            </div>
        </div>
        </section>

        <section class="page-section" data-section="players">
            <div class="header-actions">
                <div>
                    <h2><?php echo htmlspecialchars(admin_text('Players', 'Oyuncular')); ?></h2>
                    <p class="surface-note"><?php echo htmlspecialchars(admin_text('Manage roster state and review placement output without mixing it into fixture editing.', 'Kadro durumunu yönetin ve yerleşim çıktısını fikstür düzenlemeyle karıştırmadan inceleyin.')); ?></p>
                </div>
            </div>
            <div class="subsection-toggle" aria-label="<?php echo htmlspecialchars(admin_text('Player management panels', 'Oyuncu yönetim panelleri')); ?>">
                <button type="button" class="active" data-players-panel-button="roster"><?php echo htmlspecialchars(admin_text('Roster Management', 'Kadro Yönetimi')); ?></button>
                <button type="button" data-players-panel-button="snapshot"><?php echo htmlspecialchars(admin_text('Players Snapshot', 'Oyuncu Özeti')); ?></button>
            </div>
            <div class="section-panel-stack">
            <div class="section-card bracket-view-panel" data-players-panel="roster">
                <h3><?php echo htmlspecialchars(admin_text('Roster Management', 'Kadro Yönetimi')); ?></h3>
                <form action="../../services/update_tournament.php" method="post" id="playerRosterForm">
                    <input type="hidden" name="tour_id" value="<?php echo (int) $tourId; ?>">
                    <h4 style="margin-top:16px;"><?php echo htmlspecialchars(admin_text('Current Players', 'Mevcut Oyuncular')); ?></h4>
                    <div class="filter-bar filter-bar--double">
                        <div>
                            <label for="currentPlayerFilter"><?php echo htmlspecialchars(admin_text('Filter current players', 'Mevcut oyuncuları filtrele')); ?></label>
                            <input type="text" id="currentPlayerFilter" placeholder="<?php echo htmlspecialchars(admin_text('Search the current tournament roster', 'Mevcut turnuva kadrosunda ara')); ?>">
                        </div>
                        <div>
                            <label for="currentPlayerSort"><?php echo htmlspecialchars(admin_text('Sort current players', 'Mevcut oyuncuları sırala')); ?></label>
                            <select id="currentPlayerSort">
                                <option value="name_asc"><?php echo htmlspecialchars(admin_text('Player A-Z', 'Oyuncu A-Z')); ?></option>
                                <option value="name_desc"><?php echo htmlspecialchars(admin_text('Player Z-A', 'Oyuncu Z-A')); ?></option>
                                <option value="status"><?php echo htmlspecialchars(admin_text('Status', 'Durum')); ?></option>
                                <?php if ($tournament['tour_type'] === 'League'): ?>
                                    <option value="group"><?php echo htmlspecialchars(admin_text('Group', 'Grup')); ?></option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <div class="table-shell">
                    <table class="players-table">
                        <thead>
                            <tr>
                                <th><?php echo htmlspecialchars(admin_text('Name', 'Ad')); ?></th>
                                <th><?php echo htmlspecialchars(admin_text('Status', 'Durum')); ?></th>
                                <?php if ($tournament['tour_type'] === 'League'): ?><th><?php echo htmlspecialchars(admin_text('Group', 'Grup')); ?></th><?php endif; ?>
                                <th><?php echo htmlspecialchars($removeActionLabel); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($players as $player): ?>
                                <tr
                                    data-selectable-row
                                    data-selection-target="remove_player_<?php echo (int) $player['plr_idNum']; ?>"
                                    data-current-player-row
                                    data-player-id="<?php echo (int) $player['plr_idNum']; ?>"
                                    data-player-name="<?php echo htmlspecialchars(strtolower(player_name($player))); ?>"
                                    data-player-status="<?php echo htmlspecialchars(strtolower(admin_player_status_label((string) $player['player_status']))); ?>"
                                    data-player-group="<?php echo htmlspecialchars((string) ($player['group_number'] ?? '')); ?>"
                                >
                                    <td><?php echo htmlspecialchars(player_name($player)); ?></td>
                                    <td>
                                        <select name="player_status[<?php echo (int) $player['plr_idNum']; ?>]">
                                            <?php foreach (['Active', 'Registered', 'Withdrawn'] as $playerStatus): ?>
                                                <option value="<?php echo $playerStatus; ?>" <?php echo $player['player_status'] === $playerStatus ? 'selected' : ''; ?>><?php echo htmlspecialchars(admin_player_status_label($playerStatus)); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <?php if ($tournament['tour_type'] === 'League'): ?><td><?php echo $player['group_number'] !== null ? (int) $player['group_number'] : '-'; ?></td><?php endif; ?>
                                    <td>
                                        <label class="row-toggle" data-row-toggle>
                                            <input type="checkbox" id="remove_player_<?php echo (int) $player['plr_idNum']; ?>" name="remove_players[]" value="<?php echo (int) $player['plr_idNum']; ?>">
                                            <span class="row-toggle-indicator" aria-hidden="true"></span>
                                            <span><?php echo htmlspecialchars($removeActionLabel); ?></span>
                                        </label>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                    <div class="selection-meta" id="removePlayerSelectionCount"><?php echo htmlspecialchars($removeSelectionEmptyText); ?></div>
                    <?php if ($rosterUsesWithdrawals): ?>
                        <p class="surface-note" style="margin-top: 10px;">
                            <?php echo htmlspecialchars(admin_text('This tournament has already started or recorded results. Removing a player now marks them withdrawn and clears only unfinished future slots so completed match history stays intact.', 'Bu turnuva başladı veya sonuç kaydetti. Oyuncuyu şimdi kaldırmak, tamamlanmış maç geçmişi korunurken oyuncuyu çekildi olarak işaretler ve yalnızca tamamlanmamış gelecek sıralarını temizler.')); ?>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($availablePlayers)): ?>
                        <h4 style="margin-top:16px;"><?php echo htmlspecialchars(admin_text('Add Players', 'Oyuncu Ekle')); ?></h4>
                        <div class="filter-bar">
                            <div>
                                <label for="newPlayerFilter"><?php echo htmlspecialchars(admin_text('Filter available players', 'Uygun oyuncuları filtrele')); ?></label>
                                <input type="text" id="newPlayerFilter" placeholder="<?php echo htmlspecialchars(admin_text('Type a player name to narrow the add list', 'Ekleme listesini daraltmak için oyuncu adı yazın')); ?>">
                            </div>
                            <div>
                                <label for="newPlayerSort"><?php echo htmlspecialchars(admin_text('Sort available players', 'Uygun oyuncuları sırala')); ?></label>
                                <select id="newPlayerSort">
                                    <option value="name_asc"><?php echo htmlspecialchars(admin_text('Player A-Z', 'Oyuncu A-Z')); ?></option>
                                    <option value="name_desc"><?php echo htmlspecialchars(admin_text('Player Z-A', 'Oyuncu Z-A')); ?></option>
                                    <option value="id_asc"><?php echo htmlspecialchars(admin_text('Oldest first', 'En eski önce')); ?></option>
                                    <option value="id_desc"><?php echo htmlspecialchars(admin_text('Newest first', 'En yeni önce')); ?></option>
                                </select>
                            </div>
                            <div>
                                <label for="new_players_status"><?php echo htmlspecialchars(admin_text('Add selected players as', 'Seçili oyuncuları şu olarak ekle')); ?></label>
                                <select name="new_players_status" id="new_players_status">
                                    <option value="Registered" <?php echo $newPlayersDefaultStatus === 'Registered' ? 'selected' : ''; ?>><?php echo htmlspecialchars(admin_text('Registered entrants', 'Kayıtlı katılımcılar')); ?></option>
                                    <option value="Active" <?php echo $newPlayersDefaultStatus === 'Active' ? 'selected' : ''; ?>><?php echo htmlspecialchars(admin_text('Active competition roster', 'Aktif yarışma kadrosu')); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="player-picker">
                            <table class="players-table">
                                <thead>
                                    <tr>
                                        <th><?php echo htmlspecialchars(admin_text('Add', 'Ekle')); ?></th>
                                        <th><?php echo htmlspecialchars(admin_text('Player Name', 'Oyuncu Adı')); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($availablePlayers as $availablePlayer): ?>
                                        <tr
                                            data-player-add-row
                                            data-player-id="<?php echo (int) $availablePlayer['plr_idNum']; ?>"
                                            data-player-name="<?php echo htmlspecialchars(strtolower(player_name($availablePlayer))); ?>"
                                            data-selectable-row
                                            data-selection-target="new_player_<?php echo (int) $availablePlayer['plr_idNum']; ?>"
                                        >
                                            <td>
                                                <label class="row-toggle" data-row-toggle>
                                                    <input type="checkbox" id="new_player_<?php echo (int) $availablePlayer['plr_idNum']; ?>" name="new_players[]" value="<?php echo (int) $availablePlayer['plr_idNum']; ?>">
                                                    <span class="row-toggle-indicator" aria-hidden="true"></span>
                                                    <span><?php echo htmlspecialchars(admin_text('Add', 'Ekle')); ?></span>
                                                </label>
                                            </td>
                                            <td><?php echo htmlspecialchars(player_name($availablePlayer)); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="selection-meta" id="newPlayerSelectionCount"><?php echo htmlspecialchars(admin_text('No new players selected yet.', 'Henüz eklenecek yeni oyuncu seçilmedi.')); ?></div>
                    <?php endif; ?>

                    <div class="form-buttons" style="margin-top:16px;">
                        <button type="submit" class="submit-btn"><?php echo htmlspecialchars(admin_text('Save Roster Changes', 'Kadro Değişikliklerini Kaydet')); ?></button>
                    </div>
                </form>
            </div>

            <div class="section-card bracket-view-panel is-hidden" data-players-panel="snapshot">
                <h3><?php echo htmlspecialchars(admin_text('Players Snapshot', 'Oyuncu Özeti')); ?></h3>
                <table class="players-table">
                    <thead>
                        <tr>
                            <th><?php echo htmlspecialchars(admin_text('Name', 'Ad')); ?></th>
                            <th><?php echo htmlspecialchars(admin_text('Status', 'Durum')); ?></th>
                            <?php if ($tournament['tour_type'] === 'League'): ?><th><?php echo htmlspecialchars(admin_text('Group', 'Grup')); ?></th><?php endif; ?>
                            <?php if (!empty($placementPlayers)): ?><th><?php echo htmlspecialchars(admin_text('Placement', 'Derece')); ?></th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($players as $player): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(player_name($player)); ?></td>
                                <td><?php echo htmlspecialchars(admin_player_status_label((string) $player['player_status'])); ?></td>
                                <?php if ($tournament['tour_type'] === 'League'): ?><td><?php echo $player['group_number'] !== null ? (int) $player['group_number'] : '-'; ?></td><?php endif; ?>
                                <?php if (!empty($placementPlayers)): ?>
                                    <td><?php echo htmlspecialchars((string) ($player['placement_label'] ?: ($player['final_rank'] !== null ? ('#' . (int) $player['final_rank']) : '-'))); ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if (!empty($placementPlayers)): ?>
                    <h4 style="margin-top:18px;"><?php echo htmlspecialchars(admin_text('Placement Snapshot', 'Derece Özeti')); ?></h4>
                    <table class="players-table">
                        <thead>
                            <tr>
                                <th><?php echo htmlspecialchars(admin_text('Rank', 'Sıra')); ?></th>
                                <th><?php echo htmlspecialchars(admin_text('Player', 'Oyuncu')); ?></th>
                                <th><?php echo htmlspecialchars(admin_text('Placement', 'Derece')); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($placementPlayers as $placementPlayer): ?>
                                <tr>
                                    <td><?php echo $placementPlayer['final_rank'] !== null ? (int) $placementPlayer['final_rank'] : '-'; ?></td>
                                    <td><?php echo htmlspecialchars(player_name($placementPlayer)); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($placementPlayer['placement_label'] ?? '-')); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        </section>

        <section class="page-section" data-section="matches">
            <div class="header-actions">
                <div>
                    <h2><?php echo htmlspecialchars($tournament['tour_type'] === 'Group' ? ($groupUsesIndividualMatches ? admin_text('Player Fixtures', 'Oyuncu Fikstürü') : admin_text('Team Fixtures', 'Takım Fikstürü')) : admin_text('Matches', 'Maçlar')); ?></h2>
                    <p class="surface-note">
                        <?php if ($tournament['tour_type'] === 'Group' && !$groupUsesIndividualMatches && !empty($teamMatches)): ?>
                            <?php echo htmlspecialchars(admin_text('Team results can be recorded directly in the table below.', 'Takım sonuçları doğrudan aşağıdaki tabloda kaydedilebilir.')); ?>
                        <?php elseif ($tournament['tour_type'] === 'Group'): ?>
                            <?php echo htmlspecialchars(admin_text('Group tournaments now score player-versus-player matches across the two team rosters, while the team standings section rolls those results back up to the team view.', 'Grup turnuvaları artık iki takım kadrosu arasında oyuncu-oyuncuya maçları puanlıyor; takım puan durumu bölümü de bu sonuçları yeniden takım görünümüne topluyor.')); ?>
                        <?php else: ?>
                            <?php echo htmlspecialchars(admin_text('Record scores directly in the table when you want speed, or open the detail modal for deeper edits.', 'Hız gerektiğinde skorları doğrudan tabloda kaydedin veya daha ayrıntılı düzenlemeler için ayrıntı penceresini açın.')); ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="dense-actions">
                    <?php if ($canStartTournament): ?>
                        <button type="button" class="submit-btn" id="startTournamentBtn"><?php echo htmlspecialchars(admin_text('Start Tournament', 'Turnuvayı Başlat')); ?></button>
                    <?php endif; ?>
                    <button type="button" class="action-btn" id="generateStructureBtn"><?php echo htmlspecialchars($structureGenerated ? admin_text('Rebuild Structure', 'Yapıyı Yeniden Oluştur') : admin_text('Generate Structure', 'Yapıyı Oluştur')); ?></button>
                    <?php if (in_array($tournament['tour_type'], ['Round Robin', 'League'], true)): ?>
                        <button type="button" class="action-btn" id="openLeagueToolsBtn"><?php echo htmlspecialchars(admin_text('Reschedule Structure', 'Yapıyı Yeniden Planla')); ?></button>
                    <?php endif; ?>
                    <?php if ($tournament['tour_type'] !== 'Group'): ?>
                        <button type="button" class="action-btn" id="addMatchBtn"><?php echo htmlspecialchars(admin_text('Add Match', 'Maç Ekle')); ?></button>
                    <?php endif; ?>
                    <button type="button" class="action-btn" id="refreshMatchesBtn"><?php echo htmlspecialchars(admin_text('Refresh', 'Yenile')); ?></button>
                </div>
            </div>

            <?php if ($tournament['tour_type'] === 'Group' && !$groupUsesIndividualMatches && !empty($teamMatches)): ?>
                <div class="table-shell">
                <table class="matches-table">
                    <thead>
                        <tr>
                            <th><?php echo htmlspecialchars(admin_text('Round', 'Tur')); ?></th>
                            <th><?php echo htmlspecialchars(admin_text('Date', 'Tarih')); ?></th>
                            <th><?php echo htmlspecialchars(admin_text('Time', 'Saat')); ?></th>
                            <th><?php echo htmlspecialchars(admin_text('Team 1', 'Takım 1')); ?></th>
                            <th><?php echo htmlspecialchars(admin_text('Score', 'Skor')); ?></th>
                            <th><?php echo htmlspecialchars(admin_text('Team 2', 'Takım 2')); ?></th>
                            <th><?php echo htmlspecialchars(admin_text('Status', 'Durum')); ?></th>
                            <th><?php echo htmlspecialchars(admin_text('Record Result', 'Sonucu Kaydet')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teamMatches as $teamMatch): ?>
                            <tr>
                                <td><?php echo (int) $teamMatch['round_number']; ?></td>
                                <td><?php echo htmlspecialchars($teamMatch['match_date']); ?></td>
                                <td><?php echo htmlspecialchars($teamMatch['match_time']); ?></td>
                                <td><?php echo htmlspecialchars((string) ($teamMatch['team1_name'] ?? admin_translate_value('TBD'))); ?></td>
                                <td>
                                    <?php if ($teamMatch['match_status'] === 'Completed'): ?>
                                        <?php echo htmlspecialchars((string) $teamMatch['team1_score']); ?> - <?php echo htmlspecialchars((string) $teamMatch['team2_score']); ?>
                                    <?php else: ?>
                                        vs
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars((string) ($teamMatch['team2_name'] ?? admin_translate_value('TBD'))); ?></td>
                                <td><?php echo htmlspecialchars(admin_match_status_label((string) $teamMatch['match_status'])); ?></td>
                                <td>
                                    <form onsubmit="return saveTeamMatchResult(event, <?php echo (int) $teamMatch['team_match_id']; ?>)" style="display:flex;gap:6px;align-items:center;">
                                        <input type="number" min="0" name="team1_score" style="width:72px;">
                                        <input type="number" min="0" name="team2_score" style="width:72px;">
                                        <button type="submit" class="action-btn"><?php echo htmlspecialchars(admin_text('Save', 'Kaydet')); ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php else: ?>
                <div class="table-shell">
                <table class="matches-table">
                    <thead>
                        <tr>
                            <th><?php echo htmlspecialchars(admin_text('Match #', 'Maç #')); ?></th>
                            <th><?php echo htmlspecialchars(admin_text('Round', 'Tur')); ?></th>
                            <th><?php echo htmlspecialchars(admin_text('Date', 'Tarih')); ?></th>
                            <th><?php echo htmlspecialchars(admin_text('Time', 'Saat')); ?></th>
                            <th><?php echo htmlspecialchars(admin_text('Bracket', 'Braket')); ?></th>
                            <th><?php echo htmlspecialchars(admin_text('Group', 'Grup')); ?></th>
                            <?php if ($tournament['tour_type'] === 'Group'): ?><th><?php echo htmlspecialchars(admin_text('Team 1', 'Takım 1')); ?></th><?php endif; ?>
                            <th><?php echo htmlspecialchars(admin_text('Player 1', 'Oyuncu 1')); ?></th>
                            <?php if ($tournament['tour_type'] === 'Group'): ?><th><?php echo htmlspecialchars(admin_text('Team 2', 'Takım 2')); ?></th><?php endif; ?>
                            <th><?php echo htmlspecialchars(admin_text('Player 2', 'Oyuncu 2')); ?></th>
                            <th><?php echo htmlspecialchars(admin_text('Status', 'Durum')); ?></th>
                            <th><?php echo htmlspecialchars(admin_text('Quick Score', 'Hızlı Skor')); ?></th>
                            <th><?php echo htmlspecialchars(admin_text('Action', 'İşlem')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matches as $match): ?>
                            <tr>
                                <td><?php echo (int) $matchNumbersById[(int) $match['match_id']]; ?></td>
                                <td><?php echo (int) $match['round_number']; ?></td>
                                <td><?php echo htmlspecialchars($match['match_date']); ?></td>
                                <td><?php echo htmlspecialchars($match['match_time']); ?></td>
                                <td><?php echo htmlspecialchars($match['bracket'] ? admin_translate_value((string) $match['bracket']) : '-'); ?></td>
                                <td><?php echo $match['group_number'] !== null ? (int) $match['group_number'] : '-'; ?></td>
                                <?php if ($tournament['tour_type'] === 'Group'): ?><td><?php echo htmlspecialchars((string) ($match['player1_team_name'] ?? '-')); ?></td><?php endif; ?>
                                <td><?php echo htmlspecialchars(tournament_admin_match_label($match, 'player1', $matchNumbersById)); ?></td>
                                <?php if ($tournament['tour_type'] === 'Group'): ?><td><?php echo htmlspecialchars((string) ($match['player2_team_name'] ?? '-')); ?></td><?php endif; ?>
                                <td><?php echo htmlspecialchars(tournament_admin_match_label($match, 'player2', $matchNumbersById)); ?></td>
                                <td><?php echo htmlspecialchars(admin_match_status_label((string) $match['match_status'])); ?></td>
                                <td>
                                    <?php $canQuickScore = !empty($match['player1_id']) && !empty($match['player2_id']); ?>
                                    <form class="quick-score-form <?php echo $canQuickScore ? '' : 'is-disabled'; ?>" onsubmit="return saveQuickMatchResult(event, <?php echo (int) $match['match_id']; ?>)">
                                        <input type="number" min="0" name="player1_score" value="<?php echo $match['player1_score'] !== null ? (int) $match['player1_score'] : ''; ?>" <?php echo $canQuickScore ? '' : 'disabled'; ?>>
                                        <span>-</span>
                                        <input type="number" min="0" name="player2_score" value="<?php echo $match['player2_score'] !== null ? (int) $match['player2_score'] : ''; ?>" <?php echo $canQuickScore ? '' : 'disabled'; ?>>
                                        <button type="submit" class="submit-btn" <?php echo $canQuickScore ? '' : 'disabled'; ?>><?php echo htmlspecialchars(admin_text('Save', 'Kaydet')); ?></button>
                                    </form>
                                </td>
                                <td>
                                    <button type="button" class="action-btn" onclick="openMatchModal(<?php echo (int) $match['match_id']; ?>)"><?php echo htmlspecialchars(admin_text('Details', 'Ayrıntılar')); ?></button>
                                    <button type="button" class="cancel-btn" onclick="deleteMatch(<?php echo (int) $match['match_id']; ?>)"><?php echo htmlspecialchars(admin_text('Delete', 'Sil')); ?></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </section>

        <?php if ($tournament['tour_type'] === 'Round Robin'): ?>
            <section class="page-section" data-section="standings">
                <h2><?php echo htmlspecialchars(admin_text('Round Robin Standings', 'Round Robin Puan Durumu')); ?></h2>
                <table class="standings-table">
                    <thead>
                        <tr>
                            <th><?php echo htmlspecialchars(admin_text('Position', 'Sıra')); ?></th>
                            <th><?php echo htmlspecialchars(admin_text('Player', 'Oyuncu')); ?></th>
                            <th><?php echo htmlspecialchars(admin_text('Played', 'Oynanan')); ?></th>
                            <th><?php echo htmlspecialchars(admin_text('Won', 'Galibiyet')); ?></th>
                            <th><?php echo htmlspecialchars(admin_text('Lost', 'Mağlubiyet')); ?></th>
                            <th><?php echo htmlspecialchars(admin_text('Drawn', 'Beraberlik')); ?></th>
                            <th><?php echo htmlspecialchars(admin_text('Points', 'Puan')); ?></th>
                            <th><?php echo htmlspecialchars(admin_text('Leg Diff', 'Leg Farkı')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($standings as $index => $standing): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars(player_name($standing)); ?></td>
                                <td><?php echo (int) $standing['matches_played']; ?></td>
                                <td><?php echo (int) $standing['matches_won']; ?></td>
                                <td><?php echo (int) $standing['matches_lost']; ?></td>
                                <td><?php echo (int) $standing['matches_drawn']; ?></td>
                                <td><?php echo (int) $standing['points']; ?></td>
                                <td><?php echo (int) $standing['leg_difference']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        <?php endif; ?>

        <?php if ($tournament['tour_type'] === 'League'): ?>
            <section class="page-section" data-section="groups">
                <h2><?php echo htmlspecialchars(admin_text('Group Stage', 'Grup Aşaması')); ?></h2>
                <div class="section-grid league-groups-grid">
                    <?php foreach ($groupStandings as $groupNumber => $rows): ?>
                        <div class="section-card">
                            <h3><?php echo htmlspecialchars(admin_translate_value('Group ' . (int) $groupNumber)); ?></h3>
                            <table class="standings-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th><?php echo htmlspecialchars(admin_text('Player', 'Oyuncu')); ?></th>
                                        <th><?php echo htmlspecialchars(admin_text('Pts', 'P')); ?></th>
                                        <th><?php echo htmlspecialchars(admin_text('Leg Diff', 'Leg Farkı')); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rows as $index => $row): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars(player_name($row)); ?></td>
                                            <td><?php echo (int) $row['points']; ?></td>
                                            <td><?php echo (int) $row['leg_difference']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                            <?php if (!empty($groupMatches[$groupNumber])): ?>
                                <h4 style="margin-top:12px;"><?php echo htmlspecialchars(admin_text('Matches', 'Maçlar')); ?></h4>
                                <?php foreach ($groupMatches[$groupNumber] as $groupMatch): ?>
                                    <div class="match-card">
                                        <strong><?php echo htmlspecialchars(tournament_admin_match_label($groupMatch, 'player1', $matchNumbersById)); ?></strong>
                                        vs
                                        <strong><?php echo htmlspecialchars(tournament_admin_match_label($groupMatch, 'player2', $matchNumbersById)); ?></strong>
                                        <div class="tag" style="margin-top:8px;">
                                            <?php echo htmlspecialchars(admin_match_status_label((string) $groupMatch['match_status'])); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($tournament['tour_type'] === 'Group'): ?>
            <section class="page-section" data-section="teams">
                <h2><?php echo htmlspecialchars(admin_text('Teams & Standings', 'Takımlar ve Puan Durumu')); ?></h2>
                <div class="section-grid">
                    <div class="section-card">
                        <h3><?php echo htmlspecialchars(admin_text('Team Standings', 'Takım Puan Durumu')); ?></h3>
                        <table class="standings-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th><?php echo htmlspecialchars(admin_text('Team', 'Takım')); ?></th>
                                    <th><?php echo htmlspecialchars(admin_text('Played', 'Oynanan')); ?></th>
                                    <th><?php echo htmlspecialchars(admin_text('Won', 'Galibiyet')); ?></th>
                                    <th><?php echo htmlspecialchars(admin_text('Lost', 'Mağlubiyet')); ?></th>
                                    <th><?php echo htmlspecialchars(admin_text('Drawn', 'Beraberlik')); ?></th>
                                    <th><?php echo htmlspecialchars(admin_text('Points', 'Puan')); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teamStandings as $index => $standing): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($standing['team_name']); ?></td>
                                        <td><?php echo (int) $standing['matches_played']; ?></td>
                                        <td><?php echo (int) $standing['matches_won']; ?></td>
                                        <td><?php echo (int) $standing['matches_lost']; ?></td>
                                        <td><?php echo (int) $standing['matches_drawn']; ?></td>
                                        <td><?php echo (int) $standing['points']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="team-roster-grid">
                    <?php foreach ($teams as $index => $team): ?>
                        <?php
                        $teamStanding = $teamStandingsById[(int) ($team['team_id'] ?? 0)] ?? null;
                        $teamNote = $groupUsesIndividualMatches
                            ? admin_text('Each player row rolls up the completed head-to-head fixtures between the two rosters.', 'Her oyuncu satırı, iki kadro arasındaki tamamlanan birebir fikstürü toplar.')
                            : admin_text('Player stats will populate once the group fixtures are expanded into player-versus-player matches.', 'Grup fikstürü oyuncu-oyuncuya maçlara genişletildiğinde oyuncu istatistikleri dolacaktır.');
                        ?>
                        <article class="section-card team-roster-card">
                            <div class="team-roster-head">
                                <div>
                                    <span class="team-roster-chip"><?php echo htmlspecialchars(admin_translate_value('Team ' . ($index + 1))); ?></span>
                                    <h3 style="margin-top: 10px;"><?php echo htmlspecialchars($team['team_name']); ?></h3>
                                    <p class="team-roster-note"><?php echo htmlspecialchars($teamNote); ?></p>
                                </div>
                                <?php if ($teamStanding !== null): ?>
                                    <div class="team-roster-meta">
                                        <span class="team-roster-chip"><?php echo htmlspecialchars(admin_text('Played', 'Oynanan')); ?> <?php echo (int) $teamStanding['matches_played']; ?></span>
                                        <span class="team-roster-chip"><?php echo htmlspecialchars(admin_text('W-D-L', 'G-B-M')); ?> <?php echo (int) $teamStanding['matches_won']; ?>-<?php echo (int) $teamStanding['matches_drawn']; ?>-<?php echo (int) $teamStanding['matches_lost']; ?></span>
                                        <span class="team-roster-chip"><?php echo htmlspecialchars(admin_text('Points', 'Puan')); ?> <?php echo (int) $teamStanding['points']; ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="team-roster-table-shell">
                                <table class="team-roster-table">
                                    <thead>
                                        <tr>
                                            <th><?php echo htmlspecialchars(admin_text('Player', 'Oyuncu')); ?></th>
                                            <th><?php echo htmlspecialchars(admin_text('P', 'O')); ?></th>
                                            <th><?php echo htmlspecialchars(admin_text('W', 'G')); ?></th>
                                            <th><?php echo htmlspecialchars(admin_text('L', 'M')); ?></th>
                                            <th><?php echo htmlspecialchars(admin_text('D', 'B')); ?></th>
                                            <th><?php echo htmlspecialchars(admin_text('Pts', 'P')); ?></th>
                                            <th><?php echo htmlspecialchars(admin_text('Leg Diff', 'Leg Farkı')); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($team['players'])): ?>
                                            <?php foreach ($team['players'] as $teamPlayer): ?>
                                                <?php
                                                $playerId = (int) $teamPlayer['plr_idNum'];
                                                $playerStats = $groupTeamPlayerStats[$playerId] ?? [
                                                    'matches_played' => 0,
                                                    'matches_won' => 0,
                                                    'matches_lost' => 0,
                                                    'matches_drawn' => 0,
                                                    'points' => 0,
                                                    'leg_difference' => 0,
                                                ];
                                                ?>
                                                <tr>
                                                    <td class="team-roster-name"><?php echo htmlspecialchars(trim($teamPlayer['plr_name'] . ' ' . $teamPlayer['plr_surname'])); ?></td>
                                                    <td><?php echo (int) $playerStats['matches_played']; ?></td>
                                                    <td><?php echo (int) $playerStats['matches_won']; ?></td>
                                                    <td><?php echo (int) $playerStats['matches_lost']; ?></td>
                                                    <td><?php echo (int) $playerStats['matches_drawn']; ?></td>
                                                    <td><?php echo (int) $playerStats['points']; ?></td>
                                                    <td><?php echo ($playerStats['leg_difference'] > 0 ? '+' : '') . (int) $playerStats['leg_difference']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7"><?php echo htmlspecialchars(admin_text('No players assigned yet.', 'Henüz oyuncu atanmadı.')); ?></td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if (!empty($knockoutBracketGroups)): ?>
            <section class="page-section" data-section="bracket" id="adminBracketSection">
                <div class="header-actions">
                    <div>
                        <h2><?php echo htmlspecialchars(admin_text('Tournament Bracket', 'Turnuva Braketi')); ?></h2>
                        <p class="surface-note">
                            <?php if ($tournament['tour_type'] === 'Double Elimination'): ?>
                                <?php echo htmlspecialchars(admin_text('Use the merged view for the full winners-versus-losers picture, or switch to a single path so the double-elimination layout never dumps every path on top of itself.', 'Kazanan-kaybeden görünümünün tamamı için birleşik görünümü kullanın veya çift eliminasyon düzeni tüm yolları üst üste bindirmesin diye tek bir yola geçin.')); ?>
                            <?php else: ?>
                                <?php echo htmlspecialchars(admin_text('Use the connected knockout bracket for drag-and-drop reseeding, quick scoring, and visual round flow. Once a match is completed, its slots stay locked.', 'Sürükle-bırak yeniden yerleştirme, hızlı skor girişi ve görsel tur akışı için bağlantılı eleme braketini kullanın. Bir maç tamamlandığında sıraları kilitli kalır.')); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="dense-actions">
                        <button type="button" class="action-btn" id="adminBracketFocusButton" onclick="toggleBracketSectionFocus()"><?php echo htmlspecialchars(admin_text('Open focus mode', 'Odak modunu aç')); ?></button>
                    </div>
                </div>
                <?php if ($isDoubleElimination): ?>
                    <div class="bracket-focus-toolbar">
                        <p class="surface-note"><?php echo htmlspecialchars(admin_text('Start in the merged grid, then narrow the view to a single path when you need to inspect winners, losers, or the final in isolation.', 'Birleşik ızgarada başlayın, ardından kazananları, kaybedenleri veya finali tek başına incelemek gerektiğinde görünümü tek bir yola daraltın.')); ?></p>
                        <div class="bracket-view-toggle">
                            <button type="button" class="action-btn" data-bracket-view-button="merged" onclick="showBracketView('merged')"><?php echo htmlspecialchars(admin_translate_value('Merged Bracket')); ?></button>
                            <button type="button" class="action-btn" data-bracket-view-button="Winners Bracket" onclick="showBracketView('Winners Bracket')"><?php echo htmlspecialchars(admin_translate_value('Winners Bracket')); ?></button>
                            <button type="button" class="action-btn" data-bracket-view-button="Losers Bracket" onclick="showBracketView('Losers Bracket')"><?php echo htmlspecialchars(admin_translate_value('Losers Bracket')); ?></button>
                            <?php if (isset($knockoutBracketGroups['Grand Final'])): ?>
                                <button type="button" class="action-btn" data-bracket-view-button="Grand Final" onclick="showBracketView('Grand Final')"><?php echo htmlspecialchars(admin_text('Finals', 'Finaller')); ?></button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif ($hasSingleEliminationBracketViews): ?>
                    <div class="bracket-focus-toolbar">
                        <p class="surface-note"><?php echo htmlspecialchars(admin_text('Filter down to a single bracket path when the connected layout gets crowded, or switch back to the full board when you need the whole knockout picture.', 'Bağlantılı düzen kalabalıklaştığında tek bir braket yoluna filtreleyin veya tüm eleme görünümüne ihtiyaç duyduğunuzda tam panoya geri dönün.')); ?></p>
                        <div class="bracket-view-toggle">
                            <button type="button" class="action-btn" data-bracket-view-button="all" onclick="showBracketView('all')"><?php echo htmlspecialchars(admin_text('All Paths', 'Tüm Yollar')); ?></button>
                            <?php foreach ($knockoutBracketGroups as $jumpGroup): ?>
                                <button
                                    type="button"
                                    class="action-btn"
                                    data-bracket-view-button="<?php echo htmlspecialchars((string) $jumpGroup['source_label'], ENT_QUOTES); ?>"
                                    onclick="showBracketView('<?php echo htmlspecialchars((string) $jumpGroup['source_label'], ENT_QUOTES); ?>')"
                                >
                                    <?php echo htmlspecialchars($jumpGroup['display_label']); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="bracket-focus-toolbar">
                    <p class="surface-note"><?php echo htmlspecialchars(admin_text('Completed earlier-round matches fade after they feed the next stage, but their names stay visible for auditability.', 'Sonraki aşamaya oyuncu taşıyan tamamlanmış önceki tur maçları soluklaşır; ancak denetlenebilirlik için isimler görünür kalır.')); ?></p>
                    <div class="bracket-view-toggle">
                        <button type="button" class="action-btn" data-completed-fade-toggle onclick="toggleCompletedMatchFade()"><?php echo htmlspecialchars(admin_text('Show completed rounds', 'Tamamlanan turları göster')); ?></button>
                    </div>
                </div>
                <div class="<?php echo $isDoubleElimination ? 'merged-bracket-grid' : 'section-grid'; ?>" id="adminBracketGroups" data-bracket-groups-container data-active-view="<?php echo $isDoubleElimination ? 'merged' : 'all'; ?>">
                    <?php foreach ($knockoutBracketGroups as $bracketGroup): ?>
                        <?php $bracketGroupDomId = 'admin-bracket-group-' . preg_replace('/[^a-z0-9]+/i', '-', strtolower((string) $bracketGroup['key'])); ?>
                        <div class="section-card" id="<?php echo htmlspecialchars($bracketGroupDomId); ?>" data-bracket-group="<?php echo htmlspecialchars((string) $bracketGroup['source_label']); ?>">
                            <h3><?php echo htmlspecialchars($bracketGroup['display_label']); ?></h3>
                            <?php if ($isDoubleElimination): ?>
                                <p class="surface-note" style="margin-top:6px; margin-bottom:14px;">
                                    <?php if ($bracketGroup['source_label'] === 'Opening Round'): ?>
                                        <?php echo htmlspecialchars(admin_text('Every entrant starts here before the field splits into the winners and losers branches.', 'Alan kazananlar ve kaybedenler kollarına ayrılmadan önce her katılımcı burada başlar.')); ?>
                                    <?php elseif ($bracketGroup['source_label'] === 'Winners Bracket'): ?>
                                        <?php echo htmlspecialchars(admin_text('Winners from the opening round advance into this right-side single-elimination path.', 'Açılış turunun kazananları bu sağ taraftaki tekli eleme yoluna ilerler.')); ?>
                                    <?php elseif ($bracketGroup['source_label'] === 'Losers Bracket'): ?>
                                        <?php echo htmlspecialchars(admin_text('Opening-round losers advance into this left-side single-elimination path.', 'Açılış turunun kaybedenleri bu sol taraftaki tekli eleme yoluna ilerler.')); ?>
                                    <?php elseif ($bracketGroup['source_label'] === 'Grand Final'): ?>
                                        <?php echo htmlspecialchars(admin_text('The winners-side champion meets the losers-side champion here.', 'Kazananlar kolunun şampiyonu burada kaybedenler kolunun şampiyonuyla karşılaşır.')); ?>
                                    <?php elseif ($bracketGroup['source_label'] === 'Third Place Playoff'): ?>
                                        <?php echo htmlspecialchars(admin_text('The losing finalists from each branch meet here to settle third and fourth place.', 'Her koldaki kaybeden finalistler burada üçüncü ve dördüncü sırayı belirlemek için karşılaşır.')); ?>
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>
                            <div class="connected-bracket-shell"<?php echo $isDoubleElimination && $bracketGroup['source_label'] === 'Losers Bracket' ? ' data-mirrored-bracket-shell' : ''; ?>>
                                <div class="connected-bracket">
                                <?php foreach ($bracketGroup['rounds'] as $roundNumber => $roundMatches): ?>
                                    <?php $roundIndex = (int) ($bracketGroup['round_positions'][(int) $roundNumber] ?? 1); ?>
                                    <?php
                                    $expectedRoundMatches = max(1, (int) ($bracketGroup['slot_count'] / max(1, pow(2, $roundIndex))));
                                    $renderRoundMatches = array_values($roundMatches);
                                    while (count($renderRoundMatches) < $expectedRoundMatches) {
                                        $renderRoundMatches[] = ['is_placeholder' => true];
                                    }
                                    ?>
                                    <div class="connected-bracket-round">
                                        <h3><?php echo htmlspecialchars(tournament_admin_round_title($bracketGroup['source_label'], (int) $roundNumber, (int) $bracketGroup['round_count'])); ?></h3>
                                        <div class="connected-bracket-lane" style="--slot-count: <?php echo (int) $bracketGroup['slot_count']; ?>;">
                                        <?php foreach ($renderRoundMatches as $matchIndex => $roundMatch): ?>
                                            <?php
                                            $rowSpan = (int) pow(2, $roundIndex);
                                            $rowStart = ($matchIndex * $rowSpan) + 1;
                                            $rowEnd = $rowStart + $rowSpan;
                                            $isPlaceholderMatch = !empty($roundMatch['is_placeholder']);
                                            $visualState = $isPlaceholderMatch ? ['card' => 'state-scheduled', 'player1' => '', 'player2' => ''] : tournament_match_visual_state($roundMatch);
                                            ?>
                                            <div
                                                class="connected-bracket-node <?php echo $roundIndex > 1 ? 'has-incoming' : ''; ?>"
                                                style="grid-row: <?php echo $rowStart; ?> / <?php echo $rowEnd; ?>;"
                                            >
                                                <div
                                                    class="connected-bracket-matchup <?php echo $isPlaceholderMatch ? 'is-placeholder' : ''; ?> <?php echo $roundIndex < $bracketGroup['round_count'] ? 'has-outgoing' : ''; ?> <?php echo htmlspecialchars($visualState['card']); ?> <?php echo (!$isPlaceholderMatch && $roundMatch['match_status'] === 'Completed' && (!empty($roundMatch['next_match_id']) || !empty($roundMatch['loser_next_match_id']))) ? 'fadeable-completed-match' : ''; ?>"
                                                    <?php if (!$isPlaceholderMatch): ?>
                                                        data-admin-bracket-match
                                                        data-match-id="<?php echo (int) $roundMatch['match_id']; ?>"
                                                        tabindex="0"
                                                        role="button"
                                                        aria-label="<?php echo htmlspecialchars(admin_text('Open match ', 'Maç ') . (int) $matchNumbersById[(int) $roundMatch['match_id']] . admin_text(' details', ' ayrıntılarını aç')); ?>"
                                                        onclick="openMatchModal(<?php echo (int) $roundMatch['match_id']; ?>)"
                                                    <?php endif; ?>
                                                >
                                                    <div class="connected-bracket-summary">
                                                        <span><?php echo htmlspecialchars($isPlaceholderMatch ? admin_translate_value('Bye Slot') : admin_translate_value('Match ' . (int) $matchNumbersById[(int) $roundMatch['match_id']])); ?></span>
                                                        <span><?php echo htmlspecialchars($isPlaceholderMatch ? admin_translate_value('Auto-advance') : admin_match_status_label((string) $roundMatch['match_status'])); ?></span>
                                                    </div>

                                                    <div class="connected-bracket-slot-stack">
                                                        <button
                                                            type="button"
                                                            class="bracket-slot bracket-slot--compact <?php echo htmlspecialchars($visualState['player1']); ?>"
                                                            draggable="<?php echo (!$isPlaceholderMatch && !empty($roundMatch['player1_id']) && $roundMatch['match_status'] !== 'Completed') ? 'true' : 'false'; ?>"
                                                            <?php if (!$isPlaceholderMatch): ?>
                                                                data-bracket-slot
                                                                data-match-id="<?php echo (int) $roundMatch['match_id']; ?>"
                                                                data-match-status="<?php echo htmlspecialchars($roundMatch['match_status']); ?>"
                                                                data-slot="player1"
                                                                data-player-id="<?php echo !empty($roundMatch['player1_id']) ? (int) $roundMatch['player1_id'] : ''; ?>"
                                                                data-player-name="<?php echo htmlspecialchars(tournament_admin_match_label($roundMatch, 'player1', $matchNumbersById)); ?>"
                                                            <?php endif; ?>
                                                            <?php echo $isPlaceholderMatch ? 'disabled' : ''; ?>
                                                        >
                                                            <span class="bracket-slot-label"><?php echo htmlspecialchars(admin_translate_value('Top')); ?></span>
                                                            <span class="bracket-slot-name"><?php echo $isPlaceholderMatch ? htmlspecialchars(admin_translate_value('Bye / no fixture')) : htmlspecialchars(tournament_admin_match_label($roundMatch, 'player1', $matchNumbersById)); ?></span>
                                                            <span class="bracket-slot-hint"><?php echo htmlspecialchars($isPlaceholderMatch ? admin_translate_value('Bracket spacer') : admin_translate_value(!empty($roundMatch['player1_id']) ? 'Drag' : 'Drop')); ?></span>
                                                        </button>

                                                        <button
                                                            type="button"
                                                            class="bracket-slot bracket-slot--compact <?php echo htmlspecialchars($visualState['player2']); ?>"
                                                            draggable="<?php echo (!$isPlaceholderMatch && !empty($roundMatch['player2_id']) && $roundMatch['match_status'] !== 'Completed') ? 'true' : 'false'; ?>"
                                                            <?php if (!$isPlaceholderMatch): ?>
                                                                data-bracket-slot
                                                                data-match-id="<?php echo (int) $roundMatch['match_id']; ?>"
                                                                data-match-status="<?php echo htmlspecialchars($roundMatch['match_status']); ?>"
                                                                data-slot="player2"
                                                                data-player-id="<?php echo !empty($roundMatch['player2_id']) ? (int) $roundMatch['player2_id'] : ''; ?>"
                                                                data-player-name="<?php echo htmlspecialchars(tournament_admin_match_label($roundMatch, 'player2', $matchNumbersById)); ?>"
                                                            <?php endif; ?>
                                                            <?php echo $isPlaceholderMatch ? 'disabled' : ''; ?>
                                                        >
                                                            <span class="bracket-slot-label"><?php echo htmlspecialchars(admin_translate_value('Bottom')); ?></span>
                                                            <span class="bracket-slot-name"><?php echo $isPlaceholderMatch ? htmlspecialchars(admin_translate_value('Auto-advanced slot')) : htmlspecialchars(tournament_admin_match_label($roundMatch, 'player2', $matchNumbersById)); ?></span>
                                                            <span class="bracket-slot-hint"><?php echo htmlspecialchars($isPlaceholderMatch ? admin_translate_value('Bracket spacer') : admin_translate_value(!empty($roundMatch['player2_id']) ? 'Drag' : 'Drop')); ?></span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="page-section" data-section="bracket_board">
                <div class="header-actions">
                    <div>
                        <h2><?php echo htmlspecialchars(admin_text('Bracket Board', 'Braket Panosu')); ?></h2>
                        <p class="bracket-board-note"><?php echo htmlspecialchars(admin_text('This keeps the compact card stack from the previous view for quick scanning by round, while the connected bracket handles live reseeding and scoring.', 'Bu görünüm, turlara göre hızlı tarama için önceki görünümdeki kompakt kart yığınını korurken bağlantılı braket canlı yeniden yerleştirme ve skor girişini yönetir.')); ?></p>
                    </div>
                </div>
                <?php if ($isDoubleElimination): ?>
                    <div class="bracket-focus-toolbar">
                        <p class="surface-note"><?php echo htmlspecialchars(admin_text('Use the same view filters here when you want a denser round-by-round board without mixing the winners and losers paths together.', 'Kazanan ve kaybeden yollarını karıştırmadan daha yoğun bir tur-tur pano istediğinizde burada aynı görünüm filtrelerini kullanın.')); ?></p>
                        <div class="bracket-view-toggle">
                            <button type="button" class="action-btn" data-bracket-view-button="merged" onclick="showBracketView('merged')"><?php echo htmlspecialchars(admin_translate_value('Merged Bracket')); ?></button>
                            <button type="button" class="action-btn" data-bracket-view-button="Winners Bracket" onclick="showBracketView('Winners Bracket')"><?php echo htmlspecialchars(admin_translate_value('Winners Bracket')); ?></button>
                            <button type="button" class="action-btn" data-bracket-view-button="Losers Bracket" onclick="showBracketView('Losers Bracket')"><?php echo htmlspecialchars(admin_translate_value('Losers Bracket')); ?></button>
                            <?php if (isset($knockoutBracketGroups['Grand Final'])): ?>
                                <button type="button" class="action-btn" data-bracket-view-button="Grand Final" onclick="showBracketView('Grand Final')"><?php echo htmlspecialchars(admin_text('Finals', 'Finaller')); ?></button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif ($hasSingleEliminationBracketViews): ?>
                    <div class="bracket-focus-toolbar">
                        <p class="surface-note"><?php echo htmlspecialchars(admin_text('Use the same knockout path filters here when you want the quick-scan board to stay readable on smaller screens.', 'Hızlı tarama panosunun küçük ekranlarda okunaklı kalmasını istediğinizde burada aynı eleme yolu filtrelerini kullanın.')); ?></p>
                        <div class="bracket-view-toggle">
                            <button type="button" class="action-btn" data-bracket-view-button="all" onclick="showBracketView('all')"><?php echo htmlspecialchars(admin_text('All Paths', 'Tüm Yollar')); ?></button>
                            <?php foreach ($knockoutBracketGroups as $boardGroup): ?>
                                <button
                                    type="button"
                                    class="action-btn"
                                    data-bracket-view-button="<?php echo htmlspecialchars((string) $boardGroup['source_label'], ENT_QUOTES); ?>"
                                    onclick="showBracketView('<?php echo htmlspecialchars((string) $boardGroup['source_label'], ENT_QUOTES); ?>')"
                                >
                                    <?php echo htmlspecialchars($boardGroup['display_label']); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="bracket-focus-toolbar">
                    <p class="surface-note"><?php echo htmlspecialchars(admin_text('Use the same fade toggle here when you need to inspect a completed previous round again.', 'Tamamlanan önceki turu yeniden incelemek gerektiğinde burada da aynı soluklaştırma anahtarını kullanın.')); ?></p>
                    <div class="bracket-view-toggle">
                        <button type="button" class="action-btn" data-completed-fade-toggle onclick="toggleCompletedMatchFade()"><?php echo htmlspecialchars(admin_text('Show completed rounds', 'Tamamlanan turları göster')); ?></button>
                    </div>
                </div>
                <div class="<?php echo $isDoubleElimination ? 'merged-bracket-grid' : 'section-grid'; ?>" id="adminBracketBoardGroups" data-bracket-groups-container data-active-view="<?php echo $isDoubleElimination ? 'merged' : 'all'; ?>">
                    <?php foreach ($knockoutBracketGroups as $bracketGroup): ?>
                        <div class="section-card" data-bracket-group="<?php echo htmlspecialchars((string) $bracketGroup['source_label']); ?>">
                            <h3><?php echo htmlspecialchars($bracketGroup['display_label']); ?></h3>
                            <div class="bracket-grid">
                                <?php foreach ($bracketGroup['rounds'] as $roundNumber => $roundMatches): ?>
                                    <div class="bracket-round">
                                        <h3><?php echo htmlspecialchars(tournament_admin_round_title($bracketGroup['source_label'], (int) $roundNumber, (int) $bracketGroup['round_count'])); ?></h3>
                                        <?php foreach ($roundMatches as $roundMatch): ?>
                                            <?php
                                            $visualState = tournament_match_visual_state($roundMatch);
                                            $canQuickScore = !empty($roundMatch['player1_id']) && !empty($roundMatch['player2_id']);
                                            $pathLabel = tournament_match_advancement_label($roundMatch, $matchNumbersById);
                                            ?>
                                            <div class="match-card bracket-card <?php echo htmlspecialchars($visualState['card']); ?> <?php echo ($roundMatch['match_status'] === 'Completed' && (!empty($roundMatch['next_match_id']) || !empty($roundMatch['loser_next_match_id']))) ? 'fadeable-completed-match' : ''; ?>" data-match-card data-match-id="<?php echo (int) $roundMatch['match_id']; ?>">
                                                <div class="bracket-card-head">
                                                    <span class="tag"><?php echo htmlspecialchars(admin_translate_value('Match ' . (int) $matchNumbersById[(int) $roundMatch['match_id']])); ?></span>
                                                    <span class="tag"><?php echo htmlspecialchars(admin_match_status_label((string) $roundMatch['match_status'])); ?></span>
                                                </div>
                                                <div class="bracket-card-meta">
                                                    <span><?php echo htmlspecialchars($roundMatch['match_date']); ?> <?php echo htmlspecialchars(admin_text('at', 'saat')); ?> <?php echo htmlspecialchars(substr((string) $roundMatch['match_time'], 0, 5)); ?></span>
                                                    <span class="bracket-link-chip"><?php echo htmlspecialchars(admin_match_flow_label($pathLabel)); ?></span>
                                                </div>

                                                <div class="bracket-slot <?php echo htmlspecialchars($visualState['player1']); ?>">
                                                    <span class="bracket-slot-label"><?php echo htmlspecialchars(admin_translate_value('Top Slot')); ?></span>
                                                    <span class="bracket-slot-name"><?php echo htmlspecialchars(tournament_admin_match_label($roundMatch, 'player1', $matchNumbersById)); ?></span>
                                                    <span class="bracket-slot-hint"><?php echo htmlspecialchars(admin_translate_value(!empty($roundMatch['player1_id']) ? 'Seeded' : 'Waiting')); ?></span>
                                                </div>

                                                <div class="bracket-slot <?php echo htmlspecialchars($visualState['player2']); ?>">
                                                    <span class="bracket-slot-label"><?php echo htmlspecialchars(admin_translate_value('Bottom Slot')); ?></span>
                                                    <span class="bracket-slot-name"><?php echo htmlspecialchars(tournament_admin_match_label($roundMatch, 'player2', $matchNumbersById)); ?></span>
                                                    <span class="bracket-slot-hint"><?php echo htmlspecialchars(admin_translate_value(!empty($roundMatch['player2_id']) ? 'Seeded' : 'Waiting')); ?></span>
                                                </div>

                                                <div class="bracket-card-footer">
                                                    <div class="bracket-status">
                                                        <?php if ($canQuickScore && $roundMatch['player1_score'] !== null && $roundMatch['player2_score'] !== null): ?>
                                                            <?php echo htmlspecialchars(admin_text('Score', 'Skor')); ?>: <?php echo (int) $roundMatch['player1_score']; ?> - <?php echo (int) $roundMatch['player2_score']; ?>
                                                        <?php else: ?>
                                                            <?php echo htmlspecialchars(admin_translate_value('Waiting for result')); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="dense-actions">
                                                        <button type="button" class="action-btn" onclick="openMatchModal(<?php echo (int) $roundMatch['match_id']; ?>)"><?php echo htmlspecialchars(admin_text('Details', 'Ayrıntılar')); ?></button>
                                                        <button type="button" class="cancel-btn" onclick="deleteMatch(<?php echo (int) $roundMatch['match_id']; ?>)"><?php echo htmlspecialchars(admin_text('Delete', 'Sil')); ?></button>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <div class="modal-overlay" id="matchModal">
        <div class="modal-card match-modal-card">
            <div class="header-actions">
                <div>
                    <h3><?php echo htmlspecialchars(admin_text('Match Details', 'Maç Ayrıntıları')); ?></h3>
                    <p class="match-modal-subtitle"><?php echo htmlspecialchars(admin_text('Update the scoreline and schedule for the selected matchup.', 'Seçilen eşleşmenin skorunu ve takvimini güncelleyin.')); ?></p>
                </div>
                <button type="button" class="cancel-btn" onclick="closeMatchModal()"><?php echo htmlspecialchars(admin_text('Close', 'Kapat')); ?></button>
            </div>
            <input type="hidden" id="mf_match_id">
            <div class="match-modal-summary">
                <div class="match-modal-meta">
                    <div class="match-chip-row">
                        <span class="tag" id="mf_match_number"><?php echo htmlspecialchars(admin_text('Match', 'Maç')); ?></span>
                        <span class="match-chip" id="mf_round_label"><?php echo htmlspecialchars(admin_text('Round', 'Tur')); ?></span>
                    </div>
                    <div class="match-chip-row">
                        <span class="tag" id="mf_status_label"><?php echo htmlspecialchars(admin_match_status_label('Scheduled')); ?></span>
                        <span class="match-chip" id="mf_flow_label"><?php echo htmlspecialchars(admin_translate_value('Winner path pending')); ?></span>
                    </div>
                </div>
                <div class="match-player-grid">
                    <div class="match-player-card">
                        <span><?php echo htmlspecialchars(admin_text('Top slot', 'Üst sıra')); ?></span>
                        <a class="player-link" id="mf_player1_link" href="#" hidden></a>
                        <strong id="mf_player1_label"><?php echo htmlspecialchars(admin_translate_value('TBD')); ?></strong>
                    </div>
                    <div class="match-player-card">
                        <span><?php echo htmlspecialchars(admin_text('Bottom slot', 'Alt sıra')); ?></span>
                        <a class="player-link" id="mf_player2_link" href="#" hidden></a>
                        <strong id="mf_player2_label"><?php echo htmlspecialchars(admin_translate_value('TBD')); ?></strong>
                    </div>
                </div>
            </div>
            <div class="match-modal-state">
                <span class="match-modal-state-badge" id="mf_feedback_badge" data-tone="neutral"><?php echo htmlspecialchars(admin_text('Ready', 'Hazır')); ?></span>
                <span class="surface-note" id="mf_feedback_text"><?php echo htmlspecialchars(admin_text('No unsaved changes yet.', 'Henüz kaydedilmemiş değişiklik yok.')); ?></span>
            </div>
            <div class="match-modal-body">
                <section class="match-modal-section">
                    <h4><?php echo htmlspecialchars(admin_text('Score Entry', 'Skor Girişi')); ?></h4>
                    <p><?php echo htmlspecialchars(admin_text('Record the latest scoreline for this matchup. Results stay disabled until both slots are seeded.', 'Bu eşleşme için en son skoru kaydedin. İki sıra da dolana kadar sonuç kaydı devre dışı kalır.')); ?></p>
                    <div class="match-score-grid">
                        <label class="match-score-field" for="mf_p1s">
                            <span id="mf_score_label_1"><?php echo htmlspecialchars(admin_text('Top slot score', 'Üst sıra skoru')); ?></span>
                            <input type="number" id="mf_p1s" min="0" inputmode="numeric">
                        </label>
                        <label class="match-score-field" for="mf_p2s">
                            <span id="mf_score_label_2"><?php echo htmlspecialchars(admin_text('Bottom slot score', 'Alt sıra skoru')); ?></span>
                            <input type="number" id="mf_p2s" min="0" inputmode="numeric">
                        </label>
                    </div>
                </section>
                <section class="match-modal-section">
                    <h4><?php echo htmlspecialchars(admin_text('Schedule', 'Takvim')); ?></h4>
                    <p><?php echo htmlspecialchars(admin_text('Adjust the planned date and start time without touching the bracket wiring.', 'Braket bağlantılarına dokunmadan planlanan tarih ve başlangıç saatini ayarlayın.')); ?></p>
                    <div class="inline-grid">
                        <label class="schedule-field" for="mf_date">
                            <span><?php echo htmlspecialchars(admin_text('Date', 'Tarih')); ?></span>
                            <input type="date" id="mf_date">
                        </label>
                        <label class="schedule-field schedule-field--time" for="mf_time">
                            <span><?php echo htmlspecialchars(admin_text('Time', 'Saat')); ?></span>
                            <input type="time" id="mf_time">
                        </label>
                    </div>
                </section>
            </div>
            <div class="form-buttons" style="margin-top:18px;">
                <button type="button" class="action-btn" id="mf_save_schedule" onclick="saveMatchFields()"><?php echo htmlspecialchars(admin_text('Save Schedule', 'Takvimi Kaydet')); ?></button>
                <button type="button" class="submit-btn" id="mf_save_result" onclick="saveMatchResult()"><?php echo htmlspecialchars(admin_text('Record Result', 'Sonucu Kaydet')); ?></button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="leagueToolsModal">
        <div class="modal-card">
            <div class="header-actions">
                <h3><?php echo htmlspecialchars(admin_text('Structure Rescheduling', 'Yapı Yeniden Planlama')); ?></h3>
                <button type="button" class="cancel-btn" onclick="closeLeagueToolsModal()"><?php echo htmlspecialchars(admin_text('Close', 'Kapat')); ?></button>
            </div>
            <div class="inline-grid">
                <div>
                    <label for="lt_startdate"><?php echo htmlspecialchars(admin_text('New Start Date', 'Yeni Başlangıç Tarihi')); ?></label>
                    <input type="date" id="lt_startdate" value="<?php echo htmlspecialchars(date('Y-m-d', strtotime($tournament['tour_creationDate']))); ?>">
                </div>
            </div>
            <div class="form-buttons" style="margin-top:16px;">
                <button type="button" class="submit-btn" onclick="submitLeagueTools()"><?php echo htmlspecialchars(admin_text('Apply', 'Uygula')); ?></button>
            </div>
        </div>
    </div>

    <script type="application/json" id="tournament-page-data"><?php
        echo json_encode([
            'tourId' => (int) $tourId,
            'type' => $tournament['tour_type'],
            'status' => $tournament['status'],
            'structureGenerated' => $structureGenerated,
            'rosterUsesWithdrawals' => $rosterUsesWithdrawals,
            'players' => $playersForJs,
            'matches' => $matchesForJs,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    ?></script>
    <script src="../../js/ui_feedback.js?v=20260406-1"></script>
    <script src="../../js/admin_tournament_details.js"></script>
</body>
</html>
