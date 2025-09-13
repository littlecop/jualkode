<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/util.php';

// Ensure table exists
ensure_settings_table();

// Load categories for category descriptions
$cats = [];
try {
    $cats = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
} catch (Throwable $e) { /* ignore */ }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['checkout_mode'] ?? 'cart';
    $waNumber = trim($_POST['wa_number'] ?? '');
    $siteName = trim($_POST['site_name'] ?? '');
    $ga4Id    = trim($_POST['ga4_id'] ?? '');

    if (!in_array($mode, ['cart','wa'], true)) {
        $errors[] = 'Mode tidak valid.';
    }
    if ($mode === 'wa') {
        if ($waNumber === '') {
            $errors[] = 'Nomor WhatsApp wajib diisi ketika mode WA aktif.';
        }
    }

    if (!$errors) {
        set_setting('checkout_mode', $mode);
        if ($waNumber !== '') {
            set_setting('wa_number', $waNumber);
        }
        // Save site name and GA4 ID
        set_setting('site_name', $siteName);
        set_setting('ga4_id', $ga4Id);

        // Handle site_logo upload
        if (!empty($_FILES['site_logo']) && is_array($_FILES['site_logo']) && ($_FILES['site_logo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $tmp  = (string)$_FILES['site_logo']['tmp_name'];
            $name = (string)$_FILES['site_logo']['name'];
            $size = (int)($_FILES['site_logo']['size'] ?? 0);
            $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $allowed = ['png','jpg','jpeg','webp','svg'];
            if (!in_array($ext, $allowed, true)) {
                $errors[] = 'Format logo tidak didukung. Gunakan PNG/JPG/WebP/SVG.';
            } elseif ($size > 2*1024*1024) { // 2MB
                $errors[] = 'Ukuran logo terlalu besar. Maksimal 2MB.';
            } else {
                $safeBase = preg_replace('/[^a-zA-Z0-9_-]+/', '-', pathinfo($name, PATHINFO_FILENAME));
                if ($safeBase === '') { $safeBase = 'logo'; }
                $newName = 'logo_' . time() . '_' . $safeBase . '.' . $ext;
                $destRel = 'uploads/' . $newName;
                $destAbs = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . $destRel;
                if (@move_uploaded_file($tmp, $destAbs)) {
                    set_setting('site_logo', $destRel);
                } else {
                    $errors[] = 'Gagal menyimpan file logo.';
                }
            }
        }

        // Delete logo when requested
        if (!empty($_POST['delete_logo']) && $_POST['delete_logo'] === '1') {
            $current = get_setting('site_logo', '');
            if ($current !== '') {
                $abs = realpath(__DIR__ . '/..' . DIRECTORY_SEPARATOR . $current);
                // Only delete if inside uploads directory
                $uploads = realpath(__DIR__ . '/../uploads');
                if ($abs && $uploads && str_starts_with($abs, $uploads)) {
                    @unlink($abs);
                }
            }
            set_setting('site_logo', '');
        }

        // Save category descriptions
        foreach ($cats as $c) {
            $cid = (int)$c['id'];
            $key = 'cat_desc_' . $cid;
            $val = isset($_POST[$key]) ? (string)$_POST[$key] : '';
            set_setting($key, $val);
        }

        if (!$errors) {
            flash_set('ok', 'Pengaturan berhasil disimpan.');
            header('Location: ' . base_url('admin/settings.php'));
            exit;
        } else {
            flash_set('err', implode("\n", $errors));
        }
    } else {
        flash_set('err', implode("\n", $errors));
    }
}

$currentMode = get_setting('checkout_mode', 'cart');
$currentWa = get_setting('wa_number', '081227841755');
// Current site settings
$currentSiteName = get_setting('site_name', 'Store Code Market');
$currentGa4 = get_setting('ga4_id', '');
$currentLogo = get_setting('site_logo', '');

include __DIR__ . '/../includes/header.php';
?>
<h1 class="text-2xl font-semibold mb-4">Pengaturan Situs</h1>
<?php if ($m = flash_get('ok')): ?>
  <div class="mb-4 p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm"><?= nl2br(htmlspecialchars($m)) ?></div>
<?php endif; ?>
<?php if ($m = flash_get('err')): ?>
  <div class="mb-4 p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm"><?= nl2br(htmlspecialchars($m)) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="space-y-8 max-w-3xl">
  <div class="rounded-xl border p-4 bg-white">
    <div class="text-lg font-semibold mb-3">Brand & Analitik</div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm text-slate-600 mb-1">Nama Situs</label>
        <input name="site_name" value="<?= htmlspecialchars($currentSiteName) ?>" placeholder="Nama Brand" class="w-full px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-primary">
      </div>
      <div>
        <label class="block text-sm text-slate-600 mb-1">Google Analytics 4 ID</label>
        <input name="ga4_id" value="<?= htmlspecialchars($currentGa4) ?>" placeholder="G-XXXXXXXXXX" class="w-full px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-primary">
        <p class="text-xs text-slate-500 mt-1">Diinject otomatis pada head jika terisi.</p>
      </div>
      <div class="md:col-span-2">
        <label class="block text-sm text-slate-600 mb-1">Logo Situs</label>
        <?php if ($currentLogo): ?>
          <div class="flex items-center gap-3 mb-2">
            <img src="<?= htmlspecialchars(base_url($currentLogo)) ?>" alt="Logo" class="h-10 w-auto border rounded bg-white p-1">
            <span class="text-xs text-slate-500">File saat ini: <?= htmlspecialchars($currentLogo) ?></span>
          </div>
          <label class="inline-flex items-center gap-2 text-sm mb-2">
            <input type="checkbox" name="delete_logo" value="1" class="rounded border-slate-300">
            <span>Hapus logo saat ini</span>
          </label>
        <?php endif; ?>
        <input type="file" name="site_logo" accept=".png,.jpg,.jpeg,.webp,.svg" class="block w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:opacity-90">
        <p class="text-xs text-slate-500 mt-1">PNG/JPG/WebP/SVG, maksimal 2MB. Mengunggah file baru akan mengganti logo saat ini.</p>
      </div>
    </div>
  </div>

  <div class="rounded-xl border p-4 bg-white">
    <div class="text-lg font-semibold mb-3">Checkout</div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm text-slate-600 mb-1">Mode Tombol Checkout</label>
        <select name="checkout_mode" class="w-full px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-primary">
          <option value="cart" <?= $currentMode === 'cart' ? 'selected' : '' ?>>Tambah ke Keranjang</option>
          <option value="wa" <?= $currentMode === 'wa' ? 'selected' : '' ?>>Order via WhatsApp</option>
        </select>
        <p class="text-xs text-slate-500 mt-1">Pilih aksi utama di halaman produk.</p>
      </div>
      <div>
        <label class="block text-sm text-slate-600 mb-1">Nomor WhatsApp</label>
        <input name="wa_number" value="<?= htmlspecialchars($currentWa) ?>" placeholder="08xxxxxxxxxx" class="w-full px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-primary">
        <p class="text-xs text-slate-500 mt-1">Nomor akan dinormalisasi otomatis (contoh: 0812.. menjadi 62812..). Hanya dipakai jika mode WA aktif.</p>
      </div>
    </div>
  </div>

  <?php if ($cats): ?>
  <div class="rounded-xl border p-4 bg-white">
    <div class="text-lg font-semibold mb-3">Deskripsi SEO per Kategori</div>
    <div class="space-y-4">
      <?php foreach ($cats as $c): $cid=(int)$c['id']; $key='cat_desc_'.$cid; $val=get_setting($key,''); ?>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Kategori: <?= htmlspecialchars($c['name']) ?></label>
          <textarea name="<?= htmlspecialchars($key) ?>" rows="3" class="w-full px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Tulis deskripsi SEO singkat untuk kategori ini (150–300 kata)."><?= htmlspecialchars($val) ?></textarea>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <button class="px-5 py-3 rounded-lg bg-primary text-white font-medium">Simpan</button>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
