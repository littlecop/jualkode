<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
include __DIR__ . '/../includes/header.php';
?>
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
  <a href="<?= htmlspecialchars(base_url('admin/products/')) ?>" class="p-6 rounded-2xl border bg-white hover:shadow-glow transition">
    <div class="text-slate-500">Produk</div>
    <div class="text-3xl font-semibold mt-1">Kelola Produk</div>
  </a>
  <a href="<?= htmlspecialchars(base_url('admin/orders.php')) ?>" class="p-6 rounded-2xl border bg-white hover:shadow-glow transition">
    <div class="text-slate-500">Pesanan</div>
    <div class="text-3xl font-semibold mt-1">Lihat Pesanan</div>
  </a>
  <a href="<?= htmlspecialchars(base_url('admin/changelogs.php')) ?>" class="p-6 rounded-2xl border bg-white hover:shadow-glow transition">
    <div class="text-slate-500">Konten</div>
    <div class="text-3xl font-semibold mt-1">Changelog Produk</div>
  </a>
  <a href="<?= htmlspecialchars(base_url('admin/invoices/index.php')) ?>" class="p-6 rounded-2xl border bg-white hover:shadow-glow transition">
    <div class="text-slate-500">Keuangan</div>
    <div class="text-3xl font-semibold mt-1">Daftar Invoice</div>
  </a>
  <a href="<?= htmlspecialchars(base_url('admin/invoices/generate.php')) ?>" class="p-6 rounded-2xl border bg-white hover:shadow-glow transition">
    <div class="text-slate-500">Keuangan</div>
    <div class="text-3xl font-semibold mt-1">Generate Invoice</div>
  </a>
  <a href="<?= htmlspecialchars(base_url('admin/settings.php')) ?>" class="p-6 rounded-2xl border bg-white hover:shadow-glow transition">
    <div class="text-slate-500">Pengaturan</div>
    <div class="text-3xl font-semibold mt-1">Pengaturan Situs</div>
  </a>
  <a href="<?= htmlspecialchars(base_url('admin/logout.php')) ?>" class="p-6 rounded-2xl border bg-white hover:shadow-glow transition">
    <div class="text-slate-500">Akun</div>
    <div class="text-3xl font-semibold mt-1">Logout</div>
  </a>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
