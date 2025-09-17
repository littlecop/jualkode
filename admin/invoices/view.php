<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
require_once __DIR__ . '/../../includes/util.php';
ensure_invoices_tables();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  flash_set('err', 'Invoice tidak ditemukan.');
  header('Location: ' . base_url('admin/invoices/index.php'));
  exit;
}

// Load invoice
$stmt = $pdo->prepare('SELECT * FROM invoices WHERE id = ?');
$stmt->execute([$id]);
$inv = $stmt->fetch();
if (!$inv) {
  flash_set('err', 'Invoice tidak ditemukan.');
  header('Location: ' . base_url('admin/invoices/index.php'));
  exit;
}
$its = $pdo->prepare('SELECT description, qty, price, line_total FROM invoice_items WHERE invoice_id = ? ORDER BY id');
$its->execute([$id]);
$items = $its->fetchAll() ?: [];

$pageTitle = 'Invoice #' . (int)$inv['id'];
$invoiceLogo = get_setting('site_logo', '');
$invoiceWatermark = get_setting('invoice_watermark_text', '');
$invoicePaymentNotes = get_setting('invoice_payment_notes', '');
include __DIR__ . '/../../includes/header.php';
?>
<style>
  /* Print improvements: larger text and spacing for better readability */
  @media print {
    body { font-size: 12pt; line-height: 1.5; }
    h1, .text-2xl, .text-3xl { font-size: 22pt !important; }
    table.min-w-full th, table.min-w-full td { padding: 10px !important; }
    .p-6 { padding: 16px !important; }
  }
</style>
<div class="mb-6 flex items-center justify-between">
  <h1 class="text-2xl font-semibold">Invoice: <?= htmlspecialchars($inv['invoice_number']) ?></h1>
  <div class="flex gap-2">
    <a href="<?= htmlspecialchars(base_url('admin/invoices/index.php')) ?>" class="px-4 py-2 rounded-lg border hover:bg-slate-50">Kembali</a>
    <a href="<?= htmlspecialchars(base_url('admin/invoices/generate.php?id=' . (int)$inv['id'])) ?>" class="px-4 py-2 rounded-lg border hover:bg-slate-50">Edit</a>
    <a href="<?= htmlspecialchars(base_url('admin/invoices/export_pdf.php?id=' . (int)$inv['id'])) ?>" class="px-4 py-2 rounded-lg border hover:bg-slate-50">Export PDF</a>
    <button onclick="window.print()" class="px-4 py-2 rounded-lg bg-primary text-white hover:opacity-90">Cetak</button>
  </div>
</div>

