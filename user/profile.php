<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = currentUser();
$role = $user['role'];
$table = $role === 'admin' ? 'Admin' : ($role === 'owner' ? 'Owner' : null);
$idColumn = $role === 'admin' ? 'admin_id' : 'owner_id';
$message = '';
$error = '';

if (!$table) {
    http_response_code(403);
    exit('บทบาทนี้ยังไม่รองรับการแก้ไขข้อมูลส่วนตัว');
}

$stmt = $pdo->prepare("SELECT * FROM {$table} WHERE {$idColumn} = ?");
$stmt->execute([$user['id']]);
$account = $stmt->fetch();

if (!$account) {
    http_response_code(404);
    exit('ไม่พบบัญชีผู้ใช้งาน');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');

    if ($fullName === '') {
        $error = 'กรุณากรอกชื่อ-นามสกุล';
    } elseif ($role === 'admin') {
        $email = trim($_POST['email'] ?? '');
        $stmt = $pdo->prepare("UPDATE Admin SET full_name = ?, email = ? WHERE admin_id = ?");
        $stmt->execute([$fullName, $email !== '' ? $email : null, $user['id']]);
        $message = 'บันทึกข้อมูลส่วนตัวสำเร็จแล้ว';
    } else {
        $phone = trim($_POST['phone'] ?? '');
        $shopName = trim($_POST['shop_name'] ?? '');
        if ($shopName === '') {
            $error = 'กรุณากรอกชื่อร้าน';
        } else {
            $stmt = $pdo->prepare("UPDATE Owner SET full_name = ?, phone = ?, shop_name = ? WHERE owner_id = ?");
            $stmt->execute([$fullName, $phone !== '' ? $phone : null, $shopName, $user['id']]);
            $message = 'บันทึกข้อมูลส่วนตัวสำเร็จแล้ว';
        }
    }

    $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE {$idColumn} = ?");
    $stmt->execute([$user['id']]);
    $account = $stmt->fetch();
    $_SESSION['user_name'] = $account['full_name'];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ข้อมูลส่วนตัว - ร้านเอกเซอร์วิส</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/kanit@5.0.0/thai-400.css">
<style> * { font-family: 'Kanit', sans-serif; box-sizing: border-box; } body { background: #f4f6fa; } </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
<div class="bg-white w-full max-w-lg rounded-2xl shadow-lg p-8">
  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-2xl font-bold text-slate-800">ข้อมูลส่วนตัว</h1>
      <p class="text-sm text-slate-500">บัญชี <?= htmlspecialchars($account['username']) ?></p>
    </div>
    <a href="<?= $role === 'admin' ? '/web_app/admin/users.php' : '/web_app/owner/dashboard.php' ?>" class="text-sm text-blue-900 hover:underline">กลับหน้าหลัก</a>
  </div>
  <?php if ($message): ?>
    <div class="mb-4 rounded-lg bg-green-50 p-3 text-green-700"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="mb-4 rounded-lg bg-red-50 p-3 text-red-700"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="POST" class="space-y-4">
    <div>
      <label class="mb-1 block text-sm font-medium text-slate-700">Username</label>
      <input value="<?= htmlspecialchars($account['username']) ?>" readonly class="w-full rounded-lg border border-slate-300 bg-slate-100 px-4 py-3 text-slate-500">
    </div>
    <div>
      <label class="mb-1 block text-sm font-medium text-slate-700">ชื่อ-นามสกุล</label>
      <input name="full_name" value="<?= htmlspecialchars($account['full_name']) ?>" required class="w-full rounded-lg border border-slate-300 px-4 py-3">
    </div>
    <?php if ($role === 'admin'): ?>
    <div>
      <label class="mb-1 block text-sm font-medium text-slate-700">อีเมล</label>
      <input name="email" type="email" value="<?= htmlspecialchars($account['email'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 px-4 py-3">
    </div>
    <?php else: ?>
    <div>
      <label class="mb-1 block text-sm font-medium text-slate-700">เบอร์โทรศัพท์</label>
      <input name="phone" value="<?= htmlspecialchars($account['phone'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 px-4 py-3">
    </div>
    <div>
      <label class="mb-1 block text-sm font-medium text-slate-700">ชื่อร้าน</label>
      <input name="shop_name" value="<?= htmlspecialchars($account['shop_name'] ?? '') ?>" required class="w-full rounded-lg border border-slate-300 px-4 py-3">
    </div>
    <?php endif; ?>
    <div class="flex justify-end gap-3 border-t border-slate-200 pt-4">
      <a href="/web_app/user/change_password.php" class="rounded-lg border border-slate-300 px-4 py-3 text-slate-700 hover:bg-slate-50">เปลี่ยนรหัสผ่าน</a>
      <button type="submit" class="rounded-lg bg-blue-900 px-6 py-3 font-semibold text-white hover:bg-blue-800">บันทึกข้อมูล</button>
    </div>
  </form>
</div>
</body>
</html>
