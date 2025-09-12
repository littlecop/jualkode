<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/util.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: ' . base_url('admin/'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT id, password_hash, name FROM users WHERE email = ? AND role = 'admin' AND is_active = 1");
    $stmt->execute([$email]);
    $u = $stmt->fetch();
    if ($u && password_verify($password, $u['password_hash'])) {
        $_SESSION['admin_id'] = (int)$u['id'];
        $_SESSION['admin_name'] = $u['name'];
        header('Location: ' . base_url('admin/'));
        exit;
    } else {
        $err = 'Email atau password salah';
    }
}
include __DIR__ . '/../includes/header.php';
?>
<div class="max-w-sm mx-auto">
  <h1 class="text-2xl font-semibold mb-4">Login Admin</h1>
  <?php if (!empty($err)): ?>
    <div class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 border border-red-200"><?= htmlspecialchars($err) ?></div>
  <?php endif; ?>
  <form method="post" class="space-y-4">
    <div>
      <label class="block text-sm text-slate-600 mb-1">Email</label>
      <input name="email" type="email" class="w-full px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-primary" required>
    </div>
    <div>
      <label class="block text-sm text-slate-600 mb-1">Password</label>
      <input name="password" type="password" class="w-full px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-primary" required>
    </div>
    <button class="px-5 py-2.5 rounded-lg bg-primary text-white font-medium hover:opacity-90">Masuk</button>
  </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
admin@example.com
admin123