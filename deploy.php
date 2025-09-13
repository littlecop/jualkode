<?php
// ==== CONFIG ====
$secret   = "G@m@techn0123"; // samakan dengan secret di GitHub webhook
$repoDir  = "/home/jualkode/repositories/jualkode"; // path repo di server cPanel
$branch   = "main"; // branch default
$webRoot  = "/home/jualkode/public_html"; // lokasi website
$logFile  = __DIR__ . "/deploy.log"; // file log di lokasi deploy.php

// ==== START LOG ====
file_put_contents($logFile, "===== Deploy run at ".date("Y-m-d H:i:s")." =====\n", FILE_APPEND);

// ==== CEK SIGNATURE ====
$headers = getallheaders();
$hubSignature = $headers['X-Hub-Signature-256'] ?? '';

$payload = file_get_contents('php://input');
$hash = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (!hash_equals($hash, $hubSignature)) {
    http_response_code(403);
    file_put_contents($logFile, "Invalid signature!\n", FILE_APPEND);
    exit("Invalid signature!");
}

// ==== DECODE EVENT ====
$data = json_decode($payload, true);
if (!$data) {
    http_response_code(400);
    file_put_contents($logFile, "Invalid payload!\n", FILE_APPEND);
    exit("Invalid payload!");
}

// Pastikan hanya event push di branch yg sesuai
if (($data['ref'] ?? '') !== "refs/heads/$branch") {
    file_put_contents($logFile, "Not target branch, skipping...\n", FILE_APPEND);
    exit("Not target branch, skipping...");
}

// ==== JALANKAN DEPLOY ====
$commands = [
    "whoami",
    "which git",
    "cd $repoDir && git status",
    "cd $repoDir && git reset --hard",
    "cd $repoDir && git pull origin $branch",
    // copy semua file kecuali .git
    "rsync -av --exclude='.git' $repoDir/ $webRoot/"
];

foreach ($commands as $cmd) {
    $result = shell_exec($cmd . " 2>&1");
    file_put_contents($logFile, "\n$ ".$cmd."\n".$result."\n", FILE_APPEND);
}

// ==== SELESAI ====
file_put_contents($logFile, "===== Deploy finished =====\n\n", FILE_APPEND);

echo "Deploy success, check deploy.log for details.";
