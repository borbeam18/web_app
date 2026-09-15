<?php
ob_start();
$pageTitle = 'รายละเอียดงาน';
$activePage = 'jobDetail';
require_once __DIR__ . '/includes/auth.php';
requireRole('owner');
$flashStatus = $_SESSION['status_flash'] ?? null;
unset($_SESSION['status_flash']);

$bookingId = (int)($_GET['id'] ?? 0);
if (!$bookingId) { header('Location: /web_app/owner_jobs.php'); exit; }

// Fetch booking
$stmt = $pdo->prepare("
  SELECT b.*, c.full_name AS customer, c.phone, c.address, s.service_name,
         t1.full_name AS tech1_name, t2.full_name AS tech2_name
  FROM Booking b
  JOIN Customer c ON b.customer_id = c.customer_id
  JOIN Services s ON b.service_id = s.service_id
  LEFT JOIN Technician t1 ON b.tech_id_1 = t1.tech_id
  LEFT JOIN Technician t2 ON b.tech_id_2 = t2.tech_id
  WHERE b.booking_id = ?
");
$stmt->execute([$bookingId]);
$job = $stmt->fetch();
if (!$job) { echo 'ไม่พบข้อมูลงาน'; exit; }

// Materials
$stmt = $pdo->prepare("SELECT * FROM Job_Material WHERE booking_id = ?");
$stmt->execute([$bookingId]);
$materials = $stmt->fetchAll();

// Payment
$stmt = $pdo->prepare("SELECT * FROM Payment WHERE booking_id = ?");
$stmt->execute([$bookingId]);
$payment = $stmt->fetch();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $newStatus = $_POST['status_service'] ?? '';
    $pdo->prepare("UPDATE Booking SET status_service = ? WHERE booking_id = ?")
        ->execute([$newStatus, $bookingId]);
    $_SESSION['status_flash'] = $newStatus;
    header('Location: /web_app/owner/job_detail.php?id=' . $bookingId);
    exit;
}

// Status steps
$statusSteps = ['รอรับงาน', 'รับงานแล้ว', 'กำลังเดินทาง', 'กำลังซ่อม', 'เสร็จสิ้น'];
$currentIdx  = array_search($job['status_service'], $statusSteps);
if ($currentIdx === false) $currentIdx = 0;

require_once __DIR__ . '/../includes/header_owner.php';
?>

<?php if ($flashStatus): ?>
<div id="statusSuccessModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
  <div class="w-full max-w-md rounded-2xl bg-white p-8 text-center shadow-2xl">
    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-100 text-3xl text-green-600">✓</div>
    <h2 class="mb-2 text-2xl font-bold text-slate-800">อัปเดตสถานะเรียบร้อยแล้ว</h2>
    <p class="mb-6 text-slate-600">สถานะปัจจุบัน: <strong><?= htmlspecialchars($flashStatus) ?></strong></p>
    <button type="button" onclick="closeStatusSuccessModal()" class="rounded-lg bg-blue-900 px-6 py-3 font-semibold text-white hover:bg-blue-800">ตกลง</button>
  </div>
</div>
<script>
function closeStatusSuccessModal() {
  const modal = document.getElementById('statusSuccessModal');
  if (modal) modal.remove();
}
const statusModal = document.getElementById('statusSuccessModal');
if (statusModal) {
  statusModal.addEventListener('click', event => {
    if (event.target === statusModal) closeStatusSuccessModal();
  });
}
</script>
<?php endif; ?>

