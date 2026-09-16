<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/upload.php';
requireRole('customer');
$user = currentUser();
$error = '';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    try {
        $stmt = $pdo->prepare('SELECT booking_id FROM Booking WHERE booking_id = ? AND customer_id = ?');
        $stmt->execute([$bookingId, $user['id']]);
        if (!$stmt->fetch()) throw new RuntimeException('ไม่พบงานของคุณ');
        $slip = saveUploadedImage('slip', 'payment');
        if (!$slip || $amount <= 0) throw new RuntimeException('กรุณากรอกยอดเงินและแนบสลิป');
        $stmt = $pdo->prepare('INSERT INTO Payment (booking_id, amount, slip_photo_url, payment_date) VALUES (?, ?, ?, NOW())');
        $stmt->execute([$bookingId, $amount, $slip]);
        $message = 'ส่งหลักฐานการชำระเงินแล้ว';
    } catch (RuntimeException $exception) { $error = $exception->getMessage(); }
}
$stmt = $pdo->prepare("SELECT b.booking_id, s.service_name FROM Booking b JOIN Services s ON b.service_id = s.service_id WHERE b.customer_id = ? AND b.status_service = 'เสร็จสิ้น' ORDER BY b.booking_date DESC");
$stmt->execute([$user['id']]);
$jobs = $stmt->fetchAll();
?>
<!DOCTYPE html><html lang="th"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>ส่งสลิป</title><script src="https://cdn.tailwindcss.com"></script></head><body class="min-h-screen bg-slate-100 p-4"><main class="mx-auto w-full max-w-xl rounded-2xl bg-white p-6 shadow"><h1 class="mb-5 text-2xl font-bold">ส่งหลักฐานการชำระเงิน</h1><?php if ($message): ?><div class="mb-4 rounded-lg bg-green-50 p-3 text-green-700"><?= htmlspecialchars($message) ?></div><?php endif; ?><?php if ($error): ?><div class="mb-4 rounded-lg bg-red-50 p-3 text-red-700"><?= htmlspecialchars($error) ?></div><?php endif; ?><form method="POST" enctype="multipart/form-data" class="space-y-4"><div><label class="mb-1 block text-sm font-medium">งานบริการ</label><select name="booking_id" required class="w-full rounded-lg border px-3 py-3"><option value="">เลือกงาน</option><?php foreach ($jobs as $job): ?><option value="<?= (int)$job['booking_id'] ?>">#<?= (int)$job['booking_id'] ?> <?= htmlspecialchars($job['service_name']) ?></option><?php endforeach; ?></select></div><div><label class="mb-1 block text-sm font-medium">ยอดเงิน</label><input name="amount" type="number" min="0.01" step="0.01" required class="w-full rounded-lg border px-3 py-3"></div><div><label class="mb-1 block text-sm font-medium">รูปสลิป</label><input name="slip" type="file" accept="image/jpeg,image/png,image/webp" required class="w-full rounded-lg border px-3 py-3"></div><button class="w-full rounded-lg bg-blue-900 px-4 py-3 font-semibold text-white">ส่งสลิป</button></form></main></body></html>
