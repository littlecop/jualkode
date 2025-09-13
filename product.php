<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/util.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare('SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.id = ? AND p.is_active = 1');
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) {
    http_response_code(404);
}
// If coming from legacy URL and product exists, redirect to SEO URL
if ($product && isset($_GET['redirect'])) {
    $seo = product_url((int)$product['id'], (string)$product['title']);
    header('Location: ' . $seo, true, 301);
    exit;
}

// SEO variables
if ($product) {
  $titleText = (string)$product['title'];
  $descText = isset($product['description']) ? trim(strip_tags((string)$product['description'])) : '';
  if (mb_strlen($descText) > 160) { $descText = mb_substr($descText, 0, 157) . '...'; }
  $pageTitle = $titleText . ' - Store Code Market';
  $metaDescription = $descText !== '' ? $descText : 'Detail produk di Store Code Market.';
  $canonical = product_url((int)$product['id'], (string)$product['title']);
  $ogImage = !empty($product['image']) ? base_url('uploads/' . $product['image']) : null;
  // Pre-calc aggregate rating for JSON-LD
  $avgRatingHead = null; $reviewsCountHead = 0;
  try {
    $rstmt0 = $pdo->prepare('SELECT AVG(rating) AS avg_rating, COUNT(*) AS cnt FROM reviews WHERE product_id = ?');
    $rstmt0->execute([(int)$product['id']]);
    $rdata0 = $rstmt0->fetch();
    $avgRatingHead = ($rdata0 && $rdata0['avg_rating'] !== null) ? round((float)$rdata0['avg_rating'], 1) : null;
    $reviewsCountHead = (int)($rdata0['cnt'] ?? 0);
  } catch (Throwable $e) { /* ignore */ }
  // JSON-LD Product and Breadcrumb
  $jsonProduct = [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $titleText,
    'image' => $ogImage ? [$ogImage] : [],
    'description' => $descText,
    'sku' => (string)$product['id'],
    'category' => $product['category_name'] ?? 'Umum',
    'offers' => [
      '@type' => 'Offer',
      'priceCurrency' => 'IDR',
      'price' => (string)(int)$product['price'],
      'availability' => 'https://schema.org/InStock',
      'url' => $canonical
    ]
  ];
  if ($reviewsCountHead > 0 && $avgRatingHead !== null) {
    $jsonProduct['aggregateRating'] = [
      '@type' => 'AggregateRating',
      'ratingValue' => (string)$avgRatingHead,
      'reviewCount' => (string)$reviewsCountHead
    ];
  }
  $crumbs = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
      [
        '@type' => 'ListItem',
        'position' => 1,
        'name' => 'Beranda',
        'item' => base_url('')
      ],
      [
        '@type' => 'ListItem',
        'position' => 2,
        'name' => $product['category_name'] ?? 'Umum',
        'item' => base_url('index.php?category_id=' . (int)($product['category_id'] ?? 0))
      ],
      [
        '@type' => 'ListItem',
        'position' => 3,
        'name' => $titleText,
        'item' => $canonical
      ]
    ]
  ];
  $extraHead = '<script type="application/ld+json">' . json_encode($jsonProduct, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . '</script>'
           . "\n" . '<script type="application/ld+json">' . json_encode($crumbs, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . '</script>';
} else {
  $pageTitle = 'Produk tidak ditemukan - Store Code Market';
  $metaDescription = 'Produk yang Anda cari tidak ditemukan.';
  $canonical = base_url('product.php?id=' . (int)$id);
}
include __DIR__ . '/includes/header.php';
?>
<?php if (!$product): ?>
  <div class="text-center py-24">
    <h1 class="text-3xl font-semibold">Produk tidak ditemukan</h1>
    <p class="mt-2 text-slate-600">Item yang Anda cari mungkin telah dihapus.</p>
  </div>
<?php else: ?>
  <?php $isNew = isset($product['created_at']) ? (strtotime($product['created_at']) >= (time() - 14*24*3600)) : false; ?>
  <?php
    // Build gallery images from product_images table (fallback to main image only)
    $gallery = [];
    if (!empty($product['image'])) { $gallery[] = $product['image']; }
    $gstmt = $pdo->prepare('SELECT image FROM product_images WHERE product_id = ? ORDER BY sort_order, id');
    $gstmt->execute([(int)$product['id']]);
    foreach ($gstmt->fetchAll() as $gi) {
      if (!empty($gi['image'])) $gallery[] = $gi['image'];
    }
    // Limit gallery size (1 main + up to 6 thumbs)
    if (count($gallery) > 7) { $gallery = array_slice($gallery, 0, 7); }

    // Reviews aggregate
    $rstmt = $pdo->prepare('SELECT AVG(rating) AS avg_rating, COUNT(*) AS cnt FROM reviews WHERE product_id = ?');
    $rstmt->execute([(int)$product['id']]);
    $rdata = $rstmt->fetch();
    $avgRating = ($rdata && $rdata['avg_rating'] !== null) ? round((float)$rdata['avg_rating'], 1) : null;
    $reviewsCount = (int)($rdata['cnt'] ?? 0);
  ?>
  <!-- Breadcrumb / Back link -->
  <div class="mb-6 flex items-center gap-2 text-sm text-slate-500">
    <a href="<?= htmlspecialchars(base_url('')) ?>" class="hover:text-slate-700">Beranda</a>
    <span>/</span>
    <a href="<?= htmlspecialchars(base_url('?category_id=' . (int)($product['category_id'] ?? 0))) ?>" class="hover:text-slate-700"><?= htmlspecialchars($product['category_name'] ?? 'Umum') ?></a>
    <span>/</span>
    <span class="text-slate-700 line-clamp-1"><?= htmlspecialchars($product['title']) ?></span>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
    <!-- Left: Media Card -->
    <div class="lg:col-span-3">
      <div class="card-tilt relative rounded-2xl border border-slate-200/80 bg-white/90 glass neon-border overflow-hidden shadow-sm">
        <div class="aspect-video relative group">
          <?php if (!empty($product['image'])): ?>
            <img id="mainImage" src="<?= htmlspecialchars(base_url('uploads/' . $product['image'])) ?>" alt="<?= htmlspecialchars($product['title']) ?>" loading="eager" fetchpriority="high" decoding="async" class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
          <?php else: ?>
            <div class="absolute inset-0 grid place-items-center text-slate-400">No Image</div>
          <?php endif; ?>
          <div class="absolute left-3 top-3 flex gap-2">
            <span class="px-2 py-1 text-[11px] rounded-full bg-white/85 backdrop-blur border text-slate-700"><?= htmlspecialchars($product['category_name'] ?? 'Umum') ?></span>
            <?php if ($isNew): ?><span class="px-2 py-1 text-[11px] rounded-full bg-emerald-500/90 text-white">New</span><?php endif; ?>
          </div>
          <div class="absolute -right-10 -bottom-10 h-28 w-28 rounded-full bg-gradient-to-tr from-primary to-neon opacity-25 blur-3xl"></div>
        </div>
      </div>
      <?php if (!empty($gallery) && count($gallery) > 1): ?>
        <div class="mt-3 flex gap-3 overflow-x-auto pb-1">
          <?php foreach (array_slice($gallery, 0) as $idx => $g): ?>
            <button type="button" class="thumb-btn group relative shrink-0 h-16 w-24 rounded-lg border <?= $idx === 0 ? 'border-primary' : 'border-slate-200' ?> overflow-hidden hover:border-primary focus:outline-none" data-src="<?= htmlspecialchars(base_url('uploads/' . $g)) ?>">
              <img src="<?= htmlspecialchars(base_url('uploads/' . $g)) ?>" alt="thumb" loading="lazy" decoding="async" class="h-full w-full object-cover group-hover:scale-105 transition-transform"/>
            </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <!-- Info highlights -->
      <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="rounded-xl border border-slate-200 bg-white p-4 flex items-center gap-3">
          <svg class="h-5 w-5 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 1.343-3 3v7h6v-7c0-1.657-1.343-3-3-3z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 21h14"/></svg>
          <div>
            <div class="text-sm font-medium">Lisensi komersial</div>
            <div class="text-xs text-slate-500">Bebas pakai untuk project klien</div>
          </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 flex items-center gap-3">
          <svg class="h-5 w-5 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 6h10M7 14h10M3 18h18"/></svg>
          <div>
            <div class="text-sm font-medium">Kode rapi</div>
            <div class="text-xs text-slate-500">Struktur jelas & mudah dikembangkan</div>
          </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 flex items-center gap-3">
          <svg class="h-5 w-5 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3"/><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" fill="none"/></svg>
          <div>
            <div class="text-sm font-medium">Dukungan update</div>
            <div class="text-xs text-slate-500">Perbaikan bug minor</div>
          </div>
        </div>
      </div>
      <!-- Description panel (desktop/laptop only) -->
      <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4 hidden lg:block">
        <div class="text-sm font-semibold mb-2">Deskripsi</div>
        <div class="prose prose-sm max-w-none text-slate-700 text-sm product-description">
          <?php
            $desc = (string)($product['description'] ?? '');
            // Allow comprehensive HTML tags for rich content
            $allowed = '<p><br><strong><b><em><i><u><s><strike><del><ins><a><ul><ol><li><blockquote><code><pre><h1><h2><h3><h4><h5><h6><table><thead><tbody><tfoot><tr><th><td><div><span><img><hr><sub><sup><small><mark><abbr><cite><q><kbd><samp><var><time><address><article><aside><details><summary><figure><figcaption><footer><header><main><nav><section><dl><dt><dd>'; 
            $clean = strip_tags($desc, $allowed);
            // Remove dangerous event handlers and attributes
            $clean = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean);
            // Remove dangerous protocols
            $clean = preg_replace('/(javascript|vbscript|data|file|about):/i', '', $clean);
            // Add safe defaults to links
            $clean = preg_replace('/<a\s+/i', '<a target="_blank" rel="noopener nofollow" ', $clean);
            // Remove style attributes that could contain malicious CSS
            $clean = preg_replace('/\sstyle\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean);
            echo $clean;
          ?>
        </div>
      </div>
    </div>

    <!-- Right: Info + CTA -->
    <div class="lg:col-span-2">
      <div class="rounded-2xl border border-slate-200/80 bg-white/90 glass neon-border p-6">
        <h1 class="text-2xl md:text-3xl font-semibold tracking-tight"><?= htmlspecialchars($product['title']) ?></h1>
        <div class="mt-2 flex items-center gap-3 text-sm text-slate-600">
          <span>Kategori: <?= htmlspecialchars($product['category_name'] ?? 'Umum') ?></span>
          <!-- Rating dynamic -->
          <span class="inline-flex items-center gap-1 text-amber-500">
            <!-- 5 stars, with 4.5 filled look -->
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.802 2.036a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.802-2.036a1 1 0 00-1.175 0l-2.802 2.036c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81H7.03a1 1 0 00.95-.69l1.07-3.292z"/></svg>
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.802 2.036a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.802-2.036a1 1 0 00-1.175 0l-2.802 2.036c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81H7.03a1 1 0 00.95-.69l1.07-3.292z"/></svg>
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.802 2.036a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.802-2.036a1 1 0 00-1.175 0l-2.802 2.036c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81H7.03a1 1 0 00.95-.69l1.07-3.292z"/></svg>
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.802 2.036a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.802-2.036a1 1 0 00-1.175 0l-2.802 2.036c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81H7.03a1 1 0 00.95-.69l1.07-3.292z"/></svg>
            <svg class="h-4 w-4 text-slate-300" viewBox="0 0 20 20" fill="currentColor"><defs><linearGradient id="half"><stop offset="50%" stop-color="currentColor"/><stop offset="50%" stop-color="#D1D5DB"/></linearGradient></defs><path fill="url(#half)" d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.802 2.036a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.802-2.036a1 1 0 00-1.175 0l-2.802 2.036c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81H7.03a1 1 0 00.95-.69l1.07-3.292z"/></svg>
            <?php if ($reviewsCount > 0): ?>
              <span class="ml-1 text-slate-600"><?= number_format($avgRating, 1) ?> • <?= (int)$reviewsCount ?> ulasan</span>
            <?php else: ?>
              <span class="ml-1 text-slate-500">Belum ada ulasan</span>
            <?php endif; ?>
          </span>
        </div>
        <div class="mt-4 text-3xl font-semibold text-primary"><?= format_price((int)$product['price']) ?></div>
        <?php
          // Determine checkout mode: 'cart' or 'wa'
          $checkoutMode = get_setting('checkout_mode', 'cart');
          $waNumber = get_setting('wa_number', '081227841755');
        ?>
        <?php if ($checkoutMode === 'wa'): ?>
          <div class="mt-6 flex flex-wrap gap-3">
            <a href="<?= htmlspecialchars(wa_order_link($product, $waNumber)) ?>" target="_blank" rel="noopener" class="flex-1 px-5 py-3 rounded-xl bg-emerald-500 text-white font-semibold hover:opacity-90 ripple">Order via WhatsApp</a>
            <?php if (!empty($product['demo_url'])): ?>
              <a href="<?= htmlspecialchars($product['demo_url']) ?>" target="_blank" rel="noopener nofollow" class="px-5 py-3 rounded-xl border font-medium hover:bg-slate-50">Lihat Demo</a>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <form class="mt-6" method="post" action="<?= htmlspecialchars(base_url('cart')) ?>">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
            <div class="flex flex-wrap gap-3">
              <button class="flex-1 px-5 py-3 rounded-xl bg-primary text-white font-semibold hover:opacity-90 ripple">Tambah ke Keranjang</button>
              <?php if (!empty($product['demo_url'])): ?>
                <a href="<?= htmlspecialchars($product['demo_url']) ?>" target="_blank" rel="noopener nofollow" class="px-5 py-3 rounded-xl border font-medium hover:bg-slate-50">Lihat Demo</a>
              <?php endif; ?>
            </div>
          </form>
        <?php endif; ?>
      </div>

      <!-- Specs / details block -->
      <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4">
          <div class="text-sm font-semibold mb-2">Detail</div>
          <ul class="text-sm text-slate-600 space-y-1 list-disc list-inside">
            <li>Kategori: <?= htmlspecialchars($product['category_name'] ?? 'Umum') ?></li>
            <li>Rilis: <?= htmlspecialchars(isset($product['created_at']) ? date('d M Y', strtotime($product['created_at'])) : '-') ?></li>
            <li>Kode: #<?= (int)$product['id'] ?></li>
          </ul>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4">
          <div class="text-sm font-semibold mb-2">Termasuk</div>
          <ul class="text-sm text-slate-600 space-y-1 list-disc list-inside">
            <li>Source code lengkap</li>
            <li>Instruksi setup singkat</li>
            <li>Lisensi pemakaian</li>
          </ul>
        </div>
        <?php
          // Load changelogs for this product
          $__changelogs = changelog_list((int)$product['id'], 6);
        ?>
        <?php if ($__changelogs): ?>
        <div class="rounded-xl border border-slate-200 bg-white p-4 md:col-span-2">
          <div class="text-sm font-semibold">Changelog</div>
          <div class="mt-3 divide-y border rounded-lg">
            <?php foreach ($__changelogs as $idx => $cl): ?>
              <?php $open = $idx === 0; $rowId = 'clx_' . (int)$cl['id']; $meta = changelog_meta((string)$cl['title']); ?>
              <div class="">
                <button type="button" class="w-full flex items-center justify-between gap-3 px-3 py-2 text-left hover:bg-slate-50" aria-controls="<?= $rowId ?>" aria-expanded="<?= $open ? 'true' : 'false' ?>" onclick="(function(btn){var el=document.getElementById('<?= $rowId ?>');var exp=btn.getAttribute('aria-expanded')==='true';btn.setAttribute('aria-expanded',(!exp).toString());if(el){el.classList.toggle('hidden');}})(this)">
                  <div class="flex items-center gap-2">
                    <span class="inline-block h-2.5 w-2.5 rounded-full <?= htmlspecialchars($meta['dotBgClass']) ?>"></span>
                    <span class="flex items-center gap-1 font-medium text-sm leading-snug line-clamp-1">
                      <span class="text-slate-700" aria-hidden="true"><?php echo $meta['iconSvg']; ?></span>
                      <?= htmlspecialchars($cl['title']) ?>
                      <?php if (!empty($meta['version'])): ?>
                        <span class="ml-2 inline-flex items-center text-[10px] px-2 py-0.5 rounded-full border <?= htmlspecialchars($meta['badgeClass']) ?>"><?= htmlspecialchars($meta['version']) ?></span>
                      <?php endif; ?>
                    </span>
                  </div>
                  <div class="flex items-center gap-2">
                    <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border"><?= htmlspecialchars(date('d M Y', strtotime($cl['created_at']))) ?></span>
                    <svg class="h-4 w-4 text-slate-500 transition-transform" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12l-4-4h8l-4 4z"/></svg>
                  </div>
                </button>
                <div id="<?= $rowId ?>" class="px-3 pb-3 <?= $open ? '' : 'hidden' ?>">
                  <?php if (!empty($cl['content'])): ?>
                    <div class="prose prose-sm max-w-none text-sm mt-2"><?php echo sanitize_changelog_html((string)$cl['content']); ?></div>
                  <?php else: ?>
                    <div class="text-xs text-slate-500 mt-1">Tidak ada detail tambahan.</div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php
    // Related products (same category, newest)
    $relStmt = $pdo->prepare('SELECT id, title, price, image, created_at FROM products WHERE is_active=1 AND category_id = ? AND id <> ? ORDER BY created_at DESC LIMIT 4');
    $relStmt->execute([(int)($product['category_id'] ?? 0), (int)$product['id']]);
    $related = $relStmt->fetchAll();
  ?>
  <!-- Mobile-only Description at bottom -->
  <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4 lg:hidden">
    <div class="text-sm font-semibold mb-2">Deskripsi</div>
    <div class="prose prose-sm max-w-none text-slate-700 text-sm product-description">
      <?php
        $desc = (string)($product['description'] ?? '');
        $allowed = '<p><br><strong><b><em><i><u><s><strike><del><ins><a><ul><ol><li><blockquote><code><pre><h1><h2><h3><h4><h5><h6><table><thead><tbody><tfoot><tr><th><td><div><span><img><hr><sub><sup><small><mark><abbr><cite><q><kbd><samp><var><time><address><article><aside><details><summary><figure><figcaption><footer><header><main><nav><section><dl><dt><dd>';
        $clean = strip_tags($desc, $allowed);
        $clean = preg_replace('/\\son[a-z]+\\s*=\\s*("[^"]*"|\'[^\']*\'|[^\\s>]+)/i', '', $clean);
        $clean = preg_replace('/(javascript|vbscript|data|file|about):/i', '', $clean);
        $clean = preg_replace('/<a\\s+/i', '<a target="_blank" rel="noopener nofollow" ', $clean);
        $clean = preg_replace('/\\sstyle\\s*=\\s*("[^"]*"|\'[^\']*\'|[^\\s>]+)/i', '', $clean);
        echo $clean;
      ?>
    </div>
  </div>
  <?php if ($related): ?>
    <div class="mt-12">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-semibold">Produk terkait</h2>
        <a href="<?= htmlspecialchars(base_url('?category_id=' . (int)($product['category_id'] ?? 0))) ?>" class="text-sm text-primary hover:underline">Lihat semua</a>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php foreach ($related as $r): ?>
          <?php $rNew = isset($r['created_at']) ? (strtotime($r['created_at']) >= (time() - 14*24*3600)) : false; ?>
          <a href="<?= htmlspecialchars(product_url((int)$r['id'], (string)$r['title'])) ?>" class="card-tilt group rounded-2xl border border-slate-200/80 bg-white/90 glass neon-border overflow-hidden hover:shadow-glow transition shadow-sm hover:-translate-y-1">
            <div class="aspect-video bg-slate-50 relative overflow-hidden">
              <?php if (!empty($r['image'])): ?>
                <img src="<?= htmlspecialchars(base_url('uploads/' . $r['image'])) ?>" alt="<?= htmlspecialchars($r['title']) ?>" loading="lazy" decoding="async" class="absolute inset-0 h-full w-full object-cover transition transform group-hover:scale-105">
              <?php else: ?>
                <div class="absolute inset-0 grid place-items-center text-slate-400">No Image</div>
              <?php endif; ?>
              <div class="absolute left-3 top-3 flex gap-2">
                <?php if ($rNew): ?><span class="px-2 py-1 text-[11px] rounded-full bg-emerald-500/90 text-white">New</span><?php endif; ?>
              </div>
            </div>
            <div class="p-4">
              <div class="font-semibold leading-snug line-clamp-2 min-h-[2.75rem]">
                <?= htmlspecialchars($r['title']) ?>
              </div>
              <div class="mt-3 flex items-center justify-between">
                <div class="text-primary font-semibold"><?= format_price((int)$r['price']) ?></div>
                <div class="text-slate-500 text-sm">Detail →</div>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
<?php endif; ?>
<script>
  // Simple thumbnail switcher
  (function(){
    const main = document.getElementById('mainImage');
    if (!main) return;
    const buttons = document.querySelectorAll('.thumb-btn');
    buttons.forEach(btn => {
      btn.addEventListener('click', ()=>{
        const src = btn.getAttribute('data-src');
        if (!src || main.getAttribute('src') === src) return;
        // swap with fade
        main.classList.add('opacity-0');
        setTimeout(()=>{
          main.setAttribute('src', src);
          main.classList.remove('opacity-0');
        }, 120);
        // active ring
        buttons.forEach(b=> b.classList.remove('border-primary'));
        btn.classList.add('border-primary');
      });
    });
  })();
</script>
<style>
.product-description ul {
  list-style-type: disc;
  margin-left: 1.5rem;
  margin-bottom: 1rem;
}
.product-description ol {
  list-style-type: decimal;
  margin-left: 1.5rem;
  margin-bottom: 1rem;
}
.product-description li {
  margin-bottom: 0.25rem;
  padding-left: 0.25rem;
}
.product-description p {
  margin-bottom: 1rem;
}
.product-description strong, .product-description b {
  font-weight: 600;
}
.product-description em, .product-description i {
  font-style: italic;
}
.product-description u {
  text-decoration: underline;
}
.product-description s, .product-description strike, .product-description del {
  text-decoration: line-through;
}
.product-description ins {
  text-decoration: underline;
  background-color: #fef3c7;
}
.product-description mark {
  background-color: #fef3c7;
  padding: 0.125rem 0.25rem;
}
.product-description h1, .product-description h2, .product-description h3, 
.product-description h4, .product-description h5, .product-description h6 {
  font-weight: 600;
  margin-top: 1.5rem;
  margin-bottom: 0.75rem;
}
.product-description h1 { font-size: 1.5rem; }
.product-description h2 { font-size: 1.25rem; }
.product-description h3 { font-size: 1.125rem; }
.product-description h4 { font-size: 1rem; }
.product-description blockquote {
  border-left: 4px solid #e5e7eb;
  padding-left: 1rem;
  margin: 1rem 0;
  font-style: italic;
  color: #6b7280;
}
.product-description code {
  background-color: #f3f4f6;
  padding: 0.125rem 0.25rem;
  border-radius: 0.25rem;
  font-family: monospace;
  font-size: 0.875rem;
}
.product-description pre {
  background-color: #f3f4f6;
  padding: 1rem;
  border-radius: 0.5rem;
  overflow-x: auto;
  margin: 1rem 0;
}
.product-description pre code {
  background: none;
  padding: 0;
}
.product-description table {
  width: 100%;
  border-collapse: collapse;
  margin: 1rem 0;
}
.product-description th, .product-description td {
  border: 1px solid #e5e7eb;
  padding: 0.5rem;
  text-align: left;
}
.product-description th {
  background-color: #f9fafb;
  font-weight: 600;
}
.product-description img {
  max-width: 100%;
  height: auto;
  border-radius: 0.5rem;
  margin: 1rem 0;
}
.product-description hr {
  border: none;
  border-top: 1px solid #e5e7eb;
  margin: 1.5rem 0;
}
.product-description a {
  color: #3b82f6;
  text-decoration: underline;
}
.product-description a:hover {
  color: #1d4ed8;
}
.product-description sub {
  vertical-align: sub;
  font-size: 0.75rem;
}
.product-description sup {
  vertical-align: super;
  font-size: 0.75rem;
}
.product-description small {
  font-size: 0.875rem;
}
.product-description dl {
  margin: 1rem 0;
}
.product-description dt {
  font-weight: 600;
  margin-top: 0.5rem;
}
.product-description dd {
  margin-left: 1rem;
  margin-bottom: 0.5rem;
}
</style>
<?php include __DIR__ . '/includes/footer.php'; ?>
