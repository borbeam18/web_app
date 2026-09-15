<?php
ob_start();
$pageTitle = 'รายรับ-รายจ่าย';
$activePage = 'expenses';
require_once __DIR__ . '/../includes/auth.php';
requireRole('owner');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $expenseId = (int)($_POST['expense_id'] ?? 0);
    $expenseType = trim($_POST['expense_type'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    $expenseDate = $_POST['expense_date'] ?? '';
    $note = trim($_POST['note'] ?? '');

    if ($action === 'add') {
        $pdo->prepare("INSERT INTO Expense (owner_id, expense_type, amount, expense_date, note) VALUES (?, ?, ?, ?, ?)")
            ->execute([$_SESSION['user_id'], $expenseType, $amount, $expenseDate, $note]);
    } elseif ($action === 'update' && $expenseId > 0) {
        $pdo->prepare("UPDATE Expense SET expense_type = ?, amount = ?, expense_date = ?, note = ? WHERE expense_id = ? AND owner_id = ?")
            ->execute([$expenseType, $amount, $expenseDate, $note, $expenseId, $_SESSION['user_id']]);
    } elseif ($action === 'delete' && $expenseId > 0) {
        $pdo->prepare("DELETE FROM Expense WHERE expense_id = ? AND owner_id = ?")
            ->execute([$expenseId, $_SESSION['user_id']]);
    }

    header('Location: /web_app/owner/expenses.php');
    exit;
}

$stats = [];
$stats['income'] = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM Payment WHERE verify_status='อนุมัติแล้ว' AND MONTH(payment_date)=MONTH(CURRENT_DATE)")->fetchColumn();
$expenseStats = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM Expense WHERE owner_id = ? AND MONTH(expense_date) = MONTH(CURRENT_DATE)");
$expenseStats->execute([$_SESSION['user_id']]);
$stats['expense'] = $expenseStats->fetchColumn();
$stats['profit'] = $stats['income'] - $stats['expense'];

$stmt = $pdo->prepare("SELECT * FROM Expense WHERE owner_id = ? ORDER BY expense_date DESC LIMIT 50");
$stmt->execute([$_SESSION['user_id']]);
$expenses = $stmt->fetchAll();
require_once __DIR__ . '/../includes/header_owner.php';
?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-5">
  <div class="stat-card green"><div class="text-xs text-slate-500 mb-1">รายรับ (เดือนนี้)</div><div class="text-2xl font-bold">฿<?= number_format($stats['income'],0) ?></div></div>
  <div class="stat-card red"><div class="text-xs text-slate-500 mb-1">รายจ่าย (เดือนนี้)</div><div class="text-2xl font-bold">฿<?= number_format($stats['expense'],0) ?></div></div>
  <div class="stat-card"><div class="text-xs text-slate-500 mb-1">กำไรสุทธิ</div><div class="text-2xl font-bold text-green-600">฿<?= number_format($stats['profit'],0) ?></div></div>
</div>

<div class="card p-6">
  <div class="flex items-center justify-between mb-5">
    <h3 class="font-bold text-slate-800">บันทึกรายรับ-รายจ่าย</h3>
    <button onclick="openModal('addExpenseModal')" class="btn-accent px-4 py-2 rounded-lg text-sm font-semibold">+ บันทึกรายการ</button>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full data-table">
      <thead><tr><th class="text-left p-3">วันที่</th><th class="text-left p-3">ประเภท</th><th class="text-left p-3">รายการ</th><th class="text-left p-3">จำนวนเงิน</th><th class="text-left p-3">หมายเหตุ</th><th class="text-left p-3">จัดการ</th></tr></thead>
      <tbody>
      <?php foreach ($expenses as $e): ?>
        <tr class="hover:bg-slate-50">
          <td class="p-3"><?= $e['expense_date'] ?></td>
          <td class="p-3"><span class="badge badge-red"><?= $e['expense_type'] ?></span></td>
          <td class="p-3 font-semibold"><?= htmlspecialchars($e['expense_type']) ?></td>
          <td class="p-3 font-bold text-red-600">-฿<?= number_format($e['amount'],2) ?></td>
          <td class="p-3 text-sm text-slate-500"><?= htmlspecialchars($e['note'] ?? '') ?></td>
          <td class="p-3 whitespace-nowrap">
            <button type="button" class="text-blue-700 hover:underline mr-3" onclick='openEditExpense(<?= json_encode($e, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>แก้ไข</button>
            <form method="POST" class="inline" onsubmit="return confirm('ยืนยันการลบรายการนี้หรือไม่?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="expense_id" value="<?= (int)$e['expense_id'] ?>">
              <button type="submit" class="text-red-600 hover:underline">ลบ</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div id="addExpenseModal" class="fixed inset-0 modal-backdrop hidden z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl w-full max-w-lg fade-in">
    <div class="p-6 border-b border-slate-200 flex items-center justify-between">
      <h3 class="text-lg font-bold">บันทึกรายจ่าย</h3>
      <button onclick="closeModal('addExpenseModal')" class="text-2xl">×</button>
    </div>
    <form method="POST" class="p-6 space-y-4">
      <input type="hidden" name="action" value="add">
      <div><label class="block text-sm font-medium mb-1">ประเภทรายจ่าย</label><input name="expense_type" required class="w-full px-3 py-2 border border-slate-300 rounded-lg" placeholder="เช่น ค่าน้ำมันรถ"></div>
      <div><label class="block text-sm font-medium mb-1">จำนวนเงิน (฿)</label><input name="amount" type="number" step="0.01" required class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
      <div><label class="block text-sm font-medium mb-1">วันที่</label><input name="expense_date" type="date" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
      <div><label class="block text-sm font-medium mb-1">หมายเหตุ</label><textarea name="note" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-lg"></textarea></div>
      <div class="flex justify-end gap-3 pt-3 border-t">
        <button type="button" onclick="closeModal('addExpenseModal')" class="px-4 py-2 border border-slate-300 rounded-lg">ยกเลิก</button>
        <button type="submit" class="btn-accent px-6 py-2 rounded-lg font-semibold">บันทึก</button>
      </div>
    </form>
  </div>
</div>

<div id="editExpenseModal" class="fixed inset-0 modal-backdrop hidden z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl w-full max-w-lg fade-in">
    <div class="p-6 border-b border-slate-200 flex items-center justify-between">
      <h3 class="text-lg font-bold">แก้ไขรายการรายจ่าย</h3>
      <button type="button" onclick="closeModal('editExpenseModal')" class="text-2xl">×</button>
    </div>
    <form method="POST" class="p-6 space-y-4">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="expense_id" id="editExpenseId">
      <div><label class="block text-sm font-medium mb-1">ประเภทรายจ่าย</label><input id="editExpenseType" name="expense_type" required class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
      <div><label class="block text-sm font-medium mb-1">จำนวนเงิน (฿)</label><input id="editExpenseAmount" name="amount" type="number" step="0.01" min="0" required class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
      <div><label class="block text-sm font-medium mb-1">วันที่</label><input id="editExpenseDate" name="expense_date" type="date" required class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
      <div><label class="block text-sm font-medium mb-1">หมายเหตุ</label><textarea id="editExpenseNote" name="note" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-lg"></textarea></div>
      <div class="flex justify-end gap-3 pt-3 border-t">
        <button type="button" onclick="closeModal('editExpenseModal')" class="px-4 py-2 border border-slate-300 rounded-lg">ยกเลิก</button>
        <button type="submit" class="btn-accent px-6 py-2 rounded-lg font-semibold">บันทึกการแก้ไข</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditExpense(expense) {
  document.getElementById('editExpenseId').value = expense.expense_id;
  document.getElementById('editExpenseType').value = expense.expense_type || '';
  document.getElementById('editExpenseAmount').value = expense.amount;
  document.getElementById('editExpenseDate').value = expense.expense_date;
  document.getElementById('editExpenseNote').value = expense.note || '';
  openModal('editExpenseModal');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>