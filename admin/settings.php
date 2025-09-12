<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/util.php';

// Ensure table exists
ensure_settings_table();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['checkout_mode'] ?? 'cart';
    $waNumber = trim($_POST['wa_number'] ?? '');

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
        flash_set('ok', 'Pengaturan berhasil disimpan.');
        header('Location: ' . base_url('admin/settings.php'));
        exit;
    } else {
        flash_set('err', implode("\n", $errors));
    }
}

$currentMode = get_setting('checkout_mode', 'cart');
$currentWa = get_setting('wa_number', '081227841755');

include __DIR__ . '/../includes/header.php';
?>
<h1 class="text-2xl font-semibold mb-4">Pengaturan Checkout</h1>
<?php if ($m = flash_get('ok')): ?>
  <div class="mb-4 p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm"><?= nl2br(htmlspecialchars($m)) ?></div>
<?php endif; ?>
<?php if ($m = flash_get('err')): ?>
  <div class="mb-4 p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm"><?= nl2br(htmlspecialchars($m)) ?></div>
<?php endif; ?>

<form method="post" class="space-y-5 max-w-xl">
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

  <button class="px-5 py-3 rounded-lg bg-primary text-white font-medium">Simpan</button>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
