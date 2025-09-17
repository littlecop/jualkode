<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
require_once __DIR__ . '/../../includes/util.php';
ensure_invoices_tables();

// Helper: sanitize numeric string to int (rupiah in smallest unit)
function rupiah_to_int($s): int {
    $s = (string)$s;
    // Remove anything except digits and dots/commas
    $s = preg_replace('/[^0-9.,-]/', '', $s) ?? '';
    // Normalize comma to dot for decimal, but we use integers (no cents), so strip non-digits
    $s = preg_replace('/[^0-9-]/', '', $s) ?? '0';
    if ($s === '' || $s === '-' ) return 0;
    return (int)$s;
}

$editingId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$mode = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['action'] ?? 'preview') : 'form';
$pageTitle = ($mode === 'preview') ? 'Preview Invoice' : ($editingId ? 'Edit Invoice' : 'Generate Invoice');

// Helper to insert/update invoice
function save_invoice(array $data, array $items, int $id = 0): int {
    global $pdo;
    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE invoices SET invoice_number=?, invoice_date=?, due_date=?, from_name=?, from_email=?, from_phone=?, from_address=?, bill_name=?, bill_email=?, bill_phone=?, bill_address=?, notes=?, status=?, tax_percent=?, discount_amount=?, shipping_amount=?, subtotal=?, tax_amount=?, grand_total=? WHERE id=?');
        $stmt->execute([
            $data['invoice_number'],$data['invoice_date'],$data['due_date'],
            $data['from_name'],$data['from_email'],$data['from_phone'],$data['from_address'],
            $data['bill_name'],$data['bill_email'],$data['bill_phone'],$data['bill_address'],
            $data['notes'],$data['status'],$data['tax_percent'],$data['discount_amount'],$data['shipping_amount'],
            $data['subtotal'],$data['tax_amount'],$data['grand_total'], $id
        ]);
        // replace items
        $pdo->prepare('DELETE FROM invoice_items WHERE invoice_id=?')->execute([$id]);
        $ins = $pdo->prepare('INSERT INTO invoice_items (invoice_id, description, qty, price, line_total) VALUES (?,?,?,?,?)');
        foreach ($items as $it) { $ins->execute([$id,$it['desc'],$it['qty'],$it['price'],$it['total']]); }
        return $id;
    } else {
        $stmt = $pdo->prepare('INSERT INTO invoices (invoice_number, invoice_date, due_date, from_name, from_email, from_phone, from_address, bill_name, bill_email, bill_phone, bill_address, notes, status, tax_percent, discount_amount, shipping_amount, subtotal, tax_amount, grand_total) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([
            $data['invoice_number'],$data['invoice_date'],$data['due_date'],
            $data['from_name'],$data['from_email'],$data['from_phone'],$data['from_address'],
            $data['bill_name'],$data['bill_email'],$data['bill_phone'],$data['bill_address'],
            $data['notes'],$data['status'],$data['tax_percent'],$data['discount_amount'],$data['shipping_amount'],
            $data['subtotal'],$data['tax_amount'],$data['grand_total']
        ]);
        $newId = (int)$pdo->lastInsertId();
        $ins = $pdo->prepare('INSERT INTO invoice_items (invoice_id, description, qty, price, line_total) VALUES (?,?,?,?,?)');
        foreach ($items as $it) { $ins->execute([$newId,$it['desc'],$it['qty'],$it['price'],$it['total']]); }
        return $newId;
    }
}

