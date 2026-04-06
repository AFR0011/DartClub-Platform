<?php

require_once __DIR__ . '/../app_bootstrap.php';

function player_fetch_for_user(mysqli $db, int $userId): ?array
{
    $sql = 'SELECT p.*, u.email, u.user_name, u.membership_status
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

function player_fetch_public(mysqli $db, int $playerId): ?array
{
    $sql = 'SELECT
                p.plr_idNum,
                p.plr_name,
                p.plr_surname,
                p.plr_username,
                u.user_name,
                u.membership_status
            FROM players p
            LEFT JOIN users u ON u.user_id = p.user_id
            WHERE p.plr_idNum = ?';

    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $playerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $player = $result->num_rows > 0 ? $result->fetch_assoc() : null;
    $stmt->close();

    return $player ?: null;
}

function player_name(array $player): string
{
    return trim(($player['plr_name'] ?? '') . ' ' . ($player['plr_surname'] ?? ''));
}

function player_seed_name_from_username(string $username): array
{
    $normalized = trim((string) preg_replace('/[._-]+/', ' ', $username));
    $parts = preg_split('/\s+/', $normalized) ?: [];
    $parts = array_values(array_filter(array_map('trim', $parts)));

    if (empty($parts)) {
        return [
            'plr_name' => 'Club',
            'plr_surname' => 'Member',
        ];
    }

    $firstName = ucwords(strtolower($parts[0]));
    $surnameParts = array_slice($parts, 1);
    $surname = !empty($surnameParts)
        ? implode(' ', array_map(static function (string $part): string {
            return ucwords(strtolower($part));
        }, $surnameParts))
        : 'Member';

    return [
        'plr_name' => $firstName,
        'plr_surname' => $surname,
    ];
}

function player_seed_payload_for_user(mysqli $db, int $userId): ?array
{
    $stmt = $db->prepare('SELECT user_name FROM users WHERE user_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || empty($row['user_name'])) {
        return null;
    }

    return player_seed_name_from_username((string) $row['user_name']);
}

function player_create_guest(mysqli $db, array $payload): int
{
    $firstName = trim((string) ($payload['plr_name'] ?? ''));
    $surname = trim((string) ($payload['plr_surname'] ?? ''));

    if ($firstName === '' || $surname === '') {
        throw new InvalidArgumentException('First name and surname are required.');
    }

    $slugSource = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $firstName . '-' . $surname) ?? '');
    $slugSource = trim($slugSource, '-');
    if ($slugSource === '') {
        $slugSource = 'guest-player';
    }

    $guestUsername = $slugSource . '-' . substr(bin2hex(random_bytes(4)), 0, 8);

    $sql = 'INSERT INTO players (
                plr_name,
                plr_surname,
                plr_username,
                user_id
            ) VALUES (?, ?, ?, NULL)';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('sss', $firstName, $surname, $guestUsername);
    $stmt->execute();
    $playerId = (int) $db->insert_id;
    $stmt->close();

    return $playerId;
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

function player_create_or_update_for_user(mysqli $db, int $userId, array $payload): int
{
    $firstName = trim((string) ($payload['plr_name'] ?? ''));
    $surname = trim((string) ($payload['plr_surname'] ?? ''));

    if ($firstName === '' || $surname === '') {
        throw new InvalidArgumentException('First name and surname are required.');
    }

    $existing = player_fetch_for_user($db, $userId);
    $usernameStmt = $db->prepare('SELECT user_name FROM users WHERE user_id = ?');
    $usernameStmt->bind_param('i', $userId);
    $usernameStmt->execute();
    $usernameRow = $usernameStmt->get_result()->fetch_assoc();
    $usernameStmt->close();

    $plrAddress = app_value_or_null($payload['plr_address'] ?? null);
    $plrDob = app_value_or_null($payload['plr_dob'] ?? null);
    $plrMother = app_value_or_null($payload['plr_mother'] ?? null);
    $plrFather = app_value_or_null($payload['plr_father'] ?? null);
    $plrPob = app_value_or_null($payload['plr_pob'] ?? null);
    $plrPhone = app_value_or_null($payload['plr_phone'] ?? null);
    $plrUsername = $usernameRow['user_name'] ?? ('user-' . $userId);

    if ($existing && !empty($existing['plr_idNum'])) {
        $sql = 'UPDATE players
                SET plr_name = ?, plr_surname = ?, plr_address = ?, plr_dob = ?, plr_mother = ?, plr_father = ?, plr_pob = ?, plr_phone = ?, plr_username = ?
                WHERE user_id = ?';
        $stmt = $db->prepare($sql);
        $stmt->bind_param(
            'sssssssssi',
            $firstName,
            $surname,
            $plrAddress,
            $plrDob,
            $plrMother,
            $plrFather,
            $plrPob,
            $plrPhone,
            $plrUsername,
            $userId
        );
        $stmt->execute();
        $stmt->close();

        return (int) $existing['plr_idNum'];
    }

    $sql = 'INSERT INTO players (
                plr_name,
                plr_surname,
                plr_address,
                plr_dob,
                plr_mother,
                plr_father,
                plr_pob,
                plr_phone,
                plr_username,
                user_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
    $stmt = $db->prepare($sql);
    $stmt->bind_param(
        'sssssssssi',
        $firstName,
        $surname,
        $plrAddress,
        $plrDob,
        $plrMother,
        $plrFather,
        $plrPob,
        $plrPhone,
        $plrUsername,
        $userId
    );
    $stmt->execute();
    $playerId = (int) $db->insert_id;
    $stmt->close();

    return $playerId;
}
