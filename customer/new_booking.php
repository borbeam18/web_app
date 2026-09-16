<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/upload.php';
requireRole('customer');

$user = currentUser();
$message = '';
$error = '';
$services = $pdo->query("SELECT service_id, service_name, category, base_price FROM Services WHERE status = 'เปิดใช้งาน' ORDER BY service_name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serviceId = (int)($_POST['service_id'] ?? 0);
    $date = $_POST['booking_date'] ?? '';
    $time = $_POST['booking_time'] ?? '';
    $description = trim($_POST['problem_description'] ?? '');

    if (!$serviceId || !$date || !$time || $description === '') {
        $error = 'กรุณากรอกข้อมูลให้ครบทุกช่อง';
    } elseif ($date < date('Y-m-d')) {
        $error = 'ไม่สามารถเลือกวันที่ผ่านมาแล้วได้';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM Booking WHERE customer_id = ? AND booking_date = ? AND booking_time = ? AND status_service <> ?');
            $stmt->execute([$user['id'], $date, $time, 'ยกเลิก']);
            if ((int)$stmt->fetchColumn() > 0) {
                throw new RuntimeException('คุณมีงานในวันและเวลานี้อยู่แล้ว');
            }
            $problemPhotoUrl = saveUploadedImage('problem_photo', 'problem');
            $stmt = $pdo->prepare('INSERT INTO Booking (customer_id, service_id, booking_date, booking_time, problem_description, problem_photo_url) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$user['id'], $serviceId, $date, $time, $description, $problemPhotoUrl]);
            $message = 'ส่งคำขอรับบริการแล้ว เจ้าของร้านจะตรวจสอบและยืนยันคิวให้';
        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ขอรับบริการ - ร้านเอกเซอร์วิส</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/kanit@5.0.0/thai-400.css">
</head>
<body class="min-h-screen bg-slate-100 p-4">
<main class="mx-auto w-full max-w-xl rounded-2xl bg-white p-5 shadow sm:p-8">
  <div class="mb-6 flex items-start justify-between gap-3">
    <div><h1 class="text-2xl font-bold text-slate-800">ขอรับบริการ</h1><p class="text-sm text-slate-500">กรอกข้อมูลเพื่อจองคิวนัดหมาย</p></div>
    <a href="/web_app/customer/dashboard.php" class="text-sm text-blue-800">กลับ</a>
  </div>
  <?php if ($message): ?><div class="mb-4 rounded-lg bg-green-50 p-3 text-green-700"><?= htmlspecialchars($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="mb-4 rounded-lg bg-red-50 p-3 text-red-700"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="POST" enctype="multipart/form-data" class="space-y-4">
    <div><label class="mb-1 block text-sm font-medium">ประเภทบริการ</label><select name="service_id" required class="w-full rounded-lg border px-3 py-3"><option value="">เลือกบริการ</option><?php foreach ($services as $service): ?><option value="<?= (int)$service['service_id'] ?>"><?= htmlspecialchars($service['service_name']) ?> (เริ่มต้น ฿<?= number_format($service['base_price'], 2) ?>)</option><?php endforeach; ?></select></div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2"><div><label class="mb-1 block text-sm font-medium">วันที่ต้องการ</label><input name="booking_date" type="date" min="<?= date('Y-m-d') ?>" required class="w-full rounded-lg border px-3 py-3"></div><div><label class="mb-1 block text-sm font-medium">เวลา</label><input name="booking_time" type="time" required class="w-full rounded-lg border px-3 py-3"></div></div>
    <div><label class="mb-1 block text-sm font-medium">รายละเอียดอาการ</label><textarea name="problem_description" rows="5" required class="w-full rounded-lg border px-3 py-3"></textarea></div>
    <div><label class="mb-1 block text-sm font-medium">รูปอาการเสีย (ถ้ามี)</label><input name="problem_photo" type="file" accept="image/jpeg,image/png,image/webp" class="w-full rounded-lg border px-3 py-3 text-sm"><p class="mt-1 text-xs text-slate-500">รองรับ JPG, PNG, WEBP ขนาดไม่เกิน 5 MB</p></div>
    <button class="w-full rounded-lg bg-red-600 px-4 py-3 font-semibold text-white hover:bg-red-700">ส่งคำขอรับบริการ</button>
  </form>
</main>
</body>
</html>