if ($mode === 'preview' || $mode === 'save' || $mode === 'save_preview') {
    // Collect form inputs
    $invNo   = trim($_POST['invoice_number'] ?? '');
    if ($invNo === '') { $invNo = 'INV-' . date('Ymd-His'); }
    $invDate = trim($_POST['invoice_date'] ?? date('Y-m-d'));
    $dueDate = trim($_POST['due_date'] ?? '');

    $fromName = trim($_POST['from_name'] ?? get_setting('site_name', 'Store Code Market'));
    $fromEmail = trim($_POST['from_email'] ?? '');
    $fromPhone = trim($_POST['from_phone'] ?? '');
    $fromAddr  = trim($_POST['from_address'] ?? '');

    $billName = trim($_POST['bill_name'] ?? '');
    $billEmail = trim($_POST['bill_email'] ?? '');
    $billPhone = trim($_POST['bill_phone'] ?? '');
    $billAddr  = trim($_POST['bill_address'] ?? '');

    $notes = trim($_POST['notes'] ?? '');
    $status = $_POST['status'] ?? 'Draft';
    if (!in_array($status, ['Draft','Sent','Paid'], true)) { $status = 'Draft'; }

    $descArr = $_POST['item_desc'] ?? [];
    $qtyArr  = $_POST['item_qty'] ?? [];
    $priceArr= $_POST['item_price'] ?? [];

    $items = [];
    $subtotal = 0;
    for ($i = 0; $i < count($descArr); $i++) {
        $d = trim((string)($descArr[$i] ?? ''));
        if ($d === '') continue;
        $q = max(1, (int)($qtyArr[$i] ?? 1));
        $p = rupiah_to_int($priceArr[$i] ?? 0);
        $line = $q * $p;
        $subtotal += $line;
        $items[] = [
            'desc' => $d,
            'qty' => $q,
            'price' => $p,
            'total' => $line,
        ];
    }

    $taxPercent = (float)($_POST['tax_percent'] ?? 0);
    if ($taxPercent < 0) $taxPercent = 0; if ($taxPercent > 100) $taxPercent = 100;
    $discount = rupiah_to_int($_POST['discount_amount'] ?? 0);
    $shipping = rupiah_to_int($_POST['shipping_amount'] ?? 0);

    $taxAmount = (int)round($subtotal * ($taxPercent / 100));
    $grandTotal = max(0, $subtotal + $taxAmount + $shipping - $discount);
    // If saving requested, persist then redirect
    if ($mode === 'save' || $mode === 'save_preview') {
        $data = [
            'invoice_number'=>$invNo,
            'invoice_date'=>$invDate,
            'due_date'=>$dueDate ?: null,
            'from_name'=>$fromName,
            'from_email'=>$fromEmail,
            'from_phone'=>$fromPhone,
            'from_address'=>$fromAddr,
            'bill_name'=>$billName,
            'bill_email'=>$billEmail,
            'bill_phone'=>$billPhone,
            'bill_address'=>$billAddr,
            'notes'=>$notes,
            'status'=>$status,
            'tax_percent'=>$taxPercent,
            'discount_amount'=>$discount,
            'shipping_amount'=>$shipping,
            'subtotal'=>$subtotal,
            'tax_amount'=>$taxAmount,
            'grand_total'=>$grandTotal,
        ];
        $postId = isset($_POST['invoice_id']) ? (int)$_POST['invoice_id'] : 0;
        $savedId = save_invoice($data, $items, $postId);
        if ($mode === 'save_preview') {
            header('Location: ' . base_url('admin/invoices/view.php?id=' . $savedId));
            exit;
        } else {
            flash_set('ok', 'Invoice berhasil disimpan.');
            header('Location: ' . base_url('admin/invoices/index.php'));
            exit;
        }
    }
    // Include header only when rendering preview (not during redirects)
    include __DIR__ . '/../../includes/header.php';
    // Template settings
    $invoiceLogo = get_setting('site_logo', '');
    $invoiceWatermark = get_setting('invoice_watermark_text', '');
    $invoicePaymentNotes = get_setting('invoice_payment_notes', '');
    ?>
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-semibold">Preview Invoice</h1>
      <div class="flex gap-2">
        <a href="<?= htmlspecialchars(base_url('admin/invoices/generate.php')) ?>" class="px-4 py-2 rounded-lg border hover:bg-slate-50">Kembali</a>
        <button onclick="window.print()" class="px-4 py-2 rounded-lg bg-primary text-white hover:opacity-90">Cetak / Simpan PDF</button>
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
          <div class="text-slate-600 mt-1">No: <?= htmlspecialchars($invNo) ?></div>
          <div class="text-slate-600">Tanggal: <?= htmlspecialchars($invDate) ?></div>
          <?php if ($dueDate): ?><div class="text-slate-600">Jatuh Tempo: <?= htmlspecialchars($dueDate) ?></div><?php endif; ?>
          <div class="mt-2 inline-flex items-center px-2 py-1 text-xs rounded-full border <?= $status==='Paid'?'border-emerald-300 text-emerald-700 bg-emerald-50':($status==='Sent'?'border-amber-300 text-amber-700 bg-amber-50':'border-slate-300 text-slate-700 bg-slate-50') ?>"><?= htmlspecialchars($status) ?></div>
        </div>
        <div class="text-right">
          <div class="font-semibold">Dari</div>
          <div><?= nl2br(htmlspecialchars($fromName)) ?></div>
          <?php if ($fromEmail): ?><div class="text-slate-600 text-sm">Email: <?= htmlspecialchars($fromEmail) ?></div><?php endif; ?>
          <?php if ($fromPhone): ?><div class="text-slate-600 text-sm">Telp: <?= htmlspecialchars($fromPhone) ?></div><?php endif; ?>
          <?php if ($fromAddr): ?><div class="text-slate-600 text-sm">Alamat: <?= nl2br(htmlspecialchars($fromAddr)) ?></div><?php endif; ?>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
        <div class="border rounded-xl p-4">
          <div class="font-semibold mb-2">Tagihan Kepada</div>
          <div class="text-slate-700"><?= nl2br(htmlspecialchars($billName)) ?></div>
          <?php if ($billEmail): ?><div class="text-slate-600 text-sm">Email: <?= htmlspecialchars($billEmail) ?></div><?php endif; ?>
          <?php if ($billPhone): ?><div class="text-slate-600 text-sm">Telp: <?= htmlspecialchars($billPhone) ?></div><?php endif; ?>
          <?php if ($billAddr): ?><div class="text-slate-600 text-sm">Alamat: <?= nl2br(htmlspecialchars($billAddr)) ?></div><?php endif; ?>
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
                  <div class="font-medium"><?= htmlspecialchars($it['desc']) ?></div>
                </td>
                <td class="px-4 py-3"><?= (int)$it['qty'] ?></td>
                <td class="px-4 py-3"><?= format_price((int)$it['price']) ?></td>
                <td class="px-4 py-3 font-semibold text-right"><?= format_price((int)$it['total']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <?php if ($notes): ?>
            <div class="text-sm text-slate-600">Catatan:</div>
            <div class="mt-1 whitespace-pre-wrap"><?= nl2br(htmlspecialchars($notes)) ?></div>
          <?php endif; ?>
          <?php if ($invoicePaymentNotes): ?>
            <div class="text-sm text-slate-600 mt-4">Catatan Pembayaran:</div>
            <div class="mt-1 whitespace-pre-wrap"><?= nl2br(htmlspecialchars($invoicePaymentNotes)) ?></div>
          <?php endif; ?>
        </div>
        <div>
          <div class="bg-slate-50 rounded-xl p-4">
            <div class="flex items-center justify-between py-1">
              <div>Subtotal</div>
              <div class="font-medium"><?= format_price((int)$subtotal) ?></div>
            </div>
            <div class="flex items-center justify-between py-1">
              <div>Pajak (<?= htmlspecialchars((string)$taxPercent) ?>%)</div>
              <div class="font-medium"><?= format_price((int)$taxAmount) ?></div>
            </div>
            <?php if ($shipping > 0): ?>
            <div class="flex items-center justify-between py-1">
              <div>Ongkir</div>
              <div class="font-medium"><?= format_price((int)$shipping) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($discount > 0): ?>
            <div class="flex items-center justify-between py-1">
              <div>Diskon</div>
              <div class="font-medium text-emerald-600">-<?= format_price((int)$discount) ?></div>
            </div>
            <?php endif; ?>
            <div class="h-px my-2 bg-slate-200"></div>
            <div class="flex items-center justify-between py-1 text-lg">
              <div class="font-semibold">Total</div>
              <div class="font-bold text-primary"><?= format_price((int)$grandTotal) ?></div>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-8 text-sm text-slate-500">
        Invoice dibuat pada <?= htmlspecialchars(date('Y-m-d H:i')) ?>. Terima kasih telah bertransaksi.
      </div>
    </div>
    <?php
} else {
    // Form mode
    $today = date('Y-m-d');
    $defaultInv = 'INV-' . date('Ymd-His');
    $siteName = get_setting('site_name', 'Store Code Market');
    $prefill = [
      'invoice_number' => $defaultInv,
      'invoice_date' => $today,
      'due_date' => '',
      'from_name' => $siteName,
      'from_email' => '',
      'from_phone' => '',
      'from_address' => '',
      'bill_name' => '',
      'bill_email' => '',
      'bill_phone' => '',
      'bill_address' => '',
      'notes' => '',
      'status' => 'Draft',
      'tax_percent' => '0',
      'discount_amount' => '0',
      'shipping_amount' => '0',
    ];
    $prefillItems = [ [ 'desc'=>'', 'qty'=>1, 'price'=>0 ] ];
    if ($editingId) {
        // load invoice and items
        $stmt = $pdo->prepare('SELECT * FROM invoices WHERE id=?');
        $stmt->execute([$editingId]);
        if ($inv = $stmt->fetch()) {
            $prefill = array_merge($prefill, [
              'invoice_number'=>$inv['invoice_number'],
              'invoice_date'=>$inv['invoice_date'],
              'due_date'=>$inv['due_date'] ?? '',
              'from_name'=>$inv['from_name'] ?? '',
              'from_email'=>$inv['from_email'] ?? '',
              'from_phone'=>$inv['from_phone'] ?? '',
              'from_address'=>$inv['from_address'] ?? '',
              'bill_name'=>$inv['bill_name'] ?? '',
              'bill_email'=>$inv['bill_email'] ?? '',
              'bill_phone'=>$inv['bill_phone'] ?? '',
              'bill_address'=>$inv['bill_address'] ?? '',
              'notes'=>$inv['notes'] ?? '',
              'status'=>$inv['status'] ?? 'Draft',
              'tax_percent'=>$inv['tax_percent'],
              'discount_amount'=>$inv['discount_amount'],
              'shipping_amount'=>$inv['shipping_amount'],
            ]);
            $its = $pdo->prepare('SELECT description, qty, price FROM invoice_items WHERE invoice_id=? ORDER BY id');
            $its->execute([$editingId]);
            $rows = $its->fetchAll();
            if ($rows) {
                $prefillItems = [];
                foreach ($rows as $r) { $prefillItems[] = ['desc'=>$r['description'],'qty'=>(int)$r['qty'],'price'=>(int)$r['price']]; }
            }
        } else {
            $editingId = 0; // not found, fallback to new
        }
    }
    include __DIR__ . '/../../includes/header.php';
    ?>
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-semibold"><?= $editingId ? 'Edit Invoice' : 'Generate Invoice' ?></h1>
      <div></div>
    </div>

    <form method="post" class="space-y-8">
      <?php if ($editingId): ?><input type="hidden" name="invoice_id" value="<?= (int)$editingId ?>"><?php endif; ?>
      <div class="rounded-xl border p-4 bg-white">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm text-slate-600 mb-1">No. Invoice</label>
            <input name="invoice_number" value="<?= htmlspecialchars($prefill['invoice_number']) ?>" class="w-full px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-primary">
          </div>
          <div>
            <label class="block text-sm text-slate-600 mb-1">Tanggal</label>
            <input type="date" name="invoice_date" value="<?= htmlspecialchars($prefill['invoice_date']) ?>" class="w-full px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-primary">
          </div>
          <div>
            <label class="block text-sm text-slate-600 mb-1">Jatuh Tempo</label>
            <input type="date" name="due_date" value="<?= htmlspecialchars($prefill['due_date']) ?>" class="w-full px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-primary">
          </div>
          <div>
            <label class="block text-sm text-slate-600 mb-1">Status</label>
            <select name="status" class="w-full px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-primary">
              <option value="Draft" <?= ($prefill['status'] ?? 'Draft')==='Draft'?'selected':'' ?>>Draft</option>
              <option value="Sent" <?= ($prefill['status'] ?? 'Draft')==='Sent'?'selected':'' ?>>Sent</option>
              <option value="Paid" <?= ($prefill['status'] ?? 'Draft')==='Paid'?'selected':'' ?>>Paid</option>
            </select>
          </div>
        </div>
      </div>

      <div class="rounded-xl border p-4 bg-white">
        <div class="text-lg font-semibold mb-3">Dari</div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm text-slate-600 mb-1">Nama / Perusahaan</label>
            <input name="from_name" value="<?= htmlspecialchars($prefill['from_name']) ?>" class="w-full px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-primary">
          </div>
          <div>
            <label class="block text-sm text-slate-600 mb-1">Email</label>
            <input name="from_email" value="<?= htmlspecialchars($prefill['from_email']) ?>" class="w-full px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-primary">
          </div>
          <div>
            <label class="block text-sm text-slate-600 mb-1">Telepon</label>
            <input name="from_phone" value="<?= htmlspecialchars($prefill['from_phone']) ?>" class="w-full px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-primary">
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm text-slate-600 mb-1">Alamat</label>
            <textarea name="from_address" rows="2" class="w-full px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-primary"><?= htmlspecialchars($prefill['from_address']) ?></textarea>
          </div>
        </div>
      </div>

      <div class="rounded-xl border p-4 bg-white">
        <div class="text-lg font-semibold mb-3">Tagihan Kepada</div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm text-slate-600 mb-1">Nama</label>
            <input name="bill_name" required value="<?= htmlspecialchars($prefill['bill_name']) ?>" class="w-full px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-primary">
          </div>
          <div>
            <label class="block text-sm text-slate-600 mb-1">Email</label>
            <input name="bill_email" type="email" value="<?= htmlspecialchars($prefill['bill_email']) ?>" class="w-full px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-primary">
          </div>
          <div>
            <label class="block text-sm text-slate-600 mb-1">Telepon</label>
            <input name="bill_phone" value="<?= htmlspecialchars($prefill['bill_phone']) ?>" class="w-full px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-primary">
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm text-slate-600 mb-1">Alamat</label>
            <textarea name="bill_address" rows="2" class="w-full px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-primary"><?= htmlspecialchars($prefill['bill_address']) ?></textarea>
          </div>
        </div>
      </div>

      <div class="rounded-xl border p-4 bg-white">
        <div class="flex items-center justify-between mb-3">
          <div class="text-lg font-semibold">Item</div>
          <button type="button" id="addItemBtn" class="px-3 py-2 rounded-lg border hover:bg-slate-50">Tambah Item</button>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full" id="itemsTable">
            <thead class="bg-slate-50 text-left text-sm text-slate-600">
              <tr>
                <th class="px-3 py-2">Produk/Deskripsi</th>
                <th class="px-3 py-2">Qty</th>
                <th class="px-3 py-2">Harga</th>
                <th class="px-3 py-2">Jumlah</th>
                <th class="px-3 py-2"></th>
              </tr>
            </thead>
            <tbody class="divide-y" id="itemsBody">
              <?php foreach ($prefillItems as $it): ?>
              <tr>
                <td class="px-3 py-2"><input name="item_desc[]" value="<?= htmlspecialchars($it['desc']) ?>" placeholder="Nama produk / deskripsi" class="w-full px-3 py-2 rounded-lg border"></td>
                <td class="px-3 py-2 w-28"><input name="item_qty[]" type="number" value="<?= (int)$it['qty'] ?>" min="1" class="w-full px-3 py-2 rounded-lg border qty-input"></td>
                <td class="px-3 py-2 w-40"><input name="item_price[]" value="<?= (int)$it['price'] ?>" placeholder="100000" class="w-full px-3 py-2 rounded-lg border price-input"></td>
                <td class="px-3 py-2 text-right align-middle"><span class="line-total">Rp0</span></td>
                <td class="px-3 py-2 text-right"><button type="button" class="removeBtn text-rose-600">Hapus</button></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
          <div>
            <label class="block text-sm text-slate-600 mb-1">Catatan</label>
            <textarea name="notes" rows="3" class="w-full px-3 py-2 rounded-lg border"><?= htmlspecialchars($prefill['notes']) ?></textarea>
          </div>
          <div>
            <div class="bg-slate-50 rounded-xl p-4">
              <div class="flex items-center justify-between py-1">
                <div>Subtotal</div>
                <div id="subtotalView" class="font-medium">Rp0</div>
              </div>
              <div class="flex items-center justify-between py-1 gap-2">
                <label class="flex items-center gap-2">Pajak (%)<input name="tax_percent" type="number" value="<?= htmlspecialchars((string)$prefill['tax_percent']) ?>" min="0" max="100" step="0.01" class="w-24 px-3 py-2 rounded-lg border ml-2"></label>
                <div id="taxView" class="font-medium">Rp0</div>
              </div>
              <div class="flex items-center justify-between py-1 gap-2">
                <label class="flex items-center gap-2">Ongkir<input name="shipping_amount" value="<?= htmlspecialchars((string)$prefill['shipping_amount']) ?>" placeholder="0" class="w-32 px-3 py-2 rounded-lg border ml-2 amount-input"></label>
                <div></div>
              </div>
              <div class="flex items-center justify-between py-1 gap-2">
                <label class="flex items-center gap-2">Diskon<input name="discount_amount" value="<?= htmlspecialchars((string)$prefill['discount_amount']) ?>" placeholder="0" class="w-32 px-3 py-2 rounded-lg border ml-2 amount-input"></label>
                <div></div>
              </div>
              <div class="h-px my-2 bg-slate-200"></div>
              <div class="flex items-center justify-between py-1 text-lg">
                <div class="font-semibold">Total</div>
                <div id="grandView" class="font-bold text-primary">Rp0</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="flex items-center justify-end gap-2">
        <button name="action" value="save" class="px-5 py-3 rounded-lg border hover:bg-slate-50">Simpan</button>
        <button name="action" value="save_preview" class="px-5 py-3 rounded-lg border hover:bg-slate-50">Simpan & Preview</button>
        <button name="action" value="preview" class="px-5 py-3 rounded-lg bg-primary text-white font-medium hover:opacity-90">Preview Tanpa Simpan</button>
      </div>
    </form>

    <script>
      (function(){
        const itemsBody = document.getElementById('itemsBody');
        const addBtn = document.getElementById('addItemBtn');
        const currency = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });

        function parseAmount(v){
          if (!v) return 0;
          v = String(v).replace(/[^0-9-]/g,'');
          if (v === '' || v === '-') return 0;
          return parseInt(v, 10) || 0;
        }

        function recalc(){
          let subtotal = 0;
          itemsBody.querySelectorAll('tr').forEach(row => {
            const qty = parseInt(row.querySelector('.qty-input')?.value || '1', 10) || 1;
            const price = parseAmount(row.querySelector('.price-input')?.value || '0');
            const line = qty * price;
            subtotal += line;
            const lineEl = row.querySelector('.line-total');
            if (lineEl) lineEl.textContent = currency.format(line);
          });
          document.getElementById('subtotalView').textContent = currency.format(subtotal);
          const taxPct = parseFloat(document.querySelector('input[name="tax_percent"]').value || '0') || 0;
          const tax = Math.round(subtotal * (taxPct/100));
          document.getElementById('taxView').textContent = currency.format(tax);
          const shipping = parseAmount(document.querySelector('input[name="shipping_amount"]').value || '0');
          const discount = parseAmount(document.querySelector('input[name="discount_amount"]').value || '0');
          const grand = Math.max(0, subtotal + tax + shipping - discount);
          document.getElementById('grandView').textContent = currency.format(grand);
        }

        function bindRow(row){
          row.querySelectorAll('input').forEach(inp => inp.addEventListener('input', recalc));
          const rm = row.querySelector('.removeBtn');
          if (rm) rm.addEventListener('click', () => { row.remove(); recalc(); });
        }

        addBtn.addEventListener('click', () => {
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td class="px-3 py-2"><input name="item_desc[]" placeholder="Nama produk / deskripsi" class="w-full px-3 py-2 rounded-lg border"></td>
            <td class="px-3 py-2 w-28"><input name="item_qty[]" type="number" value="1" min="1" class="w-full px-3 py-2 rounded-lg border qty-input"></td>
            <td class="px-3 py-2 w-40"><input name="item_price[]" placeholder="100000" class="w-full px-3 py-2 rounded-lg border price-input"></td>
            <td class="px-3 py-2 text-right align-middle"><span class="line-total">Rp0</span></td>
            <td class="px-3 py-2 text-right"><button type="button" class="removeBtn text-rose-600">Hapus</button></td>
          `;
          itemsBody.appendChild(tr);
          bindRow(tr);
          recalc();
        });

        itemsBody.querySelectorAll('tr').forEach(bindRow);
        document.querySelectorAll('input[name="tax_percent"], .amount-input').forEach(inp => inp.addEventListener('input', recalc));
        recalc();
      })();
    </script>
    <?php
}
include __DIR__ . '/../../includes/footer.php';
