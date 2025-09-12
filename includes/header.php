<?php
// includes/header.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/util.php';
require_once __DIR__ . '/auth.php';
// Prepare SEO variables (can be set by page before including header)
$__seoTitle = isset($pageTitle) && $pageTitle !== '' ? $pageTitle : 'Store Code Market';
$__seoDesc = isset($metaDescription) && $metaDescription !== '' ? $metaDescription : 'Jual beli source code berkualitas. Tema modern, futuristik, dan ringan.';
$__seoCanonical = isset($canonical) && $canonical !== '' ? $canonical : null;
$__ogImage = isset($ogImage) && $ogImage !== '' ? $ogImage : null;
$__extraHead = isset($extraHead) && $extraHead !== '' ? $extraHead : '';
?>
<!doctype html>
<html lang="id" class="h-full bg-white">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($__seoTitle) ?></title>
  <meta name="description" content="<?= htmlspecialchars($__seoDesc) ?>">
  <?php if ($__seoCanonical): ?>
    <link rel="canonical" href="<?= htmlspecialchars($__seoCanonical) ?>"/>
  <?php endif; ?>
  <!-- Open Graph -->
  <meta property="og:site_name" content="Store Code Market"/>
  <meta property="og:type" content="website"/>
  <meta property="og:title" content="<?= htmlspecialchars($__seoTitle) ?>"/>
  <meta property="og:description" content="<?= htmlspecialchars($__seoDesc) ?>"/>
  <?php if ($__seoCanonical): ?>
    <meta property="og:url" content="<?= htmlspecialchars($__seoCanonical) ?>"/>
  <?php endif; ?>
  <?php if ($__ogImage): ?>
    <meta property="og:image" content="<?= htmlspecialchars($__ogImage) ?>"/>
  <?php endif; ?>
  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image"/>
  <meta name="twitter:title" content="<?= htmlspecialchars($__seoTitle) ?>"/>
  <meta name="twitter:description" content="<?= htmlspecialchars($__seoDesc) ?>"/>
  <?php if ($__ogImage): ?>
    <meta name="twitter:image" content="<?= htmlspecialchars($__ogImage) ?>"/>
  <?php endif; ?>
  <?= $__extraHead ?>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    // Force light theme: remove any persisted dark mode and class
    (function() {
      try {
        localStorage.removeItem('theme');
      } catch (_) {}
      try {
        document.documentElement.classList.remove('dark');
      } catch (_) {}
    })();
  </script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            primary: '#6366F1',
            neon: '#22d3ee'
          },
          boxShadow: {
            glow: '0 0 20px rgba(99,102,241,0.25)'
          },
          backdropBlur: {
            xs: '2px'
          },
          keyframes: {
            floaty: {
              '0%, 100%': { transform: 'translateY(0px)' },
              '50%': { transform: 'translateY(-6px)' }
            },
            shimmer: {
              '0%': { backgroundPosition: '0% 50%' },
              '100%': { backgroundPosition: '100% 50%' }
            }
          },
          animation: {
            floaty: 'floaty 6s ease-in-out infinite',
            shimmer: 'shimmer 3s linear infinite'
          }
        }
      }
    }
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Outfit', system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, 'Helvetica Neue', Arial; }
    /* Glassmorphism + neon helpers */
    .glass { backdrop-filter: saturate(160%) blur(16px); background: rgba(255,255,255,0.7); }
    .neon-border {
      position: relative;
    }
    .neon-border:before { /* subtle neon ring */
      content: '';
      position: absolute; inset: -1px;
      border-radius: inherit;
      background: radial-gradient(60% 60% at 50% 40%, rgba(99,102,241,0.35), rgba(34,211,238,0.2) 60%, transparent 70%);
      filter: blur(12px); opacity: .5; z-index: -1;
    }
    /* Clamp without plugin */
    .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    /* Ripple effect helper */
    .ripple { position: relative; overflow: hidden; }
    .ripple:after { content: ''; position: absolute; inset: 0; background: radial-gradient(circle, rgba(255,255,255,0.35) 10%, transparent 11%) center/10px 10px; opacity: 0; transition: opacity .4s; }
    .ripple:active:after { opacity: .6; transition: 0s; }
  </style>
