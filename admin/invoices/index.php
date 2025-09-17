<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
require_once __DIR__ . '/../../includes/util.php';
ensure_invoices_tables();

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delId = (int)$_POST['delete_id'];
    if ($delId > 0) {
        global $pdo;
        $pdo->prepare('DELETE FROM invoices WHERE id=?')->execute([$delId]);
        flash_set('ok', 'Invoice #' . $delId . ' telah dihapus.');
        header('Location: ' . base_url('admin/invoices/index.php'));
        exit;
    }
}

// Filters
$status = isset($_GET['status']) ? (string)$_GET['status'] : '';
if (!in_array($status, ['Draft','Sent','Paid',''], true)) { $status = ''; }
$q = trim((string)($_GET['q'] ?? ''));

// Fetch invoices with filters
$invoices = [];
try {
    $where = [];
    $params = [];
    if ($status !== '') { $where[] = 'status = ?'; $params[] = $status; }
    if ($q !== '') { $where[] = '(invoice_number LIKE ? OR bill_name LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
    $sql = 'SELECT id, invoice_number, invoice_date, bill_name, grand_total, status, created_at FROM invoices';
    if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
    $sql .= ' ORDER BY id DESC LIMIT 200';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $invoices = $stmt->fetchAll();
} catch (Throwable $e) { $invoices = []; }

$pageTitle = 'Invoices';
include __DIR__ . '/../../includes/header.php';
?>
<div class="flex items-center justify-between mb-4">
  <h1 class="text-2xl font-semibold">Invoices</h1>
  <a href="<?= htmlspecialchars(base_url('admin/invoices/generate.php')) ?>" class="px-4 py-2 rounded-lg bg-primary text-white hover:opacity-90">Buat Invoice</a>
</div>
<?php if ($m = flash_get('ok')): ?>
  <div class="mb-4 p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm"><?= nl2br(htmlspecialchars($m)) ?></div>
<?php endif; ?>
<?php if ($m = flash_get('err')): ?>
  <div class="mb-4 p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm"><?= nl2br(htmlspecialchars($m)) ?></div>
<?php endif; ?>
<form method="get" class="mb-3 flex flex-wrap gap-2 items-end">
  <div>
    <label class="block text-sm text-slate-600 mb-1">Status</label>
    <select name="status" class="px-3 py-2 rounded-lg border bg-white">
      <option value="" <?= $status===''?'selected':'' ?>>Semua</option>
      <option value="Draft" <?= $status==='Draft'?'selected':'' ?>>Draft</option>
      <option value="Sent" <?= $status==='Sent'?'selected':'' ?>>Sent</option>
      <option value="Paid" <?= $status==='Paid'?'selected':'' ?>>Paid</option>
    </select>
  </div>
  <div>
    <label class="block text-sm text-slate-600 mb-1">Cari</label>
    <input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="No. Invoice / Nama" class="px-3 py-2 rounded-lg border">
  </div>
  <div>
    <button class="px-4 py-2 rounded-lg border hover:bg-slate-50">Filter</button>
  </div>
</form>

<div class="overflow-x-auto border rounded-xl bg-white">
  <table class="min-w-full">
    <thead class="bg-slate-50 text-left text-sm text-slate-600">
      <tr>
        <th class="px-4 py-3">ID</th>
        <th class="px-4 py-3">No. Invoice</th>
        <th class="px-4 py-3">Tanggal</th>
        <th class="px-4 py-3">Customer</th>
        <th class="px-4 py-3">Total</th>
        <th class="px-4 py-3">Status</th>
        <th class="px-4 py-3 text-right">Aksi</th>
      </tr>
    </thead>
    <tbody class="divide-y">
      <?php foreach ($invoices as $inv): ?>
        <tr>
          <td class="px-4 py-3 font-medium">#<?= (int)$inv['id'] ?></td>
          <td class="px-4 py-3"><?= htmlspecialchars($inv['invoice_number']) ?></td>
          <td class="px-4 py-3"><?= htmlspecialchars($inv['invoice_date']) ?></td>
          <td class="px-4 py-3"><?= htmlspecialchars($inv['bill_name'] ?? '') ?></td>
          <td class="px-4 py-3 text-primary font-semibold"><?= format_price((int)$inv['grand_total']) ?></td>
          <td class="px-4 py-3">
            <?php $st = (string)($inv['status'] ?? 'Draft'); $badge = $st==='Paid'?'bg-emerald-50 text-emerald-700 border-emerald-200':($st==='Sent'?'bg-amber-50 text-amber-700 border-amber-200':'bg-slate-50 text-slate-700 border-slate-200'); ?>
            <span class="inline-flex items-center px-2 py-1 text-xs rounded-full border <?= $badge ?>"><?= htmlspecialchars($st) ?></span>
          </td>
          <td class="px-4 py-3">
            <div class="flex items-center gap-2 justify-end">
              <a class="px-3 py-1 rounded-lg border hover:bg-slate-50" href="<?= htmlspecialchars(base_url('admin/invoices/view.php?id=' . (int)$inv['id'])) ?>">View</a>
              <a class="px-3 py-1 rounded-lg border hover:bg-slate-50" href="<?= htmlspecialchars(base_url('admin/invoices/generate.php?id=' . (int)$inv['id'])) ?>">Edit</a>
              <a class="px-3 py-1 rounded-lg border hover:bg-slate-50" href="<?= htmlspecialchars(base_url('admin/invoices/export_pdf.php?id=' . (int)$inv['id'])) ?>">Export PDF</a>
              <form method="post" onsubmit="return confirm('Hapus invoice ini?');">
                <input type="hidden" name="delete_id" value="<?= (int)$inv['id'] ?>">
                <button class="px-3 py-1 rounded-lg border text-rose-600 hover:bg-rose-50">Hapus</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$invoices): ?>
        <tr><td colspan="6" class="px-4 py-6 text-center text-slate-600">Belum ada invoice.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
