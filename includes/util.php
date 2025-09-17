<?php
// includes/util.php

function format_price(int|float $amount): string {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// ------------------------------------------------------------
// Invoices: ensure tables
// ------------------------------------------------------------
function ensure_invoices_tables(): void {
    static $ensuredInvoices = false;
    if ($ensuredInvoices) return;
    global $pdo;
    // invoices table
    $pdo->exec("CREATE TABLE IF NOT EXISTS invoices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        invoice_number VARCHAR(100) NOT NULL,
        invoice_date DATE NOT NULL,
        due_date DATE NULL,
        from_name VARCHAR(200) NULL,
        from_email VARCHAR(200) NULL,
        from_phone VARCHAR(100) NULL,
        from_address TEXT NULL,
        bill_name VARCHAR(200) NULL,
        bill_email VARCHAR(200) NULL,
        bill_phone VARCHAR(100) NULL,
        bill_address TEXT NULL,
        notes TEXT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'Draft',
        tax_percent DECIMAL(6,2) NOT NULL DEFAULT 0,
        discount_amount INT NOT NULL DEFAULT 0,
        shipping_amount INT NOT NULL DEFAULT 0,
        subtotal INT NOT NULL DEFAULT 0,
        tax_amount INT NOT NULL DEFAULT 0,
        grand_total INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (invoice_number)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // Try to add status column if table already existed without it
    try {
        $pdo->exec("ALTER TABLE invoices ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'Draft'");
    } catch (Throwable $e) {
        // ignore if exists
    }
    // invoice_items table
    $pdo->exec("CREATE TABLE IF NOT EXISTS invoice_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        invoice_id INT NOT NULL,
        description TEXT NOT NULL,
        qty INT NOT NULL DEFAULT 1,
        price INT NOT NULL DEFAULT 0,
        line_total INT NOT NULL DEFAULT 0,
        INDEX(invoice_id),
        CONSTRAINT fk_invoice_items_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $ensuredInvoices = true;
}

/**
 * Parse changelog title to infer meta: type (add/fix/improve/other), color classes, icon svg, and version badge.
 */
function changelog_meta(string $title): array {
    $t = trim($title);
    $lower = strtolower($t);
    $type = 'other';
    if (preg_match('/^add\b/i', $t)) { $type = 'add'; }
    elseif (preg_match('/^fix\b/i', $t)) { $type = 'fix'; }
    elseif (preg_match('/^(improve|change)\b/i', $t)) { $type = 'improve'; }

    $colors = [
        'add' => ['bg' => 'bg-emerald-500', 'badge' => 'bg-emerald-100 text-emerald-700 border-emerald-200'],
        'fix' => ['bg' => 'bg-amber-500', 'badge' => 'bg-amber-100 text-amber-700 border-amber-200'],
        'improve' => ['bg' => 'bg-indigo-500', 'badge' => 'bg-indigo-100 text-indigo-700 border-indigo-200'],
        'other' => ['bg' => 'bg-slate-400', 'badge' => 'bg-slate-100 text-slate-700 border-slate-200'],
    ];
    $color = $colors[$type];

    // Version like v1, v1.2, v1.2.3
    $version = null;
    if (preg_match('/\b(v\d+(?:\.\d+){0,2})\b/i', $t, $m)) {
        $version = $m[1];
    }

    // Icons: add -> check, fix -> bug, improve -> bolt, other -> dot
    $icons = [
        'add' => '<svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 4a1 1 0 011 1v4h4a1 1 0 110 2h-4v4a1 1 0 11-2 0v-4H5a1 1 0 110-2h4V5a1 1 0 011-1z"/></svg>',
        'fix' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M19.77 7.23l-2.12-2.12-3.54 3.54-1.41-1.41 3.54-3.54-2.12-2.12L10.58 3.1 8.46 1 7.05 2.41 9.17 4.53 3 10.7V14h3.3l6.17-6.17 2.12 2.12-6.17 6.17V20h2.12l6.17-6.17 2.12 2.12 1.41-1.41-2.12-2.12 3.54-3.54z"/></svg>',
        'improve' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M11 3l1.5 5H18l-4 3 1.5 5L11 16l-4 3 1.5-5-4-3h5.5L11 3z"/></svg>',
        'other' => '<svg class="h-3 w-3" viewBox="0 0 8 8" fill="currentColor"><circle cx="4" cy="4" r="3"/></svg>',
    ];
    $icon = $icons[$type];

    return [
        'type' => $type,
        'dotBgClass' => $color['bg'],
        'badgeClass' => $color['badge'],
        'iconSvg' => $icon,
        'version' => $version,
    ];
}

function flash_set(string $key, string $msg): void {
    $_SESSION['flash'][$key] = $msg;
}

function flash_get(string $key): ?string {
    if (!empty($_SESSION['flash'][$key])) {
        $m = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $m;
    }
    return null;
}

/**
 * Convert a string to a URL-friendly slug
 */
function slugify(string $text): string {
    $text = strtolower($text);
    $text = preg_replace('~[^a-z0-9]+~', '-', $text);
    $text = trim($text, '-');
    return $text ?: 'produk';
}

/**
 * Build SEO-friendly product URL: /store/p/{id}-{slug}
 */
function product_url(int $id, string $title = ''): string {
    $slug = slugify($title);
    return base_url('p/' . $id . '-' . $slug);
}

/**
 * Settings helpers (stored in a simple key-value `settings` table)
 */
function ensure_settings_table(): void {
    static $ensured = false;
    if ($ensured) return;
    global $pdo;
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        k VARCHAR(100) PRIMARY KEY,
        v TEXT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $ensured = true;
}

function get_setting(string $key, ?string $default = null): ?string {
    ensure_settings_table();
    global $pdo;
    $stmt = $pdo->prepare('SELECT v FROM settings WHERE k = ?');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    if ($row && array_key_exists('v', $row)) {
        return (string)$row['v'];
    }
    return $default;
}

function set_setting(string $key, string $value): void {
    ensure_settings_table();
    global $pdo;
    $stmt = $pdo->prepare('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)');
    $stmt->execute([$key, $value]);
}

/**
 * Normalize phone number to WhatsApp format (Indonesia default): 08xx -> 628xx
 */
function wa_normalize_number(string $number): string {
    $digits = preg_replace('/\D+/', '', $number);
    if ($digits === null) return '';
    if (str_starts_with($digits, '0')) {
        return '62' . substr($digits, 1);
    }
    if (str_starts_with($digits, '62')) {
        return $digits;
    }
    // If starts with 8xxx assume Indonesia mobile without leading 0
    if (preg_match('/^8\d+$/', $digits)) {
        return '62' . $digits;
    }
    return $digits; // fallback as-is
}

/**
 * Build a WhatsApp order link for a product
 */
function wa_order_link(array $product, string $phoneRaw): string {
    $phone = wa_normalize_number($phoneRaw);
    $title = (string)($product['title'] ?? '');
    $id = (int)($product['id'] ?? 0);
    $price = format_price((int)($product['price'] ?? 0));
    $link = product_url($id, $title);
    $text = "Halo, saya ingin order produk:\n$title (ID: $id)\nHarga: $price\nLink: $link";
    return 'https://wa.me/' . rawurlencode($phone) . '?text=' . rawurlencode($text);
}

/**
 * Product changelog helpers
 */
function ensure_changelog_table(): void {
    static $done = false;
    if ($done) return;
    global $pdo;
    $pdo->exec("CREATE TABLE IF NOT EXISTS product_changelogs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        content TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (product_id),
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $done = true;
}

function changelog_add(int $productId, string $title, string $content = ''): void {
    ensure_changelog_table();
    global $pdo;
    $stmt = $pdo->prepare('INSERT INTO product_changelogs (product_id, title, content) VALUES (?,?,?)');
    $stmt->execute([$productId, $title, $content]);
}

function changelog_delete(int $id, int $productId): void {
    ensure_changelog_table();
    global $pdo;
    $stmt = $pdo->prepare('DELETE FROM product_changelogs WHERE id = ? AND product_id = ?');
    $stmt->execute([$id, $productId]);
}

function changelog_list(int $productId, int $limit = 10): array {
    ensure_changelog_table();
    global $pdo;
    $stmt = $pdo->prepare('SELECT id, title, content, created_at FROM product_changelogs WHERE product_id = ? ORDER BY created_at DESC, id DESC LIMIT ' . (int)$limit);
    $stmt->execute([$productId]);
    return $stmt->fetchAll() ?: [];
}

function changelog_get(int $id, int $productId): ?array {
    ensure_changelog_table();
    global $pdo;
    $stmt = $pdo->prepare('SELECT id, product_id, title, content, created_at FROM product_changelogs WHERE id = ? AND product_id = ?');
    $stmt->execute([$id, $productId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function changelog_update(int $id, int $productId, string $title, string $content = ''): void {
    ensure_changelog_table();
    global $pdo;
    $stmt = $pdo->prepare('UPDATE product_changelogs SET title = ?, content = ? WHERE id = ? AND product_id = ?');
    $stmt->execute([$title, $content, $id, $productId]);
}

function sanitize_changelog_html(string $html): string {
    // Allow simple formatting similar to product description but narrower
    $allowed = '<p><br><strong><b><em><i><u><s><a><ul><ol><li><code><pre><h3><h4><blockquote>'; 
    $clean = strip_tags($html, $allowed);
    $clean = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean);
    $clean = preg_replace('/(javascript|vbscript|data|file|about):/i', '', $clean);
    $clean = preg_replace('/<a\s+/i', '<a target="_blank" rel="noopener nofollow" ', $clean);
    $clean = preg_replace('/\sstyle\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean);
    return $clean;
}

/**
 * Compress and optionally resize an image using GD.
 * - Supports JPEG, PNG, WebP input
 * - Preserves aspect ratio, limits to maxWidth x maxHeight
 * - Always outputs JPEG with white background to handle transparency
 *
 * @param string $src Absolute path to source image (can be an upload tmp_name)
 * @param string $dest Absolute path to destination file (should end with .jpg or .jpeg)
 * @param int $maxWidth Max width in pixels
 * @param int $maxHeight Max height in pixels
 * @param int $quality JPEG quality 0-100
 * @return bool True on success, false on failure
 */
function compress_image(string $src, string $dest, int $maxWidth = 1600, int $maxHeight = 1600, int $quality = 82): bool {
    try {
        if (!is_file($src)) return false;
        $info = @getimagesize($src);
        if ($info === false) return false;
        [$width, $height] = $info;
        $mime = $info['mime'] ?? '';

        switch ($mime) {
            case 'image/jpeg':
            case 'image/pjpeg':
                $img = @imagecreatefromjpeg($src);
                break;
            case 'image/png':
                $img = @imagecreatefrompng($src);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $img = @imagecreatefromwebp($src);
                } else {
                    $img = false;
                }
                break;
            default:
                $img = false;
        }
        if (!$img) return false;

        // Compute target size
        $scale = 1.0;
        if ($width > $maxWidth || $height > $maxHeight) {
            $scale = min($maxWidth / max(1, $width), $maxHeight / max(1, $height));
        }
        $newW = max(1, (int)floor($width * $scale));
        $newH = max(1, (int)floor($height * $scale));

        // Create destination canvas with white background (for PNG/WebP transparency)
        $dst = imagecreatetruecolor($newW, $newH);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);
        imagecopyresampled($dst, $img, 0, 0, 0, 0, $newW, $newH, $width, $height);

        // Ensure destination directory exists
        $dir = dirname($dest);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $ok = @imagejpeg($dst, $dest, max(0, min(100, $quality)));
        imagedestroy($dst);
        imagedestroy($img);
        return (bool)$ok;
    } catch (Throwable $e) {
        // Avoid breaking the request; allow caller to fallback to move_uploaded_file
        return false;
    }
}