</head>
<body class="h-full text-slate-800">
  <header class="sticky top-0 z-40 backdrop-blur bg-white/70 dark:bg-slate-900/70 border-b border-slate-200/70 dark:border-slate-700/70">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
      <a href="<?= htmlspecialchars(base_url('')) ?>" class="flex items-center gap-2">
        <div class="h-9 w-9 rounded-xl bg-gradient-to-tr from-primary to-neon shadow-glow"></div>
        <span class="text-xl font-semibold tracking-tight">Jual Kode</span>
      </a>
      <!-- Desktop nav -->
      <nav class="hidden md:flex items-center gap-3">
        <a class="px-3 py-2 rounded-lg hover:bg-slate-100" href="<?= htmlspecialchars(base_url('')) ?>">Beranda</a>
        <a class="px-3 py-2 rounded-lg hover:bg-slate-100" href="<?= htmlspecialchars(base_url('services')) ?>">Jasa & Sewa</a>
        <?php if (get_setting('checkout_mode', 'cart') === 'cart'): ?>
          <a class="px-3 py-2 rounded-lg hover:bg-slate-100" href="<?= htmlspecialchars(base_url('cart')) ?>">Keranjang</a>
        <?php endif; ?>
        <?php if (admin_logged_in()): ?>
          <a class="px-3 py-2 rounded-lg hover:bg-slate-100" href="<?= htmlspecialchars(base_url('admin/index.php')) ?>">Dashboard</a>
        <?php else: ?>
          <a class="px-3 py-2 rounded-lg hover:bg-slate-100" href="<?= htmlspecialchars(base_url('admin/login.php')) ?>">Admin</a>
        <?php endif; ?>
      </nav>
      <!-- Mobile hamburger -->
      <button id="mobileMenuBtn" class="md:hidden inline-flex items-center justify-center h-10 w-10 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 ripple" aria-expanded="false" aria-controls="mobileMenu" title="Menu">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
    </div>
    <!-- Mobile menu panel -->
    <div id="mobileMenu" class="md:hidden hidden border-t border-slate-200/70 bg-white/90 backdrop-blur">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 space-y-1">
        <a class="block px-3 py-2 rounded-lg hover:bg-slate-100" href="<?= htmlspecialchars(base_url('')) ?>">Beranda</a>
        <a class="block px-3 py-2 rounded-lg hover:bg-slate-100" href="<?= htmlspecialchars(base_url('services')) ?>">Jasa & Sewa</a>
        <?php if (get_setting('checkout_mode', 'cart') === 'cart'): ?>
          <a class="block px-3 py-2 rounded-lg hover:bg-slate-100" href="<?= htmlspecialchars(base_url('cart')) ?>">Keranjang</a>
        <?php endif; ?>
        <?php if (admin_logged_in()): ?>
          <a class="block px-3 py-2 rounded-lg hover:bg-slate-100" href="<?= htmlspecialchars(base_url('admin/index.php')) ?>">Dashboard</a>
        <?php else: ?>
          <a class="block px-3 py-2 rounded-lg hover:bg-slate-100" href="<?= htmlspecialchars(base_url('admin/login.php')) ?>">Admin</a>
        <?php endif; ?>
      </div>
    </div>
  </header>
  <main class="min-h-[70vh] bg-gradient-to-b from-white to-slate-50 dark:from-slate-900 dark:to-slate-950 text-slate-800 dark:text-slate-100 relative">
    <!-- Decorative animated orbs -->
    <div class="pointer-events-none absolute -z-10 right-10 top-20 h-40 w-40 rounded-full bg-gradient-to-tr from-primary to-neon opacity-20 blur-3xl animate-floaty"></div>
    <div class="pointer-events-none absolute -z-10 left-10 bottom-20 h-32 w-32 rounded-full bg-gradient-to-tr from-neon to-primary opacity-20 blur-3xl animate-floaty" style="animation-delay: -2s"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
