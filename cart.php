<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/util.php';

if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $pid = (int)($_POST['product_id'] ?? 0);
        $_SESSION['cart'][$pid] = ($_SESSION['cart'][$pid] ?? 0) + 1;
        flash_set('ok', 'Produk ditambahkan ke keranjang');
        header('Location: ' . base_url('cart.php'));
        exit;
    } elseif ($action === 'remove') {
        $pid = (int)($_POST['product_id'] ?? 0);
        unset($_SESSION['cart'][$pid]);
        header('Location: ' . base_url('cart.php'));
        exit;
    } elseif ($action === 'clear') {
        $_SESSION['cart'] = [];
        header('Location: ' . base_url('cart.php'));
        exit;
    }
}

$ids = array_keys($_SESSION['cart']);
$items = [];
$total = 0;
if ($ids) {
    $in = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id, title, price, image FROM products WHERE id IN ($in)");
    $stmt->execute($ids);
    $rows = $stmt->fetchAll();
    foreach ($rows as $r) {
        $qty = $_SESSION['cart'][$r['id']] ?? 0;
        $subtotal = $qty * (int)$r['price'];
        $total += $subtotal;
        $items[] = ['id'=>$r['id'],'title'=>$r['title'],'price'=>$r['price'],'image'=>$r['image'],'qty'=>$qty,'subtotal'=>$subtotal];
    }
}
include __DIR__ . '/includes/header.php';
?>
<div class="flex items-start gap-8 flex-col lg:flex-row">
  <div class="flex-1 w-full">
    <h1 class="text-2xl font-semibold mb-4">Keranjang</h1>
    <?php if ($msg = flash_get('ok')): ?>
      <div class="mb-4 p-3 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <?php if (!$items): ?>
      <p class="text-slate-600">Keranjang kosong.</p>
    <?php else: ?>
      <div class="divide-y border border-slate-200 rounded-xl overflow-hidden">
        <?php foreach ($items as $it): ?>
          <div class="p-4 flex items-center gap-4">
            <?php if (!empty($it['image'])): ?>
              <img class="h-16 w-16 rounded-lg object-cover border" src="<?= htmlspecialchars(base_url('uploads/' . $it['image'])) ?>" alt="">
            <?php endif; ?>
            <div class="flex-1">
              <div class="font-medium"><?= htmlspecialchars($it['title']) ?></div>
              <div class="text-sm text-slate-600">Qty: <?= (int)$it['qty'] ?> • <?= format_price((int)$it['price']) ?></div>
            </div>
            <div class="font-medium"><?= format_price((int)$it['subtotal']) ?></div>
            <form method="post" class="ml-4">
              <input type="hidden" name="action" value="remove">
              <input type="hidden" name="product_id" value="<?= (int)$it['id'] ?>">
              <button class="text-red-600 hover:underline">Hapus</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  <aside class="w-full lg:w-80 lg:sticky lg:top-24">
    <div class="border border-slate-200 rounded-xl p-4 bg-white shadow-sm">
      <div class="flex items-center justify-between mb-2">
        <span class="text-slate-600">Total</span>
        <span class="text-xl font-semibold text-primary"><?= format_price($total) ?></span>
      </div>
      <a href="<?= htmlspecialchars(base_url('checkout.php')) ?>" class="block text-center w-full mt-3 px-4 py-3 rounded-lg bg-primary text-white font-medium hover:opacity-90">Checkout</a>
      <form method="post" class="mt-3 text-center">
        <input type="hidden" name="action" value="clear">
        <button class="text-slate-500 hover:underline" type="submit">Kosongkan Keranjang</button>
      </form>
    </div>
  </aside>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
