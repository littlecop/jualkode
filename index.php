<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/util.php';

// Ambil query params
$q = trim($_GET['q'] ?? '');
$category_id = (int)($_GET['category_id'] ?? 0);
$sort = $_GET['sort'] ?? 'newest'; // newest | price_asc | price_desc | best
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;
$isAjax = isset($_GET['ajax']);

// Ambil kategori untuk filter
$cats = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

// Query produk aktif dengan optional search & filter kategori
// Tambah sales_count dan flag is_new untuk badge tanpa GROUP BY
$sql = "SELECT p.*, c.name AS category_name,
        (SELECT COALESCE(SUM(oi.quantity),0) FROM order_items oi WHERE oi.product_id = p.id) AS sales_count,
        (p.created_at >= (NOW() - INTERVAL 14 DAY)) AS is_new
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE p.is_active = 1";
$params = [];

if ($q !== '') {
    $sql .= " AND p.title LIKE ?";
    $params[] = '%' . $q . '%';
}
if ($category_id) {
    $sql .= " AND p.category_id = ?";
    $params[] = $category_id;
}

$order = " ORDER BY p.created_at DESC, p.id DESC";
if ($sort === 'price_asc') $order = " ORDER BY p.price ASC, p.id DESC";
elseif ($sort === 'price_desc') $order = " ORDER BY p.price DESC, p.id DESC";
elseif ($sort === 'best') $order = " ORDER BY sales_count DESC, p.created_at DESC";

$sqlCount = "SELECT COUNT(*) FROM products p WHERE p.is_active=1" . ($q !== '' ? " AND p.title LIKE ?" : '') . ($category_id ? " AND p.category_id = ?" : '');
$stmtCount = $pdo->prepare($sqlCount);
$stmtCount->execute($params);
$totalRows = (int)$stmtCount->fetchColumn();
$totalPages = (int)ceil($totalRows / $perPage);

$sql .= $order . " LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

if ($isAjax) {
    // Return JSON: { html: "...cards...", hasMore: bool, nextPage: int }
    ob_start();
    foreach ($products as $p) {
        $cardUrl = htmlspecialchars(product_url((int)$p['id'], (string)$p['title']));
        $img = !empty($p['image']) ? htmlspecialchars(base_url('uploads/' . $p['image'])) : '';
        $cat = htmlspecialchars($p['category_name'] ?? 'Umum');
        $title = htmlspecialchars($p['title']);
        $price = format_price((int)$p['price']);
        $isNew = (int)$p['is_new'] === 1;
        $isBest = ((int)$p['sales_count']) >= 5;
        echo '<a href="' . $cardUrl . '" class="card-tilt group rounded-2xl border border-slate-200/80 bg-white/90 glass neon-border overflow-hidden hover:shadow-glow transition shadow-sm hover:-translate-y-1">';
        echo '  <div class="aspect-video bg-slate-50 relative overflow-hidden">';
        if ($img) {
            echo '    <img src="' . $img . '" alt="' . $title . '" loading="lazy" decoding="async" class="absolute inset-0 h-full w-full object-cover transition transform group-hover:scale-105">';
        } else {
            echo '    <div class="absolute inset-0 grid place-items-center text-slate-400">No Image</div>';
        }
        echo '    <div class="absolute left-3 top-3 flex gap-2">';
        echo '      <span class="px-2 py-1 text-[11px] rounded-full bg-white/80 backdrop-blur border text-slate-700">' . $cat . '</span>';
        if ($isNew) echo '      <span class="px-2 py-1 text-[11px] rounded-full bg-emerald-500/90 text-white">New</span>';
        if ($isBest) echo '      <span class="px-2 py-1 text-[11px] rounded-full bg-amber-500/90 text-white">Best Seller</span>';
        echo '    </div>';
        echo '    <div class="absolute -right-8 -bottom-8 h-24 w-24 rounded-full bg-gradient-to-tr from-primary to-neon opacity-20 blur-2xl"></div>';
        echo '  </div>';
        echo '  <div class="p-4">';
        echo '    <div class="font-semibold leading-snug line-clamp-2 min-h-[2.75rem]">' . $title . '</div>';
        echo '    <div class="mt-3 flex items-center justify-between">';
        echo '      <div class="text-primary font-semibold">' . $price . '</div>';
        echo '      <div class="text-slate-500 text-sm">Detail →</div>';
        echo '    </div>';
        echo '  </div>';
        echo '</a>';
    }
    $html = ob_get_clean();
    $hasMore = $page < $totalPages;
    header('Content-Type: application/json');
    echo json_encode(['html' => $html, 'hasMore' => $hasMore, 'nextPage' => $page + 1]);
    exit;
}

