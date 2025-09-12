<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

$cats = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $price = (int)($_POST['price'] ?? 0);
    $cat = (int)($_POST['category_id'] ?? 0);
    $demo = trim($_POST['demo_url'] ?? '');
    $active = isset($_POST['is_active']) ? 1 : 0;
    $imageName = null;

    if (!empty($_FILES['image']['name'])) {
        @mkdir(__DIR__ . '/../../uploads', 0777, true);
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $imageName = 'p_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
        move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../../uploads/' . $imageName);
    }

    $stmt = $pdo->prepare('INSERT INTO products (title, description, price, category_id, image, demo_url, is_active) VALUES (?,?,?,?,?,?,?)');
    $stmt->execute([$title, $desc, $price, $cat ?: null, $imageName, $demo !== '' ? $demo : null, $active]);
    $newId = (int)$pdo->lastInsertId();
    
    // Handle multiple gallery images
    if (!empty($_FILES['images']['name']) && is_array($_FILES['images']['name'])) {
        @mkdir(__DIR__ . '/../../uploads', 0777, true);
        $order = 1;
        $ins = $pdo->prepare('INSERT INTO product_images (product_id, image, sort_order) VALUES (?,?,?)');
        foreach ($_FILES['images']['name'] as $i => $name) {
            if (empty($name) || !is_uploaded_file($_FILES['images']['tmp_name'][$i])) continue;
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $fname = 'p_' . $newId . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . strtolower($ext);
            move_uploaded_file($_FILES['images']['tmp_name'][$i], __DIR__ . '/../../uploads/' . $fname);
            $ins->execute([$newId, $fname, $order++]);
        }
    }
    header('Location: ' . base_url('admin/products/'));
    exit;
}
include __DIR__ . '/../../includes/header.php';
?>
<h1 class="text-2xl font-semibold mb-4">Tambah Produk</h1>
<form method="post" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-6">
  <div class="space-y-4">
    <div>
      <label class="block text-sm text-slate-600 mb-1">Judul</label>
      <input name="title" required class="w-full px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-primary">
    </div>
    <div>
      <label class="block text-sm text-slate-600 mb-1">Harga (Rp)</label>
      <input name="price" type="number" min="0" required class="w-full px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-primary">
    </div>
    <div>
      <label class="block text-sm text-slate-600 mb-1">Demo URL (opsional)</label>
      <input name="demo_url" type="url" placeholder="https://contoh-demo.com" class="w-full px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-primary">
      <p class="text-xs text-slate-500 mt-1">Link ke demo aplikasi. Sertakan http(s)://</p>
    </div>
    <div>
      <label class="block text-sm text-slate-600 mb-1">Kategori</label>
      <select name="category_id" class="w-full px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-primary">
        <option value="">-</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm text-slate-600 mb-1">Gambar (thumbnail)</label>
      <input type="file" name="image" accept="image/*" class="w-full">
    </div>
    <div>
      <label class="block text-sm text-slate-600 mb-1">Gallery (bisa lebih dari satu)</label>
      <input type="file" name="images[]" accept="image/*" multiple class="w-full">
      <p class="text-xs text-slate-500 mt-1">Opsional. Gambar tambahan untuk gallery produk.</p>
    </div>
    <label class="inline-flex items-center gap-2">
      <input type="checkbox" name="is_active" class="rounded"> Aktif
    </label>
    <button class="px-5 py-3 rounded-lg bg-primary text-white font-medium">Simpan</button>
  </div>
  <div>
    <label class="block text-sm text-slate-600 mb-1">Deskripsi</label>
    <!-- <textarea name="description" rows="12" class="w-full px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-primary"></textarea> -->
    <textarea class="ckeditor" id="ckeditor" name="description"></textarea>
  </div>
</form>
<script src="<?= base_url('admin/ckeditor-full/ckeditor.js') ?>"></script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
