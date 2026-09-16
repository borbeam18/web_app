<?php
require_once __DIR__ . '/auth.php';
requireRole('admin'); // ต้องเป็น admin เท่านั้น
$me = currentUser();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Admin - ร้านเอกเซอร์วิส</title>
<script src="https://cdn.tailwindcss.com"></script>
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
  .btn-accent { background: #e63946; color: white; }
  .btn-accent:hover { background: #c62828; }
  .btn-outline { border: 1px solid #1e3a5f; color: #1e3a5f; }
  .btn-outline:hover { background: #1e3a5f; color: white; }
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
    table.data-table { min-width: 680px; }
    .modal-panel, .modal-backdrop > div { width: 100%; max-width: none; max-height: calc(100vh - 1rem); border-radius: 1rem; }
    .grid-cols-2 { grid-template-columns: minmax(0, 1fr) !important; }
    .page-content .overflow-x-auto { max-width: 100%; }
  }
  .fade-in { animation: fadeIn 0.3s ease; }
  @keyframes fadeIn { from { opacity: 0; transform: translateY(8px);} to { opacity:1; transform: translateY(0);} }
</style>
</head>
<body>
<div id="mobileOverlay" class="mobile-overlay fixed inset-0 bg-slate-900/50 z-30" onclick="closeMobileMenu()"></div>
<aside id="mainSidebar" class="sidebar w-64 text-white flex flex-col fixed h-screen">
  <div class="p-5 border-b border-white/10">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center font-bold">เอก</div>
      <div><div class="font-bold">ร้านเอกเซอร์วิส</div><div class="text-xs text-white/60">Admin Panel</div></div>
    </div>
  </div>
  <nav class="flex-1 p-3 space-y-1">
    <a href="/web_app/admin/users.php" class="nav-item active flex items-center gap-3 px-4 py-3 rounded-lg">👥 จัดการผู้ใช้งาน</a>
  </nav>
  <div class="p-3 border-t border-white/10">
    <a href="/web_app/user/profile.php" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition text-white/80">👤 ข้อมูลส่วนตัว</a>
    <a href="/web_app/user/change_password.php" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition text-white/80">🔑 เปลี่ยนรหัสผ่าน</a>
    <a href="/web_app/api/logout.php" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition text-white/80">🚪 ออกจากระบบ</a>
  </div>
</aside>

<main class="main-content">
  <header class="bg-white border-b border-slate-200 px-8 py-4 flex items-center justify-between sticky top-0 z-20">
    <div class="flex items-center gap-3 min-w-0">
      <button type="button" class="mobile-menu-button items-center justify-center rounded-lg border border-slate-300 p-2 text-slate-700" onclick="openMobileMenu()" aria-label="เปิดเมนู">☰</button>
      <div class="min-w-0">
        <div class="text-xs text-slate-500 truncate">ระบบผู้ดูแลระบบ</div>
        <h1 class="text-xl font-bold text-slate-800 truncate"><?= $pageTitle ?? 'จัดการผู้ใช้งาน' ?></h1>
      </div>
    </div>
    <div class="flex items-center gap-3 pl-4 border-l border-slate-200">
      <div class="w-10 h-10 rounded-full bg-gradient-to-br from-red-600 to-red-800 text-white flex items-center justify-center font-bold"><?= mb_substr($me['name'], 0, 1) ?></div>
      <div>
        <div class="font-semibold text-sm text-slate-800"><?= htmlspecialchars($me['name']) ?></div>
        <div class="text-xs text-slate-500">ผู้ดูแลระบบ (Admin)</div>
      </div>
    </div>
  </header>
  <div class="page-content p-8">