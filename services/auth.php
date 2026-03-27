<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function get_current_user_id(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function get_current_role(): string {
    return $_SESSION['user_role'] ?? 'guest';
}

function require_login(): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.html');
        exit();
    }
}

function require_role(string $role): void {
    if (get_current_role() !== $role) {
        header('Location: ../main.php');
        exit();
    }
}

function require_any_role(array $roles): void {
    if (!in_array(get_current_role(), $roles, true)) {
        header('Location: ../main.php');
        exit();
    }
}