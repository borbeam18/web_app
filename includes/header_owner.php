<?php
require_once __DIR__ . '/auth.php';
requireRole('owner'); // ต้องเป็น owner เท่านั้น
$me = currentUser();

// โหลดสิทธิ์พนักงานจากฐานข้อมูลทุกครั้ง เพื่อรองรับ Owner ใหม่และ Session เก่า
$techStmt = $pdo->prepare('SELECT tech_id, full_name FROM Technician WHERE owner_id = ? LIMIT 1');
$techStmt->execute([(int)$_SESSION['user_id']]);
if ($technician = $techStmt->fetch()) {
    $_SESSION['available_roles'] = $_SESSION['available_roles'] ?? [];
    $_SESSION['available_roles']['technician'] = [
        'user_id' => (int)$technician['tech_id'],
        'name' => $technician['full_name'],
    ];
}

$me = currentUser();
$activePage = $activePage ?? '';
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Owner - ร้านเอกเซอร์วิส</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/kanit@5.0.0/thai-400.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/kanit@5.0.0/thai-600.css" />
<style>
  * { font-family: 'Kanit', sans-serif; box-sizing: border-box; }
  body { background: #f4f6fa; margin: 0; }
  .sidebar { background: linear-gradient(180deg, #1e3a5f 0%, #152a45 100%); }
  .nav-item { transition: all 0.2s; }
  .nav-item:hover { background: rgba(255,255,255,0.08); }
  .nav-item.active { background: #e63946; color: white; }
  .card { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
  .btn-primary { background: #1e3a5f; color: white; }
  .btn-primary:hover { background: #152a45; }
  .btn-accent { background: #e63946; color: white; }
  .btn-accent:hover { background: #c62828; }
  .btn-outline { border: 1px solid #1e3a5f; color: #1e3a5f; }
  .btn-outline:hover { background: #1e3a5f; color: white; }
  .stat-card { background: white; border-radius: 12px; padding: 20px; border-left: 4px solid #1e3a5f; }
  .stat-card.red { border-left-color: #e63946; }
  .stat-card.green { border-left-color: #10b981; }
  .stat-card.orange { border-left-color: #f59e0b; }
  .status-pill { padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 500; display: inline-block; }
  .status-pending { background: #fef3c7; color: #92400e; }
  .status-progress { background: #dbeafe; color: #1e40af; }
  .status-done { background: #d1fae5; color: #065f46; }
  .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; }
  .badge-blue { background: #dbeafe; color: #1e40af; }
  .badge-red { background: #fee2e2; color: #991b1b; }
  .badge-green { background: #d1fae5; color: #065f46; }
  table.data-table th { background: #f8fafc; color: #475569; font-weight: 600; font-size: 13px; }
  table.data-table td { font-size: 14px; color: #334155; }
  .main-content { margin-left: 256px; min-width: 0; }
  .mobile-menu-button { display: none !important; pointer-events: none; }
  .mobile-overlay { display: none; }
  .modal-backdrop { background: rgba(15,23,42,0.5); backdrop-filter: blur(4px); }
  .modal-panel { max-height: calc(100vh - 2rem); overflow-y: auto; }
  @media (max-width: 767px) {
    body { overflow-x: hidden; }
    .sidebar { width: 280px; max-width: 85vw; transform: translateX(-100%); transition: transform 0.25s ease; z-index: 40; }
    .sidebar.mobile-open { transform: translateX(0); }
    .main-content { margin-left: 0; width: 100%; }
    .mobile-menu-button { display: inline-flex !important; pointer-events: auto; }
    .mobile-overlay { display: none; }
    .mobile-overlay.visible { display: block; }
    .page-content { padding: 1rem !important; }
    .card { border-radius: 10px; }
    .stat-card { padding: 1rem; }
    table.data-table { min-width: 680px; }
    .calendar-cell { min-height: 72px; }
    .calendar-event { font-size: 10px; padding: 4px; overflow: hidden; }
    .modal-panel, .modal-backdrop > div { width: 100%; max-width: none; max-height: calc(100vh - 1rem); border-radius: 1rem; }
    .grid-cols-2 { grid-template-columns: minmax(0, 1fr) !important; }
    .page-content > .flex, .page-content .flex.items-center.justify-between { gap: .75rem; }
    .page-content .overflow-x-auto { max-width: 100%; }
  }
  .fade-in { animation: fadeIn 0.3s ease; }
  @keyframes fadeIn { from { opacity: 0; transform: translateY(8px);} to { opacity:1; transform: translateY(0);} }
  .scrollbar-thin::-webkit-scrollbar { width: 6px; }
  .scrollbar-thin::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
  .calendar-cell { min-height: 86px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; }
  .calendar-cell.today { background: #fef2f2; border: 2px solid #ef4444; box-shadow: 0 0 0 2px rgba(239,68,68,0.12); }
  .calendar-cell.today > div:first-child { color: #b91c1c; font-size: 15px; font-weight: 700; }
  .calendar-event { margin-top: 5px; padding: 5px 6px; border-radius: 6px; color: #1e293b; font-size: 11px; }
  .calendar-event.pending, .status-legend.pending { background: #fef3c7; color: #92400e; }
  .calendar-event.accepted, .status-legend.accepted { background: #dbeafe; color: #1e40af; }
  .calendar-event.traveling, .status-legend.traveling { background: #ede9fe; color: #6d28d9; }
  .calendar-event.repairing, .status-legend.repairing { background: #ffedd5; color: #c2410c; }
  .calendar-event.completed, .status-legend.completed { background: #d1fae5; color: #047857; }
  .status-legend { display: inline-flex; align-items: center; border-radius: 999px; padding: 4px 8px; font-weight: 600; }
</style>
</head>
<body>
<div id="mobileOverlay" class="mobile-overlay fixed inset-0 bg-slate-900/50 z-30" onclick="closeMobileMenu()"></div>
<aside id="mainSidebar" class="sidebar w-64 text-white flex flex-col fixed h-screen overflow-y-auto scrollbar-thin">
  <div class="p-5 border-b border-white/10">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center font-bold">เอก</div>
      <div><div class="font-bold">ร้านเอกเซอร์วิส</div><div class="text-xs text-white/60">Owner Panel</div></div>
    </div>
  </div>
  <nav class="flex-1 p-3 space-y-1">
    <a href="/web_app/owner/dashboard.php" class="nav-item <?= $activePage==='dashboard'?'active':'' ?> flex items-center gap-3 px-4 py-3 rounded-lg">📊 Dashboard</a>
    <a href="/web_app/owner/calendar.php" class="nav-item <?= $activePage==='calendar'?'active':'' ?> flex items-center gap-3 px-4 py-3 rounded-lg">📅 ปฏิทินนัดหมาย</a>
    <a href="/web_app/owner/jobs.php" class="nav-item <?= $activePage==='jobs'?'active':'' ?> flex items-center gap-3 px-4 py-3 rounded-lg">📋 คำขอรับบริการ</a>
    <a href="/web_app/owner/payment.php" class="nav-item <?= $activePage==='payment'?'active':'' ?> flex items-center gap-3 px-4 py-3 rounded-lg">💰 ตรวจสอบการชำระเงิน</a>
    <a href="/web_app/owner/services.php" class="nav-item <?= $activePage==='services'?'active':'' ?> flex items-center gap-3 px-4 py-3 rounded-lg">⚙️ ประเภทงานบริการ</a>
    <a href="/web_app/owner/customers.php" class="nav-item <?= $activePage==='customers'?'active':'' ?> flex items-center gap-3 px-4 py-3 rounded-lg"> ข้อมูลลูกค้า</a>
    <a href="/web_app/owner/expenses.php" class="nav-item <?= $activePage==='expenses'?'active':'' ?> flex items-center gap-3 px-4 py-3 rounded-lg">💵 รายรับ-รายจ่าย</a>
  </nav>
  <div class="p-3 border-t border-white/10">
    <a href="/web_app/user/profile.php" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition text-white/80">👤 ข้อมูลส่วนตัว</a>
    <a href="/web_app/user/change_password.php" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition text-white/80">🔑 เปลี่ยนรหัสผ่าน</a>
    <a href="/web_app/api/logout.php" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition text-white/80">🚪 ออกจากระบบ</a>
  </div>
</aside>

<main class="main-content">
  <header class="bg-white border-b border-slate-200 px-4 md:px-8 py-3 md:py-4 flex items-center justify-between sticky top-0 z-20">
    <div class="flex items-center gap-3 min-w-0">
      <button type="button" class="mobile-menu-button items-center justify-center rounded-lg border border-slate-300 p-2 text-slate-700" onclick="openMobileMenu()" aria-label="เปิดเมนู">☰</button>
      <div class="min-w-0">
        <div class="text-xs text-slate-500 truncate"><?= $breadcrumb ?? 'จัดการงานบริการ' ?></div>
        <h1 class="text-lg md:text-xl font-bold text-slate-800 truncate"><?= $pageTitle ?? 'Dashboard' ?></h1>
      </div>
    </div>
    <div class="flex items-center gap-3 pl-4 border-l border-slate-200">
      <?php if (isset($_SESSION['available_roles']['technician'])): ?>
      <a href="/web_app/api/switch_role.php?role=technician" class="hidden sm:inline-flex rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">สลับเป็นพนักงาน</a>
      <?php endif; ?>
      <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-900 to-blue-700 text-white flex items-center justify-center font-bold"><?= mb_substr($me['name'], 0, 1) ?></div>
      <div>
        <div class="font-semibold text-sm text-slate-800"><?= htmlspecialchars($me['name']) ?></div>
        <div class="text-xs text-slate-500">เจ้าของกิจการ</div>
      </div>
    </div>
  </header>
  <div class="page-content p-8">