<?php
// config/db.php
session_start();

$DB_HOST = getenv('DB_HOST') ?: '127.0.0.1';
$DB_NAME = getenv('DB_NAME') ?: 'store_db';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';
$DB_CHARSET = 'utf8mb4';

$dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=$DB_CHARSET";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (Throwable $e) {
    http_response_code(500);
    echo '<h1>Database Connection Error</h1>';
    echo '<p>Please check your DB credentials in <code>config/db.php</code>.</p>';
    exit;
}

function base_url(string $path = ''): string {
    // Compute base path relative to DOCUMENT_ROOT so it is stable across nested routes
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost'; // may include port

    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    $projectRoot = dirname(__DIR__); // filesystem path to app root

    $docRootReal = $docRoot ? realpath($docRoot) : false;
    $projRootReal = realpath($projectRoot);

    $docRootReal = $docRootReal ? str_replace('\\', '/', rtrim($docRootReal, '/')) : '';
    $projRootReal = $projRootReal ? str_replace('\\', '/', rtrim($projRootReal, '/')) : '';

    $basePath = '/';
    if ($docRootReal && $projRootReal && str_starts_with($projRootReal, $docRootReal)) {
        $rel = substr($projRootReal, strlen($docRootReal));
        $rel = '/' . ltrim($rel, '/');
        $basePath = rtrim($rel, '/') . '/';
    }

    $base = $protocol . $host . $basePath;
    return $base . ltrim($path, '/');
}