<div class="mb-4 flex items-center justify-between flex-wrap gap-3">
  <div>
    <h2 class="text-2xl font-bold text-slate-800"><?= htmlspecialchars($job['service_name']) ?></h2>
    <div class="text-sm text-slate-500 mt-1">รหัสงาน: #<?= $job['booking_id'] ?> • วันที่นัดหมาย: <?= $job['booking_date'] ?>, <?= substr($job['booking_time'],0,5) ?> น.</div>
  </div>
  <div class="flex items-center gap-2">
    <a href="/web_app/owner_jobs.php" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">← ย้อนกลับ</a>
    <form method="POST" class="flex gap-2" onsubmit="this.querySelector('button[type=submit]').disabled = true; this.querySelector('button[type=submit]').textContent = 'กำลังบันทึก...';">
      <input type="hidden" name="action" value="update_status">
      <select name="status_service" class="px-3 py-2 border border-slate-300 rounded-lg text-sm">
        <?php foreach ($statusSteps as $s): ?>
          <option value="<?= $s ?>" <?= $s === $job['status_service'] ? 'selected' : '' ?>><?= $s ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn-primary px-4 py-2 rounded-lg text-sm font-semibold">อัปเดตสถานะ</button>
    </form>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
  <div class="card p-6">
    <h3 class="font-bold text-slate-800 mb-4">👤 ข้อมูลลูกค้า</h3>
    <div class="space-y-3 text-sm">
      <div><span class="text-slate-500">ชื่อ:</span> <span class="font-semibold"><?= htmlspecialchars($job['customer']) ?></span></div>
      <div><span class="text-slate-500">โทร:</span> <span class="font-semibold"><?= htmlspecialchars($job['phone']) ?></span></div>
      <div><span class="text-slate-500">สถานที่:</span> <span class="font-semibold"><?= htmlspecialchars($job['address']) ?></span></div>
      <div><span class="text-slate-500">อาการ:</span> <div class="font-semibold"><?= nl2br(htmlspecialchars($job['problem_description'])) ?></div></div>
    </div>
  </div>

  <div class="card p-6 lg:col-span-2">
    <h3 class="font-bold text-slate-800 mb-5">✅ สถานะงาน</h3>
    <div class="flex items-center justify-between mb-8 px-2 flex-wrap gap-2">
      <?php foreach ($statusSteps as $idx => $s):
        $cls = $idx < $currentIdx ? 'done' : ($idx === $currentIdx ? 'current' : '');
      ?>
        <div class="progress-step <?= $cls ?>">
          <div class="circle"><?= $idx < $currentIdx ? '✓' : ($idx+1) ?></div>
          <div class="ml-2 text-sm"><?= $s ?></div>
        </div>
        <?php if ($idx < count($statusSteps)-1): ?>
          <div class="progress-line <?= $idx < $currentIdx ? 'done' : '' ?>"></div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="card p-6 mt-5">
  <h3 class="font-bold text-slate-800 mb-4">📋 วัสดุอุปกรณ์ / ค่าใช้จ่าย</h3>
  <table class="w-full data-table mb-4">
    <thead><tr><th class="text-left p-3">รายการ</th><th class="text-left p-3">จำนวน</th><th class="text-left p-3">ราคา/หน่วย</th><th class="text-left p-3">ค่าแรง</th><th class="text-left p-3">รวม</th></tr></thead>
    <tbody>
    <?php $totalMaterial = 0; foreach ($materials as $m): $totalMaterial += $m['total_price']; ?>
      <tr><td class="p-3"><?= htmlspecialchars($m['material_name']) ?></td><td class="p-3"><?= $m['quantity'] ?></td><td class="p-3"><?= number_format($m['unit_price'],2) ?></td><td class="p-3"><?= number_format($m['labor_cost'],2) ?></td><td class="p-3 font-semibold"><?= number_format($m['total_price'],2) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <div class="text-right pt-4 border-t border-slate-200">
    <div class="text-sm text-slate-500">รวมค่าวัสดุ+ค่าแรง: <?= number_format($totalMaterial, 2) ?> ฿</div>
    <div class="text-2xl font-bold text-slate-800">ยอดสุทธิ: <?= number_format($totalMaterial, 2) ?> ฿</div>
  </div>
</div>

<?php if ($payment): ?>
<div class="card p-6 mt-5">
  <h3 class="font-bold text-slate-800 mb-4">💰 การชำระเงิน</h3>
  <div class="flex items-center gap-4">
    <?php if ($payment['slip_photo_url']): ?>
      <img src="<?= htmlspecialchars($payment['slip_photo_url']) ?>" class="image-thumb" />
    <?php endif; ?>
    <div>
      <div class="text-lg font-bold">฿<?= number_format($payment['amount'], 2) ?></div>
      <div class="text-sm text-slate-500"><?= $payment['payment_date'] ?></div>
      <div class="mt-2"><span class="status-pill <?= $payment['verify_status']==='อนุมัติแล้ว'?'status-done':'status-pending' ?>"><?= $payment['verify_status'] ?></span></div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>