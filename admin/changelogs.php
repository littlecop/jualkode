<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/util.php';

// Ensure tables exist
ensure_changelog_table();

// Load products for selector
$products = $pdo->query('SELECT id, title FROM products ORDER BY created_at DESC')->fetchAll();

// Read selected product id
$pid = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
if (!$pid && $products) {
    $pid = (int)$products[0]['id'];
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create or update
    if (isset($_POST['save_changelog'])) {
        $formPid = (int)($_POST['product_id'] ?? 0);
        $cid = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        if ($formPid && $title !== '') {
            if ($cid > 0) {
                changelog_update($cid, $formPid, $title, $content);
                flash_set('ok', 'Changelog berhasil diperbarui.');
            } else {
                changelog_add($formPid, $title, $content);
                flash_set('ok', 'Changelog berhasil ditambahkan.');
            }
            header('Location: ' . base_url('admin/changelogs.php?product_id=' . $formPid));
            exit;
        } else {
            flash_set('err', 'Mohon pilih produk dan isi judul.');
        }
    }
    // Delete
    if (isset($_POST['delete_id']) && isset($_POST['product_id'])) {
        $delId = (int)$_POST['delete_id'];
        $formPid = (int)$_POST['product_id'];
        if ($delId && $formPid) {
            changelog_delete($delId, $formPid);
            flash_set('ok', 'Changelog dihapus.');
        }
        header('Location: ' . base_url('admin/changelogs.php?product_id=' . $formPid));
        exit;
    }
}

// Load changelogs for selected product
$entries = $pid ? changelog_list($pid, 50) : [];

include __DIR__ . '/../includes/header.php';
?>
<h1 class="text-2xl font-semibold mb-4">Changelog Produk</h1>

<?php if ($m = flash_get('ok')): ?>
  <div class="mb-4 p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm"><?= nl2br(htmlspecialchars($m)) ?></div>
<?php endif; ?>
<?php if ($m = flash_get('err')): ?>
  <div class="mb-4 p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm"><?= nl2br(htmlspecialchars($m)) ?></div>
<?php endif; ?>

<!-- Bantuan penulisan judul changelog -->
<div class="mb-4 rounded-xl border border-slate-200 bg-white p-4">
  <div class="text-sm font-semibold">Petunjuk Penulisan Changelog</div>
  <ul class="mt-2 text-sm text-slate-600 space-y-2">
    <li class="flex items-center gap-2">
      <span class="inline-block h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
      <span class="inline-flex items-center gap-1">
        <span class="text-emerald-600" aria-hidden="true"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 4a1 1 0 011 1v4h4a1 1 0 110 2h-4v4a1 1 0 11-2 0v-4H5a1 1 0 110-2h4V5a1 1 0 011-1z"/></svg></span>
        <strong>Add</strong> → ikon check, warna emerald
      </span>
    </li>
    <li class="flex items-center gap-2">
      <span class="inline-block h-2.5 w-2.5 rounded-full bg-amber-500"></span>
      <span class="inline-flex items-center gap-1">
        <span class="text-amber-600" aria-hidden="true"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M19.77 7.23l-2.12-2.12-3.54 3.54-1.41-1.41 3.54-3.54-2.12-2.12L10.58 3.1 8.46 1 7.05 2.41 9.17 4.53 3 10.7V14h3.3l6.17-6.17 2.12 2.12-6.17 6.17V20h2.12l6.17-6.17 2.12 2.12 1.41-1.41-2.12-2.12 3.54-3.54z"/></svg></span>
        <strong>Fix</strong> → ikon bug, warna amber
      </span>
    </li>
    <li class="flex items-center gap-2">
      <span class="inline-block h-2.5 w-2.5 rounded-full bg-indigo-500"></span>
      <span class="inline-flex items-center gap-1">
        <span class="text-indigo-600" aria-hidden="true"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M11 3l1.5 5H18l-4 3 1.5 5L11 16l-4 3 1.5-5-4-3h5.5L11 3z"/></svg></span>
        <strong>Improve/Change</strong> → ikon bolt, warna indigo
      </span>
    </li>
  </ul>
  <div class="mt-2 text-xs text-slate-500">Tip: Sertakan versi pada judul (misal: v1, v1.2, v1.2.3) untuk menampilkan badge versi otomatis.</div>
</div>

<form method="get" class="mb-4">
  <label class="block text-sm text-slate-600 mb-1">Pilih Produk</label>
  <div class="flex gap-2 items-center">
    <select name="product_id" class="px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-primary">
      <?php foreach ($products as $prod): ?>
        <option value="<?= (int)$prod['id'] ?>" <?= $pid === (int)$prod['id'] ? 'selected' : '' ?>>#<?= (int)$prod['id'] ?> - <?= htmlspecialchars($prod['title']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="px-4 py-2 rounded-lg border">Pilih</button>
  </div>
