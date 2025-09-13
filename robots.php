<?php
// robots.php - Dynamic robots.txt
require_once __DIR__ . '/includes/util.php';

header('Content-Type: text/plain; charset=utf-8');
$site = rtrim(base_url(''), '/');
$sitemapUrl = $site . '/sitemap.xml';

echo "User-agent: *\n";
echo "Disallow: /admin/\n";
echo "Allow: /\n\n";
echo "Sitemap: ${sitemapUrl}\n";
