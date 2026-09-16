<?php
// includes/auth.php
require_once __DIR__ . '/../config/db.php';

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['role']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: /web_app/user/login.php');
        exit;
    }
}

function requireRole(string ...$roles): void
{
    requireLogin();
    if (!in_array($_SESSION['role'], $roles, true)) {
        http_response_code(403);
        exit('Access denied: คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
    }
}

function currentUser(): array
{
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'role' => $_SESSION['role'] ?? null,
        'name' => $_SESSION['user_name'] ?? '',
        'auth_method' => $_SESSION['auth_method'] ?? 'password',
    ];
}

function redirectToRoleHome(): void
{
    $map = [
        'admin' => '/web_app/admin/users.php',
        'owner' => '/web_app/owner/dashboard.php',
        'technician' => '/web_app/technician/dashboard.php',
        'customer' => '/web_app/customer/dashboard.php',
    ];
    $role = $_SESSION['role'] ?? '';
    header('Location: ' . ($map[$role] ?? '/web_app/user/login.php'));
    exit;
}
