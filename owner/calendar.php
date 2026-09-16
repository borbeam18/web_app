<?php
$pageTitle = 'จัดการปฏิทินนัดหมาย';
$activePage = 'calendar';
require_once __DIR__ . '/../includes/header_owner.php';

$month = (int)($_GET['m'] ?? date('n'));
$year  = (int)($_GET['y'] ?? date('Y'));

$thaiMonths = ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];

// Fetch bookings for this month
$stmt = $pdo->prepare("
  SELECT b.booking_id, b.booking_date, b.booking_time, b.status_service, c.full_name AS customer
  FROM Booking b JOIN Customer c ON b.customer_id = c.customer_id
  WHERE YEAR(b.booking_date) = ? AND MONTH(b.booking_date) = ?
  ORDER BY b.booking_date, b.booking_time
");
$stmt->execute([$year, $month]);
$bookings = $stmt->fetchAll();

$eventsByDay = [];
foreach ($bookings as $b) {
  $day = (int)date('j', strtotime($b['booking_date']));
  $eventsByDay[$day][] = $b;
}

$statusClass = [
  'รอรับงาน' => 'status-pending',
  'รับงานแล้ว' => 'status-progress',
  'กำลังเดินทาง' => 'status-progress',
  'กำลังซ่อม' => 'status-progress',
  'เสร็จสิ้น' => 'status-done',
  'ยกเลิก' => 'status-cancel'
];

// Handle new booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'new_booking') {
    $stmt = $pdo->prepare("INSERT INTO Booking (customer_id, service_id, tech_id_1, booking_date, booking_time, problem_description) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        (int)$_POST['customer_id'],
        (int)$_POST['service_id'],
        (int)$_POST['tech_id'],
        $_POST['booking_date'],
        $_POST['booking_time'],
        $_POST['problem_description']
    ]);
    header('Location: /web_app/owner/calendar.php?m=' . $month . '&y=' . $year);
    exit;
}

$customers = $pdo->query("SELECT * FROM Customer ORDER BY full_name")->fetchAll();
$services  = $pdo->query("SELECT * FROM Services WHERE status = 'เปิดใช้งาน'")->fetchAll();
$techs     = $pdo->query("SELECT * FROM Technician WHERE status = 'ว่าง'")->fetchAll();
?>

