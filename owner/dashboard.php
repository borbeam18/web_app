<?php
// owner_dashboard.php
$pageTitle = 'Dashboard';
$activePage = 'dashboard';
$breadcrumb = 'แดชบอร์ดสรุปสารสนเทศ';
require_once __DIR__ . '/../includes/header_owner.php';

// Stats
$stats = [];
$stats['total_jobs'] = $pdo->query("SELECT COUNT(*) FROM Booking WHERE MONTH(booking_date) = MONTH(CURRENT_DATE) AND YEAR(booking_date) = YEAR(CURRENT_DATE)")->fetchColumn();
$stats['pending']    = $pdo->query("SELECT COUNT(*) FROM Payment WHERE verify_status = 'รอตรวจสอบ'")->fetchColumn();
$stats['revenue']    = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM Payment WHERE verify_status = 'อนุมัติแล้ว' AND MONTH(payment_date) = MONTH(CURRENT_DATE)")->fetchColumn();
$stats['tech_free']  = $pdo->query("SELECT COUNT(*) FROM Technician WHERE status = 'ว่าง'")->fetchColumn();
$stats['tech_total'] = $pdo->query("SELECT COUNT(*) FROM Technician")->fetchColumn();

// Recent jobs
$recentJobs = $pdo->query("
  SELECT b.booking_id, b.booking_date, b.status_service, c.full_name AS customer, s.service_name
  FROM Booking b
  JOIN Customer c ON b.customer_id = c.customer_id
  JOIN Services s ON b.service_id = s.service_id
  ORDER BY b.booking_date DESC, b.booking_time DESC
  LIMIT 5
")->fetchAll();

// Pending slips
$pendingSlips = $pdo->query("
  SELECT p.payment_id, p.amount, p.verify_status, b.booking_id, c.full_name AS customer
  FROM Payment p
  JOIN Booking b ON p.booking_id = b.booking_id
  JOIN Customer c ON b.customer_id = c.customer_id
  WHERE p.verify_status = 'รอตรวจสอบ'
  ORDER BY p.payment_date DESC
  LIMIT 5
")->fetchAll();

// Monthly revenue for chart
$monthlyRevenue = $pdo->query("
  SELECT MONTH(payment_date) AS m, SUM(amount) AS total
  FROM Payment WHERE verify_status = 'อนุมัติแล้ว' AND YEAR(payment_date) = YEAR(CURRENT_DATE)
  GROUP BY MONTH(payment_date) ORDER BY m
")->fetchAll();
$revenueByMonth = array_fill(1, 12, 0);
foreach ($monthlyRevenue as $r) $revenueByMonth[(int)$r['m']] = (float)$r['total'];

// Service distribution
$serviceDist = $pdo->query("
  SELECT s.category, COUNT(*) AS cnt
  FROM Booking b JOIN Services s ON b.service_id = s.service_id
  GROUP BY s.category
")->fetchAll();
?>

<div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-6">
  <div class="stat-card"><div class="text-xs text-slate-500 mb-1">งานทั้งหมด (เดือนนี้)</div><div class="text-3xl font-bold"><?= $stats['total_jobs'] ?></div></div>
  <div class="stat-card red"><div class="text-xs text-slate-500 mb-1">รออนุมัติ</div><div class="text-3xl font-bold"><?= $stats['pending'] ?></div></div>
  <div class="stat-card green"><div class="text-xs text-slate-500 mb-1">รายได้เดือนนี้</div><div class="text-3xl font-bold">฿<?= number_format($stats['revenue'], 0) ?></div></div>
  <div class="stat-card orange"><div class="text-xs text-slate-500 mb-1">ช่างพร้อมงาน</div><div class="text-3xl font-bold"><?= $stats['tech_free'] ?>/<?= $stats['tech_total'] ?></div></div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
  <div class="card p-6 lg:col-span-2">
    <h3 class="font-bold text-slate-800 mb-4">รายได้รายเดือน</h3>
    <canvas id="revenueChart" height="100"></canvas>
  </div>
  <div class="card p-6">
    <h3 class="font-bold text-slate-800 mb-4">งานตามประเภท</h3>
    <canvas id="serviceChart" height="200"></canvas>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mt-5">
  <div class="card p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-bold text-slate-800">งานล่าสุด</h3>
      <a href="/web_app/owner/jobs.php" class="text-sm text-blue-900 hover:underline">ดูทั้งหมด →</a>
    </div>
    <div class="space-y-3">
      <?php foreach ($recentJobs as $j): ?>
      <a href="/web_app/owner/job_detail.php?id=<?= $j['booking_id'] ?>" class="flex items-center justify-between p-3 hover:bg-slate-50 rounded-lg no-underline">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-900 flex items-center justify-center font-bold text-xs">#<?= $j['booking_id'] ?></div>
          <div>
            <div class="font-semibold text-sm"><?= htmlspecialchars($j['customer']) ?></div>
            <div class="text-xs text-slate-500"><?= htmlspecialchars($j['service_name']) ?> • <?= $j['booking_date'] ?></div>
          </div>
        </div>
        <span class="status-pill status-progress"><?= $j['status_service'] ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-bold text-slate-800">สลิปที่รอตรวจสอบ</h3>
      <span class="badge badge-red"><?= count($pendingSlips) ?> รอตรวจสอบ</span>
    </div>
    <div class="space-y-3">
      <?php foreach ($pendingSlips as $s): ?>
      <div class="flex items-center justify-between p-3 hover:bg-slate-50 rounded-lg">
        <div>
          <div class="font-semibold text-sm"><?= htmlspecialchars($s['customer']) ?></div>
          <div class="text-xs text-slate-500">#<?= $s['booking_id'] ?> • ฿<?= number_format($s['amount'], 2) ?></div>
        </div>
        <a href="/web_app/owner/payment.php" class="text-xs btn-primary px-3 py-1.5 rounded-lg no-underline">อนุมัติ</a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<script>
new Chart(document.getElementById('revenueChart'), {
  type: 'line',
  data: {
    labels: ['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'],
    datasets: [{
      label: 'รายได้ (฿)',
      data: <?= json_encode(array_values($revenueByMonth)) ?>,
      borderColor: '#1e3a5f', backgroundColor: 'rgba(30,58,95,0.1)',
      fill: true, tension: 0.4, borderWidth: 2
    }]
  },
  options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { callback: v => '฿' + v/1000 + 'k' } } } }
});
new Chart(document.getElementById('serviceChart'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode(array_column($serviceDist, 'category')) ?>,
    datasets: [{ data: <?= json_encode(array_column($serviceDist, 'cnt')) ?>, backgroundColor: ['#1e3a5f','#e63946','#f59e0b','#10b981'] }]
  },
  options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>