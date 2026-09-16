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
$stmt = $pdo->prepare('SELECT tech_id AS user_id, full_name, "technician" AS role FROM Technician WHERE line_user_id = ?');
$stmt->execute([$lineUserId]);
$user = $stmt->fetch();

if (!$user) {
    $stmt = $pdo->prepare('SELECT customer_id AS user_id, full_name, "customer" AS role FROM Customer WHERE line_user_id = ?');
    $stmt->execute([$lineUserId]);
    $user = $stmt->fetch();
}

if ($user && $user['role'] === 'customer') {
    if ($user['full_name'] === 'ลูกค้า LINE' && $displayName !== '') {
        $pdo->prepare('UPDATE Customer SET full_name = ? WHERE customer_id = ?')
            ->execute([$displayName, $user['user_id']]);
        $user['full_name'] = $displayName;
    }
}

if (!$user) {
    // ลูกค้าใหม่เข้าใช้งานผ่าน LINE ได้ทันที โดยสร้างบัญชี Customer อัตโนมัติ
    $stmt = $pdo->prepare(
        'INSERT INTO Customer (line_user_id, full_name, phone, email, password, address)
         VALUES (?, ?, NULL, NULL, NULL, NULL)'
    );
    $stmt->execute([$lineUserId, $displayName]);
    $user = [
        'user_id' => (int)$pdo->lastInsertId(),
        'full_name' => $displayName,
        'role' => 'customer',
    ];
}

$_SESSION['user_id'] = (int)$user['user_id'];
$_SESSION['role'] = $user['role'];
$_SESSION['auth_method'] = 'line';
$_SESSION['user_name'] = $user['full_name'];

$redirect = $user['role'] === 'technician'
    ? '/web_app/technician/dashboard.php'
    : '/web_app/customer/dashboard.php';

echo json_encode(['success' => true, 'role' => $user['role'], 'redirect' => $redirect]);
