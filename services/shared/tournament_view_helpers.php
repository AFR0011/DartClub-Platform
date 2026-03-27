<?php

require_once __DIR__ . '/player_helpers.php';

function tournament_status_data(array $tournament): array
{
    $today = new DateTime();
    $endDate = new DateTime($tournament['tour_endDate']);
    $startDate = new DateTime($tournament['tour_creationDate']);

    if ($today > $endDate) {
        return ['label' => 'Completed', 'class' => 'status-completed'];
    }

    if ($today >= $startDate) {
        return ['label' => 'Active', 'class' => 'status-active'];
    }

    return ['label' => 'Upcoming', 'class' => 'status-upcoming'];
}

function tournament_match_label(array $match, string $slot, array $matchNumbersById): string
{
    $idKey = $slot . '_id';
    $nameKey = $slot . '_name';
    $surnameKey = $slot . '_surname';

    if (!empty($match[$idKey])) {
        return trim(($match[$nameKey] ?? '') . ' ' . ($match[$surnameKey] ?? ''));
    }

    $prevKey = $slot === 'player1' ? 'prev_match1_id' : 'prev_match2_id';
    if (!empty($match[$prevKey])) {
        $previousMatchNumber = $matchNumbersById[(int) $match[$prevKey]] ?? null;
        return $previousMatchNumber ? 'Winner of Match ' . $previousMatchNumber : 'Winner TBD';
    }

    return 'TBD';
}

function tournament_round_title(int $roundNumber): string
{
    $labels = [
        1 => 'Round 1',
        2 => 'Round 2',
        3 => 'Semifinal',
        4 => 'Final',
    ];

    return $labels[$roundNumber] ?? ('Round ' . $roundNumber);
}
