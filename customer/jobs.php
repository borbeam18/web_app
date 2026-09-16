<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('customer');
$user = currentUser();
$stmt = $pdo->prepare("SELECT b.*, s.service_name, t.full_name AS technician_name FROM Booking b JOIN Services s ON b.service_id = s.service_id LEFT JOIN Technician t ON b.tech_id_1 = t.tech_id WHERE b.customer_id = ? ORDER BY b.booking_date DESC, b.booking_time DESC");
$stmt->execute([$user['id']]);
$jobs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>งานของฉัน</title><script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/kanit@5.0.0/thai-400.css"></head>
<body class="min-h-screen bg-slate-100 p-4"><main class="mx-auto w-full max-w-4xl rounded-2xl bg-white p-5 shadow sm:p-8"><div class="mb-6 flex items-center justify-between gap-3"><div><h1 class="text-2xl font-bold text-slate-800">งานของฉัน</h1><p class="text-sm text-slate-500">ติดตามสถานะคำขอรับบริการ</p></div><a href="/web_app/customer/dashboard.php" class="text-sm text-blue-800">กลับหน้าหลัก</a></div><div class="space-y-4">
<?php foreach ($jobs as $job): ?><article class="rounded-xl border border-slate-200 p-4"><div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="font-bold text-slate-800">#<?= (int)$job['booking_id'] ?> · <?= htmlspecialchars($job['service_name']) ?></h2><p class="text-sm text-slate-500"><?= htmlspecialchars($job['booking_date']) ?> เวลา <?= htmlspecialchars(substr($job['booking_time'], 0, 5)) ?></p></div><span class="rounded-full bg-blue-100 px-3 py-1 text-sm text-blue-800"><?= htmlspecialchars($job['status_service']) ?></span></div><p class="mt-3 text-sm text-slate-600"><?= nl2br(htmlspecialchars($job['problem_description'] ?? '')) ?></p><p class="mt-2 text-sm text-slate-500">ช่าง: <?= htmlspecialchars($job['technician_name'] ?? 'รอมอบหมาย') ?></p></article><?php endforeach; ?>
<?php if (!$jobs): ?><div class="rounded-xl bg-slate-50 p-6 text-center text-slate-500">ยังไม่มีงานบริการ</div><?php endif; ?></div></main></body></html>
