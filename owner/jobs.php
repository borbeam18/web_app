<?php
$pageTitle = 'คำขอรับบริการ';
$activePage = 'jobs';
require_once __DIR__ . '/../includes/header_owner.php';

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

$sql = "SELECT b.*, c.full_name AS customer, s.service_name, t.full_name AS tech_name
        FROM Booking b
        JOIN Customer c ON b.customer_id = c.customer_id
        JOIN Services s ON b.service_id = s.service_id
        LEFT JOIN Technician t ON b.tech_id_1 = t.tech_id
        WHERE 1=1";
$params = [];
if ($search) { $sql .= " AND (b.booking_id LIKE ? OR c.full_name LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($status) { $sql .= " AND b.status_service = ?"; $params[] = $status; }
$sql .= " ORDER BY b.booking_date DESC, b.booking_time DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

$statusClass = [
  'รอรับงาน' => 'status-pending',
  'กำลังเดินทาง' => 'status-progress',
  'กำลังซ่อม' => 'status-progress',
  'เสร็จสิ้น' => 'status-done',
  'ยกเลิก' => 'status-cancel'
];
?>

<div class="card p-6">
  <form method="GET" class="flex items-center justify-between mb-5 flex-wrap gap-3">
    <div class="flex items-center gap-3 flex-wrap">
      <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="ค้นหาเลขงาน, ชื่อลูกค้า..." class="px-4 py-2 border border-slate-300 rounded-lg w-full sm:w-72 text-sm outline-none" />
      <select name="status" class="px-4 py-2 border border-slate-300 rounded-lg text-sm">
        <option value="">ทุกสถานะ</option>
        <option <?= $status==='รอรับงาน'?'selected':'' ?>>รอรับงาน</option>
        <option <?= $status==='กำลังเดินทาง'?'selected':'' ?>>กำลังเดินทาง</option>
        <option <?= $status==='กำลังซ่อม'?'selected':'' ?>>กำลังซ่อม</option>
        <option <?= $status==='เสร็จสิ้น'?'selected':'' ?>>เสร็จสิ้น</option>
      </select>
      <button class="btn-primary px-4 py-2 rounded-lg text-sm">ค้นหา</button>
    </div>
    <a href="/web_app/owner/calendar.php" class="btn-accent px-4 py-2 rounded-lg text-sm font-semibold no-underline">+ สร้างงานใหม่</a>
  </form>
  <div class="overflow-x-auto">
    <table class="w-full data-table">
      <thead><tr><th class="text-left p-3">เลขงาน</th><th class="text-left p-3">ลูกค้า</th><th class="text-left p-3">ประเภทงาน</th><th class="text-left p-3">วัน-เวลา</th><th class="text-left p-3">ช่าง</th><th class="text-left p-3">สถานะ</th><th class="text-left p-3">จัดการ</th></tr></thead>
      <tbody>
      <?php foreach ($jobs as $j): ?>
        <tr class="hover:bg-slate-50 cursor-pointer" onclick="location.href='/web_app/owner/job_detail.php?id=<?= $j['booking_id'] ?>'">
          <td class="p-3 font-semibold text-blue-900">#<?= $j['booking_id'] ?></td>
          <td class="p-3"><?= htmlspecialchars($j['customer']) ?></td>
          <td class="p-3"><?= htmlspecialchars($j['service_name']) ?></td>
          <td class="p-3"><?= $j['booking_date'] ?> <?= substr($j['booking_time'],0,5) ?></td>
          <td class="p-3"><?= htmlspecialchars($j['tech_name'] ?? '-') ?></td>
          <td class="p-3"><span class="status-pill <?= $statusClass[$j['status_service']] ?? 'status-pending' ?>"><?= $j['status_service'] ?></span></td>
          <td class="p-3"><button class="text-blue-900 hover:underline text-sm">ดูรายละเอียด</button></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>