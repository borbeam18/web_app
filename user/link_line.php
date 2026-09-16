<?php
require_once __DIR__ . '/../config/db.php';

$pendingLineUserId = $_SESSION['pending_line_user_id'] ?? '';
if ($pendingLineUserId === '') {
    header('Location: /web_app/user/login.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } else {
        try {
            $user = null;
            $role = '';

            $stmt = $pdo->prepare("SELECT owner_id AS user_id, username, password, full_name FROM Owner WHERE username = ?");
            $stmt->execute([$username]);
            $owner = $stmt->fetch();
            if ($owner && password_verify($password, $owner['password'])) {
                $user = $owner;
                $role = 'owner';
                $pdo->prepare('UPDATE Owner SET line_user_id = ? WHERE owner_id = ?')
                    ->execute([$pendingLineUserId, $owner['user_id']]);
            }

            if (!$user) {
                $stmt = $pdo->prepare("SELECT customer_id AS user_id, email, password, full_name FROM Customer WHERE email = ?");
                $stmt->execute([$username]);
                $customer = $stmt->fetch();
                if ($customer && password_verify($password, $customer['password'])) {
                    $user = $customer;
                    $role = 'customer';
                    $pdo->prepare('UPDATE Customer SET line_user_id = ? WHERE customer_id = ?')
                        ->execute([$pendingLineUserId, $customer['user_id']]);
                }
            }

            if ($user) {
                unset($_SESSION['pending_line_user_id']);
                $_SESSION['user_id'] = (int)$user['user_id'];
                $_SESSION['role'] = $role;
                $_SESSION['user_name'] = $user['full_name'];
                header('Location: /web_app/customer/dashboard.php');
                exit;
            }

            $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
        } catch (PDOException $e) {
            $error = 'ไม่สามารถผูกบัญชีได้ กรุณาตรวจสอบข้อมูลอีกครั้ง';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ผูกบัญชี LINE - ร้านเอกเซอร์วิส</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-slate-900 p-4">
  <div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-2xl">
    <h1 class="mb-2 text-2xl font-bold text-slate-800">ผูกบัญชี LINE</h1>
    <p class="mb-6 text-sm text-slate-500">กรอกบัญชีเดิมของเว็บไซต์เพื่อเชื่อมกับ LINE นี้</p>
    <?php if ($error): ?><div class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST" class="space-y-4">
      <div><label class="mb-1 block text-sm font-medium">Email</label><input name="username" required class="w-full rounded-lg border px-4 py-3" placeholder="กรอก email"></div>
      <div><label class="mb-1 block text-sm font-medium">รหัสผ่านเว็บไซต์</label><input name="password" type="password" required class="w-full rounded-lg border px-4 py-3"></div>
      <button class="w-full rounded-lg bg-green-600 py-3 font-semibold text-white hover:bg-green-700">ยืนยันการผูกบัญชี</button>
    </form>
    <a href="/web_app/user/login.php" class="mt-4 block text-center text-sm text-blue-800">กลับหน้าเข้าสู่ระบบ</a>
  </div>
</body>
</html>
