<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('customer', 'technician');

$user = currentUser();
$roleLabel = $user['role'] === 'technician' ? 'พนักงาน/ช่าง' : 'ลูกค้า';
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>หน้าหลัก - ร้านเอกเซอร์วิส</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/kanit@5.0.0/thai-400.css">
<style>
  * { font-family: 'Kanit', sans-serif; box-sizing: border-box; }
  body { background: #f4f6fa; }
</style>
</head>
<body class="min-h-screen">
<header class="bg-blue-900 px-6 py-4 text-white shadow">
  <div class="mx-auto flex max-w-5xl items-center justify-between">
    <div>
      <div class="text-xl font-bold">ร้านเอกเซอร์วิส</div>
      <div class="text-sm text-white/70">หน้าหลักผู้ใช้งาน</div>
    </div>
    <a href="/web_app/api/logout.php" class="rounded-lg bg-red-500 px-4 py-2 text-sm font-semibold hover:bg-red-600">ออกจากระบบ</a>
  </div>
</header>

<main class="mx-auto max-w-5xl p-6">
  <div class="mb-6 rounded-2xl bg-white p-6 shadow">
    <h1 class="mb-2 text-2xl font-bold text-slate-800">ยินดีต้อนรับ</h1>
    <p class="text-slate-600">คุณ <?= htmlspecialchars($user['name']) ?></p>
    <span class="mt-3 inline-block rounded-full bg-blue-100 px-3 py-1 text-sm text-blue-800">บทบาท: <?= htmlspecialchars($roleLabel) ?></span>
  </div>

  <div class="mb-5 grid gap-5 sm:grid-cols-2">
    <a href="/web_app/customer/new_booking.php" class="rounded-2xl bg-red-600 p-6 text-white shadow hover:bg-red-700">
      <div class="mb-2 text-2xl">🛠️</div>
      <h2 class="font-bold">ขอรับบริการ</h2>
      <p class="text-sm text-white/80">สร้างคำขอและเลือกวันเวลานัดหมาย</p>
    </a>
    <a href="/web_app/customer/jobs.php" class="rounded-2xl bg-blue-900 p-6 text-white shadow hover:bg-blue-800">
      <div class="mb-2 text-2xl">📋</div>
      <h2 class="font-bold">งานของฉัน</h2>
      <p class="text-sm text-white/80">ติดตามสถานะงานและประวัติบริการ</p>
    </a>
    <a href="/web_app/customer/upload_payment.php" class="rounded-2xl bg-white p-6 shadow hover:shadow-lg">
      <div class="mb-2 text-2xl">💳</div>
      <h2 class="font-bold text-slate-800">ส่งสลิปชำระเงิน</h2>
      <p class="text-sm text-slate-500">แนบหลักฐานการโอนเงิน</p>
    </a>
  </div>

  <div class="grid gap-5 md:grid-cols-3">
    <a href="/web_app/user/profile.php" class="rounded-2xl bg-white p-6 shadow hover:shadow-lg">
      <div class="mb-2 text-2xl">👤</div>
      <h2 class="font-bold text-slate-800">ข้อมูลส่วนตัว</h2>
      <p class="text-sm text-slate-500">ดูและแก้ไขข้อมูลของคุณ</p>
    </a>
    <?php if (($user['auth_method'] ?? 'password') !== 'line'): ?>
    <a href="/web_app/user/change_password.php" class="rounded-2xl bg-white p-6 shadow hover:shadow-lg">
      <div class="mb-2 text-2xl">🔑</div>
      <h2 class="font-bold text-slate-800">เปลี่ยนรหัสผ่าน</h2>
      <p class="text-sm text-slate-500">ตั้งรหัสผ่านใหม่ของคุณ</p>
    </a>
    <?php endif; ?>
    <a href="/web_app/api/logout.php" class="rounded-2xl bg-white p-6 shadow hover:shadow-lg">
      <div class="mb-2 text-2xl">🚪</div>
      <h2 class="font-bold text-slate-800">ออกจากระบบ</h2>
      <p class="text-sm text-slate-500">ออกจากบัญชีผู้ใช้งาน</p>
    </a>
  </div>
</main>
</body>
</html>
