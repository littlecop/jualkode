<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/util.php';

// SEO
http_response_code(404);
$pageTitle = 'Halaman tidak ditemukan - 404';
$metaDescription = 'Maaf, halaman yang Anda cari tidak ditemukan.';
$extraHead = '<meta name="robots" content="noindex, nofollow">';
include __DIR__ . '/includes/header.php';
?>

<section class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white glass neon-border p-8 text-center">
  <div class="relative z-10">
    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-gradient-to-r from-primary/10 to-neon/10 border border-primary/20 text-primary text-xs">Error 404</div>
    <h1 class="mt-3 text-3xl md:text-4xl font-semibold">Halaman tidak ditemukan</h1>
    <p class="mt-3 text-slate-600 max-w-2xl mx-auto">
      Maaf, halaman yang Anda tuju tidak tersedia atau telah dipindahkan.
      Pastikan URL sudah benar atau kembali ke beranda.
    </p>
    <div class="mt-6 flex flex-wrap gap-3 justify-center">
      <a href="<?= htmlspecialchars(base_url('')) ?>" class="px-5 py-3 rounded-xl bg-primary text-white font-medium hover:opacity-90 ripple">Kembali ke Beranda</a>
      <a href="<?= htmlspecialchars(base_url('services')) ?>" class="px-5 py-3 rounded-xl border font-medium hover:bg-slate-50">Lihat Jasa & Sewa</a>
    </div>
  </div>
  <div class="absolute -right-16 -top-16 h-56 w-56 rounded-full bg-gradient-to-tr from-primary to-neon opacity-20 blur-3xl"></div>
</section>

<section class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4">
  <div class="rounded-xl border border-slate-200 bg-white p-4">
    <div class="text-sm font-semibold">Kemungkinan penyebab</div>
    <ul class="mt-2 text-sm text-slate-600 space-y-1 list-disc list-inside">
      <li>URL salah ketik atau sudah berubah</li>
      <li>Konten telah dihapus atau dipindahkan</li>
      <li>Masalah sementara pada server</li>
    </ul>
  </div>
  <div class="rounded-xl border border-slate-200 bg-white p-4">
    <div class="text-sm font-semibold">Yang bisa Anda lakukan</div>
    <ul class="mt-2 text-sm text-slate-600 space-y-1 list-disc list-inside">
      <li>Kembali ke beranda untuk menelusuri produk</li>
      <li>Kunjungi halaman Jasa & Sewa untuk layanan kami</li>
      <li>Hubungi kami jika membutuhkan bantuan</li>
    </ul>
  </div>
  <div class="rounded-xl border border-slate-200 bg-white p-4">
    <div class="text-sm font-semibold">Butuh bantuan cepat?</div>
    <?php $wa = get_setting('wa_number', '081227841755'); $waLink = 'https://wa.me/' . rawurlencode(wa_normalize_number($wa)) . '?text=' . rawurlencode('Halo, saya menemukan error 404 dan butuh bantuan.'); ?>
    <a href="<?= htmlspecialchars($waLink) ?>" target="_blank" rel="noopener" class="mt-3 inline-block px-4 py-2 rounded-lg border font-medium hover:bg-slate-50">Chat WhatsApp</a>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
