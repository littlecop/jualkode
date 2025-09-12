<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
require_once __DIR__ . '/../../includes/util.php';

$products = $pdo->query('SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id ORDER BY p.id DESC')->fetchAll();
include __DIR__ . '/../../includes/header.php';
?>
<div class="flex items-center justify-between mb-4">
  <h1 class="text-2xl font-semibold">Produk</h1>
  <a href="<?= htmlspecialchars(base_url('admin/products/create.php')) ?>" class="px-4 py-2 rounded-lg bg-primary text-white">Tambah</a>
</div>
<div class="overflow-x-auto border rounded-xl">
  <table class="min-w-full">
    <thead class="bg-slate-50 text-left text-sm text-slate-600">
      <tr>
        <th class="px-4 py-3">ID</th>
        <th class="px-4 py-3">Gambar</th>
        <th class="px-4 py-3">Judul</th>
        <th class="px-4 py-3">Kategori</th>
        <th class="px-4 py-3">Harga</th>
        <th class="px-4 py-3">Status</th>
        <th class="px-4 py-3">Aksi</th>
      </tr>
    </thead>
    <tbody class="divide-y">
      <?php foreach ($products as $p): ?>
        <tr>
          <td class="px-4 py-3 font-medium">#<?= (int)$p['id'] ?></td>
          <td class="px-4 py-3">
            <?php if (!empty($p['image'])): ?>
              <img class="h-12 w-12 rounded object-cover border" src="<?= htmlspecialchars(base_url('uploads/' . $p['image'])) ?>" alt="">
            <?php endif; ?>
          </td>
          <td class="px-4 py-3"><?= htmlspecialchars($p['title']) ?></td>
          <td class="px-4 py-3"><?= htmlspecialchars($p['category_name'] ?? '-') ?></td>
          <td class="px-4 py-3 text-primary font-semibold"><?= format_price((int)$p['price']) ?></td>
          <td class="px-4 py-3">
            <span class="px-2 py-1 text-xs rounded <?= $p['is_active'] ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' ?>">
              <?= $p['is_active'] ? 'Aktif' : 'Draft' ?>
            </span>
          </td>
          <td class="px-4 py-3">
            <a class="text-primary hover:underline" href="<?= htmlspecialchars(base_url('admin/products/edit.php?id=' . (int)$p['id'])) ?>">Edit</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