<div class="bg-white border rounded-2xl p-6 print:p-0 relative overflow-hidden">
  <?php if ($invoiceWatermark !== ''): ?>
  <div style="position:absolute; inset:0; display:grid; place-items:center; pointer-events:none; opacity:.06; font-size:72px; font-weight:700; transform:rotate(-20deg);">
    <?= htmlspecialchars($invoiceWatermark) ?>
  </div>
  <?php endif; ?>
  <div class="flex flex-wrap items-start justify-between gap-6">
    <div>
      <div class="text-3xl font-semibold">Invoice</div>
      <div class="text-slate-600 mt-1">No: <?= htmlspecialchars($inv['invoice_number']) ?></div>
      <div class="text-slate-600">Tanggal: <?= htmlspecialchars($inv['invoice_date']) ?></div>
      <?php if (!empty($inv['due_date'])): ?><div class="text-slate-600">Jatuh Tempo: <?= htmlspecialchars($inv['due_date']) ?></div><?php endif; ?>
      <?php $st = (string)($inv['status'] ?? 'Draft'); $badge = $st==='Paid'?'border-emerald-300 text-emerald-700 bg-emerald-50':($st==='Sent'?'border-amber-300 text-amber-700 bg-amber-50':'border-slate-300 text-slate-700 bg-slate-50'); ?>
      <div class="mt-2 inline-flex items-center px-2 py-1 text-xs rounded-full border <?= $badge ?>"><?= htmlspecialchars($st) ?></div>
    </div>
    <div class="text-right">
      <div class="font-semibold">Dari</div>
      <div><?= nl2br(htmlspecialchars($inv['from_name'] ?? '')) ?></div>
      <?php if (!empty($inv['from_email'])): ?><div class="text-slate-600 text-sm">Email: <?= htmlspecialchars($inv['from_email']) ?></div><?php endif; ?>
      <?php if (!empty($inv['from_phone'])): ?><div class="text-slate-600 text-sm">Telp: <?= htmlspecialchars($inv['from_phone']) ?></div><?php endif; ?>
      <?php if (!empty($inv['from_address'])): ?><div class="text-slate-600 text-sm">Alamat: <?= nl2br(htmlspecialchars($inv['from_address'])) ?></div><?php endif; ?>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
    <div class="border rounded-xl p-4">
      <div class="font-semibold mb-2">Tagihan Kepada</div>
      <div class="text-slate-700"><?= nl2br(htmlspecialchars($inv['bill_name'] ?? '')) ?></div>
      <?php if (!empty($inv['bill_email'])): ?><div class="text-slate-600 text-sm">Email: <?= htmlspecialchars($inv['bill_email']) ?></div><?php endif; ?>
      <?php if (!empty($inv['bill_phone'])): ?><div class="text-slate-600 text-sm">Telp: <?= htmlspecialchars($inv['bill_phone']) ?></div><?php endif; ?>
      <?php if (!empty($inv['bill_address'])): ?><div class="text-slate-600 text-sm">Alamat: <?= nl2br(htmlspecialchars($inv['bill_address'])) ?></div><?php endif; ?>
    </div>
  </div>

  <div class="overflow-x-auto mt-6">
    <table class="min-w-full">
      <thead class="bg-slate-50 text-left text-sm text-slate-600">
        <tr>
          <th class="px-4 py-3">Produk/Deskripsi</th>
          <th class="px-4 py-3">Qty</th>
          <th class="px-4 py-3">Harga</th>
          <th class="px-4 py-3">Jumlah</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        <?php foreach ($items as $it): ?>
          <tr>
            <td class="px-4 py-3 align-top">
              <div class="font-medium"><?= htmlspecialchars($it['description']) ?></div>
            </td>
            <td class="px-4 py-3"><?= (int)$it['qty'] ?></td>
            <td class="px-4 py-3"><?= format_price((int)$it['price']) ?></td>
            <td class="px-4 py-3 font-semibold text-right"><?= format_price((int)$it['line_total']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
      <?php if (!empty($inv['notes'])): ?>
        <div class="text-sm text-slate-600">Catatan:</div>
        <div class="mt-1 whitespace-pre-wrap"><?= nl2br(htmlspecialchars($inv['notes'])) ?></div>
      <?php endif; ?>
      <?php if (!empty($invoicePaymentNotes)): ?>
        <div class="mt-4">
          <div class="text-sm text-slate-600 mb-2">Catatan Pembayaran</div>
          <div class="rounded-lg border border-slate-200 bg-white p-4">
            <?php $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string)$invoicePaymentNotes))); ?>
            <?php if (!empty($lines)): ?>
              <ul class="list-disc pl-5 space-y-1">
                <?php foreach ($lines as $ln): ?>
                  <li><?= htmlspecialchars($ln) ?></li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <div class="whitespace-pre-wrap"><?= nl2br(htmlspecialchars($invoicePaymentNotes)) ?></div>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
    <div>
      <div class="bg-slate-50 rounded-xl p-4">
        <div class="flex items-center justify-between py-1">
          <div>Subtotal</div>
          <div class="font-medium"><?= format_price((int)$inv['subtotal']) ?></div>
        </div>
        <div class="flex items-center justify-between py-1">
          <div>Pajak (<?= htmlspecialchars((string)$inv['tax_percent']) ?>%)</div>
          <div class="font-medium"><?= format_price((int)$inv['tax_amount']) ?></div>
        </div>
        <?php if ((int)$inv['shipping_amount'] > 0): ?>
        <div class="flex items-center justify-between py-1">
          <div>Ongkir</div>
          <div class="font-medium"><?= format_price((int)$inv['shipping_amount']) ?></div>
        </div>
        <?php endif; ?>
        <?php if ((int)$inv['discount_amount'] > 0): ?>
        <div class="flex items-center justify-between py-1">
          <div>Diskon</div>
          <div class="font-medium text-emerald-600">-<?= format_price((int)$inv['discount_amount']) ?></div>
        </div>
        <?php endif; ?>
        <div class="h-px my-2 bg-slate-200"></div>
        <div class="flex items-center justify-between py-1 text-lg">
          <div class="font-semibold">Total</div>
          <div class="font-bold text-primary"><?= format_price((int)$inv['grand_total']) ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="mt-8 text-sm text-slate-500">
    Invoice dibuat pada <?= htmlspecialchars($inv['created_at']) ?>.
  </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
