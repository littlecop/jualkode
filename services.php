<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/util.php';

// SEO
$pageTitle = 'Jasa Pembuatan Website & Sewa Website - Modern, Cepat, Futuristik';
$metaDescription = 'Kami menerima jasa pembuatan website custom dan sewa website (termasuk aplikasi ujian online). Desain modern, performa cepat, dan dukungan profesional.';
$canonical = base_url('services.php');

// WhatsApp CTA
$waNumber = get_setting('wa_number', '081227841755');
$waPhone  = wa_normalize_number($waNumber);

$ctaTextGeneral = "Halo, saya tertarik konsultasi pembuatan/sewa website.";
$ctaTextDev     = "Halo, saya ingin konsultasi jasa pembuatan website.";
$ctaTextRental  = "Halo, saya ingin sewa website/aplikasi (mis: ujian online).";

$waCtaGeneral = 'https://wa.me/' . rawurlencode($waPhone) . '?text=' . rawurlencode($ctaTextGeneral);
$waCtaDev     = 'https://wa.me/' . rawurlencode($waPhone) . '?text=' . rawurlencode($ctaTextDev);
$waCtaRental  = 'https://wa.me/' . rawurlencode($waPhone) . '?text=' . rawurlencode($ctaTextRental);

// FAQPage JSON-LD
$faqLd = [
  '@context' => 'https://schema.org',
  '@type' => 'FAQPage',
  'mainEntity' => [
    [
      '@type' => 'Question',
      'name' => 'Apakah bisa revisi?',
      'acceptedAnswer' => [
        '@type' => 'Answer',
        'text' => 'Bisa. Kami ramah revisi untuk penyempurnaan UI/UX dan konten pada masa pengembangan.'
      ]
    ],
    [
      '@type' => 'Question',
      'name' => 'Berapa estimasi waktu?',
      'acceptedAnswer' => [
        '@type' => 'Answer',
        'text' => 'Tergantung kompleksitas. Landing page umumnya 3–7 hari kerja, aplikasi khusus bisa lebih lama.'
      ]
    ],
    [
      '@type' => 'Question',
      'name' => 'Bagaimana soal biaya?',
      'acceptedAnswer' => [
        '@type' => 'Answer',
        'text' => 'Kami fleksibel: bisa project-based atau sewa bulanan/tahunan. Sampaikan kebutuhan Anda, kami bantu rekomendasikan paket terbaik.'
      ]
    ],
    [
      '@type' => 'Question',
      'name' => 'Apakah ada maintenance?',
      'acceptedAnswer' => [
        '@type' => 'Answer',
        'text' => 'Ada. Paket maintenance tersedia meliputi update minor, backup sederhana, dan monitoring ringan.'
      ]
    ]
  ]
];
$extraHead = '<script type="application/ld+json">' . json_encode($faqLd, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . '</script>';

include __DIR__ . '/includes/header.php';
?>

<!-- Hero -->
<section class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white glass neon-border p-8">
  <div class="relative z-10 grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
    <div>
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-gradient-to-r from-primary/10 to-neon/10 border border-primary/20 text-primary text-xs">Layanan Profesional</div>
      <h1 class="mt-3 text-3xl md:text-4xl font-semibold leading-tight">
        Jasa Pembuatan Website & Sewa Website
      </h1>
      <p class="mt-3 text-slate-600">
        Bangun kehadiran digital Anda dengan desain modern, performa cepat, dan pengalaman pengguna yang memukau. Kami juga menyediakan sewa website siap pakai seperti aplikasi ujian online.
      </p>
      <div class="mt-5 flex flex-wrap gap-3">
        <a href="<?= htmlspecialchars($waCtaGeneral) ?>" target="_blank" rel="noopener" class="px-5 py-3 rounded-xl bg-primary text-white font-medium hover:opacity-90 ripple">Konsultasi Gratis via WhatsApp</a>
        <a href="<?= htmlspecialchars(base_url('index.php')) ?>" class="px-5 py-3 rounded-xl border font-medium hover:bg-slate-50">Lihat Produk</a>
      </div>
      <div class="mt-3 text-xs text-slate-500">Respon cepat • Bahasa Indonesia • Ramah revisi</div>
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

<!-- Services Cards -->
<section class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-6">
  <div class="rounded-2xl border border-slate-200 bg-white p-6 hover:shadow-glow transition">
    <div class="text-sm font-semibold">Jasa Pembuatan Website</div>
    <p class="mt-2 text-sm text-slate-600">Website custom sesuai kebutuhan bisnis: landing page, company profile, katalog, sampai aplikasi web. Desain konsisten, modern, dan mobile-friendly.</p>
    <ul class="mt-3 text-sm text-slate-600 space-y-1 list-disc list-inside">
      <li>Desain futuristik, UX rapi</li>
      <li>Performa cepat & SEO dasar</li>
      <li>Integrasi formulir, payment, dan lainnya</li>
    </ul>
    <a href="<?= htmlspecialchars($waCtaDev) ?>" target="_blank" rel="noopener" class="mt-4 inline-block px-4 py-2 rounded-lg border font-medium hover:bg-slate-50">Diskusikan Kebutuhan</a>
  </div>

  <div class="rounded-2xl border border-slate-200 bg-white p-6 hover:shadow-glow transition">
    <div class="text-sm font-semibold">Sewa Website (Termasuk Ujian Online)</div>
    <p class="mt-2 text-sm text-slate-600">Butuh cepat tanpa ribet? Sewa website siap pakai. Cocok untuk event, sekolah, atau bisnis yang butuh solusi instan.</p>
    <ul class="mt-3 text-sm text-slate-600 space-y-1 list-disc list-inside">
      <li>Contoh: Aplikasi Ujian Online</li>
      <li>Setup cepat, hemat biaya awal</li>
      <li>Termasuk maintenance & support</li>
    </ul>
    <a href="<?= htmlspecialchars($waCtaRental) ?>" target="_blank" rel="noopener" class="mt-4 inline-block px-4 py-2 rounded-lg border font-medium hover:bg-slate-50">Tanya Paket Sewa</a>
  </div>

  <div class="rounded-2xl border border-slate-200 bg-white p-6 hover:shadow-glow transition">
    <div class="text-sm font-semibold">Maintenance & Support</div>
    <p class="mt-2 text-sm text-slate-600">Kami bantu perawatan berkala, update konten, optimasi kecepatan, hingga perbaikan bug kecil.</p>
    <ul class="mt-3 text-sm text-slate-600 space-y-1 list-disc list-inside">
      <li>Monitoring & backup sederhana</li>
      <li>Perbaikan minor gratis (paket tertentu)</li>
      <li>Jaminan respon cepat</li>
    </ul>
    <a href="<?= htmlspecialchars($waCtaGeneral) ?>" target="_blank" rel="noopener" class="mt-4 inline-block px-4 py-2 rounded-lg border font-medium hover:bg-slate-50">Butuh Bantuan?</a>
  </div>
</section>

<!-- Highlights -->
<section class="mt-10 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
  <div class="rounded-xl border border-slate-200 bg-white p-4 flex items-center gap-3">
    <svg class="h-5 w-5 text-primary" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3 7h7l-5.5 4 2 7L12 16l-6.5 4 2-7L2 9h7z"/></svg>
    <div>
      <div class="text-sm font-medium">Desain Modern</div>
      <div class="text-xs text-slate-500">Tampilan konsisten, futuristik, dan mudah dinavigasi</div>
    </div>
  </div>
  <div class="rounded-xl border border-slate-200 bg-white p-4 flex items-center gap-3">
    <svg class="h-5 w-5 text-primary" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M5 12h14M5 6h14M5 18h14"/></svg>
    <div>
      <div class="text-sm font-medium">Performa Cepat</div>
      <div class="text-xs text-slate-500">Fokus pada kecepatan dan pengalaman pengguna</div>
    </div>
  </div>
  <div class="rounded-xl border border-slate-200 bg-white p-4 flex items-center gap-3">
    <svg class="h-5 w-5 text-primary" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3a9 9 0 100 18 9 9 0 000-18zm-1 5h2v5h-2V8zm0 6h2v2h-2v-2z"/></svg>
    <div>
      <div class="text-sm font-medium">Dukungan Profesional</div>
      <div class="text-xs text-slate-500">Bantuan cepat, ramah revisi, dan komunikatif</div>
    </div>
  </div>
  <div class="rounded-xl border border-slate-200 bg-white p-4 flex items-center gap-3">
    <svg class="h-5 w-5 text-primary" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 12l2-2 4 4L21 4l2 2-14 14z"/></svg>
    <div>
      <div class="text-sm font-medium">Siap Produksi</div>
      <div class="text-xs text-slate-500">Langsung dipakai, mudah dikembangkan</div>
    </div>
  </div>
</section>

<!-- Process -->
<section class="mt-10 rounded-2xl border border-slate-200 bg-white p-6">
  <div class="text-sm font-semibold">Proses Kerja</div>
  <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
    <div class="rounded-xl border p-4">
      <div class="font-medium">1. Konsultasi</div>
      <div class="text-slate-600 mt-1">Bahas kebutuhan, tujuan, dan referensi.</div>
    </div>
    <div class="rounded-xl border p-4">
      <div class="font-medium">2. Desain & Rencana</div>
      <div class="text-slate-600 mt-1">Susun konsep UI/UX dan rencana fitur.</div>
    </div>
    <div class="rounded-xl border p-4">
      <div class="font-medium">3. Pengembangan</div>
      <div class="text-slate-600 mt-1">Implementasi cepat dengan standar terbaik.</div>
    </div>
    <div class="rounded-xl border p-4">
      <div class="font-medium">4. Launch & Support</div>
      <div class="text-slate-600 mt-1">Uji kelayakan, rilis, dan dukungan berkelanjutan.</div>
    </div>
  </div>
  <div class="mt-5">
    <a href="<?= htmlspecialchars($waCtaGeneral) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-emerald-500 text-white font-medium hover:opacity-90 ripple">
      Konsultasi Sekarang via WhatsApp
    </a>
  </div>
</section>

<!-- FAQ / Notes -->
<section class="mt-10 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
  <div class="rounded-2xl border border-slate-200 bg-white p-6">
    <div class="text-sm font-semibold">Apakah bisa revisi?</div>
    <p class="mt-2 text-slate-600">Bisa. Kami ramah revisi untuk penyempurnaan UI/UX dan konten pada masa pengembangan.</p>
    <div class="mt-4 text-sm font-semibold">Berapa estimasi waktu?</div>
    <p class="mt-2 text-slate-600">Tergantung kompleksitas. Landing page umumnya 3-7 hari kerja, aplikasi khusus bisa lebih lama.</p>
  </div>
  <div class="rounded-2xl border border-slate-200 bg-white p-6">
    <div class="text-sm font-semibold">Bagaimana soal biaya?</div>
    <p class="mt-2 text-slate-600">Kami fleksibel: bisa project-based atau sewa bulanan/tahunan. Sampaikan kebutuhan Anda, kami bantu rekomendasikan paket terbaik.</p>
    <div class="mt-4 text-sm font-semibold">Apakah ada maintenance?</div>
    <p class="mt-2 text-slate-600">Ada. Paket maintenance tersedia meliputi update minor, backup sederhana, dan monitoring ringan.</p>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
