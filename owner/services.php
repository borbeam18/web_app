<?php
ob_start();
$pageTitle = 'ประเภทงานบริการ';
$activePage = 'services';
require_once __DIR__ . '/../includes/auth.php';
requireRole('owner');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $pdo->prepare("INSERT INTO Services (service_name, category, base_price, description) VALUES (?, ?, ?, ?)")
            ->execute([$_POST['service_name'], $_POST['category'], (float)$_POST['base_price'], $_POST['description']]);
    } elseif ($action === 'update') {
        $pdo->prepare("UPDATE Services SET service_name = ?, category = ?, base_price = ?, description = ? WHERE service_id = ?")
            ->execute([
                trim($_POST['service_name']),
                $_POST['category'],
                (float)$_POST['base_price'],
                trim($_POST['description']),
                (int)$_POST['service_id'],
            ]);
    } elseif ($action === 'delete') {
        $pdo->prepare("DELETE FROM Services WHERE service_id = ?")->execute([(int)$_POST['service_id']]);
    }
    header('Location: /web_app/owner/services.php');
    exit;
}

$services = $pdo->query("SELECT * FROM Services ORDER BY category, service_name")->fetchAll();
$icons = ['แอร์'=>'❄️','ไฟฟ้า'=>'⚡','ประปา'=>'💧','ทั่วไป'=>'🔧'];
require_once __DIR__ . '/../includes/header_owner.php';
?>

<div class="card p-6">
  <div class="flex items-center justify-between mb-5">
    <h3 class="font-bold text-slate-800">ประเภทงานบริการ</h3>
    <button onclick="openModal('newServiceModal')" class="btn-accent px-4 py-2 rounded-lg text-sm font-semibold">+ เพิ่มประเภทงาน</button>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($services as $s): ?>
    <div class="card p-5 hover:shadow-lg transition">
      <div class="flex items-start justify-between mb-3">
        <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-2xl"><?= $icons[$s['category']] ?? '🔧' ?></div>
        <span class="badge badge-blue"><?= $s['category'] ?></span>
      </div>
      <h4 class="font-bold text-slate-800 mb-1"><?= htmlspecialchars($s['service_name']) ?></h4>
      <p class="text-xs text-slate-500 mb-3"><?= htmlspecialchars($s['description']) ?></p>
      <div class="flex items-center justify-between pt-3 border-t border-slate-200">
        <div class="text-lg font-bold text-blue-900">฿<?= number_format($s['base_price'],2) ?></div>
        <div class="flex items-center gap-3">
          <button type="button" class="text-blue-700 hover:underline text-sm" onclick='openEditService(<?= json_encode($s, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>แก้ไข</button>
          <form method="POST" onsubmit="return confirm('ยืนยันการลบ?')">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="service_id" value="<?= (int)$s['service_id'] ?>">
            <button class="text-red-600" type="submit">🗑</button>
          </form>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<div id="newServiceModal" class="fixed inset-0 modal-backdrop hidden z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl w-full max-w-lg fade-in">
    <div class="p-6 border-b border-slate-200 flex items-center justify-between">
      <h3 class="text-lg font-bold">เพิ่มประเภทงานบริการ</h3>
      <button onclick="closeModal('newServiceModal')" class="text-2xl">×</button>
    </div>
    <form method="POST" class="p-6 space-y-4">
      <input type="hidden" name="action" value="add">
      <div><label class="block text-sm font-medium mb-1">ชื่องานบริการ</label><input name="service_name" required class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
      <div><label class="block text-sm font-medium mb-1">หมวดหมู่</label><select name="category" class="w-full px-3 py-2 border border-slate-300 rounded-lg"><option>แอร์</option><option>ไฟฟ้า</option><option>ประปา</option><option>ทั่วไป</option></select></div>
      <div><label class="block text-sm font-medium mb-1">ราคาพื้นฐาน (฿)</label><input name="base_price" type="number" step="0.01" required class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
      <div><label class="block text-sm font-medium mb-1">รายละเอียด</label><textarea name="description" rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-lg"></textarea></div>
      <div class="flex justify-end gap-3 pt-3 border-t">
        <button type="button" onclick="closeModal('newServiceModal')" class="px-4 py-2 border border-slate-300 rounded-lg">ยกเลิก</button>
        <button type="submit" class="btn-accent px-6 py-2 rounded-lg font-semibold">บันทึก</button>
      </div>
    </form>
  </div>
</div>

<div id="editServiceModal" class="fixed inset-0 modal-backdrop hidden z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl w-full max-w-lg fade-in">
    <div class="p-6 border-b border-slate-200 flex items-center justify-between">
      <h3 class="text-lg font-bold">แก้ไขประเภทงานบริการ</h3>
      <button type="button" onclick="closeModal('editServiceModal')" class="text-2xl">×</button>
    </div>
    <form method="POST" class="p-6 space-y-4">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="service_id" id="editServiceId">
      <div><label class="block text-sm font-medium mb-1">ชื่องานบริการ</label><input id="editServiceName" name="service_name" required class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
      <div><label class="block text-sm font-medium mb-1">หมวดหมู่</label><select id="editServiceCategory" name="category" class="w-full px-3 py-2 border border-slate-300 rounded-lg"><option>แอร์</option><option>ไฟฟ้า</option><option>ประปา</option><option>ทั่วไป</option></select></div>
      <div><label class="block text-sm font-medium mb-1">ราคาพื้นฐาน (฿)</label><input id="editServicePrice" name="base_price" type="number" step="0.01" min="0" required class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
      <div><label class="block text-sm font-medium mb-1">รายละเอียด</label><textarea id="editServiceDescription" name="description" rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-lg"></textarea></div>
      <div class="flex justify-end gap-3 pt-3 border-t">
        <button type="button" onclick="closeModal('editServiceModal')" class="px-4 py-2 border border-slate-300 rounded-lg">ยกเลิก</button>
        <button type="submit" class="btn-accent px-6 py-2 rounded-lg font-semibold">บันทึกการแก้ไข</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditService(service) {
  document.getElementById('editServiceId').value = service.service_id;
  document.getElementById('editServiceName').value = service.service_name || '';
  document.getElementById('editServiceCategory').value = service.category || 'ทั่วไป';
  document.getElementById('editServicePrice').value = service.base_price;
  document.getElementById('editServiceDescription').value = service.description || '';
  openModal('editServiceModal');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>