// SEO variables for listing page
$pageTitle = 'Store Code Market';
if ($q !== '') {
  $pageTitle = "Cari '" . $q . "' - Store Code Market";
} elseif ($category_id) {
  $catName = '';
  foreach ($cats as $c) { if ((int)$c['id'] === $category_id) { $catName = (string)$c['name']; break; } }
  if ($catName !== '') { $pageTitle = 'Kategori ' . $catName . ' - Store Code Market'; }
}
$metaDescription = 'Temukan dan beli source code berkualitas: web app, mobile, UI kit, dan otomasi di Store Code Market.';
// Build canonical URL (keep relevant filters; omit page when 1)
$canonParams = [];
if ($q !== '') $canonParams['q'] = $q;
if ($category_id) $canonParams['category_id'] = (string)$category_id;
if ($sort && $sort !== 'newest') $canonParams['sort'] = $sort;
if ($page > 1) $canonParams['page'] = (string)$page;
$canonical = base_url('index.php' . (empty($canonParams) ? '' : ('?' . http_build_query($canonParams))));

include __DIR__ . '/includes/header.php';
?>
<section class="relative overflow-hidden rounded-3xl border border-slate-200/80 glass neon-border p-8 md:p-12 mb-8">
  <div class="relative z-10 max-w-3xl">
    <h1 class="text-3xl md:text-4xl font-semibold tracking-tight">
      Marketplace Source Code
    </h1>
    <p class="mt-2 text-slate-600">
      Temukan source code berkualitas: web app, mobile, UI kit, dan otomasi. Modern, ringan, dan siap pakai.
    </p>
    <form id="filterForm" method="get" class="mt-6 grid grid-cols-1 md:grid-cols-8 gap-3">
      <div class="md:col-span-3">
        <input
          type="text"
          name="q"
          value="<?= htmlspecialchars($q) ?>"
          placeholder="Cari judul source code..."
          class="w-full px-4 py-3 rounded-xl border focus:outline-none focus:ring-2 focus:ring-primary"
        >
      </div>
      <div class="md:col-span-2">
        <select
          name="category_id"
          class="w-full px-4 py-3 rounded-xl border bg-white focus:outline-none focus:ring-2 focus:ring-primary"
        >
          <option value="0">Semua Kategori</option>
          <?php foreach ($cats as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $category_id == $c['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="md:col-span-2">
        <select name="sort" class="w-full px-4 py-3 rounded-xl border bg-white focus:outline-none focus:ring-2 focus:ring-primary">
          <option value="newest" <?= $sort==='newest'?'selected':'' ?>>Terbaru</option>
          <option value="price_asc" <?= $sort==='price_asc'?'selected':'' ?>>Harga Termurah</option>
          <option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Harga Termahal</option>
          <option value="best" <?= $sort==='best'?'selected':'' ?>>Best Seller</option>
        </select>
      </div>
      <div class="md:col-span-1">
        <button class="w-full px-4 py-3 rounded-xl bg-primary text-white font-medium hover:opacity-90 ripple">
          Cari
        </button>
      </div>
    </form>
  </div>
  <!-- animated gradient sheen -->
  <div class="absolute inset-0 opacity-20 animate-shimmer" style="background-image: linear-gradient(120deg, rgba(99,102,241,0.12), rgba(34,211,238,0.12) 50%, rgba(255,255,255,0.1)); background-size: 200% 200%;"></div>
  <div class="absolute -right-16 -top-16 h-56 w-56 rounded-full bg-gradient-to-tr from-primary to-neon opacity-30 blur-3xl"></div>
</section>

<?php if (!$products): ?>
  <div class="text-center py-16">
    <div class="text-2xl font-semibold">Tidak ada produk ditemukan</div>
    <p class="mt-2 text-slate-600">Coba ubah kata kunci atau kategori.</p>
  </div>
<?php else: ?>
  <div id="productGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" data-page="<?= (int)$page ?>" data-total-pages="<?= (int)$totalPages ?>">
    <?php foreach ($products as $p): ?>
      <?php
        $isNew = (strtotime($p['created_at']) >= (time() - 14*24*3600));
        $salesCount = (int)($p['sales_count'] ?? 0);
        $isBest = $salesCount >= 5;
      ?>
      <a href="<?= htmlspecialchars(product_url((int)$p['id'], (string)$p['title'])) ?>"
         class="card-tilt group rounded-2xl border border-slate-200/80 bg-white/90 glass neon-border overflow-hidden hover:shadow-glow transition shadow-sm hover:-translate-y-1">
        <div class="aspect-video bg-slate-50 relative overflow-hidden">
          <?php if (!empty($p['image'])): ?>
            <img
              src="<?= htmlspecialchars(base_url('uploads/' . $p['image'])) ?>"
              alt="<?= htmlspecialchars($p['title']) ?>"
              loading="lazy" decoding="async"
              class="absolute inset-0 h-full w-full object-cover transition transform group-hover:scale-105"
            >
          <?php else: ?>
            <div class="absolute inset-0 grid place-items-center text-slate-400">No Image</div>
          <?php endif; ?>
          <div class="absolute left-3 top-3 flex gap-2">
            <span class="px-2 py-1 text-[11px] rounded-full bg-white/80 backdrop-blur border text-slate-700"><?= htmlspecialchars($p['category_name'] ?? 'Umum') ?></span>
            <?php if ($isNew): ?><span class="px-2 py-1 text-[11px] rounded-full bg-emerald-500/90 text-white">New</span><?php endif; ?>
            <?php if ($isBest): ?><span class="px-2 py-1 text-[11px] rounded-full bg-amber-500/90 text-white">Best Seller</span><?php endif; ?>
          </div>
          <div class="absolute -right-8 -bottom-8 h-24 w-24 rounded-full bg-gradient-to-tr from-primary to-neon opacity-20 blur-2xl"></div>
        </div>
        <div class="p-4">
          <div class="text-xs text-slate-500 mb-1">
            <?= htmlspecialchars($p['category_name'] ?? 'Umum') ?>
          </div>
          <div class="font-semibold leading-snug line-clamp-2 min-h-[2.75rem]">
            <?= htmlspecialchars($p['title']) ?>
          </div>
          <div class="mt-3 flex items-center justify-between">
            <div class="text-primary font-semibold">
              <?= format_price((int)$p['price']) ?>
            </div>
            <div class="text-slate-500 text-sm">Detail →</div>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
  <?php if ($page < $totalPages): ?>
    <div id="loadMoreWrap" class="mt-8 text-center">
      <button id="loadMoreBtn" class="px-4 py-2 rounded-lg border hover:bg-slate-50 ripple">Muat lagi</button>
    </div>
  <?php endif; ?>
<?php endif; ?>

<script>
// Infinite scroll for product grid
(function(){
  const grid = document.getElementById('productGrid');
  if (!grid) return;
  const form = document.getElementById('filterForm');
  let loading = false;
  let page = parseInt(grid.dataset.page || '1', 10);
  const totalPages = parseInt(grid.dataset.totalPages || '1', 10);
  async function loadNext(){
    if (loading) return; if (page >= totalPages) return; loading = true;
    const params = new URLSearchParams(new FormData(form));
    params.set('page', String(page+1));
    params.set('ajax','1');
    try {
      const res = await fetch('<?= htmlspecialchars(base_url('index.php')) ?>?' + params.toString());
      const data = await res.json();
      if (data && data.html) {
        const tmp = document.createElement('div'); tmp.innerHTML = data.html;
        tmp.childNodes.forEach(n=> grid.appendChild(n));
        page += 1; grid.dataset.page = String(page);
        try { window.bindTilt && window.bindTilt(); } catch(_){ setTimeout(()=> window.bindTilt && window.bindTilt(), 50); }
      }
      if (!data || !data.hasMore) { window.removeEventListener('scroll', onScroll); document.getElementById('loadMoreWrap')?.remove(); }
    } catch(e) {}
    loading = false;
  }
  function nearBottom(){ return window.innerHeight + window.scrollY >= (document.body.offsetHeight - 300); }
  function onScroll(){ if (nearBottom()) loadNext(); }
  window.addEventListener('scroll', onScroll);
  document.getElementById('loadMoreBtn')?.addEventListener('click', loadNext);
})();
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>