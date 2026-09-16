<?php
require_once __DIR__ . '/../config/db.php';

$roles = $_SESSION['pending_line_roles'] ?? [];
if (!$roles) {
    header('Location: /web_app/user/login.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? '';
    if (!isset($roles[$role])) {
        $error = 'บทบาทที่เลือกไม่ถูกต้อง';
    } else {
        $_SESSION['user_id'] = $roles[$role]['user_id'];
        $_SESSION['role'] = $role;
        $_SESSION['auth_method'] = 'line';
        $_SESSION['user_name'] = $roles[$role]['name'];
        unset($_SESSION['pending_line_user_id'], $_SESSION['pending_line_roles']);
        $redirect = match ($role) {
            'owner' => '/web_app/owner/dashboard.php',
            'technician' => '/web_app/technician/dashboard.php',
            default => '/web_app/customer/dashboard.php',
        };
        header('Location: ' . $redirect);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>เลือกบทบาท - ร้านเอกเซอร์วิส</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/kanit@5.0.0/thai-400.css">
</head>
<body class="min-h-screen bg-slate-100 p-4 flex items-center justify-center">
<main class="w-full max-w-md rounded-2xl bg-white p-6 shadow sm:p-8">
  <h1 class="text-2xl font-bold text-slate-800">เลือกบทบาทการใช้งาน</h1>
  <p class="mt-2 text-sm text-slate-500">บัญชี LINE นี้มีมากกว่าหนึ่งบทบาท</p>
  <?php if ($error): ?><div class="mt-4 rounded-lg bg-red-50 p-3 text-red-700"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="POST" class="mt-6 space-y-3">
    <?php if (isset($roles['owner'])): ?><button name="role" value="owner" class="w-full rounded-xl bg-blue-900 px-4 py-4 text-left font-semibold text-white">Owner / เจ้าของร้าน<span class="mt-1 block text-sm font-normal text-white/70">จัดการงาน คิว การเงิน และรายงาน</span></button><?php endif; ?>
    <?php if (isset($roles['technician'])): ?><button name="role" value="technician" class="w-full rounded-xl bg-emerald-600 px-4 py-4 text-left font-semibold text-white">Technician / พนักงานช่าง<span class="mt-1 block text-sm font-normal text-white/70">ดูงานที่ได้รับและอัปเดตสถานะ</span></button><?php endif; ?>
    <?php if (isset($roles['customer'])): ?><button name="role" value="customer" class="w-full rounded-xl bg-slate-600 px-4 py-4 text-left font-semibold text-white">Customer / ลูกค้า<span class="mt-1 block text-sm font-normal text-white/70">ขอรับบริการและติดตามงาน</span></button><?php endif; ?>
  </form>
</main>
</body>
</html>
