<?php

require_once __DIR__ . '/app_bootstrap.php';

app_start_session();

function get_current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function get_current_role(): string
{
    return $_SESSION['user_role'] ?? 'guest';
}

function get_current_membership_status(): string
{
    return $_SESSION['membership_status'] ?? 'not_submitted';
}

function is_logged_in(): bool
{
    return get_current_user_id() !== null;
}

function is_manager_or_admin(): bool
{
    return in_array(get_current_role(), ['manager', 'admin'], true);
}

function can_author_blog_posts(): bool
{
    return is_manager_or_admin() || get_current_membership_status() === 'approved';
}

function can_publish_blog_posts(): bool
{
    return is_manager_or_admin();
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: ../login.html');
        exit();
    }
}

function require_role(string $role): void
{
    if (get_current_role() !== $role) {
        header('Location: ../main.php');
        exit();
    }
}

function require_any_role(array $roles): void
{
    if (!in_array(get_current_role(), $roles, true)) {
        header('Location: ../main.php');
        exit();
    }
}

