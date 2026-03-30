<?php

require_once __DIR__ . '/player_helpers.php';

function tournament_status_data(array $tournament): array
{
    $status = $tournament['status'] ?? 'draft';
    $map = [
        'draft' => ['label' => 'Draft', 'class' => 'status-upcoming'],
        'registration_open' => ['label' => 'Registration Open', 'class' => 'status-upcoming'],
        'registration_closed' => ['label' => 'Registration Closed', 'class' => 'status-upcoming'],
        'in_progress' => ['label' => 'Live', 'class' => 'status-active'],
        'completed' => ['label' => 'Completed', 'class' => 'status-completed'],
        'archived' => ['label' => 'Archived', 'class' => 'status-completed'],
    ];

    return $map[$status] ?? ['label' => ucfirst(str_replace('_', ' ', $status)), 'class' => 'status-upcoming'];
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
        2 => 'Quarterfinal',
        3 => 'Semifinal',
        4 => 'Final',
    ];

    return $labels[$roundNumber] ?? ('Round ' . $roundNumber);
}

