<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/util.php';

if (!isset($_SESSION['cart']) || !$_SESSION['cart']) {
    header('Location: ' . base_url('cart.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    if (!$name || !$email) {
        $err = 'Nama dan email wajib diisi';
    } else {
        $pdo->beginTransaction();
        try {
            $total = 0;
            $ids = array_keys($_SESSION['cart']);
            $in = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("SELECT id, price FROM products WHERE id IN ($in)");
            $stmt->execute($ids);
            $map = [];
            foreach ($stmt->fetchAll() as $r) { $map[$r['id']] = (int)$r['price']; }
            foreach ($_SESSION['cart'] as $pid=>$qty) { $total += $qty * ($map[$pid] ?? 0); }

            $pdo->prepare('INSERT INTO orders (customer_name, customer_email, total_amount) VALUES (?,?,?)')
                ->execute([$name, $email, $total]);
            $order_id = (int)$pdo->lastInsertId();

            $oi = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?,?,?,?)');
            foreach ($_SESSION['cart'] as $pid=>$qty) {
                $oi->execute([$order_id, $pid, $qty, $map[$pid] ?? 0]);
            }

            $pdo->commit();
            $_SESSION['cart'] = [];
            $success = true;
        } catch (Throwable $e) {
            $pdo->rollBack();
            $err = 'Terjadi kesalahan. Silakan coba lagi.';
        }
    }
}

include __DIR__ . '/includes/header.php';
?>
<div class="max-w-2xl mx-auto">
  <h1 class="text-2xl font-semibold mb-4">Checkout</h1>
  <?php if (!empty($success)): ?>
    <div class="p-4 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200">
      Pesanan berhasil! Kami akan mengirim detail pembelian ke email Anda.
    </div>
  <?php else: ?>
    <?php if (!empty($err)): ?>
      <div class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 border border-red-200"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>
    <form method="post" class="space-y-4">
      <div>
        <label class="block text-sm text-slate-600 mb-1">Nama</label>
        <input name="name" class="w-full px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-primary" required>
      </div>
      <div>
        <label class="block text-sm text-slate-600 mb-1">Email</label>
        <input name="email" type="email" class="w-full px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-primary" required>
      </div>
      <button class="px-5 py-3 rounded-lg bg-primary text-white font-medium hover:opacity-90">Buat Pesanan</button>
    </form>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
