<?php
// admin_users.php
$pageTitle = 'จัดการผู้ใช้งาน';
require_once __DIR__ . '/../includes/header_admin.php';

$roleLabels = ['Admin', 'Owner', 'ช่าง', 'ลูกค้า'];
$roleTables = [
    'Admin' => ['table' => 'Admin', 'id' => 'admin_id'],
    'Owner' => ['table' => 'Owner', 'id' => 'owner_id'],
    'ช่าง' => ['table' => 'Technician', 'id' => 'tech_id'],
    'ลูกค้า' => ['table' => 'Customer', 'id' => 'customer_id'],
];

function moveUserToRole(PDO $pdo, string $sourceRole, string $targetRole, int $id, array $roleTables): void
{
    if (!isset($roleTables[$sourceRole], $roleTables[$targetRole])) {
        throw new RuntimeException('บทบาทผู้ใช้ไม่ถูกต้อง');
    }
    if ($sourceRole === $targetRole) {
        throw new RuntimeException('บทบาทใหม่ต้องแตกต่างจากบทบาทเดิม');
    }

    $source = $roleTables[$sourceRole];
    $target = $roleTables[$targetRole];
    $stmt = $pdo->prepare("SELECT * FROM {$source['table']} WHERE {$source['id']} = ? FOR UPDATE");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if (!$user) {
        throw new RuntimeException('ไม่พบผู้ใช้ที่ต้องการเปลี่ยนบทบาท');
    }

    if ($sourceRole === 'Owner') {
        $expenseCount = $pdo->prepare('SELECT COUNT(*) FROM Expense WHERE owner_id = ?');
        $expenseCount->execute([$id]);
        $paymentCount = $pdo->prepare('SELECT COUNT(*) FROM Payment WHERE verified_by = ?');
        $paymentCount->execute([$id]);
        if ((int)$expenseCount->fetchColumn() > 0 || (int)$paymentCount->fetchColumn() > 0) {
            throw new RuntimeException('Owner รายนี้มีข้อมูลรายจ่ายหรือการตรวจสอบการชำระเงินผูกอยู่ จึงยังเปลี่ยนบทบาทไม่ได้');
        }
    } elseif ($sourceRole === 'ช่าง') {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM Booking WHERE tech_id_1 = ? OR tech_id_2 = ?');
        $stmt->execute([$id, $id]);
        if ((int)$stmt->fetchColumn() > 0) {
            throw new RuntimeException('ช่างรายนี้มีงานที่ผูกอยู่ จึงยังเปลี่ยนบทบาทไม่ได้');
        }
    } elseif ($sourceRole === 'ลูกค้า') {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM Booking WHERE customer_id = ?');
        $stmt->execute([$id]);
        if ((int)$stmt->fetchColumn() > 0) {
            throw new RuntimeException('ลูกค้ารายนี้มีคำขอรับบริการผูกอยู่ จึงยังเปลี่ยนบทบาทไม่ได้');
        }
    }

    $username = $user['username'] ?? $user['line_user_id'] ?? null;
    $password = $user['password'] ?? null;
    if (in_array($targetRole, ['Admin', 'Owner'], true) && !$password) {
        throw new RuntimeException('บัญชีช่าง/ลูกค้ายังไม่มี password จึงเปลี่ยนเป็น Admin หรือ Owner ไม่ได้');
    }
    $fullName = $user['full_name'];
    $contact = $user['phone'] ?? null;

    if ($targetRole === 'Admin') {
        $stmt = $pdo->prepare('INSERT INTO Admin (username, password, full_name, email, status) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$username, $password, $fullName, $user['email'] ?? null, $user['status'] ?? 'Active']);
    } elseif ($targetRole === 'Owner') {
        $stmt = $pdo->prepare('INSERT INTO Owner (username, password, full_name, phone, shop_name) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$username, $password, $fullName, $contact, $user['shop_name'] ?? 'ร้านเอกเซอร์วิส']);
    } elseif ($targetRole === 'ช่าง') {
        $stmt = $pdo->prepare('INSERT INTO Technician (line_user_id, full_name, phone, specialty, status) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$username, $fullName, $contact, $user['specialty'] ?? null, $user['status'] ?? 'ว่าง']);
    } else {
        $stmt = $pdo->prepare('INSERT INTO Customer (line_user_id, full_name, phone, email, address) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$username, $fullName, $contact, $user['email'] ?? null, $user['address'] ?? null]);
    }

    $pdo->prepare("DELETE FROM {$source['table']} WHERE {$source['id']} = ?")->execute([$id]);
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'edit') {
            $sourceRole = $_POST['role'];
            $targetRole = $_POST['target_role'] ?? $sourceRole;
            $id = (int)$_POST['id'];
            if ($sourceRole === 'Admin' && $id === (int)($_SESSION['user_id'] ?? 0) && $targetRole !== 'Admin') {
                throw new RuntimeException('ไม่สามารถเปลี่ยนบทบาทของบัญชี Admin ที่กำลังใช้งานอยู่ได้');
            }

            $pdo->beginTransaction();
            if ($sourceRole !== $targetRole) {
                moveUserToRole($pdo, $sourceRole, $targetRole, $id, $roleTables);
                $pdo->commit();
                $msg = 'เปลี่ยนบทบาทผู้ใช้งานสำเร็จ';
            } elseif ($sourceRole === 'Admin') {
                $stmt = $pdo->prepare('UPDATE Admin SET full_name = ?, email = ?, status = ? WHERE admin_id = ?');
                $stmt->execute([trim($_POST['full_name']), trim($_POST['contact']) ?: null, $_POST['status'], $id]);
                $pdo->commit();
                $msg = 'แก้ไขข้อมูลผู้ใช้งานสำเร็จ';
            } elseif ($sourceRole === 'Owner') {
                $stmt = $pdo->prepare('UPDATE Owner SET full_name = ?, phone = ?, shop_name = ? WHERE owner_id = ?');
                $stmt->execute([trim($_POST['full_name']), trim($_POST['contact']) ?: null, trim($_POST['shop_name']), $id]);
                $pdo->commit();
                $msg = 'แก้ไขข้อมูลผู้ใช้งานสำเร็จ';
            } elseif ($sourceRole === 'ช่าง') {
                $stmt = $pdo->prepare('UPDATE Technician SET full_name = ?, phone = ?, specialty = ?, status = ? WHERE tech_id = ?');
                $stmt->execute([trim($_POST['full_name']), trim($_POST['contact']) ?: null, trim($_POST['specialty']), $_POST['status'], $id]);
                $pdo->commit();
                $msg = 'แก้ไขข้อมูลผู้ใช้งานสำเร็จ';
            } elseif ($sourceRole === 'ลูกค้า') {
                $stmt = $pdo->prepare('UPDATE Customer SET full_name = ?, phone = ?, email = ?, address = ? WHERE customer_id = ?');
                $stmt->execute([trim($_POST['full_name']), trim($_POST['contact']) ?: null, trim($_POST['email']) ?: null, trim($_POST['address']), $id]);
                $pdo->commit();
                $msg = 'แก้ไขข้อมูลผู้ใช้งานสำเร็จ';
            }
        } elseif ($action === 'add') {
            $role = $_POST['role'];
            $username = trim($_POST['username']);
            $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $fullName = trim($_POST['full_name']);
            $contact  = trim($_POST['contact']);
            
            if ($role === 'admin') {
                $stmt = $pdo->prepare("INSERT INTO Admin (username, password, full_name, email) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $password, $fullName, $contact]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO Owner (username, password, full_name, phone) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $password, $fullName, $contact]);
            }
            $msg = 'เพิ่มผู้ใช้งานสำเร็จ';
        } elseif ($action === 'delete') {
            $role = $_POST['role'];
            $id   = (int)$_POST['id'];
            if ($id <= 0) {
                throw new RuntimeException('รหัสผู้ใช้งานไม่ถูกต้อง');
            }
            if ($role === 'admin') {
                $pdo->prepare("DELETE FROM Admin WHERE admin_id = ?")->execute([$id]);
            } elseif ($role === 'owner') {
                $pdo->prepare("DELETE FROM Owner WHERE owner_id = ?")->execute([$id]);
            } elseif ($role === 'tech') {
                $pdo->prepare("DELETE FROM Technician WHERE tech_id = ?")->execute([$id]);
            } elseif ($role === 'customer') {
                $pdo->prepare("DELETE FROM Customer WHERE customer_id = ?")->execute([$id]);
            }
            $msg = 'ลบผู้ใช้งานสำเร็จ';
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $err = $e->getMessage();
    }
}

// Fetch all users
$admins = $pdo->query("SELECT admin_id AS id, username, full_name, 'Admin' AS role, email AS contact, status, '' AS shop_name, '' AS specialty, '' AS address FROM Admin")->fetchAll();
$owners = $pdo->query("SELECT owner_id AS id, username, full_name, 'Owner' AS role, phone AS contact, 'Active' AS status, shop_name, '' AS specialty, '' AS address FROM Owner")->fetchAll();
$techs  = $pdo->query("SELECT tech_id AS id, line_user_id AS username, full_name, 'ช่าง' AS role, phone AS contact, status, '' AS shop_name, specialty, '' AS address FROM Technician")->fetchAll();
$customers = $pdo->query("SELECT customer_id AS id, line_user_id AS username, full_name, 'ลูกค้า' AS role, phone AS contact, email, 'Active' AS status, '' AS shop_name, '' AS specialty, address FROM Customer")->fetchAll();
$allUsers = array_merge($admins, $owners, $techs, $customers);
?>

<div class="card p-6">
  <div class="flex items-center justify-between mb-5">
    <h3 class="font-bold text-slate-800">รายชื่อผู้ใช้งานในระบบ</h3>
    <button onclick="openModal('addUserModal')" class="btn-accent px-4 py-2 rounded-lg text-sm font-semibold">+ เพิ่มผู้ใช้งาน</button>
  </div>
  <?php if (!empty($msg)): ?><div class="bg-green-50 text-green-700 p-3 rounded mb-4"><?= $msg ?></div><?php endif; ?>
  <?php if (!empty($err)): ?><div class="bg-red-50 text-red-700 p-3 rounded mb-4"><?= $err ?></div><?php endif; ?>
  
  <div class="overflow-x-auto">
    <table class="w-full data-table">
      <thead>
        <tr><th class="text-left p-3">ID</th><th class="text-left p-3">ชื่อผู้ใช้</th><th class="text-left p-3">ชื่อ-นามสกุล</th><th class="text-left p-3">บทบาท</th><th class="text-left p-3">อีเมล/โทร</th><th class="text-left p-3">สถานะ</th><th class="text-left p-3">จัดการ</th></tr>
      </thead>
      <tbody>
      <?php foreach ($allUsers as $u): ?>
        <tr class="hover:bg-slate-50">
          <td class="p-3 font-semibold">#<?= $u['id'] ?></td>
          <td class="p-3 font-mono text-sm"><?= htmlspecialchars($u['username']) ?></td>
          <td class="p-3 font-semibold"><?= htmlspecialchars($u['full_name']) ?></td>
          <td class="p-3"><span class="badge badge-<?= $u['role']==='Admin'?'red':($u['role']==='Owner'?'blue':'green') ?>"><?= $u['role'] ?></span></td>
          <td class="p-3 text-sm"><?= htmlspecialchars($u['contact'] ?? '-') ?></td>
          <td class="p-3"><span class="badge badge-green"><?= $u['status'] ?></span></td>
          <td class="p-3">
            <button type="button" class="text-blue-700 hover:underline text-sm mr-3" onclick='openEditUser(<?= json_encode($u, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>แก้ไข</button>
            <?php if ($u['role'] !== 'Admin'): ?>
            <form method="POST" onsubmit="return confirm('ยืนยันการลบ?')" style="display:inline">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="role" value="<?= $u['role']==='Owner' ? 'owner' : ($u['role']==='ช่าง' ? 'tech' : 'customer') ?>">
              <input type="hidden" name="id" value="<?= $u['id'] ?>">
              <button class="text-red-600 hover:underline text-sm">ลบ</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal แก้ไขผู้ใช้ -->
<div id="editUserModal" class="fixed inset-0 modal-backdrop hidden z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl w-full max-w-lg fade-in">
    <div class="p-6 border-b border-slate-200 flex items-center justify-between">
      <h3 class="text-lg font-bold">แก้ไขข้อมูลผู้ใช้งาน</h3>
      <button type="button" onclick="closeModal('editUserModal')" class="text-2xl">×</button>
    </div>
    <form method="POST" class="p-6 space-y-4">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="editUserId">
      <input type="hidden" name="role" id="editUserRole">
      <div>
        <label class="block text-sm font-medium mb-1">บทบาทใหม่</label>
        <select name="target_role" id="editUserTargetRole" class="w-full px-3 py-2 border border-slate-300 rounded-lg" onchange="updateEditFields()">
          <option value="Admin">Admin</option>
          <option value="Owner">Owner</option>
          <option value="ช่าง">พนักงาน/ช่าง</option>
          <option value="ลูกค้า">ลูกค้า/User</option>
        </select>
        <p class="mt-1 text-xs text-slate-500">ถ้ามีข้อมูลงานที่ผูกอยู่ ระบบจะไม่อนุญาตให้ย้ายบทบาท</p>
      </div>
      <div><label class="block text-sm font-medium mb-1">บทบาทเดิม</label><input id="editUserRoleLabel" readonly class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-slate-100"></div>
      <div><label class="block text-sm font-medium mb-1">ชื่อผู้ใช้</label><input id="editUserUsername" readonly class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-slate-100"></div>
      <div><label class="block text-sm font-medium mb-1">ชื่อ-นามสกุล</label><input name="full_name" id="editUserFullName" required class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
      <div><label class="block text-sm font-medium mb-1" id="editContactLabel">อีเมล / เบอร์โทร</label><input name="contact" id="editUserContact" class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
      <div id="editEmailGroup"><label class="block text-sm font-medium mb-1">Email</label><input name="email" id="editUserEmail" type="email" class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
      <div id="editShopGroup"><label class="block text-sm font-medium mb-1">ชื่อร้าน</label><input name="shop_name" id="editShopName" class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
      <div id="editSpecialtyGroup"><label class="block text-sm font-medium mb-1">ความถนัด</label><input name="specialty" id="editSpecialty" class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
      <div id="editAddressGroup"><label class="block text-sm font-medium mb-1">ที่อยู่</label><textarea name="address" id="editAddress" rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-lg"></textarea></div>
      <div id="editStatusGroup"><label class="block text-sm font-medium mb-1">สถานะ</label><select name="status" id="editUserStatus" class="w-full px-3 py-2 border border-slate-300 rounded-lg"><option value="Active">Active</option><option value="Inactive">Inactive</option><option value="ว่าง">ว่าง</option><option value="ไม่ว่าง">ไม่ว่าง</option></select></div>
      <div class="flex justify-end gap-3 pt-3 border-t">
        <button type="button" onclick="closeModal('editUserModal')" class="px-4 py-2 border border-slate-300 rounded-lg">ยกเลิก</button>
        <button type="submit" class="btn-accent px-6 py-2 rounded-lg font-semibold">บันทึกการแก้ไข</button>
      </div>
    </form>
  </div>
</div>

<script>
function updateEditFields() {
  const role = document.getElementById('editUserTargetRole').value;
  document.getElementById('editShopGroup').classList.toggle('hidden', role !== 'Owner');
  document.getElementById('editSpecialtyGroup').classList.toggle('hidden', role !== 'ช่าง');
  document.getElementById('editAddressGroup').classList.toggle('hidden', role !== 'ลูกค้า');
  document.getElementById('editStatusGroup').classList.toggle('hidden', role === 'Owner' || role === 'ลูกค้า');
  document.getElementById('editEmailGroup').classList.toggle('hidden', role !== 'ลูกค้า');
  const status = document.getElementById('editUserStatus');
  if (role === 'ช่าง' && ['Active', 'Inactive'].includes(status.value)) status.value = 'ว่าง';
  if (role === 'Admin' && ['ว่าง', 'ไม่ว่าง'].includes(status.value)) status.value = 'Active';
  document.getElementById('editContactLabel').textContent = role === 'Admin' ? 'อีเมล' : 'เบอร์โทรศัพท์';
}

function openEditUser(user) {
  document.getElementById('editUserId').value = user.id;
  document.getElementById('editUserRole').value = user.role;
  document.getElementById('editUserRoleLabel').value = user.role;
  document.getElementById('editUserTargetRole').value = user.role;
  document.getElementById('editUserUsername').value = user.username || '';
  document.getElementById('editUserFullName').value = user.full_name || '';
  document.getElementById('editUserContact').value = user.contact || '';
  document.getElementById('editUserEmail').value = user.email || '';
  document.getElementById('editShopName').value = user.shop_name || '';
  document.getElementById('editSpecialty').value = user.specialty || '';
  document.getElementById('editAddress').value = user.address || '';
  document.getElementById('editUserStatus').value = user.status || 'Active';
  updateEditFields();
  openModal('editUserModal');
}
</script>

<!-- Modal เพิ่มผู้ใช้ -->
<div id="addUserModal" class="fixed inset-0 modal-backdrop hidden z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl w-full max-w-lg fade-in">
    <div class="p-6 border-b border-slate-200 flex items-center justify-between">
      <h3 class="text-lg font-bold">เพิ่มผู้ใช้งานใหม่</h3>
      <button onclick="closeModal('addUserModal')" class="text-2xl">×</button>
    </div>
    <form method="POST" class="p-6 space-y-4">
      <input type="hidden" name="action" value="add">
      <div><label class="block text-sm font-medium mb-1">บทบาท</label>
        <select name="role" class="w-full px-3 py-2 border border-slate-300 rounded-lg"><option value="admin">Admin</option><option value="owner">Owner</option></select>
      </div>
      <div><label class="block text-sm font-medium mb-1">ชื่อผู้ใช้</label><input name="username" required class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
      <div><label class="block text-sm font-medium mb-1">รหัสผ่าน</label><input name="password" type="password" required class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
      <div><label class="block text-sm font-medium mb-1">ชื่อ-นามสกุล</label><input name="full_name" required class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
      <div><label class="block text-sm font-medium mb-1">อีเมล / เบอร์โทร</label><input name="contact" class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
      <div class="flex justify-end gap-3 pt-3 border-t">
        <button type="button" onclick="closeModal('addUserModal')" class="px-4 py-2 border border-slate-300 rounded-lg">ยกเลิก</button>
        <button type="submit" class="btn-accent px-6 py-2 rounded-lg font-semibold">บันทึก</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>