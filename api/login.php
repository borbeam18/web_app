<?php
// api/login.php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';

if (!$username || !$password) {
    echo json_encode(['success' => false, 'message' => 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน']);
    exit;
}

try {
    // 1) ตรวจสอบ Admin
    $stmt = $pdo->prepare("SELECT * FROM Admin WHERE username = ? AND status = 'Active'");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']   = $user['admin_id'];
        $_SESSION['role']      = 'admin';
        $_SESSION['user_name'] = $user['full_name'];
        echo json_encode([
            'success'  => true,
            'role'     => 'admin',
            'redirect' => '/web_app/admin/users.php'
        ]);
        exit;
    }

    // 2) ตรวจสอบ Owner
    $stmt = $pdo->prepare("SELECT * FROM Owner WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']   = $user['owner_id'];
        $_SESSION['role']      = 'owner';
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['auth_method'] = 'password';
        $_SESSION['available_roles'] = [
            'owner' => [
                'user_id' => (int)$user['owner_id'],
                'name' => $user['full_name'],
            ],
        ];

        // ถ้า Owner มีบัญชีช่างที่ผูกกับ LINE ID เดียวกัน ให้สลับเป็นช่างได้
        if (!empty($user['line_user_id'])) {
            $techStmt = $pdo->prepare('SELECT tech_id, full_name FROM Technician WHERE line_user_id = ?');
            $techStmt->execute([$user['line_user_id']]);
            if ($technician = $techStmt->fetch()) {
                $_SESSION['available_roles']['technician'] = [
                    'user_id' => (int)$technician['tech_id'],
                    'name' => $technician['full_name'],
                ];
            }
        }

        echo json_encode([
            'success'  => true,
            'role'     => 'owner',
            'redirect' => '/web_app/owner/dashboard.php'
        ]);
        exit;
    }

    // 3) ตรวจสอบ Technician (ผ่าน line_user_id หรือ username)
    $stmt = $pdo->prepare("SELECT * FROM Technician WHERE line_user_id = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['user_id']   = $user['tech_id'];
        $_SESSION['role']      = 'technician';
        $_SESSION['auth_method'] = 'password';
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['available_roles'] = [
            'technician' => [
                'user_id' => (int)$user['tech_id'],
                'name' => $user['full_name'],
            ],
        ];

        // ถ้าช่างมี Owner ที่ใช้ LINE ID เดียวกัน ให้สลับกลับเป็น Owner ได้
        if (!empty($user['line_user_id'])) {
            $ownerStmt = $pdo->prepare('SELECT owner_id, full_name FROM Owner WHERE line_user_id = ?');
            $ownerStmt->execute([$user['line_user_id']]);
            if ($owner = $ownerStmt->fetch()) {
                $_SESSION['available_roles']['owner'] = [
                    'user_id' => (int)$owner['owner_id'],
                    'name' => $owner['full_name'],
                ];
            }
        }

        echo json_encode([
            'success'  => true,
            'role'     => 'technician',
            'redirect' => '/web_app/technician/dashboard.php'
        ]);
        exit;
    }

    // 4) ตรวจสอบ Customer ด้วย Email หรือ LINE ID + password
    $stmt = $pdo->prepare("SELECT * FROM Customer WHERE email = ? OR line_user_id = ? LIMIT 1");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    if ($user && !empty($user['password']) && password_verify($password, $user['password'])) {
        $_SESSION['user_id']   = $user['customer_id'];
        $_SESSION['role']      = 'customer';
        $_SESSION['auth_method'] = 'password';
        $_SESSION['user_name'] = $user['full_name'];
        echo json_encode([
            'success'  => true,
            'role'     => 'customer',
            'redirect' => '/web_app/customer/dashboard.php'
        ]);
        exit;
    }

    // ไม่พบผู้ใช้ในทุกตาราง
    echo json_encode(['success' => false, 'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}