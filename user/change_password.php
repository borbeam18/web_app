<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = currentUser();
$table = $user['role'] === 'admin' ? 'Admin' : ($user['role'] === 'owner' ? 'Owner' : null);
$idColumn = $user['role'] === 'admin' ? 'admin_id' : 'owner_id';
$message = '';
$error = '';

if (!$table) {
    http_response_code(403);
    exit('บทบาทนี้ยังไม่รองรับการเปลี่ยนรหัสผ่าน');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $error = 'กรุณากรอกข้อมูลให้ครบทุกช่อง';
    } elseif (strlen($newPassword) < 8) {
        $error = 'รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน';
    } else {
        $stmt = $pdo->prepare("SELECT password FROM {$table} WHERE {$idColumn} = ?");
        $stmt->execute([$user['id']]);
        $account = $stmt->fetch();

        if (!$account || !password_verify($currentPassword, $account['password'])) {
            $error = 'รหัสผ่านเดิมไม่ถูกต้อง';
        } else {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE {$table} SET password = ? WHERE {$idColumn} = ?");
            $stmt->execute([$newHash, $user['id']]);
            $message = 'เปลี่ยนรหัสผ่านสำเร็จแล้ว';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>เปลี่ยนรหัสผ่าน - ร้านเอกเซอร์วิส</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/kanit@5.0.0/thai-400.css">
<style> * { font-family: 'Kanit', sans-serif; box-sizing: border-box; } body { background: #f4f6fa; } </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
<div class="bg-white w-full max-w-lg rounded-2xl shadow-lg p-8">
  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-2xl font-bold text-slate-800">เปลี่ยนรหัสผ่าน</h1>
      <p class="text-sm text-slate-500">บัญชี: <?= htmlspecialchars($user['name']) ?></p>
    </div>
    <a href="<?= $user['role'] === 'admin' ? '/web_app/admin/users.php' : ($user['role'] === 'customer' ? '/web_app/user/login.php' : '/web_app/owner/dashboard.php') ?>" class="text-sm text-blue-900 hover:underline">กลับหน้าหลัก</a>
  </div>
  <?php if ($message): ?>
    <div class="mb-4 rounded-lg bg-green-50 p-3 text-green-700"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="mb-4 rounded-lg bg-red-50 p-3 text-red-700"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="POST" class="space-y-4">
    <div>
      <label class="mb-1 block text-sm font-medium text-slate-700">รหัสผ่านเดิม</label>
      <input name="current_password" type="password" required autocomplete="current-password" class="w-full rounded-lg border border-slate-300 px-4 py-3">
    </div>
    <div>
      <label class="mb-1 block text-sm font-medium text-slate-700">รหัสผ่านใหม่</label>
      <input name="new_password" type="password" required minlength="8" autocomplete="new-password" class="w-full rounded-lg border border-slate-300 px-4 py-3">
      <p class="mt-1 text-xs text-slate-500">ต้องมีอย่างน้อย 8 ตัวอักษร</p>
    </div>
    <div>
      <label class="mb-1 block text-sm font-medium text-slate-700">ยืนยันรหัสผ่านใหม่</label>
      <input name="confirm_password" type="password" required minlength="8" autocomplete="new-password" class="w-full rounded-lg border border-slate-300 px-4 py-3">
    </div>
    <button type="submit" class="w-full rounded-lg bg-blue-900 px-4 py-3 font-semibold text-white hover:bg-blue-800">บันทึกรหัสผ่านใหม่</button>
  </form>
</div>
</body>
</html>
