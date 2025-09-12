<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/util.php';

$orders = $pdo->query('SELECT * FROM orders ORDER BY id DESC LIMIT 100')->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="flex items-center justify-between mb-4">
  <h1 class="text-2xl font-semibold">Pesanan</h1>
</div>
<div class="overflow-x-auto border rounded-xl">
  <table class="min-w-full">
    <thead class="bg-slate-50 text-left text-sm text-slate-600">
      <tr>
        <th class="px-4 py-3">ID</th>
        <th class="px-4 py-3">Nama</th>
        <th class="px-4 py-3">Email</th>
        <th class="px-4 py-3">Total</th>
        <th class="px-4 py-3">Waktu</th>
      </tr>
    </thead>
    <tbody class="divide-y">
      <?php foreach ($orders as $o): ?>
        <tr>
          <td class="px-4 py-3 font-medium">#<?= (int)$o['id'] ?></td>
          <td class="px-4 py-3"><?= htmlspecialchars($o['customer_name']) ?></td>
          <td class="px-4 py-3"><?= htmlspecialchars($o['customer_email']) ?></td>
          <td class="px-4 py-3 text-primary font-semibold"><?= format_price((int)$o['total_amount']) ?></td>
          <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($o['created_at']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
