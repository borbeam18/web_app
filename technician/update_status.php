<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('technician');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /web_app/technician/dashboard.php'); exit; }
$user = currentUser();
$bookingId = (int)($_POST['booking_id'] ?? 0);
$status = trim($_POST['status_service'] ?? '');
$allowed = ['รับงานแล้ว', 'กำลังเดินทาง', 'กำลังซ่อม', 'เสร็จสิ้น'];
if (!$bookingId || !in_array($status, $allowed, true)) { header('Location: /web_app/technician/dashboard.php'); exit; }
$stmt = $pdo->prepare('UPDATE Booking SET status_service = ? WHERE booking_id = ? AND (tech_id_1 = ? OR tech_id_2 = ?)');
$stmt->execute([$status, $bookingId, $user['id'], $user['id']]);
header('Location: /web_app/technician/dashboard.php');
exit;
