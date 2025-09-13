<?php
// ==== CONFIG ====
$secret   = "G@m@techn0123"; // samakan dengan secret yang kamu isi di GitHub webhook
$repoDir  = "/home/jualkode/repositories/jualkode"; // path repo di server cPanel
$branch   = "main"; // branch default

// ==== CEK SIGNATURE ====
$headers = getallheaders();
$hubSignature = $headers['X-Hub-Signature-256'] ?? '';

$payload = file_get_contents('php://input');
$hash = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (!hash_equals($hash, $hubSignature)) {
    http_response_code(403);
    echo "Invalid signature!";
    exit;
}

// ==== DECODE EVENT ====
$data = json_decode($payload, true);
if (!$data) {
    http_response_code(400);
    echo "Invalid payload!";
    exit;
}

// Pastikan hanya event push di branch yg sesuai
if (($data['ref'] ?? '') !== "refs/heads/$branch") {
    echo "Not target branch, skipping...";
    exit;
}

// ==== JALANKAN DEPLOY ====
$commands = [
    "cd $repoDir",
    "git reset --hard",
    "git pull origin $branch",
    "cp -r $repoDir/* /home/jualkode/public_html/"
];

$output = [];
foreach ($commands as $cmd) {
    $output[] = shell_exec($cmd . ' 2>&1');
}

echo "Deploy finished:\n" . implode("\n", $output);
