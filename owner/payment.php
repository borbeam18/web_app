<?php
$pageTitle = 'ตรวจสอบการชำระเงิน';
$activePage = 'payment';
require_once __DIR__ . '/../includes/header_owner.php';

$filter = $_GET['filter'] ?? 'รอตรวจสอบ';

// Handle approve
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'approve') {
    $pdo->prepare("UPDATE Payment SET verify_status = 'อนุมัติแล้ว', verified_by = ? WHERE payment_id = ?")
        ->execute([$_SESSION['user_id'], (int)$_POST['payment_id']]);
    header('Location: /web_app/owner/payment.php?filter=' . $filter);
    exit;
}

$stmt = $pdo->prepare("
  SELECT p.*, b.booking_id, c.full_name AS customer
  FROM Payment p
  JOIN Booking b ON p.booking_id = b.booking_id
  JOIN Customer c ON b.customer_id = c.customer_id
  WHERE p.verify_status = ?
  ORDER BY p.payment_date DESC
");
$stmt->execute([$filter]);
$slips = $stmt->fetchAll();

$stats = [
  'pending' => $pdo->query("SELECT COALESCE(SUM(amount),0) FROM Payment WHERE verify_status='รอตรวจสอบ'")->fetchColumn(),
  'approved' => $pdo->query("SELECT COALESCE(SUM(amount),0) FROM Payment WHERE verify_status='อนุมัติแล้ว' AND MONTH(payment_date)=MONTH(CURRENT_DATE)")->fetchColumn(),
  'total' => $pdo->query("SELECT COALESCE(SUM(amount),0) FROM Payment")->fetchColumn(),
];
?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-5">
  <div class="stat-card"><div class="text-xs text-slate-500 mb-1">ยอดรอตรวจสอบ</div><div class="text-2xl font-bold">฿<?= number_format($stats['pending'],0) ?></div></div>
  <div class="stat-card green"><div class="text-xs text-slate-500 mb-1">อนุมัติแล้ว (เดือนนี้)</div><div class="text-2xl font-bold">฿<?= number_format($stats['approved'],0) ?></div></div>
  <div class="stat-card orange"><div class="text-xs text-slate-500 mb-1">ยอดรวมทั้งหมด</div><div class="text-2xl font-bold">฿<?= number_format($stats['total'],0) ?></div></div>
</div>

<div class="card p-6">
  <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
    <h3 class="font-bold text-slate-800">สลิปโอนเงิน (<?= $filter ?>)</h3>
    <div class="flex gap-2">
      <a href="?filter=รอตรวจสอบ" class="px-3 py-1.5 text-sm btn-outline rounded-lg no-underline">รอตรวจสอบ</a>
      <a href="?filter=อนุมัติแล้ว" class="px-3 py-1.5 text-sm btn-outline rounded-lg no-underline">อนุมัติแล้ว</a>
    </div>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($slips as $s): ?>
    <div class="card p-4">
      <div class="flex items-center justify-between mb-3">
        <span class="text-xs font-semibold text-slate-500">#<?= $s['booking_id'] ?></span>
        <span class="status-pill <?= $s['verify_status']==='รอตรวจสอบ'?'status-pending':'status-done' ?>"><?= $s['verify_status'] ?></span>
      </div>
      <div class="bg-slate-100 rounded-lg h-32 mb-3 flex items-center justify-center text-slate-400">
        <?php if ($s['slip_photo_url']): ?>
          <img src="<?= htmlspecialchars($s['slip_photo_url']) ?>" class="h-full w-full object-cover rounded-lg" />
        <?php else: ?> สลิป<?php endif; ?>
      </div>
      <div class="text-sm font-semibold"><?= htmlspecialchars($s['customer']) ?></div>
      <div class="text-xs text-slate-500 mb-3"><?= $s['payment_date'] ?></div>
      <div class="flex items-center justify-between pt-3 border-t border-slate-200">
        <div class="text-lg font-bold">฿<?= number_format($s['amount'],2) ?></div>
        <?php if ($s['verify_status'] === 'รอตรวจสอบ'): ?>
        <form method="POST"><input type="hidden" name="action" value="approve"><input type="hidden" name="payment_id" value="<?= $s['payment_id'] ?>"><button class="btn-primary px-3 py-1.5 rounded-lg text-xs">อนุมัติ</button></form>
        <?php else: ?><span class="badge badge-green">✓ อนุมัติแล้ว</span><?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>