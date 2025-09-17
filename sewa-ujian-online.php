<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/util.php';

// SEO
$pageTitle = 'Sewa Aplikasi Ujian Online (CBT) — Bank Soal, Random Soal, Timer, Analytics';
$metaDescription = 'Sewa aplikasi ujian online (CBT) siap pakai: bank soal, random soal & opsi, timer, anti-cheat dasar, dashboard & analytics, leaderboard, export CSV, mobile friendly. Cocok untuk sekolah/kampus/pelatihan dengan 300+ pengguna concurrent.';
$canonical = base_url('sewa-ujian-online.php');

// WhatsApp CTA
$waNumber = get_setting('wa_number', '081227841755');
$waPhone  = wa_normalize_number($waNumber);
$ctaText  = "Halo, saya ingin sewa Aplikasi Ujian Online (CBT). Mohon info paket & demo.";
$waCta    = 'https://wa.me/' . rawurlencode($waPhone) . '?text=' . rawurlencode($ctaText);

// JSON-LD: Service + FAQ
$serviceLd = [
  '@context' => 'https://schema.org',
  '@type' => 'Service',
  'name' => 'Sewa Aplikasi Ujian Online (CBT)',
  'serviceType' => 'Software as a Service',
  'areaServed' => 'ID',
  'description' => 'Sewa aplikasi ujian online (CBT) untuk sekolah, kampus, dan lembaga pelatihan. Termasuk setup awal, panduan, dan support.',
  'provider' => [
    '@type' => 'Organization',
    'name' => get_setting('site_name', 'Store Code Market'),
    'url'  => rtrim(base_url(''), '/') . '/',
  ],
];
$faqLd = [
  '@context' => 'https://schema.org',
  '@type' => 'FAQPage',
  'mainEntity' => [
    [
      '@type' => 'Question',
      'name' => 'Apakah bisa demo aplikasi?',
      'acceptedAnswer' => [
        '@type' => 'Answer',
        'text' => 'Bisa. Hubungi kami via WhatsApp untuk mendapatkan akses demo dan penjelasan fitur.'
      ]
    ],
    [
      '@type' => 'Question',
      'name' => 'Apakah mendukung bank soal dan randomisasi?',
      'acceptedAnswer' => [
        '@type' => 'Answer',
        'text' => 'Ya. Mendukung bank soal per mata pelajaran, random urutan soal, dan dukungan gambar/file media.'
      ]
    ],
    [
      '@type' => 'Question',
      'name' => 'Apakah ada laporan dan export nilai?',
      'acceptedAnswer' => [
        '@type' => 'Answer',
        'text' => 'Ada. Tersedia rekap nilai per peserta/kelas, unduh CSV/XLSX (opsional), dan arsip hasil.'
      ]
    ],
    [
      '@type' => 'Question',
      'name' => 'Bagaimana skema biaya?',
      'acceptedAnswer' => [
        '@type' => 'Answer',
        'text' => 'Tersedia paket bulanan dan tahunan. Kami bantu rekomendasikan paket sesuai kebutuhan jumlah peserta dan fitur tambahan.'
      ]
    ]
  ]
];
$extraHead = '<script type="application/ld+json">' . json_encode($serviceLd, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . '</script>'
           . '<script type="application/ld+json">' . json_encode($faqLd, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . '</script>';

include __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white glass neon-border p-8">
  <div class="relative z-10 grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
    <div>
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-gradient-to-r from-primary/10 to-neon/10 border border-primary/20 text-primary text-xs">Sewa Aplikasi</div>
      <h1 class="mt-3 text-3xl md:text-4xl font-semibold leading-tight">
        Sewa Aplikasi Ujian Online (CBT)
      </h1>
      <p class="mt-3 text-slate-600">
        Solusi <strong>aplikasi ujian online (CBT)</strong> cepat dan hemat untuk sekolah, kampus, dan lembaga pelatihan. Fitur inti: <strong>bank soal</strong>,
        <strong>random soal & opsi</strong>, <strong>timer</strong>, <strong>anti‑cheat dasar</strong>, <strong>login peserta</strong>, <strong>rekap nilai otomatis</strong>, dan <strong>dashboard analytics</strong>.
        Siap pakai, mobile friendly, dan dapat dipandu hingga live.
      </p>
      <div class="mt-5 flex flex-wrap gap-3">
        <a href="<?= htmlspecialchars($waCta) ?>" target="_blank" rel="noopener" class="px-5 py-3 rounded-xl bg-primary text-white font-medium hover:opacity-90 ripple">Minta Demo via WhatsApp</a>
        <a href="<?= htmlspecialchars(base_url('services.php')) ?>" class="px-5 py-3 rounded-xl border font-medium hover:bg-slate-50">Lihat Layanan Lain</a>
      </div>
      <div class="mt-3 text-xs text-slate-500">Setup cepat • Panduan penggunaan • Support responsif</div>
    </div>
    <div class="relative">
      <div class="aspect-video rounded-2xl bg-gradient-to-tr from-primary/15 to-neon/15 border border-slate-200 grid place-items-center overflow-hidden">
        <div class="h-40 w-40 rounded-full bg-gradient-to-tr from-primary to-neon opacity-40 blur-2xl animate-floaty"></div>
        <div class="absolute inset-0 grid place-items-center">
          <svg class="h-24 w-24 text-primary/70" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 7h18M3 12h18M3 17h18"/></svg>
        </div>
      </div>
    </div>
  </div>
  <div class="absolute -right-16 -top-16 h-56 w-56 rounded-full bg-gradient-to-tr from-primary to-neon opacity-20 blur-3xl"></div>
</section>

<!-- Feature List (ringkas) -->
<section class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-6">
  <div class="rounded-2xl border border-slate-200 bg-white p-6">
    <div class="text-sm font-semibold">Fitur Utama</div>
    <ul class="mt-3 text-sm text-slate-600 space-y-1 list-disc list-inside">
      <li><strong>Bank soal</strong> per mata pelajaran, import massal (CSV)</li>
      <li><strong>Random soal & opsi</strong>, <strong>timer</strong>, batas percobaan</li>
      <li>Jenis soal PG, esai singkat (opsional)</li>
      <li><strong>Anti‑cheat dasar</strong>: peringatan tab switch, audit aktivitas</li>
      <li><strong>Skor otomatis</strong>, rekap & export hasil</li>
      <li><strong>Mobile friendly</strong> (Android/iOS)</li>
    </ul>
  </div>
  <div class="rounded-2xl border border-slate-200 bg-white p-6">
    <div class="text-sm font-semibold">Manfaat</div>
    <ul class="mt-3 text-sm text-slate-600 space-y-1 list-disc list-inside">
      <li>Implementasi cepat, <strong>siap digunakan</strong></li>
      <li>Hemat biaya awal (model sewa)</li>
      <li><strong>Skalabel</strong> hingga 300+ pengguna concurrent</li>
      <li>Dukungan & panduan penggunaan</li>
      <li>Backup sederhana & monitoring</li>
    </ul>
  </div>
  <div class="rounded-2xl border border-slate-200 bg-white p-6">
    <div class="text-sm font-semibold">Keamanan & Akurasi</div>
    <ul class="mt-3 text-sm text-slate-600 space-y-1 list-disc list-inside">
      <li>Role admin/guru/peserta</li>
      <li><strong>Login email/username</strong>, bcrypt hashing</li>
      <li><strong>CSRF protection</strong>, rate limiting login</li>
      <li>Session policy: single/newest login</li>
      <li>Hasil tersimpan & auditable</li>
    </ul>
  </div>
</section>

<!-- Detail Fitur (SEO keywords) -->
<section class="mt-10 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
  <div class="rounded-2xl border border-slate-200 bg-white p-6">
    <div class="text-sm font-semibold">Dashboard & Analytics</div>
    <ul class="mt-3 text-slate-600 space-y-1 list-disc list-inside">
      <li>Statistik real-time: total pengguna, soal, ujian, attempts</li>
      <li>Monitoring <strong>pengguna online</strong> & aktivitas</li>
      <li>Grafik attempt 7 hari, <strong>rata‑rata nilai</strong> per mapel/kelas</li>
      <li>Analisis <strong>tingkat kesulitan soal</strong></li>
      <li><strong>Export analytics ke CSV</strong></li>
    </ul>
    <div class="text-sm font-semibold mt-6">Monitoring & Laporan</div>
    <ul class="mt-3 text-slate-600 space-y-1 list-disc list-inside">
      <li>Filter hasil per mapel/ujian, detail attempt per siswa</li>
      <li><strong>Export hasil ke CSV</strong>, hapus/retake attempt</li>
      <li><strong>Leaderboard</strong> global/per ujian</li>
    </ul>
  </div>
  <div class="rounded-2xl border border-slate-200 bg-white p-6">
    <div class="text-sm font-semibold">Pengalaman Siswa (UX)</div>
    <ul class="mt-3 text-slate-600 space-y-1 list-disc list-inside">
      <li>Daftar ujian tersedia, status & info durasi/jumlah soal</li>
      <li><strong>Timer countdown</strong>, auto‑save jawaban, navigasi soal</li>
      <li>Konfirmasi submit, proteksi dari kehilangan data</li>
      <li>Hasil & review jawaban (opsional oleh admin)</li>
      <li>UI modern <strong>Tailwind CSS</strong>, <strong>responsive</strong>, tema terang/gelap</li>
    </ul>
    <div class="text-sm font-semibold mt-6">Performa & Skalabilitas</div>
    <ul class="mt-3 text-slate-600 space-y-1 list-disc list-inside">
      <li>Query dioptimasi, indexing, pagination</li>
      <li>Randomisasi efisien, session management untuk concurrency</li>
      <li>Auto‑cleanup data temporary</li>
      <li>Kompatibel browser modern</li>
    </ul>
  </div>
  <div class="rounded-2xl border border-slate-200 bg-white p-6 md:col-span-2">
    <div class="text-sm font-semibold">Manajemen Konten & Ujian</div>
    <ul class="mt-3 text-slate-600 space-y-1 list-disc list-inside">
      <li>Mapel & kelas (CRUD, pencarian & filter)</li>
      <li><strong>Bank soal</strong> (CRUD, filter mapel, pencarian, import CSV)</li>
      <li><strong>Manajemen ujian</strong>: durasi, jadwal buka/tutup, jumlah soal acak, assign kelas/global, shuffle jawaban</li>
    </ul>
  </div>
</section>

<!-- Pricing / Packages -->
<section class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-6">
  <div class="rounded-2xl border border-slate-200 bg-white p-6">
    <div class="text-sm font-semibold">Paket Starter</div>
    <div class="mt-2 text-2xl font-semibold text-primary">Hemat</div>
    <ul class="mt-3 text-sm text-slate-600 space-y-1 list-disc list-inside">
      <li>Hingga 200 peserta aktif/bulan</li>
      <li>Bank soal & randomisasi</li>
      <li>Rekap nilai standar</li>
    </ul>
    <a href="<?= htmlspecialchars($waCta) ?>" target="_blank" class="mt-4 inline-block px-4 py-2 rounded-lg border font-medium hover:bg-slate-50">Tanya Detail</a>
  </div>
  <div class="rounded-2xl border border-slate-200 bg-white p-6">
    <div class="text-sm font-semibold">Paket Pro</div>
    <div class="mt-2 text-2xl font-semibold text-primary">Populer</div>
    <ul class="mt-3 text-sm text-slate-600 space-y-1 list-disc list-inside">
      <li>Hingga 1.000 peserta aktif/bulan</li>
      <li>Import soal CSV/Excel (opsional)</li>
      <li>Laporan lanjutan</li>
    </ul>
    <a href="<?= htmlspecialchars($waCta) ?>" target="_blank" class="mt-4 inline-block px-4 py-2 rounded-lg border font-medium hover:bg-slate-50">Tanya Detail</a>
  </div>
  <div class="rounded-2xl border border-slate-200 bg-white p-6">
    <div class="text-sm font-semibold">Paket Enterprise</div>
    <div class="mt-2 text-2xl font-semibold text-primary">Skalabel</div>
    <ul class="mt-3 text-sm text-slate-600 space-y-1 list-disc list-inside">
      <li>> 1.000 peserta aktif/bulan</li>
      <li>Kustomisasi & integrasi opsional</li>
      <li>Prioritas support</li>
    </ul>
    <a href="<?= htmlspecialchars($waCta) ?>" target="_blank" class="mt-4 inline-block px-4 py-2 rounded-lg border font-medium hover:bg-slate-50">Tanya Detail</a>
  </div>
</section>

<!-- CTA -->
<section class="mt-10 rounded-2xl border border-emerald-200 bg-emerald-50 p-6">
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div>
      <div class="text-sm font-semibold text-emerald-700">Butuh demo dan konsultasi cepat?</div>
      <div class="text-sm text-emerald-700/90">Klik tombol di samping, tim kami akan bantu rekomendasi paket terbaik.</div>
    </div>
    <a href="<?= htmlspecialchars($waCta) ?>" target="_blank" rel="noopener" class="px-5 py-3 rounded-xl bg-emerald-500 text-white font-medium hover:opacity-90 ripple">Hubungi via WhatsApp</a>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