</form>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
  <div class="rounded-xl border border-slate-200 bg-white p-4">
    <div class="text-sm font-semibold mb-2">Tambah / Edit Changelog</div>
    <form method="post" class="space-y-3">
      <input type="hidden" name="id" id="chg_id" value="0">
      <div>
        <label class="block text-sm text-slate-600 mb-1">Produk</label>
        <select name="product_id" id="chg_product" class="w-full px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-primary">
          <?php foreach ($products as $prod): ?>
            <option value="<?= (int)$prod['id'] ?>" <?= $pid === (int)$prod['id'] ? 'selected' : '' ?>>#<?= (int)$prod['id'] ?> - <?= htmlspecialchars($prod['title']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm text-slate-600 mb-1">Judul / Ringkasan</label>
        <input name="title" id="chg_title" required class="w-full px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Contoh: v1.2 - Penambahan fitur X">
      </div>
      <div>
        <label class="block text-sm text-slate-600 mb-1">Detail (opsional)</label>
        <textarea name="content" id="chg_content" rows="8" class="ckeditor w-full px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
      </div>
      <div class="flex gap-2">
        <button name="save_changelog" value="1" class="px-4 py-2 rounded-lg bg-primary text-white">Simpan</button>
        <button type="reset" class="px-4 py-2 rounded-lg border" onclick="resetForm()">Reset</button>
      </div>
    </form>
  </div>

  <div class="rounded-xl border border-slate-200 bg-white p-4">
    <div class="text-sm font-semibold mb-2">Daftar Changelog</div>
    <?php if (!$entries): ?>
      <div class="text-sm text-slate-500">Belum ada changelog untuk produk ini.</div>
    <?php else: ?>
      <div class="divide-y">
        <?php foreach ($entries as $e): ?>
          <?php $meta = changelog_meta((string)$e['title']); ?>
          <div class="py-3">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="flex items-center gap-2 font-medium text-sm">
                  <span class="inline-block h-2.5 w-2.5 rounded-full <?= htmlspecialchars($meta['dotBgClass']) ?>"></span>
                  <span class="text-slate-700" aria-hidden="true"><?php echo $meta['iconSvg']; ?></span>
                  <span><?= htmlspecialchars($e['title']) ?></span>
                  <?php if (!empty($meta['version'])): ?>
                    <span class="ml-1 inline-flex items-center text-[10px] px-2 py-0.5 rounded-full border <?= htmlspecialchars($meta['badgeClass']) ?>"><?= htmlspecialchars($meta['version']) ?></span>
                  <?php endif; ?>
                </div>
                <div class="text-xs text-slate-500 mt-1"><?= htmlspecialchars(date('d M Y H:i', strtotime($e['created_at']))) ?></div>
              </div>
              <div class="flex gap-2">
                <button class="px-3 py-1 text-xs rounded border hover:bg-slate-50" onclick="editEntry(<?= (int)$e['id'] ?>)">Edit</button>
                <form method="post" onsubmit="return confirm('Hapus changelog ini?')">
                  <input type="hidden" name="delete_id" value="<?= (int)$e['id'] ?>">
                  <input type="hidden" name="product_id" value="<?= (int)$pid ?>">
                  <button class="px-3 py-1 text-xs rounded border hover:bg-slate-50">Hapus</button>
                </form>
              </div>
            </div>
            <?php if (!empty($e['content'])): ?>
              <div class="prose prose-sm max-w-none text-sm mt-2"><?php echo sanitize_changelog_html((string)$e['content']); ?></div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<script src="<?= base_url('admin/ckeditor-full/ckeditor.js') ?>"></script>
<script>
  // Initialize CKEditor for content field
  if (window.CKEDITOR) {
    CKEDITOR.replace('chg_content', {
      height: 220,
      removePlugins: 'elementspath',
      resize_enabled: false,
      toolbar: [
        ['Bold', 'Italic', 'Underline', 'Strike'],
        ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent'],
        ['Link', 'Unlink'],
        ['RemoveFormat', 'Source']
      ]
    });
  }
  function resetForm(){
    document.getElementById('chg_id').value = 0;
    document.getElementById('chg_title').value = '';
    if (window.CKEDITOR && CKEDITOR.instances['chg_content']) {
      CKEDITOR.instances['chg_content'].setData('');
    } else {
      document.getElementById('chg_content').value = '';
    }
  }
  function editEntry(id){
    // Simple inline fetch using embedded dataset to avoid new endpoint
    const data = <?= json_encode($entries, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
    const row = data.find(x => +x.id === +id);
    if(!row) return;
    document.getElementById('chg_id').value = row.id;
    document.getElementById('chg_title').value = row.title;
    if (window.CKEDITOR && CKEDITOR.instances['chg_content']) {
      CKEDITOR.instances['chg_content'].setData(row.content || '');
    } else {
      document.getElementById('chg_content').value = row.content || '';
    }
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
