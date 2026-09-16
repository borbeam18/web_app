<?php
require_once __DIR__ . '/../config/db.php';

$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['full_name'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $lineId  = trim($_POST['line_id'] ?? ('U' . bin2hex(random_bytes(16))));
    
    if (!$name || !$phone) {
        $error = 'กรุณากรอกชื่อและเบอร์โทร';
    } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'กรุณากรอก Email ให้ถูกต้อง';
    } elseif (strlen($password) < 8) {
        $error = 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร';
    } elseif ($password !== $confirmPassword) {
        $error = 'รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO Customer (line_user_id, full_name, phone, email, password, address) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$lineId, $name, $phone, $email, password_hash($password, PASSWORD_DEFAULT), $address]);
            $success = 'สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ';
        } catch (PDOException $e) {
            $error = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }
    }
}
?>
<?php if ($success): ?>
<script>
  alert('สมัครสมาชิกสำเร็จ กรุณาเข้าสู่ระบบ');
  window.location.href = '/web_app/user/login.php';
</script>
<?php endif; ?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>สมัครสมาชิก - ร้านเอกเซอร์วิส</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/kanit@5.0.0/thai-400.css" />
<style>
  * { font-family: 'Kanit', sans-serif; }
  body { background: linear-gradient(135deg, #1e3a5f 0%, #0f172a 100%); margin: 0; }
  .login-card { background: white; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
  .btn-accent { background: #e63946; color: white; }
</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
  <div class="login-card w-full max-w-md p-8">
    <div class="text-center mb-6">
      <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-900 to-blue-700 text-white text-2xl font-bold mb-3">เอก</div>
      <h1 class="text-2xl font-bold">สมัครสมาชิก</h1>
      <p class="text-sm text-slate-500">ลงทะเบียนเพื่อใช้บริการร้านเอกเซอร์วิส</p>
    </div>
    <?php if ($success): ?><div class="bg-green-50 text-green-700 p-3 rounded mb-4"><?= $success ?></div><?php endif; ?>
    <?php if ($error): ?><div class="bg-red-50 text-red-700 p-3 rounded mb-4"><?= $error ?></div><?php endif; ?>
    <form method="POST" class="space-y-4">
      <div><label class="block text-sm font-medium mb-1">ชื่อ-นามสกุล</label><input name="full_name" required class="w-full px-4 py-3 border border-slate-300 rounded-lg outline-none" /></div>
      <div><label class="block text-sm font-medium mb-1">เบอร์โทรศัพท์</label><input name="phone" required class="w-full px-4 py-3 border border-slate-300 rounded-lg outline-none" /></div>
      <div><label class="block text-sm font-medium mb-1">Email</label><input name="email" type="email" required class="w-full px-4 py-3 border border-slate-300 rounded-lg outline-none" placeholder="example@email.com" /></div>
      <div><label class="block text-sm font-medium mb-1">รหัสผ่าน</label><input name="password" type="password" required minlength="8" class="w-full px-4 py-3 border border-slate-300 rounded-lg outline-none" /></div>
      <div><label class="block text-sm font-medium mb-1">ยืนยันรหัสผ่าน</label><input name="confirm_password" type="password" required minlength="8" class="w-full px-4 py-3 border border-slate-300 rounded-lg outline-none" /></div>
      <div><label class="block text-sm font-medium mb-1">ที่อยู่</label><textarea name="address" rows="2" class="w-full px-4 py-3 border border-slate-300 rounded-lg outline-none"></textarea></div>
      <div><label class="block text-sm font-medium mb-1">LINE ID</label><input name="line_id" class="w-full px-4 py-3 border border-slate-300 rounded-lg outline-none" placeholder="เว้นว่างเพื่อสร้างอัตโนมัติ" /></div>
      <button type="submit" class="w-full btn-accent py-3 rounded-lg font-semibold">สมัครสมาชิก</button>
      <div class="text-center pt-3 border-t border-slate-200">
        <p class="text-sm text-slate-600">มีบัญชีแล้ว? <a href="/web_app/user/login.php" class="text-blue-900 font-semibold">เข้าสู่ระบบ</a></p>
      </div>
    </form>
  </div>
</body>
</html>