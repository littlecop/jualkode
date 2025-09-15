<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/util.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$cats = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$errors = [];

// Helper: safer random hex with fallbacks
function safe_random_hex(int $bytes): string {
    try { return bin2hex(random_bytes($bytes)); } catch (Throwable $e) {}
    if (function_exists('openssl_random_pseudo_bytes')) {
        $buf = openssl_random_pseudo_bytes($bytes, $strong);
        if ($buf !== false) return bin2hex($buf);
    }
    return bin2hex(substr(uniqid('', true), 0, $bytes));
}
$stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) { http_response_code(404); echo 'Not found'; exit; }

// Load existing gallery images
$imgsStmt = $pdo->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order, id');
$imgsStmt->execute([$id]);
$gallery = $imgsStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Delete gallery image action
    if (isset($_POST['delete_image_id'])) {
        $imgId = (int)$_POST['delete_image_id'];
        $one = $pdo->prepare('SELECT image FROM product_images WHERE id = ? AND product_id = ?');
        $one->execute([$imgId, $id]);
        if ($row = $one->fetch()) {
            @unlink(__DIR__ . '/../../uploads/' . $row['image']);
            $pdo->prepare('DELETE FROM product_images WHERE id = ? AND product_id = ?')->execute([$imgId, $id]);
        }
        header('Location: ' . base_url('admin/products/edit.php?id=' . $id));
        exit;
    }
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $price = (int)($_POST['price'] ?? 0);
    $cat = (int)($_POST['category_id'] ?? 0);
    $demo = trim($_POST['demo_url'] ?? '');
    $active = isset($_POST['is_active']) ? 1 : 0;
    $imageName = $p['image'];

    // Ensure uploads dir exists with safe permissions
    $uploadDir = __DIR__ . '/../../uploads';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }

    if (!empty($_FILES['image']['name'])) {
        $err = (int)($_FILES['image']['error'] ?? 0);
        if ($err !== UPLOAD_ERR_OK) {
            $errors[] = 'Upload gambar gagal (thumbnail). Kode error: ' . $err . '. Coba unggah file lebih kecil atau hubungi admin hosting.';
        } else {
            $tmp = $_FILES['image']['tmp_name'];
            $origExt = strtolower((string)pathinfo((string)$_FILES['image']['name'], PATHINFO_EXTENSION));
            $rand = safe_random_hex(4);
            $targetJpg = 'p_' . time() . '_' . $rand . '.jpg';
            $destJpgPath = $uploadDir . '/' . $targetJpg;
            if (compress_image($tmp, $destJpgPath, 1600, 1600, 82)) {
                $imageName = $targetJpg;
            } else {
                $fallbackName = 'p_' . time() . '_' . $rand . '.' . ($origExt ?: 'jpg');
                if (!@move_uploaded_file($tmp, $uploadDir . '/' . $fallbackName)) {
                    $errors[] = 'Gagal menyimpan file thumbnail ke server. Periksa permission folder uploads dan batasan hosting.';
                } else {
                    $imageName = $fallbackName;
                }
            }
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare('UPDATE products SET title=?, description=?, price=?, category_id=?, image=?, demo_url=?, is_active=? WHERE id=?');
        $stmt->execute([$title, $desc, $price, $cat ?: null, $imageName, $demo !== '' ? $demo : null, $active, $id]);
    }

    // Handle additional gallery images upload
    if (!$errors && !empty($_FILES['images']['name']) && is_array($_FILES['images']['name'])) {
        $nextOrder = (int)$pdo->query('SELECT COALESCE(MAX(sort_order),0) FROM product_images WHERE product_id = ' . (int)$id)->fetchColumn() + 1;
        $ins = $pdo->prepare('INSERT INTO product_images (product_id, image, sort_order) VALUES (?,?,?)');
        foreach ($_FILES['images']['name'] as $i => $name) {
            if (empty($name) || !is_uploaded_file($_FILES['images']['tmp_name'][$i])) continue;
            $tmp = $_FILES['images']['tmp_name'][$i];
            $origExt = strtolower((string)pathinfo($name, PATHINFO_EXTENSION));
            $rand = safe_random_hex(3);
            $targetJpg = 'p_' . $id . '_' . time() . '_' . $rand . '.jpg';
            $destJpgPath = $uploadDir . '/' . $targetJpg;
            if (compress_image($tmp, $destJpgPath, 1600, 1600, 80)) {
                $fname = $targetJpg;
            } else {
                $fallbackName = 'p_' . $id . '_' . time() . '_' . $rand . '.' . ($origExt ?: 'jpg');
                if (!@move_uploaded_file($tmp, $uploadDir . '/' . $fallbackName)) {
                    $errors[] = 'Gagal menyimpan salah satu gambar gallery (' . htmlspecialchars($name) . ').';
                    continue;
                }
                $fname = $fallbackName;
            }
            $ins->execute([$id, $fname, $nextOrder++]);
        }
    }

    if (!$errors) {
        header('Location: ' . base_url('admin/products/'));
        exit;
    }
}
include __DIR__ . '/../../includes/header.php';
?>
<h1 class="text-2xl font-semibold mb-4">Edit Produk</h1>
<?php if (!empty($errors)): ?>
  <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 text-amber-800 p-3 text-sm">
    <div class="font-semibold mb-1">Upload bermasalah:</div>
    <ul class="list-disc ml-5">
      <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
      <?php endforeach; ?>
    </ul>
    <div class="mt-2 text-slate-600">Tips: coba gambar ukuran lebih kecil. Admin hosting dapat menaikkan upload_max_filesize dan post_max_size.</div>
  </div>
