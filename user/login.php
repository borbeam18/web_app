<?php
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
if (isset($_SESSION['role'])) {
    $redirect = match($_SESSION['role']) {
        'admin'      => '/web_app/admin/users.php',
        'owner'      => '/web_app/owner/dashboard.php',
        'technician' => '/web_app/technician/dashboard.php',
        'customer'   => '/web_app/customer/dashboard.php',
        default      => '/web_app/user/login.php',
    };
    header('Location: ' . $redirect);
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>เข้าสู่ระบบ - ร้านเอกเซอร์วิส</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/kanit@5.0.0/thai-400.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/kanit@5.0.0/thai-600.css" />
<style>
  * { font-family: 'Kanit', sans-serif; box-sizing: border-box; }
  body { background: linear-gradient(135deg, #1e3a5f 0%, #0f172a 100%); margin: 0; }
  .login-card { background: white; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
  .fade-in { animation: fadeIn 0.4s ease; }
  @keyframes fadeIn { from { opacity: 0; transform: translateY(10px);} to { opacity:1; transform: translateY(0);} }
  .btn-accent { background: #e63946; color: white; }
  .btn-accent:hover { background: #c62828; }
</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
  <div class="login-card w-full max-w-md p-8 fade-in">
    <div class="text-center mb-6">
      <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-900 to-blue-700 text-white text-2xl font-bold mb-3">เอก</div>
      <h1 class="text-2xl font-bold text-slate-800">ร้านเอกเซอร์วิส</h1>
      <p class="text-sm text-slate-500">ระบบบริหารจัดการการรับงานและการนัดหมาย</p>
    </div>

    <form id="loginForm" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">ชื่อผู้ใช้</label>
        <input id="loginUser" name="username" type="text" required
               class="w-full px-4 py-3 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-blue-900"
               placeholder="กรุณากรอก email" />
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">รหัสผ่าน</label>
        <input id="loginPass" name="password" type="password" required
               class="w-full px-4 py-3 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-blue-900"
               placeholder="กรุณากรอกรหัสผ่าน" />
      </div>
      <div id="errorMsg" class="hidden text-sm text-red-600 bg-red-50 p-3 rounded-lg"></div>
      <button type="submit" id="submitBtn"
              class="w-full btn-accent py-3 rounded-lg font-semibold hover:opacity-90 transition">
        เข้าสู่ระบบ
      </button>
      <button type="button" id="lineLoginBtn"
              class="w-full bg-green-500 text-white py-3 rounded-lg font-semibold hover:bg-green-600 transition flex items-center justify-center gap-2">
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.03 2 11c0 2.76 1.36 5.23 3.5 6.86V22l3.74-2.06c.88.24 1.81.37 2.76.37 5.52 0 10-4.03 10-9S17.52 2 12 2z"/></svg>
        เข้าสู่ระบบผ่าน LINE
      </button>
      <div class="text-center pt-3 border-t border-slate-200">
        <p class="text-sm text-slate-600">ยังไม่มีบัญชี? <a href="/web_app/user/register.php" class="text-blue-900 font-semibold hover:underline">สมัครสมาชิก</a></p>
      </div>
    </form>
  </div>

<script>
const errorMessage = document.getElementById('errorMsg');
const showError = message => {
  errorMessage.textContent = message;
  errorMessage.classList.remove('hidden');
};

async function submitLineLogin(accessToken) {
  const response = await fetch('../api/line_login.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ access_token: accessToken })
  });
  const data = await response.json();
  if (!data.success) {
    if ((data.code === 'choose_role' || data.code === 'not_linked') && data.redirect) {
      window.location.href = data.redirect;
      return;
    }
    throw new Error(data.message || 'เข้าสู่ระบบผ่าน LINE ไม่สำเร็จ');
  }
  window.location.href = data.redirect;
}

document.getElementById('loginForm').addEventListener('submit', async event => {
  event.preventDefault();
  const button = document.getElementById('submitBtn');
  button.disabled = true;
  button.textContent = 'กำลังเข้าสู่ระบบ...';
  errorMessage.classList.add('hidden');

  try {
    const response = await fetch('../api/login.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        username: document.getElementById('loginUser').value.trim(),
        password: document.getElementById('loginPass').value
      })
    });
    const data = await response.json();
    if (!data.success) throw new Error(data.message || 'เข้าสู่ระบบไม่สำเร็จ');
    window.location.href = data.redirect;
  } catch (error) {
    showError(error.message || 'เกิดข้อผิดพลาดในการเชื่อมต่อ');
  } finally {
    button.disabled = false;
    button.textContent = 'เข้าสู่ระบบ';
  }
});

document.getElementById('lineLoginBtn').addEventListener('click', async () => {
  const button = document.getElementById('lineLoginBtn');
  button.disabled = true;
  button.textContent = 'กำลังเชื่อมต่อ LINE...';
  errorMessage.classList.add('hidden');

  try {
    await liff.init({ liffId: '2011627827-2s8sq190' });
    if (!liff.isLoggedIn()) {
      liff.login();
      return;
    }
    const accessToken = liff.getAccessToken();
    if (!accessToken) throw new Error('ไม่พบ Access Token จาก LINE');
    await submitLineLogin(accessToken);
  } catch (error) {
    showError(error.message || 'เชื่อมต่อ LINE ไม่สำเร็จ');
    button.disabled = false;
    button.textContent = 'เข้าสู่ระบบผ่าน LINE';
  }
});
</script>
</body>
</html>
</html>