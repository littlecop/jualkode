<?php
// sitemap.php - Dynamic sitemap generator
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/util.php';

header('Content-Type: application/xml; charset=utf-8');
$site = rtrim(base_url(''), '/');
$now  = gmdate('Y-m-d\TH:i:s\Z');

$urls = [];
// Static pages
$urls[] = [ 'loc' => $site . '/', 'changefreq' => 'daily', 'priority' => '1.0' ];
$urls[] = [ 'loc' => $site . '/services', 'changefreq' => 'weekly', 'priority' => '0.7' ];

// Categories (if any)
try {
  $cats = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
  foreach ($cats as $c) {
    $urls[] = [
      'loc' => $site . '/?category_id=' . (int)$c['id'],
      'changefreq' => 'daily',
      'priority' => '0.6'
    ];
  }
} catch (Throwable $e) { /* ignore */ }

// Products
try {
  $stmt = $pdo->query('SELECT id, title, updated_at, created_at FROM products WHERE is_active=1 ORDER BY id DESC');
  foreach ($stmt->fetchAll() as $p) {
    $loc = product_url((int)$p['id'], (string)$p['title']);
    $lastmod = !empty($p['updated_at'] ?? null) ? gmdate('Y-m-d\TH:i:s\Z', strtotime($p['updated_at'])) : (!empty($p['created_at'] ?? null) ? gmdate('Y-m-d\TH:i:s\Z', strtotime($p['created_at'])) : $now);
    $urls[] = [ 'loc' => $loc, 'lastmod' => $lastmod, 'changefreq' => 'weekly', 'priority' => '0.8' ];
  }
} catch (Throwable $e) {
  // Fallback query without updated_at if column not exists
  try {
    $stmt = $pdo->query('SELECT id, title, created_at FROM products WHERE is_active=1 ORDER BY id DESC');
    foreach ($stmt->fetchAll() as $p) {
      $loc = product_url((int)$p['id'], (string)$p['title']);
      $lastmod = !empty($p['created_at'] ?? null) ? gmdate('Y-m-d\TH:i:s\Z', strtotime($p['created_at'])) : $now;
      $urls[] = [ 'loc' => $loc, 'lastmod' => $lastmod, 'changefreq' => 'weekly', 'priority' => '0.8' ];
    }
  } catch (Throwable $e2) { /* ignore */ }
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
  echo "  <url>\n";
  echo '    <loc>' . htmlspecialchars($u['loc'], ENT_XML1) . "</loc>\n";
  if (!empty($u['lastmod'] ?? '')) echo '    <lastmod>' . $u['lastmod'] . "</lastmod>\n";
  if (!empty($u['changefreq'] ?? '')) echo '    <changefreq>' . $u['changefreq'] . "</changefreq>\n";
  if (!empty($u['priority'] ?? '')) echo '    <priority>' . $u['priority'] . "</priority>\n";
  echo "  </url>\n";
}
echo "</urlset>\n";
