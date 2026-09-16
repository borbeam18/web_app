<?php
require_once __DIR__ . '/../config/db.php';

$role = $_GET['role'] ?? '';
$availableRoles = $_SESSION['available_roles'] ?? [];

if (!isset($availableRoles[$role])) {
    http_response_code(403);
    exit('ไม่สามารถสลับบทบาทนี้ได้');
}

$_SESSION['user_id'] = $availableRoles[$role]['user_id'];
$_SESSION['role'] = $role;
$_SESSION['auth_method'] = 'line';
$_SESSION['user_name'] = $_SESSION['line_display_name'] ?? $availableRoles[$role]['name'];

$redirect = match ($role) {
    'owner' => '/web_app/owner/dashboard.php',
    'technician' => '/web_app/technician/dashboard.php',
    default => '/web_app/customer/dashboard.php',
};

header('Location: ' . $redirect);
exit;
