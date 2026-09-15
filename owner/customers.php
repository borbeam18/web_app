<?php
$pageTitle = 'ข้อมูลลูกค้า';
$activePage = 'customers';
require_once __DIR__ . '/../includes/header_owner.php';

$search = trim($_GET['search'] ?? '');
$sql = "SELECT * FROM Customer WHERE 1=1";
$params = [];
if ($search) { $sql .= " AND (full_name LIKE ? OR phone LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();
?>

<div class="card p-6">
  <form method="GET" class="flex items-center justify-between mb-5 flex-wrap gap-3">
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="ค้นหาชื่อ, เบอร์โทร..." class="px-4 py-2 border border-slate-300 rounded-lg w-72 text-sm outline-none" />
    <button class="btn-accent px-4 py-2 rounded-lg text-sm font-semibold">ค้นหา</button>
  </form>
  <div class="overflow-x-auto">
    <table class="w-full data-table">
      <thead><tr><th class="text-left p-3">ID</th><th class="text-left p-3">ชื่อ-นามสกุล</th><th class="text-left p-3">เบอร์โทร</th><th class="text-left p-3">ที่อยู่</th><th class="text-left p-3">วันที่สมัคร</th><th class="text-left p-3">จัดการ</th></tr></thead>
      <tbody>
      <?php foreach ($customers as $c): ?>
        <tr class="hover:bg-slate-50">
          <td class="p-3 font-semibold">#<?= $c['customer_id'] ?></td>
          <td class="p-3 font-semibold"><?= htmlspecialchars($c['full_name']) ?></td>
          <td class="p-3"><?= htmlspecialchars($c['phone']) ?></td>
          <td class="p-3 text-sm"><?= htmlspecialchars($c['address']) ?></td>
          <td class="p-3 text-sm text-slate-500"><?= $c['created_at'] ?></td>
          <td class="p-3"><a href="/web_app/owner/jobs.php?search=<?= urlencode($c['full_name']) ?>" class="text-blue-900 hover:underline text-sm">ดูประวัติ</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>