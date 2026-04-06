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
    $sourceKey = $slot === 'player1' ? 'prev_match1_source' : 'prev_match2_source';
    if (!empty($match[$prevKey])) {
        $previousMatchNumber = $matchNumbersById[(int) $match[$prevKey]] ?? null;
        $source = strtolower((string) ($match[$sourceKey] ?? 'winner'));
        if ($source === 'loser') {
            return $previousMatchNumber ? 'Loser of Match ' . $previousMatchNumber : 'Loser TBD';
        }

        return $previousMatchNumber ? 'Winner of Match ' . $previousMatchNumber : 'Winner TBD';
    }

    return 'TBD';
}

function tournament_match_advancement_label(array $match, array $matchNumbersById): string
{
    if (($match['bracket'] ?? '') === 'Grand Final') {
        return 'Champion decided here';
    }

    if (($match['bracket'] ?? '') === 'Third Place Playoff') {
        return '3rd place decided here';
    }

    $parts = [];

    if (!empty($match['next_match_id'])) {
        $nextMatchNumber = $matchNumbersById[(int) $match['next_match_id']] ?? null;
        if ($nextMatchNumber !== null) {
            $parts[] = 'Winner to Match ' . $nextMatchNumber;
        }
    }

    if (!empty($match['loser_next_match_id'])) {
        $loserNextMatchNumber = $matchNumbersById[(int) $match['loser_next_match_id']] ?? null;
        if ($loserNextMatchNumber !== null) {
            $parts[] = 'Loser to Match ' . $loserNextMatchNumber;
        }
    }

    if (empty($parts)) {
        return 'Winner path pending';
    }

    return implode(' | ', $parts);
}

function tournament_round_title(int $roundPosition, ?int $roundCount = null): string
{
    if ($roundCount !== null && $roundCount > 0) {
        if ($roundCount === 1 || $roundPosition >= $roundCount) {
            return 'Final';
        }

        if ($roundPosition === ($roundCount - 1)) {
            return 'Semifinal';
        }

        if ($roundPosition === ($roundCount - 2)) {
            return 'Quarterfinal';
        }
    }

    return 'Round ' . $roundPosition;
}

function tournament_bracket_round_title(string $bracketLabel, int $roundPosition, int $roundCount): string
{
    if ($bracketLabel === 'Grand Final') {
        return 'Grand Final';
    }

    if ($bracketLabel === 'Third Place Playoff') {
        return 'Third Place Playoff';
    }

    if ($bracketLabel === 'Winners Bracket') {
        $baseTitle = tournament_round_title($roundPosition, $roundCount);

        if (str_starts_with($baseTitle, 'Round ')) {
            return 'Winners ' . $baseTitle;
        }

        return 'Winners ' . $baseTitle;
    }

    if ($bracketLabel === 'Losers Bracket') {
        if ($roundCount === 1) {
            return 'Losers Final';
        }

        if ($roundPosition === $roundCount) {
            return 'Losers Final';
        }

        if ($roundPosition === ($roundCount - 1)) {
            return 'Losers Semifinal';
        }

        return 'Losers Round ' . $roundPosition;
    }

    return tournament_round_title($roundPosition, $roundCount);
}
