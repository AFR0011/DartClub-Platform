<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/shared/tournament_helpers.php';
require_once __DIR__ . '/shared/tournament_view_helpers.php';

$tourId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($tourId <= 0) {
    app_json_response(['success' => false, 'message' => 'Invalid tournament id.'], 422);
}

try {
    $pageData = tournament_fetch_page_data($conn, $tourId);
    $tournament = $pageData['tournament'];
    if ((int) ($tournament['is_public'] ?? 0) !== 1) {
        app_json_response(['success' => false, 'message' => 'Tournament is not public.'], 403);
    }

    $recentResults = [];
    if ($tournament['tour_type'] === 'Group') {
        if (!empty($pageData['matches'])) {
            foreach ($pageData['matches'] as $match) {
                if (($match['match_status'] ?? '') === 'Completed') {
                    $recentResults[] = $match;
                }
            }
        } else {
            foreach ($pageData['team_matches'] as $match) {
                if (($match['match_status'] ?? '') === 'Completed') {
                    $recentResults[] = $match;
                }
            }
        }
    } else {
        foreach ($pageData['matches'] as $match) {
            if (($match['match_status'] ?? '') === 'Completed') {
                $recentResults[] = $match;
            }
        }
    }

    usort($recentResults, static function (array $left, array $right): int {
        $leftKey = ($left['match_date'] ?? '') . ' ' . ($left['match_time'] ?? '');
        $rightKey = ($right['match_date'] ?? '') . ' ' . ($right['match_time'] ?? '');

        return strcmp($rightKey, $leftKey);
    });

    app_json_response([
        'success' => true,
        'tournament' => $tournament,
        'players' => $pageData['players'],
        'matches' => $pageData['matches'],
        'standings' => $pageData['standings'],
        'group_standings' => $pageData['group_standings'],
        'teams' => $pageData['teams'],
        'team_matches' => $pageData['team_matches'],
        'team_standings' => $pageData['team_standings'],
        'recent_results' => array_slice($recentResults, 0, 8),
    ]);
} catch (Throwable $exception) {
    app_json_response(['success' => false, 'message' => $exception->getMessage()], 404);
}
