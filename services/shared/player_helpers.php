<?php

require_once __DIR__ . '/../app_bootstrap.php';

function player_fetch_for_user(mysqli $db, int $userId): ?array
{
    $sql = 'SELECT p.*, u.email, u.user_name
            FROM users u
            LEFT JOIN players p ON p.user_id = u.user_id
            WHERE u.user_id = ?';

    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $player = $result->num_rows > 0 ? $result->fetch_assoc() : null;
    $stmt->close();

    return $player ?: null;
}

function player_id_for_user(mysqli $db, int $userId): ?int
{
    $player = player_fetch_for_user($db, $userId);

    if (!$player || empty($player['plr_idNum'])) {
        return null;
    }

    return (int) $player['plr_idNum'];
}

function player_name(array $player): string
{
    return trim(($player['plr_name'] ?? '') . ' ' . ($player['plr_surname'] ?? ''));
}

function players_not_in_tournament(mysqli $db, int $tourId): array
{
    $sql = 'SELECT p.plr_idNum, p.plr_name, p.plr_surname
            FROM players p
            WHERE NOT EXISTS (
                SELECT 1
                FROM tournament_players tp
                WHERE tp.tour_id = ? AND tp.plr_id = p.plr_idNum
            )
            ORDER BY p.plr_surname, p.plr_name';

    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $tourId);
    $stmt->execute();
    $players = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $players;
}
