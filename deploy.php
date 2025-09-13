<?php
// ==== CONFIG ====
$secret   = "G@m@techn0123"; 
$repoDir  = "/home/jualkode/repositories/jualkode";
$branch   = "main";
$webRoot  = "/home/jualkode/public_html";
$logFile  = __DIR__ . "/deploy.log";

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
    "cp -r $repoDir/* $webRoot/",
    "rm -rf $webRoot/.git"
];

foreach ($commands as $cmd) {
    $result = shell_exec($cmd . " 2>&1");
    file_put_contents($logFile, "\n$ ".$cmd."\n".$result."\n", FILE_APPEND);
}

file_put_contents($logFile, "===== Deploy finished =====\n\n", FILE_APPEND);

echo "Deploy success, check deploy.log for details.";