<div class="card p-6">
  <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
    <div class="flex items-center gap-3">
      <a href="?m=<?= $month-1 ?>&y=<?= $month===1?$year-1:$year ?>" class="p-2 rounded-lg hover:bg-slate-100">◀</a>
      <h2 class="text-xl font-bold"><?= $thaiMonths[$month] ?> <?= $year + 543 ?></h2>
      <a href="?m=<?= $month+1 ?>&y=<?= $month===12?$year+1:$year ?>" class="p-2 rounded-lg hover:bg-slate-100">▶</a>
      <a href="?m=<?= date('n') ?>&y=<?= date('Y') ?>" class="ml-2 px-3 py-1.5 text-sm btn-outline rounded-lg no-underline">วันนี้</a>
    </div>
    <button onclick="openModal('newBookingModal')" class="btn-accent px-4 py-2 rounded-lg text-sm font-semibold">+ สร้างงานบริการใหม่</button>
  </div>

  <div class="grid grid-cols-7 gap-1 mb-2">
    <?php foreach (['อาทิตย์','จันทร์','อังคาร','พุธ','พฤหัสบดี','ศุกร์','เสาร์'] as $d): ?>
      <div class="text-center text-xs font-semibold text-slate-500 py-2"><?= $d ?></div>
    <?php endforeach; ?>
  </div>
  <div class="grid grid-cols-7 gap-1">
    <?php
    $firstDay = (int)date('w', mktime(0,0,0,$month,1,$year));
    $daysInMonth = (int)date('t', mktime(0,0,0,$month,1,$year));
    $daysInPrevMonth = (int)date('t', mktime(0,0,0,$month-1,1,$year));
    $today = (int)date('j');
    $isCurrentMonth = $month === (int)date('n') && $year === (int)date('Y');
    
    for ($i = $firstDay - 1; $i >= 0; $i--):
      echo '<div class="calendar-cell other-month p-2 text-xs">' . ($daysInPrevMonth - $i) . '</div>';
    endfor;
    
    for ($d = 1; $d <= $daysInMonth; $d++):
      $isToday = $isCurrentMonth && $d === $today;
      $dayEvents = $eventsByDay[$d] ?? [];
    ?>
      <div class="calendar-cell <?= $isToday ? 'today' : '' ?> p-2">
        <div class="text-xs font-semibold <?= $isToday ? 'text-blue-900' : 'text-slate-700' ?>"><?= $d ?></div>
        <?php foreach ($dayEvents as $e): ?>
          <a href="job_detail.php?id=<?= (int)$e['booking_id'] ?>" class="calendar-event <?= $statusClass[$e['status_service']] ?? 'status-pending' ?> block no-underline">
            <span class="block font-semibold">#<?= (int)$e['booking_id'] ?> <?= htmlspecialchars(mb_substr($e['customer'], 0, 12)) ?></span>
            <span class="block text-[10px] opacity-90"><?= htmlspecialchars($e['status_service']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endfor;
    
    $totalCells = $firstDay + $daysInMonth;
    $remaining = (7 - ($totalCells % 7)) % 7;
    for ($i = 1; $i <= $remaining; $i++):
      echo '<div class="calendar-cell other-month p-2 text-xs">' . $i . '</div>';
    endfor;
    ?>
  </div>
</div>

<!-- Modal สร้างงานใหม่ -->
<div id="newBookingModal" class="fixed inset-0 modal-backdrop hidden z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto fade-in">
    <div class="p-6 border-b border-slate-200 flex items-center justify-between sticky top-0 bg-white">
      <h3 class="text-lg font-bold">สร้างงานบริการใหม่</h3>
      <button onclick="closeModal('newBookingModal')" class="text-2xl">×</button>
    </div>
    <form method="POST" class="p-6 space-y-4">
      <input type="hidden" name="action" value="new_booking">
      <div class="grid grid-cols-2 gap-4">
        <div><label class="block text-sm font-medium mb-1">ลูกค้า</label>
          <select name="customer_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg">
            <?php foreach ($customers as $c): ?><option value="<?= $c['customer_id'] ?>"><?= htmlspecialchars($c['full_name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div><label class="block text-sm font-medium mb-1">ประเภทงาน</label>
          <select name="service_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg">
            <?php foreach ($services as $s): ?><option value="<?= $s['service_id'] ?>"><?= htmlspecialchars($s['service_name']) ?></option><?php endforeach; ?>
          </select>
        </div>
      </div>
      <div><label class="block text-sm font-medium mb-1">รายละเอียดอาการ</label><textarea name="problem_description" rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-lg"></textarea></div>
      <div class="grid grid-cols-2 gap-4">
        <div><label class="block text-sm font-medium mb-1">วันที่</label><input type="date" name="booking_date" required class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
        <div><label class="block text-sm font-medium mb-1">เวลา</label><input type="time" name="booking_time" required class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
      </div>
      <div><label class="block text-sm font-medium mb-1">มอบหมายช่าง</label>
        <select name="tech_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg">
          <option value="">- ยังไม่มอบหมาย -</option>
          <?php foreach ($techs as $t): ?><option value="<?= $t['tech_id'] ?>"><?= htmlspecialchars($t['full_name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="flex justify-end gap-3 pt-3 border-t">
        <button type="button" onclick="closeModal('newBookingModal')" class="px-4 py-2 border border-slate-300 rounded-lg">ยกเลิก</button>
        <button type="submit" class="btn-accent px-6 py-2 rounded-lg font-semibold">บันทึกงาน</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>