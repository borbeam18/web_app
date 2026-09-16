<?php
// api/line_login.php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$accessToken = trim($input['access_token'] ?? '');
if ($accessToken === '') {
    echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลการเข้าสู่ระบบ LINE']);
    exit;
}

$ch = curl_init('https://api.line.me/v2/profile');
curl_setopt_array($ch, [
    CURLOPT_HTTPGET => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
]);
$response = curl_exec($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$profile = json_decode($response ?: '', true);

if ($httpCode !== 200 || empty($profile['userId'])) {
    echo json_encode(['success' => false, 'message' => 'ยืนยันตัวตนกับ LINE ไม่สำเร็จ']);
    exit;
}

$lineUserId = $profile['userId'];
$displayName = trim($profile['displayName'] ?? 'ลูกค้า LINE');
$roles = [];

$stmt = $pdo->prepare("SELECT owner_id AS user_id, full_name FROM Owner WHERE line_user_id = ?");
$stmt->execute([$lineUserId]);
if ($user = $stmt->fetch()) {
    $roles['owner'] = ['user_id' => (int)$user['user_id'], 'name' => $user['full_name']];
}

$techQuery = 'SELECT tech_id AS user_id, full_name FROM Technician WHERE line_user_id = ?';
$techParams = [$lineUserId];
if (isset($roles['owner'])) {
    $techQuery = 'SELECT tech_id AS user_id, full_name FROM Technician WHERE owner_id = ? LIMIT 1';
    $techParams = [$roles['owner']['user_id']];
}
$stmt = $pdo->prepare($techQuery);
$stmt->execute($techParams);
if ($user = $stmt->fetch()) {
    $roles['technician'] = ['user_id' => (int)$user['user_id'], 'name' => $user['full_name']];
}

// ถ้า LINE ID เป็น Owner หรือช่างอยู่แล้ว จะไม่ดึง/สร้างบทบาท Customer ซ้ำ
if (!isset($roles['owner']) && !isset($roles['technician'])) {
    $stmt = $pdo->prepare("SELECT customer_id AS user_id, full_name FROM Customer WHERE line_user_id = ?");
    $stmt->execute([$lineUserId]);
    if ($user = $stmt->fetch()) {
        $roles['customer'] = ['user_id' => (int)$user['user_id'], 'name' => $user['full_name']];
    }
}

if (!$roles) {
    $stmt = $pdo->prepare(
        'INSERT INTO Customer (line_user_id, full_name, phone, email, password, address)
         VALUES (?, ?, NULL, NULL, NULL, NULL)'
    );
    $stmt->execute([$lineUserId, $displayName]);
    $roles['customer'] = [
        'user_id' => (int)$pdo->lastInsertId(),
        'name' => $displayName,
    ];
}

$_SESSION['available_roles'] = $roles;

// ถ้ามี Owner ให้เข้า Owner Dashboard เป็นค่าเริ่มต้น
// และคงบทบาท Technician ไว้สำหรับปุ่มสลับบทบาท
$role = isset($roles['owner']) ? 'owner' : array_key_first($roles);
$_SESSION['user_id'] = $roles[$role]['user_id'];
$_SESSION['role'] = $role;
$_SESSION['auth_method'] = 'line';
$_SESSION['line_display_name'] = $displayName;
// ใช้ชื่อจากฐานข้อมูลตามบทบาท ไม่ใช้ Display Name จาก LINE เป็นชื่อในระบบ
$_SESSION['user_name'] = $roles[$role]['name'];

$redirect = match ($role) {
    'owner' => '/web_app/owner/dashboard.php',
    'technician' => '/web_app/technician/dashboard.php',
    default => '/web_app/customer/dashboard.php',
};

echo json_encode(['success' => true, 'role' => $role, 'redirect' => $redirect]);
