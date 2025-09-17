<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
require_once __DIR__ . '/../../includes/util.php';
ensure_invoices_tables();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  http_response_code(400);
  echo 'Invalid invoice id';
  exit;
}

// Load invoice
$stmt = $pdo->prepare('SELECT * FROM invoices WHERE id = ?');
$stmt->execute([$id]);
$inv = $stmt->fetch();
if (!$inv) {
  http_response_code(404);
  echo 'Invoice not found';
  exit;
}
$its = $pdo->prepare('SELECT description, qty, price, line_total FROM invoice_items WHERE invoice_id = ? ORDER BY id');
$its->execute([$id]);
$items = $its->fetchAll() ?: [];

// Build HTML for PDF (standalone minimal styles)
ob_start();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Invoice <?= htmlspecialchars($inv['invoice_number']) ?></title>
  <style>
    @page { size: A4; margin: 14mm; }
    * { box-sizing: border-box; }
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; color: #0f172a; font-size: 12pt; line-height: 1.5; }
    h1 { margin: 0; font-size: 22pt; }
    .muted { color: #475569; }
    .wrap { padding: 0; }
    .row { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; }
    .box { border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; }
    table { width: 100%; border-collapse: collapse; font-size: 11pt; }
    th, td { padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: left; vertical-align: top; }
    th { background: #f1f5f9; }
    .right { text-align: right; }
    .totals { width: 60%; margin-left: auto; }
    .badge { display:inline-flex; padding: 2px 8px; border-radius: 999px; font-size: 10px; border:1px solid #cbd5e1; }
    .badge-paid { background:#ecfdf5; color:#047857; border-color:#a7f3d0; }
    .badge-sent { background:#fffbeb; color:#b45309; border-color:#fde68a; }
    .badge-draft{ background:#f8fafc; color:#334155; border-color:#cbd5e1; }
    .wm { position: absolute; inset: 0; display:flex; align-items:center; justify-content:center; opacity:.06; font-size:64px; font-weight:700; transform: rotate(-20deg); }
  </style>
</head>
<body>
  <div class="wrap" style="position:relative;">
    <?php $wm = get_setting('invoice_watermark_text',''); if ($wm !== ''): ?>
      <div class="wm"><?= htmlspecialchars($wm) ?></div>
    <?php endif; ?>
    <div class="row">
      <div>
        <h1>Invoice</h1>
        <div class="muted">No: <?= htmlspecialchars($inv['invoice_number']) ?></div>
        <div class="muted">Tanggal: <?= htmlspecialchars($inv['invoice_date']) ?></div>
        <?php if (!empty($inv['due_date'])): ?><div class="muted">Jatuh Tempo: <?= htmlspecialchars($inv['due_date']) ?></div><?php endif; ?>
        <?php $st = (string)($inv['status'] ?? 'Draft'); $badge = $st==='Paid'?'badge badge-paid':($st==='Sent'?'badge badge-sent':'badge badge-draft'); ?>
        <div class="<?= $badge ?>" style="margin-top:6px; display:inline-block;"><?= htmlspecialchars($st) ?></div>
      </div>
      <div style="text-align:right">
        <div><strong>Dari</strong></div>
        <div><?= nl2br(htmlspecialchars($inv['from_name'] ?? '')) ?></div>
        <?php if (!empty($inv['from_email'])): ?><div class="muted">Email: <?= htmlspecialchars($inv['from_email']) ?></div><?php endif; ?>
        <?php if (!empty($inv['from_phone'])): ?><div class="muted">Telp: <?= htmlspecialchars($inv['from_phone']) ?></div><?php endif; ?>
        <?php if (!empty($inv['from_address'])): ?><div class="muted">Alamat: <?= nl2br(htmlspecialchars($inv['from_address'])) ?></div><?php endif; ?>
      </div>
    </div>

    <div class="row" style="margin-top:16px">
      <div class="box" style="width:100%">
        <div><strong>Tagihan Kepada</strong></div>
        <div><?= nl2br(htmlspecialchars($inv['bill_name'] ?? '')) ?></div>
        <?php if (!empty($inv['bill_email'])): ?><div class="muted">Email: <?= htmlspecialchars($inv['bill_email']) ?></div><?php endif; ?>
        <?php if (!empty($inv['bill_phone'])): ?><div class="muted">Telp: <?= htmlspecialchars($inv['bill_phone']) ?></div><?php endif; ?>
        <?php if (!empty($inv['bill_address'])): ?><div class="muted">Alamat: <?= nl2br(htmlspecialchars($inv['bill_address'])) ?></div><?php endif; ?>
      </div>
    </div>

    <div style="margin-top:16px">
      <table>
        <thead>
          <tr>
            <th>Produk/Deskripsi</th>
            <th>Qty</th>
            <th>Harga</th>
            <th class="right">Jumlah</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $it): ?>
            <tr>
              <td><?= htmlspecialchars($it['description']) ?></td>
              <td><?= (int)$it['qty'] ?></td>
              <td><?= format_price((int)$it['price']) ?></td>
              <td class="right"><strong><?= format_price((int)$it['line_total']) ?></strong></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="totals" style="margin-top:16px">
      <table>
        <tr><td>Subtotal</td><td class="right"><?= format_price((int)$inv['subtotal']) ?></td></tr>
        <tr><td>Pajak (<?= htmlspecialchars((string)$inv['tax_percent']) ?>%)</td><td class="right"><?= format_price((int)$inv['tax_amount']) ?></td></tr>
        <?php if ((int)$inv['shipping_amount'] > 0): ?><tr><td>Ongkir</td><td class="right"><?= format_price((int)$inv['shipping_amount']) ?></td></tr><?php endif; ?>
        <?php if ((int)$inv['discount_amount'] > 0): ?><tr><td>Diskon</td><td class="right">-<?= format_price((int)$inv['discount_amount']) ?></td></tr><?php endif; ?>
        <tr><th>Total</th><th class="right"><?= format_price((int)$inv['grand_total']) ?></th></tr>
      </table>
    </div>

    <?php if (!empty($inv['notes'])): ?>
      <div style="margin-top:20px">
        <div class="muted">Catatan:</div>
        <div><?= nl2br(htmlspecialchars($inv['notes'])) ?></div>
      </div>
    <?php endif; ?>
    <?php $payNotes = get_setting('invoice_payment_notes',''); if (!empty($payNotes)): ?>
      <div style="margin-top:16px">
        <div class="muted">Catatan Pembayaran:</div>
        <div><?= nl2br(htmlspecialchars($payNotes)) ?></div>
      </div>
    <?php endif; ?>

    <div class="muted" style="margin-top:24px">Dibuat: <?= htmlspecialchars($inv['created_at']) ?></div>
  </div>
</body>
</html>
<?php
$html = ob_get_clean();

// Try to use Dompdf if available
$dompdfReady = false;
try {
  // Common autoload locations
  $candidates = [
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../vendor/autoload.php',
  ];
  foreach ($candidates as $auto) {
    if (is_file($auto)) { require_once $auto; break; }
  }
  if (class_exists('Dompdf\\Dompdf')) {
    $dompdfReady = true;
  }
} catch (Throwable $e) { $dompdfReady = false; }

if ($dompdfReady) {
  try {
    $dompdf = new Dompdf\Dompdf([ 'isRemoteEnabled' => true ]);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $filename = 'invoice-' . preg_replace('/[^a-zA-Z0-9_-]+/','-', $inv['invoice_number']) . '.pdf';
    $dompdf->stream($filename, ['Attachment' => true]);
    exit;
  } catch (Throwable $e) {
    // Fallback to HTML if PDF generation fails
  }
}

// Fallback: show HTML with instruction
header('Content-Type: text/html; charset=utf-8');
echo $html;
echo "\n<!-- Catatan: Dompdf tidak ditemukan. Instal via Composer: composer require dompdf/dompdf -->";