<?php endif; ?>
<form method="post" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-6">
  <div class="space-y-4">
    <div>
      <label class="block text-sm text-slate-600 mb-1">Judul</label>
      <input name="title" value="<?= htmlspecialchars($p['title']) ?>" required class="w-full px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-primary">
    </div>
    <div>
      <label class="block text-sm text-slate-600 mb-1">Harga (Rp)</label>
      <input name="price" type="number" min="0" value="<?= (int)$p['price'] ?>" required class="w-full px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-primary">
    </div>
    <div>
      <label class="block text-sm text-slate-600 mb-1">Demo URL (opsional)</label>
      <input name="demo_url" type="url" value="<?= htmlspecialchars($p['demo_url'] ?? '') ?>" placeholder="https://contoh-demo.com" class="w-full px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-primary">
      <p class="text-xs text-slate-500 mt-1">Link ke demo aplikasi. Sertakan http(s)://</p>
    </div>
    <div>
      <label class="block text-sm text-slate-600 mb-1">Kategori</label>
      <select name="category_id" class="w-full px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-primary">
        <option value="">-</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= ($p['category_id'] == $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm text-slate-600 mb-1">Gambar (thumbnail)</label>
      <?php if (!empty($p['image'])): ?>
        <img class="h-20 w-20 rounded border mb-2" src="<?= htmlspecialchars(base_url('uploads/' . $p['image'])) ?>" alt="">
      <?php endif; ?>
      <input type="file" name="image" accept="image/*" class="w-full">
    </div>
    <div>
      <label class="block text-sm text-slate-600 mb-1">Gallery (bisa lebih dari satu)</label>
      <input type="file" name="images[]" accept="image/*" multiple class="w-full">
      <p class="text-xs text-slate-500 mt-1">Unggah gambar tambahan. Gunakan untuk galeri di halaman produk.</p>
    </div>
    <label class="inline-flex items-center gap-2">
      <input type="checkbox" name="is_active" class="rounded" <?= $p['is_active'] ? 'checked' : '' ?>> Aktif
    </label>
    <button class="px-5 py-3 rounded-lg bg-primary text-white font-medium">Simpan</button>
  </div>
  <div>
    <label class="block text-sm text-slate-600 mb-1">Deskripsi</label>
    <!-- <textarea name="description" rows="12" class="w-full px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-primary"><?= $p['description'] ?></textarea> -->
    <textarea class="ckeditor" id="ckeditor" name="description"><?= $p['description'] ?></textarea>
  </div>
</form>

<?php if ($gallery): ?>
  <div class="mt-6">
    <div class="text-sm font-semibold mb-2">Gallery saat ini</div>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
      <?php foreach ($gallery as $g): ?>
        <div class="rounded border overflow-hidden">
          <img class="w-full h-28 object-cover" src="<?= htmlspecialchars(base_url('uploads/' . $g['image'])) ?>" alt="">
          <form method="post" onsubmit="return confirm('Hapus gambar ini?')" class="p-2 text-right">
            <input type="hidden" name="delete_image_id" value="<?= (int)$g['id'] ?>">
            <button class="px-3 py-1 text-xs rounded border hover:bg-slate-50">Hapus</button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<script src="<?= base_url('admin/ckeditor-full/ckeditor.js') ?>"></script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
